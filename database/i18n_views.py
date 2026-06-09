"""
Bulk i18n update for all PEGASUS ERP view files.
Replaces hardcoded English strings with _e() translation calls.
"""
import re
import os

BASE = r'C:\Users\R.Nozaki\Downloads\Pegasus_ERP_R1'

# ======== 1. Add new translation keys ========

new_keys_en = {
    # Common extras
    'all_statuses': 'All Statuses',
    'search_customer': 'Search customer...',
    'search_supplier': 'Search supplier...',
    'search_item': 'Search item code or name...',
    'all_warehouses': 'All Warehouses',
    'prev': '&laquo; Prev',
    'next': 'Next &raquo;',
    'showing_of': 'Showing %s-%s of %s',
    'sales': 'Sales',
    'purchasing': 'Purchasing',
    'inventory': 'Inventory',
    'accounting': 'Accounting',
    'hr': 'HR',
    'production': 'Production',
    'master': 'Master',
    'reports': 'Reports',
    'stock': 'Stock',

    # Pipeline
    'total_pipeline': 'Total Pipeline',
    'weighted_value': 'Weighted Value',
    'win_rate': 'Win Rate',
    'avg_deal_size': 'Avg Deal Size',
    'pipeline_deals': 'Pipeline Deals',
    'expected_close': 'Expected Close',
    'no_deals': 'No deals in pipeline.',
    'no_pipeline_available': 'No pipeline data available.',

    # Stock
    'avg_cost': 'Avg Cost',
    'total_value': 'Total Value',
    'no_stock_found': 'No stock records found.',

    # Warehouse
    'warehouse_code': 'Warehouse Code',
    'warehouse_name': 'Warehouse Name',
    'location': 'Location',
    'new_warehouse': '+ New Warehouse',
    'no_warehouses_found': 'No warehouses found.',

    # Accounting extras
    'period': 'Period',
    'from_date': 'From',
    'to_date': 'To',
    'account': 'Account',
    'all_accounts': 'All Accounts',
    'total_debit': 'Total Debit',
    'total_credit': 'Total Credit',
    'opening_balance': 'Opening Balance',
    'closing_balance': 'Closing Balance',
    'no_entries_found': 'No entries found.',
    'no_transactions_found': 'No transactions found.',
    'assets': 'Assets',
    'total_assets': 'Total Assets',
    'liabilities': 'Liabilities',
    'total_liabilities': 'Total Liabilities',
    'equity': 'Equity',
    'total_equity': 'Total Equity',
    'total_liabilities_equity': 'Total Liabilities & Equity',
    'as_of': 'As of %s',
    'period_label': 'Period: %s to %s',

    # AR/AP
    'new_invoice': '+ New Invoice',
    'new_payment': '+ New Payment',
    'no_invoices_found': 'No invoices found.',
    'no_payments_found': 'No payments found.',

    # HR extras
    'phone': 'Phone',
    'hire_date': 'Hire Date',
    'salary': 'Salary',
    'nationality': 'Nationality',
    'no_attendance_found': 'No attendance records found.',
    'no_leave_found': 'No leave requests found.',
    'employee': 'Employee',
    'all_employees': 'All Employees',
    'work_date': 'Work Date',
    'work_hours': 'Work Hours',
    'ot_hours': 'OT Hours',
    'new_leave': '+ New Leave Request',
    'leave_balance': 'Leave Balance',

    # Payroll
    'new_payroll': '+ Generate Payroll',
    'run_payroll': 'Run Payroll',
    'no_payroll_found': 'No payroll records found.',

    # Expense
    'category': 'Category',
    'receipt': 'Receipt',
    'mileage': 'Mileage',
    'no_claims_found': 'No expense claims found.',

    # Production
    'bom_no': 'BOM No',
    'parent_item': 'Parent Item',
    'component': 'Component',
    'qty_per': 'Qty per Unit',
    'new_bom': '+ New BOM',
    'no_bom_found': 'No BOM records found.',
    'no_mo_found': 'No manufacturing orders found.',
    'mrp_snapshot': 'MRP Snapshot',
    'run_mrp': 'Run MRP',
    'demand': 'Demand',
    'supply': 'Supply',
    'net_requirement': 'Net Requirement',
    'recommended_action': 'Recommended Action',
    'no_mrp_found': 'No MRP data found.',

    # Master extras
    'new_customer': '+ New Customer',
    'new_supplier': '+ New Supplier',
    'new_item': '+ New Item',
    'new_account': '+ New Account',
    'new_payment_term': '+ New Payment Term',
    'new_division': '+ New Division',
    'code': 'Code',
    'contact_person': 'Contact Person',
    'address': 'Address',
    'tax_id': 'Tax ID',
    'no_customers_found': 'No customers found.',
    'no_suppliers_found': 'No suppliers found.',
    'no_items_found': 'No items found.',
    'no_accounts_found': 'No accounts found.',
    'no_payment_terms_found': 'No payment terms found.',
    'no_divisions_found': 'No divisions found.',
    'division_code': 'Division Code',
    'division_name': 'Division Name',
    'country': 'Country',
    'item_type': 'Item Type',
    'unit': 'Unit',
    'cost_price': 'Cost Price',
    'sell_price': 'Sell Price',
    'installments': 'Installments',
    'term_name': 'Term Name',
    'due_days': 'Due Days',

    # Cashflow
    'cashflow': 'Cash Flow',
    'cf_actual': 'Cash Flow Actual',
    'cf_forecast': 'Cash Flow Forecast',
    'forecast': 'Forecast',

    # Reports
    'report_list': 'Report List',
    'select_report': 'Select a report to view',
}

