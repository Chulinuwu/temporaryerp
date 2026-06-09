-- PEGASUS ERP: Cost Sheets Table (原価算出)
-- Adds cost_sheets table referenced by CostSheetController but missing from schema

BEGIN;

CREATE TABLE IF NOT EXISTS cost_sheets (
    cost_sheet_id   SERIAL          PRIMARY KEY,
    sheet_no        VARCHAR(50)     NOT NULL UNIQUE,
    sheet_name      VARCHAR(300),
    customer_id     INT             REFERENCES customers(customer_id),
    quotation_id    INT             REFERENCES quotation_headers(quotation_id),
    project_id      INT             REFERENCES projects(project_id),
    status          VARCHAR(30)     NOT NULL DEFAULT 'DRAFT', -- DRAFT | APPROVED | ARCHIVED
    total_cost      NUMERIC(18,2)   DEFAULT 0,
    source_file     VARCHAR(500),
    notes           TEXT,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by      INT             REFERENCES users(user_id),
    updated_by      INT             REFERENCES users(user_id),
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_cost_sheets_customer ON cost_sheets(customer_id);
CREATE INDEX IF NOT EXISTS idx_cost_sheets_quotation ON cost_sheets(quotation_id);
CREATE INDEX IF NOT EXISTS idx_cost_sheets_project ON cost_sheets(project_id);
CREATE INDEX IF NOT EXISTS idx_cost_sheets_status ON cost_sheets(status);

-- Add cost_sheet_id to project_cost_items (previously keyed only by project_id)
ALTER TABLE project_cost_items
    ADD COLUMN IF NOT EXISTS cost_sheet_id INT REFERENCES cost_sheets(cost_sheet_id);

ALTER TABLE project_cost_items
    ALTER COLUMN project_id DROP NOT NULL;

CREATE INDEX IF NOT EXISTS idx_cost_items_sheet ON project_cost_items(cost_sheet_id);

COMMIT;
