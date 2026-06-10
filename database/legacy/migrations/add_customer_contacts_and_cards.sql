-- ============================================================
-- Customer Contacts & Business Cards
--   Multi-contact support per customer + uploaded name-card images
-- ============================================================

BEGIN;

-- ── Contacts (名刺ベースの連絡先) ──
CREATE TABLE IF NOT EXISTS customer_contacts (
    contact_id      SERIAL          PRIMARY KEY,
    customer_id     INT             REFERENCES customers(customer_id) ON DELETE CASCADE,
    -- customer_id may be NULL temporarily while the card is uploaded but not yet linked
    full_name       VARCHAR(150)    NOT NULL,
    full_name_local VARCHAR(150),               -- native script (JP / TH / CN)
    title           VARCHAR(120),               -- 役職 e.g. "Supervisor", "Engineer Manager"
    department      VARCHAR(120),               -- 部署 e.g. "Sale & Marketing Department"
    company_name    VARCHAR(200),               -- raw company name off the card (if different / not yet linked)
    email           VARCHAR(200),
    phone           VARCHAR(50),
    mobile          VARCHAR(50),
    fax             VARCHAR(50),
    address         TEXT,
    website         VARCHAR(200),
    is_primary      BOOLEAN         NOT NULL DEFAULT FALSE,
    notes           TEXT,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    created_by      INT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_by      INT,
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_customer_contacts_customer ON customer_contacts(customer_id)
    WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_customer_contacts_email ON customer_contacts(LOWER(email));

-- ── Business Card images (名刺画像) ──
CREATE TABLE IF NOT EXISTS business_cards (
    card_id         SERIAL          PRIMARY KEY,
    contact_id      INT             REFERENCES customer_contacts(contact_id) ON DELETE SET NULL,
    customer_id     INT             REFERENCES customers(customer_id) ON DELETE SET NULL,
    file_path       VARCHAR(500)    NOT NULL,   -- /uploads/business_cards/xxx.jpg
    file_name       VARCHAR(255),               -- original file name
    file_size       INT,
    mime_type       VARCHAR(80),
    ocr_raw_text    TEXT,                       -- raw OCR output (optional; front-end can fill)
    uploaded_by     INT,
    uploaded_at     TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_business_cards_contact ON business_cards(contact_id);
CREATE INDEX IF NOT EXISTS idx_business_cards_customer ON business_cards(customer_id);

-- ── Seed sample contacts (from provided business card images) ──
-- These insert only if the customer row does not yet exist; safe to re-run.
DO $$
DECLARE
    v_division_id INT;
    v_futaba_id INT;
    v_daido_id  INT;
    v_cbc_id    INT;
BEGIN
    SELECT division_id INTO v_division_id FROM divisions ORDER BY division_id LIMIT 1;
    IF v_division_id IS NULL THEN
        RAISE NOTICE 'No division found — skipping sample customer seed';
        RETURN;
    END IF;

    -- Futaba JTW (Thailand) Ltd.
    SELECT customer_id INTO v_futaba_id FROM customers
        WHERE customer_name ILIKE 'FUTABA JTW%' AND is_deleted = FALSE LIMIT 1;
    IF v_futaba_id IS NULL THEN
        INSERT INTO customers (customer_code, division_id, customer_name, customer_name_jp, country, address, tax_id, contact_person, phone)
        VALUES ('CUST-FUTABA', v_division_id, 'FUTABA JTW (THAILAND) LTD. (HEAD OFFICE)', 'フタバJTW(タイランド)',
                'TH', '78 Moo 2, Wellgrow Industrial Estate, Bangna-Trad Road, Tambon Pimpa, Bangpakong District, Chachoengsao 24130, Thailand',
                NULL, 'Kenichi Yamagata', '0-3852-2270-4')
        RETURNING customer_id INTO v_futaba_id;
    END IF;

    INSERT INTO customer_contacts (customer_id, full_name, full_name_local, title, company_name, email, phone, mobile, fax, address, website, is_primary)
    SELECT v_futaba_id, 'Kenichi Yamagata', '山形 賢一', 'President',
           'FUTABA JTW (THAILAND) LTD.', 'yamagata@futaba.co.jp', '0-3852-2270-4', '09-3794-0722', '0-3852-2275',
           '78 Moo 2, Wellgrow Industrial Estate, Bangna-Trad Road, Tambon Pimpa, Bangpakong District, Chachoengsao 24130, Thailand',
           'http://www.ftna.com', TRUE
    WHERE NOT EXISTS (SELECT 1 FROM customer_contacts WHERE customer_id = v_futaba_id AND full_name = 'Kenichi Yamagata');

    INSERT INTO customer_contacts (customer_id, full_name, full_name_local, title, company_name, email, phone, mobile, fax, address, website, is_primary)
    SELECT v_futaba_id, 'Hitoshi Murakami', '村上 仁', 'Engineer Manager',
           'FUTABA JTW (THAILAND) LTD.', 'murakami@futaba.co.jp', '0-3852-2270-4', '08-4024-1678', '0-3852-2275',
           '78 Moo 2, Wellgrow Industrial Estate, Bangna-Trad Road, Tambon Pimpa, Bangpakong District, Chachoengsao 24130, Thailand',
           'http://www.ftna.com', FALSE
    WHERE NOT EXISTS (SELECT 1 FROM customer_contacts WHERE customer_id = v_futaba_id AND full_name = 'Hitoshi Murakami');

    -- Daido Manufacturing (Thailand) Co., Ltd.
    SELECT customer_id INTO v_daido_id FROM customers
        WHERE customer_name ILIKE 'DAIDO MANUFACTURING%' AND is_deleted = FALSE LIMIT 1;
    IF v_daido_id IS NULL THEN
        INSERT INTO customers (customer_code, division_id, customer_name, customer_name_jp, country, address, phone)
        VALUES ('CUST-DAIDO', v_division_id, 'DAIDO MANUFACTURING (THAILAND) CO., LTD.', '野田製作所(タイ)',
                'TH', '283 Moo 7, Chachoengsao Factory, Pluakdaeng, Rayong 21140 Thailand',
                '(038) 891-545') ON CONFLICT DO NOTHING
        RETURNING customer_id INTO v_daido_id;
    END IF;

    IF v_daido_id IS NOT NULL THEN
        INSERT INTO customer_contacts (customer_id, full_name, full_name_local, title, company_name, phone, mobile, fax, address, is_primary)
        SELECT v_daido_id, 'Yasushi Noda', '野田 泰士', 'Sales/Purchase/Production Control Manager',
               'DAIDO MANUFACTURING (THAILAND) CO., LTD.', '(038) 891-545', '090-920-5893', '(038) 891-548',
               '283 Moo 7, Chachoengsao Factory, Pluakdaeng, Rayong 21140 Thailand', TRUE
        WHERE NOT EXISTS (SELECT 1 FROM customer_contacts WHERE customer_id = v_daido_id AND full_name = 'Yasushi Noda');
    END IF;

    -- CBC Manufacturing (Thailand) Co., Ltd.
    SELECT customer_id INTO v_cbc_id FROM customers
        WHERE customer_name ILIKE 'CBC MANUFACTURING%' AND is_deleted = FALSE LIMIT 1;
    IF v_cbc_id IS NULL THEN
        INSERT INTO customers (customer_code, division_id, customer_name, country, address, tax_id, phone)
        VALUES ('CUST-CBC', v_division_id, 'CBC MANUFACTURING (THAILAND) CO., LTD. (HEAD OFFICE)',
                'TH', '700/869 Moo 5, Amata City Chonburi Industrial Estate, Tambon Nongkakha, Amphur Panthong, Chonburi Province 20160 Thailand',
                '0105551090166', '038-185-300-4') ON CONFLICT DO NOTHING
        RETURNING customer_id INTO v_cbc_id;
    END IF;

    IF v_cbc_id IS NOT NULL THEN
        INSERT INTO customer_contacts (customer_id, full_name, full_name_local, title, department, company_name, email, phone, mobile, fax, address, is_primary)
        SELECT v_cbc_id, 'Masato Sanji', '三治 雅人', 'Supervisor', 'Sale & Marketing Department',
               'CBC MANUFACTURING (THAILAND) CO., LTD.', 'sanji@cbcmfg-thai.com', '038-185-300-4', '081-940-1232', '038-185-305',
               '700/869 Moo 5, Amata City Chonburi Industrial Estate, Tambon Nongkakha, Amphur Panthong, Chonburi Province 20160 Thailand', TRUE
        WHERE NOT EXISTS (SELECT 1 FROM customer_contacts WHERE customer_id = v_cbc_id AND full_name = 'Masato Sanji');
    END IF;
END $$;

-- Verify
SELECT c.customer_id, c.customer_name, COUNT(cc.contact_id) AS contacts
FROM customers c
LEFT JOIN customer_contacts cc ON cc.customer_id = c.customer_id AND cc.is_deleted = FALSE
WHERE c.customer_name ILIKE ANY (ARRAY['%FUTABA%','%DAIDO%','%CBC%'])
GROUP BY c.customer_id, c.customer_name
ORDER BY c.customer_name;

COMMIT;
