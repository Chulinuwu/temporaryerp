import pandas as pd
import sys
import bcrypt

df = pd.read_excel(r'C:\Users\R.Nozaki\Downloads\E-mail list.xlsx', sheet_name='BUSINESS CARD', header=None)

employees = []
for i in range(6, len(df)):
    row = df.iloc[i]
    no = row[0]
    if pd.isna(no) or not isinstance(no, (int, float)):
        continue
    no = int(no)
    emp_type = row[22] if pd.notna(row[22]) else -1
    if int(emp_type) != 0:
        continue

    name_jp = str(row[1]).strip() if pd.notna(row[1]) else ''
    name_en = str(row[2]).strip() if pd.notna(row[2]) else ''
    name_th = str(row[3]).strip() if pd.notna(row[3]) else ''
    dept = str(row[4]).strip() if pd.notna(row[4]) else '-'
    job = str(row[5]).strip() if pd.notna(row[5]) else ''
    mobile = str(row[6]).strip() if pd.notna(row[6]) else ''
    email = str(row[7]).strip() if pd.notna(row[7]) else ''
    nickname = str(row[8]).strip() if pd.notna(row[8]) else ''
    hire_date = row[13]
    salary = float(row[28]) if pd.notna(row[28]) else 0

    if hire_date and hasattr(hire_date, 'strftime'):
        hire_date = hire_date.strftime('%Y-%m-%d')
    else:
        hire_date = '2024-01-01'

    dept_norm = dept.replace('Department', 'Dept.').strip()
    if dept_norm == '-':
        dept_norm = 'Management'

    nat = 'TH'
    if any(x in name_en.lower() for x in ['nguyen', 'pham', 'doan', 'phan']):
        nat = 'VN'
    if any(x in name_en.lower() for x in ['nozaki', 'nakano']):
        nat = 'JP'

    employees.append({
        'no': no, 'name_jp': name_jp, 'name_en': name_en, 'name_th': name_th,
        'dept': dept_norm, 'job': job, 'mobile': mobile, 'email': email,
        'nickname': nickname, 'hire_date': hire_date, 'salary': salary, 'nat': nat
    })

def esc(s):
    return s.replace("'", "''").replace('\u200b', '')

def dept_id_sql(dept):
    mapping = {
        'Sales & Consulting Dept.': '1',
        'Application Engineer Dept.': "(SELECT department_id FROM departments WHERE department_code = 'APPENG' LIMIT 1)",
        'IoT Engineer Dept.': "(SELECT department_id FROM departments WHERE department_code = 'IOTENG' LIMIT 1)",
        'Administration Dept.': "(SELECT department_id FROM departments WHERE department_code = 'ADMIN_DEPT' LIMIT 1)",
        'Mechanical Engineer Dept.': "(SELECT department_id FROM departments WHERE department_code = 'MECHENG' LIMIT 1)",
        'Management': '8',
    }
    return mapping.get(dept, '7')

def get_role(email, job):
    if email == 'wannasiwaporn.k@tomastc.com':
        return 'ADMIN'
    jl = job.lower()
    if 'manager' in jl or 'cto' in jl or 'gm ' in jl:
        return 'MANAGER'
    if 'sales' in jl and 'engineer' not in jl:
        return 'SALES'
    if 'accounting' in jl:
        return 'ACCOUNTING'
    if 'admin' in jl and 'engineer' not in jl:
        return 'HR'
    if 'driver' in jl:
        return 'STAFF'
    return 'STAFF'

default_pw = 'Pegasus2026!'
admin_pw = 'PegasusAdmin2026!'
pw_hash = bcrypt.hashpw(default_pw.encode(), bcrypt.gensalt(rounds=10)).decode()
admin_hash = bcrypt.hashpw(admin_pw.encode(), bcrypt.gensalt(rounds=10)).decode()

lines = []
lines.append('-- ============================================================')
lines.append('-- PEGASUS ERP - Employee Import from E-mail list.xlsx')
lines.append('-- Generated: 2026-04-13')
lines.append('-- ============================================================')
lines.append('')
lines.append('BEGIN;')
lines.append('')
lines.append('-- ============================================================')
lines.append('-- 1. ADD NEW DEPARTMENTS')
lines.append('-- ============================================================')
lines.append("""INSERT INTO departments (department_code, division_id, department_name, department_name_jp) VALUES
('APPENG',     1, 'Application Engineer', 'アプリケーションエンジニア部'),
('IOTENG',     1, 'IoT Engineer',         'IoTエンジニア部'),
('ADMIN_DEPT', 1, 'Administration',       '管理部'),
('MECHENG',    1, 'Mechanical Engineer',  '機械エンジニア部')
ON CONFLICT DO NOTHING;""")
lines.append('')

