-- ============================================================
-- PEGASUS ERP - Seed Data v3.0
-- Tomas Tech Co., Ltd.
-- ============================================================
-- Run AFTER schema.sql
-- ============================================================

BEGIN;

-- ============================================================
-- 1. DIVISION (HQ)
-- ============================================================
INSERT INTO divisions (division_code, division_name, division_name_jp, division_type, country_code, currency_code, tax_id)
VALUES ('TOMAS-HQ', 'Tomas Tech Co., Ltd.', 'トーマステック株式会社', 'COMPANY', 'TH', 'THB', '0105564000001');

-- ============================================================
-- 2. DEPARTMENTS
-- ============================================================
INSERT INTO departments (department_code, division_id, department_name, department_name_jp) VALUES
('SALES',       1, 'Sales',          '営業部'),
('PURCHASING',  1, 'Purchasing',     '購買部'),
('PRODUCTION',  1, 'Production',     '製造部'),
('QA',          1, 'Quality Assurance', '品質管理部'),
('ACCOUNTING',  1, 'Accounting',     '経理部'),
('HR',          1, 'Human Resources','人事部'),
('IT',          1, 'IT',             '情報システム部'),
('MGMT',        1, 'Management',     '経営管理部');

-- ============================================================
-- 3. DEFAULT ADMIN EMPLOYEE
-- ============================================================
INSERT INTO employees (
    emp_code, division_id, department_id,
    full_name, full_name_jp, full_name_th,
    nationality, hire_date, employment_type,
    position_title, position_level,
    salary_type, base_salary, salary_currency,
    annual_leave_days, sick_leave_days,
    leave_balance_annual, leave_balance_sick,
    role, approval_limit
) VALUES (
    'EMP001', 1, 8,
    'Ryo Nozaki', '野崎 涼', 'เรียว โนซากิ',
    'JP', '2024-01-01', 'FULL_TIME',
    'Management Director', 'DIRECTOR',
    'MONTHLY', 150000.00, 'THB',
    15, 30,
    15, 30,
    'ADMIN', 99999999.99
);

-- ============================================================
-- 4. DEFAULT ADMIN USER
-- ============================================================
-- Password: 'password' hashed with bcrypt ($2y$ prefix for PHP compatibility)
INSERT INTO users (username, password_hash, email, role, employee_id, is_active)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin@tomastech.co.th',
    'ADMIN',
    1,
    TRUE
);

-- ============================================================
-- 5. DEFAULT WORK SCHEDULE
-- ============================================================
INSERT INTO work_schedules (schedule_code, schedule_name, work_start_time, work_end_time, break_minutes, work_days_per_week, std_hours_per_day, std_hours_per_week)
VALUES ('STD-TH', 'Standard Thai Office Hours', '08:00', '17:00', 60, 5, 8.0, 40.0);

-- ============================================================
-- 6. THAI PIT TAX BRACKETS (2025)
-- ============================================================
INSERT INTO pit_tax_brackets (fiscal_year, income_from, income_to, tax_rate) VALUES
(2025,       0.00,  150000.00,  0.00),
(2025,  150001.00,  300000.00,  5.00),
(2025,  300001.00,  500000.00, 10.00),
(2025,  500001.00,  750000.00, 15.00),
(2025,  750001.00, 1000000.00, 20.00),
(2025, 1000001.00, 2000000.00, 25.00),
(2025, 2000001.00, 5000000.00, 30.00),
(2025, 5000001.00,      NULL,  35.00);

-- ============================================================
-- 7. BANKS
-- ============================================================
INSERT INTO banks (bank_code, bank_name, bank_name_th, swift_code) VALUES
('SCB',   'Siam Commercial Bank',                'ธนาคารไทยพาณิชย์',           'SICOTHBK'),
('KBANK', 'Kasikorn Bank',                       'ธนาคารกสิกรไทย',             'KASITHBK'),
('BBL',   'Bangkok Bank',                        'ธนาคารกรุงเทพ',              'BKKBTHBK'),
('KTB',   'Krungthai Bank',                      'ธนาคารกรุงไทย',              'KRTHTHBK'),
('TTB',   'TMBThanachart Bank',                  'ธนาคารทหารไทยธนชาต',          'TMBKTHBK'),
('BAY',   'Bank of Ayudhya (Krungsri)',           'ธนาคารกรุงศรีอยุธยา',          'AYUDTHBK'),
('CIMBT', 'CIMB Thai Bank',                      'ธนาคารซีไอเอ็มบีไทย',         'UBOBTHBK'),
('TISCO', 'TISCO Bank',                          'ธนาคารทิสโก้',               'TFPCTHB1'),
('UOB',   'United Overseas Bank (Thai)',          'ธนาคารยูโอบี',               'UABORUMM'),
('LHBANK','Land and Houses Bank',                'ธนาคารแลนด์ แอนด์ เฮ้าส์',     'LAABORUMM');

