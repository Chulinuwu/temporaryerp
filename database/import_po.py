#!/usr/bin/env python3
"""
PEGASUS ERP - PO PDF Import Script
Reads PO PDFs, extracts header/line data, registers suppliers and purchase orders.
Only processes text-based PDFs (Format A). Image-based PDFs are skipped.
"""

import os
import re
import sys
import io
import traceback
import fitz  # PyMuPDF
import psycopg2
from psycopg2.extras import RealDictCursor
from datetime import datetime

# Fix Windows console encoding
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

# ── Configuration ──
DB_CONFIG = {
    'host': 'localhost',
    'dbname': 'pegasus_erp',
    'user': 'postgres',
    'password': '',
}

PO_ROOT = r"C:\Users\R.Nozaki\Google Drive\01.Business\02.Project\★プロジェクト管理\PO list"
DIVISION_ID = 1
CREATED_BY = 1

# ── Database Connection ──
conn = psycopg2.connect(**DB_CONFIG)
conn.autocommit = False
cur = conn.cursor(cursor_factory=RealDictCursor)

# ── Supplier cache ──
supplier_cache = {}  # tax_id -> supplier_id


def clean_text(text):
    if not text:
        return ''
    return re.sub(r'\s+', ' ', text).strip()


def parse_number(s):
    if not s:
        return 0.0
    s = str(s).strip().replace(',', '').replace(' ', '')
    s = re.sub(r'[^\d.\-]', '', s)
    try:
        return float(s) if s else 0.0
    except:
        return 0.0


def parse_date(s):
    if not s or s.strip() in ['-', '']:
        return None
    s = s.strip()
    m = re.match(r'(\d{1,2})/(\d{1,2})/(\d{4})', s)
    if m:
        d, mo, y = int(m.group(1)), int(m.group(2)), int(m.group(3))
        try:
            return datetime(y, mo, d).strftime('%Y-%m-%d')
        except:
            pass
    return None


