-- ============================================================
-- Fully remove payroll feature + salary/bank columns from employees
-- (payroll module will NOT be used)
-- ============================================================

BEGIN;

-- 1) Drop payroll tables (order matters: lines → header → tax brackets)
DROP TABLE IF EXISTS payroll_lines       CASCADE;
DROP TABLE IF EXISTS payroll_headers     CASCADE;
DROP TABLE IF EXISTS pit_tax_brackets    CASCADE;

-- 2) Drop salary / bank columns from employees
ALTER TABLE employees DROP COLUMN IF EXISTS base_salary;
ALTER TABLE employees DROP COLUMN IF EXISTS salary_type;
ALTER TABLE employees DROP COLUMN IF EXISTS bank_code;
ALTER TABLE employees DROP COLUMN IF EXISTS bank_account_no;
ALTER TABLE employees DROP COLUMN IF EXISTS bank_account_name;

-- 3) Verify
SELECT column_name FROM information_schema.columns
 WHERE table_name = 'employees' AND column_name ILIKE ANY (ARRAY['%salary%','%bank%']);
-- ^ should return 0 rows

SELECT tablename FROM pg_tables WHERE schemaname='public'
 AND tablename ILIKE '%payroll%' OR tablename = 'pit_tax_brackets';
-- ^ should return 0 rows

COMMIT;
