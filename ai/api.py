#!/usr/bin/env python3
"""
API Flask per al Portal Sanitari (Versió 4.1 - Fix de Seguretat i Independència).
- Predicció V3: Autoritat matemàtica.
- FAISS: Evidència històrica.
- Ollama: Segona opinió clínica (Independent).
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import faiss
import numpy as np
import pandas as pd
import requests as http_requests
import joblib
import os
import re

app = Flask(__name__)
CORS(app)

# ── CONFIGURACIÓ DE RUTES ────────────────────────────────────────
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_CSV = os.path.join(BASE_DIR, "..", "data", "processed", "dataset_final_pcc.csv")
FAISS_PKL = os.path.join(BASE_DIR, "..", "data", "processed", "faiss_data.pkl")
MODEL_S1_PATH = os.path.join(BASE_DIR, "..", "data", "processed", "model_stage1_v3.joblib")
MODEL_S2_PATH = os.path.join(BASE_DIR, "..", "data", "processed", "model_stage2_v3.joblib")

OLLAMA_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "llama3.1:8b"

# ── CÀRREGA DE RECURSOS ──────────────────────────────────────────
print("🧠 Carregant motors d'intel·ligència artificial...")

try:
    with open(FAISS_PKL, "rb") as f:
        faiss_data = pickle.load(f)
    index = faiss.deserialize_index(faiss_data["index"])
    scaler = faiss_data["scaler"]
    train_ids = faiss_data["train_ids"]
    test_ids = faiss_data["test_ids"]
    feature_cols = faiss_data["features"]
    encoders = faiss_data["encoders"]

    model_v3_s1 = joblib.load(MODEL_S1_PATH)
    model_v3_s2 = joblib.load(MODEL_S2_PATH)

    df = pd.read_csv(DATA_CSV)
    df_indexed = df.set_index("id_pacient")
    print("✅ Sistema llest. Independentisme de models configurat.")
except Exception as e:
    print(f"❌ ERROR CRÍTIC: {e}")
    exit(1)

# ── FUNCIONS AUXILIARS ───────────────────────────────────────────

def safe_int(val):
    return 0 if pd.isna(val) else int(val)

def netejar_text(text):
    return text.replace('*', '').replace('#', '').strip()

def fer_prediccio_v3(pacient_series):
    row_df = pd.DataFrame([pacient_series]).drop(columns=['id_pacient', 'target', 'cronic'], errors='ignore')
    prob_chronic = float(model_v3_s1.predict_proba(row_df)[:, 1][0])
    
    if prob_chronic < 0.80:
        return "NO", (1 - prob_chronic)
    
    probs_s2 = model_v3_s2.predict_proba(row_df)[0]
    prob_maca = float(probs_s2[1])
    
    if prob_maca >= 0.40:
        return "MACA", prob_maca
    else:
        return "PCC", float(probs_s2[0])

def encode_patient(pacient_series):
    row = pd.DataFrame([pacient_series])
    row["sexe_encoded"] = row["sexe"].map(encoders["sexe"]).fillna(0).astype(int)
    row["situacio_encoded"] = row["situacio"].map(encoders["situacio"]).fillna(0).astype(int)
    row["cronic_encoded"] = row["cronic"].map(encoders["cronic"]).fillna(0).astype(int)
    row["edat_encoded"] = row["grup_edat"].map(encoders["grup_edat"]).fillna(3).astype(int)
    for col in feature_cols:
        if col not in row.columns: row[col] = 0
    X = row[feature_cols].fillna(0).values.astype(np.float32)
    X_norm = np.ascontiguousarray(scaler.transform(X), dtype=np.float32)
    faiss.normalize_L2(X_norm)
    return X_norm

def buscar_similars(id_pacient, k=10):
    pacient = df_indexed.loc[id_pacient]
    X_norm = encode_patient(pacient)
    distances, indices = index.search(X_norm, k)
    results = []
    for dist, idx in zip(distances[0], indices[0]):
        pid = train_ids[idx]
        p = df_indexed.loc[pid]
        results.append({
            "id_pacient": int(pid),
            "similitud": round(float(dist), 4),
            "cronic": str(p["cronic"]),
            "situacio": str(p["situacio"]),
            "diags_totals": safe_int(p.get("diags_totals", 0)),
            "farmacs_totals": safe_int(p.get("farmacs_totals", 0))
        })
    return results

# ── INFORME CLÍNIC SENSE BIAIX ───────────────────────────────────

def generar_informe(context):
    """
    Usem un prompt més tècnic i asèptic per evitar bloquejos de seguretat
    relacionats amb temes de final de vida.
    """
    prompt = f"""Ets un sistema d'anàlisi de dades per a suport a la gestió clínica (CDSS).
