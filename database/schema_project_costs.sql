-- PEGASUS ERP: Project Cost Breakdown Items (PJ原価算出)
-- Stores detailed cost items imported from Excel or linked from quotations

CREATE TABLE IF NOT EXISTS project_cost_items (
    cost_item_id    SERIAL          PRIMARY KEY,
    project_id      INT             NOT NULL REFERENCES projects(project_id),
    line_no         INT             NOT NULL DEFAULT 0,
    category        VARCHAR(200),       -- D列: 分類 (Standard equipment cost, Electrical cost, etc.)
    description     VARCHAR(500),       -- E列: 説明
    supplier        VARCHAR(200),       -- F列: 仕入先
    brand           VARCHAR(100),       -- G列: ブランド
    lead_time       VARCHAR(50),        -- H列: リードタイム
    unit_price      NUMERIC(18,4)   DEFAULT 0,  -- J列: 単価
    quantity        NUMERIC(14,4)   DEFAULT 0,  -- K列: 数量
    total_amount    NUMERIC(18,2)   DEFAULT 0,  -- L列: 合計
    unit            VARCHAR(30),        -- M列: 単位
    remark          VARCHAR(500),       -- N列: 備考
    is_category_row BOOLEAN         NOT NULL DEFAULT FALSE,  -- 分類見出し行
    quotation_id    INT             REFERENCES quotation_headers(quotation_id),  -- 見積書からリンク
    source          VARCHAR(30)     DEFAULT 'MANUAL',  -- MANUAL | IMPORT | QUOTATION
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_cost_items_project ON project_cost_items(project_id);
CREATE INDEX IF NOT EXISTS idx_cost_items_quotation ON project_cost_items(quotation_id);
