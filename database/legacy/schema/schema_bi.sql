-- ============================================================
-- PEGASUS ERP - BI Dashboard Builder Schema
-- ============================================================

-- BI Dashboards
CREATE TABLE IF NOT EXISTS bi_dashboards (
    bi_dashboard_id   SERIAL PRIMARY KEY,
    dashboard_name    VARCHAR(200) NOT NULL,
    dashboard_name_jp VARCHAR(200),
    dashboard_name_th VARCHAR(200),
    description       TEXT,
    is_default        BOOLEAN DEFAULT FALSE,
    is_shared         BOOLEAN DEFAULT FALSE,
    owner_user_id     INT NOT NULL,
    allowed_roles     VARCHAR(500),
    is_deleted        BOOLEAN DEFAULT FALSE,
    created_by        INT,
    created_at        TIMESTAMPTZ DEFAULT NOW(),
    updated_by        INT,
    updated_at        TIMESTAMPTZ DEFAULT NOW()
);

-- BI Widgets
CREATE TABLE IF NOT EXISTS bi_widgets (
    bi_widget_id     SERIAL PRIMARY KEY,
    bi_dashboard_id  INT NOT NULL REFERENCES bi_dashboards(bi_dashboard_id) ON DELETE CASCADE,
    widget_name      VARCHAR(200) NOT NULL,
    chart_type       VARCHAR(30) NOT NULL,
    data_config      JSONB NOT NULL DEFAULT '{}',
    chart_options    JSONB DEFAULT '{}',
    grid_x           INT DEFAULT 0,
    grid_y           INT DEFAULT 0,
    grid_w           INT DEFAULT 6,
    grid_h           INT DEFAULT 4,
    sort_order       INT DEFAULT 0,
    is_deleted       BOOLEAN DEFAULT FALSE,
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_bi_widgets_dashboard ON bi_widgets(bi_dashboard_id) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_bi_dashboards_owner ON bi_dashboards(owner_user_id) WHERE is_deleted = FALSE;
