-- ============================================================
-- Extend sales_kpi_targets with rate-based model:
--   Annual Orders  = profit / profit_per_order
--   Annual Meetings = orders / close_rate_pct
--   Annual Contacts = meetings / appt_rate_pct
-- ============================================================

BEGIN;

ALTER TABLE sales_kpi_targets
    ADD COLUMN IF NOT EXISTS profit_per_order NUMERIC(18,2) NOT NULL DEFAULT 100000,   -- THB per order
    ADD COLUMN IF NOT EXISTS close_rate_pct   NUMERIC(6,3)  NOT NULL DEFAULT 5.0,      -- meetings → orders
    ADD COLUMN IF NOT EXISTS appt_rate_pct    NUMERIC(6,3)  NOT NULL DEFAULT 10.0,     -- contacts → meetings
    ADD COLUMN IF NOT EXISTS annual_order_target INT        NOT NULL DEFAULT 0;        -- derived / editable

-- Re-seed Picharmon & Nichapa with the new rate-based figures
DO $$
DECLARE
    v_pich INT; v_nich INT;
BEGIN
    SELECT employee_id INTO v_pich FROM employees WHERE full_name ILIKE '%Picharmon%' LIMIT 1;
    SELECT employee_id INTO v_nich FROM employees WHERE full_name ILIKE '%Nichapa%'   LIMIT 1;

    IF v_pich IS NOT NULL THEN
        UPDATE sales_kpi_targets SET
            annual_profit_target  = 2000000,
            profit_per_order      = 100000,
            close_rate_pct        = 5.0,
            appt_rate_pct         = 10.0,
            annual_order_target   = 20,     -- 2,000,000 / 100,000
            annual_meeting_target = 400,    -- 20 / 0.05
            annual_contact_target = 4000,   -- 400 / 0.10
            updated_at = NOW()
        WHERE employee_id = v_pich AND fiscal_year = 2026;
    END IF;

    IF v_nich IS NOT NULL THEN
        UPDATE sales_kpi_targets SET
            annual_profit_target  = 2300000,
            profit_per_order      = 100000,
            close_rate_pct        = 5.0,
            appt_rate_pct         = 10.0,
            annual_order_target   = 23,     -- 2,300,000 / 100,000
            annual_meeting_target = 460,    -- 23 / 0.05
            annual_contact_target = 4600,   -- 460 / 0.10
            updated_at = NOW()
        WHERE employee_id = v_nich AND fiscal_year = 2026;
    END IF;
END $$;

-- Verify
SELECT e.full_name,
       k.annual_profit_target, k.profit_per_order,
       k.annual_order_target, k.close_rate_pct,
       k.annual_meeting_target, k.appt_rate_pct,
       k.annual_contact_target
FROM sales_kpi_targets k
JOIN employees e ON e.employee_id = k.employee_id
ORDER BY e.full_name;

COMMIT;
