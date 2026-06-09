#!/usr/bin/env python3
"""
PEGASUS ERP - Import quotation line items from PDFs only (no Excel).

Strategy:
 1. Walk all PDFs in QT data/
 2. Parse filename: QT{YYMMDD}_{NN}_{desc}_R{rev}.pdf
 3. For each (date, NN) keep only highest revision R#
 4. Match to quotation_headers by issue_date (+ fuzzy project_name if multiple)
 5. Extract line items via fitz and insert into quotation_lines
"""
import os, re, sys
from collections import defaultdict
import psycopg2
import fitz

QT_DIR = r"C:\Users\R.Nozaki\Downloads\Pegasus_ERP_R1\QT data"
DB = dict(host='localhost', port='5432', dbname='pegasus_erp', user='postgres', password='postgres')

QT_NUM_RE = re.compile(r'QT[A-Z]?(\d{6})[_\-]?N?(\d{1,2})', re.IGNORECASE)
REV_RE = re.compile(r'[_\-]R(\d+)', re.IGNORECASE)


def parse_filename(fname):
    # accept any filename containing QT{YYMMDD}[_-][N]{seq}
    m = QT_NUM_RE.search(fname)
    if not m:
        return None
    yymmdd, seq = m.group(1), m.group(2)
    rv = REV_RE.search(fname)
    rev = rv.group(1) if rv else None
    # extract desc = everything after QT{...}_{seq}_
    tail = fname[m.end():]
    desc = re.sub(r'\.pdf$', '', tail, flags=re.IGNORECASE)
    desc = re.sub(r'[_\-]R\d+$', '', desc, flags=re.IGNORECASE)
    desc = desc.lstrip('_-')
    try:
        yy = int(yymmdd[0:2]); mm = int(yymmdd[2:4]); dd = int(yymmdd[4:6])
        year = 2000 + yy
        from datetime import date
        d = date(year, mm, dd)
    except Exception:
        return None
    return {
        'date': d,
        'seq': seq,
        'desc': desc,
        'rev': int(rev) if rev else 0,
    }


def tokens(s):
    s = (s or '').lower()
    s = re.sub(r'[^a-z0-9]+', ' ', s)
    return set(t for t in s.split() if len(t) > 2)


def similarity(a, b):
    ta, tb = tokens(a), tokens(b)
    if not ta or not tb:
        return 0.0
    inter = ta & tb
    return len(inter) / max(len(ta), len(tb))


