import os
import re
import joblib
import warnings
import numpy as np
import pandas as pd

from sklearn.model_selection import train_test_split, cross_val_score, StratifiedKFold
from sklearn.compose import ColumnTransformer
from sklearn.pipeline import Pipeline
from sklearn.impute import SimpleImputer
from sklearn.preprocessing import OneHotEncoder, StandardScaler
from sklearn.metrics import (
    classification_report,
    roc_auc_score,
    roc_curve,
    confusion_matrix,
    precision_recall_fscore_support
)
from sklearn.linear_model import LogisticRegression
from sklearn.ensemble import RandomForestClassifier

warnings.filterwarnings("ignore")

# =========================================================
# CONFIGURACIÓ
# =========================================================
BASE_PATH = "."   # canvia-ho si cal
RANDOM_STATE = 42
TRAIN_SIZE = 0.65
TEST_SIZE = 0.25
RESERVA_SIZE = 0.10
N_FOLDS = 5           # nombre de folds per validació creuada
TARGET_NAMES = ["NO", "PCC", "MACA"]

# Fitxers
FILE_COHORT = os.path.join(BASE_PATH, "cohort.xlsx")
FILE_FARMACS = os.path.join(BASE_PATH, "farmacs.xlsx")
FILE_DIAGS = os.path.join(BASE_PATH, "diagnostics.xlsx")
FILE_AP = os.path.join(BASE_PATH, "visites_primaria.xlsx")
FILE_URG = os.path.join(BASE_PATH, "visites_urgencies.xlsx")
FILE_HOSP = os.path.join(BASE_PATH, "visites_hospital.xlsx")
FILE_INTERM = os.path.join(BASE_PATH, "visites_intermedia.xlsx")
FILE_LAB = os.path.join(BASE_PATH, "laboratori.xlsx")

OUT_DATASET = os.path.join(BASE_PATH, "dataset_final_pcc.csv")
OUT_RESERVA = os.path.join(BASE_PATH, "reserva_pcc.csv")
OUT_TEST_PRED = os.path.join(BASE_PATH, "prediccions_test_pcc.csv")
OUT_MODEL_LOG = os.path.join(BASE_PATH, "model_logistic_pcc.joblib")
OUT_MODEL_RF = os.path.join(BASE_PATH, "model_randomforest_pcc.joblib")
OUT_FEATURES_RF = os.path.join(BASE_PATH, "importancia_variables_rf.csv")
OUT_HIGH_RISK_PCC = os.path.join(BASE_PATH, "pacients_no_alta_probabilitat_pcc.csv")
OUT_HIGH_RISK_MACA = os.path.join(BASE_PATH, "pacients_no_alta_probabilitat_maca.csv")


# =========================================================
# UTILITATS
# =========================================================
def neteja_text(x):
    """Normalitza noms de columnes/categories perquè siguin segurs."""
    if pd.isna(x):
        return "missing"
    x = str(x).strip().lower()
    x = x.replace("à", "a").replace("á", "a").replace("è", "e").replace("é", "e")
    x = x.replace("í", "i").replace("ï", "i").replace("ò", "o").replace("ó", "o")
    x = x.replace("ú", "u").replace("ü", "u").replace("ç", "c")
    x = x.replace(">", "gt").replace("<", "lt")
    x = re.sub(r"[^a-z0-9]+", "_", x)
    x = re.sub(r"_+", "_", x).strip("_")
    return x


def comprova_duplicates(df, nom):
    n_dup = df["id_pacient"].duplicated().sum()
    print(f"[{nom}] files: {len(df):,} | pacients únics: {df['id_pacient'].nunique():,} | duplicats id_pacient: {n_dup:,}")


# =========================================================
# CÀRREGA
# =========================================================
print("Carregant fitxers...")
cohort = pd.read_excel(FILE_COHORT)
farmacs = pd.read_excel(FILE_FARMACS)
diags = pd.read_excel(FILE_DIAGS)
ap = pd.read_excel(FILE_AP)
urg = pd.read_excel(FILE_URG)
hosp = pd.read_excel(FILE_HOSP)
interm = pd.read_excel(FILE_INTERM)
lab = pd.read_excel(FILE_LAB)

# =========================================================
# 1) COHORT + TARGET
# =========================================================
print("\nPreparant cohort...")

cohort = cohort.copy()
cohort["cronic"] = cohort["cronic"].astype(str).str.strip().str.upper()