def extract_po_from_text(text):
    """
    Extract PO data from page 1 text (Format A).
    Text is newline-separated from PyMuPDF get_text().

    Typical structure:
      ID\nDescription\nQt.\nPrice\nDsc.\nVat\nPre-Tax Amount
      1\nDR22D0L-M3W\nSIGNALTOWER...\n1.00\n402.71\n0.00\n7%\n402.71
      Remark\n-\nAdditional Info\nPayment
      Purchase Order\nRef. : XXX\nDate : DD/MM/YYYY\nDue Date : ...\nNo. : PO-XXXXX
      TOMAS TECH CO., LTD (Head Office) : 0115564003364
      Seller\n...\nบริษัท XXX จำกัด (สำนักงานใหญ่) : TAXID
      Discount\n0.00\nTotal (VAT-Exempted amount)\n0.00\nVAT\n28.19\n
      Grand Total (baht)\n430.90\nTotal (Pre-VAT amount)\n402.71
    """
    data = {
        'po_no': None, 'ref': None, 'order_date': None, 'due_date': None,
        'seller_name_th': None, 'seller_tax_id': None, 'seller_address': None,
        'seller_tel': None, 'lines': [],
        'discount': 0, 'subtotal': 0, 'vat_amount': 0, 'grand_total': 0,
    }

    lines = text.split('\n')

    # ── Extract header fields ──
    for i, line in enumerate(lines):
        line_s = line.strip()

        # PO No
        if line_s.startswith('No. :') or line_s.startswith('No.:'):
            m = re.search(r'(PO-?\d[\w\-]*)', line_s)
            if m:
                data['po_no'] = m.group(1)

        # Ref
        if line_s.startswith('Ref. :') or line_s.startswith('Ref.:'):
            ref = line_s.split(':', 1)[1].strip()
            if ref and ref != '-':
                data['ref'] = ref

        # Date
        if line_s.startswith('Date :') or line_s.startswith('Date:'):
            m = re.search(r'(\d{1,2}/\d{1,2}/\d{4})', line_s)
            if m:
                data['order_date'] = parse_date(m.group(1))

        # Due Date
        if 'Due Date' in line_s:
            m = re.search(r'(\d{1,2}/\d{1,2}/\d{4})', line_s)
            if m:
                data['due_date'] = parse_date(m.group(1))

    # ── Extract seller info ──
    # Structure: after "Seller" line, we have:
    #   \xa0 lines (empty), address, Tel line, then "บริษัท XXX จำกัด (...) : TAXID"
    # The company name+tax line comes AFTER Tel, before "You can scan"
    tomas_tax = '0115564003364'

    # Find all company name + tax ID patterns in the entire text
    company_pattern = re.compile(
        r'(บริษัท\s*.+?(?:จำกัด|จํากัด)(?:\s*\([^)]*\))?)\s*[:：]\s*(\d{10,13})'
    )
    for cm in company_pattern.finditer(text):
        tax_id = cm.group(2).strip()
        if tax_id != tomas_tax:
            data['seller_name_th'] = clean_text(cm.group(1))
            data['seller_tax_id'] = tax_id
            break

    # If not found with combined pattern, look separately
    if not data['seller_name_th']:
        all_companies = re.findall(r'(บริษัท\s*.+?(?:จำกัด|จํากัด)(?:\s*\([^)]*\))?)', text)
        for comp in all_companies:
            name = clean_text(comp)
            if 'โทมัส' not in name and 'TOMAS' not in name.upper():
                data['seller_name_th'] = name
                break

    if not data['seller_tax_id']:
        all_tax = re.findall(r'(\d{13})', text)
        for tid in all_tax:
            if tid != tomas_tax:
                data['seller_tax_id'] = tid
                break

    # Extract seller address and tel from Seller section
    seller_section = False
    for i, line in enumerate(lines):
        line_s = line.strip()
        if line_s == 'Seller':
            seller_section = True
            continue
        if seller_section:
            if not data.get('seller_address') and len(line_s) > 20 and any(
                kw in line_s for kw in ['เลขที่', 'หมู่', 'ถนน', 'ตำบล', 'แขวง', 'อำเภอ', 'จังหวัด', 'Moo ', 'Soi ']
            ):
                data['seller_address'] = clean_text(line_s)
            if line_s.startswith('Tel'):
                m = re.search(r'Tel\s*[:：]?\s*([\d\-\s]+)', line_s)
                if m:
                    tel = m.group(1).strip()
                    if tel != '-' and len(tel) > 3:
                        data['seller_tel'] = tel
                break  # Done with seller section

    # ── Extract totals ──
    for i, line in enumerate(lines):
        line_s = line.strip()

        if line_s == 'Discount' and i + 1 < len(lines):
            data['discount'] = parse_number(lines[i + 1].strip())

        if line_s == 'Total (Pre-VAT amount)' and i + 1 < len(lines):
            data['subtotal'] = parse_number(lines[i + 1].strip())

        if line_s == 'VAT' and i + 1 < len(lines):
            val = lines[i + 1].strip()
            if re.match(r'^[\d,]+\.?\d*$', val):
                data['vat_amount'] = parse_number(val)

        if line_s == 'Grand Total (baht)' and i + 1 < len(lines):
            data['grand_total'] = parse_number(lines[i + 1].strip())

    # ── Extract line items ──
    # Find the table header, then parse items until "Remark"
    header_idx = None
    remark_idx = None
    for i, line in enumerate(lines):
        if 'Pre-Tax Amount' in line:
            header_idx = i
        if line.strip() == 'Remark' and header_idx is not None:
            remark_idx = i
            break

    if header_idx is not None:
        end_idx = remark_idx if remark_idx else len(lines)
        item_lines = lines[header_idx + 1:end_idx]

        # Parse items: look for numeric ID at start, then collect description lines,
        # then numeric fields (qty, price, dsc, vat%, amount)
        current_item = None
        desc_parts = []

        for line in item_lines:
            line_s = line.strip()
            if not line_s or line_s == '\xa0':
                continue

            # Check if this is a new item line (starts with a number)
            m = re.match(r'^(\d+)$', line_s)
            if m:
                # Save previous item
                if current_item and current_item.get('ext_price', 0) > 0:
                    current_item['description'] = clean_text(' '.join(desc_parts))
                    data['lines'].append(current_item)

                current_item = {'line_no': int(m.group(1))}
                desc_parts = []
                continue

            if current_item is not None:
                # Check if this is a numeric value (qty, price, etc)
                if re.match(r'^[\d,]+\.?\d*$', line_s) or re.match(r'^\d+%$', line_s):
                    if 'quantity' not in current_item:
                        current_item['quantity'] = parse_number(line_s)
                    elif 'unit_price' not in current_item:
                        current_item['unit_price'] = parse_number(line_s)
                    elif 'discount' not in current_item:
                        current_item['discount'] = parse_number(line_s)
                    elif 'vat_pct' not in current_item:
                        current_item['vat_pct'] = line_s
                    elif 'ext_price' not in current_item:
                        current_item['ext_price'] = parse_number(line_s)
                else:
                    # Description text
                    desc_parts.append(line_s)

        # Save last item
        if current_item and current_item.get('ext_price', 0) > 0:
            current_item['description'] = clean_text(' '.join(desc_parts))
            data['lines'].append(current_item)

    # If no subtotal, calculate from lines
    if data['subtotal'] == 0 and data['lines']:
        data['subtotal'] = sum(l.get('ext_price', 0) for l in data['lines'])

    if data['grand_total'] == 0 and data['subtotal'] > 0:
        data['grand_total'] = data['subtotal'] + data['vat_amount']

    return data