-- ============================================================
-- 8. EXPENSE ACCOUNT MAPPING
-- ============================================================
INSERT INTO expense_account_mapping (expense_category, account_code, division_id) VALUES
('TRANSPORT_MILEAGE', '63300', 1),
('TRANSPORT_PUBLIC',  '63200', 1),
('TRANSPORT_TAXI',    '63200', 1),
('ACCOMMODATION',     '63200', 1),
('MEAL',              '63400', 1),
('ENTERTAINMENT',     '63400', 1),
('COMMUNICATION',     '63100', 1),
('STATIONERY',        '63900', 1),
('REGISTRATION',      '63500', 1),
('OTHER',             '63200', 1);

-- ============================================================
-- 9. PAYMENT TERMS
-- ============================================================
INSERT INTO payment_terms (term_code, division_id, term_name_en, term_name_jp, term_name_th, installment_count, credit_days, display_order) VALUES
('NET30',        1, 'Net 30 Days',                      '30日払い',                 'ชำระภายใน 30 วัน',         1, 30,  1),
('NET60',        1, 'Net 60 Days',                      '60日払い',                 'ชำระภายใน 60 วัน',         1, 60,  2),
('NET90',        1, 'Net 90 Days',                      '90日払い',                 'ชำระภายใน 90 วัน',         1, 90,  3),
('COD',          1, 'Cash on Delivery',                 '代金引換',                 'ชำระเงินปลายทาง',          1,  0,  4),
('PREPAID',      1, '100% Prepaid',                     '前払い100%',               'ชำระเงินล่วงหน้า 100%',     1,  0,  5),
('DEP50',        1, '50% Deposit + 50% on Delivery',    '50%前払い+50%納品時',        '50% มัดจำ + 50% ส่งมอบ',   2,  0,  6),
('DEP30',        1, '30% Deposit + 70% on Delivery',    '30%前払い+70%納品時',        '30% มัดจำ + 70% ส่งมอบ',   2,  0,  7),
('MILE3',        1, '3-Milestone (30/40/30)',            '3段階払い(30/40/30)',        '3 งวด (30/40/30)',          3,  0,  8),
('DEP50-20-10-10-10', 1, '5-Installment (50/20/10/10/10)', '5段階払い(50/20/10/10/10)', '5 งวด (50/20/10/10/10)',  5,  0,  9);

-- Installment details for DEP50
INSERT INTO payment_term_installments (term_id, seq_no, percentage, description_en, description_jp, description_th, trigger_type, credit_days)
SELECT t.term_id, s.seq_no, s.pct, s.desc_en, s.desc_jp, s.desc_th, s.trig, s.days
FROM payment_terms t
CROSS JOIN (VALUES
    (1, 50.00, 'Deposit upon PO',           'PO発行時前払い',       'มัดจำเมื่อออก PO',        'PO',       0),
    (2, 50.00, 'Balance on delivery',       '納品時残金',           'ชำระเมื่อส่งมอบ',         'DELIVERY', 0)
) AS s(seq_no, pct, desc_en, desc_jp, desc_th, trig, days)
WHERE t.term_code = 'DEP50';

-- Installment details for DEP30
INSERT INTO payment_term_installments (term_id, seq_no, percentage, description_en, description_jp, description_th, trigger_type, credit_days)
SELECT t.term_id, s.seq_no, s.pct, s.desc_en, s.desc_jp, s.desc_th, s.trig, s.days
FROM payment_terms t
CROSS JOIN (VALUES
    (1, 30.00, 'Deposit upon PO',           'PO発行時前払い',       'มัดจำเมื่อออก PO',        'PO',       0),
    (2, 70.00, 'Balance on delivery',       '納品時残金',           'ชำระเมื่อส่งมอบ',         'DELIVERY', 0)
) AS s(seq_no, pct, desc_en, desc_jp, desc_th, trig, days)
WHERE t.term_code = 'DEP30';

-- Installment details for MILE3
INSERT INTO payment_term_installments (term_id, seq_no, percentage, description_en, description_jp, description_th, trigger_type, credit_days)
SELECT t.term_id, s.seq_no, s.pct, s.desc_en, s.desc_jp, s.desc_th, s.trig, s.days
FROM payment_terms t
CROSS JOIN (VALUES
    (1, 30.00, 'Upon PO / Design Approval',     'PO/設計承認時',             'เมื่อออก PO/อนุมัติแบบ',      'DESIGN',       0),
    (2, 40.00, 'Upon Delivery / FAT',           '納品/FAT時',               'เมื่อส่งมอบ/FAT',            'FAT',          0),
    (3, 30.00, 'Upon Installation / SAT',       '据付/SAT完了時',            'เมื่อติดตั้ง/SAT',            'SAT',         30)
) AS s(seq_no, pct, desc_en, desc_jp, desc_th, trig, days)
WHERE t.term_code = 'MILE3';

-- Installment details for DEP50-20-10-10-10
INSERT INTO payment_term_installments (term_id, seq_no, percentage, description_en, description_jp, description_th, trigger_type, credit_days)
SELECT t.term_id, s.seq_no, s.pct, s.desc_en, s.desc_jp, s.desc_th, s.trig, s.days
FROM payment_terms t
CROSS JOIN (VALUES
    (1, 50.00, 'Deposit upon PO',                       'PO発行時前払い',           'มัดจำเมื่อออก PO',            'PO',           0),
    (2, 20.00, 'Upon design approval',                  '設計承認時',               'เมื่ออนุมัติแบบ',              'DESIGN',       0),
    (3, 10.00, 'Upon delivery / FAT',                   '納品/FAT時',              'เมื่อส่งมอบ/FAT',             'FAT',          0),
    (4, 10.00, 'Upon installation / SAT',               '据付/SAT完了時',           'เมื่อติดตั้ง/SAT',             'SAT',          0),
    (5, 10.00, 'Final payment after completion',        '完了後最終支払い',          'ชำระสุดท้ายหลังแล้วเสร็จ',      'COMPLETION',  30)
) AS s(seq_no, pct, desc_en, desc_jp, desc_th, trig, days)
WHERE t.term_code = 'DEP50-20-10-10-10';

