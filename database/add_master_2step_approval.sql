-- ============================================================
-- PEGASUS ERP — Customer/Supplier master 2-step approval upgrade
--   DRAFT → PENDING_MANAGER → PENDING_CEO → APPROVED
--   or REJECTED at either step
-- Plus supplier_attachments for credit/business docs.
-- ============================================================

BEGIN;

-- ---- 1) Customers: add approval-trail columns ----
ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS submitted_by         INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS submitted_at         TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS manager_approved_by  INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS manager_approved_at  TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS ceo_approved_by      INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS ceo_approved_at      TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS rejected_by          INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS rejected_at          TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS rejection_reason     TEXT;

-- ---- 2) Suppliers: same columns + attachments ----
ALTER TABLE suppliers
    ADD COLUMN IF NOT EXISTS submitted_by         INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS submitted_at         TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS manager_approved_by  INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS manager_approved_at  TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS ceo_approved_by      INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS ceo_approved_at      TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS rejected_by          INTEGER REFERENCES users(user_id),
    ADD COLUMN IF NOT EXISTS rejected_at          TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS rejection_reason     TEXT;

-- ---- 3) Expand approval_status CHECK to include the new states.
--      (the original check is auto-generated; we use a NOT-IN guard via trigger-style)
-- Drop any existing approval_status check, then re-create.
DO $$
DECLARE r record;
BEGIN
    FOR r IN
        SELECT conname, conrelid::regclass AS tbl
        FROM pg_constraint
        WHERE conrelid IN ('customers'::regclass, 'suppliers'::regclass)
          AND pg_get_constraintdef(oid) ILIKE '%approval_status%'
    LOOP
        EXECUTE format('ALTER TABLE %s DROP CONSTRAINT %I', r.tbl, r.conname);
    END LOOP;
END $$;

ALTER TABLE customers ADD CONSTRAINT customers_approval_status_check
    CHECK (approval_status IN ('DRAFT','PENDING','PENDING_MANAGER','PENDING_CEO','APPROVED','REJECTED'));
ALTER TABLE suppliers ADD CONSTRAINT suppliers_approval_status_check
    CHECK (approval_status IN ('DRAFT','PENDING','PENDING_MANAGER','PENDING_CEO','APPROVED','REJECTED'));

-- ---- 4) Supplier attachments (credit / commercial registration docs) ----
CREATE TABLE IF NOT EXISTS supplier_attachments (
    attachment_id   SERIAL PRIMARY KEY,
    supplier_id     INTEGER NOT NULL REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    doc_type        VARCHAR(40) NOT NULL DEFAULT 'OTHER',
    -- doc_type: COMMERCIAL_REGISTRATION / TAX_CERTIFICATE / BANK_BOOK /
    --           CREDIT_REPORT / FINANCIAL_STATEMENT / NDA / OTHER
    file_name       VARCHAR(255) NOT NULL,
    stored_path     VARCHAR(500) NOT NULL,
    file_size       INTEGER       NOT NULL,
    mime_type       VARCHAR(120),
    description     VARCHAR(255),
    uploaded_by     INTEGER REFERENCES users(user_id),
    uploaded_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    is_deleted      BOOLEAN NOT NULL DEFAULT FALSE
);
CREATE INDEX IF NOT EXISTS idx_sup_attach_sup ON supplier_attachments(supplier_id) WHERE is_deleted = FALSE;

GRANT SELECT ON supplier_attachments TO pegasus_mcp_ro;

COMMIT;
