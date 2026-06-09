-- ============================================================
-- Rename customer_code prefix: CUST- → CUS-
-- ============================================================

BEGIN;

-- Preview
SELECT customer_code AS before, regexp_replace(customer_code, '^CUST-', 'CUS-') AS after
FROM customers
WHERE customer_code LIKE 'CUST-%'
ORDER BY customer_code;

-- Apply rename
UPDATE customers
SET customer_code = regexp_replace(customer_code, '^CUST-', 'CUS-')
WHERE customer_code LIKE 'CUST-%';

-- Verify (no CUST- left)
SELECT customer_code, customer_name FROM customers
WHERE customer_code LIKE 'CUST-%';
SELECT COUNT(*) AS total, COUNT(*) FILTER (WHERE customer_code LIKE 'CUS-%') AS new_prefix
FROM customers;

COMMIT;