# Target multiclasse:
# NO = 0, PCC = 1, MACA = 2
map_target = {
    "NO": 0,
    "PCC": 1,
    "MACA": 2
}
cohort["target"] = cohort["cronic"].map(map_target)

# eliminem possibles valors inesperats
cohort = cohort.dropna(subset=["target"]).copy()
cohort["target"] = cohort["target"].astype(int)

# guardem una còpia del label original per anàlisi, però NO l'usarem com a input
base = cohort[["id_pacient", "sexe", "situacio", "grup_edat", "target", "cronic"]].copy()

print("Distribució target (0=NO, 1=PCC, 2=MACA):")
print(base["target"].value_counts(dropna=False).sort_index())
print(base["target"].value_counts(normalize=True, dropna=False).sort_index())

# =========================================================
# 2) FARMACS (ja ve agregat per pacient)
# =========================================================
print("\nPreparant farmacs...")
farmacs = farmacs.copy()
farmacs.columns = [neteja_text(c) for c in farmacs.columns]
# assegurar nom clau
if "id_pacient" not in farmacs.columns:
    raise ValueError("No trobo la columna id_pacient a farmacs.xlsx")
comprova_duplicates(farmacs, "farmacs")

# =========================================================
# 3) DIAGNÒSTICS (ja ve agregat per pacient)
# =========================================================
print("\nPreparant diagnostics...")
diags = diags.copy()
diags.columns = [neteja_text(c) for c in diags.columns]
if "id_pacient" not in diags.columns:
    raise ValueError("No trobo la columna id_pacient a diagnostics.xlsx")
comprova_duplicates(diags, "diagnostics")

# =========================================================
# 4) VISITES PRIMÀRIA
# =========================================================
print("\nPreparant visites de primaria...")
ap = ap.copy()
ap["servei_clean"] = ap["servei"].apply(neteja_text)
ap["tipus_visita_clean"] = ap["tipus_visita"].apply(neteja_text)

# pivot servei x tipus_visita amb suma de visites
ap_pivot = ap.pivot_table(
    index="id_pacient",
    columns=["servei_clean", "tipus_visita_clean"],
    values="visites",
    aggfunc="sum",
    fill_value=0
)

ap_pivot.columns = [f"ap_{a}_{b}" for a, b in ap_pivot.columns]
ap_pivot = ap_pivot.reset_index()

# total visites AP
ap_total = ap.groupby("id_pacient", as_index=False)["visites"].sum()
ap_total = ap_total.rename(columns={"visites": "ap_visites_totals"})

# nº de serveis diferents
ap_nserveis = ap.groupby("id_pacient", as_index=False)["servei_clean"].nunique()
ap_nserveis = ap_nserveis.rename(columns={"servei_clean": "ap_n_serveis_diferents"})

# nº de tipus_visita diferents
ap_ntipus = ap.groupby("id_pacient", as_index=False)["tipus_visita_clean"].nunique()
ap_ntipus = ap_ntipus.rename(columns={"tipus_visita_clean": "ap_n_tipus_visita_diferents"})

ap_feat = ap_total.merge(ap_nserveis, on="id_pacient", how="outer")
ap_feat = ap_feat.merge(ap_ntipus, on="id_pacient", how="outer")
ap_feat = ap_feat.merge(ap_pivot, on="id_pacient", how="outer")
comprova_duplicates(ap_feat, "visites_primaria_features")

# =========================================================
# 5) VISITES URGÈNCIES
# =========================================================
print("\nPreparant urgencies...")
urg = urg.copy()
urg["nivell_triatge_clean"] = urg["nivell_triatge"].apply(neteja_text)
urg["iniciativa_clean"] = urg["iniciativa"].apply(neteja_text)

urg_total = urg.groupby("id_pacient", as_index=False).size().rename(columns={"size": "urg_total_visites"})
urg_ntr = urg.groupby("id_pacient", as_index=False)["nivell_triatge_clean"].nunique().rename(
    columns={"nivell_triatge_clean": "urg_n_nivells_triatge"}
)
urg_ninit = urg.groupby("id_pacient", as_index=False)["iniciativa_clean"].nunique().rename(
    columns={"iniciativa_clean": "urg_n_iniciatives"}
)

urg_triage = (
    urg.groupby(["id_pacient", "nivell_triatge_clean"])
    .size()
    .unstack(fill_value=0)
    .reset_index()
)
urg_triage.columns = ["id_pacient"] + [f"urg_triatge_{c}" for c in urg_triage.columns[1:]]