new_keys_ja = {
    'all_statuses': '全ステータス',
    'search_customer': '顧客を検索...',
    'search_supplier': '仕入先を検索...',
    'search_item': '品目コードまたは名称を検索...',
    'all_warehouses': '全倉庫',
    'prev': '&laquo; 前へ',
    'next': '次へ &raquo;',
    'showing_of': '%s-%s / %s 件',
    'sales': '営業',
    'purchasing': '購買',
    'inventory': '在庫',
    'accounting': '会計',
    'hr': '人事',
    'production': '製造',
    'master': 'マスタ',
    'reports': 'レポート',
    'stock': '在庫',
    'total_pipeline': 'パイプライン合計',
    'weighted_value': '加重金額',
    'win_rate': '受注率',
    'avg_deal_size': '平均案件規模',
    'pipeline_deals': 'パイプライン案件',
    'expected_close': '予定クローズ日',
    'no_deals': 'パイプラインに案件がありません。',
    'no_pipeline_available': 'パイプラインデータがありません。',
    'avg_cost': '平均原価',
    'total_value': '合計金額',
    'no_stock_found': '在庫データがありません。',
    'warehouse_code': '倉庫コード',
    'warehouse_name': '倉庫名',
    'location': '所在地',
    'new_warehouse': '+ 新規倉庫',
    'no_warehouses_found': '倉庫データがありません。',
    'period': '期間',
    'from_date': '開始',
    'to_date': '終了',
    'account': '勘定',
    'all_accounts': '全勘定',
    'total_debit': '借方合計',
    'total_credit': '貸方合計',
    'opening_balance': '期首残高',
    'closing_balance': '期末残高',
    'no_entries_found': '仕訳データがありません。',
    'no_transactions_found': '取引データがありません。',
    'assets': '資産',
    'total_assets': '資産合計',
    'liabilities': '負債',
    'total_liabilities': '負債合計',
    'equity': '純資産',
    'total_equity': '純資産合計',
    'total_liabilities_equity': '負債・純資産合計',
    'as_of': '%s 現在',
    'period_label': '期間: %s ～ %s',
    'new_invoice': '+ 新規請求書',
    'new_payment': '+ 新規入金',
    'no_invoices_found': '請求書がありません。',
    'no_payments_found': '入金データがありません。',
    'phone': '電話',
    'hire_date': '入社日',
    'salary': '給与',
    'nationality': '国籍',
    'no_attendance_found': '勤怠データがありません。',
    'no_leave_found': '休暇申請がありません。',
    'employee': '従業員',
    'all_employees': '全従業員',
    'work_date': '勤務日',
    'work_hours': '勤務時間',
    'ot_hours': '残業時間',
    'new_leave': '+ 新規休暇申請',
    'leave_balance': '休暇残高',
    'new_payroll': '+ 給与計算実行',
    'run_payroll': '給与計算',
    'no_payroll_found': '給与データがありません。',
    'category': 'カテゴリ',
    'receipt': '領収書',
    'mileage': '走行距離',
    'no_claims_found': '経費申請がありません。',
    'bom_no': 'BOM番号',
    'parent_item': '親品目',
    'component': '構成部品',
    'qty_per': '使用数量',
    'new_bom': '+ 新規BOM',
    'no_bom_found': 'BOMデータがありません。',
    'no_mo_found': '製造指示がありません。',
    'mrp_snapshot': 'MRPスナップショット',
    'run_mrp': 'MRP計算',
    'demand': '需要',
    'supply': '供給',
    'net_requirement': '正味所要量',
    'recommended_action': '推奨アクション',
    'no_mrp_found': 'MRPデータがありません。',
    'new_customer': '+ 新規得意先',
    'new_supplier': '+ 新規仕入先',
    'new_item': '+ 新規品目',
    'new_account': '+ 新規勘定',
    'new_payment_term': '+ 新規支払条件',
    'new_division': '+ 新規事業部',
    'code': 'コード',
    'contact_person': '担当者',
    'address': '住所',
    'tax_id': '税番号',
    'no_customers_found': '得意先がありません。',
    'no_suppliers_found': '仕入先がありません。',
    'no_items_found': '品目がありません。',
    'no_accounts_found': '勘定科目がありません。',
    'no_payment_terms_found': '支払条件がありません。',
    'no_divisions_found': '事業部がありません。',
    'division_code': '事業部コード',
    'division_name': '事業部名',
    'country': '国',
    'item_type': '品目種別',
    'unit': '単位',
    'cost_price': '原価',
    'sell_price': '売価',
    'installments': '分割回数',
    'term_name': '条件名',
    'due_days': '支払日数',
    'cashflow': 'キャッシュフロー',
    'cf_actual': 'キャッシュフロー実績',
    'cf_forecast': 'キャッシュフロー予測',
    'forecast': '予測',
    'report_list': 'レポート一覧',
    'select_report': 'レポートを選択してください',
}

