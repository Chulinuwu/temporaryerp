-- ============================================================
-- Add payment term: 50% DP / 40% After Installation / 10% After Handover
-- All installments: Credit 30 days
-- ============================================================

BEGIN;

WITH new_term AS (
    INSERT INTO payment_terms (
        term_code, term_name_en, term_name_jp, term_name_th,
        installment_count, credit_days, display_order, notes
    ) VALUES (
        'DP50-INST40-HO10-N30',
        '50% DP upon PO / 40% After Installation / 10% After Handover (Credit 30 days each)',
        '前受50% (PO時) / 40% 据付完了後 / 10% 引渡後 (各 Credit 30日)',
        'มัดจำ 50% เมื่อได้รับ PO / 40% หลังติดตั้ง / 10% หลังส่งมอบ (เครดิต 30 วันแต่ละงวด)',
        3, 30, 100,
        'Progress billing: 50%/40%/10% with Net 30 on each milestone.'
    )
    RETURNING term_id
)
INSERT INTO payment_term_installments (
    term_id, seq_no, percentage, description_en, description_jp, description_th,
    trigger_type, credit_days
)
SELECT term_id, 1, 50.00,
       '50% Down Payment upon PO, Credit 30 days',
       '50% 前受金 (PO受領時), 30日以内',
       'มัดจำ 50% เมื่อได้รับ PO, เครดิต 30 วัน',
       'PO', 30
FROM new_term
UNION ALL
SELECT term_id, 2, 40.00,
       '40% After Installation, Credit 30 days',
       '40% 据付完了後, 30日以内',
       '40% หลังติดตั้ง, เครดิต 30 วัน',
       'INSTALLATION', 30
FROM new_term
UNION ALL
SELECT term_id, 3, 10.00,
       '10% After Handover, Credit 30 days',
       '10% 引渡完了後, 30日以内',
       '10% หลังส่งมอบ, เครดิต 30 วัน',
       'COMPLETION', 30
FROM new_term;

-- Verify
SELECT pt.term_id, pt.term_code, pt.term_name_en, pt.installment_count,
       pti.seq_no, pti.percentage, pti.trigger_type, pti.credit_days, pti.description_en
FROM payment_terms pt
JOIN payment_term_installments pti ON pti.term_id = pt.term_id
WHERE pt.term_code = 'DP50-INST40-HO10-N30'
ORDER BY pti.seq_no;

COMMIT;
