-- ============================================================
-- PEGASUS ERP - CRM v2 Schema Updates
-- Adds: Project Management fields, standalone Activity Log, Win Rate stages
-- ============================================================

-- ── Update deal_statuses with progress/phase/stage from Win Rate Master ──
ALTER TABLE deal_statuses ADD COLUMN IF NOT EXISTS phase_description VARCHAR(200);
ALTER TABLE deal_statuses ADD COLUMN IF NOT EXISTS stage VARCHAR(50);

-- ── Update deals table with Project Management fields ──
ALTER TABLE deals ADD COLUMN IF NOT EXISTS first_contact_date DATE;
ALTER TABLE deals ADD COLUMN IF NOT EXISTS budget_status VARCHAR(20) DEFAULT 'No';
ALTER TABLE deals ADD COLUMN IF NOT EXISTS budget_amount NUMERIC(18,2) DEFAULT 0;
ALTER TABLE deals ADD COLUMN IF NOT EXISTS win_rate INT DEFAULT 0;
ALTER TABLE deals ADD COLUMN IF NOT EXISTS est_revenue NUMERIC(18,2) DEFAULT 0;
ALTER TABLE deals ADD COLUMN IF NOT EXISTS est_profit NUMERIC(18,2) DEFAULT 0;
ALTER TABLE deals ADD COLUMN IF NOT EXISTS eval_profit NUMERIC(18,2) DEFAULT 0;
ALTER TABLE deals ADD COLUMN IF NOT EXISTS next_action TEXT;
ALTER TABLE deals ADD COLUMN IF NOT EXISTS due_date DATE;
ALTER TABLE deals ADD COLUMN IF NOT EXISTS meeting_notes TEXT;
ALTER TABLE deals ADD COLUMN IF NOT EXISTS sales_person_name VARCHAR(100);

-- ── Update deal_activities for standalone Activity Log ──
-- Make deal_id optional (NULL = standalone activity)
ALTER TABLE deal_activities ALTER COLUMN deal_id DROP NOT NULL;
-- Add company/customer fields for standalone activities
ALTER TABLE deal_activities ADD COLUMN IF NOT EXISTS customer_id INT REFERENCES customers(customer_id);
ALTER TABLE deal_activities ADD COLUMN IF NOT EXISTS company_name VARCHAR(200);
ALTER TABLE deal_activities ADD COLUMN IF NOT EXISTS sales_person_name VARCHAR(100);
ALTER TABLE deal_activities ADD COLUMN IF NOT EXISTS sales_person_id INT REFERENCES employees(employee_id);

-- ── Update activity_categories to match Excel ──
-- Truncate and re-insert to match exact names from the Excel
DELETE FROM activity_categories;
INSERT INTO activity_categories (category_name, category_name_jp, category_name_th, icon, sort_order) VALUES
    ('Call (Phone)',                'TEL発信',              'โทร (โทรศัพท์)',          '📞', 1),
    ('Walk-in Visit',              '飛び込み訪問',          'เยี่ยมเยียน (Walk-in)',     '🚶', 2),
    ('Posting (Flyer Drop)',       'チラシ投函',            'แจกใบปลิว',              '📄', 3),
    ('Referral (Thai → Japanese)', '紹介(タイ→日本)',       'แนะนำ (ไทย→ญี่ปุ่น)',      '🤝', 4),
    ('Meeting Follow-up',          'フォローアップ',         'ติดตาม',                 '🔄', 5),
    ('Email / LINE Contact',       'メール/LINE連絡',      'อีเมล / LINE',            '📧', 6),
    ('Exhibition / Event',         '展示会・イベント',       'นิทรรศการ / อีเว้นท์',      '🎪', 7),
    ('Other',                      'その他',               'อื่นๆ',                   '📝', 8),
    ('Meeting',                    'ミーティング',          'ประชุม',                  '💼', 9);