new_keys_th = {
    'all_statuses': 'ทุกสถานะ',
    'search_customer': 'ค้นหาลูกค้า...',
    'search_supplier': 'ค้นหาผู้จัดจำหน่าย...',
    'search_item': 'ค้นหารหัสหรือชื่อสินค้า...',
    'all_warehouses': 'ทุกคลัง',
    'prev': '&laquo; ก่อนหน้า',
    'next': 'ถัดไป &raquo;',
    'showing_of': 'แสดง %s-%s จาก %s',
    'sales': 'การขาย',
    'purchasing': 'การจัดซื้อ',
    'inventory': 'คลังสินค้า',
    'accounting': 'บัญชี',
    'hr': 'ทรัพยากรบุคคล',
    'production': 'การผลิต',
    'master': 'ข้อมูลหลัก',
    'reports': 'รายงาน',
    'stock': 'สต็อก',
    'total_pipeline': 'ยอดไปป์ไลน์ทั้งหมด',
    'weighted_value': 'มูลค่าถ่วงน้ำหนัก',
    'win_rate': 'อัตราชนะ',
    'avg_deal_size': 'ขนาดดีลเฉลี่ย',
    'pipeline_deals': 'ดีลไปป์ไลน์',
    'expected_close': 'คาดว่าปิด',
    'no_deals': 'ไม่มีดีลในไปป์ไลน์',
    'no_pipeline_available': 'ไม่มีข้อมูลไปป์ไลน์',
    'avg_cost': 'ต้นทุนเฉลี่ย',
    'total_value': 'มูลค่ารวม',
    'no_stock_found': 'ไม่พบข้อมูลสต็อก',
    'warehouse_code': 'รหัสคลัง',
    'warehouse_name': 'ชื่อคลัง',
    'location': 'ที่ตั้ง',
    'new_warehouse': '+ คลังใหม่',
    'no_warehouses_found': 'ไม่พบคลังสินค้า',
    'period': 'งวด',
    'from_date': 'จาก',
    'to_date': 'ถึง',
    'account': 'บัญชี',
    'all_accounts': 'ทุกบัญชี',
    'total_debit': 'รวมเดบิต',
    'total_credit': 'รวมเครดิต',
    'opening_balance': 'ยอมยกมา',
    'closing_balance': 'ยอดคงเหลือ',
    'no_entries_found': 'ไม่พบรายการ',
    'no_transactions_found': 'ไม่พบธุรกรรม',
    'assets': 'สินทรัพย์',
    'total_assets': 'รวมสินทรัพย์',
    'liabilities': 'หนี้สิน',
    'total_liabilities': 'รวมหนี้สิน',
    'equity': 'ส่วนของผู้ถือหุ้น',
    'total_equity': 'รวมส่วนของผู้ถือหุ้น',
    'total_liabilities_equity': 'รวมหนี้สินและส่วนของผู้ถือหุ้น',
    'as_of': 'ณ วันที่ %s',
    'period_label': 'งวด: %s ถึง %s',
    'new_invoice': '+ ใบแจ้งหนี้ใหม่',
    'new_payment': '+ รับชำระใหม่',
    'no_invoices_found': 'ไม่พบใบแจ้งหนี้',
    'no_payments_found': 'ไม่พบการชำระเงิน',
    'phone': 'โทรศัพท์',
    'hire_date': 'วันเริ่มงาน',
    'salary': 'เงินเดือน',
    'nationality': 'สัญชาติ',
    'no_attendance_found': 'ไม่พบข้อมูลการลงเวลา',
    'no_leave_found': 'ไม่พบการลา',
    'employee': 'พนักงาน',
    'all_employees': 'ทุกพนักงาน',
    'work_date': 'วันทำงาน',
    'work_hours': 'ชั่วโมงทำงาน',
    'ot_hours': 'ชั่วโมงล่วงเวลา',
    'new_leave': '+ ขอลาใหม่',
    'leave_balance': 'วันลาคงเหลือ',
    'new_payroll': '+ คำนวณเงินเดือน',
    'run_payroll': 'คำนวณเงินเดือน',
    'no_payroll_found': 'ไม่พบข้อมูลเงินเดือน',
    'category': 'หมวดหมู่',
    'receipt': 'ใบเสร็จ',
    'mileage': 'ระยะทาง',
    'no_claims_found': 'ไม่พบการเบิกค่าใช้จ่าย',
    'bom_no': 'เลขที่ BOM',
    'parent_item': 'สินค้าหลัก',
    'component': 'ส่วนประกอบ',
    'qty_per': 'จำนวนต่อหน่วย',
    'new_bom': '+ สูตรการผลิตใหม่',
    'no_bom_found': 'ไม่พบสูตรการผลิต',
    'no_mo_found': 'ไม่พบใบสั่งผลิต',
    'mrp_snapshot': 'MRP สแนปช็อต',
    'run_mrp': 'คำนวณ MRP',
    'demand': 'ความต้องการ',
    'supply': 'อุปทาน',
    'net_requirement': 'ความต้องการสุทธิ',
    'recommended_action': 'การดำเนินการที่แนะนำ',
    'no_mrp_found': 'ไม่พบข้อมูล MRP',
    'new_customer': '+ ลูกค้าใหม่',
    'new_supplier': '+ ผู้จัดจำหน่ายใหม่',
    'new_item': '+ สินค้าใหม่',
    'new_account': '+ บัญชีใหม่',
    'new_payment_term': '+ เงื่อนไขใหม่',
    'new_division': '+ หน่วยงานใหม่',
    'code': 'รหัส',
    'contact_person': 'ผู้ติดต่อ',
    'address': 'ที่อยู่',
    'tax_id': 'เลขประจำตัวผู้เสียภาษี',
    'no_customers_found': 'ไม่พบลูกค้า',
    'no_suppliers_found': 'ไม่พบผู้จัดจำหน่าย',
    'no_items_found': 'ไม่พบสินค้า',
    'no_accounts_found': 'ไม่พบบัญชี',
    'no_payment_terms_found': 'ไม่พบเงื่อนไขการชำระ',
    'no_divisions_found': 'ไม่พบหน่วยงาน',
    'division_code': 'รหัสหน่วยงาน',
    'division_name': 'ชื่อหน่วยงาน',
    'country': 'ประเทศ',
    'item_type': 'ประเภทสินค้า',
    'unit': 'หน่วย',
    'cost_price': 'ราคาทุน',
    'sell_price': 'ราคาขาย',
    'installments': 'งวดชำระ',
    'term_name': 'ชื่อเงื่อนไข',
    'due_days': 'จำนวนวันครบกำหนด',
    'cashflow': 'กระแสเงินสด',
    'cf_actual': 'กระแสเงินสดจริง',
    'cf_forecast': 'กระแสเงินสดพยากรณ์',
    'forecast': 'พยากรณ์',
    'report_list': 'รายการรายงาน',
    'select_report': 'เลือกรายงานเพื่อดู',
}


