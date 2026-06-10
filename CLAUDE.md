# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Pegasus ERP (Tomas Tech Co., Ltd.) - an ERP covering CRM, Projects, Accounting, and BI.
Plain **PHP 8.2+ + PostgreSQL 14+**, no Composer, no framework, no vendor dependencies.
The whole app runs on a hand-rolled MVC core. Optional Python MCP server lives in `mcp-server/`.

## Run it

```bash
docker compose up -d              # PostgreSQL 16, auto-loads database/main/init.sql on first boot
php -S localhost:8080 -t public   # serve the app (any OS); open http://localhost:8080
```

- `start-server.bat [port]` is the Windows wrapper around the `php -S` command.
- DB connection reads env (`DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS`) with dev defaults
  in `config/database.php` that already match the Docker stack - no config needed locally.
- There is **no build step and no automated test runner**. The `*.php` files under
  `database/` (e.g. `system_test.php`, `pr_workflow_e2e_test.php`) are manual scripts run
  directly: `php database/system_test.php [base_url] [email] [password]`.

## Architecture

Every request flows through `public/index.php` (the only entry point):

1. `index.php` requires the `core/` files, then registers **every route explicitly** on a
   `Router` instance (`$router->get('/path', 'SomeController@method')`). Routes are flat
   strings with `{id}`-style params. There is no route auto-discovery - new endpoints must
   be added to `index.php` by hand.
2. `Router::dispatch()` matches the URI, `require`s `controllers/<Name>.php`, instantiates
   the controller, and calls the method with extracted URL params.
3. Controllers extend `core/Controller.php`, which provides `render($view, $data)` (wraps a
   `views/*.php` file inside `views/layout/app.php`), `json()`, `redirect()`, and the auth
   gates below.
4. Data access goes through `core/Model.php` - a thin active-record base (`find`, `all`,
   `create`, `update`, `softDelete`, `search`, `paginate`) over a PDO singleton
   (`core/Database.php`). Models lean on `is_deleted` soft-delete columns and write audit
   rows via `logAudit()` (mirrored by the DB-side `fn_audit_trigger` on key tables).

### Auth and permissions

Auth is **enforced per-controller, not globally**. A handler is public unless it calls a
gate from `core/Controller.php` at the top of the method:
- `requireAuth()` - logged in
- `requireRole($roles)` - specific role(s)
- `requireAccess($section)` - module-level access (`sales`, `accounting`, `hr`, ...) via
  `Auth::canAccess()`

When adding a protected endpoint, replicate the gate call used by sibling methods in the
same controller. `core/Auth.php` resolves roles from the `users` row (string, comma-list,
or related table).

### Approval flows

`core/ApprovalFlow.php` centralizes multi-step approval state machines (`masterStepper`,
`prStepper`, `poStepper`) for master-data changes, purchase requests, and purchase orders.
Approval-related routes (`/approve`, `/reject`) live alongside the normal CRUD routes.

### Logging

`core/Logger.php` writes daily-rotated files to `logs/` (e.g. `10_jun_2026.log`), wired up
at the top of `public/index.php`. It auto-captures requests, exceptions, fatals, SQL
errors, and auth events, and auto-prunes logs older than 7 days. Use `Logger::error(...)`
or the `logger($level, $msg, $context)` helper for manual entries. See
[`reference/logging.md`](reference/logging.md).

### i18n

`__('key', ...)` (defined in `core/Helpers.php`) looks up `lang/{en,ja,th}.php`. The app is
tri-lingual (English / Japanese / Thai) and many master tables carry `*_jp` / `*_th` columns -
keep all three in sync when touching display strings or master schemas.

## Database

`database/main/init.sql` is the single canonical schema (consolidated dump of live prod:
extensions + `fn_audit_trigger` + 80 tables + views). Docker and fresh installs load only
this file. Everything else under `database/` (`legacy/`, `seed/`, import scripts) is
historical or optional.

**Read [`reference/db.md`](reference/db.md) before editing anything under `database/`** - it
maps which files are canonical vs legacy vs tooling, explains the `seed/` data, and how
`init.sql` is regenerated. Note the Docker init script only runs on an empty volume;
`docker compose down -v` is required to re-apply a changed `init.sql`.

## Conventions

- Adding a feature usually means: new `controllers/<X>Controller.php` + `views/<area>/*.php`
  + explicit route registration in `public/index.php`. Match the structure of an existing
  area (e.g. `controllers/QuotationController.php` + `views/sales/`).
- Secrets are never committed: `config/credentials/` and `mcp-server/.env` are gitignored
  and must be created per machine from the documented defaults / `.env.example`.
- Full bilingual manuals (TH / JA) live in `docs/`.
- Deep, topic-specific notes live in `reference/<topic>.md` (e.g. `reference/db.md`) to keep
  this file light. When work touches a topic that has a reference doc, read it first; when
  you produce durable detail worth keeping, add or update a `reference/<topic>.md` rather
  than bloating this file or scattering nested `CLAUDE.md` files.
