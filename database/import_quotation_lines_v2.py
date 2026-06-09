#!/usr/bin/env python3
"""
PEGASUS ERP - v2: Match PDF -> quotation_header by (issue_date + customer + QT No).

Strategy:
 1. Walk PDFs (dedup by date+seq, keep highest revision)
 2. For each PDF extract: header QT No, date, customer, project from text blocks
 3. Resolve PDF customer -> customers.customer_id via fuzzy name match
 4. Pick header matching (issue_date, customer_id), tie-break by project similarity
 5. Fallback to date-only project match if no customer match
 6. Insert quotation_lines
"""
import os, re, sys
from collections import defaultdict
from datetime import date
import psycopg2
import fitz

QT_DIR = r"C:\Users\R.Nozaki\Downloads\Pegasus_ERP_R1\QT data"
DB = dict(host='localhost', port='5432', dbname='pegasus_erp', user='postgres', password='postgres')

QT_FNAME = re.compile(r'QT[A-Z]?(\d{6})[_\-]?N?(\d{1,2})', re.IGNORECASE)
REV_RE = re.compile(r'[_\-]R(\d+)', re.IGNORECASE)


def parse_filename(fname):
    m = QT_FNAME.search(fname)
    if not m: return None
    yymmdd, seq = m.group(1), m.group(2)
    try:
        d = date(2000 + int(yymmdd[0:2]), int(yymmdd[2:4]), int(yymmdd[4:6]))
    except Exception:
        return None
    rv = REV_RE.search(fname)
    rev = int(rv.group(1)) if rv else 0
    return {'date': d, 'seq': seq, 'rev': rev}


def norm_name(s):
    if not s: return ''
    s = s.lower()
    s = re.sub(r'[\(\),\.\-_/]', ' ', s)
    # drop common legal entity words
    for w in ['co ltd','co.,ltd','company limited','limited','ltd','co','corporation',
              'corp','inc','pcl','public company','thailand','(thailand)','thai']:
        s = s.replace(w, ' ')
    s = re.sub(r'\s+', ' ', s).strip()
    return s


def tokens(s, min_len=3):
    s = norm_name(s)
    return set(t for t in re.split(r'\s+', s) if len(t) >= min_len)


def sim(a, b):
    ta, tb = tokens(a), tokens(b)
    if not ta or not tb: return 0.0
    return len(ta & tb) / max(len(ta), len(tb))


def extract_header_and_lines(pdf_path):
    """Return (header_info, lines) from the PDF."""
    try:
        doc = fitz.open(pdf_path)
    except Exception:
        return {}, []
    hdr = {}
    lines = []
    for pno in range(len(doc)):
        page = doc[pno]
        if pno == 0:
            blocks = page.get_text('dict')['blocks']
            for b in blocks:
                if 'lines' not in b: continue
                for ln in b['lines']:
                    txt = ''.join(sp['text'] for sp in ln['spans']).strip()
                    if not txt: continue
                    x, y = ln['bbox'][0], ln['bbox'][1]
                    # customer (To:)  x ~95-380, y ~130-145
                    if 90 < x < 380 and 128 < y < 145 and 'customer' not in hdr:
                        hdr['customer'] = txt
                    # attention y ~145-160
                    elif 90 < x < 380 and 145 < y < 162 and 'attention' not in hdr:
                        hdr['attention'] = txt
                    # project y ~170-190
                    elif 90 < x < 380 and 170 < y < 195 and 'project' not in hdr:
                        hdr['project'] = txt
                    # QT No  x ~440-580, y ~128-145
                    elif 440 < x < 580 and 128 < y < 145 and 'qt_no' not in hdr:
                        hdr['qt_no'] = txt
                    # date   y ~145-160
                    elif 440 < x < 580 and 145 < y < 162 and 'date' not in hdr:
                        hdr['date'] = txt
        # tables
        tables = page.find_tables()
        for tab in tables.tables:
            data = tab.extract()
            if len(data) < 2: continue
            hdr_row = data[0]
            if not hdr_row or not any('Item' in str(c) or 'Description' in str(c) for c in hdr_row if c):
                continue
            if data[1]:
                r = data[1]
                ns = [x.strip() for x in (r[0] or '').split('\n') if x.strip()]
                ds = [x.strip() for x in (r[1] or '').split('\n') if x.strip()]
                qs = [x.strip() for x in (r[2] or '').split('\n') if x.strip()]
                us = [x.strip() for x in (r[3] or '').split('\n') if x.strip()]
                ps = [x.strip() for x in (r[4] or '').split('\n') if x.strip()]
                es = [x.strip() for x in (r[5] or '').split('\n') if x.strip()]
                di = qi = ui = pi = ei = 0
                for i, no in enumerate(ns):
                    is_cat  = bool(re.match(r'^\d+$', no))
                    is_item = bool(re.match(r'^\d+-\d+$', no))
                    if is_cat:
                        cd = ds[di] if di < len(ds) else ''
                        di += 1
                        lines.append(dict(line_no=no, is_category=True,
                                          description=cd, quantity=None, unit=None,
                                          unit_price=0, ext_price=0))
                    elif is_item:
                        parts = []
                        if di < len(ds):
                            parts.append(ds[di]); di += 1
                        nxt = i+1
                        while di < len(ds) and nxt < len(ns):
                            nn = ns[nxt]
                            if re.match(r'^\d+$', nn) or re.match(r'^\d+-\d+$', nn):
                                break
                            parts.append(ds[di]); di += 1
                        qv = None
                        if qi < len(qs):
                            try: qv = float(qs[qi].replace(',', ''))
                            except: qv = 1
                            qi += 1
                        uv = us[ui] if ui < len(us) else None
                        if ui < len(us): ui += 1
                        pv = 0.0
                        if pi < len(ps):
                            try: pv = float(ps[pi].replace(',', ''))
                            except: pass
                            pi += 1
                        ev = 0.0
                        if ei < len(es):
                            try: ev = float(es[ei].replace(',', ''))
                            except: pass
                            ei += 1
                        lines.append(dict(line_no=no, is_category=False,
                                          description=' / '.join(parts),
                                          quantity=qv, unit=uv,
                                          unit_price=pv, ext_price=ev))
        text = page.get_text()
        m = re.search(r'Remark\s*:\s*\n(.+?)(?=Currency|$)', text, re.DOTALL)
        if m and 'remark' not in hdr:
            rl = [l.strip() for l in m.group(1).strip().split('\n') if l.strip()]
            hdr['remark'] = '\n'.join(rl[:5])
        lt = re.search(r'Lead\s*time\s*:\s*(.+)', text)
        if lt and 'lead_time' not in hdr:
            hdr['lead_time'] = lt.group(1).strip()
    doc.close()
    return hdr, lines