def append_keys(filepath, new_keys):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find existing keys to avoid duplicates
    existing = set(re.findall(r"'(\w+)'\s*=>", content))
    to_add = {k: v for k, v in new_keys.items() if k not in existing}

    if not to_add:
        print(f"  No new keys for {os.path.basename(filepath)}")
        return

    # Insert before the closing ];
    lines = []
    lines.append("\n    // ── Additional ──")
    for k, v in to_add.items():
        escaped = v.replace("'", "\\'")
        lines.append(f"    '{k}' => '{escaped}',")

    insert_text = '\n'.join(lines) + '\n'
    content = content.replace('\n];', insert_text + '];')

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"  Added {len(to_add)} keys to {os.path.basename(filepath)}")


print("=== Adding translation keys ===")
append_keys(os.path.join(BASE, 'lang', 'en.php'), new_keys_en)
append_keys(os.path.join(BASE, 'lang', 'ja.php'), new_keys_ja)
append_keys(os.path.join(BASE, 'lang', 'th.php'), new_keys_th)


# ======== 2. Update view files ========

# Map of exact string replacements per file
# Format: { 'file_path': [ (old, new), ... ] }

replacements = {}

def add(filepath, pairs):
    full = os.path.join(BASE, 'views', filepath)
    replacements[full] = pairs