-- ============================================================
-- 10. CHART OF ACCOUNTS (TFRS Compliant)
-- ============================================================

-- ──────────────────────────────────────
-- ASSETS (1XXXX)
-- ──────────────────────────────────────

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('11000', 1, 'Cash and Cash Equivalents',          '現金及び現金同等物',         'เงินสดและรายการเทียบเท่าเงินสด',   'ASSET', 'BS', NULL),
('11100', 1, 'Cash on Hand',                       '手許現金',                 'เงินสดในมือ',                     'ASSET', 'BS', '11000'),
('11200', 1, 'Bank Deposits - THB',                '銀行預金（バーツ）',         'เงินฝากธนาคาร - บาท',              'ASSET', 'BS', '11000'),
('11300', 1, 'Bank Deposits - FCY',                '銀行預金（外貨）',           'เงินฝากธนาคาร - สกุลต่างประเทศ',    'ASSET', 'BS', '11000'),
('11400', 1, 'Petty Cash',                         '小口現金',                 'เงินสดย่อย',                      'ASSET', 'BS', '11000');

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('12000', 1, 'Trade Receivables',                  '売掛金',                   'ลูกหนี้การค้า',                    'ASSET', 'BS', NULL),
('12100', 1, 'Accounts Receivable - Domestic',     '売掛金（国内）',             'ลูกหนี้การค้า - ในประเทศ',          'ASSET', 'BS', '12000'),
('12200', 1, 'Accounts Receivable - Export',        '売掛金（輸出）',             'ลูกหนี้การค้า - ส่งออก',            'ASSET', 'BS', '12000'),
('12300', 1, 'Notes Receivable',                   '受取手形',                 'ตั๋วเงินรับ',                      'ASSET', 'BS', '12000'),
('12400', 1, 'Allowance for Doubtful Accounts',    '貸倒引当金',               'ค่าเผื่อหนี้สงสัยจะสูญ',            'ASSET', 'BS', '12000'),
('12500', 1, 'Advance to Customers',               '前受金（顧客）',            'เงินทดรองจ่ายให้ลูกค้า',            'ASSET', 'BS', '12000');

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('13000', 1, 'Inventories',                        '棚卸資産',                 'สินค้าคงเหลือ',                    'ASSET', 'BS', NULL),
('13100', 1, 'Raw Materials',                      '原材料',                   'วัตถุดิบ',                         'ASSET', 'BS', '13000'),
('13200', 1, 'Work in Progress',                   '仕掛品',                   'งานระหว่างทำ',                     'ASSET', 'BS', '13000'),
('13300', 1, 'Finished Goods',                     '製品',                     'สินค้าสำเร็จรูป',                   'ASSET', 'BS', '13000'),
('13400', 1, 'Merchandise Goods',                  '商品',                     'สินค้า',                           'ASSET', 'BS', '13000'),
('13500', 1, 'Supplies',                           '消耗品',                   'วัสดุสิ้นเปลือง',                   'ASSET', 'BS', '13000');

-- Other Current Assets (with tax relevance)
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code, is_tax_relevant, tax_form) VALUES
('14000', 1, 'Other Current Assets',               'その他流動資産',             'สินทรัพย์หมุนเวียนอื่น',            'ASSET', 'BS', NULL,    FALSE, NULL),
('14100', 1, 'Input VAT',                          '仮払消費税（VAT）',          'ภาษีซื้อ',                         'ASSET', 'BS', '14000', TRUE,  'PP30'),
('14200', 1, 'WHT Prepaid',                        '前払源泉所得税',             'ภาษีเงินได้หัก ณ ที่จ่ายล่วงหน้า',   'ASSET', 'BS', '14000', TRUE,  'PND53'),
('14300', 1, 'Prepaid Expenses',                   '前払費用',                 'ค่าใช้จ่ายจ่ายล่วงหน้า',            'ASSET', 'BS', '14000', FALSE, NULL),
('14400', 1, 'Accrued Income',                     '未収収益',                 'รายได้ค้างรับ',                     'ASSET', 'BS', '14000', FALSE, NULL),
('14500', 1, 'Employee Advances',                  '従業員仮払金',              'เงินทดรองจ่ายพนักงาน',              'ASSET', 'BS', '14000', FALSE, NULL),
('14600', 1, 'Expense Claim Advances',             '経費精算仮払金',             'เงินทดรองจ่ายเบิกค่าใช้จ่าย',       'ASSET', 'BS', '14000', FALSE, NULL);

