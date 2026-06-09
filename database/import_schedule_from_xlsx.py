"""
Import Invoice Schedule and Income Schedule from quotation_list.xlsx
into quotation_headers.expected_invoice_date / expected_income_date.

Matches on quotation_no (trimmed).
"""
import openpyxl, os, sys
from datetime import date, datetime

XLSX = r"C:\Users\R.Nozaki\Downloads\quotation_list.xlsx"

try:
    import psycopg2
except ImportError:
    os.system(sys.executable + ' -m pip install psycopg2-binary -q')
    import psycopg2

conn = psycopg2.connect(
    host='localhost', port=5432,
    dbname='pegasus_erp', user='postgres', password='postgres'
)
conn.autocommit = False
cur = conn.cursor()

print(f'Loading {XLSX}...')
wb = openpyxl.load_workbook(XLSX, data_only=True, read_only=True)
ws = wb['list']

# Column indices (1-based per openpyxl convention); header is row 2
COL = {
    'quotation_no': 3,
    'invoice_schedule': 22,
    'income_schedule': 23,
}

updated = 0
not_found = 0
no_dates = 0

for r, row in enumerate(ws.iter_rows(min_row=3, values_only=True), start=3):
    if r % 500 == 0:
        print(f'  processed {r} rows...')
    qt_no = row[COL['quotation_no'] - 1]
    inv_sched = row[COL['invoice_schedule'] - 1]
    inc_sched = row[COL['income_schedule'] - 1]
    if not qt_no:
        continue
    qt_no = str(qt_no).strip()
    if not inv_sched and not inc_sched:
        no_dates += 1
        continue

    def to_date(v):
        if isinstance(v, datetime):
            return v.date()
        if isinstance(v, date):
            return v
        return None

    inv_d = to_date(inv_sched)
    inc_d = to_date(inc_sched)

    cur.execute(
        """UPDATE quotation_headers
           SET expected_invoice_date = COALESCE(%s, expected_invoice_date),
               expected_income_date  = COALESCE(%s, expected_income_date)
           WHERE quotation_no = %s""",
        (inv_d, inc_d, qt_no)
    )
    if cur.rowcount > 0:
        updated += cur.rowcount
    else:
        not_found += 1

conn.commit()
cur.close()
conn.close()

print(f'Updated: {updated} quotations')
print(f'Not found in DB (skipped): {not_found}')
print(f'Rows without dates: {no_dates}')
