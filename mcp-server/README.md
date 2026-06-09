# PEGASUS MCP Server

Model Context Protocol (MCP) server that exposes PEGASUS ERP read-only
operations to Claude Code / Claude Desktop.

## Phase 1 (MVP) — implemented

| Tool | Purpose |
|---|---|
| `pegasus_search_customers` | Search customers by keyword/industry |
| `pegasus_get_customer` | Single customer + recent quotes + open AR |
| `pegasus_list_quotations` | List quotations w/ status/customer/recency filters |
| `pegasus_get_quotation` | Quotation header + lines |
| `pegasus_pending_approvals` | Snapshot of 4 approval queues |
| `pegasus_kpi_dashboard` | Annual KPI rollup (target vs WON) |
| `pegasus_pipeline` | Pipeline by status + top 10 customers |
| `pegasus_ar_aging` | AR aging buckets (current/1-30/31-60/61-90/90+) |
| `pegasus_run_system_test` | Run 164-item System Test |
| `pegasus_run_deep_audit` | Run 31-item deep audit |

## Setup

```powershell
cd mcp-server
py -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
# edit .env — at minimum set PG_PASSWORD and PEGASUS_ROOT
```

## Create the read-only DB role (one-time)

```sql
-- as postgres superuser
CREATE ROLE pegasus_mcp_ro LOGIN PASSWORD 'CHANGE_THIS';
GRANT CONNECT ON DATABASE pegasus_erp TO pegasus_mcp_ro;
\c pegasus_erp
GRANT USAGE ON SCHEMA public TO pegasus_mcp_ro;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO pegasus_mcp_ro;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
  GRANT SELECT ON TABLES TO pegasus_mcp_ro;
```

A helper script is provided: `database/create_mcp_role.sql` at the
project root.

## Run

```powershell
python -m pegasus_mcp                # stdio server
python -m pegasus_mcp --list-tools   # dump registered tools as JSON
```

## Register with Claude Code

Add to `~/.claude.json` (or via `claude mcp add`):

```json
{
  "mcpServers": {
    "pegasus-erp": {
      "command": "python",
      "args": ["-m", "pegasus_mcp"],
      "cwd": "C:/Users/R.Nozaki/Downloads/Pegasus_ERP_R1/mcp-server",
      "env": {
        "PYTHONPATH": "C:/Users/R.Nozaki/Downloads/Pegasus_ERP_R1/mcp-server/src"
      }
    }
  }
}
```

(values from `mcp-server/.env` are loaded automatically via python-dotenv)

## Security model

| Layer | Enforcement |
|---|---|
| DB role | `pegasus_mcp_ro` is GRANT SELECT only |
| Session | `SET default_transaction_read_only = on` per connection |
| Statement timeout | 15 s |
| Write ops | Phase 2 — must go through PEGASUS HTTP API + RBAC + audit |

## Phase 2 (planned)

- Write tools: `pegasus_create_quotation_draft`, approve/reject endpoints
- HTTP client w/ JWT (`api.py`) calling PEGASUS PHP backend
- MCP resources (`pegasus://customers/{code}` etc.)
- Rate limiting + per-tool quotas
- MCP audit log entries with `mcp:` prefix in `audit_logs`
