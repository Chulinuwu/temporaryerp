-- ============================================================
-- Company bank accounts master
-- (own bank accounts for receiving customer payments — printed on invoices)
-- ============================================================
BEGIN;

CREATE TABLE IF NOT EXISTS company_bank_accounts (
    cba_id           SERIAL          PRIMARY KEY,
    bank_name        VARCHAR(100)    NOT NULL,
    bank_name_th     VARCHAR(100),
    branch           VARCHAR(100),
    branch_th        VARCHAR(100),
    account_type     VARCHAR(30)     DEFAULT 'CURRENT',  -- CURRENT / SAVING / FIXED
    account_no       VARCHAR(40)     NOT NULL,
    account_name     VARCHAR(150)    NOT NULL,
    currency_code    CHAR(3)         NOT NULL DEFAULT 'THB',
    swift_code       VARCHAR(20),
    notes            TEXT,
    is_default       BOOLEAN         NOT NULL DEFAULT FALSE,
    is_active        BOOLEAN         NOT NULL DEFAULT TRUE,
    sort_order       INT             NOT NULL DEFAULT 0,
    created_by       INT,
    created_at       TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by       INT,
    updated_at       TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- Only one default per currency
CREATE UNIQUE INDEX IF NOT EXISTS uniq_default_per_currency
    ON company_bank_accounts(currency_code)
    WHERE is_default = TRUE AND is_active = TRUE;

-- Seed sample (Tomas Tech main account)
INSERT INTO company_bank_accounts
    (bank_name, bank_name_th, branch, account_type, account_no, account_name,
     currency_code, is_default, sort_order)
VALUES
    ('Bangkok Bank', 'ธนาคารกรุงเทพ', 'Head Office', 'CURRENT',
     '123-4-56789-0', 'Tomas Tech Co., Ltd.', 'THB', TRUE, 10)
ON CONFLICT DO NOTHING;

SELECT * FROM company_bank_accounts ORDER BY sort_order;

COMMIT;
