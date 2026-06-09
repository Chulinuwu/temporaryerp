-- ============================================================
-- Dedupe supplier_code: keep smallest supplier_id, renumber others
-- ============================================================

BEGIN;

DO $$
DECLARE
    r RECORD;
    next_n INT;
    new_code TEXT;
BEGIN
    FOR r IN
        SELECT supplier_id, supplier_code
        FROM suppliers
        WHERE (supplier_code, supplier_id) NOT IN (
            SELECT supplier_code, MIN(supplier_id)
            FROM suppliers
            WHERE is_deleted = FALSE AND is_current = TRUE
            GROUP BY supplier_code
        )
        AND is_deleted = FALSE AND is_current = TRUE
        ORDER BY supplier_id
    LOOP
        SELECT COALESCE(MAX(CAST(SUBSTRING(supplier_code FROM 5) AS INT)), 0) + 1 INTO next_n
        FROM suppliers WHERE supplier_code ~ '^SUP-[0-9]+$';
        new_code := 'SUP-' || LPAD(next_n::text, 4, '0');
        RAISE NOTICE 'Renumber supplier_id=% from % → %', r.supplier_id, r.supplier_code, new_code;
        UPDATE suppliers SET supplier_code = new_code WHERE supplier_id = r.supplier_id;
    END LOOP;
END $$;

CREATE UNIQUE INDEX uniq_supplier_code_active
    ON suppliers (supplier_code)
    WHERE is_deleted = FALSE AND is_current = TRUE;

-- Verify
SELECT supplier_code, COUNT(*) AS n
FROM suppliers WHERE is_deleted=FALSE AND is_current=TRUE
GROUP BY supplier_code HAVING COUNT(*) > 1;

SELECT COALESCE(MAX(CAST(SUBSTRING(supplier_code FROM 5) AS INT)), 0) AS max_seq
FROM suppliers WHERE supplier_code ~ '^SUP-[0-9]+$';

COMMIT;