def find_or_create_supplier(tax_id, name_th, address=None, tel=None):
    """Find supplier by tax_id or name, create if not found"""
    # Check cache first
    cache_key = tax_id or name_th or ''
    if cache_key in supplier_cache:
        return supplier_cache[cache_key]

    # Search by tax_id
    if tax_id and len(tax_id) >= 10:
        cur.execute(
            "SELECT supplier_id FROM suppliers WHERE tax_id = %s AND is_deleted = FALSE LIMIT 1",
            [tax_id]
        )
        row = cur.fetchone()
        if row:
            supplier_cache[cache_key] = row['supplier_id']
            return row['supplier_id']

    # Search by name (exact normalized match)
    if name_th:
        normalized = re.sub(r'\s+', '', name_th)
        cur.execute("SELECT supplier_id, supplier_name, supplier_name_th FROM suppliers WHERE is_deleted = FALSE")
        for row in cur.fetchall():
            for field in ['supplier_name', 'supplier_name_th']:
                existing = row.get(field) or ''
                if re.sub(r'\s+', '', existing) == normalized:
                    supplier_cache[cache_key] = row['supplier_id']
                    return row['supplier_id']

    # Create new supplier
    cur.execute(
        "SELECT supplier_code FROM suppliers WHERE supplier_code LIKE 'SUP-%' ORDER BY supplier_id DESC LIMIT 1"
    )
    row = cur.fetchone()
    next_num = 1
    if row and row['supplier_code']:
        m = re.search(r'SUP-(\d+)', row['supplier_code'])
        if m:
            next_num = int(m.group(1)) + 1
    supplier_code = f'SUP-{next_num:04d}'

    supplier_name = name_th or 'Unknown Supplier'

    cur.execute(
        """INSERT INTO suppliers (supplier_code, division_id, supplier_name, supplier_name_th,
           country, address, tax_id, phone, currency_code, payment_terms, created_by)
           VALUES (%s, %s, %s, %s, 'TH', %s, %s, %s, 'THB', 30, %s)
           RETURNING supplier_id""",
        [supplier_code, DIVISION_ID, supplier_name, name_th,
         address, tax_id, tel, CREATED_BY]
    )
    new_row = cur.fetchone()
    sid = new_row['supplier_id']
    supplier_cache[cache_key] = sid
    print(f"    + NEW SUPPLIER: {supplier_code} = {supplier_name[:50]} (Tax: {tax_id or 'N/A'})")
    return sid


