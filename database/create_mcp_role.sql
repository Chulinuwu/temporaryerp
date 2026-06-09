-- ============================================================
-- PEGASUS ERP — Read-only role for the MCP server
-- Run as postgres superuser:
--   psql -U postgres -d pegasus_erp -f database/create_mcp_role.sql
-- After running, set the same password in mcp-server/.env (PG_PASSWORD)
-- ============================================================

-- 1. Create role (idempotent)
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'pegasus_mcp_ro') THEN
    CREATE ROLE pegasus_mcp_ro LOGIN PASSWORD 'change_this_strong_password';
  END IF;
END
$$;

-- 2. Connect privilege
GRANT CONNECT ON DATABASE pegasus_erp TO pegasus_mcp_ro;

-- 3. Schema usage + SELECT on all current and future tables
GRANT USAGE ON SCHEMA public TO pegasus_mcp_ro;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO pegasus_mcp_ro;
GRANT SELECT ON ALL SEQUENCES IN SCHEMA public TO pegasus_mcp_ro;

ALTER DEFAULT PRIVILEGES IN SCHEMA public
  GRANT SELECT ON TABLES TO pegasus_mcp_ro;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
  GRANT SELECT ON SEQUENCES TO pegasus_mcp_ro;

-- 4. Verification
\echo ''
\echo '=== Role pegasus_mcp_ro privileges ==='
SELECT grantee, privilege_type, COUNT(*) AS tables
FROM information_schema.role_table_grants
WHERE grantee = 'pegasus_mcp_ro'
GROUP BY grantee, privilege_type;
