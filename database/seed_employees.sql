-- PEGASUS ERP - Employee & User Seed Data
-- Generated from E-mail list.xlsx | 2026-04-14
-- Roles: ADMIN(2), SALES_MANAGER(5), ACCOUNTING(1), STAFF(rest)

-- Update role constraint
ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check;
ALTER TABLE users ADD CONSTRAINT users_role_check
    CHECK (role IN ('ADMIN','SALES_MANAGER','ACCOUNTING','STAFF'));

-- EMP001: Ryo Nozaki [ADMIN]
UPDATE employees SET base_salary = 160000.00, position_title = 'CEO', phone = '+66-94-552-3097', email = 'nozaki.ryo@tomastc.com', role = 'ADMIN' WHERE emp_code = 'EMP001';
UPDATE users SET role = 'ADMIN' WHERE username = 'admin';

-- EMP003: Anek Sanohkham [SALES_MANAGER]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP003', 1, 'Anek Sanohkham', '', 'เอนก เสนาะคำ', 'Yung', 'TH', '2020-03-30', 'FULL_TIME', 'Sales & Consulting Dept. Manager', 'MONTHLY', 47000.00, 'THB', 'anek.s@tomastc.com', '+66-84-196-9791', 6, 30, 6, 30, 'SALES_MANAGER')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP005: Arunwit Isarapongporn [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP005', 1, 'Arunwit Isarapongporn', '', 'อรุณวิชญ์  อิสระพงศ์พร', 'Nick', 'TH', '2020-08-03', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 49000.00, 'THB', 'arunwit.i@tomastc.com', '+66-87-327-8259', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP006: Chamnan Thaithani [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP006', 1, 'Chamnan Thaithani', '', 'ชำนาญ ไทยธานี', 'Chan', 'TH', '2024-02-12', 'FULL_TIME', 'Driver', 'MONTHLY', 27000.00, 'THB', NULL, '+66-94-535-7117', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP013: Kittisak Isarapongporn [SALES_MANAGER]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP013', 1, 'Kittisak Isarapongporn', '', 'กิตติศักดิ์ อิสระพงศ์พร', 'Nack', 'TH', '2021-05-01', 'FULL_TIME', 'CTO & Application Group 1G Manager', 'MONTHLY', 120000.00, 'THB', 'kittisak.i@tomastc.com', '+66-97-364-6149', 6, 30, 6, 30, 'SALES_MANAGER')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP015: Nongnut Tophimai [ACCOUNTING]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP015', 1, 'Nongnut Tophimai', '', 'นงนุช ตอพิมาย', 'Nook', 'TH', '2021-04-07', 'FULL_TIME', 'Accounting', 'MONTHLY', 28200.00, 'THB', 'nongnut.t@tomastc.com', '+66-82-132-6650', 6, 30, 6, 30, 'ACCOUNTING')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP020: Soraya Norasing [SALES_MANAGER]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP020', 1, 'Soraya Norasing', '', 'โสรยา นรสิงห์', 'So', 'TH', '2022-01-11', 'FULL_TIME', 'Application Group 3G Manager', 'MONTHLY', 63000.00, 'THB', 'soraya.n@tomastc.com', '+66-96-951-0640', 6, 30, 6, 30, 'SALES_MANAGER')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP025: Nattapol Poeam [SALES_MANAGER]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP025', 1, 'Nattapol Poeam', '', 'ณัฐพล โพธิ์เอี่ยม', 'Boy', 'TH', '2023-04-17', 'FULL_TIME', 'GM & IoT Engineer Department Manager', 'MONTHLY', 80000.00, 'THB', 'nattapol.p@tomastc.com', '+66-85-995-0178', 6, 30, 6, 30, 'SALES_MANAGER')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP036: Panupong Raksakit [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP036', 1, 'Panupong Raksakit', '', 'ภานุพงศ์ รักษากิจ', 'Dear', 'TH', '2024-01-04', 'FULL_TIME', 'IoT Engineer', 'MONTHLY', 65000.00, 'THB', 'panupong.r@tomastc.com', '+66-89-998-4299', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP037: Pornpimon Jinawan [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP037', 1, 'Pornpimon Jinawan', '', 'พรพิมล จินาวัน', 'Mon', 'TH', '2023-12-09', 'FULL_TIME', 'Inside Sales & Admin', 'MONTHLY', 22000.00, 'THB', 'pornpimon.j@tomastc.com', '+66-85-582-9540', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP040: Thanthima Phaengkham [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP040', 1, 'Thanthima Phaengkham', '', 'ทัณฑิมา แพงคำ', 'Ann', 'TH', '2024-04-01', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 70000.00, 'THB', 'thanthima.p@tomastc.com', '+66-98-826-5369', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP041: Ronnagon Kongsuwun [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP041', 1, 'Ronnagon Kongsuwun', '', 'รณกร กองสุวรรณ์', 'Nueng', 'TH', '2024-05-01', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 57000.00, 'THB', 'ronnagon.k@tomastc.com', '+66-92-668-5556', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP043: Phatthadon Inthachot [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP043', 1, 'Phatthadon Inthachot', '', 'พัทธดนย์ อินทะโชติ', 'Toy', 'TH', '2024-06-04', 'FULL_TIME', 'IoT Engineer', 'MONTHLY', 33000.00, 'THB', 'phatthadon.i@tomastc.com', '+66-99-132-8444', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP044: Konlawat Saechee [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP044', 1, 'Konlawat Saechee', '', 'กลวัชร แซ่ชี้', 'Nook', 'TH', '2024-05-06', 'FULL_TIME', 'IoT Engineer Department Assistant Manager', 'MONTHLY', 57000.00, 'THB', 'konlawat.s@tomastc.com', '+66-94-487-2879', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP049: Thiraporn Amornsirinukorh [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP049', 1, 'Thiraporn Amornsirinukorh', '', 'ธิราภรณ์ อมรศิรินุเคราะห์', 'Gam', 'TH', '2024-08-01', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 43000.00, 'THB', 'thiraporn.a@tomastc.com', '+66-82-208-2177', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP050: Nattapong Sukcharoen [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP050', 1, 'Nattapong Sukcharoen', '', 'ณัฐพงศ์ สุขเจริญ', 'Game', 'TH', '2024-09-01', 'FULL_TIME', 'IoT Engineer', 'MONTHLY', 35000.00, 'THB', 'nattapong.s@tomastc.com', '+66-82-712-5067', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP051: Panthita Roekdee [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP051', 1, 'Panthita Roekdee', '', 'ปัณฑิตา ฤกษ์ดี', 'Oil', 'TH', '2024-09-09', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 31000.00, 'THB', 'panthita.r@tomastc.com', '+66-92-919-1622', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP053: Nichapa Bhichaiangkul [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP053', 1, 'Nichapa Bhichaiangkul', '', 'นิชาภา พิชัยอังกูร', 'Teena', 'TH', '2024-10-01', 'FULL_TIME', 'Sales Engineer', 'MONTHLY', 50000.00, 'THB', 'nichapa.b@tomastc.com', '+66-81-159-8090', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP055: Purinat Thongbaiyai [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP055', 1, 'Purinat Thongbaiyai', '', 'ภูริณัฐ ทองใบใหญ่', 'Dream', 'TH', '2024-10-21', 'FULL_TIME', 'IoT Engineer', 'MONTHLY', 51000.00, 'THB', 'purinat.t@tomastc.com', '+66-89-006-9992', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP056: Nattaporn Chaiya [SALES_MANAGER]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP056', 1, 'Nattaporn Chaiya', '', 'ณัฐพร ชัยยะ', 'Pak', 'TH', '2024-10-01', 'FULL_TIME', 'Application Group 2G Manger', 'MONTHLY', 71000.00, 'THB', 'nattaporn.c@tomastc.com', '+66-86-707-5159', 6, 30, 6, 30, 'SALES_MANAGER')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP057: Karnthida Wannasiwaporn [ADMIN]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP057', 1, 'Karnthida Wannasiwaporn', '', 'กานต์ธิดา วรรณศิวพร', 'Mai', 'TH', '2025-01-06', 'FULL_TIME', 'Administrator Department Manager', 'MONTHLY', 53000.00, 'THB', 'wannasiwaporn.k@tomastc.com', '+66-82-767-6255', 6, 30, 6, 30, 'ADMIN')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP060: Soemsak Powe [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP060', 1, 'Soemsak Powe', '', 'เสริมศักดิ์ โพธิ์หวี', 'Nok', 'TH', '2024-12-16', 'FULL_TIME', 'IoT Engineer', 'MONTHLY', 51000.00, 'THB', 'sermsak.p@tomastc.com', '+66-89-897-0712', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP066: Yutthapong Sricha [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP066', 1, 'Yutthapong Sricha', '', 'ยุทธพงษ์ ศรีชา', 'Yut', 'TH', '2025-02-03', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 70000.00, 'THB', 'yutthapong.s@tomastc.com', '+66-88-639-5166', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP069: Surasak Atchanawat [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP069', 1, 'Surasak Atchanawat', '', 'สุรศักดิ์ อัจนวัจน์', 'Ball', 'TH', '2025-04-01', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 80000.00, 'THB', 'surasak.a@tomastc.com', '+66-89-449-5452', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP070: Pattanasak Chaonchom [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP070', 1, 'Pattanasak Chaonchom', '', 'พัฒนศักดิ์ ฉอ้อนโฉม', 'Kong', 'TH', '2025-03-10', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 35000.00, 'THB', 'pattanasak.c@tomastc.com', '+66-94-787-9319', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP071: Surat Duangchiaw [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP071', 1, 'Surat Duangchiaw', '', 'สุราษฎร์ ด้วงเชี่ยว', 'Ryu', 'TH', '2025-03-10', 'FULL_TIME', 'IoT Engineer', 'MONTHLY', 35000.00, 'THB', 'surat.d@tomastc.com', '+66-86-947-6682', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP074: Nattikorn Srisadet [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP074', 1, 'Nattikorn Srisadet', '', 'ณัฐติกรณ์ ศรีษะเดช', 'Nat', 'TH', '2025-06-02', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 65000.00, 'THB', 'nattikorn.s@tomastc.com', '+66-91-701-0088', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP077: Suphawat Tinaso [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP077', 1, 'Suphawat Tinaso', '', 'ศุภวัฒน์ ตินะโส', 'Book', 'TH', '2025-06-23', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 43000.00, 'THB', 'suphawat.t@tomastc.com', '+66-93-538-4867', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP081: Tanong Amnuaypornsri [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP081', 1, 'Tanong Amnuaypornsri', '', 'ทนง อำนวยพรศรี', 'Ton', 'TH', '2025-07-07', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 70000.00, 'THB', 'tanong.a@tomastc.com', '+66-88-620-1702', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP083: Warit Chunlaka [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP083', 1, 'Warit Chunlaka', '', 'วริษฏ์ จุลกะ', 'Natt', 'TH', '2025-08-13', 'FULL_TIME', 'Mechanical Engineering Manager', 'MONTHLY', 61000.00, 'THB', 'warit.c@tomastc.com', '+66-83-263-5076', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP084: Nattawat Hannok [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP084', 1, 'Nattawat Hannok', '', 'ณัฐวัฒน์ หาญนอก', 'Sab', 'TH', '2025-08-13', 'FULL_TIME', 'Mechanical Engineer', 'MONTHLY', 37000.00, 'THB', 'nattawat.h@tomastc.com', '+66-83-516-5322', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP086: Worawit Khantamool [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP086', 1, 'Worawit Khantamool', '', 'วรวิทย์ ขันธมูล', 'Aum', 'TH', '2025-08-04', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 53000.00, 'THB', 'worawit.k@tomastc.com', '+66-80-043-3394', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP087: Supatnchai Hempolchom [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP087', 1, 'Supatnchai Hempolchom', '', 'สุพัฒน์ชัย เหมพลชม', 'At', 'TH', '2025-08-07', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 51000.00, 'THB', 'supatnchai.h@tomastc.com', '+66-93-375-0162', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP088: Trin Tintanee [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP088', 1, 'Trin Tintanee', '', 'ตฤณ ถิ่นธานี', 'Trin', 'TH', '2025-08-25', 'FULL_TIME', 'Sales Engineer', 'MONTHLY', 31000.00, 'THB', 'trin.t@tomastc.com', '+66-99-194-1549', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP089: Siriwilai Chaiwattanaphon [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP089', 1, 'Siriwilai Chaiwattanaphon', '', 'สิริวิลัย ชัยวัฒนาพนธ์', 'Bew', 'TH', '2025-09-29', 'FULL_TIME', 'Engineer and Administrator', 'MONTHLY', 40000.00, 'THB', 'siriwilai.c@tomastc.com', '+66-80-025-8369', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP091: Kittikun Chunhachotwanit [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP091', 1, 'Kittikun Chunhachotwanit', '', 'กิตติคุณ ชุณหโชตวานิช', 'Kit', 'TH', '2025-10-01', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 29000.00, 'THB', 'kittikun.c@tomastc.com', '+66-93-191-5254', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP092: Taweesak Suriyon [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP092', 1, 'Taweesak Suriyon', '', 'ทวีศักดิ์ สุริยนต์', 'Mos', 'TH', '2025-10-08', 'FULL_TIME', 'IoT Engineer', 'MONTHLY', 47000.00, 'THB', 'taweesak.s@tomastc.com', '+66-87-568-1279', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP093: Nguyen Tat Hung [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP093', 1, 'Nguyen Tat Hung', '', '', 'Hong', 'VN', '2025-10-20', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 80000.00, 'THB', 'nguyentathung@tomastc.com', '+84.983250482', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP094: Nguyen Quang Truong [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP094', 1, 'Nguyen Quang Truong', '', '', 'Truong', 'VN', '2025-10-20', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 24620.00, 'THB', 'nguyenquangtruong@tomastc.com', '+84.971903563', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP095: Phan Mai Son [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP095', 1, 'Phan Mai Son', '', '', 'Son', 'VN', '2025-10-20', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 26200.00, 'THB', 'phanmaison@tomastc.com', '+84.972839374', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP096: Nantiga Suksan [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP096', 1, 'Nantiga Suksan', '', 'นันทิกา สุขแสน', 'Nun', 'TH', '2025-11-03', 'FULL_TIME', 'Administrator', 'MONTHLY', 22000.00, 'THB', 'nantiga.s@tomastc.com', '+66-94-324-0914', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP097: Pham Trung Duc [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP097', 1, 'Pham Trung Duc', '', '', 'Duc', 'VN', '2025-11-13', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 41090.00, 'THB', 'phamtrungduc@tomastc.com', '+84.364986611', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP098: Doan Trong Hieu [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP098', 1, 'Doan Trong Hieu', '', '', 'Hieu', 'VN', '2025-11-13', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 37240.00, 'THB', 'doantronghieu@tomastc.com', '+84.383427522', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP099: Picharmon Sriboonjun [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP099', 1, 'Picharmon Sriboonjun', '', 'พิชามญชุ์ ศรีบุญจันทร์', 'Oil', 'TH', '2025-12-01', 'FULL_TIME', 'Sales Engineer', 'MONTHLY', 40000.00, 'THB', 'picharmon.s@tomastc.com', '+66-87-123-3778', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP100: Tachapon Mulmanee [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP100', 1, 'Tachapon Mulmanee', '', 'เตชภณ มูลมณี', 'Tae', 'TH', '2026-01-05', 'FULL_TIME', 'SA & Software Engineer', 'MONTHLY', 60000.00, 'THB', 'tachapon.m@tomastc.com', '+66-94-145-9953', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP101: Chaiwat Thitaratanaporn [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP101', 1, 'Chaiwat Thitaratanaporn', '', 'ชัยวัฒน์ ฐิตรัตนาภรณ์', 'Boss', 'TH', '2026-01-14', 'FULL_TIME', 'System Engineer', 'MONTHLY', 30000.00, 'THB', 'chaiwat.t@tomastc.com', '+66-93-131-5584', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP102: Waraporn Promsopa [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP102', 1, 'Waraporn Promsopa', '', 'วราพร พรมโสภา', 'Kratai', 'TH', '2026-02-02', 'FULL_TIME', 'Administrator', 'MONTHLY', 20000.00, 'THB', 'waraporn.p@tomastc.com', '+66-98-256-8302', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP045: Pairoj Neamjarn [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP045', 1, 'Pairoj Neamjarn', '', 'ไพโรจน์ เนียมจันทร์', 'Pairoj', 'TH', '2026-01-27', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 0.00, 'THB', NULL, '+66-87-042-5865', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP103: Mekkalar Sansiri [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP103', 1, 'Mekkalar Sansiri', '', 'เมขลา สารศิริ', 'Lillies', 'TH', '2026-03-16', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 25000.00, 'THB', 'mekkalar.s@tomastc.com', '+66-80-396-8709', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP104: Suriyothai Thakrainet [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP104', 1, 'Suriyothai Thakrainet', '', 'สุริโยทัย ทะไกรเนตร', 'Tee', 'TH', '2026-03-16', 'FULL_TIME', 'Administrator', 'MONTHLY', 25000.00, 'THB', 'suriyothai.t@tomastc.com', '+66-99-029-8981', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP105: Weerapat Inudom [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP105', 1, 'Weerapat Inudom', '', 'วีรภัทร อินอุดม', 'Am', 'TH', '2026-04-01', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 25000.00, 'THB', 'weerapat.i@tomastc.com', '+66-90-425-9515', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP106: Natthakit Saengrungrat [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP106', 1, 'Natthakit Saengrungrat', '', 'ณัฐกิตติ์ แสงรุ่งรัตน์', 'Peach', 'TH', '2026-04-01', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 25000.00, 'THB', 'natthakit.s@tomastc.com', '+66-61-221-3220', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP107: Nopparat Talalux [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP107', 1, 'Nopparat Talalux', '', 'นพรัตน์ ตาละลักษมณ์', 'Ohm', 'TH', '2026-04-01', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 40000.00, 'THB', 'nopparat.t@tomastc.com', '+66-95-253-2160', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP108: Saori Nakano [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP108', 1, 'Saori Nakano', '', 'นากะโนะ ซาโอริ', NULL, 'JP', '2026-04-01', 'FULL_TIME', 'Sales Engineer', 'MONTHLY', 80000.00, 'THB', 'nakano.saori@tomastc.com', NULL, 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;

-- EMP109: Sirawit Jantar [STAFF]
INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th, nickname, nationality, hire_date, employment_type, position_title, salary_type, base_salary, salary_currency, email, phone, annual_leave_days, sick_leave_days, leave_balance_annual, leave_balance_sick, role)
VALUES ('EMP109', 1, 'Sirawit Jantar', '', 'ศิรวิชช์ จันทร์ทา', 'Tor', 'TH', '2026-04-06', 'FULL_TIME', 'Software Engineer', 'MONTHLY', 35000.00, 'THB', 'sirawit.j@tomastc.com', '+66-87-521-8126', 6, 30, 6, 30, 'STAFF')
ON CONFLICT (emp_code) DO UPDATE SET full_name=EXCLUDED.full_name, full_name_jp=EXCLUDED.full_name_jp, full_name_th=EXCLUDED.full_name_th, position_title=EXCLUDED.position_title, base_salary=EXCLUDED.base_salary, email=EXCLUDED.email, phone=EXCLUDED.phone, role=EXCLUDED.role;


-- USER ACCOUNTS (default password: password123)

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'anek.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'anek.s@tomastc.com', 'SALES_MANAGER', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP003'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'arunwit.i', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'arunwit.i@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP005'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'emp006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP006'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'kittisak.i', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kittisak.i@tomastc.com', 'SALES_MANAGER', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP013'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nongnut.t', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nongnut.t@tomastc.com', 'ACCOUNTING', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP015'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'soraya.n', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'soraya.n@tomastc.com', 'SALES_MANAGER', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP020'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nattapol.p', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nattapol.p@tomastc.com', 'SALES_MANAGER', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP025'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'panupong.r', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'panupong.r@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP036'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'pornpimon.j', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pornpimon.j@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP037'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'thanthima.p', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'thanthima.p@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP040'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'ronnagon.k', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ronnagon.k@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP041'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'phatthadon.i', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'phatthadon.i@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP043'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'konlawat.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'konlawat.s@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP044'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'thiraporn.a', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'thiraporn.a@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP049'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nattapong.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nattapong.s@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP050'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'panthita.r', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'panthita.r@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP051'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nichapa.b', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nichapa.b@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP053'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'purinat.t', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'purinat.t@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP055'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nattaporn.c', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nattaporn.c@tomastc.com', 'SALES_MANAGER', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP056'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'wannasiwaporn.k', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'wannasiwaporn.k@tomastc.com', 'ADMIN', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP057'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'sermsak.p', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sermsak.p@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP060'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'yutthapong.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'yutthapong.s@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP066'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'surasak.a', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'surasak.a@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP069'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'pattanasak.c', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pattanasak.c@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP070'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'surat.d', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'surat.d@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP071'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nattikorn.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nattikorn.s@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP074'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'suphawat.t', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'suphawat.t@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP077'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'tanong.a', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tanong.a@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP081'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'warit.c', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'warit.c@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP083'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nattawat.h', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nattawat.h@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP084'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'worawit.k', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worawit.k@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP086'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'supatnchai.h', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supatnchai.h@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP087'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'trin.t', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trin.t@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP088'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'siriwilai.c', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siriwilai.c@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP089'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'kittikun.c', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kittikun.c@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP091'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'taweesak.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'taweesak.s@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP092'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nguyentathung', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nguyentathung@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP093'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nguyenquangtruong', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nguyenquangtruong@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP094'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'phanmaison', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'phanmaison@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP095'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nantiga.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nantiga.s@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP096'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'phamtrungduc', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'phamtrungduc@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP097'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'doantronghieu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doantronghieu@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP098'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'picharmon.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'picharmon.s@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP099'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'tachapon.m', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tachapon.m@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP100'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'chaiwat.t', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'chaiwat.t@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP101'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'waraporn.p', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'waraporn.p@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP102'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'emp045', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP045'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'mekkalar.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mekkalar.s@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP103'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'suriyothai.t', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'suriyothai.t@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP104'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'weerapat.i', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'weerapat.i@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP105'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'natthakit.s', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'natthakit.s@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP106'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nopparat.t', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nopparat.t@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP107'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'nakano.saori', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nakano.saori@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP108'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;

INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
SELECT 'sirawit.j', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sirawit.j@tomastc.com', 'STAFF', e.employee_id, TRUE
FROM employees e WHERE e.emp_code = 'EMP109'
ON CONFLICT (username) DO UPDATE SET role=EXCLUDED.role, employee_id=EXCLUDED.employee_id;
