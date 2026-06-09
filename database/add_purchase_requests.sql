-- =====================================================================
-- PEGASUS ERP — Purchase Request (PR) workflow
-- Flow: Requester (any user) DRAFT → SUBMITTED
--   → PENDING_PURCHASING (purchasing officer approves)
--   → PENDING_MANAGER (ADMIN/MANAGER final approval)
--   → APPROVED → may be CONVERTED to PO
--   or REJECTED at any step.
-- =====================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS purchase_requests (
    pr_id                   SERIAL PRIMARY KEY,
    pr_no                   VARCHAR(50)  NOT NULL UNIQUE,
    requester_id            INTEGER      NOT NULL REFERENCES employees(employee_id),
    department              VARCHAR(100),
    request_date            DATE         NOT NULL DEFAULT CURRENT_DATE,
    needed_by_date          DATE,
    justification           TEXT,
    suggested_supplier_id   INTEGER REFERENCES suppliers(supplier_id),
    currency_code           CHAR(3)      NOT NULL DEFAULT 'THB',
    est_total_thb           NUMERIC(18,2) NOT NULL DEFAULT 0,

    -- Workflow status
    status                  VARCHAR(30)  NOT NULL DEFAULT 'DRAFT',
    -- DRAFT / SUBMITTED / PENDING_PURCHASING / PENDING_MANAGER /
    -- APPROVED / REJECTED / CONVERTED / CANCELLED

    -- Step 1: purchasing officer
    purchasing_approved_by  INTEGER REFERENCES users(user_id),
    purchasing_approved_at  TIMESTAMPTZ,
    purchasing_note         TEXT,

    -- Step 2: manager / admin
    manager_approved_by     INTEGER REFERENCES users(user_id),
    manager_approved_at     TIMESTAMPTZ,
    manager_note            TEXT,

    -- Rejection
    rejected_by             INTEGER REFERENCES users(user_id),
    rejected_at             TIMESTAMPTZ,
    rejection_reason        TEXT,

    -- Conversion link
    converted_po_id         INTEGER REFERENCES purchase_order_headers(po_id),
    converted_at            TIMESTAMPTZ,

    notes                   TEXT,
    is_deleted              BOOLEAN      NOT NULL DEFAULT FALSE,
    created_by              INTEGER REFERENCES users(user_id),
    created_at              TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ  NOT NULL DEFAULT NOW(),

    CONSTRAINT purchase_requests_status_check CHECK (
        status IN ('DRAFT','SUBMITTED','PENDING_PURCHASING','PENDING_MANAGER',
                   'APPROVED','REJECTED','CONVERTED','CANCELLED')
    )
);

CREATE INDEX IF NOT EXISTS idx_pr_status         ON purchase_requests(status) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_pr_requester      ON purchase_requests(requester_id);
CREATE INDEX IF NOT EXISTS idx_pr_request_date   ON purchase_requests(request_date DESC);

CREATE TABLE IF NOT EXISTS purchase_request_lines (
    pr_line_id       SERIAL PRIMARY KEY,
    pr_id            INTEGER NOT NULL REFERENCES purchase_requests(pr_id) ON DELETE CASCADE,
    line_no          INTEGER NOT NULL,
    item_code        VARCHAR(50),
    item_description TEXT NOT NULL,
    quantity         NUMERIC(18,3) NOT NULL DEFAULT 1,
    unit             VARCHAR(20)   NOT NULL DEFAULT 'PCS',
    est_unit_price   NUMERIC(18,4) NOT NULL DEFAULT 0,
    est_line_total   NUMERIC(18,2) NOT NULL DEFAULT 0,
    suggested_supplier_id INTEGER REFERENCES suppliers(supplier_id),
    needed_by_date   DATE,
    remark           TEXT,
    is_deleted       BOOLEAN NOT NULL DEFAULT FALSE,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (pr_id, line_no)
);

-- Link PO to its source PR (nullable — direct POs still allowed)
ALTER TABLE purchase_order_headers
    ADD COLUMN IF NOT EXISTS pr_id INTEGER REFERENCES purchase_requests(pr_id);

CREATE INDEX IF NOT EXISTS idx_po_pr_id ON purchase_order_headers(pr_id) WHERE pr_id IS NOT NULL;

COMMIT;
