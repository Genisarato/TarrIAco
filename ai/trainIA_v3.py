import os
import joblib
import warnings
import numpy as np
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.compose import ColumnTransformer
from sklearn.pipeline import Pipeline
from sklearn.impute import SimpleImputer
from sklearn.preprocessing import StandardScaler, OneHotEncoder
from sklearn.linear_model import LogisticRegression
from sklearn.ensemble import RandomForestClassifier, HistGradientBoostingClassifier
from sklearn.calibration import CalibratedClassifierCV
from sklearn.metrics import classification_report, confusion_matrix, precision_recall_fscore_support

warnings.filterwarnings("ignore")

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_PATH = os.path.join(BASE_DIR, "..", "data", "processed", "dataset_final_pcc.csv")
MODEL_S1_PATH = os.path.join(BASE_DIR, "..", "data", "processed", "model_stage1_v3.joblib")
MODEL_S2_PATH = os.path.join(BASE_DIR, "..", "data", "processed", "model_stage2_v3.joblib")
RANDOM_STATE = 42

print("--- Iniciant entrenament jeràrquic V3 (Ultra Precisió) ---")

if not os.path.exists(DATA_PATH):
    print(f"ERROR: No trobo {DATA_PATH}")
    exit()

df = pd.read_csv(DATA_PATH)

# =========================================================
# PREPROCESSAMENT COMÚ
# =========================================================
drop_cols = ["id_pacient", "target", "cronic"]
X_all = df.drop(columns=[c for c in drop_cols if c in df.columns]).copy()
num_cols = X_all.select_dtypes(include=["int64", "float64"]).columns.tolist()
cat_cols = X_all.select_dtypes(include=["object", "category"]).columns.tolist()

preprocessor = ColumnTransformer(
    transformers=[
        ("num", Pipeline(steps=[("imputer", SimpleImputer(strategy="median")), ("scaler", StandardScaler())]), num_cols),
        ("cat", Pipeline(steps=[("imputer", SimpleImputer(strategy="most_frequent")), ("onehot", OneHotEncoder(handle_unknown="ignore"))]), cat_cols),
    ]
)

# =========================================================
# ESTAT 1: CRÒNIC vs NO
# =========================================================
print("\n[Estat 1] Entrenant Chronic vs NO...")
y1 = (df["target"] > 0).astype(int)
X1_train, X1_test, y1_train, y1_test = train_test_split(X_all, y1, test_size=0.3, random_state=RANDOM_STATE, stratify=y1)

model1 = Pipeline(steps=[
    ("preprocessor", preprocessor),
    ("classifier", RandomForestClassifier(n_estimators=300, class_weight="balanced", random_state=RANDOM_STATE, n_jobs=-1))
])
model1.fit(X1_train, y1_train)

# =========================================================
# ESTAT 2: PCC vs MACA amb CALIBRACIÓ
# =========================================================
print("[Estat 2] Entrenant PCC vs MACA amb HistGradientBoosting + Calibració...")
df_chronic = df[df["target"] > 0].copy()
X2_all = df_chronic.drop(columns=[c for c in drop_cols if c in df_chronic.columns]).copy()
y2 = df_chronic["target"].copy() # 1=PCC, 2=MACA

X2_train, X2_test, y2_train, y2_test = train_test_split(X2_all, y2, test_size=0.3, random_state=RANDOM_STATE, stratify=y2)

# Usem HistGradientBoosting calibrat per obtenir probabilitats més precises
base_model2 = HistGradientBoostingClassifier(
    max_iter=400,
    max_depth=10,
    class_weight="balanced",
    random_state=RANDOM_STATE
)

model2 = Pipeline(steps=[
    ("preprocessor", preprocessor),
    ("classifier", CalibratedClassifierCV(estimator=base_model2, cv=3))
])
model2.fit(X2_train, y2_train)

# =========================================================
# AVALUACIÓ ULTRA-PRECIÓ
# =========================================================
print("\n" + "="*40)
print("AVALUACIÓ DEL MODEL V3 (MÀXIMA PRECISSIÓ)")
print("="*40)

X_final_test, y_final_true = X1_test, df.loc[X1_test.index, "target"]

# 1. Probabilitat de ser crònic (Estat 1)
y1_prob = model1.predict_proba(X_final_test)[:, 1]
y1_pred = (y1_prob >= 0.70).astype(int) 

# 2. Predicció jeràrquica
y_final_pred = np.zeros_like(y1_pred)
chronic_indices = np.where(y1_pred == 1)[0]

if len(chronic_indices) > 0:
    X_chronic_detected = X_final_test.iloc[chronic_indices]
    y2_probs = model2.predict_proba(X_chronic_detected)
    
    # y2_probs[:, 0] -> Prob PCC, y2_probs[:, 1] -> Prob MACA
    THRESHOLD_MACA = 0.40
    y2_pred = np.where(y2_probs[:, 1] >= THRESHOLD_MACA, 2, 1)
    
    y_final_pred[chronic_indices] = y2_pred

target_names = ["NO", "PCC", "MACA"]
print(classification_report(y_final_true, y_final_pred, target_names=target_names))

print("\nMatriu de Confusió:")
cm = confusion_matrix(y_final_true, y_final_pred)
df_cm = pd.DataFrame(cm, index=target_names, columns=["Pred_NO", "Pred_PCC", "Pred_MACA"])
print(df_cm)

# Verifiquem si hem passat del 80% en ambdós
metrics = precision_recall_fscore_support(y_final_true, y_final_pred, average=None)
prec_pcc = metrics[0][1]
prec_maca = metrics[0][2]

print(f"\nPRECISIÓ PCC:  {prec_pcc:.4f} " + ("✅ (>80%)" if prec_pcc >= 0.8 else "❌"))
print(f"PRECISIÓ MACA: {prec_maca:.4f} " + ("✅ (>80%)" if prec_maca >= 0.8 else "❌"))

# Guardar si és millor
if prec_pcc >= 0.8 and prec_maca >= 0.8:
    joblib.dump(model1, MODEL_S1_PATH)
    joblib.dump(model2, MODEL_S2_PATH)
    print("\nModels super-precisos guardats!")