-- ============================================================
-- PEGASUS ERP - CRM / Deal Management Schema Extension
-- ============================================================

-- ── Solution Categories (ソリューションカテゴリ) ──
CREATE TABLE IF NOT EXISTS solution_categories (
    category_id     SERIAL          PRIMARY KEY,
    category_name   VARCHAR(100)    NOT NULL UNIQUE,
    category_name_jp VARCHAR(100),
    category_name_th VARCHAR(100),
    sort_order      INT             NOT NULL DEFAULT 0,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Deal Statuses / Possibility (案件ステータス) ──
CREATE TABLE IF NOT EXISTS deal_statuses (
    status_id       SERIAL          PRIMARY KEY,
    status_name     VARCHAR(50)     NOT NULL UNIQUE,
    status_name_jp  VARCHAR(50),
    status_name_th  VARCHAR(50),
    win_pct         NUMERIC(5,2)    NOT NULL DEFAULT 0,
    sort_order      INT             NOT NULL DEFAULT 0,
    color           VARCHAR(20)     DEFAULT '#757575',
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Activity Categories (活動種別) ──
CREATE TABLE IF NOT EXISTS activity_categories (
    category_id     SERIAL          PRIMARY KEY,
    category_name   VARCHAR(50)     NOT NULL UNIQUE,
    category_name_jp VARCHAR(50),
    category_name_th VARCHAR(50),
    icon            VARCHAR(10)     DEFAULT '',
    sort_order      INT             NOT NULL DEFAULT 0,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Deals (案件) ──
CREATE TABLE IF NOT EXISTS deals (
    deal_id         SERIAL          PRIMARY KEY,
    deal_no         VARCHAR(30)     NOT NULL UNIQUE,
    deal_name       VARCHAR(200)    NOT NULL,
    customer_id     INT             REFERENCES customers(customer_id),
    customer_staff  VARCHAR(100),
    touch_point     VARCHAR(100),
    status_id       INT             REFERENCES deal_statuses(status_id),
    solution_category_id INT        REFERENCES solution_categories(category_id),
    expected_amount NUMERIC(18,2)   DEFAULT 0,
    expected_close  DATE,
    sales_person_id INT             REFERENCES employees(employee_id),
    pj_no           VARCHAR(50),
    related_projects TEXT,
    note            TEXT,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by      INT,
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Deal Activities (活動ログ) ──
CREATE TABLE IF NOT EXISTS deal_activities (
    activity_id     SERIAL          PRIMARY KEY,
    deal_id         INT             NOT NULL REFERENCES deals(deal_id),
    activity_category_id INT        REFERENCES activity_categories(category_id),
    activity_date   DATE            NOT NULL DEFAULT CURRENT_DATE,
    subject         VARCHAR(200)    NOT NULL,
    description     TEXT,
    contact_person  VARCHAR(100),
    duration_min    INT,
    next_action     TEXT,
    next_action_date DATE,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by      INT,
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── ALTER quotation_headers: link to deals + extra fields ──
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS deal_id INT REFERENCES deals(deal_id);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS solution_category_id INT REFERENCES solution_categories(category_id);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS possibility VARCHAR(30);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS pj_no VARCHAR(50);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS sales_person_id INT REFERENCES employees(employee_id);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS customer_staff VARCHAR(100);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS touch_point VARCHAR(100);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS solution_name VARCHAR(200);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS service_cost NUMERIC(18,2) DEFAULT 0;
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS engineer_cost NUMERIC(18,2) DEFAULT 0;
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS commission NUMERIC(18,2) DEFAULT 0;
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS service_profit NUMERIC(18,2) DEFAULT 0;
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS service_profit_pct NUMERIC(5,2) DEFAULT 0;
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS gross_profit NUMERIC(18,2) DEFAULT 0;
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS gross_profit_pct NUMERIC(5,2) DEFAULT 0;
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS invoice_schedule VARCHAR(50);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS income_schedule VARCHAR(50);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS budget VARCHAR(50);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS unique_key VARCHAR(200);
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS po_date DATE;
ALTER TABLE quotation_headers ADD COLUMN IF NOT EXISTS sales_name VARCHAR(100);

-- ── Indexes ──
CREATE INDEX IF NOT EXISTS idx_deals_customer ON deals(customer_id);
CREATE INDEX IF NOT EXISTS idx_deals_status ON deals(status_id);
CREATE INDEX IF NOT EXISTS idx_deals_sales ON deals(sales_person_id);
CREATE INDEX IF NOT EXISTS idx_deal_activities_deal ON deal_activities(deal_id);
CREATE INDEX IF NOT EXISTS idx_quotation_deal ON quotation_headers(deal_id);
CREATE INDEX IF NOT EXISTS idx_quotation_unique_key ON quotation_headers(unique_key);

-- ── Number sequence for Deals ──
INSERT INTO number_sequences (seq_name, prefix, current_no, format_pattern)
VALUES ('DEAL', 'DL', 0, '{PREFIX}-{YYYYMM}-{NNNN}')
ON CONFLICT (seq_name) DO NOTHING;

-- Update quotation sequence to new format QT-YYYYMM-NNNN
UPDATE number_sequences SET format_pattern = '{PREFIX}-{YYYYMM}-{NNNN}' WHERE seq_name = 'QUOTATION';
INSERT INTO number_sequences (seq_name, prefix, current_no, format_pattern)
VALUES ('QUOTATION', 'QT', 0, '{PREFIX}-{YYYYMM}-{NNNN}')
ON CONFLICT (seq_name) DO NOTHING;