-- Property Plant and Equipment
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('15000', 1, 'Property Plant and Equipment',       '有形固定資産',              'ที่ดิน อาคารและอุปกรณ์',             'ASSET', 'BS', NULL),
('15100', 1, 'Land',                               '土地',                    'ที่ดิน',                            'ASSET', 'BS', '15000'),
('15200', 1, 'Buildings',                          '建物',                    'อาคาร',                           'ASSET', 'BS', '15000'),
('15210', 1, 'Accumulated Depreciation - Buildings','建物減価償却累計額',         'ค่าเสื่อมราคาสะสม - อาคาร',         'ASSET', 'BS', '15200'),
('15300', 1, 'Machinery and Equipment',            '機械装置',                 'เครื่องจักรและอุปกรณ์',              'ASSET', 'BS', '15000'),
('15310', 1, 'Accumulated Depreciation - Machinery','機械装置減価償却累計額',      'ค่าเสื่อมราคาสะสม - เครื่องจักร',    'ASSET', 'BS', '15300'),
('15400', 1, 'Vehicles',                           '車両運搬具',               'ยานพาหนะ',                        'ASSET', 'BS', '15000'),
('15410', 1, 'Accumulated Depreciation - Vehicles','車両減価償却累計額',          'ค่าเสื่อมราคาสะสม - ยานพาหนะ',      'ASSET', 'BS', '15400'),
('15500', 1, 'Office Equipment',                   '什器備品',                 'เครื่องใช้สำนักงาน',                 'ASSET', 'BS', '15000'),
('15510', 1, 'Accumulated Depreciation - Office Equip','什器備品減価償却累計額',   'ค่าเสื่อมราคาสะสม - เครื่องใช้สำนักงาน','ASSET','BS','15500'),
('15600', 1, 'Right-of-Use Assets (TFRS16)',       '使用権資産',               'สินทรัพย์สิทธิการใช้',               'ASSET', 'BS', '15000'),
('15610', 1, 'Accumulated Depreciation - ROU Assets','使用権資産償却累計額',      'ค่าเสื่อมราคาสะสม - สินทรัพย์สิทธิการใช้','ASSET','BS','15600');

-- Intangible Assets
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('16000', 1, 'Intangible Assets',                  '無形固定資産',              'สินทรัพย์ไม่มีตัวตน',               'ASSET', 'BS', NULL),
('16100', 1, 'Software Licenses',                  'ソフトウェアライセンス',      'ลิขสิทธิ์ซอฟต์แวร์',                'ASSET', 'BS', '16000'),
('16200', 1, 'Goodwill',                           'のれん',                   'ค่าความนิยม',                      'ASSET', 'BS', '16000');

-- Deferred Tax Assets & Long-term Deposits
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code, is_tax_relevant, tax_form) VALUES
('17000', 1, 'Deferred Tax Assets',                '繰延税金資産',              'สินทรัพย์ภาษีเงินได้รอตัดบัญชี',     'ASSET', 'BS', NULL,    TRUE,  'PND50'),
('17100', 1, 'Long-term Deposits',                 '長期預り敷金',              'เงินมัดจำระยะยาว',                  'ASSET', 'BS', NULL,    FALSE, NULL);

-- WIP Manufacturing sub-accounts
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('95300', 1, 'WIP - Direct Material',              '仕掛品（直接材料費）',        'งานระหว่างทำ - วัตถุดิบทางตรง',      'ASSET', 'BS', '13200'),
('95400', 1, 'WIP - Direct Labor',                 '仕掛品（直接労務費）',        'งานระหว่างทำ - ค่าแรงทางตรง',       'ASSET', 'BS', '13200'),
('95500', 1, 'WIP - Overhead',                     '仕掛品（製造間接費）',        'งานระหว่างทำ - ค่าใช้จ่ายการผลิต',   'ASSET', 'BS', '13200');


-- ──────────────────────────────────────
-- LIABILITIES (2XXXX)
-- ──────────────────────────────────────

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('21000', 1, 'Trade Payables',                     '買掛金',                   'เจ้าหนี้การค้า',                    'LIABILITY', 'BS', NULL),
('21100', 1, 'Accounts Payable - Domestic',        '買掛金（国内）',             'เจ้าหนี้การค้า - ในประเทศ',          'LIABILITY', 'BS', '21000'),
('21200', 1, 'Accounts Payable - Import',           '買掛金（輸入）',             'เจ้าหนี้การค้า - นำเข้า',            'LIABILITY', 'BS', '21000'),
('21300', 1, 'Notes Payable',                      '支払手形',                 'ตั๋วเงินจ่าย',                      'LIABILITY', 'BS', '21000');

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('22000', 1, 'Accrued Expenses',                   '未払費用',                 'ค่าใช้จ่ายค้างจ่าย',                 'LIABILITY', 'BS', NULL),
('22100', 1, 'Accrued Salaries and Wages',         '未払給与',                 'เงินเดือนค้างจ่าย',                  'LIABILITY', 'BS', '22000'),
('22200', 1, 'Accrued Bonus',                      '未払賞与',                 'โบนัสค้างจ่าย',                     'LIABILITY', 'BS', '22000'),
('22300', 1, 'Accrued Annual Leave',               '未払年次有給',              'ค่าลาพักร้อนค้างจ่าย',               'LIABILITY', 'BS', '22000'),
('22400', 1, 'Accrued Severance Pay',              '未払退職金',               'ค่าชดเชยค้างจ่าย',                  'LIABILITY', 'BS', '22000'),
('22500', 1, 'Accrued Audit Fees',                 '未払監査費用',              'ค่าสอบบัญชีค้างจ่าย',                'LIABILITY', 'BS', '22000'),
('22600', 1, 'Accrued Interest',                   '未払利息',                 'ดอกเบี้ยค้างจ่าย',                   'LIABILITY', 'BS', '22000'),
('22700', 1, 'Expense Claims Payable',             '経費精算未払金',             'ค่าเบิกค่าใช้จ่ายค้างจ่าย',          'LIABILITY', 'BS', '22000');