# --- sales/quotations.php ---
add('sales/quotations.php', [
    ("$pageTitle = 'Quotations - PEGASUS ERP';", "$pageTitle = __('quotations') . ' - PEGASUS ERP';"),
    ("<h1 class=\"page-title\">Quotations</h1>", "<h1 class=\"page-title\"><?= _e('quotations') ?></h1>"),
    ("<a href=\"/dashboard\">Home</a>", "<a href=\"/dashboard\"><?= _e('home') ?></a>"),
    ("<a href=\"/sales\">Sales</a>", "<a href=\"/sales\"><?= _e('sales') ?></a>"),
    ("<span class=\"breadcrumb-current\">Quotations</span>", "<span class=\"breadcrumb-current\"><?= _e('quotations') ?></span>"),
    (">+ New Quotation</a>", "><?= _e('new_quotation') ?></a>"),
    ("<option value=\"\">All Statuses</option>", "<option value=\"\"><?= _e('all_statuses') ?></option>"),
    ("placeholder=\"Search customer...\">", "placeholder=\"<?= _e('search_customer') ?>\">"),
    (">Filter</button>", "><?= _e('filter') ?></button>"),
    (">Clear</a>", "><?= _e('clear') ?></a>"),
    ("<th class=\"sortable\">Quotation No</th>", "<th class=\"sortable\"><?= _e('quotation_no') ?></th>"),
    ("<th class=\"sortable\">Date</th>", "<th class=\"sortable\"><?= _e('date') ?></th>"),
    ("<th>Customer</th>", "<th><?= _e('customer') ?></th>"),
    ("<th>Project</th>", "<th><?= _e('project') ?></th>"),
    ("<th class=\"text-right\">Subtotal</th>", "<th class=\"text-right\"><?= _e('subtotal') ?></th>"),
    ("<th class=\"text-right\">VAT</th>", "<th class=\"text-right\"><?= _e('vat') ?></th>"),
    ("<th class=\"text-right\">Grand Total</th>", "<th class=\"text-right\"><?= _e('grand_total') ?></th>"),
    ("<th class=\"text-center\">Status</th>", "<th class=\"text-center\"><?= _e('status') ?></th>"),
    ("<th class=\"text-center\">Actions</th>", "<th class=\"text-center\"><?= _e('actions') ?></th>"),
    ("No quotations found.", "<?= _e('msg_no_quotations') ?>"),
    ("<span>Showing <?= $pagination['from'] ?>-<?= $pagination['to'] ?> of <?= $pagination['total'] ?></span>",
     "<span><?= __('showing_of', $pagination['from'], $pagination['to'], $pagination['total']) ?></span>"),
    (">&laquo; Prev</a>", "><?= __('prev') ?></a>"),
    (">Next &raquo;</a>", "><?= __('next') ?></a>"),
])

