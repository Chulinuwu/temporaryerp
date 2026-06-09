-- ============================================================
-- #16 Fix Division / Department master values
--   - Add TH name columns
--   - Set proper Thai/English/Japanese names
-- ============================================================

BEGIN;

-- Add TH columns if missing
ALTER TABLE divisions   ADD COLUMN IF NOT EXISTS division_name_th  VARCHAR(100);
ALTER TABLE departments ADD COLUMN IF NOT EXISTS department_name_th VARCHAR(100);

-- ── Divisions: Tomas Tech HQ ──
UPDATE divisions
SET division_name    = 'Tomas Tech Co., Ltd.',
    division_name_jp = 'トーマステック株式会社',
    division_name_th = 'บริษัท โทมัสเทค จำกัด'
WHERE division_id = 1;

-- ── Departments: standardise EN/JP/TH names ──
UPDATE departments SET department_name = 'Sales',                     department_name_jp = '営業部',                  department_name_th = 'ฝ่ายขาย'                              WHERE department_code = 'SALES';
UPDATE departments SET department_name = 'Purchasing',                department_name_jp = '購買部',                  department_name_th = 'ฝ่ายจัดซื้อ'                          WHERE department_code = 'PURCHASING';
UPDATE departments SET department_name = 'Production',                department_name_jp = '製造部',                  department_name_th = 'ฝ่ายผลิต'                              WHERE department_code = 'PRODUCTION';
UPDATE departments SET department_name = 'Quality Assurance',         department_name_jp = '品質管理部',              department_name_th = 'ฝ่ายควบคุมคุณภาพ'                      WHERE department_code = 'QA';
UPDATE departments SET department_name = 'Accounting',                department_name_jp = '経理部',                  department_name_th = 'ฝ่ายบัญชี'                            WHERE department_code = 'ACCOUNTING';
UPDATE departments SET department_name = 'Human Resources',           department_name_jp = '人事部',                  department_name_th = 'ฝ่ายบุคคล'                            WHERE department_code = 'HR';
UPDATE departments SET department_name = 'IT',                        department_name_jp = '情報システム部',           department_name_th = 'ฝ่ายไอที'                             WHERE department_code = 'IT';
UPDATE departments SET department_name = 'Management',                department_name_jp = '経営管理部',              department_name_th = 'ฝ่ายบริหาร'                            WHERE department_code = 'MGMT';
UPDATE departments SET department_name = 'Sales & Consulting Dept.',  department_name_jp = '営業・コンサルティング部', department_name_th = 'ฝ่ายขายและที่ปรึกษา'                  WHERE department_code = 'SALES_CONSULT';
UPDATE departments SET department_name = 'Application Engineer Dept.',department_name_jp = 'アプリケーションエンジニア部', department_name_th = 'ฝ่ายวิศวกรประยุกต์'              WHERE department_code = 'APP_ENG';
UPDATE departments SET department_name = 'Administration Dept.',      department_name_jp = '総務部',                  department_name_th = 'ฝ่ายธุรการ'                            WHERE department_code = 'ADMIN';
UPDATE departments SET department_name = 'IoT Engineer Dept.',        department_name_jp = 'IoTエンジニア部',         department_name_th = 'ฝ่ายวิศวกร IoT'                       WHERE department_code = 'IOT_ENG';
UPDATE departments SET department_name = 'Mechanical Engineer Dept.', department_name_jp = '機械エンジニア部',         department_name_th = 'ฝ่ายวิศวกรเครื่องกล'                  WHERE department_code = 'MECH_ENG';

-- Verify
SELECT division_id, division_code, division_name, division_name_jp, division_name_th FROM divisions ORDER BY division_id;
SELECT department_id, department_code, department_name, department_name_jp, department_name_th FROM departments ORDER BY department_id;

COMMIT;
