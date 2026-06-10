-- ============================================================
-- Reseed deal_statuses with the new 21-status list
-- ------------------------------------------------------------
-- WARNING: This clears deal_statuses entirely. To avoid FK
-- violation from deals.status_id, existing deals' status_id is
-- temporarily set to NULL, then (optionally) remapped by name
-- after the reseed.
-- ============================================================

BEGIN;

-- ① Preserve current status names per deal so we can remap later
CREATE TEMP TABLE _deal_status_backup AS
SELECT d.deal_id, s.status_name AS old_status_name
FROM deals d
LEFT JOIN deal_statuses s ON s.status_id = d.status_id;

-- ② Break FK references so we can delete freely
UPDATE deals SET status_id = NULL;

-- ③ Delete all existing statuses and reset PK sequence
DELETE FROM deal_statuses;
ALTER SEQUENCE deal_statuses_status_id_seq RESTART WITH 1;

-- ④ Insert the new canonical list
--    Colors chosen to roughly progress: gray → blue → teal → green; Lost/Hold = red/amber
INSERT INTO deal_statuses (status_name, status_name_jp, status_name_th, win_pct, sort_order, color) VALUES
-- Phase 1: Lead Generation
('Lead Identified',      'リード特定',            'ระบุลูกค้าเป้าหมาย',       5,   10, '#9E9E9E'),
('Appointment',          'アポイント',            'การนัดพบ',                10,  20, '#9E9E9E'),
-- Phase 2: Initial Contact
('Meeting Scheduled',    '面談設定済み',          'นัดประชุมแล้ว',            15,  30, '#64B5F6'),
('1st Meeting',          '初回面談',              'ประชุมครั้งแรก',           20,  40, '#64B5F6'),
('Needs Analysis',       'ヒアリング・課題分析',  'วิเคราะห์ความต้องการ',     25,  50, '#64B5F6'),
-- Phase 3: Proposal
('Solution Design',      'ソリューション設計',    'ออกแบบโซลูชัน',           30,  60, '#42A5F5'),
('Demo / Presentation',  'デモ・プレゼン',        'สาธิต/นำเสนอ',             35,  70, '#42A5F5'),
('Proposal',             '提案',                  'เสนอ',                    40,  80, '#42A5F5'),
-- Phase 4: Quotation & Negotiation
('RFQ Received',         '見積依頼受領',          'รับใบขอเสนอราคา',         45,  90, '#26A69A'),
('Quotation',            '見積提出',              'เสนอราคา',                50, 100, '#26A69A'),
('Quotation Reviewed',   '見積確認中',            'กำลังพิจารณาใบเสนอราคา',  55, 110, '#26A69A'),
('Verbal Approval',      '口頭承認',              'อนุมัติด้วยวาจา',          60, 120, '#26A69A'),
('Negotiation',          '交渉',                  'เจรจา',                   70, 130, '#26A69A'),
('Negotiation Final',    '最終交渉',              'เจรจาขั้นสุดท้าย',         75, 140, '#26A69A'),
-- Phase 5: Closing
('PO Received',          'PO受領',                'รับ PO',                   80, 150, '#66BB6A'),
('PO Confirmed',         'PO内容確認済み',        'ยืนยัน PO แล้ว',           85, 160, '#66BB6A'),
('Contract',             '契約',                  'สัญญา',                    90, 170, '#66BB6A'),
('Contract Signed',      '契約締結完了',          'ลงนามสัญญาแล้ว',          95, 180, '#66BB6A'),
('Closed Won',           '受注',                  'ปิดการขายได้',            100, 190, '#2E7D32'),
-- Special
('Lost',                 '失注',                  'สูญเสีย',                   0, 900, '#E53935'),
('On Hold',              '保留',                  'ระงับ',                     0, 910, '#FB8C00');

-- ⑤ Remap deals to new status_id by matching old_status_name → new status_name
--    (exact match on EN name). Falls back to NULL if old name isn't in the new list.
UPDATE deals d
SET status_id = ns.status_id
FROM _deal_status_backup b
JOIN deal_statuses ns ON ns.status_name = b.old_status_name
WHERE d.deal_id = b.deal_id;

-- ⑥ Show any deals whose old status didn't map (manual review may be needed)
SELECT b.old_status_name, COUNT(*) AS unmapped_deals
FROM _deal_status_backup b
LEFT JOIN deal_statuses ns ON ns.status_name = b.old_status_name
WHERE ns.status_id IS NULL
  AND b.old_status_name IS NOT NULL
GROUP BY b.old_status_name
ORDER BY unmapped_deals DESC;

-- ⑦ Verify final state
SELECT status_id, sort_order, win_pct, status_name, status_name_jp, status_name_th
FROM deal_statuses
ORDER BY sort_order, status_id;

COMMIT;
