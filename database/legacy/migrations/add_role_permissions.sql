-- ============================================================
-- #18 Permission Master (role-based)
--   - roles + permissions + role_permissions
-- ============================================================

BEGIN;

-- Roles (synced with users.role check constraint)
CREATE TABLE IF NOT EXISTS roles (
    role_code   VARCHAR(50)  PRIMARY KEY,
    role_name   VARCHAR(100) NOT NULL,
    role_name_jp VARCHAR(100),
    role_name_th VARCHAR(100),
    description TEXT,
    is_system   BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

INSERT INTO roles (role_code, role_name, role_name_jp, role_name_th, is_system) VALUES
    ('ADMIN',         'Administrator',  '管理者',           'ผู้ดูแลระบบ',        TRUE),
    ('SALES_MANAGER', 'Sales Manager',  '営業マネージャー', 'ผู้จัดการฝ่ายขาย',  TRUE),
    ('ACCOUNTING',    'Accounting',     '経理',             'บัญชี',             TRUE),
    ('STAFF',         'Staff',          '一般スタッフ',     'พนักงานทั่วไป',     TRUE)
ON CONFLICT (role_code) DO NOTHING;

-- Permissions catalogue
CREATE TABLE IF NOT EXISTS permissions (
    permission_code VARCHAR(80)  PRIMARY KEY,
    module          VARCHAR(40)  NOT NULL,
    description     TEXT,
    description_jp  TEXT,
    description_th  TEXT
);

INSERT INTO permissions (permission_code, module, description, description_jp, description_th) VALUES
    ('sales.view',         'sales',      'View sales module',          '営業モジュール閲覧',         'ดูโมดูลฝ่ายขาย'),
    ('sales.edit',         'sales',      'Edit deals/quotations',      '案件/見積書編集',            'แก้ไขดีล/ใบเสนอราคา'),
    ('sales.approve',      'sales',      'Approve quotations',         '見積書承認',                 'อนุมัติใบเสนอราคา'),
    ('sales.convert_order','sales',      'Convert deal → order',       '案件→受注変換',              'แปลงดีล→คำสั่งขาย'),
    ('sales.cancel_order', 'sales',      'Cancel sales orders',        '受注キャンセル',             'ยกเลิกคำสั่งขาย'),
    ('purchasing.view',    'purchasing', 'View purchasing',            '購買閲覧',                   'ดูฝ่ายจัดซื้อ'),
    ('purchasing.edit',    'purchasing', 'Edit POs',                   '発注編集',                   'แก้ไข PO'),
    ('purchasing.approve', 'purchasing', 'Approve POs',                '発注承認',                   'อนุมัติ PO'),
    ('inventory.view',     'inventory',  'View inventory',             '在庫閲覧',                   'ดูสต๊อก'),
    ('inventory.edit',     'inventory',  'Edit inventory',             '在庫編集',                   'แก้ไขสต๊อก'),
    ('accounting.view',    'accounting', 'View accounting',            '会計閲覧',                   'ดูบัญชี'),
    ('accounting.edit',    'accounting', 'Post journal entries',       '仕訳入力',                   'บันทึกรายการบัญชี'),
    ('ar.view',            'ar',         'View AR',                    '売掛閲覧',                   'ดูลูกหนี้'),
    ('ar.edit',            'ar',         'Create AR invoices',         '売掛請求書作成',             'สร้างใบแจ้งหนี้ AR'),
    ('ap.view',            'ap',         'View AP',                    '買掛閲覧',                   'ดูเจ้าหนี้'),
    ('ap.edit',            'ap',         'Create AP invoices',         '買掛請求書作成',             'สร้างใบแจ้งหนี้ AP'),
    ('hr.view',            'hr',         'View HR',                    '人事閲覧',                   'ดูบุคคล'),
    ('hr.edit',            'hr',         'Edit employees',             '従業員編集',                 'แก้ไขพนักงาน'),
    ('payroll.view',       'payroll',    'View payroll',               '給与閲覧',                   'ดูเงินเดือน'),
    ('payroll.edit',       'payroll',    'Process payroll',            '給与計算',                   'คำนวณเงินเดือน'),
    ('expense.view',       'expense',    'View expenses',              '経費閲覧',                   'ดูค่าใช้จ่าย'),
    ('expense.approve',    'expense',    'Approve expenses',           '経費承認',                   'อนุมัติค่าใช้จ่าย'),
    ('production.view',    'production', 'View production',            '製造閲覧',                   'ดูการผลิต'),
    ('production.edit',    'production', 'Edit production orders',     '製造指示編集',               'แก้ไขใบสั่งผลิต'),
    ('master.view',        'master',     'View masters',               'マスタ閲覧',                 'ดูมาสเตอร์'),
    ('master.edit',        'master',     'Edit masters',               'マスタ編集',                 'แก้ไขมาสเตอร์'),
    ('admin.audit_log',    'admin',      'View audit logs',            '監査ログ閲覧',               'ดูบันทึกการเปลี่ยนแปลง'),
    ('admin.permissions',  'admin',      'Manage permissions',         '権限管理',                   'จัดการสิทธิ์')
ON CONFLICT (permission_code) DO NOTHING;

-- Role-Permission mapping
CREATE TABLE IF NOT EXISTS role_permissions (
    role_code       VARCHAR(50) NOT NULL REFERENCES roles(role_code) ON DELETE CASCADE,
    permission_code VARCHAR(80) NOT NULL REFERENCES permissions(permission_code) ON DELETE CASCADE,
    granted         BOOLEAN     NOT NULL DEFAULT TRUE,
    PRIMARY KEY (role_code, permission_code)
);

-- Default mappings
DELETE FROM role_permissions;

-- ADMIN: all
INSERT INTO role_permissions (role_code, permission_code)
SELECT 'ADMIN', permission_code FROM permissions;

-- SALES_MANAGER: sales/purchasing/ar + master view
INSERT INTO role_permissions (role_code, permission_code) VALUES
    ('SALES_MANAGER', 'sales.view'),  ('SALES_MANAGER', 'sales.edit'),
    ('SALES_MANAGER', 'sales.approve'),('SALES_MANAGER', 'sales.convert_order'),
    ('SALES_MANAGER', 'purchasing.view'),('SALES_MANAGER', 'purchasing.edit'),
    ('SALES_MANAGER', 'inventory.view'),
    ('SALES_MANAGER', 'ar.view'),     ('SALES_MANAGER', 'ar.edit'),
    ('SALES_MANAGER', 'master.view'),
    ('SALES_MANAGER', 'expense.view');

-- ACCOUNTING: accounting/AR/AP/payroll
INSERT INTO role_permissions (role_code, permission_code) VALUES
    ('ACCOUNTING', 'accounting.view'),('ACCOUNTING', 'accounting.edit'),
    ('ACCOUNTING', 'ar.view'),        ('ACCOUNTING', 'ar.edit'),
    ('ACCOUNTING', 'ap.view'),        ('ACCOUNTING', 'ap.edit'),
    ('ACCOUNTING', 'payroll.view'),   ('ACCOUNTING', 'payroll.edit'),
    ('ACCOUNTING', 'expense.view'),   ('ACCOUNTING', 'expense.approve'),
    ('ACCOUNTING', 'master.view');

-- STAFF: minimal view
INSERT INTO role_permissions (role_code, permission_code) VALUES
    ('STAFF', 'sales.view'),
    ('STAFF', 'inventory.view'),
    ('STAFF', 'expense.view');

-- Verify
SELECT r.role_code, COUNT(rp.permission_code) AS perm_count
FROM roles r
LEFT JOIN role_permissions rp ON rp.role_code = r.role_code
GROUP BY r.role_code
ORDER BY r.role_code;

COMMIT;
