-- ============================================================
-- PEGASUS ERP — PR enhancements: project link + supplier quotation attachments
-- ============================================================

BEGIN;

-- 1) Project linkage on PR header
ALTER TABLE purchase_requests
    ADD COLUMN IF NOT EXISTS project_id INTEGER REFERENCES projects(project_id);

CREATE INDEX IF NOT EXISTS idx_pr_project ON purchase_requests(project_id) WHERE is_deleted = FALSE;

-- 2) Attachments table (supplier quotations etc.)
CREATE TABLE IF NOT EXISTS purchase_request_attachments (
    attachment_id   SERIAL PRIMARY KEY,
    pr_id           INTEGER NOT NULL REFERENCES purchase_requests(pr_id) ON DELETE CASCADE,
    file_name       VARCHAR(255) NOT NULL,
    stored_path     VARCHAR(500) NOT NULL,        -- relative to public/
    file_size       INTEGER       NOT NULL,
    mime_type       VARCHAR(120),
    description     VARCHAR(255),                  -- e.g. 'Supplier quotation - ABC Co.'
    uploaded_by     INTEGER REFERENCES users(user_id),
    uploaded_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    is_deleted      BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE INDEX IF NOT EXISTS idx_pr_attach_pr ON purchase_request_attachments(pr_id) WHERE is_deleted = FALSE;

GRANT SELECT ON purchase_request_attachments TO pegasus_mcp_ro;

COMMIT;
