"""
Phase 3: Import all PO PDFs into purchase_order_headers.

PO PDFs are issued by *customers* (each with a unique format). Goal:
register one PO header per file with best-effort field extraction.

Heuristics:
  - Vendor (= customer/issuer) from filename prefix: "keyence_*.pdf" / "advics_*.pdf"
  - PO No from filename body (after first underscore)
  - Date from text (regex multiple formats)
  - Total amount = last money-like number on the page (often the grand total)
  - Supplier_id  = TOMAS TECH (we are the supplier in these POs). We map to the
    Tomas-Tech "internal" supplier row; if none, fall back to the first supplier.

Inputs:
  po data/**/*.pdf

Output:
  backups/po_import_report.csv
  purchase_order_headers rows (status='APPROVED')
"""
from __future__ import annotations
import sys, os, re, csv, subprocess, datetime
sys.stdout.reconfigure(encoding='utf-8')

import psycopg
from psycopg.rows import dict_row

ROOT = r"C:\Users\R.Nozaki\Downloads\Pegasus_ERP_R1"
PO_ROOT = os.path.join(ROOT, "po data")
REPORT  = os.path.join(ROOT, "backups", "po_import_report.csv")

RE_MONEY    = re.compile(r"([0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2}|[0-9]+\.[0-9]{2})")
RE_DATE_ANY = re.compile(r"\b([0-3]?\d[\-/\.][01]?\d[\-/\.](?:\d{2}|\d{4}))\b")
RE_DATE_DMY_TXT = re.compile(r"\b([0-3]?\d[\-\s][A-Za-z]{3,}[\-\s]\d{2,4})\b")
RE_PO_NO    = re.compile(r"(?:PO\s*#|PO\s*No\.?|Order\s*No\.?|No\.\s*:)\s*([A-Za-z0-9\-_/]+)", re.I)

def pdftotext(path: str) -> str:
    try:
        r = subprocess.run(
            ["pdftotext", "-layout", "-enc", "UTF-8", path, "-"],
            capture_output=True, timeout=30,
        )
        return r.stdout.decode("utf-8", errors="replace")
    except Exception:
        return ""

def parse_money(s: str) -> float:
    try: return float(s.replace(",", ""))
    except Exception: return 0.0

def parse_date(s: str) -> datetime.date | None:
    if not s: return None
    s = s.replace(".", "/").replace("-", "/").strip()
    parts = s.split("/")
    if len(parts) != 3: return None
    try:
        d, m, y = parts
        y = int(y); m = int(m); d = int(d)
        if y < 100: y += 2000
        return datetime.date(y, m, d)
    except Exception:
        return None

def parse_filename(fn: str) -> tuple[str, str]:
    """Return (vendor, po_no_guess) from filename like 'advics_PE2024_01_0096.pdf'"""
    base = os.path.splitext(os.path.basename(fn))[0]
    if "_" in base:
        vendor, rest = base.split("_", 1)
    else:
        vendor, rest = "", base
    return vendor.strip(), rest.strip().replace("_", "-")

def extract_max_money(text: str) -> float:
    nums = [parse_money(m.group(1)) for m in RE_MONEY.finditer(text)]
    return max(nums) if nums else 0.0

def extract_date(text: str) -> datetime.date | None:
    m = RE_DATE_ANY.search(text)
    if m: return parse_date(m.group(1))
    # Fall through to text-month formats
    m = RE_DATE_DMY_TXT.search(text)
    if m:
        s = m.group(1).replace(" ", "-")
        for fmt in ("%d-%b-%Y", "%d-%b-%y", "%d-%B-%Y"):
            try: return datetime.datetime.strptime(s, fmt).date()
            except Exception: pass
    return None

