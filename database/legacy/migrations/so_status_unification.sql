-- ============================================================
-- #12 Sales Order status unification: only 3 statuses
--   CONFIRMED  = 受注済 (registered)
--   INVOICED   = 請求書発行済
--   CANCELLED  = キャンセル
-- Drop & recreate CHECK constraint
-- ============================================================

BEGIN;

-- Map any legacy statuses to the 3 canonical values
UPDATE sales_order_headers SET status = 'CONFIRMED'
    WHERE status IN ('DRAFT','PENDING','APPROVED','SHIPPED','PARTIAL','OPEN');

-- Drop existing check constraint (if any)
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT conname FROM pg_constraint
        WHERE conrelid = 'sales_order_headers'::regclass
          AND contype = 'c'
          AND conname ILIKE '%status%'
    LOOP
        EXECUTE 'ALTER TABLE sales_order_headers DROP CONSTRAINT ' || quote_ident(r.conname);
    END LOOP;
END $$;

-- Re-add narrowed constraint
ALTER TABLE sales_order_headers
    ADD CONSTRAINT sales_order_headers_status_check
    CHECK (status IN ('CONFIRMED','INVOICED','CANCELLED'));

-- Verify
SELECT status, COUNT(*) FROM sales_order_headers GROUP BY status ORDER BY status;

COMMIT;