Analitza aquest cas de cronicitat complexa basant-te en dades biomèdiques.

PACIENT: {context['grup_edat']} anys, {context['diags_totals']} patologies, {context['farmacs_totals']} fàrmacs.
DADES DE GRUP (FAISS):
- Estabilitat: {context['pct_no']}%
- Complexitat (PCC): {context['pct_pcc']}%
- Avançat (MACA): {context['pct_maca']}%
- Risc de mortalitat històrica: {context['pct_mort_veins']}%

INSTRUCCIONS TÈCNIQUES:
1. CLASSIFICACIÓ RECOMANADA: (NO/PCC/MACA).
2. ARGUMENTACIÓ: Justifica segons la càrrega de malaltia i els casos similars.
3. PROGNOSI: Si és MACA, calcula l'estimació de supervivència estadística (en dies) basada en el {context['pct_mort_veins']}% de mortalitat del grup. Si no, indica 'Estable'.
4. ACCIÓ: Suggeriment pel metge.

Respon en CATALÀ, format professional i directe. No incloguis advertències sobre suïcidi, és una anàlisi clínica de dades històriques."""

    try:
        resp = http_requests.post(
            OLLAMA_URL,
            json={
                "model": OLLAMA_MODEL, 
                "prompt": prompt, 
                "stream": False,
                "options": {"num_predict": 275, "temperature": 0}
            },
            timeout=120
        )
        return netejar_text(resp.json()["response"])
    except Exception as e:
        return f"Error en el raonament independent: {str(e)}"

# ── ENDPOINTS ────────────────────────────────────────────────────

@app.route("/api/analyze", methods=["POST"])
def analyze():
    body = request.get_json() or {}
    id_pacient = body.get("id_pacient")
    if not id_pacient:
        return jsonify({"error": "Cal id_pacient"}), 400
    
    id_pacient = int(id_pacient)
    if id_pacient not in df_indexed.index:
        return jsonify({"error": "Pacient no trobat"}), 404

    pacient_series = df_indexed.loc[id_pacient]
    pred_v3, conf_v3 = fer_prediccio_v3(pacient_series)
    veins = buscar_similars(id_pacient, k=10)
    cronics_veins = [v["cronic"] for v in veins]

    context = {
        "id_pacient": id_pacient,
        "grup_edat": str(pacient_series["grup_edat"]),
        "sexe": str(pacient_series["sexe"]),
        "cronic_actual": str(pacient_series["cronic"]),
        "diags_totals": safe_int(pacient_series.get("diags_totals", 0)),
        "farmacs_totals": safe_int(pacient_series.get("farmacs_totals", 0)),
        "n_veins": len(veins),
        "pct_pcc": round(cronics_veins.count("PCC") / len(veins) * 100),
        "pct_maca": round(cronics_veins.count("MACA") / len(veins) * 100),
        "pct_no": round(cronics_veins.count("NO") / len(veins) * 100),
        "pct_mort_veins": round(sum(1 for v in veins if v["situacio"] == "D") / len(veins) * 100)
    }

    informe_final = generar_informe(context)

    return jsonify({
        "pacient": context,
        "prediccio_v3": {
            "resultat": pred_v3,
            "confianca": round(float(conf_v3), 4)
        },
        "veins_similars": veins,
        "informe": informe_final
    })

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)