-- ── Update solution_categories to match Excel (new naming) ──
-- Insert new categories if not exist
INSERT INTO solution_categories (category_name, category_name_jp, sort_order) VALUES
    ('1_Inventory Management / WMS',          '在庫管理システム/WMS', 1),
    ('2_Production Management System',        '生産管理システム', 2),
    ('3_Production Scheduler',                '生産スケジューラ', 3),
    ('4_Inbound & Outbound Inspection System','入出荷検品システム', 4),
    ('5_Label Print System',                  'ラベル印刷システム', 5),
    ('6_Equipment Lock Release System',       '設備ロック解除システム', 6),
    ('7_Weight Measurement Support System',   '重量計測支援システム', 7),
    ('8_Maintenance Parts Inventory System',  'メンテナンス部品在庫システム', 8),
    ('9_Maintenance Management System',       'メンテナンス管理システム', 9),
    ('10_Digital Picking System',             'デジタルピッキングシステム', 10),
    ('11_Projection Picking System',          'プロジェクションピッキングシステム', 11),
    ('12_Electronic Kanban',                  '電子カンバン', 12),
    ('13_i-Reporter (Digital Forms)',          'i-Reporter (電子帳票)', 13),
    ('14_Production Line Visualization / IoT','生産ライン見える化/IoT', 14),
    ('15_Energy Monitoring System',           'エネルギー監視システム', 15),
    ('16_Traceability System (Equipment)',    'トレーサビリティシステム(設備)', 16),
    ('17_Traceability System (Lot Management)','トレーサビリティシステム(ロット管理)', 17),
    ('18_Smart Watch System',                 'スマートウォッチシステム', 18),
    ('19_Call / Andon System',                '呼出し/あんどんシステム', 19),
    ('20_AGV - Under-ride Type',              'AGV (潜り込み型)', 20),
    ('21_AGV - Tow Type',                     'AGV (牽引型)', 21),
    ('22_AGV - Conveyor Type',                'AGV (コンベア型)', 22),
    ('23_AGV - Forklift Type',                'AGV (フォーク型)', 23),
    ('24_Robot System',                       'ロボットシステム', 24),
    ('25_Labor-saving / Inspection Equipment','省人化/検査機器', 25),
    ('26_Logistics Improvement Solution',     '物流改善ソリューション', 26),
    ('27_AGV (General)',                       'AGV (一般)', 27),
    ('28_Hardware Sales',                     'ハードウェア販売', 28),
    ('29_Software License Sales',             'ソフトウェアライセンス販売', 29),
    ('30_Unclassified / TBD',                '未分類/TBD', 30),
    ('99_Other',                              'その他', 31)
ON CONFLICT (category_name) DO UPDATE SET category_name_jp = EXCLUDED.category_name_jp, sort_order = EXCLUDED.sort_order;

-- ── Update deal_statuses with Win Rate stages ──
DELETE FROM deal_statuses;
INSERT INTO deal_statuses (status_name, status_name_jp, status_name_th, win_pct, sort_order, color, phase_description, stage) VALUES
    ('① Appointment',   'アポイント',     'การนัดพบ',       10,  1, '#90CAF9', 'Initial contact. Checking interest.', 'Lead'),
    ('② 1st Meeting',   '初回面談',       'ประชุมครั้งแรก',   20,  2, '#64B5F6', 'Needs confirmed. Problem identified.', 'Approach'),
    ('③ Proposal',      '提案',           'เสนอ',           40,  3, '#42A5F5', 'Proposal submitted. Under consideration.', 'Proposal'),
    ('④ Quotation',     '見積提出',       'เสนอราคา',       50,  4, '#FFA726', 'Quote sent. Awaiting internal approval.', 'Quote'),
    ('⑤ Negotiation',   '交渉',           'เจรจา',          70,  5, '#FF9800', 'Final negotiation. Terms being finalized.', 'Negotiation'),
    ('⑥ PO Received',   'PO受領',         'รับ PO',         80,  6, '#66BB6A', 'PO received. Contract in progress.', 'PO'),
    ('⑦ Contract',      '契約',           'สัญญา',          90,  7, '#43A047', 'Contract signed. Preparing delivery.', 'Contract'),
    ('⑧ Closed Won',    '受注',           'ปิดชนะ',         100, 8, '#2E7D32', 'Closed Won. Order confirmed.', 'Closed'),
    ('⑨ Lost',          '失注',           'สูญเสีย',         0,   9, '#E53935', 'Lost. Deal did not close.', 'Lost'),
    ('⑩ On Hold',       '保留',           'ระงับ',           0,  10, '#9E9E9E', 'On hold. Waiting for customer decision.', 'Hold');

-- ── Index for standalone activities ──
CREATE INDEX IF NOT EXISTS idx_deal_activities_date ON deal_activities(activity_date);
CREATE INDEX IF NOT EXISTS idx_deal_activities_sales ON deal_activities(sales_person_id);
