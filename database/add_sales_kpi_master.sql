-- ============================================================
-- Sales KPI master (per-employee target + monthly distribution %)
-- Powers the KPI dashboard at /sales/kpi
-- ============================================================

BEGIN;

CREATE TABLE IF NOT EXISTS sales_kpi_targets (
    kpi_id                SERIAL          PRIMARY KEY,
    employee_id           INT             NOT NULL REFERENCES employees(employee_id) ON DELETE CASCADE,
    fiscal_year           INT             NOT NULL,  -- e.g. 2026 (Apr 2026 – Mar 2027)
    annual_contact_target INT             NOT NULL DEFAULT 0,   -- yearly # of contacts
    annual_meeting_target INT             NOT NULL DEFAULT 0,   -- yearly # of meetings
    annual_profit_target  NUMERIC(18,2)   NOT NULL DEFAULT 0,   -- yearly profit (THB)
    notes                 TEXT,
    created_by            INT,
    created_at            TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at            TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (employee_id, fiscal_year)
);
CREATE INDEX IF NOT EXISTS idx_kpi_emp_year ON sales_kpi_targets(employee_id, fiscal_year);

-- Monthly distribution — percentages that sum to 100 (configurable per employee-year)
CREATE TABLE IF NOT EXISTS sales_kpi_monthly_pct (
    kpi_id       INT  NOT NULL REFERENCES sales_kpi_targets(kpi_id) ON DELETE CASCADE,
    month_no     INT  NOT NULL CHECK (month_no BETWEEN 1 AND 12), -- 1=Apr, 2=May, ... 12=Mar
    pct          NUMERIC(6,3) NOT NULL DEFAULT 8.333,              -- default = 100/12
    PRIMARY KEY (kpi_id, month_no)
);

-- Seed Picharmon (200万THB) and Nichapa (230万THB) for FY 2026
DO $$
DECLARE
    v_pich INT; v_nich INT;
    v_pk_kpi INT; v_nk_kpi INT;
BEGIN
    SELECT employee_id INTO v_pich FROM employees WHERE full_name ILIKE '%Picharmon%' LIMIT 1;
    SELECT employee_id INTO v_nich FROM employees WHERE full_name ILIKE '%Nichapa%'   LIMIT 1;

    IF v_pich IS NOT NULL THEN
        INSERT INTO sales_kpi_targets (employee_id, fiscal_year, annual_contact_target,
                                       annual_meeting_target, annual_profit_target, notes)
        VALUES (v_pich, 2026, 2775, 275, 2000000, 'Picharmon — FY2026 target (Sales_Management_Tomas Tech_20260404)')
        ON CONFLICT (employee_id, fiscal_year) DO UPDATE SET
            annual_contact_target = EXCLUDED.annual_contact_target,
            annual_meeting_target = EXCLUDED.annual_meeting_target,
            annual_profit_target  = EXCLUDED.annual_profit_target,
            notes = EXCLUDED.notes,
            updated_at = NOW()
        RETURNING kpi_id INTO v_pk_kpi;

        -- Default monthly distribution: 4% for Apr, 12% for May-Mar (matches Excel 111/333)
        DELETE FROM sales_kpi_monthly_pct WHERE kpi_id = v_pk_kpi;
        INSERT INTO sales_kpi_monthly_pct (kpi_id, month_no, pct) VALUES
            (v_pk_kpi, 1, 4.0),  (v_pk_kpi, 2, 12.0), (v_pk_kpi, 3, 12.0),
            (v_pk_kpi, 4, 12.0), (v_pk_kpi, 5, 12.0), (v_pk_kpi, 6, 12.0),
            (v_pk_kpi, 7, 12.0), (v_pk_kpi, 8, 12.0), (v_pk_kpi, 9, 12.0),
            (v_pk_kpi, 10, 0.0),(v_pk_kpi, 11, 0.0),(v_pk_kpi, 12, 0.0);
    END IF;

    IF v_nich IS NOT NULL THEN
        INSERT INTO sales_kpi_targets (employee_id, fiscal_year, annual_contact_target,
                                       annual_meeting_target, annual_profit_target, notes)
        VALUES (v_nich, 2026, 3200, 317, 2300000, 'Nichapa — FY2026 target')
        ON CONFLICT (employee_id, fiscal_year) DO UPDATE SET
            annual_contact_target = EXCLUDED.annual_contact_target,
            annual_meeting_target = EXCLUDED.annual_meeting_target,
            annual_profit_target  = EXCLUDED.annual_profit_target,
            notes = EXCLUDED.notes,
            updated_at = NOW()
        RETURNING kpi_id INTO v_nk_kpi;

        DELETE FROM sales_kpi_monthly_pct WHERE kpi_id = v_nk_kpi;
        INSERT INTO sales_kpi_monthly_pct (kpi_id, month_no, pct) VALUES
            (v_nk_kpi, 1, 4.0),  (v_nk_kpi, 2, 12.0), (v_nk_kpi, 3, 12.0),
            (v_nk_kpi, 4, 12.0), (v_nk_kpi, 5, 12.0), (v_nk_kpi, 6, 12.0),
            (v_nk_kpi, 7, 12.0), (v_nk_kpi, 8, 12.0), (v_nk_kpi, 9, 12.0),
            (v_nk_kpi, 10, 0.0),(v_nk_kpi, 11, 0.0),(v_nk_kpi, 12, 0.0);
    END IF;
END $$;

-- Verify
SELECT e.full_name, k.fiscal_year,
       k.annual_contact_target, k.annual_meeting_target, k.annual_profit_target
FROM sales_kpi_targets k
JOIN employees e ON e.employee_id = k.employee_id
ORDER BY e.full_name;

COMMIT;
