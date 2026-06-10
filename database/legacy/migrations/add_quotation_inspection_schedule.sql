-- ============================================================
-- Quotation Inspection Schedule (検収スケジュール)
-- Each quotation can have multi-phase inspection milestones,
-- typically aligned with payment_term_installments.
-- ============================================================
BEGIN;

CREATE TABLE IF NOT EXISTS quotation_inspection_schedule (
    qis_id              SERIAL          PRIMARY KEY,
    quotation_id        INT             NOT NULL REFERENCES quotation_headers(quotation_id) ON DELETE CASCADE,
    seq_no              SMALLINT        NOT NULL,                  -- 1, 2, 3 ...
    description         VARCHAR(200),                              -- e.g. "Design Phase", "Installation"
    percentage          NUMERIC(5,2)    NOT NULL DEFAULT 0,        -- % of total
    amount              NUMERIC(18,2)   NOT NULL DEFAULT 0,        -- THB amount
    expected_inspection_date DATE,                                 -- 検収予定日
    actual_inspection_date   DATE,                                 -- 実検収日
    status              VARCHAR(20)     NOT NULL DEFAULT 'PENDING'
                        CHECK (status IN ('PENDING','IN_PROGRESS','DELIVERED','INSPECTED','CANCELLED')),
    notes               TEXT,
    created_by          INT,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    UNIQUE (quotation_id, seq_no)
);
CREATE INDEX IF NOT EXISTS idx_qis_quotation ON quotation_inspection_schedule(quotation_id);
CREATE INDEX IF NOT EXISTS idx_qis_inspection_date ON quotation_inspection_schedule(expected_inspection_date);

COMMIT;
