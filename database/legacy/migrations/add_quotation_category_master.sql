-- ============================================================
-- PEGASUS ERP — Quotation line category master
--   Used in the 分類 dropdown of quotation_lines (is_category_row=TRUE)
-- ============================================================

BEGIN;

CREATE TABLE IF NOT EXISTS quotation_categories (
    category_id      SERIAL PRIMARY KEY,
    category_code    VARCHAR(40) NOT NULL UNIQUE,
    name_jp          VARCHAR(120) NOT NULL,
    name_en          VARCHAR(120),
    name_th          VARCHAR(120),
    description      TEXT,
    sort_order       INTEGER NOT NULL DEFAULT 0,
    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    is_deleted       BOOLEAN NOT NULL DEFAULT FALSE,
    created_by       INTEGER REFERENCES users(user_id),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_qcat_active
    ON quotation_categories(sort_order, name_jp) WHERE is_active = TRUE AND is_deleted = FALSE;

-- Seed common categories
INSERT INTO quotation_categories (category_code, name_jp, name_en, name_th, sort_order)
VALUES
    ('HW',       'ハードウェア',       'Hardware',          'ฮาร์ดแวร์',           10),
    ('SW',       'ソフトウェア',       'Software',          'ซอฟต์แวร์',           20),
    ('SERVICE',  'サービス',          'Service',           'บริการ',              30),
    ('LICENSE',  'ライセンス',        'License',           'ไลเซนส์',             40),
    ('MAINT',    '保守',             'Maintenance',       'การบำรุงรักษา',       50),
    ('TRAINING', 'トレーニング',       'Training',          'การอบรม',             60),
    ('OTHER',    'その他',           'Other',             'อื่นๆ',                90)
ON CONFLICT (category_code) DO NOTHING;

GRANT SELECT ON quotation_categories TO pegasus_mcp_ro;

COMMIT;