# --- sales/orders.php ---
add('sales/orders.php', [
    ("$pageTitle = 'Sales Orders - PEGASUS ERP';", "$pageTitle = __('sales_orders') . ' - PEGASUS ERP';"),
    ("<h1 class=\"page-title\">Sales Orders</h1>", "<h1 class=\"page-title\"><?= _e('sales_orders') ?></h1>"),
    ("<a href=\"/dashboard\">Home</a>", "<a href=\"/dashboard\"><?= _e('home') ?></a>"),
    ("<a href=\"/sales\">Sales</a>", "<a href=\"/sales\"><?= _e('sales') ?></a>"),
    ("<span class=\"breadcrumb-current\">Sales Orders</span>", "<span class=\"breadcrumb-current\"><?= _e('sales_orders') ?></span>"),
    (">+ New Sales Order</a>", "><?= _e('new_sales_order') ?></a>"),
    ("<option value=\"\">All Statuses</option>", "<option value=\"\"><?= _e('all_statuses') ?></option>"),
    ("placeholder=\"Search customer...\">", "placeholder=\"<?= _e('search_customer') ?>\">"),
    (">Filter</button>", "><?= _e('filter') ?></button>"),
    (">Clear</a>", "><?= _e('clear') ?></a>"),
    ("<th class=\"sortable\">SO No</th>", "<th class=\"sortable\"><?= _e('so_no') ?></th>"),
    ("<th class=\"sortable\">Date</th>", "<th class=\"sortable\"><?= _e('date') ?></th>"),
    ("<th>Customer</th>", "<th><?= _e('customer') ?></th>"),
    ("<th>Quotation Ref</th>", "<th><?= _e('quotation_ref') ?></th>"),
    ("<th class=\"text-right\">Subtotal</th>", "<th class=\"text-right\"><?= _e('subtotal') ?></th>"),
    ("<th class=\"text-right\">VAT</th>", "<th class=\"text-right\"><?= _e('vat') ?></th>"),
    ("<th class=\"text-right\">Grand Total</th>", "<th class=\"text-right\"><?= _e('grand_total') ?></th>"),
    ("<th class=\"text-center\">Status</th>", "<th class=\"text-center\"><?= _e('status') ?></th>"),
    ("<th class=\"text-center\">Actions</th>", "<th class=\"text-center\"><?= _e('actions') ?></th>"),
    ("No sales orders found.", "<?= _e('msg_no_orders') ?>"),
    ("<span>Showing <?= $pagination['from'] ?>-<?= $pagination['to'] ?> of <?= $pagination['total'] ?></span>",
     "<span><?= __('showing_of', $pagination['from'], $pagination['to'], $pagination['total']) ?></span>"),
    (">&laquo; Prev</a>", "><?= __('prev') ?></a>"),
    (">Next &raquo;</a>", "><?= __('next') ?></a>"),
])

