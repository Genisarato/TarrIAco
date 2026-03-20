#!/usr/bin/env python3
"""
Construye el dataset analítico aplanado:
  1. Carga cohort como tabla base.
  2. Left Join con diagnostics y farmacs usando id_pacient.
  3. Agregaciones 1:N — cuenta visitas por paciente.
  4. Variables de laboratorio — mean y slope por prueba.
  5. Guarda el resultado en dataset_analitico.xlsx.
"""

import pandas as pd

# ── 1. Cargar las tablas ─────────────────────────────────────────────
print("Cargando cohort.xlsx ...")
cohort = pd.read_excel("cohort.xlsx")
print(f"  cohort: {cohort.shape[0]} filas, {cohort.shape[1]} columnas")

print("Cargando diagnostics.xlsx ...")
diagnostics = pd.read_excel("diagnostics.xlsx")
print(f"  diagnostics: {diagnostics.shape[0]} filas, {diagnostics.shape[1]} columnas")

print("Cargando farmacs.xlsx ...")
farmacs = pd.read_excel("farmacs.xlsx")
print(f"  farmacs: {farmacs.shape[0]} filas, {farmacs.shape[1]} columnas")

# ── 2. Left Join 1:1 ────────────────────────────────────────────────
print("\nRealizando LEFT JOIN cohort ← diagnostics (on id_pacient) ...")
df = cohort.merge(diagnostics, on="id_pacient", how="left")
print(f"  Resultado parcial: {df.shape[0]} filas, {df.shape[1]} columnas")

print("Realizando LEFT JOIN resultado ← farmacs (on id_pacient) ...")
df = df.merge(farmacs, on="id_pacient", how="left")
print(f"  Resultado parcial: {df.shape[0]} filas, {df.shape[1]} columnas")

# ── 3. Agregaciones 1:N — conteo de visitas por paciente ─────────────
visit_tables = {
    "visites_hospital.xlsx":    ("id_visita_hospital",    "num_visitas_hospital"),
    "visites_intermedia.xlsx":  ("id_visita_intermedia",  "num_visitas_intermedia"),
    "visites_primaria.xlsx":    ("id_visita_ap",          "num_visitas_primaria"),
    "visites_urgencies.xlsx":   ("id_visita_urgencies",   "num_visitas_urgencias"),
}

for file, (visit_id_col, new_col) in visit_tables.items():
    print(f"\nCargando {file} ...")
    vdf = pd.read_excel(file)
    print(f"  {file}: {vdf.shape[0]} filas")

    # Contar registros por paciente
    counts = (
        vdf.groupby("id_pacient")[visit_id_col]
        .count()
        .reset_index()
        .rename(columns={visit_id_col: new_col})
    )
    print(f"  Pacientes con visitas: {len(counts)}")

    # Left join al dataset principal (pacientes sin visitas → 0)
    df = df.merge(counts, on="id_pacient", how="left")
    df[new_col] = df[new_col].fillna(0).astype(int)
    print(f"  Columna '{new_col}' añadida (rango: {df[new_col].min()} – {df[new_col].max()})")

# ── 4. Variables de laboratorio — mean y slope por prueba ────────────
print("\nCargando laboratori.xlsx ...")
lab = pd.read_excel("laboratori.xlsx")
print(f"  laboratori: {lab.shape[0]} filas, {lab['id_pacient'].nunique()} pacientes, {lab['desc_prova_ics'].nunique()} pruebas")

# Nombre corto para cada prueba (para usar como sufijo de columna)
SHORT_NAMES = {
    "GLUCOSA-SÈRUM":                                  "glucosa",
    "UREA-SÈRUM":                                     "urea",
    "PROTEÏNA C REACTIVA (PCR)-SÈRUM":                "pcr",
    "BILIRUBINA-SÈRUM":                               "bilirubina",
    "ASPARTAT AMINOTRANSFERASA-SÈRUM":                "ast",
    "ALANINA AMINOTRANSFERASA-SÈRUM":                 "alt",
    "ALBÚMINA-SÈRUM":                                 "albumina",
    "COLESTEROL-SÈRUM":                               "colesterol",
    "FOSFATASA ALCALINA-SÈRUM":                       "fosfatasa",
    "PROTEÏNA-SÈRUM":                                 "proteina",
    "TIROTROPINA-SÈRUM":                              "tsh",
    "ERITROSEDIMENTACIÓ (VSG)-SANG":                  "vsg",
    "ÀCID FÒLIC-SÈRUM":                               "ac_folico",
    "COBALAMINES (VITAMINA B12)-SÈRUM":               "vit_b12",
    "FERRITINA-SÈRUM":                                "ferritina",
    "FERRO-SÈRUM":                                    "ferro",
    "PRO-BNP-SÈRUM":                                  "pro_bnp",
    "DÍMER D DE LA FIBRINA (IMMUNOTURBIDIMETRIA)-PLASMA": "dimero_d",
}
lab["prova_short"] = lab["desc_prova_ics"].map(SHORT_NAMES)

# Pivotar: una columna por prueba × {mean, slope}
lab_mean = lab.pivot_table(index="id_pacient", columns="prova_short", values="mean")
lab_mean.columns = [f"{c}_mean" for c in lab_mean.columns]

lab_slope = lab.pivot_table(index="id_pacient", columns="prova_short", values="slope")
lab_slope.columns = [f"{c}_slope" for c in lab_slope.columns]

lab_pivot = lab_mean.join(lab_slope).reset_index()
print(f"  Columnas de laboratorio generadas: {lab_pivot.shape[1] - 1}")

# Left join
df = df.merge(lab_pivot, on="id_pacient", how="left")
print(f"  Dataset tras añadir laboratorio: {df.shape[0]} filas, {df.shape[1]} columnas")

# ── 5. Resumen ───────────────────────────────────────────────────────
print(f"\n{'='*50}")
print(f"=== Dataset resultante ===")
print(f"  Filas:    {df.shape[0]}")
print(f"  Columnas: {df.shape[1]}")
print(f"  Columnas: {list(df.columns)}")

print(f"\n  Estadísticas de visitas:")
for col in ["num_visitas_hospital", "num_visitas_intermedia", "num_visitas_primaria", "num_visitas_urgencias"]:
    print(f"    {col}: media={df[col].mean():.2f}, max={df[col].max()}")

lab_cols = [c for c in df.columns if c.endswith("_mean") or c.endswith("_slope")]
print(f"\n  Cobertura de laboratorio (pacientes con datos):")
for col in sorted(lab_cols):
    non_null = df[col].notna().sum()
    print(f"    {col}: {non_null} ({100*non_null/len(df):.1f}%)")

print(f"\n  Valores nulos por columna (no-lab):")
nulls = df.isnull().sum()
for col in df.columns:
    if nulls[col] > 0 and col not in lab_cols:
        print(f"    {col}: {nulls[col]} nulos ({100*nulls[col]/len(df):.1f}%)")

# ── 6. Guardar ───────────────────────────────────────────────────────
output_path = "dataset_analitico.xlsx"
print(f"\nGuardando en {output_path} ...")
df.to_excel(output_path, index=False)
print(f"¡Guardado correctamente! ({output_path})")