urg_init = (
    urg.groupby(["id_pacient", "iniciativa_clean"])
    .size()
    .unstack(fill_value=0)
    .reset_index()
)
urg_init.columns = ["id_pacient"] + [f"urg_iniciativa_{c}" for c in urg_init.columns[1:]]

urg_feat = urg_total.merge(urg_ntr, on="id_pacient", how="outer")
urg_feat = urg_feat.merge(urg_ninit, on="id_pacient", how="outer")
urg_feat = urg_feat.merge(urg_triage, on="id_pacient", how="outer")
urg_feat = urg_feat.merge(urg_init, on="id_pacient", how="outer")
comprova_duplicates(urg_feat, "urgencies_features")

# =========================================================
# 6) HOSPITAL
# =========================================================
def preparar_visites_ingres(df, prefix):
    df = df.copy()
    df["grup_dies_estada_clean"] = df["grup_dies_estada"].apply(neteja_text)
    df["grup_procedencia_clean"] = df["grup_procedencia"].apply(neteja_text)
    df["tipus_ingres_clean"] = df["tipus_ingres"].apply(neteja_text)
    df["tipus_activitat_clean"] = df["tipus_activitat"].apply(neteja_text)

    total = df.groupby("id_pacient", as_index=False).size().rename(columns={"size": f"{prefix}_total_visites"})

    p_dies = (
        df.groupby(["id_pacient", "grup_dies_estada_clean"])
        .size()
        .unstack(fill_value=0)
        .reset_index()
    )
    p_dies.columns = ["id_pacient"] + [f"{prefix}_dies_{c}" for c in p_dies.columns[1:]]

    p_proc = (
        df.groupby(["id_pacient", "grup_procedencia_clean"])
        .size()
        .unstack(fill_value=0)
        .reset_index()
    )
    p_proc.columns = ["id_pacient"] + [f"{prefix}_procedencia_{c}" for c in p_proc.columns[1:]]

    p_ing = (
        df.groupby(["id_pacient", "tipus_ingres_clean"])
        .size()
        .unstack(fill_value=0)
        .reset_index()
    )
    p_ing.columns = ["id_pacient"] + [f"{prefix}_tipus_ingres_{c}" for c in p_ing.columns[1:]]

    p_act = (
        df.groupby(["id_pacient", "tipus_activitat_clean"])
        .size()
        .unstack(fill_value=0)
        .reset_index()
    )
    p_act.columns = ["id_pacient"] + [f"{prefix}_tipus_activitat_{c}" for c in p_act.columns[1:]]

    out = total.merge(p_dies, on="id_pacient", how="outer")
    out = out.merge(p_proc, on="id_pacient", how="outer")
    out = out.merge(p_ing, on="id_pacient", how="outer")
    out = out.merge(p_act, on="id_pacient", how="outer")
    return out


print("\nPreparant hospital...")
hosp_feat = preparar_visites_ingres(hosp, "hosp")
comprova_duplicates(hosp_feat, "hospital_features")

print("\nPreparant intermedia...")
interm_feat = preparar_visites_ingres(interm, "interm")
comprova_duplicates(interm_feat, "intermedia_features")

# =========================================================
# 7) LABORATORI
# =========================================================
print("\nPreparant laboratori...")
lab = lab.copy()
lab["desc_prova_ics_clean"] = lab["desc_prova_ics"].apply(neteja_text)

# per no explotar dimensions, ens quedem amb les proves més freqüents
top_tests = lab["desc_prova_ics_clean"].value_counts().head(25).index.tolist()
lab_top = lab[lab["desc_prova_ics_clean"].isin(top_tests)].copy()

# pivot de mean
lab_mean = lab_top.pivot_table(
    index="id_pacient",
    columns="desc_prova_ics_clean",
    values="mean",
    aggfunc="mean"
)
lab_mean.columns = [f"lab_mean_{c}" for c in lab_mean.columns]
lab_mean = lab_mean.reset_index()

# pivot de slope
lab_slope = lab_top.pivot_table(
    index="id_pacient",
    columns="desc_prova_ics_clean",
    values="slope",
    aggfunc="mean"
)
lab_slope.columns = [f"lab_slope_{c}" for c in lab_slope.columns]
lab_slope = lab_slope.reset_index()

# nombre de proves diferents
lab_ntests = lab.groupby("id_pacient", as_index=False)["desc_prova_ics_clean"].nunique()
lab_ntests = lab_ntests.rename(columns={"desc_prova_ics_clean": "lab_n_proves_diferents"})

