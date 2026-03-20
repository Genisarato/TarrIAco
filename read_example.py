#!/usr/bin/env python3
"""
Exemple de lectura de la base de dades del repte de l'Hospital Joan XXIII.

Connecta a la base de dades Supabase (PostgreSQL) amb les credencials
definides al fitxer .env i llegeix files de la taula ics_data.cohort.

Se us proporcionara un fitxer .env amb els parametres de connexio.
Copieu-lo a la mateixa carpeta que aquest script i executeu-lo.

Requisits:
    pip install python-dotenv psycopg[binary]

Us:
    python read_example.py          # llegeix 10 files per defecte
    python read_example.py 25       # llegeix 25 files
"""

import os
import sys

import psycopg
from dotenv import load_dotenv

# Carrega el fitxer .env que se us ha proporcionat.
load_dotenv()

USERNAME = os.getenv("SUPABASE_READER_USERNAME")
PASSWORD = os.getenv("SUPABASE_READER_PASSWORD")
PROJECT_ID = os.getenv("PROJECT_ID")
POOLER_HOST = os.getenv("SUPABASE_CONNECTION_STRING_POOLER")


def connect():
    """Obre una connexio a la base de dades Supabase via pooler."""
    return psycopg.connect(
        host=POOLER_HOST,
        port=5432,
        dbname="postgres",
        user=f"{USERNAME}.{PROJECT_ID}",
        password=PASSWORD,
        sslmode="require",
    )


def main() -> None:
    if not all([USERNAME, PASSWORD, PROJECT_ID, POOLER_HOST]):
        print(
            "Error: No s'han trobat els parametres de connexio.\n"
            "Assegura't de copiar el fitxer .env proporcionat a la\n"
            "mateixa carpeta que aquest script."
        )
        sys.exit(1)

    limit = 10
    if len(sys.argv) >= 2:
        try:
            limit = int(sys.argv[1])
        except ValueError:
            print("Error: L'argument ha de ser un nombre enter.")
            sys.exit(1)

    conn = connect()
    try:
        with conn:
            with conn.cursor() as cur:
                cur.execute(
                    "SELECT * FROM ics_data.cohort LIMIT %s;",
                    (limit,),
                )
                rows = cur.fetchall()
                columns = [desc[0] for desc in cur.description]
    finally:
        conn.close()

    print(f"Connexio correcta! Llegides {len(rows)} rows de ics_data.cohort")
    print(f"Columnes: {', '.join(columns)}")
    print()
    for idx, row in enumerate(rows, start=1):
        print(f"  {idx:>3}: {row}")


if __name__ == "__main__":
    main()