def best_customer_match(pdf_customer, cust_list):
    """pdf_customer: raw string. cust_list: [(id, name)]. Returns (id, score)."""
    if not pdf_customer: return (None, 0.0)
    best_id, best_score = None, 0.0
    for cid, name in cust_list:
        s = sim(pdf_customer, name)
        if s > best_score:
            best_score = s; best_id = cid
    return (best_id, best_score)


def main():
    print('[1] Indexing PDFs...')
    by_key = {}
    total = bad = 0
    for root, _, files in os.walk(QT_DIR):
        for f in files:
            if not f.lower().endswith('.pdf'): continue
            total += 1
            info = parse_filename(f)
            if not info:
                bad += 1; continue
            k = (info['date'], info['seq'])
            cur = by_key.get(k)
            if not cur or info['rev'] > cur['rev']:
                by_key[k] = {**info, 'path': os.path.join(root, f)}
    print(f'  Total PDFs: {total}  unparseable: {bad}  unique: {len(by_key)}')

    conn = psycopg2.connect(**DB)
    conn.autocommit = False
    cur = conn.cursor()

    cur.execute("""
        SELECT quotation_id, quotation_no, issue_date, customer_id, project_name
        FROM quotation_headers
        ORDER BY issue_date, quotation_no
    """)
    headers = []
    hdr_by_date = defaultdict(list)
    hdr_by_date_cust = defaultdict(list)
    for qid, qno, d, cid, pn in cur.fetchall():
        h = dict(id=qid, no=qno, date=d, cid=cid, project=pn or '')
        headers.append(h)
        hdr_by_date[d].append(h)
        hdr_by_date_cust[(d, cid)].append(h)
    print(f'[2] Headers: {len(headers)} across {len(hdr_by_date)} dates')

    cur.execute("""SELECT customer_id, customer_name, customer_name_jp, customer_name_th
                   FROM customers WHERE is_deleted=FALSE""")
    custs = cur.fetchall()
    cust_list = [(r[0], r[1]) for r in custs if r[1]]
    cust_list += [(r[0], r[2]) for r in custs if r[2]]
    cust_list += [(r[0], r[3]) for r in custs if r[3]]
    print(f'  Customer name variants: {len(cust_list)}')

    print('[3] Clearing quotation_lines...')
    cur.execute('DELETE FROM quotation_lines')
    conn.commit()

    print('[4] Matching & importing...')
    stats = dict(matched_cust_date=0, matched_date_proj=0, no_header_date=0,
                 no_match=0, lines=0, errors=0, reused=0)
    used_hdr_ids = set()
    keys = sorted(by_key.keys())
    n = 0
    for k in keys:
        pdf = by_key[k]
        d = pdf['date']
        n += 1
        try:
            cur.execute('SAVEPOINT sp')
            hdr, lines = extract_header_and_lines(pdf['path'])
            if not lines:
                cur.execute('RELEASE SAVEPOINT sp'); continue
            candidates_at_date = hdr_by_date.get(d, [])
            if not candidates_at_date:
                stats['no_header_date'] += 1
                cur.execute('RELEASE SAVEPOINT sp'); continue

            chosen = None
            match_type = None
            # Try customer match
            pdf_cust = hdr.get('customer')
            if pdf_cust:
                cid, score = best_customer_match(pdf_cust, cust_list)
                if cid and score >= 0.25:
                    pool = [h for h in candidates_at_date if h['cid'] == cid and h['id'] not in used_hdr_ids]
                    if not pool:
                        pool = [h for h in candidates_at_date if h['cid'] == cid]
                    if pool:
                        # tie-break by project name similarity
                        pdf_proj = hdr.get('project', '')
                        pool.sort(key=lambda h: (h['id'] in used_hdr_ids, -sim(pdf_proj, h['project'])))
                        chosen = pool[0]
                        match_type = 'cust_date'
            # Fallback: project name similarity within date (no threshold — just best)
            if not chosen:
                pdf_proj = hdr.get('project', '')
                pool = [h for h in candidates_at_date if h['id'] not in used_hdr_ids]
                if not pool:
                    # all consumed — try any (allow reuse) for lines that might have moved
                    stats['no_match'] += 1
                    cur.execute('RELEASE SAVEPOINT sp'); continue
                pool.sort(key=lambda h: -sim(pdf_proj, h['project']))
                chosen = pool[0]
                match_type = 'date_proj'

            if not chosen:
                stats['no_match'] += 1
                cur.execute('RELEASE SAVEPOINT sp'); continue

            if chosen['id'] in used_hdr_ids:
                stats['reused'] += 1
                cur.execute('RELEASE SAVEPOINT sp'); continue
            used_hdr_ids.add(chosen['id'])
            stats['matched_' + match_type] = stats.get('matched_' + match_type, 0) + 1

            # Update header with PDF info
            updates, params = [], []
            if hdr.get('remark'):
                updates.append('remark_text = %s'); params.append(hdr['remark'])
            if hdr.get('lead_time'):
                updates.append('lead_time_text = %s'); params.append(hdr['lead_time'])
            if hdr.get('attention'):
                updates.append('attention_name = %s'); params.append(hdr['attention'][:100])
            if updates:
                params.append(chosen['id'])
                cur.execute(f"UPDATE quotation_headers SET {', '.join(updates)} WHERE quotation_id = %s", params)

            used_ln = set()
            so = 0
            for ln in lines:
                so += 1
                line_no = str(ln['line_no'])
                parent = line_no.split('-')[0] if (not ln['is_category'] and '-' in line_no) else None
                base = line_no; suf = 1
                while line_no in used_ln:
                    suf += 1
                    line_no = f'{base}_{suf}'
                used_ln.add(line_no)
                cur.execute("""
                    INSERT INTO quotation_lines (
                        quotation_id, line_no, parent_line_no, is_category_row,
                        item_description, quantity, unit,
                        unit_price, discount_rate, ext_price, cost_total, sort_order
                    ) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,0,%s,0,%s)
                """, [chosen['id'], line_no, parent, ln['is_category'],
                      (ln['description'] or '')[:500],
                      ln['quantity'], (ln['unit'] or '')[:30] if ln['unit'] else None,
                      ln['unit_price'], ln['ext_price'], so])
                stats['lines'] += 1
            cur.execute('RELEASE SAVEPOINT sp')
        except Exception as e:
            cur.execute('ROLLBACK TO SAVEPOINT sp')
            stats['errors'] += 1
            if stats['errors'] <= 5:
                print(f'  ERROR {os.path.basename(pdf["path"])[:50]}: {e}')
        if n % 200 == 0:
            conn.commit()
            print(f'  ...{n}/{len(by_key)}  stats={stats}')

    conn.commit()
    print('\n=== Summary ===')
    print(f"  PDFs unique:           {len(by_key)}")
    print(f"  Matched (cust+date):   {stats.get('matched_cust_date',0)}")
    print(f"  Matched (date+proj):   {stats.get('matched_date_proj',0)}")
    print(f"  No header at date:     {stats['no_header_date']}")
    print(f"  No match:              {stats['no_match']}")
    print(f"  Reused (skipped):      {stats['reused']}")
    print(f"  Lines inserted:        {stats['lines']}")
    print(f"  Errors:                {stats['errors']}")
    cur.close(); conn.close()


if __name__ == '__main__':
    main()
