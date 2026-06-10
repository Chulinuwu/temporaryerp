-- Link users.employee_id to employees based on name/email matching.
-- Safe to re-run: only updates rows where employee_id IS NULL.

-- 1) Exact email match
UPDATE users u
SET employee_id = e.employee_id,
    updated_at = NOW()
FROM employees e
WHERE u.employee_id IS NULL
  AND e.is_deleted = FALSE
  AND u.email IS NOT NULL
  AND LOWER(u.email) = LOWER(e.email);

-- 2) Username "first.last" matches employees.full_name "First Last"
UPDATE users u
SET employee_id = e.employee_id,
    updated_at = NOW()
FROM employees e
WHERE u.employee_id IS NULL
  AND e.is_deleted = FALSE
  AND LOWER(REPLACE(e.full_name, ' ', '.')) = LOWER(u.username);

-- 3) Username "last.first" matches employees.full_name "First Last" (reversed)
UPDATE users u
SET employee_id = e.employee_id,
    updated_at = NOW()
FROM employees e
WHERE u.employee_id IS NULL
  AND e.is_deleted = FALSE
  AND u.username LIKE '%.%'
  AND LOWER(e.full_name) =
      LOWER(split_part(u.username, '.', 2) || ' ' || split_part(u.username, '.', 1));

-- 4) Both tokens of username appear in full_name (fuzzy fallback)
UPDATE users u
SET employee_id = e.employee_id,
    updated_at = NOW()
FROM employees e
WHERE u.employee_id IS NULL
  AND e.is_deleted = FALSE
  AND u.username LIKE '%.%'
  AND LOWER(e.full_name) ILIKE '%' || LOWER(split_part(u.username, '.', 1)) || '%'
  AND LOWER(e.full_name) ILIKE '%' || LOWER(split_part(u.username, '.', 2)) || '%';

-- Check: list users still unlinked
SELECT user_id, username, email
FROM users
WHERE employee_id IS NULL AND is_active = TRUE
ORDER BY username;
