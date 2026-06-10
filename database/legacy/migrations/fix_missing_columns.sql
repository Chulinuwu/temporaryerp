-- Patch any missing columns flagged by system_test.php
BEGIN;

ALTER TABLE customers ADD COLUMN IF NOT EXISTS approval_status VARCHAR(20) NOT NULL DEFAULT 'APPROVED';
ALTER TABLE customers ADD COLUMN IF NOT EXISTS approved_by INT;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS approved_at TIMESTAMPTZ;

ALTER TABLE deals ADD COLUMN IF NOT EXISTS evaluation_profit_pct NUMERIC(5,2);

ALTER TABLE deal_activities ADD COLUMN IF NOT EXISTS contact_id INT REFERENCES customer_contacts(contact_id) ON DELETE SET NULL;
ALTER TABLE deal_activities ADD COLUMN IF NOT EXISTS start_time TIME;
ALTER TABLE deal_activities ADD COLUMN IF NOT EXISTS end_time   TIME;

ALTER TABLE divisions   ADD COLUMN IF NOT EXISTS division_name_th  VARCHAR(100);
ALTER TABLE departments ADD COLUMN IF NOT EXISTS department_name_th VARCHAR(100);

ALTER TABLE solution_categories ADD COLUMN IF NOT EXISTS category_group VARCHAR(30);
ALTER TABLE solution_categories ADD COLUMN IF NOT EXISTS evaluation_profit_pct NUMERIC(5,2) DEFAULT 100;

COMMIT;