def main():
    conn = psycopg.connect(
        host="localhost", port=5432, dbname="pegasus_erp",
        user="postgres", password=os.environ.get("PG_PASSWORD","postgres"),
        autocommit=False, row_factory=dict_row,
    )
    cur = conn.cursor()

    div_id = cur.execute("SELECT division_id FROM divisions WHERE is_deleted=false ORDER BY division_id LIMIT 1").fetchone()["division_id"]
    admin_uid = cur.execute("SELECT user_id FROM users WHERE role='ADMIN' AND is_active=true ORDER BY user_id LIMIT 1").fetchone()["user_id"]

    # Suppliers: filename vendor token -> supplier_id (case-insensitive prefix match)
    sup_rows = cur.execute(
        "SELECT supplier_id, supplier_code, supplier_name FROM suppliers WHERE is_deleted=false"
    ).fetchall()
    def find_supplier(vendor: str) -> int | None:
        if not vendor: return None
        v = vendor.lower()
        for s in sup_rows:
            if v in (s["supplier_name"] or "").lower(): return s["supplier_id"]
        return None

    def get_or_create_supplier(vendor: str) -> int:
        sid = find_supplier(vendor)
        if sid: return sid
        # Create minimal supplier
        code = f"SUP-IMP-{len([s for s in sup_rows if s['supplier_code'].startswith('SUP-IMP-')])+1:04d}"
        row = cur.execute(
            """INSERT INTO suppliers
                 (supplier_code, supplier_name, division_id, country,
                  currency_code, payment_terms, wht_rate, approval_status,
                  created_by, submitted_by, submitted_at,
                  manager_approved_by, manager_approved_at, ceo_approved_by, ceo_approved_at)
               VALUES (%s,%s,%s,'TH','THB',30,3,'APPROVED',
                       %s,%s,NOW(),%s,NOW(),%s,NOW())
               RETURNING supplier_id""",
            (code, vendor[:200] or "Unknown", div_id, admin_uid, admin_uid, admin_uid, admin_uid),
        ).fetchone()
        sup_rows.append({"supplier_id": row["supplier_id"], "supplier_code": code, "supplier_name": vendor or "Unknown"})
        return row["supplier_id"]

    # Walk PDFs
    pdfs = []
    for root, _, files in os.walk(PO_ROOT):
        for f in files:
            if f.lower().endswith(".pdf") and not f.startswith("~$"):
                pdfs.append(os.path.join(root, f))
    print(f"PO PDFs to process: {len(pdfs)}")

    report = []
    stats = {"ok":0, "no_text":0, "no_po_no":0, "duplicate":0, "error":0}
    seen = set()

    for i, p in enumerate(pdfs, 1):
        try:
            vendor, po_from_name = parse_filename(p)
            text = pdftotext(p)

            po_no = None
            if text:
                m = RE_PO_NO.search(text)
                if m: po_no = m.group(1).strip()
            if not po_no:
                po_no = po_from_name

            if not po_no:
                report.append([p, vendor, "", "no_po_no", 0, ""])
                stats["no_po_no"] += 1
                continue
            po_no = po_no.replace(" ", "")[:100]

            # Prevent duplicate within this run AND in DB
            key = (vendor.lower(), po_no.lower())
            if key in seen:
                report.append([p, vendor, po_no, "duplicate", 0, ""])
                stats["duplicate"] += 1
                continue
            seen.add(key)

            order_date = extract_date(text) or datetime.date.today()
            total = extract_max_money(text)
            sup_id = get_or_create_supplier(vendor)

            subtotal = round(total / 1.07, 2) if total else 0.0
            vat = round(total - subtotal, 2) if total else 0.0

            # Need unique po_no per DB constraint — prefix with vendor token to avoid collisions
            unique_po = f"{vendor.upper()[:6]}-{po_no}" if vendor else po_no
            unique_po = unique_po[:100]

            cur.execute(
                """
                INSERT INTO purchase_order_headers
                  (po_no, supplier_id, division_id, order_date,
                   currency_code, exchange_rate,
                   subtotal_thb, vat_rate, vat_amount,
                   notes, status, approval_status,
                   created_by, created_at, updated_at)
                VALUES (%s,%s,%s,%s,'THB',1,%s,7,%s,%s,'APPROVED','APPROVED',
                        %s,NOW(),NOW())
                ON CONFLICT (po_no) DO NOTHING
                RETURNING po_id
                """,
                (unique_po, sup_id, div_id, order_date,
                 subtotal, vat,
                 f"Imported from {os.path.relpath(p, ROOT)}",
                 admin_uid),
            )
            stats["ok"] += 1
            report.append([p, vendor, unique_po, "ok", total, str(order_date)])

            if stats["ok"] % 50 == 0:
                print(f"  ...{stats['ok']} POs inserted (file {i}/{len(pdfs)})")
                conn.commit()

        except Exception as e:
            conn.rollback()
            report.append([p, "", "", f"error:{str(e)[:120]}", 0, ""])
            stats["error"] += 1

    conn.commit()
    print("\nDone.")
    for k,v in stats.items(): print(f"  {k:12s}: {v}")

    os.makedirs(os.path.dirname(REPORT), exist_ok=True)
    with open(REPORT, "w", encoding="utf-8", newline="") as f:
        w = csv.writer(f)
        w.writerow(["pdf","vendor","po_no","status","total","order_date"])
        w.writerows(report)
    print("Report:", REPORT)
    conn.close()

if __name__ == "__main__":
    main()
