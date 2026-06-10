-- ============================================================
-- PEGASUS ERP - CRM Seed Data
-- ============================================================

-- ── Deal Statuses / Possibility (21 stages) ──
-- Phase 1: Lead Generation
INSERT INTO deal_statuses (status_name, status_name_jp, status_name_th, win_pct, sort_order, color) VALUES
    ('Lead Identified',     'リード特定',           'ระบุลูกค้าเป้าหมาย',       5,   1,  '#B0BEC5'),
    ('Appointment',         'アポイント',            'การนัดพบ',                10,   2,  '#90A4AE'),
-- Phase 2: Initial Contact
    ('Meeting Scheduled',   '面談設定済み',          'นัดประชุมแล้ว',            15,   3,  '#78909C'),
    ('1st Meeting',         '初回面談',             'ประชุมครั้งแรก',            20,   4,  '#607D8B'),
    ('Needs Analysis',      'ヒアリング・課題分析',   'วิเคราะห์ความต้องการ',      25,   5,  '#546E7A'),
-- Phase 3: Proposal
    ('Solution Design',     'ソリューション設計',     'ออกแบบโซลูชัน',           30,   6,  '#FF9800'),
    ('Demo / Presentation', 'デモ・プレゼン',        'สาธิต/นำเสนอ',            35,   7,  '#FB8C00'),
    ('Proposal',            '提案',                'เสนอ',                    40,   8,  '#F57C00'),
-- Phase 4: Quotation & Negotiation
    ('RFQ Received',        '見積依頼受領',          'รับใบขอเสนอราคา',         45,   9,  '#FFC107'),
    ('Quotation',           '見積提出',             'เสนอราคา',                50,  10,  '#FFB300'),
    ('Quotation Reviewed',  '見積確認中',            'กำลังพิจารณาใบเสนอราคา',   55,  11,  '#FFA000'),
    ('Verbal Approval',     '口頭承認',             'อนุมัติด้วยวาจา',          60,  12,  '#FF8F00'),
    ('Negotiation',         '交渉',                'เจรจา',                   70,  13,  '#4CAF50'),
    ('Negotiation Final',   '最終交渉',             'เจรจาขั้นสุดท้าย',         75,  14,  '#43A047'),
-- Phase 5: Closing
    ('PO Received',         'PO受領',              'รับ PO',                  80,  15,  '#2196F3'),
    ('PO Confirmed',        'PO内容確認済み',        'ยืนยัน PO แล้ว',          85,  16,  '#1E88E5'),
    ('Contract',            '契約',                'สัญญา',                   90,  17,  '#1976D2'),
    ('Contract Signed',     '契約締結完了',          'ลงนามสัญญาแล้ว',          95,  18,  '#1565C0'),
    ('Closed Won',          '受注',                'ปิดการขายได้',            100,  19,  '#388E3C'),
-- Special
    ('Lost',                '失注',                'สูญเสีย',                  0,  20,  '#F44336'),
    ('On Hold',             '保留',                'ระงับ',                    0,  21,  '#9E9E9E')
ON CONFLICT (status_name) DO NOTHING;

-- ── Activity Categories (from CRM) ──
INSERT INTO activity_categories (category_name, category_name_jp, category_name_th, icon, sort_order) VALUES
    ('Call',        '電話',         'โทรศัพท์',      '📞', 1),
    ('Meeting',     'ミーティング',  'ประชุม',        '🤝', 2),
    ('Email',       'メール',       'อีเมล',         '📧', 3),
    ('Visit',       '訪問',         'เยี่ยมเยียน',    '🏢', 4),
    ('Proposal',    '提案',         'เสนอราคา',      '📋', 5),
    ('Demo',        'デモ',         'สาธิต',         '💻', 6),
    ('Negotiation', '交渉',         'เจรจา',         '💬', 7),
    ('Follow-up',   'フォローアップ', 'ติดตาม',        '🔄', 8),
    ('Other',       'その他',       'อื่นๆ',          '📝', 9)
ON CONFLICT (category_name) DO NOTHING;

-- ── Solution Categories (from Excel quotation list - actual values) ──
-- These are inserted via import_quotations.py dynamically
