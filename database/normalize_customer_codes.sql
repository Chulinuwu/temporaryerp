-- ============================================================
-- Normalise all customer_code: CUST-XXXX → CUS-XXXX
-- Handle collisions by re-numbering the older-id row to next free seq
-- ============================================================

BEGIN;

-- 1) First pass: rename CUST-* → CUS-* where no collision would occur
DO $$
DECLARE
    r RECORD;
    new_code TEXT;
BEGIN
    FOR r IN
        SELECT customer_id, customer_code
        FROM customers
        WHERE customer_code LIKE 'CUST-%'
        ORDER BY customer_id
    LOOP
        -- Proposed new code: just drop the T
        new_code := 'CUS-' || SUBSTRING(r.customer_code FROM 6);

        -- If that CUS-xxxx already exists on a different row, skip — handle in pass 2
        IF EXISTS (
            SELECT 1 FROM customers
            WHERE customer_code = new_code AND customer_id <> r.customer_id
        ) THEN
            RAISE NOTICE 'Collision: % → % already taken, will renumber', r.customer_code, new_code;
        ELSE
            UPDATE customers SET customer_code = new_code WHERE customer_id = r.customer_id;
        END IF;
    END LOOP;
END $$;

-- 2) Second pass: any remaining CUST-* rows get the next free CUS- number
DO $$
DECLARE
    r RECORD;
    next_n INT;
    new_code TEXT;
BEGIN
    FOR r IN
        SELECT customer_id, customer_code
        FROM customers
        WHERE customer_code LIKE 'CUST-%'
        ORDER BY customer_id
    LOOP
        SELECT COALESCE(MAX(CAST(SUBSTRING(customer_code FROM 5) AS INT)), 0) + 1 INTO next_n
        FROM customers WHERE customer_code ~ '^CUS-[0-9]+$';
        new_code := 'CUS-' || LPAD(next_n::text, 4, '0');
        RAISE NOTICE 'Renumbering % (id=%) → %', r.customer_code, r.customer_id, new_code;
        UPDATE customers SET customer_code = new_code WHERE customer_id = r.customer_id;
    END LOOP;
END $$;

-- 3) Ensure unique partial index exists
DROP INDEX IF EXISTS uniq_customer_code_active;
CREATE UNIQUE INDEX uniq_customer_code_active
    ON customers (customer_code)
    WHERE is_deleted = FALSE AND is_current = TRUE;

-- Verify
SELECT
    COUNT(*) FILTER (WHERE customer_code LIKE 'CUST-%') AS remaining_old,
    COUNT(*) FILTER (WHERE customer_code LIKE 'CUS-%')  AS new_format,
    COUNT(*) AS total
FROM customers
WHERE customer_code IS NOT NULL;

SELECT COALESCE(MAX(CAST(SUBSTRING(customer_code FROM 5) AS INT)), 0) AS max_numeric_seq
FROM customers
WHERE customer_code ~ '^CUS-[0-9]+$';

-- Duplicates check (should be empty)
SELECT customer_code, COUNT(*) AS n
FROM customers
WHERE is_deleted = FALSE AND is_current = TRUE
GROUP BY customer_code
HAVING COUNT(*) > 1;

COMMIT;
