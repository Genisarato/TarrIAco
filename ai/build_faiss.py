#!/usr/bin/env python3
"""
Construeix un índex FAISS a partir del dataset analític net.
Genera un sol fitxer: faiss_data.pkl (índex + scaler + mapping + config)

Requisits: pip install pandas openpyxl scikit-learn faiss-cpu
"""

import pandas as pd
import numpy as np
import faiss
import pickle
from sklearn.preprocessing import StandardScaler

# ── Carregar i preparar ──────────────────────────────────────────────
df = pd.read_excel("../data/processed/dataset_analitico_clean.xlsx")
print(f"Carregat: {df.shape[0]} pacients, {df.shape[1]} columnes")

ids = df["id_pacient"].values.tolist()

# Codificar categòriques
df["sexe_encoded"] = (df["sexe"] == "H").astype(int)
df["situacio_encoded"] = df["situacio"].map({"A": 0, "D": 1}).fillna(0).astype(int)
df["cronic_encoded"] = df["cronic"].map({"NO": 0, "PCC": 1, "MACA": 2}).fillna(0).astype(int)
df["edat_encoded"] = df["grup_edat"].map({"65-70": 0, "70-75": 1, "75-80": 2, "80-85": 3, "85-90": 4, "90>": 5}).fillna(3).astype(int)

# Features: totes les numèriques + les codificades
skip = {"id_pacient", "sexe", "situacio", "cronic", "grup_edat"}
feature_cols = [c for c in df.columns if c not in skip]

# ── Normalitzar i crear índex ────────────────────────────────────────
X = df[feature_cols].fillna(0).values.astype(np.float32)
scaler = StandardScaler()
X_norm = np.ascontiguousarray(scaler.fit_transform(X), dtype=np.float32)
faiss.normalize_L2(X_norm)

index = faiss.IndexFlatIP(X_norm.shape[1])
index.add(X_norm)
print(f"Índex FAISS: {index.ntotal} vectors, dim={X_norm.shape[1]}")

# ── Guardar tot en un sol fitxer ─────────────────────────────────────
data = {
    "index": faiss.serialize_index(index),
    "scaler": scaler,
    "ids": ids,
    "features": feature_cols,
    "encoders": {
        "sexe": {"H": 1, "D": 0},
        "situacio": {"A": 0, "D": 1},
        "cronic": {"NO": 0, "PCC": 1, "MACA": 2},
        "grup_edat": {"65-70": 0, "70-75": 1, "75-80": 2, "80-85": 3, "85-90": 4, "90>": 5},
    },
}
with open("../data/processed/faiss_data.pkl", "wb") as f:
    pickle.dump(data, f)
print("✅ Guardat: data/processed/faiss_data.pkl")

# ── Test ràpid ───────────────────────────────────────────────────────
D, I = index.search(X_norm[0:1], 6)
print(f"\nTest — 5 similars al pacient id={ids[0]}:")
for d, i in zip(D[0][1:], I[0][1:]):
    print(f"  id={ids[i]}, sim={d:.4f}")