-- Tax Payables
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code, is_tax_relevant, tax_form) VALUES
('23000', 1, 'Tax Payables',                       '未払税金',                 'ภาษีค้างจ่าย',                      'LIABILITY', 'BS', NULL,    TRUE,  NULL),
('23100', 1, 'Output VAT Payable',                 '仮受消費税（VAT）',          'ภาษีขาย',                          'LIABILITY', 'BS', '23000', TRUE,  'PP30'),
('23200', 1, 'VAT Payable to Revenue Dept',        '消費税納付金',              'ภาษีมูลค่าเพิ่มนำส่งกรมสรรพากร',      'LIABILITY', 'BS', '23000', TRUE,  'PP30'),
('23300', 1, 'WHT Payable (PND1 - PIT)',           '源泉所得税預り金（個人）',     'ภาษีเงินได้หัก ณ ที่จ่าย (บุคคล)',    'LIABILITY', 'BS', '23000', TRUE,  'PND1'),
('23310', 1, 'WHT Payable (PND3 - Services)',      '源泉所得税預り金（サービス）',  'ภาษีเงินได้หัก ณ ที่จ่าย (บริการ)',   'LIABILITY', 'BS', '23000', TRUE,  'PND3'),
('23320', 1, 'WHT Payable (PND53 - Corporate)',    '源泉所得税預り金（法人）',     'ภาษีเงินได้หัก ณ ที่จ่าย (นิติบุคคล)','LIABILITY', 'BS', '23000', TRUE,  'PND53'),
('23400', 1, 'Social Security Payable - Employee', '社会保険料（従業員分）',       'เงินประกันสังคม - ส่วนพนักงาน',      'LIABILITY', 'BS', '23000', TRUE,  'SSO'),
('23410', 1, 'Social Security Payable - Employer', '社会保険料（雇用主分）',       'เงินประกันสังคม - ส่วนนายจ้าง',      'LIABILITY', 'BS', '23000', TRUE,  'SSO'),
('23500', 1, 'Corporate Income Tax Payable',       '法人所得税未払金',           'ภาษีเงินได้นิติบุคคลค้างจ่าย',       'LIABILITY', 'BS', '23000', TRUE,  'PND50'),
('23600', 1, 'Semi-Annual CIT Payable',            '中間法人税（PND51）',        'ภาษีเงินได้นิติบุคคลครึ่งปี',         'LIABILITY', 'BS', '23000', TRUE,  'PND51'),
('23700', 1, 'Land and Building Tax Payable',      '土地・建物税未払金',          'ภาษีที่ดินและสิ่งปลูกสร้างค้างจ่าย',   'LIABILITY', 'BS', '23000', TRUE,  NULL),
('23800', 1, 'Stamp Duty Payable',                 '収入印紙税未払金',           'อากรแสตมป์ค้างจ่าย',                'LIABILITY', 'BS', '23000', TRUE,  NULL);

-- Short-term Loans
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('24000', 1, 'Short-term Loans',                   '短期借入金',               'เงินกู้ยืมระยะสั้น',                 'LIABILITY', 'BS', NULL),
('24100', 1, 'Bank Overdraft',                     '当座借越',                 'เงินเบิกเกินบัญชี',                  'LIABILITY', 'BS', '24000'),
('24200', 1, 'Current Portion of Long-term Loans', '長期借入金（1年以内返済）',    'ส่วนของเงินกู้ยืมระยะยาวที่ถึงกำหนดภายในหนึ่งปี','LIABILITY','BS','24000');

-- Deferred Revenue & Deposits
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('25000', 1, 'Deferred Revenue',                   '前受収益',                 'รายได้รับล่วงหน้า',                  'LIABILITY', 'BS', NULL),
('25100', 1, 'Customer Deposits Received',         '顧客預り保証金',            'เงินมัดจำรับจากลูกค้า',              'LIABILITY', 'BS', '25000');

-- Long-term Liabilities
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code, is_tax_relevant, tax_form) VALUES
('26000', 1, 'Long-term Loans',                    '長期借入金',               'เงินกู้ยืมระยะยาว',                  'LIABILITY', 'BS', NULL,    FALSE, NULL),
('26100', 1, 'Lease Liabilities (TFRS16)',          'リース負債',               'หนี้สินตามสัญญาเช่า',                'LIABILITY', 'BS', '26000', FALSE, NULL),
('26200', 1, 'Provision for Severance Pay',        '退職給付引当金',             'ประมาณการหนี้สินผลประโยชน์พนักงาน',  'LIABILITY', 'BS', NULL,    FALSE, NULL),
('26300', 1, 'Deferred Tax Liabilities',           '繰延税金負債',              'หนี้สินภาษีเงินได้รอตัดบัญชี',        'LIABILITY', 'BS', NULL,    TRUE,  'PND50');


