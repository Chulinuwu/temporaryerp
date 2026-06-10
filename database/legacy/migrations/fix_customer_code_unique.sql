-- ============================================================
-- Fix duplicate customer_code & enforce uniqueness
--   - Renumber TOKAI RIKA (currently CUS-0001 conflict) to next CUS- number
--   - Add unique partial index so future duplicates are blocked at DB level
-- ============================================================

BEGIN;

-- 1) Resolve existing duplicate (TOKAI RIKA → next free number)
DO $$
DECLARE
    v_next INT;
    v_code TEXT;
BEGIN
    SELECT COALESCE(MAX(CAST(SUBSTRING(customer_code FROM 5) AS INT)), 0) + 1 INTO v_next
    FROM customers
    WHERE customer_code ~ '^CUS-[0-9]+$';

    v_code := 'CUS-' || LPAD(v_next::text, 4, '0');
    RAISE NOTICE 'Renumbering TOKAI RIKA to %', v_code;

    UPDATE customers
    SET customer_code = v_code
    WHERE customer_id = 499 AND customer_name ILIKE 'TOKAI RIKA%';
END $$;

-- 2) Generic dedupe pass (any other accidental duplicates)
DO $$
DECLARE
    r RECORD;
    v_next INT;
    v_code TEXT;
BEGIN
    FOR r IN
        SELECT customer_id, customer_code FROM customers
        WHERE customer_id NOT IN (
            SELECT MIN(customer_id) FROM customers
            WHERE is_deleted = FALSE AND is_current = TRUE
            GROUP BY customer_code
        )
        AND is_deleted = FALSE AND is_current = TRUE
    LOOP
        SELECT COALESCE(MAX(CAST(SUBSTRING(customer_code FROM 5) AS INT)), 0) + 1 INTO v_next
        FROM customers WHERE customer_code ~ '^CUS-[0-9]+$';
        v_code := 'CUS-' || LPAD(v_next::text, 4, '0');
        RAISE NOTICE 'Renumbering customer_id=% from % to %', r.customer_id, r.customer_code, v_code;
        UPDATE customers SET customer_code = v_code WHERE customer_id = r.customer_id;
    END LOOP;
END $$;

-- 3) Add unique partial index (only enforces on active rows; allows historical versions)
DROP INDEX IF EXISTS uniq_customer_code_active;
CREATE UNIQUE INDEX uniq_customer_code_active
    ON customers (customer_code)
    WHERE is_deleted = FALSE AND is_current = TRUE;

-- Verify: no duplicates remain
SELECT customer_code, COUNT(*) AS n
FROM customers
WHERE is_deleted = FALSE AND is_current = TRUE
GROUP BY customer_code
HAVING COUNT(*) > 1;

-- Show TOKAI RIKA after rename
SELECT customer_id, customer_code, customer_name FROM customers WHERE customer_name ILIKE 'TOKAI RIKA%';

COMMIT;
