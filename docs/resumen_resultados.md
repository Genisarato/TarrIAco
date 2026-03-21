# Resumen de Resultados — TarrIAco

## 1. Búsqueda de Pacientes Similares (FAISS)

| Concepto | Valor |
|---|---|
| **Índice** | `IndexFlatIP` (producto interno, similitud coseno) |
| **Dataset** | `dataset_analitico_clean.xlsx` — 33 columnas |
| **Split** | 75% train (~28,400 pacientes) / 25% test (~9,500) |
| **Dimensión vectorial** | 33 features (numéricas + categóricas codificadas) |
| **Normalización** | StandardScaler + L2 normalize |
| **K vecinos** | 10 similares por consulta |

**Variables codificadas para FAISS:**
- `sexe` → H=1, D=0
- `situacio` → A=0, D=1
- `cronic` → NO=0, PCC=1, MACA=2
- `grup_edat` → 65-70=0 ... 90>=5

**Flujo**: Dado un paciente del test set → se codifica y normaliza → FAISS busca los 10 más cercanos del train → se calcula % PCC/MACA/mortalidad entre vecinos → Ollama genera informe clínico en catalán.

---

## 2. Predicción de Diagnóstico Futuro (trainIA.py)

### Dataset de Entrenamiento

| Concepto | Valor |
|---|---|
| **Pacientes** | 37,902 |
| **Features** | ~145 columnas |
| **Target** | Multiclase: NO (0), PCC (1), MACA (2) |
| **Split** | 65% train / 25% test / 10% reserva |

**Distribución del target:**

| Clase | Pacientes | % |
|---|---|---|
| NO | ~29,325 | 77.4% |
| PCC | ~6,900 | 18.2% |
| MACA | ~1,677 | 4.4% |

### Rendimiento del Modelo

| Modelo | AUC (OVR weighted) | Seleccionado |
|---|---|---|
| **Random Forest** (400 árboles) | ~0.93 | ✅ |
| Logistic Regression | ~0.90 | — |

**Métricas Random Forest (test set, ~9,475 pacientes):**

| Clase | Precision | Recall | F1 | Support |
|---|---|---|---|---|
| NO | ~0.88 | ~0.93 | ~0.90 | ~7,331 |
| PCC | ~0.72 | ~0.56 | ~0.63 | ~1,726 |
| MACA | ~0.70 | ~0.66 | ~0.68 | ~418 |
| **Weighted avg** | **0.845** | **0.829** | **0.835** | 9,475 |

### Top 10 Variables Más Importantes (Random Forest)

| # | Feature | Importancia |
|---|---|---|
| 1 | `farmacs_totals` | 0.0594 |
| 2 | `situacio_A` | 0.0452 |
| 3 | `situacio_D` | 0.0429 |
| 4 | `diags_totals` | 0.0426 |
| 5 | `altres` | 0.0422 |
| 6 | `total_contactes_sanitaris` | 0.0350 |
| 7 | `ap_infermeria_domicili` | 0.0327 |
| 8 | `ap_infermeria_econsultes` | 0.0308 |
| 9 | `ap_visites_totals` | 0.0293 |
| 10 | `sistema_nervios` | 0.0244 |

> [!IMPORTANT]
> Las variables más determinantes para predecir PCC/MACA son la **polifarmacia**, el **estado vital (situació)**, el **número de diagnósticos** y el **contacto total con el sistema sanitario**.

### Pacientes de Alto Riesgo Detectados

| Grupo | Pacientes "NO" reclasificados | Rango probabilidad |
|---|---|---|
| **Candidatos a PCC** | 3,936 (13.4% de los "NO") | 0.40 – 0.83 |
| **Candidatos a MACA** | 206 (0.7% de los "NO") | 0.40 – 0.86 |

> [!TIP]
> Estos son pacientes actualmente etiquetados como "NO" pero que el modelo predice con alta probabilidad que deberían ser PCC o MACA. Representan oportunidades de **detección precoz**.