-- ──────────────────────────────────────
-- EQUITY (3XXXX)
-- ──────────────────────────────────────

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('31000', 1, 'Share Capital',                      '資本金',                   'ทุนเรือนหุ้น',                      'EQUITY', 'BS', NULL),
('31100', 1, 'Registered Capital',                 '登録資本',                 'ทุนจดทะเบียน',                     'EQUITY', 'BS', '31000'),
('31200', 1, 'Paid-up Capital',                    '払込済資本',               'ทุนชำระแล้ว',                      'EQUITY', 'BS', '31000'),
('32000', 1, 'Retained Earnings',                  '利益剰余金',               'กำไรสะสม',                         'EQUITY', 'BS', NULL),
('32100', 1, 'Legal Reserve',                      '利益準備金',               'สำรองตามกฎหมาย',                   'EQUITY', 'BS', '32000'),
('32200', 1, 'Unappropriated Retained Earnings',   '未処分利益剰余金',          'กำไรสะสมที่ยังไม่ได้จัดสรร',          'EQUITY', 'BS', '32000'),
('32300', 1, 'Current Year Net Profit/Loss',       '当期純利益（損失）',         'กำไร(ขาดทุน)สุทธิประจำปี',           'EQUITY', 'BS', '32000');


-- ──────────────────────────────────────
-- REVENUE (4XXXX)
-- ──────────────────────────────────────

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('41000', 1, 'Revenue from Sales of Goods',        '商品売上高',               'รายได้จากการขายสินค้า',               'REVENUE', 'PL', NULL),
('41100', 1, 'Revenue from AGV/AMR Sales',         'AGV/AMR 売上',            'รายได้จากการขาย AGV/AMR',            'REVENUE', 'PL', '41000'),
('41200', 1, 'Revenue from WMS/RFID Sales',        'WMS/RFID 売上',           'รายได้จากการขาย WMS/RFID',           'REVENUE', 'PL', '41000'),
('41300', 1, 'Revenue from Spare Parts',           'スペアパーツ売上',           'รายได้จากการขายอะไหล่',               'REVENUE', 'PL', '41000'),
('42000', 1, 'Revenue from Services',              'サービス収益',              'รายได้จากการบริการ',                  'REVENUE', 'PL', NULL),
('42100', 1, 'Revenue from Installation Services', '据付・設置工事収益',         'รายได้จากบริการติดตั้ง',               'REVENUE', 'PL', '42000'),
('42200', 1, 'Revenue from Maintenance Services',  '保守・メンテナンス収益',     'รายได้จากบริการบำรุงรักษา',            'REVENUE', 'PL', '42000'),
('42300', 1, 'Revenue from Consulting Services',   'コンサルティング収益',       'รายได้จากบริการให้คำปรึกษา',           'REVENUE', 'PL', '42000'),
('42400', 1, 'Revenue from Training',              '研修収益',                 'รายได้จากการฝึกอบรม',                 'REVENUE', 'PL', '42000'),
('43000', 1, 'Revenue from Export Sales',          '輸出売上',                 'รายได้จากการส่งออก',                  'REVENUE', 'PL', NULL),
('44000', 1, 'Sales Returns and Allowances',       '売上返品・値引',            'รับคืนสินค้าและส่วนลด',               'REVENUE', 'PL', NULL),
('44100', 1, 'Sales Discounts',                    '売上割引',                 'ส่วนลดการขาย',                      'REVENUE', 'PL', '44000');


-- ──────────────────────────────────────
-- COGS (5XXXX)
-- ──────────────────────────────────────

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('51000', 1, 'Cost of Goods Sold',                 '売上原価',                 'ต้นทุนขาย',                         'COGS', 'PL', NULL),
('51100', 1, 'Cost of Manufactured Goods Sold',    '製造製品売上原価',           'ต้นทุนสินค้าที่ผลิต',                 'COGS', 'PL', '51000'),
('51200', 1, 'Cost of Merchandise Sold',           '商品売上原価',              'ต้นทุนสินค้าที่ซื้อมาขาย',            'COGS', 'PL', '51000'),
('51300', 1, 'Cost of Service Delivery',           'サービス提供原価',           'ต้นทุนการให้บริการ',                  'COGS', 'PL', '51000'),
('51400', 1, 'Cost Variance - Material',           '材料費差異',               'ผลต่างต้นทุน - วัตถุดิบ',             'COGS', 'PL', '51000'),
('51500', 1, 'Cost Variance - Labor',              '労務費差異',               'ผลต่างต้นทุน - ค่าแรง',              'COGS', 'PL', '51000'),
('51600', 1, 'Cost Variance - Overhead',           '製造間接費差異',            'ผลต่างต้นทุน - ค่าใช้จ่ายการผลิต',    'COGS', 'PL', '51000');


-- ──────────────────────────────────────
-- EXPENSES (6XXXX) - SG&A
-- ──────────────────────────────────────

