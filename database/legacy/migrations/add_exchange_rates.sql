-- ============================================================
-- #10 Exchange Rate Master
--   - All currency pairs (Baht, USD, JPY, EUR, CNY, SGD, MYR, etc.)
--   - From-To validity period
-- ============================================================

BEGIN;

CREATE TABLE IF NOT EXISTS exchange_rates (
    rate_id         SERIAL          PRIMARY KEY,
    from_currency   CHAR(3)         NOT NULL,   -- e.g. 'USD'
    to_currency     CHAR(3)         NOT NULL,   -- e.g. 'THB'
    rate            NUMERIC(18,8)   NOT NULL,
    effective_from  DATE            NOT NULL,
    effective_to    DATE,                       -- NULL = open-ended
    notes           TEXT,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by      INT,
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE
);

CREATE INDEX IF NOT EXISTS idx_fx_from_to ON exchange_rates(from_currency, to_currency)
    WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_fx_effective ON exchange_rates(effective_from, effective_to)
    WHERE is_deleted = FALSE;

-- Currency master (for dropdown choices)
CREATE TABLE IF NOT EXISTS currencies (
    currency_code   CHAR(3)         PRIMARY KEY,
    currency_name   VARCHAR(50)     NOT NULL,
    currency_name_jp VARCHAR(50),
    currency_name_th VARCHAR(50),
    symbol          VARCHAR(10),
    sort_order      INT             NOT NULL DEFAULT 0,
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE
);

INSERT INTO currencies (currency_code, currency_name, currency_name_jp, currency_name_th, symbol, sort_order) VALUES
    ('THB', 'Thai Baht',        'タイバーツ',     'บาท',         '฿',  10),
    ('USD', 'US Dollar',        '米ドル',         'ดอลลาร์สหรัฐ', '$',  20),
    ('JPY', 'Japanese Yen',     '日本円',         'เยน',         '¥',  30),
    ('EUR', 'Euro',             'ユーロ',         'ยูโร',        '€',  40),
    ('CNY', 'Chinese Yuan',     '人民元',         'หยวน',        '¥',  50),
    ('SGD', 'Singapore Dollar', 'シンガポールドル','ดอลลาร์สิงคโปร์','S$', 60),
    ('MYR', 'Malaysian Ringgit','マレーシア・リンギット','ริงกิตมาเลเซีย','RM', 70),
    ('IDR', 'Indonesian Rupiah','インドネシアルピア','รูเปียห์',    'Rp', 80),
    ('VND', 'Vietnamese Dong',  'ベトナムドン',   'ด่ง',         '₫',  90),
    ('PHP', 'Philippine Peso',  'フィリピンペソ', 'เปโซฟิลิปปินส์','₱',  100),
    ('KRW', 'South Korean Won', '韓国ウォン',     'วอนเกาหลีใต้','₩',  110),
    ('TWD', 'Taiwan Dollar',    '台湾ドル',       'ดอลลาร์ไต้หวัน','NT$', 120),
    ('HKD', 'Hong Kong Dollar', '香港ドル',       'ดอลลาร์ฮ่องกง','HK$', 130),
    ('INR', 'Indian Rupee',     'インドルピー',   'รูปีอินเดีย', '₹',  140),
    ('GBP', 'British Pound',    'ポンド',         'ปอนด์',       '£',  150),
    ('AUD', 'Australian Dollar','豪ドル',         'ดอลลาร์ออสเตรเลีย','A$', 160)
ON CONFLICT (currency_code) DO NOTHING;

-- Seed initial rates (approximate as of 2026-04, all → THB and back)
-- Production data should be updated regularly through the UI.
DO $$
DECLARE
    v_today DATE := CURRENT_DATE;
    rates RECORD;
BEGIN
    FOR rates IN
        SELECT * FROM (VALUES
            ('USD', 'THB', 35.50),
            ('JPY', 'THB',  0.235),
            ('EUR', 'THB', 38.80),
            ('CNY', 'THB',  4.92),
            ('SGD', 'THB', 26.40),
            ('MYR', 'THB',  7.95),
            ('IDR', 'THB',  0.0022),
            ('VND', 'THB',  0.0014),
            ('PHP', 'THB',  0.62),
            ('KRW', 'THB',  0.025),
            ('TWD', 'THB',  1.10),
            ('HKD', 'THB',  4.55),
            ('INR', 'THB',  0.42),
            ('GBP', 'THB', 45.20),
            ('AUD', 'THB', 23.10),
            ('THB', 'THB',  1.00)
        ) AS t(from_c, to_c, r)
    LOOP
        INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_from)
        VALUES (rates.from_c, rates.to_c, rates.r, v_today);

        -- Reverse rate
        IF rates.from_c <> 'THB' THEN
            INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_from)
            VALUES (rates.to_c, rates.from_c, ROUND(1.0 / rates.r, 8), v_today);
        END IF;
    END LOOP;
END $$;

-- Verify
SELECT from_currency || '→' || to_currency AS pair, rate, effective_from
FROM exchange_rates
WHERE is_deleted = FALSE
ORDER BY from_currency, to_currency;

COMMIT;
