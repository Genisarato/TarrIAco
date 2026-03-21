# Projecte SIA
El nostre grup anomenat TarrIAco ha desenvoluapt el projecte anomenat SIA (Salut en Inteligència Artificial) i consta en dos eines. Una per a professionals que els ajudi en la toma de decissions per fer un diagnostic i una per a usuaris que els hi dongui informació de com va el seu cas.

## Entitat del repte
Hospital Universitari Joan XXIII

## Equip

### **Genis Aragonés Torralbo**
Email: genisarato@gmail.com
Github: https://github.com/Genisarato

### **Marc Roda Cortés**
Email: 1516marcroda@gmail.com
Github: https://github.com/Marc-yes

### **Massin Laaouaj**
Email: massin.laaouaj@estudiants.urv.cat
Github: https://github.com/massinlaaouaj

### **David Quintana Palomar**
Email: dqp220504@gmail.com
Github: https://github.com/Quintana-04

## Reptes abordats
- Identificació automàtica de pacients en categories de cronicitat: **NO crònic**, **PCC** (Pacient Crònic Complex) i **MACA** (Malaltia Avançada en Congestió Alta).

- Cerca de pacients similars per donar suport als clínics en la presa de decisions basada en evidència.

- Generació d'informes clínics automatitzats amb IA generativa (LLM local).

- Portal web per facilitar l'accés dels professionals sanitaris i dels propis pacients a la informació personalitzada.

## Descripció curta del projecte (màx. 50 paraules)

SIA és un sistema d'IA que classifica automàticament pacients com a NO crònics, PCC o MACA a partir de dades clíniques reals. Combina models d'aprenentatge automàtic, cerca de similitud amb FAISS i un LLM local per generar informes clínics i consells personalitzats.

## Descripció llarga del projecte (màx. 250 paraules)

SIA neix de la necessitat de donar suport als professionals sanitaris de l'Hospital Joan XXIII en la complexa tasca d'identificar i categoritzar pacients crònics. El sistema automatitza la classificació triclasse (NO / PCC / MACA) a partir de més de 80 variables clíniques per pacient, incloent-hi dades demogràfiques, diagnòstics, fàrmacs prescrits, visites a urgències i hospitalitzacions.

Hem desenvolupat dos IA's:

- El nucli predictiu és un pipeline jeràrquic en dos estadis: el primer model (Random Forest) determina si un pacient és crònic o no; el segon (HistGradientBoosting calibrat) distingeix entre PCC i MACA entre els pacients identificats com a crònics. Aquest sistema jeràrquic millora significativament la precisió respecte a un classificador directe triclasse.

- Per a la cerca de similitud, s'utilitza FAISS (Facebook AI Similarity Search), que permet identificar en mil·lisegons els 10 pacients més semblants de la base de dades. Això proporciona al clínic una evidència estadística real: quins percentatges dels pacients similars eren PCC, MACA o NO, i quins han mort, donant suport a decisions de pronòstic.

Finalment, un LLM local (Llama 3.1:8b via Ollama) genera dos tipus de text: (a) un informe clínic tècnic per al metge, i (b) consells personalitzats per al propi pacient, escrits en un to càlid i amigable en català.

Tot el sistema s'exposa via una API REST Flask i es consumeix des d'un portal web Laravel, separant nítidament backend d'IA i frontend sanitari.

## Tecnologies utilitzades

### Llenguatges de programació
- Python 3.11+
- PHP 8.2 (Laravel)
- JavaScript (ES6+), Blade (Laravel templates)

### Frameworks i llibreries
- **Scikit-learn**: RandomForestClassifier, HistGradientBoostingClassifier, CalibratedClassifierCV, pipeline de preprocessament
- **FAISS** (faiss-cpu): índex de similitud vetorial per a cerca de veïns propers
- **Flask + Flask-CORS**: API REST del backend d'IA
- **Ollama (Llama 3.1:8b)**: LLM local per a generació d'informes clínics i consells al pacient
- **Pandas / NumPy**: transformació i processament del dataset
- **Joblib**: serialització dels models entrenats
- **Laravel 11**: portal web sanitari amb autenticació i gestió de rols

### Eines i plataformes
- **Supabase (PostgreSQL)**: base de dades clínica de l'hospital (accés read-only)
- **Git / GitHub**: control de versions i treball col·laboratiu
- **Python venv**: entorn virtual per al backend de Python
- **Composer / npm**: gestió de dependències del portal Laravel

## (Opcional) Sistema Implementat

        ┌─────────────────────────────────────────────┐
        │                     AWS                     │                        
        └───────────┬─────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│                    PORTAL WEB SANITARI                       │
│                   (Laravel – PHP/Blade)                      │
│   [Vista Metge: Anàlisi Pacient]  [Vista Pacient: Consells]  │
└────────────────────┬─────────────────────────────────────────┘
                     │ HTTP REST (JSON)
                     ▼
┌──────────────────────────────────────────────────────────────┐
│                    API FLASK (Python)                        │
│  /api/analyze         │  /api/pacient-info                   │
│                       │                                      │
│  ┌─────────────────┐  │   ┌──────────────────────────────┐   │
│  │  Pipeline V3    │  │   │  FAISS Similarity Search     │   │
│  │  [Estadi 1]     │  │   │  (IndexFlatIP, k=10 veïns)   │   │
│  │  Random Forest  │  │   └──────────────────────────────┘   │
│  │  Crònic vs NO   │  │   ┌──────────────────────────────┐   │
│  │  [Estadi 2]     │  │   │ Ollama (Llama 3.1:8b)        │   │
│  │  HistGBoosting  │  │   │  · Informe clínic (metge)    │   │
│  │  PCC vs MACA    │  │   │  · Consells (pacient)        │   │
│  └─────────────────┘  │   └──────────────────────────────┘   │
└──────────────────────────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│           DADES (data/processed/)                            │
│  dataset_final_pcc.csv · model_stage1_v3.joblib              │
│  model_stage2_v3.joblib · faiss_data.pkl                     │
└──────────────────────────────────────────────────────────────┘