def import_po(data, pdf_path):
    """Import a single PO into the database"""
    po_no = data['po_no']

    # Find or create supplier
    supplier_id = None
    if data.get('seller_name_th') or data.get('seller_tax_id'):
        supplier_id = find_or_create_supplier(
            data.get('seller_tax_id'),
            data.get('seller_name_th'),
            data.get('seller_address'),
            data.get('seller_tel'),
        )

    if not supplier_id:
        # Create/find generic unknown supplier
        if 'UNKNOWN' not in supplier_cache:
            cur.execute("SELECT supplier_id FROM suppliers WHERE supplier_code = 'SUP-UNKNOWN' LIMIT 1")
            row = cur.fetchone()
            if not row:
                cur.execute(
                    """INSERT INTO suppliers (supplier_code, division_id, supplier_name, country, currency_code, payment_terms, created_by)
                       VALUES ('SUP-UNKNOWN', %s, 'Unknown Supplier', 'TH', 'THB', 30, %s) RETURNING supplier_id""",
                    [DIVISION_ID, CREATED_BY]
                )
                row = cur.fetchone()
            supplier_cache['UNKNOWN'] = row['supplier_id']
        supplier_id = supplier_cache['UNKNOWN']

    subtotal = data.get('subtotal', 0) or 0
    discount = data.get('discount', 0) or 0
    vat_amount = data.get('vat_amount', 0) or 0
    grand_total = data.get('grand_total', 0) or 0

    status = 'APPROVED'
    fname = os.path.basename(pdf_path).upper()
    if 'DRAFT' in fname:
        status = 'DRAFT'

    cur.execute(
        """INSERT INTO purchase_order_headers (
            po_no, division_id, reference_no, supplier_quotation_no,
            supplier_id, order_date, requested_date,
            currency_code, exchange_rate,
            subtotal_thb, discount_amount, vat_rate, vat_amount,
            total_before_wht, payment_amount,
            status, notes, created_by
        ) VALUES (
            %s, %s, %s, %s,
            %s, %s, %s,
            'THB', 1,
            %s, %s, 7.00, %s,
            %s, %s,
            %s, %s, %s
        ) RETURNING po_id""",
        [
            po_no, DIVISION_ID, data.get('ref'), data.get('ref'),
            supplier_id, data.get('order_date') or '2026-01-01', data.get('due_date'),
            subtotal, discount, vat_amount,
            subtotal + vat_amount, grand_total,
            status, f"Imported from PDF: {os.path.basename(pdf_path)}", CREATED_BY,
        ]
    )
    po_row = cur.fetchone()
    po_id = po_row['po_id']

    # Insert line items
    for line in data.get('lines', []):
        cur.execute(
            """INSERT INTO purchase_order_lines (
                po_id, line_no, item_description, quantity, unit,
                unit_price, discount_rate, ext_price
            ) VALUES (%s, %s, %s, %s, 'EA', %s, %s, %s)""",
            [
                po_id, line['line_no'], line.get('description', ''),
                line.get('quantity', 0), line.get('unit_price', 0),
                line.get('discount', 0), line.get('ext_price', 0),
            ]
        )

    return True


def collect_pdf_files(root_dir):
    pdf_files = []
    for dirpath, dirnames, filenames in os.walk(root_dir):
        for fname in sorted(filenames):
            if fname.lower().endswith('.pdf'):
                pdf_files.append(os.path.join(dirpath, fname))
    return pdf_files


