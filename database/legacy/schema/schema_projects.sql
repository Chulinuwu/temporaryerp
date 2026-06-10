-- ============================================================
-- PEGASUS ERP - Project Management Module Schema
-- Based on quotation_list.xlsx "Base" sheet + PJ_list Budget Table
-- ============================================================

-- ── Projects (PJ一覧) ──
CREATE TABLE IF NOT EXISTS projects (
    project_id          SERIAL          PRIMARY KEY,
    pj_no               VARCHAR(20)     NOT NULL UNIQUE,   -- PJ2600001 format
    related_pj_no       VARCHAR(20),                       -- Related Projects
    pj_segment          VARCHAR(200),                      -- PJ segment (solution category)
    pj_category         VARCHAR(200),                      -- PJ Category
    pj_classification   VARCHAR(100),                      -- PJ Classification
    pj_name             VARCHAR(500)    NOT NULL,          -- Project name
    customer_id         INT             REFERENCES customers(customer_id),
    end_user_customer   VARCHAR(300),                      -- End user customer name
    -- ── Financial: Revenue breakdown ──
    total_revenue       NUMERIC(18,2)   DEFAULT 0,         -- 受注金額 (Total Revenue)
    cost_total          NUMERIC(18,2)   DEFAULT 0,         -- Cost Total (original estimate)
    sales_hardware      NUMERIC(18,2)   DEFAULT 0,         -- Sales - Hardware
    sales_software      NUMERIC(18,2)   DEFAULT 0,         -- Sales - Software
    sales_sw_development NUMERIC(18,2)  DEFAULT 0,         -- Sales - Software development
    sales_sw_license    NUMERIC(18,2)   DEFAULT 0,         -- Sales - Software license
    sales_installation  NUMERIC(18,2)   DEFAULT 0,         -- Sales - Installation
    sales_sw_installation NUMERIC(18,2) DEFAULT 0,         -- Sales - Software Installation
    sales_hw_wiring     NUMERIC(18,2)   DEFAULT 0,         -- Sales - Hard+Wiring Installation
    -- ── Financial: Cost & Profit ──
    total_cost          NUMERIC(18,2)   DEFAULT 0,         -- 原価 (Total Cost)
    gross_profit        NUMERIC(18,2)   DEFAULT 0,         -- 粗利 (Profit)
    profit_pct          NUMERIC(5,2)    DEFAULT 0,         -- Profit %
    service_cost        NUMERIC(18,2)   DEFAULT 0,         -- Service Cost
    engineer_cost       NUMERIC(18,2)   DEFAULT 0,         -- Engineer Cost
    -- ── Man-months ──
    mm_programming      NUMERIC(6,2)    DEFAULT 0,         -- Man-months: Programming
    mm_design           NUMERIC(6,2)    DEFAULT 0,         -- Man-months: Design
    mm_testing          NUMERIC(6,2)    DEFAULT 0,         -- Man-months: Testing
    unit_price_programming NUMERIC(18,2) DEFAULT 0,        -- Unit price: Programming
    unit_price_design   NUMERIC(18,2)   DEFAULT 0,         -- Unit price: Design
    unit_price_testing  NUMERIC(18,2)   DEFAULT 0,         -- Unit price: Testing
    -- ── Budget Table fields ──
    purchase_estimate   NUMERIC(18,2)   DEFAULT 0,         -- Purchase cost (Estimate)
    purchase_target     NUMERIC(18,2)   DEFAULT 0,         -- Purchase cost (Target)
    purchase_actual     NUMERIC(18,2)   DEFAULT 0,         -- Purchase cost (Actual Result)
    gp_estimate         NUMERIC(18,2)   DEFAULT 0,         -- Gross Profit (Estimate)
    gp_target           NUMERIC(18,2)   DEFAULT 0,         -- Gross Profit (Target)
    gp_actual           NUMERIC(18,2)   DEFAULT 0,         -- Gross Profit (Actual Result)
    currency_code       CHAR(3)         DEFAULT 'THB',
    -- ── Dates ──
    po_date             DATE,                              -- 受注日 (PO Date)
    start_work_date     DATE,                              -- 作業開始日
    finished_work_date  DATE,                              -- 作業完了日
    plan_delivery_date  DATE,                              -- 納品予定日
    delivery_date       DATE,                              -- 納品日
    inspection_date     DATE,                              -- Inspection date
    complete_date       DATE,                              -- Complete date
    delivery_place      VARCHAR(300),                      -- Delivery place
    -- ── Payment Schedule ──
    payment_term_id     INT             REFERENCES payment_terms(term_id),
    -- Legacy fixed payment columns (deprecated - use project_payment_schedules table)
    payment1_plan_date  DATE,
    payment1_actual_date DATE,
    payment1_amount     NUMERIC(18,2)   DEFAULT 0,
    payment2_plan_date  DATE,
    payment2_actual_date DATE,
    payment2_amount     NUMERIC(18,2)   DEFAULT 0,
    payment3_plan_date  DATE,
    payment3_actual_date DATE,
    payment3_amount     NUMERIC(18,2)   DEFAULT 0,
    -- ── Links ──
    so_id               INT             REFERENCES sales_order_headers(so_id),
    deal_id             INT             REFERENCES deals(deal_id),
    sales_person_id     INT             REFERENCES employees(employee_id),
    sales_name          VARCHAR(100),
    -- ── Status ──
    status              VARCHAR(30)     NOT NULL DEFAULT 'ACTIVE'
                        CHECK (status IN ('ACTIVE','IN_PROGRESS','COMPLETED','ON_HOLD','CANCELLED')),
    progress_pct        NUMERIC(5,2)    DEFAULT 0,         -- Overall progress %
    remark              TEXT,
    -- ── Audit ──
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by          INT,
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Project Invoices (売上インボイス) ──
CREATE TABLE IF NOT EXISTS project_invoices (
    invoice_id          SERIAL          PRIMARY KEY,
    project_id          INT             NOT NULL REFERENCES projects(project_id) ON DELETE CASCADE,
    line_no             INT             NOT NULL,          -- Items (行番号)
    invoice_date        DATE,                              -- Date
    invoice_no          VARCHAR(100),                      -- Invoice No.
    customer_name       VARCHAR(300),                      -- Customers
    amount              NUMERIC(18,2)   DEFAULT 0,         -- Amount
    remark              TEXT,                               -- Remark
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Project Purchases (発注/コスト) ──
CREATE TABLE IF NOT EXISTS project_purchases (
    purchase_id         SERIAL          PRIMARY KEY,
    project_id          INT             NOT NULL REFERENCES projects(project_id) ON DELETE CASCADE,
    line_no             INT             NOT NULL,          -- Items (行番号)
    purchase_date       DATE,                              -- Date
    purchase_invoice_no VARCHAR(100),                      -- Invoice No
    description         VARCHAR(500),                      -- Purchase item description
    amount              NUMERIC(18,2)   DEFAULT 0,         -- Amount (Baht)
    payment_terms       VARCHAR(200),                      -- e.g., "CASH ONLY 100%", "Credit 30 days"
    po_no               VARCHAR(100),                      -- PO No. (linked purchase order)
    po_id               INT,                               -- FK to purchase_order_headers if exists
    supplier_name       VARCHAR(300),                      -- Supplier
    remark              TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Project Progress (月次進捗) ──
CREATE TABLE IF NOT EXISTS project_progress (
    progress_id         SERIAL          PRIMARY KEY,
    project_id          INT             NOT NULL REFERENCES projects(project_id) ON DELETE CASCADE,
    month_date          DATE            NOT NULL,          -- Month (first day of month)
    plan_pct            NUMERIC(5,2)    DEFAULT 0,         -- Plan progress %
    actual_pct          NUMERIC(5,2)    DEFAULT 0,         -- Actual progress %
    UNIQUE(project_id, month_date)
);

-- ── Project Payment Schedules (入金スケジュール) ──
CREATE TABLE IF NOT EXISTS project_payment_schedules (
    schedule_id         SERIAL          PRIMARY KEY,
    project_id          INT             NOT NULL REFERENCES projects(project_id) ON DELETE CASCADE,
    seq_no              SMALLINT        NOT NULL,             -- 回数 (1,2,3,4...)
    description         TEXT,                                 -- Description / milestone
    percentage          NUMERIC(5,2)    DEFAULT 0,            -- 割合 (%)
    credit_days         INT             DEFAULT 0,            -- Credit日数
    plan_date           DATE,                                 -- 予定日
    actual_date         DATE,                                 -- 実績日
    amount              NUMERIC(18,2)   DEFAULT 0,            -- 金額
    remark              TEXT,
    UNIQUE(project_id, seq_no)
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_projects_pj_no ON projects(pj_no);
CREATE INDEX IF NOT EXISTS idx_projects_customer ON projects(customer_id);
CREATE INDEX IF NOT EXISTS idx_projects_so ON projects(so_id);
CREATE INDEX IF NOT EXISTS idx_projects_deal ON projects(deal_id);
CREATE INDEX IF NOT EXISTS idx_projects_status ON projects(status);
CREATE INDEX IF NOT EXISTS idx_project_invoices_pj ON project_invoices(project_id);
CREATE INDEX IF NOT EXISTS idx_project_purchases_pj ON project_purchases(project_id);
CREATE INDEX IF NOT EXISTS idx_project_payment_schedules_pj ON project_payment_schedules(project_id);