def extract_pdf_lines(pdf_path):
    """Extract line items from a TOMAS TECH quotation PDF (reused from import_quotations.py)."""
    try:
        doc = fitz.open(pdf_path)
    except Exception:
        return [], {}
    all_lines = []
    header_info = {}
    for page_num in range(len(doc)):
        page = doc[page_num]
        tables = page.find_tables()
        for tab in tables.tables:
            data = tab.extract()
            if len(data) < 2:
                continue
            header_row = data[0]
            if not header_row or not any('Item' in str(c) or 'Description' in str(c) for c in header_row if c):
                continue
            if len(data) > 1 and data[1]:
                items_row = data[1]
                nos_str  = items_row[0] or ''
                desc_str = items_row[1] or ''
                qty_str  = items_row[2] or ''
                unit_str = items_row[3] or ''
                price_str = items_row[4] or ''
                ext_str  = items_row[5] or ''
                nos   = [x.strip() for x in nos_str.split('\n') if x.strip()]
                descs = [x.strip() for x in desc_str.split('\n') if x.strip()]
                qtys  = [x.strip() for x in qty_str.split('\n') if x.strip()]
                units = [x.strip() for x in unit_str.split('\n') if x.strip()]
                prices = [x.strip() for x in price_str.split('\n') if x.strip()]
                exts  = [x.strip() for x in ext_str.split('\n') if x.strip()]
                desc_idx = qty_idx = unit_idx = price_idx = ext_idx = 0
                for no_idx, no in enumerate(nos):
                    is_cat = bool(re.match(r'^\d+$', no))
                    is_item = bool(re.match(r'^\d+-\d+$', no))
                    if is_cat:
                        cat_desc = descs[desc_idx] if desc_idx < len(descs) else ''
                        desc_idx += 1
                        all_lines.append(dict(line_no=no, is_category=True,
                                              description=cat_desc, quantity=None,
                                              unit=None, unit_price=0, ext_price=0))
                    elif is_item:
                        parts = []
                        if desc_idx < len(descs):
                            parts.append(descs[desc_idx]); desc_idx += 1
                        nxt = no_idx + 1
                        if nxt < len(nos):
                            while desc_idx < len(descs):
                                nn = nos[nxt] if nxt < len(nos) else None
                                if nn and re.match(r'^\d+$', nn): break
                                if nn and re.match(r'^\d+-\d+$', nn): break
                                parts.append(descs[desc_idx]); desc_idx += 1
                        def popf(arr, idx):
                            if idx < len(arr):
                                try: return float(arr[idx].replace(',', '')), idx+1
                                except: return 0.0, idx+1
                            return 0.0, idx
                        qv, qty_idx = (None, qty_idx)
                        if qty_idx < len(qtys):
                            try: qv = float(qtys[qty_idx].replace(',', ''))
                            except: qv = 1
                            qty_idx += 1
                        uv = None
                        if unit_idx < len(units):
                            uv = units[unit_idx]; unit_idx += 1
                        pv, price_idx = popf(prices, price_idx)
                        ev, ext_idx = popf(exts, ext_idx)
                        all_lines.append(dict(line_no=no, is_category=False,
                                              description=' / '.join(parts),
                                              quantity=qv, unit=uv,
                                              unit_price=pv, ext_price=ev))
            # totals
            for row in data[2:]:
                if row and len(row) > 5 and row[3]:
                    label = str(row[3]).strip()
                    vs = str(row[5] or '').strip().replace(',', '')
                    try: val = float(vs)
                    except: val = 0
                    if 'Sub Total' in label:     header_info['subtotal'] = val
                    elif 'Discount' in label:    header_info['discount'] = val
                    elif 'Sales Amount' in label:header_info['sales_amount'] = val
                    elif 'Value Added Tax' in label: header_info['vat'] = val
                    elif 'Grand Total' in label: header_info['grand_total'] = val
        text = page.get_text()
        m = re.search(r'Remark\s*:\s*\n(.+?)(?=Currency|$)', text, re.DOTALL)
        if m and 'remark' not in header_info:
            rl = [l.strip() for l in m.group(1).strip().split('\n') if l.strip()]
            header_info['remark'] = '\n'.join(rl[:5])
        lt = re.search(r'Lead\s*time\s*:\s*(.+)', text)
        if lt and 'lead_time' not in header_info:
            header_info['lead_time'] = lt.group(1).strip()
    doc.close()
    return all_lines, header_info


