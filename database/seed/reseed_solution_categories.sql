-- ============================================================
-- Reseed solution_categories with 32 entries
-- - Add category_group + evaluation_profit_pct columns
-- - IT = 100%, OT/FA = 10%, Hardware/License/Other = 0%
-- ============================================================

BEGIN;

-- Add new columns
ALTER TABLE solution_categories
    ADD COLUMN IF NOT EXISTS category_group VARCHAR(50),
    ADD COLUMN IF NOT EXISTS evaluation_profit_pct NUMERIC(5,2) DEFAULT 0;

-- Backup deals' current solution_category_id mapping
CREATE TEMP TABLE _deal_sc_backup AS
SELECT d.deal_id, sc.category_name AS old_name
FROM deals d
LEFT JOIN solution_categories sc ON sc.category_id = d.solution_category_id;

CREATE TEMP TABLE _qh_sc_backup AS
SELECT qh.quotation_id, sc.category_name AS old_name
FROM quotation_headers qh
LEFT JOIN solution_categories sc ON sc.category_id = qh.solution_category_id;

-- Break FK then wipe
UPDATE deals SET solution_category_id = NULL;
UPDATE quotation_headers SET solution_category_id = NULL;
DELETE FROM solution_categories;
ALTER SEQUENCE solution_categories_category_id_seq RESTART WITH 1;

-- Reseed
INSERT INTO solution_categories
    (category_name, category_name_jp, category_name_th, category_group, evaluation_profit_pct, sort_order) VALUES
-- Information Technology (IT) — 100%
('Inventory Management System / WMS',                    '在庫管理システム/WMS',                    'ระบบบริหารคลังสินค้า / WMS',                                'Information Technology', 100, 10),
('Production Management System',                         '生産管理システム',                        'ระบบบริหารการผลิต',                                          'Information Technology', 100, 20),
('Production Scheduler',                                 '生産スケジューラー',                      'ระบบจัดตารางการผลิต',                                       'Information Technology', 100, 30),
('Inbound / Outbound Inspection System',                 '入出荷検品システム',                      'ระบบตรวจสอบสินค้าเข้า-ออก',                                  'Information Technology', 100, 40),
('Label Printing System',                                'ラベルプリントシステム',                  'ระบบพิมพ์ฉลาก',                                              'Information Technology', 100, 50),
('Unlock Control System',                                'ロック解除システム',                      'ระบบควบคุมการปลดล็อก',                                       'Information Technology', 100, 60),
('Weight Measurement Support System',                    '重量計測支援システム',                    'ระบบช่วยเหลือการวัดน้ำหนัก',                                  'Information Technology', 100, 70),
('Maintenance Spare Parts Inventory Management System',  'メンテナンス部品用在庫管理システム',      'ระบบบริหารคลังอะไหล่บำรุงรักษา',                              'Information Technology', 100, 80),
('Maintenance Management System',                        'メンテナンスシステム',                    'ระบบบริหารการบำรุงรักษา',                                    'Information Technology', 100, 90),
('Digital Picking System',                               'デジタルピッキングシステム',              'ระบบดิจิทัลพิคกิ้ง',                                          'Information Technology', 100, 100),
('Projection Picking System',                            'プロジェクションピッキングシステム',      'ระบบโปรเจคชั่นพิคกิ้ง',                                       'Information Technology', 100, 110),
('Electronic Kanban System (e-Kanban)',                  '電子カンバン',                            'ระบบคัมบังอิเล็กทรอนิกส์ (e-Kanban)',                         'Information Technology', 100, 120),
('i-Reporter',                                           'i-Reporter',                              'i-Reporter',                                                  'Information Technology', 100, 130),

-- Operation Technology (OT) — 10%
('Production Line Visualization / IoT',                  'プロダクションライン見える化/IoT',        'การแสดงผลสายการผลิต / IoT',                                  'Operation Technology',    10, 140),
('Energy Monitoring System',                             'エネルギー監視システム',                  'ระบบเฝ้าระวังพลังงาน',                                       'Operation Technology',    10, 150),
('Traceability System (Production Equipment)',           'トレーサビリティシステム(生産設備)',      'ระบบสืบย้อนกลับ (อุปกรณ์การผลิต)',                            'Operation Technology',    10, 160),
('Traceability System (Lot Management)',                 'トレーサビリティシステム(ロット管理)',    'ระบบสืบย้อนกลับ (การจัดการล็อต)',                             'Operation Technology',    10, 170),
('Smart Watch System',                                   'スマートウォッチシステム',                'ระบบสมาร์ทวอทช์',                                            'Operation Technology',    10, 180),
('Call System',                                          '呼び出しシステム',                        'ระบบเรียก',                                                   'Operation Technology',    10, 190),

-- Factory Automation (FA) — 10%
('AMR – Under-Ride Type',                                '無人搬送車-潜り込み式',                   'AMR – แบบลอดใต้',                                            'Factory Automation',      10, 200),
('AMR – Towing Type',                                    '無人搬送車-牽引型',                       'AMR – แบบลาก',                                                'Factory Automation',      10, 210),
('AMR – Conveyor Type',                                  '無人搬送車-コンベア型',                   'AMR – แบบสายพาน',                                            'Factory Automation',      10, 220),
('AMR – Forklift Type',                                  '無人搬送車-フォークリフト型',             'AMR – แบบฟอร์คลิฟท์',                                         'Factory Automation',      10, 230),
('Robot System',                                         'ロボットシステム',                        'ระบบหุ่นยนต์',                                                'Factory Automation',      10, 240),
('Labor-Saving Equipment / Inspection Equipment',        '省人化装置/検査装置',                     'อุปกรณ์ลดแรงงาน / อุปกรณ์ตรวจสอบ',                            'Factory Automation',      10, 250),
('Logistics Improvement Solutions',                      '物流改善ソリューション',                  'โซลูชันปรับปรุงโลจิสติกส์',                                   'Factory Automation',      10, 260),
('AGV',                                                  'AGV',                                     'AGV',                                                         'Factory Automation',      10, 270),

-- Hardware
('Hardware',                                             'ハードウエア販売',                        'ฮาร์ดแวร์',                                                  'Hardware',                 0, 280),

-- License
('Software License',                                     'ソフトウエアライセンス',                  'ซอฟต์แวร์ไลเซนส์',                                            'License',                  0, 290),

-- Other
('Other',                                                'その他',                                  'อื่นๆ',                                                      'Other',                    0, 990);

-- Remap deals & quotations by exact-name match
UPDATE deals d
SET solution_category_id = sc.category_id
FROM _deal_sc_backup b
JOIN solution_categories sc ON sc.category_name = b.old_name
   OR sc.category_name_jp = b.old_name
WHERE d.deal_id = b.deal_id;

UPDATE quotation_headers qh
SET solution_category_id = sc.category_id
FROM _qh_sc_backup b
JOIN solution_categories sc ON sc.category_name = b.old_name
   OR sc.category_name_jp = b.old_name
WHERE qh.quotation_id = b.quotation_id;

-- Verify
SELECT category_id, sort_order, category_group, evaluation_profit_pct,
       category_name, category_name_jp, LEFT(category_name_th, 25) AS th_name
FROM solution_categories
ORDER BY sort_order;

SELECT 'Unmapped deals' AS what, COUNT(*) AS cnt
FROM _deal_sc_backup b
LEFT JOIN solution_categories sc ON sc.category_name = b.old_name OR sc.category_name_jp = b.old_name
WHERE b.old_name IS NOT NULL AND sc.category_id IS NULL;

COMMIT;