lines.append('-- ============================================================')
lines.append('-- 2. UPDATE EXISTING ADMIN EMPLOYEE (Ryo Nozaki)')
lines.append('-- ============================================================')
ryo = [e for e in employees if e['email'] == 'nozaki.ryo@tomastc.com'][0]
lines.append(f"""UPDATE employees SET
    full_name = '{esc(ryo['name_en'])}',
    full_name_jp = '{esc(ryo['name_jp'])}',
    full_name_th = '{esc(ryo['name_th'])}',
    position_title = '{esc(ryo['job'])}',
    email = '{ryo['email']}',
    phone = '{esc(ryo['mobile'])}',
    base_salary = {ryo['salary']:.2f},
    nationality = 'JP',
    department_id = 8
WHERE emp_code = 'EMP001';""")
lines.append('')
lines.append(f"UPDATE users SET username = 'nozaki.ryo@tomastc.com', email = 'nozaki.ryo@tomastc.com', password_hash = '{admin_hash}' WHERE user_id = 1;")
lines.append('')

lines.append('-- ============================================================')
lines.append('-- 3. INSERT NEW EMPLOYEES')
lines.append('-- ============================================================')

other_emps = [e for e in employees if e['email'] != 'nozaki.ryo@tomastc.com']

for emp in other_emps:
    emp_code = f"EMP{emp['no']:03d}"
    emp['emp_code'] = emp_code
    role = get_role(emp['email'], emp['job'])
    emp['role'] = role
    did = dept_id_sql(emp['dept'])

    lines.append(f"""INSERT INTO employees (emp_code, division_id, department_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, email, phone, salary_type, base_salary, salary_currency, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('{emp_code}', 1, {did}, '{esc(emp['name_en'])}', '{esc(emp['name_jp'])}', '{esc(emp['name_th'])}', '{esc(emp['nickname'])}', '{emp['nat']}', '{emp['hire_date']}', 'FULL_TIME', '{esc(emp['job'])}', '{emp['email']}', '{esc(emp['mobile'])}', 'MONTHLY', {emp['salary']:.2f}, 'THB', 6, 30, 6, 30, '{role}')
ON CONFLICT (emp_code) DO NOTHING;""")

lines.append('')
lines.append('-- ============================================================')
lines.append('-- 4. CREATE USER ACCOUNTS')
lines.append('-- ============================================================')
lines.append(f"-- Admin password (nozaki.ryo, wannasiwaporn.k): PegasusAdmin2026!")
lines.append(f"-- Default password (all others): Pegasus2026!")
lines.append('')

# wannasiwaporn user
lines.append(f"""INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
VALUES ('wannasiwaporn.k@tomastc.com', '{admin_hash}', 'wannasiwaporn.k@tomastc.com', 'ADMIN',
    (SELECT employee_id FROM employees WHERE emp_code = 'EMP057'), TRUE)
ON CONFLICT (username) DO NOTHING;""")
lines.append('')

for emp in other_emps:
    if emp['email'] in ('-', '', 'wannasiwaporn.k@tomastc.com'):
        continue
    username = emp['email']
    emp_code = emp['emp_code']
    role = emp['role']

    lines.append(f"""INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
VALUES ('{username}', '{pw_hash}', '{emp['email']}', '{role}',
    (SELECT employee_id FROM employees WHERE emp_code = '{emp_code}'), TRUE)
ON CONFLICT (username) DO NOTHING;""")

lines.append('')
lines.append('COMMIT;')
lines.append('')
lines.append('-- ============================================================')
lines.append('-- PASSWORD REFERENCE')
lines.append('-- ============================================================')
lines.append('-- Admin users (nozaki.ryo, wannasiwaporn.k): PegasusAdmin2026!')
lines.append('-- All other users: Pegasus2026!')
lines.append('-- ============================================================')

sql = '\n'.join(lines)
with open(r'C:\Users\R.Nozaki\Downloads\Pegasus_ERP_R1\database\seed_employees.sql', 'w', encoding='utf-8') as f:
    f.write(sql)

print(f'Generated SQL: {len(other_emps)} new employees + 1 update (Ryo Nozaki)')
print(f'Total active employees: {len(employees)}')
print(f'Admin users: nozaki.ryo@tomastc.com, wannasiwaporn.k@tomastc.com')
print(f'New departments: APPENG, IOTENG, ADMIN_DEPT, MECHENG')