lab_feat = lab_ntests.merge(lab_mean, on="id_pacient", how="outer")
lab_feat = lab_feat.merge(lab_slope, on="id_pacient", how="outer")
comprova_duplicates(lab_feat, "laboratori_features")

# =========================================================
# 8) MERGE FINAL
# =========================================================
print("\nUnint taules...")

df = base.merge(farmacs, on="id_pacient", how="left")
df = df.merge(diags, on="id_pacient", how="left")
df = df.merge(ap_feat, on="id_pacient", how="left")
df = df.merge(urg_feat, on="id_pacient", how="left")
df = df.merge(hosp_feat, on="id_pacient", how="left")
df = df.merge(interm_feat, on="id_pacient", how="left")
df = df.merge(lab_feat, on="id_pacient", how="left")

print(f"Dataset final: {df.shape[0]:,} files x {df.shape[1]:,} columnes")

# =========================================================
# 9) VARIABLES DERIVADES
# =========================================================
print("\nCreant variables derivades...")

count_cols = [c for c in df.columns if c.endswith("_totals") or c.startswith(("ap_", "urg_", "hosp_", "interm_"))]
for c in count_cols:
    if c != "target_pcc" and pd.api.types.is_numeric_dtype(df[c]):
        df[c] = df[c].fillna(0)

# totals globals
for c in ["ap_visites_totals", "urg_total_visites", "hosp_total_visites", "interm_total_visites", "farmacs_totals", "diags_totals"]:
    if c not in df.columns:
        df[c] = 0

df["total_contactes_sanitaris"] = (
    df["ap_visites_totals"].fillna(0)
    + df["urg_total_visites"].fillna(0)
    + df["hosp_total_visites"].fillna(0)
    + df["interm_total_visites"].fillna(0)
)

df["ratio_urgencies_sobre_contactes"] = np.where(
    df["total_contactes_sanitaris"] > 0,
    df["urg_total_visites"] / df["total_contactes_sanitaris"],
    0
)

df["ratio_hospital_sobre_contactes"] = np.where(
    df["total_contactes_sanitaris"] > 0,
    df["hosp_total_visites"] / df["total_contactes_sanitaris"],
    0
)

# flags útils
df["polifarmacia_5"] = (df["farmacs_totals"].fillna(0) >= 5).astype(int)
df["polifarmacia_10"] = (df["farmacs_totals"].fillna(0) >= 10).astype(int)
df["multimorbiditat_5"] = (df["diags_totals"].fillna(0) >= 5).astype(int)
df["multimorbiditat_10"] = (df["diags_totals"].fillna(0) >= 10).astype(int)

# =========================================================
# 10) GUARDEM DATASET FINAL
# =========================================================
df.to_csv(OUT_DATASET, index=False)
print(f"\nDataset guardat a: {OUT_DATASET}")

# =========================================================
# 11) DEFINIR X / y
# =========================================================
# IMPORTANT:
# no podem usar 'cronic' com a predictor perquè és el mateix origen del target
drop_cols = ["id_pacient", "target", "cronic"]
X = df.drop(columns=[c for c in drop_cols if c in df.columns]).copy()
y = df["target"].copy()

numeric_features = X.select_dtypes(include=["int64", "float64", "int32", "float32"]).columns.tolist()
categorical_features = X.select_dtypes(include=["object", "category", "bool"]).columns.tolist()

print(f"\nVariables numèriques: {len(numeric_features)}")
print(f"Variables categòriques: {len(categorical_features)}")

# =========================================================
# 12) TRAIN / TEST / RESERVA SPLIT  (65% / 20% / 15%)
# =========================================================
# Pas 1: separem train (65%) de la resta (35%)
X_train, X_rest, y_train, y_rest, id_train, id_rest = train_test_split(
    X,
    y,
    df["id_pacient"],
    test_size=1 - TRAIN_SIZE,
    random_state=RANDOM_STATE,
    stratify=y
)

# Pas 2: de la resta (35%), separem test (20%) i reserva (15%)
#         test = 20/35 de la resta, reserva = 15/35 de la resta
X_test, X_reserva, y_test, y_reserva, id_test, id_reserva = train_test_split(
    X_rest,
    y_rest,
    id_rest,
    test_size=RESERVA_SIZE / (TEST_SIZE + RESERVA_SIZE),
    random_state=RANDOM_STATE,
    stratify=y_rest
)

