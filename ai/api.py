#!/usr/bin/env python3
"""
API Flask per al Portal Sanitari.
  - POST /api/analyze      → Analitza un pacient (test set) amb FAISS + Ollama
  - GET  /api/classificacio → Retorna l'usuari promig d'un grup
  - GET  /api/test-patients → Llista els IDs de pacients disponibles (test set)

Executar: cd ai && python api.py
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import faiss
import numpy as np
import pandas as pd
import requests as http_requests

app = Flask(__name__)
CORS(app)

# ── Carregar dades ───────────────────────────────────────────────
print("Carregant dades...")
with open("../data/processed/faiss_data.pkl", "rb") as f:
    data = pickle.load(f)

index = faiss.deserialize_index(data["index"])
scaler = data["scaler"]
train_ids = data["train_ids"]
test_ids = data["test_ids"]
feature_cols = data["features"]
encoders = data["encoders"]

df = pd.read_excel("../data/processed/dataset_analitico_clean.xlsx")
df_indexed = df.set_index("id_pacient")

print(f"  FAISS index: {index.ntotal} vectors (train)")
print(f"  Test patients: {len(test_ids)}")
print(f"  API llesta!\n")

OLLAMA_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "gemma3:1b"


# ── Funcions auxiliars ───────────────────────────────────────────
def safe_int(val):
    return 0 if pd.isna(val) else int(val)


def encode_patient(pacient_series):
    """Codifica un pacient (Series) en un vector normalitzat."""
    row = pd.DataFrame([pacient_series])
    row["sexe_encoded"] = row["sexe"].map(encoders["sexe"]).fillna(0).astype(int)
    row["situacio_encoded"] = row["situacio"].map(encoders["situacio"]).fillna(0).astype(int)
    row["cronic_encoded"] = row["cronic"].map(encoders["cronic"]).fillna(0).astype(int)
    row["edat_encoded"] = row["grup_edat"].map(encoders["grup_edat"]).fillna(3).astype(int)
    X = row[feature_cols].fillna(0).values.astype(np.float32)
    X_norm = np.ascontiguousarray(scaler.transform(X), dtype=np.float32)
    faiss.normalize_L2(X_norm)
    return X_norm


def buscar_similars(id_pacient, k=10):
    """Cerca els k pacients més similars del train set."""
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
            "grup_edat": str(p["grup_edat"]),
            "sexe": str(p["sexe"]),
            "situacio": str(p["situacio"]),
            "diags_totals": safe_int(p["diags_totals"]),
            "farmacs_totals": safe_int(p["farmacs_totals"]),
        })
    return results


def generar_informe(context, ollama_url=None):
    """Crida a Ollama per generar un informe clínic."""
    prompt = f"""Ets un assistent de suport a la decisió mèdica. Genera un informe en CATALÀ NATURAL.

DEFINICIONS CRÍTIQUES:
- PCC: Pacient Crònic Complex.
- MACA: Model d'Atenció Crònica Avançada (pacient en situació de final de vida).

DADES DEL PACIENT (id: {context['id_pacient']}):
- Edat: {context['grup_edat']} anys
- Sexe: {'Masculí' if context['sexe'] == 'H' else 'Femenina'}
- Classificació actual: {context['cronic_actual']}
- Clínica: {context['diags_totals']} diagnòstics, {context['farmacs_totals']} fàrmacs.

EVIDÈNCIA HISTÒRICA (Pels teus càlculs, NO citar dades brutes):
- En un grup de {context['n_veins']} pacients idèntics a aquest, la mortalitat ha estat del {context['pct_mort_veins']}%.
- La majoria ({context['pct_maca']}%) es consideren MACA.

