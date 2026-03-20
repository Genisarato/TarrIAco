import faiss
import pickle
import numpy as np
import pandas as pd
import requests

# ── Carregar tot des d'un sol fitxer ────────────────────────────
with open("../data/processed/faiss_data.pkl", "rb") as f:
    data = pickle.load(f)

index = faiss.deserialize_index(data["index"])
scaler = data["scaler"]
patient_ids = data["ids"]
feature_cols = data["features"]
encoders = data["encoders"]

df = pd.read_excel("../data/processed/dataset_analitico_clean.xlsx")
df_indexed = df.set_index("id_pacient")

# ── Consulta FAISS ───────────────────────────────────────────────
def buscar_similars(id_pacient, k=10):
    # Agafar vector del pacient
    pacient = df_indexed.loc[id_pacient]
    
    # Preparar vector (igual que al build)
    pacient_df = pd.DataFrame([pacient])
    pacient_df["sexe_encoded"] = pacient_df["sexe"].map(encoders["sexe"]).fillna(0).astype(int)
    pacient_df["situacio_encoded"] = pacient_df["situacio"].map(encoders["situacio"]).fillna(0).astype(int)
    pacient_df["cronic_encoded"] = pacient_df["cronic"].map(encoders["cronic"]).fillna(0).astype(int)
    pacient_df["edat_encoded"] = pacient_df["grup_edat"].map(encoders["grup_edat"]).fillna(3).astype(int)
    
    X = pacient_df[feature_cols].fillna(0).values.astype(np.float32)
    X_norm = np.ascontiguousarray(scaler.transform(X), dtype=np.float32)
    faiss.normalize_L2(X_norm)
    
    # Buscar
    distances, indices = index.search(X_norm, k + 1)
    
    # Treure el propi pacient si apareix
    results = []
    for dist, idx in zip(distances[0], indices[0]):
        pid = patient_ids[idx]
        if pid != id_pacient:
            results.append({
                "id_pacient": pid,
                "similitud": round(float(dist), 4),
                "cronic": df_indexed.loc[pid, "cronic"],
                "grup_edat": df_indexed.loc[pid, "grup_edat"],
                "situacio": df_indexed.loc[pid, "situacio"],
                "diags_totals": int(df_indexed.loc[pid, "diags_totals"] or 0) if pd.notna(df_indexed.loc[pid, "diags_totals"]) else 0,
                "farmacs_totals": int(df_indexed.loc[pid, "farmacs_totals"] or 0) if pd.notna(df_indexed.loc[pid, "farmacs_totals"]) else 0,
            })
        if len(results) == k:
            break
    
    return results

# ── Construir context per Ollama ─────────────────────────────────
def construir_context(id_pacient, veins):
    pacient = df_indexed.loc[id_pacient]
    
    # Agregats dels veïns
    cronics = [v["cronic"] for v in veins]
    pct_pcc  = round(cronics.count("PCC")  / len(cronics) * 100)
    pct_maca = round(cronics.count("MACA") / len(cronics) * 100)
    pct_no   = round(cronics.count("NO")   / len(cronics) * 100)
    pct_mort = round(sum(1 for v in veins if v["situacio"] == "D") / len(veins) * 100)
    
    return {
        "id_pacient": id_pacient,
        "grup_edat": pacient["grup_edat"],
        "sexe": pacient["sexe"],
        "cronic_actual": pacient["cronic"],
        "diags_totals": 0 if pd.isna(pacient.get("diags_totals")) else int(pacient["diags_totals"]),
        "farmacs_totals": 0 if pd.isna(pacient.get("farmacs_totals")) else int(pacient["farmacs_totals"]),
        "n_veins": len(veins),
        "pct_pcc": pct_pcc,
        "pct_maca": pct_maca,
        "pct_no": pct_no,
        "pct_mort_veins": pct_mort,
        "similitud_max": veins[0]["similitud"],
        "similitud_min": veins[-1]["similitud"],
    }

# ── Prompt i crida a Ollama ──────────────────────────────────────
def generar_informe(context: dict) -> str:
    prompt = f"""Ets un assistent clínic de suport a la decisió mèdica.
Analitza aquest pacient i genera un informe concís.

PACIENT (id: {context['id_pacient']}):
- Grup d'edat: {context['grup_edat']}
- Sexe: {context['sexe']}
- Diagnòstics totals: {context['diags_totals']}
- Fàrmacs totals: {context['farmacs_totals']}
- Etiqueta actual: {context['cronic_actual']}

CASOS SIMILARS ({context['n_veins']} pacients, similitud {context['similitud_max']}–{context['similitud_min']}):
- {context['pct_pcc']}% eren PCC
- {context['pct_maca']}% eren MACA
- {context['pct_no']}% eren NO crònics
- {context['pct_mort_veins']}% han mort durant el seguiment

Genera un informe amb:
1. Classificació recomanada: PCC / MACA / NO
2. Nivell de confiança: BAIX / MITJÀ / ALT
3. Justificació clínica (2-3 frases)
4. Recomanació d'acció pel metge

Sigues concís i clínic. Màxim 120 paraules. Respon en català."""

    response = requests.post(
        "http://localhost:11434/api/generate",
        json={"model": "gemma3:1b", "prompt": prompt, "stream": False}
    )
    return response.json()["response"]

# ── Funció principal ─────────────────────────────────────────────
def analitzar_pacient(id_pacient: int):
    print(f"\nAnalitzant pacient {id_pacient}...")
    
    veins = buscar_similars(id_pacient, k=10)
    context = construir_context(id_pacient, veins)
    informe = generar_informe(context)
    
    return {
        "context": context,
        "veins": veins,
        "informe": informe
    }

# ── Test ─────────────────────────────────────────────────────────
if __name__ == "__main__":
    resultat = analitzar_pacient(id_pacient=321)
    print("\n📋 INFORME OLLAMA:")
    print(resultat["informe"])