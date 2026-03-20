import os
import psycopg
import pandas as pd
from dotenv import load_dotenv

load_dotenv()

USERNAME = os.getenv("SUPABASE_READER_USERNAME")
PASSWORD = os.getenv("SUPABASE_READER_PASSWORD")
PROJECT_ID = os.getenv("PROJECT_ID")
POOLER_HOST = os.getenv("SUPABASE_CONNECTION_STRING_POOLER")
DB_NAME = "postgres"

def connect():
    return psycopg.connect(
        host=POOLER_HOST,
        port=5432,
        dbname=DB_NAME,
        user=f"{USERNAME}.{PROJECT_ID}",
        password=PASSWORD,
        sslmode="require",
    )

def main():
    conn = connect()
    with conn.cursor() as cur:
        # Fetch table names
        cur.execute("""
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'ics_data';
        """)
        tables = [row[0] for row in cur.fetchall()]
        print(f"Found {len(tables)} tables: {tables}")
        
    for table in tables:
        print(f"Extracting table {table}...")
        # A new cursor/transaction per table to avoid keeping huge memory state on DB side
        with conn.cursor() as cur:
            cur.execute(f'SELECT * FROM ics_data."{table}";')
            columns = [desc[0] for desc in cur.description]
            rows = cur.fetchall()
            
        print(f"  Loaded {len(rows)} rows for {table}. Converting to DataFrame...")
        df = pd.DataFrame(rows, columns=columns)
        
        # Optionally handle Timezone aware datetimes before saving to excel
        for col in df.select_dtypes(include=['datetimetz']).columns:
            df[col] = df[col].dt.tz_localize(None)

        excel_path = f"{table}.xlsx"
        print(f"  Saving to {excel_path}...")
        df.to_excel(excel_path, index=False)
        print(f"  Saved {excel_path} successfully.")

    conn.close()
    print("All tables extracted successfully.")

if __name__ == "__main__":
    main()