print("\nSplit:")
print(f"Train:   {len(X_train):,} ({len(X_train)/len(X)*100:.1f}%)")
print(f"Test:    {len(X_test):,} ({len(X_test)/len(X)*100:.1f}%)")
print(f"Reserva: {len(X_reserva):,} ({len(X_reserva)/len(X)*100:.1f}%)")

# Guardem el conjunt de reserva per a ús futur
reserva_df = pd.concat([
    pd.Series(id_reserva.values, name="id_pacient"),
    pd.Series(y_reserva.values, name="target"),
    X_reserva.reset_index(drop=True)
], axis=1)
reserva_df.to_csv(OUT_RESERVA, index=False)
print(f"Reserva guardada a: {OUT_RESERVA}")

# =========================================================
# 13) PREPROCESSAMENT
# =========================================================
numeric_transformer = Pipeline(steps=[
    ("imputer", SimpleImputer(strategy="median")),
    ("scaler", StandardScaler())
])

categorical_transformer = Pipeline(steps=[
    ("imputer", SimpleImputer(strategy="most_frequent")),
    ("onehot", OneHotEncoder(handle_unknown="ignore"))
])

preprocessor = ColumnTransformer(
    transformers=[
        ("num", numeric_transformer, numeric_features),
        ("cat", categorical_transformer, categorical_features),
    ]
)

# =========================================================
# 14) MODELS
# =========================================================
log_model = Pipeline(steps=[
    ("preprocessor", preprocessor),
    ("model", LogisticRegression(
        max_iter=3000,
        class_weight="balanced",
        random_state=RANDOM_STATE
    ))
])

rf_model = Pipeline(steps=[
    ("preprocessor", preprocessor),
    ("model", RandomForestClassifier(
        n_estimators=400,
        max_depth=None,
        min_samples_split=10,
        min_samples_leaf=4,
        class_weight="balanced",
        random_state=RANDOM_STATE,
        n_jobs=-1
    ))
])

# =========================================================
# 15) VALIDACIÓ CREUADA (Cross-Validation)
# =========================================================
print("\n" + "="*60)
print("VALIDACIÓ CREUADA (5-Fold Stratified) — Multiclasse")
print("="*60)

cv = StratifiedKFold(n_splits=N_FOLDS, shuffle=True, random_state=RANDOM_STATE)

for nom, model_cv in [("Regressió Logística", log_model), ("Random Forest", rf_model)]:
    scores = cross_val_score(model_cv, X_train, y_train, cv=cv, scoring="roc_auc_ovr", n_jobs=-1)
    print(f"\n{nom}:")
    print(f"  AUC (OVR) per fold: {[f'{s:.4f}' for s in scores]}")
    print(f"  AUC (OVR) mitjà:   {scores.mean():.4f} ± {scores.std():.4f}")

# =========================================================
# 16) ENTRENAMENT FINAL + AVALUACIÓ MULTICLASSE
# =========================================================
def avalua_model(nom, model, X_train, X_test, y_train, y_test):
    print(f"\n==============================")
    print(f"MODEL: {nom}")
    print(f"==============================")

    model.fit(X_train, y_train)

    y_pred = model.predict(X_test)
    y_prob = model.predict_proba(X_test)

    auc = roc_auc_score(y_test, y_prob, multi_class="ovr", average="weighted")

    cm = confusion_matrix(y_test, y_pred)
    report = classification_report(y_test, y_pred, target_names=TARGET_NAMES, digits=4)

    print(f"AUC (OVR weighted): {auc:.4f}")
    print("\nConfusion matrix (files=real, cols=predicció):")
    print(f"{'':>8} pred_NO  pred_PCC  pred_MACA")
    for i, label in enumerate(TARGET_NAMES):
        print(f"{label:>8} {cm[i][0]:>7}  {cm[i][1]:>8}  {cm[i][2]:>9}")
    print("\nClassification report:")
    print(report)

    return model, y_pred, y_prob, auc


log_model, log_pred, log_prob, log_auc = avalua_model(
    "Regressio logistica", log_model, X_train, X_test, y_train, y_test
)

rf_model, rf_pred, rf_prob, rf_auc = avalua_model(
    "Random Forest", rf_model, X_train, X_test, y_train, y_test
)

# =========================================================
# 17) ESCOLLIR MILLOR MODEL
# =========================================================
if rf_auc >= log_auc:
    best_model = rf_model
    best_name = "RandomForest"
    best_pred = rf_pred
    best_prob = rf_prob
