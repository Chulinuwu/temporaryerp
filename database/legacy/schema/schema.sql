-- ============================================================
-- PEGASUS ERP - PostgreSQL Schema v3.0
-- Tomas Tech Co., Ltd.
-- TFRS / Thailand Revenue Code / Labour Protection Act B.E.2541
-- ============================================================

-- ── Extensions ──
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ============================================================
-- 1. CORE TABLES
-- ============================================================

-- ── Users (Authentication) ──
CREATE TABLE users (
    user_id         SERIAL          PRIMARY KEY,
    username        VARCHAR(50)     NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    email           VARCHAR(200)    UNIQUE,
    role            VARCHAR(50)     NOT NULL DEFAULT 'STAFF'
                    CHECK (role IN ('ADMIN','MANAGER','ACCOUNTING','HR','SALES','PURCHASE','PRODUCTION','QA','STAFF')),
    employee_id     INT,
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE,
    last_login      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Divisions ──
CREATE TABLE divisions (
    division_id     SERIAL          PRIMARY KEY,
    division_code   VARCHAR(20)     NOT NULL UNIQUE,
    division_name   VARCHAR(100)    NOT NULL,
    division_name_jp VARCHAR(100),
    parent_id       INT             REFERENCES divisions(division_id),
    division_type   VARCHAR(20)     CHECK (division_type IN ('COMPANY','BRANCH','DEPARTMENT','SECTION')),
    country_code    CHAR(2)         NOT NULL DEFAULT 'TH',
    currency_code   CHAR(3)         NOT NULL DEFAULT 'THB',
    tax_id          VARCHAR(50),
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    effective_from  DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to    DATE,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by      INT,
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Departments ──
CREATE TABLE departments (
    department_id   SERIAL          PRIMARY KEY,
    department_code VARCHAR(20)     NOT NULL,
    division_id     INT             NOT NULL REFERENCES divisions(division_id),
    department_name VARCHAR(100)    NOT NULL,
    department_name_jp VARCHAR(100),
    manager_id      INT,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (department_code, division_id)
);

-- ── Employees ──
CREATE TABLE employees (
    employee_id         SERIAL          PRIMARY KEY,
    emp_code            VARCHAR(20)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    department_id       INT             REFERENCES departments(department_id),
    full_name           VARCHAR(100)    NOT NULL,
    full_name_jp        VARCHAR(100),
    full_name_th        VARCHAR(100),
    nickname            VARCHAR(50),
    nationality         CHAR(2)         NOT NULL DEFAULT 'TH',
    thai_id             VARCHAR(13),
    passport_no         VARCHAR(30),
    work_permit_no      VARCHAR(50),
    work_permit_expiry  DATE,
    visa_type           VARCHAR(20),
    visa_expiry         DATE,
    hire_date           DATE            NOT NULL,
    probation_end_date  DATE,
    employment_type     VARCHAR(20)     NOT NULL DEFAULT 'FULL_TIME'
                        CHECK (employment_type IN ('FULL_TIME','PART_TIME','CONTRACT','DAILY')),
    position_title      VARCHAR(100),
    position_level      VARCHAR(20),
    email               VARCHAR(200),
    phone               VARCHAR(50),
    salary_type         VARCHAR(10)     NOT NULL DEFAULT 'MONTHLY'
                        CHECK (salary_type IN ('MONTHLY','DAILY','HOURLY')),
    base_salary         NUMERIC(14,2)   NOT NULL DEFAULT 0,
    salary_currency     CHAR(3)         NOT NULL DEFAULT 'THB',
    bank_code           VARCHAR(10),
    bank_account_no     VARCHAR(30),
    bank_account_name   VARCHAR(100),
    annual_leave_days   NUMERIC(5,1)    NOT NULL DEFAULT 6,
    sick_leave_days     NUMERIC(5,1)    NOT NULL DEFAULT 30,
    leave_balance_annual NUMERIC(5,1)   NOT NULL DEFAULT 0,
    leave_balance_sick  NUMERIC(5,1)    NOT NULL DEFAULT 0,
    sso_enrolled        BOOLEAN         NOT NULL DEFAULT TRUE,
    sso_no              VARCHAR(20),
    role                VARCHAR(50),
    approval_limit      NUMERIC(18,2)   DEFAULT 0,
    effective_from      DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to        DATE,
    version_no          INT             NOT NULL DEFAULT 1,
    is_current          BOOLEAN         NOT NULL DEFAULT TRUE,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_reason      TEXT,
    termination_date    DATE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by          INT,
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- Add FK from users to employees
ALTER TABLE users ADD CONSTRAINT fk_users_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id);
ALTER TABLE departments ADD CONSTRAINT fk_dept_manager FOREIGN KEY (manager_id) REFERENCES employees(employee_id);

-- ============================================================
-- 2. MASTER TABLES
-- ============================================================

-- ── Accounts (Chart of Accounts - TFRS) ──
CREATE TABLE accounts (
    account_id      SERIAL          PRIMARY KEY,
    account_code    VARCHAR(20)     NOT NULL,
    division_id     INT             REFERENCES divisions(division_id),
    account_name    VARCHAR(200)    NOT NULL,
    account_name_jp VARCHAR(200),
    account_name_th VARCHAR(200),
    account_type    VARCHAR(20)     NOT NULL
                    CHECK (account_type IN ('ASSET','LIABILITY','EQUITY','REVENUE','COGS','EXPENSE')),
    bs_pl           CHAR(2)         NOT NULL CHECK (bs_pl IN ('BS','PL')),
    parent_code     VARCHAR(20),
    is_tax_relevant BOOLEAN         NOT NULL DEFAULT FALSE,
    tax_form        VARCHAR(20),
    default_tax_rate NUMERIC(5,2),
    is_control_acct BOOLEAN         NOT NULL DEFAULT FALSE,
    effective_from  DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to    DATE,
    version_no      INT             NOT NULL DEFAULT 1,
    is_current      BOOLEAN         NOT NULL DEFAULT TRUE,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_reason  TEXT,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by      INT,
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (account_code, division_id, effective_from)
);

-- ── Items ──
CREATE TABLE items (
    item_id         SERIAL          PRIMARY KEY,
    item_code       VARCHAR(50)     NOT NULL,
    division_id     INT             NOT NULL REFERENCES divisions(division_id),
    item_name       VARCHAR(200)    NOT NULL,
    item_name_jp    VARCHAR(200),
    item_name_th    VARCHAR(200),
    item_type       VARCHAR(20)     NOT NULL CHECK (item_type IN ('RAW','WIP','FINISHED','MERCHANDISE','SERVICE','SPARE')),
    unit            VARCHAR(20)     NOT NULL DEFAULT 'EA',
    unit_cost_std   NUMERIC(18,4)   DEFAULT 0,
    unit_price_std  NUMERIC(18,4)   DEFAULT 0,
    safety_stock    NUMERIC(14,4)   DEFAULT 0,
    reorder_point   NUMERIC(14,4)   DEFAULT 0,
    lead_time_days  INT             DEFAULT 7,
    lot_managed     BOOLEAN         NOT NULL DEFAULT FALSE,
    serial_managed  BOOLEAN         NOT NULL DEFAULT FALSE,
    tax_code        VARCHAR(20),
    effective_from  DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to    DATE,
    version_no      INT             NOT NULL DEFAULT 1,
    is_current      BOOLEAN         NOT NULL DEFAULT TRUE,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_reason  TEXT,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by      INT,
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (item_code, division_id, effective_from)
);

-- ── Customers ──
CREATE TABLE customers (
    customer_id     SERIAL          PRIMARY KEY,
    customer_code   VARCHAR(30)     NOT NULL,
    division_id     INT             NOT NULL REFERENCES divisions(division_id),
    customer_name   VARCHAR(200)    NOT NULL,
    customer_name_jp VARCHAR(200),
    customer_name_th VARCHAR(200),
    country         CHAR(2)         NOT NULL DEFAULT 'TH',
    address         TEXT,
    tax_id          VARCHAR(20),
    contact_person  VARCHAR(100),
    email           VARCHAR(200),
    phone           VARCHAR(50),
    currency_code   CHAR(3)         NOT NULL DEFAULT 'THB',
    payment_terms   INT             NOT NULL DEFAULT 30,
    credit_limit    NUMERIC(18,2)   DEFAULT 0,
    sales_rep_id    INT             REFERENCES employees(employee_id),
    effective_from  DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to    DATE,
    version_no      INT             NOT NULL DEFAULT 1,
    is_current      BOOLEAN         NOT NULL DEFAULT TRUE,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_reason  TEXT,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by      INT,
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (customer_code, division_id, effective_from)
);

-- ── Suppliers ──
CREATE TABLE suppliers (
    supplier_id     SERIAL          PRIMARY KEY,
    supplier_code   VARCHAR(30)     NOT NULL,
    division_id     INT             NOT NULL REFERENCES divisions(division_id),
    supplier_name   VARCHAR(200)    NOT NULL,
    supplier_name_jp VARCHAR(200),
    supplier_name_th VARCHAR(200),
    country         CHAR(2)         NOT NULL DEFAULT 'TH',
    address         TEXT,
    tax_id          VARCHAR(20),
    contact_person  VARCHAR(100),
    email           VARCHAR(200),
    phone           VARCHAR(50),
    currency_code   CHAR(3)         NOT NULL DEFAULT 'THB',
    payment_terms   INT             NOT NULL DEFAULT 30,
    wht_rate        NUMERIC(5,2)    DEFAULT 3.00,
    effective_from  DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to    DATE,
    version_no      INT             NOT NULL DEFAULT 1,
    is_current      BOOLEAN         NOT NULL DEFAULT TRUE,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_reason  TEXT,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by      INT,
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (supplier_code, division_id, effective_from)
);

-- ── Payment Terms ──
CREATE TABLE payment_terms (
    term_id             SERIAL          PRIMARY KEY,
    term_code           VARCHAR(30)     NOT NULL,
    division_id         INT             REFERENCES divisions(division_id),
    term_name_en        VARCHAR(200)    NOT NULL,
    term_name_jp        VARCHAR(200),
    term_name_th        VARCHAR(200),
    installment_count   INT             NOT NULL DEFAULT 1,
    credit_days         INT,
    display_order       INT             NOT NULL DEFAULT 0,
    notes               TEXT,
    effective_from      DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to        DATE,
    version_no          INT             NOT NULL DEFAULT 1,
    is_current          BOOLEAN         NOT NULL DEFAULT TRUE,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_reason      TEXT,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by          INT,
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE payment_term_installments (
    installment_id      SERIAL          PRIMARY KEY,
    term_id             INT             NOT NULL REFERENCES payment_terms(term_id) ON DELETE CASCADE,
    seq_no              SMALLINT        NOT NULL,
    percentage          NUMERIC(5,2)    NOT NULL,
    description_en      TEXT,
    description_jp      TEXT,
    description_th      TEXT,
    trigger_type        VARCHAR(20)     NOT NULL DEFAULT 'CUSTOM'
                        CHECK (trigger_type IN ('PO','DESIGN','DELIVERY','INSTALLATION','COMPLETION','FAT','SAT','INVOICE','CUSTOM')),
    credit_days         INT,
    UNIQUE (term_id, seq_no)
);

-- ── Banks ──
CREATE TABLE banks (
    bank_id         SERIAL          PRIMARY KEY,
    bank_code       VARCHAR(10)     NOT NULL UNIQUE,
    bank_name       VARCHAR(100)    NOT NULL,
    bank_name_th    VARCHAR(100),
    swift_code      VARCHAR(20),
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Warehouses ──
CREATE TABLE warehouses (
    warehouse_id    SERIAL          PRIMARY KEY,
    warehouse_code  VARCHAR(20)     NOT NULL,
    division_id     INT             NOT NULL REFERENCES divisions(division_id),
    warehouse_name  VARCHAR(100)    NOT NULL,
    address         TEXT,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (warehouse_code, division_id)
);

-- ============================================================
-- 3. SALES MODULE
-- ============================================================

-- ── Quotation Headers ──
CREATE TABLE quotation_headers (
    quotation_id        SERIAL          PRIMARY KEY,
    quotation_no        VARCHAR(30)     NOT NULL UNIQUE,
    revision_no         SMALLINT        NOT NULL DEFAULT 1,
    parent_quotation_id INT             REFERENCES quotation_headers(quotation_id),
    document_template   VARCHAR(30)     NOT NULL DEFAULT 'SYS-MN-Rev#01',
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    customer_id         INT             NOT NULL REFERENCES customers(customer_id),
    attention_name      VARCHAR(100),
    attention_phone     VARCHAR(50),
    attention_email     VARCHAR(200),
    ship_to_address     TEXT,
    project_name        VARCHAR(300),
    project_code        VARCHAR(50),
    rfq_reference       VARCHAR(100),
    issue_date          DATE            NOT NULL DEFAULT CURRENT_DATE,
    expiry_date         DATE,
    valid_days          INT,
    currency_code       CHAR(3)         NOT NULL DEFAULT 'THB',
    exchange_rate       NUMERIC(18,6)   NOT NULL DEFAULT 1,
    subtotal_thb        NUMERIC(18,2)   NOT NULL DEFAULT 0,
    discount_amount     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    vat_rate            NUMERIC(5,2)    NOT NULL DEFAULT 7.00,
    vat_amount          NUMERIC(18,2)   NOT NULL DEFAULT 0,
    grand_total_thb     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    payment_term_id     INT             REFERENCES payment_terms(term_id),
    lead_time_text      VARCHAR(200),
    lead_time_days_min  INT,
    lead_time_days_max  INT,
    warranty_text       VARCHAR(200),
    warranty_months     INT,
    tax_note            VARCHAR(100)    DEFAULT 'Including VAT.',
    incoterms           VARCHAR(30),
    remark_text         TEXT,
    note_text           TEXT,
    status              VARCHAR(20)     NOT NULL DEFAULT 'DRAFT'
                        CHECK (status IN ('DRAFT','INTERNAL_REVIEW','APPROVED','SUBMITTED','NEGOTIATING','WON','LOST','EXPIRED','CANCELLED')),
    won_so_id           INT,
    lost_reason         TEXT,
    competitor_name     VARCHAR(200),
    approved_by         INT             REFERENCES employees(employee_id),
    approved_at         TIMESTAMPTZ,
    quoted_by           INT             REFERENCES employees(employee_id),
    signature_url       VARCHAR(500),
    stamp_url           VARCHAR(500),
    stamp_date          DATE,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_reason      TEXT,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by          INT,
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Quotation Lines ──
CREATE TABLE quotation_lines (
    quot_line_id        SERIAL          PRIMARY KEY,
    quotation_id        INT             NOT NULL REFERENCES quotation_headers(quotation_id) ON DELETE CASCADE,
    line_no             VARCHAR(10)     NOT NULL,
    parent_line_no      VARCHAR(10),
    is_category_row     BOOLEAN         NOT NULL DEFAULT FALSE,
    item_id             INT             REFERENCES items(item_id),
    item_description    VARCHAR(500)    NOT NULL,
    item_description_jp VARCHAR(500),
    item_description_th VARCHAR(500),
    item_note           TEXT,
    quantity            NUMERIC(14,4),
    unit                VARCHAR(30),
    unit_price          NUMERIC(18,4),
    discount_rate       NUMERIC(5,2)    DEFAULT 0,
    ext_price           NUMERIC(18,2)   DEFAULT 0,
    cost_material       NUMERIC(18,2)   DEFAULT 0,
    cost_labor          NUMERIC(18,2)   DEFAULT 0,
    cost_outsource      NUMERIC(18,2)   DEFAULT 0,
    cost_overhead_rate  NUMERIC(5,4)    DEFAULT 0,
    cost_total          NUMERIC(18,2)   DEFAULT 0,
    delivery_days       INT,
    sort_order          INT             NOT NULL DEFAULT 0,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    UNIQUE (quotation_id, line_no)
);

-- ── Sales Orders ──
CREATE TABLE sales_order_headers (
    so_id               SERIAL          PRIMARY KEY,
    so_no               VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    customer_id         INT             NOT NULL REFERENCES customers(customer_id),
    quotation_id        INT             REFERENCES quotation_headers(quotation_id),
    order_date          DATE            NOT NULL DEFAULT CURRENT_DATE,
    requested_date      DATE,
    currency_code       CHAR(3)         NOT NULL DEFAULT 'THB',
    exchange_rate       NUMERIC(18,6)   NOT NULL DEFAULT 1,
    subtotal_thb        NUMERIC(18,2)   NOT NULL DEFAULT 0,
    discount_amount     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    vat_rate            NUMERIC(5,2)    NOT NULL DEFAULT 7.00,
    vat_amount          NUMERIC(18,2)   NOT NULL DEFAULT 0,
    grand_total_thb     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    payment_term_id     INT             REFERENCES payment_terms(term_id),
    status              VARCHAR(20)     NOT NULL DEFAULT 'DRAFT'
                        CHECK (status IN ('DRAFT','CONFIRMED','IN_PRODUCTION','PARTIAL_SHIPPED','SHIPPED','INVOICED','CLOSED','CANCELLED')),
    approved_by         INT             REFERENCES employees(employee_id),
    approved_at         TIMESTAMPTZ,
    sales_rep_id        INT             REFERENCES employees(employee_id),
    notes               TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by          INT,
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE sales_order_lines (
    so_line_id          SERIAL          PRIMARY KEY,
    so_id               INT             NOT NULL REFERENCES sales_order_headers(so_id) ON DELETE CASCADE,
    line_no             SMALLINT        NOT NULL,
    item_id             INT             REFERENCES items(item_id),
    item_description    VARCHAR(500)    NOT NULL,
    quantity            NUMERIC(14,4)   NOT NULL,
    unit                VARCHAR(30)     NOT NULL DEFAULT 'EA',
    unit_price          NUMERIC(18,4)   NOT NULL,
    discount_rate       NUMERIC(5,2)    DEFAULT 0,
    ext_price           NUMERIC(18,2)   DEFAULT 0,
    delivered_qty       NUMERIC(14,4)   NOT NULL DEFAULT 0,
    invoiced_qty        NUMERIC(14,4)   NOT NULL DEFAULT 0,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    UNIQUE (so_id, line_no)
);

-- ============================================================
-- 4. PURCHASING MODULE
-- ============================================================

CREATE TABLE purchase_order_headers (
    po_id               SERIAL          PRIMARY KEY,
    po_no               VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    reference_no        VARCHAR(100),
    supplier_quotation_no VARCHAR(100),
    our_quotation_id    INT             REFERENCES quotation_headers(quotation_id),
    supplier_id         INT             NOT NULL REFERENCES suppliers(supplier_id),
    order_date          DATE            NOT NULL DEFAULT CURRENT_DATE,
    requested_date      DATE,
    delivery_date       DATE,
    contact_person_name VARCHAR(100),
    contact_person_id   INT             REFERENCES employees(employee_id),
    currency_code       CHAR(3)         NOT NULL DEFAULT 'THB',
    exchange_rate       NUMERIC(18,6)   NOT NULL DEFAULT 1,
    subtotal_thb        NUMERIC(18,2)   NOT NULL DEFAULT 0,
    discount_amount     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    vat_rate            NUMERIC(5,2)    NOT NULL DEFAULT 7.00,
    vat_amount          NUMERIC(18,2)   NOT NULL DEFAULT 0,
    total_before_wht    NUMERIC(18,2)   NOT NULL DEFAULT 0,
    wht_amount          NUMERIC(18,2)   NOT NULL DEFAULT 0,
    payment_amount      NUMERIC(18,2)   NOT NULL DEFAULT 0,
    amount_in_words_th  TEXT,
    payment_term_id     INT             REFERENCES payment_terms(term_id),
    payment_term_text   VARCHAR(200),
    status              VARCHAR(20)     NOT NULL DEFAULT 'DRAFT'
                        CHECK (status IN ('DRAFT','PENDING_APPROVAL','APPROVED','SENT','PARTIAL_RECEIVED','FULLY_RECEIVED','CLOSED','CANCELLED')),
    approved_by         INT             REFERENCES employees(employee_id),
    approved_at         TIMESTAMPTZ,
    signature_url       VARCHAR(500),
    approval_date       DATE,
    qr_code_url         VARCHAR(500),
    qr_code_data        TEXT,
    notes               TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_reason      TEXT,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by          INT,
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE purchase_order_lines (
    po_line_id          SERIAL          PRIMARY KEY,
    po_id               INT             NOT NULL REFERENCES purchase_order_headers(po_id) ON DELETE CASCADE,
    line_no             SMALLINT        NOT NULL,
    item_id             INT             REFERENCES items(item_id),
    item_description    VARCHAR(500)    NOT NULL,
    quantity            NUMERIC(14,4)   NOT NULL,
    unit                VARCHAR(30)     NOT NULL DEFAULT 'EA',
    unit_price          NUMERIC(18,4)   NOT NULL,
    discount_rate       NUMERIC(5,2)    DEFAULT 0,
    ext_price           NUMERIC(18,2)   DEFAULT 0,
    received_qty        NUMERIC(14,4)   NOT NULL DEFAULT 0,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    UNIQUE (po_id, line_no)
);

-- ============================================================
-- 5. INVENTORY MODULE
-- ============================================================

CREATE TABLE inventory_transactions (
    txn_id              SERIAL          PRIMARY KEY,
    txn_no              VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    warehouse_id        INT             NOT NULL REFERENCES warehouses(warehouse_id),
    txn_type            VARCHAR(20)     NOT NULL
                        CHECK (txn_type IN ('RECEIPT','ISSUE','TRANSFER','ADJUSTMENT','RETURN')),
    txn_date            DATE            NOT NULL DEFAULT CURRENT_DATE,
    reference_type      VARCHAR(20),
    reference_id        INT,
    item_id             INT             NOT NULL REFERENCES items(item_id),
    quantity            NUMERIC(14,4)   NOT NULL,
    unit                VARCHAR(20)     NOT NULL DEFAULT 'EA',
    unit_cost           NUMERIC(18,4)   DEFAULT 0,
    total_cost          NUMERIC(18,2)   DEFAULT 0,
    lot_no              VARCHAR(50),
    serial_no           VARCHAR(50),
    notes               TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE stock_balances (
    balance_id          SERIAL          PRIMARY KEY,
    warehouse_id        INT             NOT NULL REFERENCES warehouses(warehouse_id),
    item_id             INT             NOT NULL REFERENCES items(item_id),
    quantity_on_hand    NUMERIC(14,4)   NOT NULL DEFAULT 0,
    quantity_reserved   NUMERIC(14,4)   NOT NULL DEFAULT 0,
    quantity_available  NUMERIC(14,4)   NOT NULL DEFAULT 0,
    avg_unit_cost       NUMERIC(18,4)   NOT NULL DEFAULT 0,
    last_updated        TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (warehouse_id, item_id)
);

-- ── Shipments ──
CREATE TABLE shipment_headers (
    shipment_id         SERIAL          PRIMARY KEY,
    shipment_no         VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    so_id               INT             REFERENCES sales_order_headers(so_id),
    customer_id         INT             NOT NULL REFERENCES customers(customer_id),
    warehouse_id        INT             NOT NULL REFERENCES warehouses(warehouse_id),
    shipment_date       DATE            NOT NULL DEFAULT CURRENT_DATE,
    status              VARCHAR(20)     NOT NULL DEFAULT 'DRAFT'
                        CHECK (status IN ('DRAFT','PICKING','PACKED','SHIPPED','DELIVERED','CANCELLED')),
    tracking_no         VARCHAR(100),
    carrier             VARCHAR(100),
    notes               TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE shipment_lines (
    shipment_line_id    SERIAL          PRIMARY KEY,
    shipment_id         INT             NOT NULL REFERENCES shipment_headers(shipment_id) ON DELETE CASCADE,
    so_line_id          INT             REFERENCES sales_order_lines(so_line_id),
    item_id             INT             NOT NULL REFERENCES items(item_id),
    quantity            NUMERIC(14,4)   NOT NULL,
    unit                VARCHAR(20)     NOT NULL DEFAULT 'EA',
    lot_no              VARCHAR(50),
    serial_no           VARCHAR(50),
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE
);

-- ============================================================
-- 6. ACCOUNTING MODULE
-- ============================================================

-- ── AR Invoices ──
CREATE TABLE ar_invoices (
    invoice_id          SERIAL          PRIMARY KEY,
    invoice_no          VARCHAR(30)     NOT NULL UNIQUE,
    invoice_type        VARCHAR(20)     NOT NULL DEFAULT 'TAX_INVOICE'
                        CHECK (invoice_type IN ('TAX_INVOICE','RECEIPT_TAX','RECEIPT','DEBIT_NOTE','CREDIT_NOTE','PROFORMA')),
    is_copy             BOOLEAN         NOT NULL DEFAULT FALSE,
    copy_purpose        VARCHAR(50),
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    so_id               INT             REFERENCES sales_order_headers(so_id),
    shipment_id         INT             REFERENCES shipment_headers(shipment_id),
    po_reference        VARCHAR(100),
    our_quotation_id    INT             REFERENCES quotation_headers(quotation_id),
    payment_term_id     INT             REFERENCES payment_terms(term_id),
    installment_seq     INT,
    customer_id         INT             NOT NULL REFERENCES customers(customer_id),
    bill_to_name        VARCHAR(200),
    bill_to_tax_id      VARCHAR(20),
    bill_to_branch      VARCHAR(50),
    bill_to_address     TEXT,
    bill_to_phone       VARCHAR(200),
    salesperson_id      INT             REFERENCES employees(employee_id),
    invoice_date        DATE            NOT NULL DEFAULT CURRENT_DATE,
    invoice_date_be     VARCHAR(20),
    due_date            DATE            NOT NULL,
    due_date_be         VARCHAR(20),
    credit_term_text    VARCHAR(100),
    credit_days         INT             NOT NULL DEFAULT 30,
    currency_code       CHAR(3)         NOT NULL DEFAULT 'THB',
    exchange_rate       NUMERIC(18,6)   NOT NULL DEFAULT 1,
    subtotal_thb        NUMERIC(18,2)   NOT NULL DEFAULT 0,
    special_discount    NUMERIC(18,2)   NOT NULL DEFAULT 0,
    total_after_discount NUMERIC(18,2)  NOT NULL DEFAULT 0,
    vat_rate            NUMERIC(5,2)    NOT NULL DEFAULT 7.00,
    vat_amount          NUMERIC(18,2)   NOT NULL DEFAULT 0,
    grand_total_thb     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    paid_amount_thb     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    balance_thb         NUMERIC(18,2)   NOT NULL DEFAULT 0,
    payment_method      VARCHAR(20)     CHECK (payment_method IN ('CASH','BANK_TRANSFER','CHEQUE','OTHER')),
    bank_name           VARCHAR(100),
    cheque_no           VARCHAR(50),
    cheque_date         DATE,
    payment_note        TEXT,
    status              VARCHAR(20)     NOT NULL DEFAULT 'OPEN'
                        CHECK (status IN ('DRAFT','OPEN','PARTIAL','PAID','OVERDUE','CANCELLED','VOID')),
    sig_bill_collector_name  VARCHAR(100),
    sig_bill_collector_date  DATE,
    sig_goods_deliver_name   VARCHAR(100),
    sig_goods_deliver_date   DATE,
    sig_goods_receiver_name  VARCHAR(100),
    sig_goods_receiver_date  DATE,
    sig_authorized_name      VARCHAR(100),
    sig_authorized_date      DATE,
    sig_authorized_url       VARCHAR(500),
    payment_received_note    TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_reason      TEXT,
    cancel_reason       TEXT,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by          INT,
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE ar_invoice_lines (
    ar_line_id          SERIAL          PRIMARY KEY,
    invoice_id          INT             NOT NULL REFERENCES ar_invoices(invoice_id) ON DELETE CASCADE,
    line_no             SMALLINT        NOT NULL,
    item_id             INT             REFERENCES items(item_id),
    item_description    VARCHAR(500)    NOT NULL,
    quantity            NUMERIC(14,4)   NOT NULL,
    unit                VARCHAR(30)     NOT NULL DEFAULT 'EA',
    unit_price          NUMERIC(18,4)   NOT NULL,
    discount_rate       NUMERIC(5,2)    DEFAULT 0,
    ext_price           NUMERIC(18,2)   DEFAULT 0,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    UNIQUE (invoice_id, line_no)
);

-- ── AR Payments ──
CREATE TABLE ar_payments (
    payment_id          SERIAL          PRIMARY KEY,
    payment_no          VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    customer_id         INT             NOT NULL REFERENCES customers(customer_id),
    payment_date        DATE            NOT NULL DEFAULT CURRENT_DATE,
    amount_thb          NUMERIC(18,2)   NOT NULL,
    payment_method      VARCHAR(20)     NOT NULL CHECK (payment_method IN ('CASH','BANK_TRANSFER','CHEQUE','OTHER')),
    bank_name           VARCHAR(100),
    cheque_no           VARCHAR(50),
    reference_no        VARCHAR(100),
    notes               TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE ar_payment_allocations (
    allocation_id       SERIAL          PRIMARY KEY,
    payment_id          INT             NOT NULL REFERENCES ar_payments(payment_id),
    invoice_id          INT             NOT NULL REFERENCES ar_invoices(invoice_id),
    allocated_amount    NUMERIC(18,2)   NOT NULL
);

-- ── AP Invoices ──
CREATE TABLE ap_invoices (
    ap_invoice_id       SERIAL          PRIMARY KEY,
    ap_invoice_no       VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    supplier_id         INT             NOT NULL REFERENCES suppliers(supplier_id),
    po_id               INT             REFERENCES purchase_order_headers(po_id),
    supplier_invoice_no VARCHAR(100),
    invoice_date        DATE            NOT NULL DEFAULT CURRENT_DATE,
    due_date            DATE            NOT NULL,
    currency_code       CHAR(3)         NOT NULL DEFAULT 'THB',
    subtotal_thb        NUMERIC(18,2)   NOT NULL DEFAULT 0,
    vat_amount          NUMERIC(18,2)   NOT NULL DEFAULT 0,
    wht_amount          NUMERIC(18,2)   NOT NULL DEFAULT 0,
    grand_total_thb     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    paid_amount_thb     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    balance_thb         NUMERIC(18,2)   NOT NULL DEFAULT 0,
    status              VARCHAR(20)     NOT NULL DEFAULT 'OPEN'
                        CHECK (status IN ('DRAFT','OPEN','PARTIAL','PAID','OVERDUE','CANCELLED')),
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE ap_invoice_lines (
    ap_line_id          SERIAL          PRIMARY KEY,
    ap_invoice_id       INT             NOT NULL REFERENCES ap_invoices(ap_invoice_id) ON DELETE CASCADE,
    line_no             SMALLINT        NOT NULL,
    item_id             INT             REFERENCES items(item_id),
    item_description    VARCHAR(500)    NOT NULL,
    quantity            NUMERIC(14,4)   NOT NULL,
    unit                VARCHAR(30)     NOT NULL DEFAULT 'EA',
    unit_price          NUMERIC(18,4)   NOT NULL,
    ext_price           NUMERIC(18,2)   DEFAULT 0,
    account_code        VARCHAR(20),
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    UNIQUE (ap_invoice_id, line_no)
);

-- ── AP Payments ──
CREATE TABLE ap_payments (
    payment_id          SERIAL          PRIMARY KEY,
    payment_no          VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    supplier_id         INT             NOT NULL REFERENCES suppliers(supplier_id),
    payment_date        DATE            NOT NULL DEFAULT CURRENT_DATE,
    amount_thb          NUMERIC(18,2)   NOT NULL,
    payment_method      VARCHAR(20)     NOT NULL CHECK (payment_method IN ('CASH','BANK_TRANSFER','CHEQUE','OTHER')),
    wht_amount          NUMERIC(18,2)   DEFAULT 0,
    bank_name           VARCHAR(100),
    cheque_no           VARCHAR(50),
    reference_no        VARCHAR(100),
    notes               TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE ap_payment_allocations (
    allocation_id       SERIAL          PRIMARY KEY,
    payment_id          INT             NOT NULL REFERENCES ap_payments(payment_id),
    ap_invoice_id       INT             NOT NULL REFERENCES ap_invoices(ap_invoice_id),
    allocated_amount    NUMERIC(18,2)   NOT NULL
);

-- ── Journal Entries ──
CREATE TABLE journal_entries (
    je_id               SERIAL          PRIMARY KEY,
    je_no               VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    je_date             DATE            NOT NULL DEFAULT CURRENT_DATE,
    period              CHAR(7)         NOT NULL,
    je_type             VARCHAR(20)     NOT NULL DEFAULT 'MANUAL'
                        CHECK (je_type IN ('MANUAL','AUTO_AR','AUTO_AP','AUTO_PAYROLL','AUTO_EXPENSE','AUTO_INVENTORY','REVERSAL')),
    description         TEXT,
    reference_type      VARCHAR(20),
    reference_id        INT,
    total_debit         NUMERIC(18,2)   NOT NULL DEFAULT 0,
    total_credit        NUMERIC(18,2)   NOT NULL DEFAULT 0,
    status              VARCHAR(20)     NOT NULL DEFAULT 'DRAFT'
                        CHECK (status IN ('DRAFT','POSTED','REVERSED','CANCELLED')),
    posted_by           INT             REFERENCES employees(employee_id),
    posted_at           TIMESTAMPTZ,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE journal_lines (
    jl_id               SERIAL          PRIMARY KEY,
    je_id               INT             NOT NULL REFERENCES journal_entries(je_id) ON DELETE CASCADE,
    line_no             SMALLINT        NOT NULL,
    account_code        VARCHAR(20)     NOT NULL,
    description         VARCHAR(500),
    debit_amount        NUMERIC(18,2)   NOT NULL DEFAULT 0,
    credit_amount       NUMERIC(18,2)   NOT NULL DEFAULT 0,
    cost_center         VARCHAR(50),
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    UNIQUE (je_id, line_no)
);

-- ============================================================
-- 7. HR & PAYROLL MODULE
-- ============================================================

-- ── Work Schedules ──
CREATE TABLE work_schedules (
    schedule_id         SERIAL          PRIMARY KEY,
    schedule_code       VARCHAR(20)     NOT NULL UNIQUE,
    schedule_name       VARCHAR(100)    NOT NULL,
    work_start_time     TIME            NOT NULL DEFAULT '08:00',
    work_end_time       TIME            NOT NULL DEFAULT '17:00',
    break_minutes       INT             NOT NULL DEFAULT 60,
    work_days_per_week  INT             NOT NULL DEFAULT 5,
    std_hours_per_day   NUMERIC(4,2)    NOT NULL DEFAULT 8.0,
    std_hours_per_week  NUMERIC(5,2)    NOT NULL DEFAULT 40.0,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Attendance Records ──
CREATE TABLE attendance_records (
    attendance_id       SERIAL          PRIMARY KEY,
    employee_id         INT             NOT NULL REFERENCES employees(employee_id),
    attendance_date     DATE            NOT NULL,
    clock_in            TIMESTAMPTZ,
    clock_out           TIMESTAMPTZ,
    break_start         TIMESTAMPTZ,
    break_end           TIMESTAMPTZ,
    regular_hours       NUMERIC(5,2)    DEFAULT 0,
    overtime_hours      NUMERIC(5,2)    DEFAULT 0,
    holiday_hours       NUMERIC(5,2)    DEFAULT 0,
    night_hours         NUMERIC(5,2)    DEFAULT 0,
    status              VARCHAR(20)     NOT NULL DEFAULT 'PRESENT'
                        CHECK (status IN ('PRESENT','ABSENT','LATE','HALF_DAY','LEAVE','HOLIDAY','ON_DUTY_TRAVEL')),
    late_minutes        INT             DEFAULT 0,
    note                TEXT,
    clock_in_method     VARCHAR(20),
    clock_out_method    VARCHAR(20),
    approved_by         INT             REFERENCES employees(employee_id),
    approved_at         TIMESTAMPTZ,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (employee_id, attendance_date)
);

-- ── Leave Requests ──
CREATE TABLE leave_requests (
    leave_id            SERIAL          PRIMARY KEY,
    employee_id         INT             NOT NULL REFERENCES employees(employee_id),
    leave_type          VARCHAR(20)     NOT NULL
                        CHECK (leave_type IN ('ANNUAL','SICK','PERSONAL','MATERNITY','PATERNITY','ORDINATION','MILITARY','COMPASSIONATE','UNPAID')),
    start_date          DATE            NOT NULL,
    end_date            DATE            NOT NULL,
    days_requested      NUMERIC(4,1)    NOT NULL,
    half_day            BOOLEAN         NOT NULL DEFAULT FALSE,
    half_day_period     VARCHAR(5),
    reason              TEXT,
    attachment_url      VARCHAR(500),
    status              VARCHAR(20)     NOT NULL DEFAULT 'PENDING'
                        CHECK (status IN ('PENDING','APPROVED','REJECTED','CANCELLED')),
    approved_by         INT             REFERENCES employees(employee_id),
    approved_at         TIMESTAMPTZ,
    reject_reason       TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Public Holidays ──
CREATE TABLE public_holidays (
    holiday_id          SERIAL          PRIMARY KEY,
    holiday_date        DATE            NOT NULL,
    holiday_name        VARCHAR(100)    NOT NULL,
    holiday_name_jp     VARCHAR(100),
    holiday_type        VARCHAR(20)     NOT NULL
                        CHECK (holiday_type IN ('NATIONAL','BANK','COMPANY')),
    fiscal_year         SMALLINT        NOT NULL,
    division_id         INT             REFERENCES divisions(division_id),
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE
);

-- ── Payroll ──
CREATE TABLE payroll_headers (
    payroll_id          SERIAL          PRIMARY KEY,
    payroll_no          VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    period              CHAR(7)         NOT NULL,
    pay_date            DATE            NOT NULL,
    status              VARCHAR(20)     NOT NULL DEFAULT 'DRAFT'
                        CHECK (status IN ('DRAFT','CALCULATED','APPROVED','PAID','LOCKED')),
    total_gross_thb     NUMERIC(18,2)   NOT NULL DEFAULT 0,
    total_deduction_thb NUMERIC(18,2)   NOT NULL DEFAULT 0,
    total_net_thb       NUMERIC(18,2)   NOT NULL DEFAULT 0,
    total_tax_thb       NUMERIC(18,2)   NOT NULL DEFAULT 0,
    total_sso_emp_thb   NUMERIC(18,2)   NOT NULL DEFAULT 0,
    total_sso_er_thb    NUMERIC(18,2)   NOT NULL DEFAULT 0,
    approved_by         INT             REFERENCES employees(employee_id),
    approved_at         TIMESTAMPTZ,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE payroll_lines (
    payroll_line_id     SERIAL          PRIMARY KEY,
    payroll_id          INT             NOT NULL REFERENCES payroll_headers(payroll_id),
    employee_id         INT             NOT NULL REFERENCES employees(employee_id),
    base_salary         NUMERIC(14,2)   NOT NULL DEFAULT 0,
    overtime_pay        NUMERIC(14,2)   NOT NULL DEFAULT 0,
    holiday_pay         NUMERIC(14,2)   NOT NULL DEFAULT 0,
    night_differential  NUMERIC(14,2)   NOT NULL DEFAULT 0,
    position_allowance  NUMERIC(14,2)   NOT NULL DEFAULT 0,
    transport_allowance NUMERIC(14,2)   NOT NULL DEFAULT 0,
    meal_allowance      NUMERIC(14,2)   NOT NULL DEFAULT 0,
    housing_allowance   NUMERIC(14,2)   NOT NULL DEFAULT 0,
    other_allowance     NUMERIC(14,2)   NOT NULL DEFAULT 0,
    bonus               NUMERIC(14,2)   NOT NULL DEFAULT 0,
    gross_pay           NUMERIC(14,2)   NOT NULL DEFAULT 0,
    sso_employee        NUMERIC(14,2)   NOT NULL DEFAULT 0,
    income_tax_withhold NUMERIC(14,2)   NOT NULL DEFAULT 0,
    provident_fund_emp  NUMERIC(14,2)   NOT NULL DEFAULT 0,
    late_deduction      NUMERIC(14,2)   NOT NULL DEFAULT 0,
    advance_deduction   NUMERIC(14,2)   NOT NULL DEFAULT 0,
    other_deduction     NUMERIC(14,2)   NOT NULL DEFAULT 0,
    total_deduction     NUMERIC(14,2)   NOT NULL DEFAULT 0,
    net_pay             NUMERIC(14,2)   NOT NULL DEFAULT 0,
    sso_employer        NUMERIC(14,2)   NOT NULL DEFAULT 0,
    provident_fund_er   NUMERIC(14,2)   NOT NULL DEFAULT 0,
    wcf_contribution    NUMERIC(14,2)   NOT NULL DEFAULT 0,
    work_days           NUMERIC(4,1)    NOT NULL DEFAULT 0,
    regular_hours       NUMERIC(6,2)    NOT NULL DEFAULT 0,
    overtime_hours      NUMERIC(6,2)    NOT NULL DEFAULT 0,
    holiday_hours       NUMERIC(6,2)    NOT NULL DEFAULT 0,
    leave_days_annual   NUMERIC(4,1)    NOT NULL DEFAULT 0,
    leave_days_sick     NUMERIC(4,1)    NOT NULL DEFAULT 0,
    absence_days        NUMERIC(4,1)    NOT NULL DEFAULT 0,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    UNIQUE (payroll_id, employee_id)
);

-- ── PIT Tax Brackets ──
CREATE TABLE pit_tax_brackets (
    bracket_id          SERIAL          PRIMARY KEY,
    fiscal_year         SMALLINT        NOT NULL,
    income_from         NUMERIC(14,2)   NOT NULL,
    income_to           NUMERIC(14,2),
    tax_rate            NUMERIC(5,2)    NOT NULL,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE
);

-- ============================================================
-- 8. EXPENSE MODULE
-- ============================================================

CREATE TABLE expense_claims (
    claim_id            SERIAL          PRIMARY KEY,
    claim_no            VARCHAR(30)     NOT NULL UNIQUE,
    employee_id         INT             NOT NULL REFERENCES employees(employee_id),
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    claim_date          DATE            NOT NULL DEFAULT CURRENT_DATE,
    period              CHAR(7)         NOT NULL,
    title               VARCHAR(200)    NOT NULL,
    purpose             TEXT,
    total_amount_thb    NUMERIC(14,2)   NOT NULL DEFAULT 0,
    status              VARCHAR(20)     NOT NULL DEFAULT 'DRAFT'
                        CHECK (status IN ('DRAFT','SUBMITTED','APPROVED','REJECTED','PAID','CANCELLED')),
    submitted_at        TIMESTAMPTZ,
    approved_by         INT             REFERENCES employees(employee_id),
    approved_at         TIMESTAMPTZ,
    paid_at             TIMESTAMPTZ,
    payment_method      VARCHAR(20),
    reject_reason       TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE expense_claim_lines (
    line_id             SERIAL          PRIMARY KEY,
    claim_id            INT             NOT NULL REFERENCES expense_claims(claim_id) ON DELETE CASCADE,
    line_no             SMALLINT        NOT NULL,
    expense_date        DATE            NOT NULL,
    expense_category    VARCHAR(30)     NOT NULL
                        CHECK (expense_category IN ('TRANSPORT_MILEAGE','TRANSPORT_PUBLIC','TRANSPORT_TAXI','ACCOMMODATION','MEAL','ENTERTAINMENT','COMMUNICATION','STATIONERY','POSTAGE','REGISTRATION','OTHER')),
    description         TEXT            NOT NULL,
    account_code        VARCHAR(20),
    amount_thb          NUMERIC(14,2)   NOT NULL DEFAULT 0,
    vat_amount          NUMERIC(14,2)   NOT NULL DEFAULT 0,
    wht_amount          NUMERIC(14,2)   NOT NULL DEFAULT 0,
    receipt_url         VARCHAR(500),
    is_mileage_claim    BOOLEAN         NOT NULL DEFAULT FALSE,
    origin_address      TEXT,
    destination_address TEXT,
    waypoints           TEXT,
    distance_km         NUMERIC(8,2),
    rate_per_km         NUMERIC(6,2)    DEFAULT 5.00,
    calculated_amount   NUMERIC(14,2)   DEFAULT 0,
    gmaps_response      JSONB,
    UNIQUE (claim_id, line_no)
);

CREATE TABLE expense_account_mapping (
    mapping_id          SERIAL          PRIMARY KEY,
    expense_category    VARCHAR(30)     NOT NULL,
    account_code        VARCHAR(20)     NOT NULL,
    division_id         INT             REFERENCES divisions(division_id),
    effective_from      DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to        DATE,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE
);

-- ============================================================
-- 9. PRODUCTION / MRP MODULE
-- ============================================================

-- ── BOM ──
CREATE TABLE bom_headers (
    bom_id              SERIAL          PRIMARY KEY,
    item_id             INT             NOT NULL REFERENCES items(item_id),
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    bom_code            VARCHAR(30)     NOT NULL,
    bom_name            VARCHAR(200),
    revision            SMALLINT        NOT NULL DEFAULT 1,
    yield_qty           NUMERIC(14,4)   NOT NULL DEFAULT 1,
    effective_from      DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to        DATE,
    is_current          BOOLEAN         NOT NULL DEFAULT TRUE,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (bom_code, division_id, revision)
);

CREATE TABLE bom_lines (
    bom_line_id         SERIAL          PRIMARY KEY,
    bom_id              INT             NOT NULL REFERENCES bom_headers(bom_id) ON DELETE CASCADE,
    line_no             SMALLINT        NOT NULL,
    component_item_id   INT             NOT NULL REFERENCES items(item_id),
    quantity_per        NUMERIC(14,6)   NOT NULL,
    unit                VARCHAR(20)     NOT NULL DEFAULT 'EA',
    scrap_rate          NUMERIC(5,4)    DEFAULT 0,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    UNIQUE (bom_id, line_no)
);

-- ── Manufacturing Orders ──
CREATE TABLE mo_headers (
    mo_id               SERIAL          PRIMARY KEY,
    mo_no               VARCHAR(30)     NOT NULL UNIQUE,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    item_id             INT             NOT NULL REFERENCES items(item_id),
    bom_id              INT             REFERENCES bom_headers(bom_id),
    so_id               INT             REFERENCES sales_order_headers(so_id),
    planned_qty         NUMERIC(14,4)   NOT NULL,
    completed_qty       NUMERIC(14,4)   NOT NULL DEFAULT 0,
    unit                VARCHAR(20)     NOT NULL DEFAULT 'EA',
    planned_start       DATE            NOT NULL,
    planned_end         DATE,
    actual_start        DATE,
    actual_end          DATE,
    status              VARCHAR(20)     NOT NULL DEFAULT 'PLANNED'
                        CHECK (status IN ('PLANNED','RELEASED','IN_PROGRESS','COMPLETED','CLOSED','CANCELLED')),
    notes               TEXT,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE mo_lines (
    mo_line_id          SERIAL          PRIMARY KEY,
    mo_id               INT             NOT NULL REFERENCES mo_headers(mo_id) ON DELETE CASCADE,
    component_item_id   INT             NOT NULL REFERENCES items(item_id),
    required_qty        NUMERIC(14,4)   NOT NULL,
    issued_qty          NUMERIC(14,4)   NOT NULL DEFAULT 0,
    unit                VARCHAR(20)     NOT NULL DEFAULT 'EA',
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE
);

-- ── MRP ──
CREATE TABLE mrp_snapshots (
    snapshot_id         SERIAL          PRIMARY KEY,
    snapshot_date       DATE            NOT NULL DEFAULT CURRENT_DATE,
    period_from         DATE            NOT NULL,
    period_to           DATE            NOT NULL,
    division_id         INT             NOT NULL REFERENCES divisions(division_id),
    status              VARCHAR(20)     NOT NULL DEFAULT 'CALCULATING'
                        CHECK (status IN ('CALCULATING','COMPLETED','FAILED')),
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE mrp_items (
    mrp_item_id         SERIAL          PRIMARY KEY,
    snapshot_id         INT             NOT NULL REFERENCES mrp_snapshots(snapshot_id),
    item_id             INT             NOT NULL REFERENCES items(item_id),
    item_code           VARCHAR(50)     NOT NULL,
    bom_level           SMALLINT        NOT NULL DEFAULT 0,
    bom_ratio           NUMERIC(12,6)   NOT NULL DEFAULT 1,
    production_leadtime INT             NOT NULL DEFAULT 0,
    rm_leadtime         INT             NOT NULL DEFAULT 0,
    order_point         NUMERIC(14,4)   NOT NULL DEFAULT 0,
    stock_base_date     NUMERIC(14,4)   NOT NULL DEFAULT 0,
    is_checked          BOOLEAN         NOT NULL DEFAULT FALSE,
    has_action          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE TABLE mrp_daily_quantities (
    daily_qty_id        SERIAL          PRIMARY KEY,
    mrp_item_id         INT             NOT NULL REFERENCES mrp_items(mrp_item_id),
    calc_date           DATE            NOT NULL,
    order_forecast_qty  NUMERIC(14,4)   NOT NULL DEFAULT 0,
    order_point_qty     NUMERIC(14,4)   NOT NULL DEFAULT 0,
    current_stock_qty   NUMERIC(14,4)   NOT NULL DEFAULT 0,
    inbound_schedule_qty NUMERIC(14,4)  NOT NULL DEFAULT 0,
    stock_incl_expected NUMERIC(14,4)   NOT NULL DEFAULT 0,
    diff_stock_orderpt  NUMERIC(14,4)   NOT NULL DEFAULT 0,
    required_qty        NUMERIC(14,4)   NOT NULL DEFAULT 0,
    order_qty           NUMERIC(14,4)   NOT NULL DEFAULT 0,
    po_issued_qty       NUMERIC(14,4)   NOT NULL DEFAULT 0,
    UNIQUE (mrp_item_id, calc_date)
);

CREATE TABLE mrp_purchase_recommendations (
    recommendation_id   SERIAL          PRIMARY KEY,
    snapshot_id         INT             NOT NULL REFERENCES mrp_snapshots(snapshot_id),
    item_id             INT             NOT NULL REFERENCES items(item_id),
    supplier_id         INT             REFERENCES suppliers(supplier_id),
    recommended_date    DATE            NOT NULL,
    required_date       DATE            NOT NULL,
    recommended_qty     NUMERIC(14,4)   NOT NULL,
    unit                VARCHAR(20)     NOT NULL,
    unit_price          NUMERIC(18,4),
    estimated_amount    NUMERIC(18,2),
    bom_level           SMALLINT        NOT NULL DEFAULT 0,
    source_so_id        INT             REFERENCES sales_order_headers(so_id),
    source_mo_id        INT             REFERENCES mo_headers(mo_id),
    action_status       VARCHAR(20)     NOT NULL DEFAULT 'PENDING'
                        CHECK (action_status IN ('PENDING','APPROVED','PO_ISSUED','IGNORED')),
    po_id               INT             REFERENCES purchase_order_headers(po_id),
    notes               TEXT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ── Standard Costs ──
CREATE TABLE standard_costs (
    std_cost_id         SERIAL          PRIMARY KEY,
    item_id             INT             NOT NULL REFERENCES items(item_id),
    division_id         INT             REFERENCES divisions(division_id),
    material_cost       NUMERIC(18,4)   NOT NULL DEFAULT 0,
    labor_cost          NUMERIC(18,4)   NOT NULL DEFAULT 0,
    overhead_cost       NUMERIC(18,4)   NOT NULL DEFAULT 0,
    total_std_cost      NUMERIC(18,4)   NOT NULL DEFAULT 0,
    effective_from      DATE            NOT NULL DEFAULT CURRENT_DATE,
    effective_to        DATE,
    is_current          BOOLEAN         NOT NULL DEFAULT TRUE,
    is_deleted          BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- ============================================================
-- 10. AUDIT LOG
-- ============================================================

CREATE TABLE audit_logs (
    log_id          BIGSERIAL       PRIMARY KEY,
    table_name      VARCHAR(100)    NOT NULL,
    record_id       BIGINT          NOT NULL,
    operation       CHAR(1)         NOT NULL CHECK (operation IN ('I','U','D')),
    changed_fields  JSONB,
    old_values      JSONB,
    new_values      JSONB,
    changed_by      INT,
    changed_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    ip_address      INET,
    session_id      VARCHAR(100),
    reason          TEXT
);
CREATE INDEX idx_audit_table_record ON audit_logs(table_name, record_id);
CREATE INDEX idx_audit_changed_at   ON audit_logs(changed_at);

-- ── Audit Trigger Function ──
CREATE OR REPLACE FUNCTION fn_audit_trigger()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
DECLARE
    v_old JSONB := NULL;
    v_new JSONB := NULL;
    v_changed JSONB := '{}';
    v_key TEXT;
    v_record_id BIGINT;
BEGIN
    IF TG_OP = 'DELETE' THEN
        v_old := to_jsonb(OLD);
        v_record_id := COALESCE(
            (v_old->>TG_ARGV[0])::BIGINT,
            (v_old->>'id')::BIGINT,
            0
        );
        INSERT INTO audit_logs(table_name, record_id, operation, old_values, changed_at)
        VALUES (TG_TABLE_NAME, v_record_id, 'D', v_old, NOW());
        RETURN OLD;
    END IF;

    v_new := to_jsonb(NEW);
    v_record_id := COALESCE(
        (v_new->>TG_ARGV[0])::BIGINT,
        (v_new->>'id')::BIGINT,
        0
    );

    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit_logs(table_name, record_id, operation, new_values, changed_at)
        VALUES (TG_TABLE_NAME, v_record_id, 'I', v_new, NOW());
    ELSIF TG_OP = 'UPDATE' THEN
        v_old := to_jsonb(OLD);
        FOR v_key IN SELECT key FROM jsonb_each(v_new) LOOP
            IF v_old->v_key IS DISTINCT FROM v_new->v_key THEN
                v_changed := v_changed || jsonb_build_object(v_key,
                    jsonb_build_object('old', v_old->v_key, 'new', v_new->v_key));
            END IF;
        END LOOP;
        IF v_changed != '{}' THEN
            INSERT INTO audit_logs(table_name, record_id, operation, old_values, new_values, changed_fields, changed_at)
            VALUES (TG_TABLE_NAME, v_record_id, 'U', v_old, v_new, v_changed, NOW());
        END IF;
    END IF;
    RETURN NEW;
END; $$;

-- Apply audit triggers
CREATE TRIGGER trg_audit_divisions AFTER INSERT OR UPDATE OR DELETE ON divisions FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('division_id');
CREATE TRIGGER trg_audit_employees AFTER INSERT OR UPDATE OR DELETE ON employees FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('employee_id');
CREATE TRIGGER trg_audit_items AFTER INSERT OR UPDATE OR DELETE ON items FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('item_id');
CREATE TRIGGER trg_audit_customers AFTER INSERT OR UPDATE OR DELETE ON customers FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('customer_id');
CREATE TRIGGER trg_audit_suppliers AFTER INSERT OR UPDATE OR DELETE ON suppliers FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('supplier_id');
CREATE TRIGGER trg_audit_accounts AFTER INSERT OR UPDATE OR DELETE ON accounts FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('account_id');
CREATE TRIGGER trg_audit_quotations AFTER INSERT OR UPDATE OR DELETE ON quotation_headers FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('quotation_id');
CREATE TRIGGER trg_audit_so AFTER INSERT OR UPDATE OR DELETE ON sales_order_headers FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('so_id');
CREATE TRIGGER trg_audit_po AFTER INSERT OR UPDATE OR DELETE ON purchase_order_headers FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('po_id');
CREATE TRIGGER trg_audit_ar_invoices AFTER INSERT OR UPDATE OR DELETE ON ar_invoices FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('invoice_id');
CREATE TRIGGER trg_audit_journal AFTER INSERT OR UPDATE OR DELETE ON journal_entries FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('je_id');
CREATE TRIGGER trg_audit_payroll AFTER INSERT OR UPDATE OR DELETE ON payroll_headers FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('payroll_id');
CREATE TRIGGER trg_audit_expense AFTER INSERT OR UPDATE OR DELETE ON expense_claims FOR EACH ROW EXECUTE FUNCTION fn_audit_trigger('claim_id');

-- ============================================================
-- 11. VIEWS
-- ============================================================

CREATE OR REPLACE VIEW v_items_current AS
SELECT * FROM items
WHERE is_current = TRUE AND is_deleted = FALSE
  AND effective_from <= CURRENT_DATE
  AND (effective_to IS NULL OR effective_to >= CURRENT_DATE);

CREATE OR REPLACE VIEW v_customers_current AS
SELECT * FROM customers
WHERE is_current = TRUE AND is_deleted = FALSE
  AND effective_from <= CURRENT_DATE
  AND (effective_to IS NULL OR effective_to >= CURRENT_DATE);

CREATE OR REPLACE VIEW v_suppliers_current AS
SELECT * FROM suppliers
WHERE is_current = TRUE AND is_deleted = FALSE
  AND effective_from <= CURRENT_DATE
  AND (effective_to IS NULL OR effective_to >= CURRENT_DATE);

CREATE OR REPLACE VIEW v_accounts_current AS
SELECT * FROM accounts
WHERE is_current = TRUE AND is_deleted = FALSE
  AND effective_from <= CURRENT_DATE
  AND (effective_to IS NULL OR effective_to >= CURRENT_DATE);

-- ── Number sequences ──
CREATE TABLE number_sequences (
    seq_name        VARCHAR(30)     PRIMARY KEY,
    prefix          VARCHAR(20)     NOT NULL,
    current_no      INT             NOT NULL DEFAULT 0,
    fiscal_year     SMALLINT,
    fiscal_month    SMALLINT,
    format_pattern  VARCHAR(50)     NOT NULL DEFAULT '{PREFIX}-{YYYY}-{NNNN}'
);