# --- sales/pipeline.php ---
add('sales/pipeline.php', [
    ("$pageTitle = 'Sales Pipeline - PEGASUS ERP';", "$pageTitle = __('pipeline') . ' - PEGASUS ERP';"),
    ("<h1 class=\"page-title\">Sales Pipeline</h1>", "<h1 class=\"page-title\"><?= _e('pipeline') ?></h1>"),
    ("<a href=\"/dashboard\">Home</a>", "<a href=\"/dashboard\"><?= _e('home') ?></a>"),
    ("<a href=\"/sales\">Sales</a>", "<a href=\"/sales\"><?= _e('sales') ?></a>"),
    ("<span class=\"breadcrumb-current\">Pipeline</span>", "<span class=\"breadcrumb-current\"><?= _e('pipeline') ?></span>"),
    ("<div class=\"kpi-label\">Total Pipeline</div>", "<div class=\"kpi-label\"><?= _e('total_pipeline') ?></div>"),
    ("<div class=\"kpi-label\">Weighted Value</div>", "<div class=\"kpi-label\"><?= _e('weighted_value') ?></div>"),
    ("<div class=\"kpi-label\">Win Rate</div>", "<div class=\"kpi-label\"><?= _e('win_rate') ?></div>"),
    ("<div class=\"kpi-label\">Avg Deal Size</div>", "<div class=\"kpi-label\"><?= _e('avg_deal_size') ?></div>"),
    ("<h3 class=\"card-title\">Pipeline by Status</h3>", "<h3 class=\"card-title\"><?= _e('pipeline_by_status') ?></h3>"),
    ("No pipeline data available.", "<?= _e('no_pipeline_available') ?>"),
    ("<h3 class=\"card-title\">Pipeline Deals</h3>", "<h3 class=\"card-title\"><?= _e('pipeline_deals') ?></h3>"),
    ("<th>Customer</th>", "<th><?= _e('customer') ?></th>"),
    ("<th>Quotation</th>", "<th><?= _e('quotation_no') ?></th>"),
    ("<th>Project</th>", "<th><?= _e('project') ?></th>"),
    ("<th class=\"text-right\">Amount</th>", "<th class=\"text-right\"><?= _e('amount') ?></th>"),
    ("<th class=\"text-center\">Status</th>", "<th class=\"text-center\"><?= _e('status') ?></th>"),
    ("<th>Expected Close</th>", "<th><?= _e('expected_close') ?></th>"),
    ("<th class=\"text-center\">Actions</th>", "<th class=\"text-center\"><?= _e('actions') ?></th>"),
    ("No deals in pipeline.", "<?= _e('no_deals') ?>"),
])

# --- purchasing/orders.php ---
add('purchasing/orders.php', [
    ("$pageTitle = 'Purchase Orders - PEGASUS ERP';", "$pageTitle = __('purchase_orders') . ' - PEGASUS ERP';"),
    ("<h1 class=\"page-title\">Purchase Orders</h1>", "<h1 class=\"page-title\"><?= _e('purchase_orders') ?></h1>"),
    ("<a href=\"/dashboard\">Home</a>", "<a href=\"/dashboard\"><?= _e('home') ?></a>"),
    ("<a href=\"/purchasing\">Purchasing</a>", "<a href=\"/purchasing\"><?= _e('purchasing') ?></a>"),
    ("<span class=\"breadcrumb-current\">Purchase Orders</span>", "<span class=\"breadcrumb-current\"><?= _e('purchase_orders') ?></span>"),
    (">+ New Purchase Order</a>", "><?= _e('new_purchase_order') ?></a>"),
    ("<option value=\"\">All Statuses</option>", "<option value=\"\"><?= _e('all_statuses') ?></option>"),
    ("placeholder=\"Search supplier...\">", "placeholder=\"<?= _e('search_supplier') ?>\">"),
    (">Filter</button>", "><?= _e('filter') ?></button>"),
    (">Clear</a>", "><?= _e('clear') ?></a>"),
    ("<th class=\"sortable\">PO No</th>", "<th class=\"sortable\"><?= _e('po_no') ?></th>"),
    ("<th class=\"sortable\">Date</th>", "<th class=\"sortable\"><?= _e('date') ?></th>"),
    ("<th>Supplier</th>", "<th><?= _e('supplier') ?></th>"),
    ("<th class=\"text-right\">Subtotal</th>", "<th class=\"text-right\"><?= _e('subtotal') ?></th>"),
    ("<th class=\"text-right\">VAT</th>", "<th class=\"text-right\"><?= _e('vat') ?></th>"),
    ("<th class=\"text-right\">WHT</th>", "<th class=\"text-right\"><?= _e('wht') ?></th>"),
    ("<th class=\"text-right\">Net Total</th>", "<th class=\"text-right\"><?= _e('net_total') ?></th>"),
    ("<th class=\"text-center\">Status</th>", "<th class=\"text-center\"><?= _e('status') ?></th>"),
    ("<th class=\"text-center\">Actions</th>", "<th class=\"text-center\"><?= _e('actions') ?></th>"),
    ("No purchase orders found.", "<?= _e('msg_no_orders') ?>"),
    ("<span>Showing <?= $pagination['from'] ?>-<?= $pagination['to'] ?> of <?= $pagination['total'] ?></span>",
     "<span><?= __('showing_of', $pagination['from'], $pagination['to'], $pagination['total']) ?></span>"),
    (">&laquo; Prev</a>", "><?= __('prev') ?></a>"),
    (">Next &raquo;</a>", "><?= __('next') ?></a>"),
])