else:
    best_model = log_model
    best_name = "LogisticRegression"
    best_pred = log_pred
    best_prob = log_prob

print(f"\nMillor model segons AUC: {best_name}")

# =========================================================
# 17) GUARDAR PREDICCIONS DE TEST
# =========================================================
pred_test = pd.DataFrame({
    "id_pacient": id_test.values,
    "y_real": y_test.values,
    "y_real_nom": [TARGET_NAMES[i] for i in y_test.values],
    "pred_classe": best_pred,
    "pred_classe_nom": [TARGET_NAMES[i] for i in best_pred],
    "prob_NO": best_prob[:, 0],
    "prob_PCC": best_prob[:, 1],
    "prob_MACA": best_prob[:, 2]
}).sort_values("prob_PCC", ascending=False)

pred_test.to_csv(OUT_TEST_PRED, index=False)
print(f"Prediccions test guardades a: {OUT_TEST_PRED}")

# =========================================================
# 18) GUARDAR MODELS
# =========================================================
joblib.dump(log_model, OUT_MODEL_LOG)
joblib.dump(rf_model, OUT_MODEL_RF)
print(f"Model logístic guardat a: {OUT_MODEL_LOG}")
print(f"Model RF guardat a: {OUT_MODEL_RF}")

# =========================================================
# 19) IMPORTÀNCIA DE VARIABLES RANDOM FOREST
# =========================================================
# Recuperem noms reals de variables després del preprocessat
rf_pre = rf_model.named_steps["preprocessor"]
rf_clf = rf_model.named_steps["model"]

feature_names = rf_pre.get_feature_names_out()
importances = rf_clf.feature_importances_

imp_df = pd.DataFrame({
    "feature": feature_names,
    "importance": importances
}).sort_values("importance", ascending=False)

imp_df.to_csv(OUT_FEATURES_RF, index=False)
print(f"Importància de variables guardada a: {OUT_FEATURES_RF}")

print("\nTop 20 variables més importants (RF):")
print(imp_df.head(20).to_string(index=False))

# =========================================================
# 21) PACIENTS "NO" AMB ALTA PROBABILITAT DE SER PCC o MACA
# =========================================================
print("\n" + "="*60)
print("PACIENTS ETIQUETATS COM 'NO' PERÒ AMB ALTA PROBABILITAT PCC/MACA")
print("="*60)

# Apliquem el millor model a TOT el dataset
all_prob = best_model.predict_proba(X)

high_risk = pd.DataFrame({
    "id_pacient": df["id_pacient"].values,
    "cronic_original": df["cronic"].values,
    "target": y.values,
    "prob_NO": all_prob[:, 0],
    "prob_PCC": all_prob[:, 1],
    "prob_MACA": all_prob[:, 2]
})

# Filtrem pacients etiquetats "NO" (target=0) amb probabilitat alta de PCC
THRESHOLD_RISK = 0.40  # llindar per considerar "alt risc"

high_risk_pcc = high_risk[
    (high_risk["target"] == 0) & (high_risk["prob_PCC"] >= THRESHOLD_RISK)
].sort_values("prob_PCC", ascending=False).copy()

print(f"\nLlindar de risc: {THRESHOLD_RISK}")
print(f"Total pacients 'NO': {(high_risk['target'] == 0).sum():,}")
print(f"\n--- Candidats a PCC ---")
print(f"Pacients 'NO' amb prob_PCC >= {THRESHOLD_RISK}: {len(high_risk_pcc):,}")
if len(high_risk_pcc) > 0:
    print(high_risk_pcc.head(15).to_string(index=False))
high_risk_pcc.to_csv(OUT_HIGH_RISK_PCC, index=False)
print(f"Guardat a: {OUT_HIGH_RISK_PCC}")

# Filtrem pacients etiquetats "NO" amb probabilitat alta de MACA
high_risk_maca = high_risk[
    (high_risk["target"] == 0) & (high_risk["prob_MACA"] >= THRESHOLD_RISK)
].sort_values("prob_MACA", ascending=False).copy()

print(f"\n--- Candidats a MACA ---")
print(f"Pacients 'NO' amb prob_MACA >= {THRESHOLD_RISK}: {len(high_risk_maca):,}")
if len(high_risk_maca) > 0:
    print(high_risk_maca.head(15).to_string(index=False))
high_risk_maca.to_csv(OUT_HIGH_RISK_MACA, index=False)
print(f"Guardat a: {OUT_HIGH_RISK_MACA}")