-- Selling Expenses
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('61000', 1, 'Selling Expenses',                   '販売費',                   'ค่าใช้จ่ายในการขาย',                  'EXPENSE', 'PL', NULL),
('61100', 1, 'Sales Commission',                   '販売手数料',               'ค่านายหน้า',                         'EXPENSE', 'PL', '61000'),
('61200', 1, 'Advertising Expense',                '広告宣伝費',               'ค่าโฆษณา',                          'EXPENSE', 'PL', '61000'),
('61300', 1, 'Exhibition Expense',                 '展示会費',                 'ค่าจัดนิทรรศการ',                    'EXPENSE', 'PL', '61000'),
('61400', 1, 'Delivery Expense',                   '配送費',                   'ค่าขนส่ง',                          'EXPENSE', 'PL', '61000'),
('61500', 1, 'Warranty Expense',                   '保証費用',                 'ค่าใช้จ่ายรับประกัน',                 'EXPENSE', 'PL', '61000');

-- Administrative Expenses
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('62000', 1, 'Administrative Expenses',            '管理費',                   'ค่าใช้จ่ายในการบริหาร',               'EXPENSE', 'PL', NULL),
('62100', 1, 'Salaries and Wages',                 '給与手当',                 'เงินเดือนและค่าจ้าง',                 'EXPENSE', 'PL', '62000'),
('62200', 1, 'Social Security Expense',            '社会保険料（会社負担）',      'ค่าประกันสังคม (ส่วนนายจ้าง)',        'EXPENSE', 'PL', '62000'),
('62300', 1, 'Provident Fund Expense',             '退職積立金（会社負担）',      'เงินสมทบกองทุนสำรองเลี้ยงชีพ',       'EXPENSE', 'PL', '62000'),
('62400', 1, 'Bonus Expense',                      '賞与',                    'ค่าโบนัส',                          'EXPENSE', 'PL', '62000'),
('62500', 1, 'Severance Pay Expense',              '退職金費用',               'ค่าชดเชย',                          'EXPENSE', 'PL', '62000'),
('62600', 1, 'Training Expense',                   '研修費',                   'ค่าฝึกอบรม',                        'EXPENSE', 'PL', '62000'),
('62700', 1, 'Welfare Expense',                    '福利厚生費',               'ค่าสวัสดิการ',                       'EXPENSE', 'PL', '62000'),
('62800', 1, 'Director Remuneration',              '役員報酬',                 'ค่าตอบแทนกรรมการ',                   'EXPENSE', 'PL', '62000'),
('62900', 1, 'Recruitment Expense',                '採用費',                   'ค่าสรรหาบุคลากร',                    'EXPENSE', 'PL', '62000');

-- Office and Operating Expenses
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('63000', 1, 'Office Expenses',                    '事務経費',                 'ค่าใช้จ่ายสำนักงาน',                  'EXPENSE', 'PL', NULL),
('63100', 1, 'Communication Expense',              '通信費',                   'ค่าสื่อสาร',                         'EXPENSE', 'PL', '63000'),
('63200', 1, 'Travel Expense',                     '旅費交通費',               'ค่าเดินทาง',                         'EXPENSE', 'PL', '63000'),
('63300', 1, 'Vehicle Expense / Mileage',          '車両費・交通費',             'ค่ายานพาหนะ/ค่าระยะทาง',             'EXPENSE', 'PL', '63000'),
('63400', 1, 'Entertainment Expense',              '交際費',                   'ค่ารับรอง',                          'EXPENSE', 'PL', '63000'),
('63500', 1, 'Registration and License Fees',      '登録免許費',               'ค่าลงทะเบียนและใบอนุญาต',            'EXPENSE', 'PL', '63000'),
('63600', 1, 'Rental Expense',                     '賃借料',                   'ค่าเช่า',                           'EXPENSE', 'PL', '63000'),
('63700', 1, 'Utility Expense',                    '水道光熱費',               'ค่าสาธารณูปโภค',                     'EXPENSE', 'PL', '63000'),
('63800', 1, 'Insurance Expense',                  '保険料',                   'ค่าประกันภัย',                       'EXPENSE', 'PL', '63000'),
('63900', 1, 'Office Supplies Expense',            '事務用品費',               'ค่าวัสดุสำนักงาน',                    'EXPENSE', 'PL', '63000');

-- IT and Depreciation
INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('64000', 1, 'IT and Depreciation Expenses',       'IT・減価償却費',             'ค่าใช้จ่าย IT และค่าเสื่อมราคา',      'EXPENSE', 'PL', NULL),
('64100', 1, 'Depreciation Expense',               '減価償却費',               'ค่าเสื่อมราคา',                      'EXPENSE', 'PL', '64000'),
('64200', 1, 'Amortization Expense',               '償却費',                   'ค่าตัดจำหน่าย',                      'EXPENSE', 'PL', '64000'),
('64300', 1, 'Bad Debt Expense',                   '貸倒損失',                 'หนี้สูญ',                           'EXPENSE', 'PL', '64000');