def main():
    print("=" * 70)
    print("PEGASUS ERP - PO PDF Import")
    print("=" * 70)

    pdf_files = collect_pdf_files(PO_ROOT)
    print(f"Found {len(pdf_files)} PDF files\n")

    total = len(pdf_files)
    imported = 0
    duplicates = 0
    image_pdfs = 0
    no_po = 0
    errors = 0

    # Count suppliers before
    cur.execute("SELECT COUNT(*) as cnt FROM suppliers WHERE is_deleted = FALSE")
    suppliers_before = cur.fetchone()['cnt']

    for i, pdf_path in enumerate(pdf_files):
        fname = os.path.basename(pdf_path)
        rel_path = os.path.relpath(pdf_path, PO_ROOT)

        if (i + 1) % 100 == 0:
            print(f"\n--- Progress: {i+1}/{total} (imported: {imported}, img: {image_pdfs}, dup: {duplicates}, err: {errors}) ---")

        try:
            # Open PDF, get page 1 text
            doc = fitz.open(pdf_path)
            if len(doc) == 0:
                doc.close()
                no_po += 1
                continue

            page = doc[0]
            text = page.get_text('text')
            doc.close()

            # Skip image-based PDFs
            if not text or len(text.strip()) < 30:
                image_pdfs += 1
                continue

            # Must contain "Purchase Order"
            if 'Purchase Order' not in text:
                no_po += 1
                continue

            # Extract data
            cur.execute("SAVEPOINT sp_po")

            data = extract_po_from_text(text)

            # Fallback: get PO no from filename if not found in text
            if not data['po_no']:
                po_from_file = None
                m = re.search(r'(PO-?\d[\w\-]*)', fname, re.IGNORECASE)
                if m:
                    po_from_file = m.group(1).upper()
                    # Normalize: remove # prefix
                    po_from_file = po_from_file.lstrip('#')
                if po_from_file:
                    data['po_no'] = po_from_file
                else:
                    no_po += 1
                    cur.execute("ROLLBACK TO SAVEPOINT sp_po")
                    continue

            # Check duplicate
            cur.execute("SELECT po_id FROM purchase_order_headers WHERE po_no = %s", [data['po_no']])
            if cur.fetchone():
                duplicates += 1
                cur.execute("ROLLBACK TO SAVEPOINT sp_po")
                continue

            result = import_po(data, pdf_path)
            if result:
                imported += 1
                cur.execute("RELEASE SAVEPOINT sp_po")
                if imported <= 30 or imported % 100 == 0:
                    seller = (data.get('seller_name_th') or 'N/A')[:45]
                    lc = len(data.get('lines', []))
                    gt = data.get('grand_total', 0)
                    print(f"  [{imported:4d}] {data['po_no']:22s} | {data.get('order_date','?'):10s} | "
                          f"{seller:45s} | L:{lc} | {gt:>12,.2f}")
            else:
                cur.execute("ROLLBACK TO SAVEPOINT sp_po")

        except Exception as e:
            errors += 1
            try:
                cur.execute("ROLLBACK TO SAVEPOINT sp_po")
            except:
                pass
            if errors <= 10:
                print(f"  ERROR: {fname}: {e}")

    # Commit
    conn.commit()

    # Stats
    cur.execute("SELECT COUNT(*) as cnt FROM suppliers WHERE is_deleted = FALSE")
    suppliers_after = cur.fetchone()['cnt']

    print("\n" + "=" * 70)
    print("IMPORT RESULTS")
    print("=" * 70)
    print(f"  Total PDFs scanned:       {total}")
    print(f"  Successfully imported:    {imported}")
    print(f"  Image-based (skipped):    {image_pdfs}")
    print(f"  No PO data (skipped):     {no_po}")
    print(f"  Duplicates (skipped):     {duplicates}")
    print(f"  Errors:                   {errors}")
    print(f"  New suppliers created:    {suppliers_after - suppliers_before}")
    print("=" * 70)

    # Summary
    cur.execute("""
        SELECT COUNT(*) as cnt, MIN(order_date) as min_date, MAX(order_date) as max_date,
               SUM(payment_amount) as total_amount,
               SUM((SELECT COUNT(*) FROM purchase_order_lines pol WHERE pol.po_id = poh.po_id)) as total_lines
        FROM purchase_order_headers poh WHERE is_deleted = FALSE
    """)
    s = cur.fetchone()
    print(f"  Total POs in DB:     {s['cnt']}")
    print(f"  Date range:          {s['min_date']} ~ {s['max_date']}")
    print(f"  Total amount:        {float(s['total_amount'] or 0):>15,.2f} THB")
    print(f"  Total line items:    {int(s['total_lines'] or 0)}")

    # Top suppliers
    print(f"\n  Suppliers ({suppliers_after} total):")
    cur.execute("""
        SELECT s.supplier_code, s.supplier_name, s.tax_id,
               COUNT(po.po_id) as po_count, COALESCE(SUM(po.payment_amount),0) as total_amt
        FROM suppliers s
        LEFT JOIN purchase_order_headers po ON po.supplier_id = s.supplier_id AND po.is_deleted = FALSE
        WHERE s.is_deleted = FALSE
        GROUP BY s.supplier_id
        ORDER BY po_count DESC LIMIT 40
    """)
    for row in cur.fetchall():
        print(f"    {row['supplier_code']:12s} | {(row['supplier_name'] or '')[:40]:40s} | "
              f"Tax:{(row['tax_id'] or 'N/A'):15s} | PO:{row['po_count']:3d} | {float(row['total_amt']):>12,.0f}")


if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        conn.rollback()
        print(f"\nFATAL ERROR: {e}")
        traceback.print_exc()
    finally:
        cur.close()
        conn.close()