INSTRUCCIONS DE RESPOSTA (FORMAT TEXT NET, SENSE ASTERISCS):
1. CLASSIFICACIÓ RECOMANADA: (PCC / MACA / NO)
2. CONFIANÇA: (BAIXA, MITJANA o ALTA)
3. JUSTIFICACIÓ: (2-3 frases sobre la fragilitat o cronicitat. NO t'inventis malalties com 'tumors' o 'Plaqueal' si no hi són).
4. PROGNOSI (DIES DE VIDA): Si és MACA, estima quants DIES DE VIDA li queden aproximadament basant-te en la mortalitat del {context['pct_mort_veins']}% i la seva fragilitat clínica. Sigues explícit. Si no és MACA, indica 'Estable'.
5. ACCIÓ SÚGGERIDA: (Què ha de fer el metge ara mateix).

REGLA DE LLENGUATGE: Usa 'Sexe', no 'Sexu'. Usa 'Pacient', no 'Paciente'. No usis 'ESTRUCTURAMEN'. Sigues molt professional i no escriguis apunts, anotacions ni res que no sigui del input"""

    try:
        url = ollama_url if ollama_url else OLLAMA_URL
        resp = http_requests.post(
            url,
            json={"model": OLLAMA_MODEL, "prompt": prompt, "stream": False},
            timeout=60,
        )
        return resp.json()["response"]
    except Exception as e:
        return f"Error Ollama ({ollama_url or OLLAMA_URL}): {str(e)}"


# ── Endpoints ────────────────────────────────────────────────────

@app.route("/api/test-patients", methods=["GET"])
def get_test_patients():
    """Retorna la llista de pacients del test set disponibles per analitzar."""
    patients = []
    for pid in test_ids[:100]:  # Limitar a 100 per no sobrecarregar
        p = df_indexed.loc[pid]
        patients.append({
            "id_pacient": int(pid),
            "sexe": str(p["sexe"]),
            "grup_edat": str(p["grup_edat"]),
            "cronic": str(p["cronic"]),
        })
    return jsonify({"patients": patients, "total": len(test_ids)})


@app.route("/api/analyze", methods=["POST"])
def analyze():
    """Analitza un pacient: FAISS similars + informe Ollama."""
    body = request.get_json() or {}
    id_pacient = body.get("id_pacient")

    if not id_pacient:
        return jsonify({"error": "Cal proporcionar id_pacient"}), 400

    id_pacient = int(id_pacient)

    if id_pacient not in df_indexed.index:
        return jsonify({"error": f"Pacient {id_pacient} no existeix"}), 404

    if id_pacient not in test_ids:
        return jsonify({"error": f"Pacient {id_pacient} està al train set, no es pot predir. Usa un ID del test set."}), 400

    # Buscar similars al train set
    veins = buscar_similars(id_pacient, k=10)

    # Construir context
    pacient = df_indexed.loc[id_pacient]
    cronics = [v["cronic"] for v in veins]
    context = {
        "id_pacient": id_pacient,
        "grup_edat": str(pacient["grup_edat"]),
        "sexe": str(pacient["sexe"]),
        "cronic_actual": str(pacient["cronic"]),
        "diags_totals": safe_int(pacient.get("diags_totals")),
        "farmacs_totals": safe_int(pacient.get("farmacs_totals")),
        "n_veins": len(veins),
        "pct_pcc": round(cronics.count("PCC") / len(cronics) * 100),
        "pct_maca": round(cronics.count("MACA") / len(cronics) * 100),
        "pct_no": round(cronics.count("NO") / len(cronics) * 100),
        "pct_mort_veins": round(sum(1 for v in veins if v["situacio"] == "D") / len(veins) * 100),
    }

    # Generar informe amb Ollama
    ollama_url = body.get("ollama_url")
    informe = generar_informe(context, ollama_url=ollama_url)

    return jsonify({
        "pacient": context,
        "veins_similars": veins,
        "informe": informe,
    })


@app.route("/api/classificacio", methods=["GET"])
def classificacio():
    """Retorna estadístiques de l'usuari promig d'un grup (cronic)."""
    grup = request.args.get("grup")
    if not grup:
        return jsonify({"error": "Cal proporcionar ?grup=PCC|MACA|NO"}), 400

    mapping = {"1": "PCC", "2": "MACA", "3": "NO"}
    grup_nom = mapping.get(grup, grup)

    df_grup = df[df["cronic"] == grup_nom]
    if df_grup.empty:
        return jsonify({"error": f"Grup '{grup_nom}' no trobat"}), 404

    num_cols = [c for c in df.columns if c not in {"id_pacient", "sexe", "situacio", "cronic", "grup_edat"}]
    promig = {}
    for col in num_cols:
        val = df_grup[col].mean()
        promig[col] = round(float(val), 2) if pd.notna(val) else 0

    return jsonify({
        "grup": grup_nom,
        "total_pacients": len(df_grup),
        "promig": promig,
        "distribucio_sexe": df_grup["sexe"].value_counts().to_dict(),
        "distribucio_edat": df_grup["grup_edat"].value_counts().to_dict(),
        "distribucio_situacio": df_grup["situacio"].value_counts().to_dict(),
    })


if __name__ == "__main__":
    print("🚀 API disponible a http://localhost:5000")
    app.run(host="0.0.0.0", port=5000, debug=True)