def main():
    # 1. Index PDFs
    print('[1] Indexing PDFs...')
    by_key = {}  # (date, seq) -> (rev, path, desc)
    total = 0; bad = 0
    for root, _, files in os.walk(QT_DIR):
        for f in files:
            if not f.lower().endswith('.pdf'): continue
            total += 1
            info = parse_filename(f)
            if not info:
                bad += 1
                continue
            key = (info['date'], info['seq'])
            existing = by_key.get(key)
            if not existing or info['rev'] > existing['rev']:
                by_key[key] = {**info, 'path': os.path.join(root, f)}
    print(f'  Total PDFs: {total}  parseable: {total-bad}  unique (date,seq): {len(by_key)}')

    # 2. Load headers
    conn = psycopg2.connect(**DB)
    conn.autocommit = False
    cur = conn.cursor()
    cur.execute("""
        SELECT quotation_id, quotation_no, issue_date, project_name
        FROM quotation_headers
        ORDER BY issue_date, quotation_no
    """)
    headers_by_date = defaultdict(list)
    for qid, qno, d, pname in cur.fetchall():
        headers_by_date[d].append(dict(id=qid, no=qno, date=d, project=pname or ''))
    print(f'[2] Loaded {sum(len(v) for v in headers_by_date.values())} quotation_headers across {len(headers_by_date)} dates')

    # Clear existing lines only
    cur.execute('SELECT COUNT(*) FROM quotation_lines')
    n0 = cur.fetchone()[0]
    print(f'[3] Existing quotation_lines: {n0} -> deleting')
    cur.execute('DELETE FROM quotation_lines')
    conn.commit()

    # 3. For each PDF, match to header
    print('[4] Matching and importing...')
    matched = 0
    no_date_hdr = 0
    no_match = 0
    lines_inserted = 0
    errors = []
    used_header_ids = set()

    # Process PDFs in date/seq order
    keys = sorted(by_key.keys(), key=lambda k: (k[0], k[1]))
    for key in keys:
        pdf = by_key[key]
        d = pdf['date']; desc = pdf['desc']
        candidates = headers_by_date.get(d, [])
        if not candidates:
            no_date_hdr += 1
            continue
        # rank by project similarity + prefer unused headers
        ranked = sorted(
            candidates,
            key=lambda h: (h['id'] in used_header_ids, -similarity(desc, h['project']))
        )
        best = ranked[0]
        sim = similarity(desc, best['project'])
        # require min similarity if multiple candidates
        if len(candidates) > 1 and sim < 0.15 and best['id'] in used_header_ids:
            no_match += 1
            continue
        if best['id'] in used_header_ids and len(candidates) <= 1:
            # reuse same header (multiple PDFs same QT) - skip
            continue
        used_header_ids.add(best['id'])
        matched += 1

        try:
            cur.execute('SAVEPOINT sp')
            lines, hdr = extract_pdf_lines(pdf['path'])
            if not lines:
                cur.execute('RELEASE SAVEPOINT sp')
                continue
            # header updates
            updates, params = [], []
            if hdr.get('remark'):
                updates.append('remark_text = %s'); params.append(hdr['remark'])
            if hdr.get('lead_time'):
                updates.append('lead_time_text = %s'); params.append(hdr['lead_time'])
            if updates:
                params.append(best['id'])
                cur.execute(f"UPDATE quotation_headers SET {', '.join(updates)} WHERE quotation_id = %s", params)
            # insert lines
            used_ln = set()
            sort_order = 0
            for ln in lines:
                sort_order += 1
                line_no = str(ln['line_no'])
                parent = line_no.split('-')[0] if (not ln['is_category'] and '-' in line_no) else None
                base_ln = line_no
                suf = 1
                while line_no in used_ln:
                    suf += 1
                    line_no = f'{base_ln}_{suf}'
                used_ln.add(line_no)
                cur.execute("""
                    INSERT INTO quotation_lines (
                        quotation_id, line_no, parent_line_no, is_category_row,
                        item_description, quantity, unit,
                        unit_price, discount_rate, ext_price, cost_total, sort_order
                    ) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,0,%s,0,%s)
                """, [best['id'], line_no, parent, ln['is_category'],
                      (ln['description'] or '')[:500],
                      ln['quantity'], (ln['unit'] or '')[:30] if ln['unit'] else None,
                      ln['unit_price'], ln['ext_price'], sort_order])
                lines_inserted += 1
            cur.execute('RELEASE SAVEPOINT sp')
        except Exception as e:
            cur.execute('ROLLBACK TO SAVEPOINT sp')
            errors.append((pdf['path'], str(e)))
            if len(errors) <= 5:
                print(f'  ERROR {os.path.basename(pdf["path"])[:50]}: {e}')

        if matched % 200 == 0:
            conn.commit()
            print(f'  ... {matched} PDFs matched, {lines_inserted} lines')

    conn.commit()
    print('\n=== Summary ===')
    print(f'  PDFs processed:   {len(by_key)}')
    print(f'  Matched:          {matched}')
    print(f'  No header @date:  {no_date_hdr}')
    print(f'  No project match: {no_match}')
    print(f'  Lines inserted:   {lines_inserted}')
    print(f'  Errors:           {len(errors)}')
    cur.close(); conn.close()


if __name__ == '__main__':
    main()