-- ──────────────────────────────────────
-- OTHER INCOME (7XXXX)
-- ──────────────────────────────────────

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code, is_tax_relevant, tax_form) VALUES
('71000', 1, 'Interest Income',                    '受取利息',                 'ดอกเบี้ยรับ',                        'REVENUE', 'PL', NULL,  TRUE,  'PND3'),
('72000', 1, 'Dividend Income',                    '受取配当',                 'เงินปันผลรับ',                       'REVENUE', 'PL', NULL,  FALSE, NULL),
('73000', 1, 'Foreign Exchange Gain',              '為替差益',                 'กำไรจากอัตราแลกเปลี่ยน',              'REVENUE', 'PL', NULL,  FALSE, NULL),
('74000', 1, 'Gain on Disposal of Assets',         '固定資産売却益',             'กำไรจากการจำหน่ายสินทรัพย์',          'REVENUE', 'PL', NULL,  FALSE, NULL),
('75000', 1, 'Other Income',                       'その他収益',               'รายได้อื่น',                          'REVENUE', 'PL', NULL,  FALSE, NULL),
('75100', 1, 'Rental Income',                      '賃貸収入',                 'รายได้ค่าเช่า',                       'REVENUE', 'PL', '75000', TRUE, 'PND3'),
('75200', 1, 'Government Grant Income',            '政府補助金収入',             'รายได้จากเงินอุดหนุนรัฐบาล',          'REVENUE', 'PL', '75000', FALSE, NULL);


-- ──────────────────────────────────────
-- OTHER EXPENSES (8XXXX)
-- ──────────────────────────────────────

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('81000', 1, 'Interest Expense',                   '支払利息',                 'ดอกเบี้ยจ่าย',                       'EXPENSE', 'PL', NULL),
('82000', 1, 'Foreign Exchange Loss',              '為替差損',                 'ขาดทุนจากอัตราแลกเปลี่ยน',            'EXPENSE', 'PL', NULL),
('83000', 1, 'Loss on Disposal of Assets',         '固定資産売却損',             'ขาดทุนจากการจำหน่ายสินทรัพย์',        'EXPENSE', 'PL', NULL),
('84000', 1, 'Impairment Loss',                    '減損損失',                 'ขาดทุนจากการด้อยค่า',                 'EXPENSE', 'PL', NULL),
('85000', 1, 'Other Expenses',                     'その他費用',               'ค่าใช้จ่ายอื่น',                      'EXPENSE', 'PL', NULL);


-- ──────────────────────────────────────
-- TAX EXPENSE (9XXXX)
-- ──────────────────────────────────────

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code, is_tax_relevant, tax_form) VALUES
('91000', 1, 'Corporate Income Tax Expense',       '法人所得税費用',             'ค่าใช้จ่ายภาษีเงินได้นิติบุคคล',      'EXPENSE', 'PL', NULL,    TRUE, 'PND50'),
('91100', 1, 'Current Tax Expense',                '当期法人税',               'ภาษีเงินได้ปัจจุบัน',                  'EXPENSE', 'PL', '91000', TRUE, 'PND50'),
('91200', 1, 'Deferred Tax Expense/Benefit',       '繰延税金費用（利益）',        'ค่าใช้จ่าย(รายได้)ภาษีเงินได้รอตัดบัญชี','EXPENSE','PL','91000', TRUE, 'PND50');


-- ──────────────────────────────────────
-- MANUFACTURING OVERHEAD (95XXX)
-- ──────────────────────────────────────

INSERT INTO accounts (account_code, division_id, account_name, account_name_jp, account_name_th, account_type, bs_pl, parent_code) VALUES
('95100', 1, 'Manufacturing Overhead Applied',     '製造間接費配賦',             'ค่าใช้จ่ายการผลิตที่ปันส่วน',          'COGS', 'PL', '51000'),
('95200', 1, 'Manufacturing Overhead Control',     '製造間接費統制',             'ค่าใช้จ่ายการผลิตควบคุม',              'COGS', 'PL', '51000');


-- ============================================================
-- 11. NUMBER SEQUENCES
-- ============================================================
INSERT INTO number_sequences (seq_name, prefix, current_no, fiscal_year, fiscal_month, format_pattern) VALUES
('QUOTATION',       'QT',  0, NULL, NULL, 'QT{YYMMDD}-{NN}'),
('SALES_ORDER',     'SO',  0, NULL, NULL, 'SO-{YYYY}{MM}{NNNNNN}'),
('PURCHASE_ORDER',  'PO',  0, NULL, NULL, 'PO-{YYYY}{MM}{NNNNNN}'),
('AR_INVOICE',      'IV',  0, NULL, NULL, 'IV-{YYYY}{MM}{NNNNNN}'),
('JOURNAL',         'JE',  0, NULL, NULL, 'JE-{YYYY}-{NNNN}'),
('EXPENSE',         'EXP', 0, NULL, NULL, 'EXP-{YYYY}-{NNNN}'),
('PAYROLL',         'PAY', 0, NULL, NULL, 'PAY-{YYYY}-{MM}'),
('MO',              'MO',  0, NULL, NULL, 'MO-{YYYY}-{NNNN}');


-- ============================================================
-- 12. DEFAULT WAREHOUSE
-- ============================================================
INSERT INTO warehouses (warehouse_code, division_id, warehouse_name, address)
VALUES ('WH-001', 1, 'Main Warehouse', 'Tomas Tech HQ, Thailand');


COMMIT;
