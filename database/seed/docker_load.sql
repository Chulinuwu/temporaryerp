-- Docker auto-seed loader (runs as /docker-entrypoint-initdb.d/02-seed.sql after init).
-- Legacy seed predates the prod schema, which dropped the payroll objects below.
-- We re-add them as a compat shim, load the seed, then drop them so the schema
-- matches prod again. Salary data has nowhere to live in the current schema and is
-- intentionally discarded.

ALTER TABLE employees
  ADD COLUMN IF NOT EXISTS salary_type     varchar(30),
  ADD COLUMN IF NOT EXISTS base_salary     numeric,
  ADD COLUMN IF NOT EXISTS salary_currency varchar(10);

CREATE TABLE IF NOT EXISTS pit_tax_brackets (
  fiscal_year int, income_from numeric, income_to numeric, tax_rate numeric
);

\i /seed/seed.sql
\i /seed/seed_employees.sql
\i /seed/seed_crm.sql

ALTER TABLE employees
  DROP COLUMN IF EXISTS salary_type,
  DROP COLUMN IF EXISTS base_salary,
  DROP COLUMN IF EXISTS salary_currency;

DROP TABLE IF EXISTS pit_tax_brackets;
