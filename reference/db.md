# Database reference

Map of the SQL assets under `database/`. Read this before touching anything there.

## What to run for a fresh setup

`database/main/init.sql` is the single source of truth for the current schema. It is a
consolidated dump of the live prod schema (extensions + the one real trigger function +
80 tables with their indexes/constraints/triggers + 4 views), ordered so it loads
top-to-bottom with zero errors.

- Docker: `docker compose up -d` mounts `database/main/init.sql` as the init script. Nothing else needed.
- Manual: `psql -d pegasus_erp -f database/main/init.sql`

Do not run the `database/legacy/` files for setup. They are superseded by `main/init.sql`.

## Directory map

| Path | Role | Run it? |
|---|---|---|
| `database/main/init.sql` | CANONICAL schema. Fresh installs and Docker use this only. | Yes |
| `database/main/source/` | Raw prod DDL (`schema.sql`, `functions.sql`, `view.sql`) exported from DBeaver. `init.sql` is assembled from these. | No (source only) |
| `database/seed/` | Master/seed data: `seed.sql`, `seed_crm.sql`, `seed_employees.sql`, `reseed_*.sql`. Fills lookup/master tables and creates login users. | Auto (Docker) |
| `database/seed/docker_load.sql` | Docker auto-seed orchestrator (mounted as `02-seed.sql`). Applies a payroll compat shim, `\i`s the three seed files in order, then drops the shim. | Auto (Docker) |
| `database/legacy/schema/` | Old incremental schema files (`schema.sql`, `schema_*.sql`) that built prod over time. | No (history) |
| `database/legacy/migrations/` | All `add_*` / `migrate_*` / `fix_*` / one-off ops / generated data imports. Already folded into `main/init.sql`. | No (history) |
| `database/create_mcp_role.sql` | Standalone helper: creates the read-only role for the MCP server. Referenced by `mcp-server/README.md`. | When using MCP |
| `database/*.py` | One-off codegen / import tools (PDF/Excel -> SQL). Hardcoded Windows paths, non-portable. Reference only. | No |
| `database/*.php` | Manual dev/test utilities (system_test, e2e tests, reset_passwords, translate). Require `../core` and `../config`, so they must stay at `database/` root. | On demand |

## seed/ details

`init.sql` creates empty tables only. The `seed/` files load the reference data the app
needs to actually function - most importantly the `users` rows you log in with.

| File | Loads |
|---|---|
| `seed.sql` | Core master: divisions, departments, accounts (chart of accounts), banks, payment_terms, tax brackets, warehouses, work_schedules, users |
| `seed_employees.sql` | Real staff roster + login users (from E-mail list.xlsx); roles ADMIN / SALES_MANAGER / ACCOUNTING / STAFF |
| `seed_crm.sql` | CRM master: activity_categories, deal_statuses |
| `reseed_deal_statuses.sql` | Deletes then re-inserts the corrected 21-status list |
| `reseed_solution_categories.sql` | Deletes then re-inserts 32 categories; also ALTERs columns |

`seed` = initial insert; `reseed` = wipe-and-replace fix-ups applied later (some include
`ALTER TABLE`). These files predate the prod dump, so they may not match `init.sql`
column-for-column. Specifically, prod dropped the payroll objects the seed still
references (`employees.salary_type/base_salary/salary_currency` and the
`pit_tax_brackets` table), so `docker_load.sql` re-adds them as a temporary shim, loads
the seed, then drops them again. `reseed_*.sql` are NOT auto-loaded - run them manually if
needed.

### Login credentials (after seed)

All seeded users share the bcrypt password `password`. Log in with `admin` (or any
employee email such as `anek.s@tomastc.com`). Roles: ADMIN / SALES_MANAGER / ACCOUNTING /
STAFF.

## init.sql assembly

`main/init.sql` = header + `main/source/` contents, with two deliberate edits:

1. The 46 C-language functions in `functions.sql` are pgcrypto / uuid-ossp internals. They
   are NOT recreated; the header issues `CREATE EXTENSION pgcrypto` and
   `CREATE EXTENSION "uuid-ossp"` instead. Only the real plpgsql function `fn_audit_trigger()`
   is carried over.
2. `client_min_messages = warning` to keep the load quiet.

To regenerate after a new prod export: drop fresh DDL into `main/source/`, then rebuild
`init.sql` as `header + fn_audit_trigger + source/schema.sql + source/view.sql`.

## Caveats

- `main/init.sql` is schema-only. No business data, no master data. Load from `seed/` if a
  module needs reference rows.
- The Docker init script runs ONLY on first boot (empty volume). After editing `init.sql`,
  run `docker compose down -v` before `up` to re-apply.
