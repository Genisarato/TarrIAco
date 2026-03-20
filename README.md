# Repte 1: Hospital Joan XXIII

[Presentacio del repte](presentacio_repte.pdf)

[ICS Camp de Tarragona](https://icscampdetarragona.cat/) - Institut Catala de la Salut

[Pagina web de l'hospital Joan XXIII](https://icscampdetarragona.cat/es/hj23-servicios/)

## Objectiu del repte

Analitzar i visualitzar dades sanitaries per millorar l'eficiencia i qualitat de l'atencio medica.

## Recursos disponibles

- **Diccionari de dades**: [Descripcio de les taules de la base de dades](diccionari_dades_hackato_urv.pdf)
- **Base de dades**: Acces proporcionat durant la Hackato als grups que triïn aquest repte

## Acces a la base de dades

La base de dades es un PostgreSQL allotjat a Supabase. Se us proporcionara un fitxer `.env` amb els parametres de connexio (només lectura) durant la Hackato.

### 1. Copiar el fitxer `.env`

Se us lliurara un fitxer `.env` amb els parametres de connexio. Copieu-lo a l'arrel del projecte (la mateixa carpeta que `read_example.py`). El fitxer conte:

```env
SUPABASE_READER_USERNAME="..."
SUPABASE_READER_PASSWORD="..."
SUPABASE_CONNECTION_STRING_POOLER="..."
PROJECT_ID="..."
```

No cal modificar-lo: l'script el llegeix automaticament.

### 2. Instal·lar dependencies

```bash
python3 -m venv .venv
source .venv/bin/activate       # Linux/Mac
# .venv\Scripts\activate        # Windows
pip install python-dotenv psycopg[binary]
```

### 3. Executar l'exemple

L'script [`read_example.py`](read_example.py) connecta a la base de dades i llegeix files de la taula `ics_data.cohort`:

```bash
python read_example.py          # llegeix 10 files per defecte
python read_example.py 25       # llegeix 25 files
```

Sortida esperada:

```
Connexio correcta! Llegides 3 files de ics_data.cohort
Columnes: id_pacient, sexe, situacio, cronic, grup_edat

    1: (1, 'D', 'A', 'NO', '80-85')
    2: (2, 'H', 'A', 'NO', '75-80')
    3: (3, 'D', 'A', 'NO', '70-75')
```

### Nota important: base de dades de nomes lectura (READ-ONLY)

La base de dades proporcionada es de **nomes lectura**. No podeu crear taules, inserir, modificar ni eliminar dades.

Es recomana descarregar les dades que necessiteu i treballar amb la vostra propia base de dades (SQLite, DuckDB, PostgreSQL local...).

### 4. Exemple de consulta personalitzada

Per llegir qualsevol altra taula, podeu reutilitzar la funcio `connect()` de l'exemple:

```python
from read_example import connect

conn = connect()
with conn:
    with conn.cursor() as cur:
        cur.execute("SELECT * FROM ics_data.visites_urgencies LIMIT 5;")
        for row in cur.fetchall():
            print(row)
conn.close()
```

O crear el vostre propi script important les mateixes dependencies:

```python
import os
from dotenv import load_dotenv
import psycopg

load_dotenv()

conn = psycopg.connect(
    host=os.getenv("SUPABASE_CONNECTION_STRING_POOLER"),
    port=5432,
    dbname="postgres",
    user=f"{os.getenv('SUPABASE_READER_USERNAME')}.{os.getenv('PROJECT_ID')}",
    password=os.getenv("SUPABASE_READER_PASSWORD"),
    sslmode="require",
)

with conn:
    with conn.cursor() as cur:
        cur.execute("SELECT * FROM ics_data.visites_urgencies LIMIT 5;")
        for row in cur.fetchall():
            print(row)

conn.close()
```
# TarrIAco
