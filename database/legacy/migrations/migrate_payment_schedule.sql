-- ============================================================
-- PEGASUS ERP - Payment Schedule Migration
-- Add dynamic payment schedules to projects
-- Register 4-installment payment term (DP50-MILE4)
-- ============================================================

-- 1. Insert new 4-milestone payment term
INSERT INTO payment_terms (term_code, division_id, term_name_en, term_name_jp, term_name_th, installment_count, credit_days, display_order, notes, created_by)
VALUES ('DP50-MILE4', 1,
        '4-Milestone (50/20/20/10)',
        E'4\u6BB5\u968E\u6255\u3044(50/20/20/10)',
        E'4 \u0E07\u0E27\u0E14 (50/20/20/10)',
        4, 7, 10,
        '50% DP upon PO Credit 7days, 20% after Design Credit 7days, 20% at Installation Credit 7days, 10% after Completion Credit 30days',
        1)
ON CONFLICT DO NOTHING;

-- 2. Insert installment details for DP50-MILE4
INSERT INTO payment_term_installments (term_id, seq_no, percentage, description_en, description_jp, description_th, trigger_type, credit_days)
SELECT t.term_id, s.seq_no, s.pct, s.desc_en, s.desc_jp, s.desc_th, s.trig, s.days
FROM payment_terms t
CROSS JOIN (VALUES
    (1, 50.00,
     'DP upon PO',
     E'PO\u767A\u884C\u6642\u524D\u6255\u3044',
     E'\u0E21\u0E31\u0E14\u0E08\u0E33\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2D\u0E2D\u0E01 PO',
     'PO', 7),
    (2, 20.00,
     'After Design Finished and Before arranging parts',
     E'\u8A2D\u8A08\u5B8C\u4E86\u5F8C\u30FB\u90E8\u54C1\u624B\u914D\u524D',
     E'\u0E2B\u0E25\u0E31\u0E07\u0E2D\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E41\u0E25\u0E30\u0E01\u0E48\u0E2D\u0E19\u0E08\u0E31\u0E14\u0E0B\u0E37\u0E49\u0E2D\u0E2D\u0E30\u0E44\u0E2B\u0E25\u0E48',
     'DESIGN', 7),
    (3, 20.00,
     'When installing the product (Warranty Start)',
     E'\u88FD\u54C1\u8A2D\u7F6E\u6642\uFF08\u4FDD\u8A3C\u958B\u59CB\uFF09',
     E'\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E15\u0E34\u0E14\u0E15\u0E31\u0E49\u0E07\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32 (\u0E40\u0E23\u0E34\u0E48\u0E21\u0E23\u0E31\u0E1A\u0E1B\u0E23\u0E30\u0E01\u0E31\u0E19)',
     'INSTALLATION', 7),
    (4, 10.00,
     'After Finished installation',
     E'\u8A2D\u7F6E\u5B8C\u4E86\u5F8C',
     E'\u0E2B\u0E25\u0E31\u0E07\u0E15\u0E34\u0E14\u0E15\u0E31\u0E49\u0E07\u0E40\u0E2A\u0E23\u0E47\u0E08',
     'COMPLETION', 30)
) AS s(seq_no, pct, desc_en, desc_jp, desc_th, trig, days)
WHERE t.term_code = 'DP50-MILE4'
ON CONFLICT (term_id, seq_no) DO NOTHING;

-- 3. Insert 3-milestone payment term (50/40/10) - all Credit 30 days
INSERT INTO payment_terms (term_code, division_id, term_name_en, term_name_jp, term_name_th, installment_count, credit_days, display_order, notes, created_by)
VALUES ('DP50-MILE3B', 1,
        '3-Milestone (50/40/10)',
        E'3\u6BB5\u968E\u6255\u3044(50/40/10)',
        E'3 \u0E07\u0E27\u0E14 (50/40/10)',
        3, 30, 11,
        '50% DP upon PO Credit 30days, 40% after Installation Credit 30days, 10% after Inspection Credit 30days',
        1)
ON CONFLICT DO NOTHING;

-- 4. Insert installment details for DP50-MILE3B
INSERT INTO payment_term_installments (term_id, seq_no, percentage, description_en, description_jp, description_th, trigger_type, credit_days)
SELECT t.term_id, s.seq_no, s.pct, s.desc_en, s.desc_jp, s.desc_th, s.trig, s.days
FROM payment_terms t
CROSS JOIN (VALUES
    (1, 50.00,
     'DP upon PO',
     E'PO\u767A\u884C\u6642\u524D\u6255\u3044',
     E'\u0E21\u0E31\u0E14\u0E08\u0E33\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2D\u0E2D\u0E01 PO',
     'PO', 30),
    (2, 40.00,
     'After installation',
     E'\u8A2D\u7F6E\u5B8C\u4E86\u5F8C',
     E'\u0E2B\u0E25\u0E31\u0E07\u0E15\u0E34\u0E14\u0E15\u0E31\u0E49\u0E07',
     'INSTALLATION', 30),
    (3, 10.00,
     'After finished inspection',
     E'\u691C\u53CE\u5B8C\u4E86\u5F8C',
     E'\u0E2B\u0E25\u0E31\u0E07\u0E15\u0E23\u0E27\u0E08\u0E23\u0E31\u0E1A\u0E40\u0E2A\u0E23\u0E47\u0E08',
     'COMPLETION', 30)
) AS s(seq_no, pct, desc_en, desc_jp, desc_th, trig, days)
WHERE t.term_code = 'DP50-MILE3B'
ON CONFLICT (term_id, seq_no) DO NOTHING;
