-- ============================================================
-- PO × Project linkage + narrowed status (APPROVED / PENDING / CANCELLED)
-- ============================================================

BEGIN;

-- 1) Link column
ALTER TABLE purchase_order_headers
    ADD COLUMN IF NOT EXISTS project_id INT REFERENCES projects(project_id);

CREATE INDEX IF NOT EXISTS idx_po_project
    ON purchase_order_headers(project_id)
    WHERE is_deleted = FALSE;

-- 2) Map legacy statuses to the 3 canonical values
UPDATE purchase_order_headers
SET status = CASE
    WHEN status IN ('DRAFT','PENDING_APPROVAL') THEN 'PENDING'
    WHEN status = 'CANCELLED'                   THEN 'CANCELLED'
    ELSE 'APPROVED'  -- APPROVED/SENT/PARTIAL_RECEIVED/FULLY_RECEIVED/CLOSED all become APPROVED
END;

-- 3) Drop old CHECK, install narrower one
DO $$
DECLARE r RECORD;
BEGIN
    FOR r IN
        SELECT conname FROM pg_constraint
        WHERE conrelid = 'purchase_order_headers'::regclass
          AND contype = 'c' AND conname ILIKE '%status%'
    LOOP
        EXECUTE 'ALTER TABLE purchase_order_headers DROP CONSTRAINT ' || quote_ident(r.conname);
    END LOOP;
END $$;

ALTER TABLE purchase_order_headers
    ADD CONSTRAINT purchase_order_headers_status_check
    CHECK (status IN ('APPROVED','PENDING','CANCELLED'));

-- Verify
SELECT status, COUNT(*) FROM purchase_order_headers
WHERE is_deleted = FALSE GROUP BY status ORDER BY status;

COMMIT;