# --- inventory/stock.php ---
add('inventory/stock.php', [
    ("$pageTitle = 'Stock Management - PEGASUS ERP';", "$pageTitle = __('stock_management') . ' - PEGASUS ERP';"),
    ("<h1 class=\"page-title\">Stock Management</h1>", "<h1 class=\"page-title\"><?= _e('stock_management') ?></h1>"),
    ("<a href=\"/dashboard\">Home</a>", "<a href=\"/dashboard\"><?= _e('home') ?></a>"),
    ("<a href=\"/inventory\">Inventory</a>", "<a href=\"/inventory\"><?= _e('inventory') ?></a>"),
    ("<span class=\"breadcrumb-current\">Stock</span>", "<span class=\"breadcrumb-current\"><?= _e('stock') ?></span>"),
    ("<option value=\"\">All Warehouses</option>", "<option value=\"\"><?= _e('all_warehouses') ?></option>"),
    ("placeholder=\"Search item code or name...\">", "placeholder=\"<?= _e('search_item') ?>\">"),
    (">Filter</button>", "><?= _e('filter') ?></button>"),
    (">Clear</a>", "><?= _e('clear') ?></a>"),
    ("<th class=\"sortable\">Item Code</th>", "<th class=\"sortable\"><?= _e('item_code') ?></th>"),
    ("<th class=\"sortable\">Item Name</th>", "<th class=\"sortable\"><?= _e('item_name') ?></th>"),
    ("<th>Warehouse</th>", "<th><?= _e('warehouse') ?></th>"),
    ("<th class=\"text-right sortable\">On Hand</th>", "<th class=\"text-right sortable\"><?= _e('on_hand') ?></th>"),
    ("<th class=\"text-right\">Reserved</th>", "<th class=\"text-right\"><?= _e('reserved') ?></th>"),
    ("<th class=\"text-right\">Available</th>", "<th class=\"text-right\"><?= _e('available') ?></th>"),
    ("<th class=\"text-right\">Avg Cost</th>", "<th class=\"text-right\"><?= _e('avg_cost') ?></th>"),
    ("<th class=\"text-right\">Total Value</th>", "<th class=\"text-right\"><?= _e('total_value') ?></th>"),
    ("No stock records found.", "<?= _e('no_stock_found') ?>"),
    ("<span>Showing <?= $pagination['from'] ?>-<?= $pagination['to'] ?> of <?= $pagination['total'] ?></span>",
     "<span><?= __('showing_of', $pagination['from'], $pagination['to'], $pagination['total']) ?></span>"),
    (">&laquo; Prev</a>", "><?= __('prev') ?></a>"),
    (">Next &raquo;</a>", "><?= __('next') ?></a>"),
])

print("\n=== Updating view files ===")
updated = 0
for filepath, pairs in replacements.items():
    if not os.path.exists(filepath):
        print(f"  SKIP (not found): {filepath}")
        continue
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    changed = False
    for old, new in pairs:
        if old in content:
            content = content.replace(old, new)
            changed = True
    if changed:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        updated += 1
        print(f"  Updated: {os.path.basename(filepath)}")
    else:
        print(f"  No changes: {os.path.basename(filepath)}")

print(f"\nDone! Updated {updated} view files.")
