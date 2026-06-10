-- ============================================================
-- PEGASUS ERP — PR 3-supplier comparison + CEO approval upgrade
--
-- New PR flow:
--   DRAFT → SUBMITTED → QUOTES_PENDING (purchasing collects 3 quotes)
--   → PENDING_MANAGER (purchasing mgr) → PENDING_CEO → APPROVED
--   → CONVERTED (PO raised)
--   any active → REJECTED / CANCELLED
--
-- New PO flow:
--   DRAFT → PENDING_MANAGER → PENDING_CEO → APPROVED → SENT/RECEIVED/CLOSED
-- ============================================================

BEGIN;

-- 1) Replace PR status check to include new states
ALTER TABLE purchase_requests DROP CONSTRAINT IF EXISTS purchase_requests_status_check;
ALTER TABLE purchase_requests ADD CONSTRAINT purchase_requests_status_check
    CHECK (status IN (
        'DRAFT','SUBMITTED',
        'QUOTES_PENDING',
        'PENDING_PURCHASING',  -- legacy, still allowed for backward-compat
        'PENDING_MANAGER','PENDING_CEO',
        'APPROVED','REJECTED','CONVERTED','CANCELLED'
    ));

-- 2) CEO approval tracking columns
ALTER TABLE purchase_requests
    ADD COLUMN IF NOT EXISTS ceo_approved_by INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS ceo_approved_at TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS ceo_note        TEXT;

-- 3) Quote headers — 3 suppliers per PR
CREATE TABLE IF NOT EXISTS purchase_request_quotes (
    quote_id          SERIAL PRIMARY KEY,
    pr_id             INTEGER NOT NULL REFERENCES purchase_requests(pr_id) ON DELETE CASCADE,
    position          SMALLINT NOT NULL,        -- 1 / 2 / 3
    supplier_id       INTEGER REFERENCES suppliers(supplier_id),
    supplier_name_text VARCHAR(200),            -- free-text when supplier not yet in master
    quote_no          VARCHAR(100),
    quote_date        DATE,
    currency_code     CHAR(3) NOT NULL DEFAULT 'THB',
    total_amount_thb  NUMERIC(18,2) NOT NULL DEFAULT 0,
    payment_terms     VARCHAR(120),
    lead_time_days    INTEGER,
    notes             TEXT,
    attachment_id     INTEGER REFERENCES purchase_request_attachments(attachment_id),
    is_overall_winner BOOLEAN NOT NULL DEFAULT FALSE,
    created_by        INTEGER REFERENCES users(user_id),
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    is_deleted        BOOLEAN NOT NULL DEFAULT FALSE,
    UNIQUE (pr_id, position)
);

CREATE INDEX IF NOT EXISTS idx_prq_pr ON purchase_request_quotes(pr_id) WHERE is_deleted = FALSE;

-- 4) Per-line price per quote (for line-level comparison & winner)
CREATE TABLE IF NOT EXISTS purchase_request_quote_lines (
    quote_line_id   SERIAL PRIMARY KEY,
    quote_id        INTEGER NOT NULL REFERENCES purchase_request_quotes(quote_id) ON DELETE CASCADE,
    pr_line_id      INTEGER NOT NULL REFERENCES purchase_request_lines(pr_line_id) ON DELETE CASCADE,
    unit_price      NUMERIC(18,4) NOT NULL DEFAULT 0,
    line_total      NUMERIC(18,2) NOT NULL DEFAULT 0,
    lead_time_days  INTEGER,
    is_winner       BOOLEAN NOT NULL DEFAULT FALSE,   -- selected as cheapest/best per line
    remark          VARCHAR(255),
    UNIQUE (quote_id, pr_line_id)
);

CREATE INDEX IF NOT EXISTS idx_prql_pr_line ON purchase_request_quote_lines(pr_line_id);

-- 5) Patch PO status check to add PENDING_MANAGER / PENDING_CEO
ALTER TABLE purchase_order_headers DROP CONSTRAINT IF EXISTS purchase_order_headers_status_check;
ALTER TABLE purchase_order_headers ADD CONSTRAINT purchase_order_headers_status_check
    CHECK (status IN (
        'DRAFT','PENDING','PENDING_APPROVAL',
        'PENDING_MANAGER','PENDING_CEO',
        'APPROVED','REJECTED','SENT','RECEIVED',
        'PARTIAL_RECEIVED','FULLY_RECEIVED','CLOSED','CANCELLED'
    ));

-- 6) CEO approval columns on PO
ALTER TABLE purchase_order_headers
    ADD COLUMN IF NOT EXISTS manager_approved_by INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS manager_approved_at TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS ceo_approved_by     INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS ceo_approved_at     TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS rejection_reason    TEXT;

-- 7) RO grants
GRANT SELECT ON purchase_request_quotes, purchase_request_quote_lines TO pegasus_mcp_ro;

COMMIT;
