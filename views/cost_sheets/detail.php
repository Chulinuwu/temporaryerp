<?php
/**
 * PEGASUS ERP - Cost Sheet Detail (原価算出 詳細)
 * Variables: $sheet, $items
 */
$s = $sheet;
$statusLabels = ['DRAFT'=>__('status_draft'), 'CONFIRMED'=>__('status_approved'), 'LINKED'=>__('linked'), 'DELETED'=>__('status_deleted')];
$statusClasses = ['DRAFT'=>'badge-draft', 'CONFIRMED'=>'badge-approved', 'LINKED'=>'badge-open', 'DELETED'=>'badge-rejected'];

$costTotal = 0;
$costCategories = [];
foreach ($items as $ci) {
    if (empty($ci['is_category_row'])) $costTotal += floatval($ci['total_amount'] ?? 0);
    if (!empty($ci['category']) && !isset($costCategories[$ci['category']])) {
        $costCategories[$ci['category']] = 0;
    }
    if (!empty($ci['category']) && empty($ci['is_category_row'])) {
        $costCategories[$ci['category']] += floatval($ci['total_amount'] ?? 0);
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($s['sheet_no']) ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/cost-sheets"><?= __('cost_sheet_list') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= e($s['sheet_no']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/cost-sheets" class="btn btn-cancel"><?= __('back') ?></a>
    </div>
</div>

<!-- ===== Sheet Header Info ===== -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:16px 20px;">
        <form method="POST" action="/cost-sheets/<?= e($s['cost_sheet_id']) ?>">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                <div>
                    <h2 style="margin:0;font-size:16px;color:#003366;"><?= __('cost_breakdown') ?></h2>
                    <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px;">
                        <?= e($s['sheet_no']) ?>
                        <?php if (!empty($s['source_file'])): ?>
                            &mdash; &#128196; <?= e($s['source_file']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge <?= $statusClasses[$s['status']] ?? 'badge-draft' ?>" style="font-size:12px;padding:4px 12px;">
                    <?= $statusLabels[$s['status']] ?? $s['status'] ?>
                </span>
            </div>

            <table style="width:100%;border-collapse:collapse;font-size:13px;" class="budget-info">
                <tr>
                    <td class="lbl" style="width:100px;"><?= __('cost_sheet_name') ?></td>
                    <td class="val" colspan="3">
                        <input type="text" name="sheet_name" class="form-input" value="<?= e($s['sheet_name']) ?>" style="font-weight:600;">
                    </td>
                    <td class="lbl" style="width:100px;"><?= __('status') ?></td>
                    <td class="val" style="width:120px;">
                        <select name="status" class="form-input">
                            <option value="DRAFT" <?= $s['status'] === 'DRAFT' ? 'selected' : '' ?>><?= __('status_draft') ?></option>
                            <option value="CONFIRMED" <?= $s['status'] === 'CONFIRMED' ? 'selected' : '' ?>><?= __('status_approved') ?></option>
                            <option value="LINKED" <?= $s['status'] === 'LINKED' ? 'selected' : '' ?>><?= __('linked') ?></option>
                            <option value="DELETED" <?= $s['status'] === 'DELETED' ? 'selected' : '' ?>><?= __('status_deleted') ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="lbl"><?= __('customer') ?></td>
                    <td class="val">
                        <select name="customer_id" class="form-input">
                            <option value="">-</option>
                            <?php foreach (($customers ?? []) as $c): ?>
                                <option value="<?= e($c['customer_id']) ?>" <?= (string)$s['customer_id'] === (string)$c['customer_id'] ? 'selected' : '' ?>>
                                    <?= e($c['customer_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="lbl" style="width:100px;"><?= __('quotation_ref') ?></td>
                    <td class="val">
                        <?php if (!empty($s['quotation_no'])): ?>
                            <a href="/sales/quotations"><?= e($s['quotation_no']) ?></a>
                            <span style="font-size:11px;color:var(--color-text-muted);margin-left:4px;"><?= e($s['qt_project_name'] ?? '') ?></span>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td class="lbl">PJ</td>
                    <td class="val">
                        <?php if (!empty($s['pj_no'])): ?>
                            <a href="/projects/<?= e($s['project_id']) ?>"><?= e($s['pj_no']) ?></a>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="lbl"><?= __('cost_total_label') ?></td>
                    <td class="val" style="font-weight:700;font-size:16px;color:#003366;"><?= formatMoney($costTotal) ?></td>
                    <td class="lbl">Items</td>
                    <td class="val"><?= count($items) ?></td>
                    <td class="lbl"><?= __('notes') ?></td>
                    <td class="val">
                        <input type="text" name="notes" class="form-input" value="<?= e($s['notes'] ?? '') ?>" style="font-size:12px;">
                    </td>
                </tr>
            </table>

            <div style="margin-top:10px;text-align:right;">
                <button type="submit" class="btn btn-primary btn-sm"><?= __('save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Action Buttons ===== -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 20px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <!-- Excel Import -->
        <form method="POST" action="/cost-sheets/<?= e($s['cost_sheet_id']) ?>/import"
              enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <label class="btn btn-cancel btn-sm" style="cursor:pointer;margin:0;height:32px;display:flex;align-items:center;gap:4px;">
                <span>&#128196;</span> <?= __('import_excel') ?>
                <input type="file" name="cost_file" accept=".xlsx,.xls"
                       style="display:none;" onchange="this.form.submit();">
            </label>
        </form>

        <!-- Import from Quotation -->
        <button type="button" class="btn btn-cancel btn-sm" style="height:32px;"
                onclick="openQtModal()">
            &#128206; <?= __('import_from_quotation') ?>
        </button>

        <!-- Add category row -->
        <form method="POST" action="/cost-sheets/<?= e($s['cost_sheet_id']) ?>/items" style="display:inline;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="is_category_row" value="1">
            <input type="text" name="category" class="form-input" style="width:180px;height:32px;font-size:12px;" placeholder="<?= __('add_category') ?>..." required>
            <input type="hidden" name="description" value="">
            <button type="submit" class="btn btn-cancel btn-sm" style="height:32px;">+ <?= __('cost_category') ?></button>
        </form>

        <!-- Spacer -->
        <div style="flex:1;"></div>

        <!-- Category summary (scrollable) -->
        <div style="font-size:10px;color:var(--color-text-muted);max-width:600px;overflow-x:auto;white-space:nowrap;">
            <?php foreach ($costCategories as $cat => $catTotal): ?>
                <span style="margin-left:8px;"><strong><?= e(substr($cat, 0, 25)) ?></strong>: <?= formatMoney($catTotal) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ===== Add Item Form ===== -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="POST" action="/cost-sheets/<?= e($s['cost_sheet_id']) ?>/items">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div style="display:grid;grid-template-columns:130px 1fr 90px 80px 90px 70px 60px;gap:8px;align-items:end;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:11px;"><?= __('cost_category') ?></label>
                    <input type="text" name="category" class="form-input" list="dl-categories">
                    <datalist id="dl-categories">
                        <?php foreach (array_keys($costCategories) as $cat): ?>
                            <option value="<?= e($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:11px;"><?= __('description') ?></label>
                    <input type="text" name="description" class="form-input" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:11px;"><?= __('unit_price') ?></label>
                    <input type="number" name="unit_price" class="form-input" step="0.01">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:11px;"><?= __('quantity') ?></label>
                    <input type="number" name="quantity" class="form-input" step="0.01">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:11px;"><?= __('supplier') ?></label>
                    <input type="text" name="supplier" class="form-input">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:11px;"><?= __('unit') ?></label>
                    <input type="text" name="unit" class="form-input" placeholder="Set">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="height:36px;">+</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Cost Items Table ===== -->
<div class="card">
    <div class="table-responsive">
        <table class="data-table" style="font-size:12px;">
            <thead>
                <tr>
                    <th style="width:35px;">#</th>
                    <th style="width:130px;"><?= __('cost_category') ?></th>
                    <th><?= __('description') ?></th>
                    <th style="width:100px;"><?= __('supplier') ?></th>
                    <th class="text-right" style="width:100px;"><?= __('unit_price') ?></th>
                    <th class="text-right" style="width:70px;"><?= __('quantity') ?></th>
                    <th class="text-right" style="width:120px;"><?= __('total') ?> (Baht)</th>
                    <th style="width:50px;"><?= __('unit') ?></th>
                    <th style="width:35px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)):
                    foreach ($items as $ci):
                        if (!empty($ci['is_category_row'])):
                ?>
                <tr style="background:#e8f0fe;font-weight:700;">
                    <td></td>
                    <td colspan="5" style="color:#003366;font-size:13px;"><?= e($ci['description'] ?? $ci['category'] ?? '') ?></td>
                    <td class="text-right" style="color:#003366;">
                        <?= isset($costCategories[$ci['category']]) ? formatMoney($costCategories[$ci['category']]) : '' ?>
                    </td>
                    <td></td>
                    <td class="text-center">
                        <form method="POST" action="/cost-sheets/<?= e($s['cost_sheet_id']) ?>/items/<?= e($ci['cost_item_id']) ?>/delete" style="display:inline;">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')"
                                    style="background:none;border:none;cursor:pointer;font-size:12px;color:#F44336;">&#128465;</button>
                        </form>
                    </td>
                </tr>
                <?php   else: ?>
                <tr>
                    <td class="text-center" style="color:var(--color-text-muted);"><?= e($ci['line_no']) ?></td>
                    <td style="font-size:11px;color:var(--color-text-muted);"><?= e($ci['category'] ?? '') ?></td>
                    <td>
                        <?= e($ci['description'] ?? '') ?>
                        <?php if (!empty($ci['brand'])): ?>
                            <span style="font-size:10px;color:#888;margin-left:4px;">[<?= e($ci['brand']) ?>]</span>
                        <?php endif; ?>
                        <?php if (!empty($ci['lead_time'])): ?>
                            <span style="font-size:10px;color:#999;margin-left:2px;">(<?= e($ci['lead_time']) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:11px;"><?= e($ci['supplier'] ?? '') ?></td>
                    <td class="text-right"><?= floatval($ci['unit_price'] ?? 0) > 0 ? number_format($ci['unit_price'], 2) : '' ?></td>
                    <td class="text-right"><?= floatval($ci['quantity'] ?? 0) > 0 ? number_format($ci['quantity'], 0) : '' ?></td>
                    <td class="text-right" style="font-weight:600;"><?= formatMoney($ci['total_amount'] ?? 0) ?></td>
                    <td style="font-size:11px;"><?= e($ci['unit'] ?? '') ?></td>
                    <td class="text-center">
                        <form method="POST" action="/cost-sheets/<?= e($s['cost_sheet_id']) ?>/items/<?= e($ci['cost_item_id']) ?>/delete" style="display:inline;">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')"
                                    style="background:none;border:none;cursor:pointer;font-size:12px;color:#F44336;">&#128465;</button>
                        </form>
                    </td>
                </tr>
                <?php   endif;
                    endforeach;
                else: ?>
                <tr><td colspan="9" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= __('no_cost_items') ?></td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700;background:#f5f7fa;">
                    <td colspan="6" class="text-right" style="font-size:13px;"><?= __('cost_total_label') ?></td>
                    <td class="text-right" style="color:#003366;font-size:14px;"><?= formatMoney($costTotal) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- ===== Quotation Modal ===== -->
<div id="qt-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;justify-content:center;align-items:center;">
    <div style="background:#fff;border-radius:12px;width:700px;max-height:80vh;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding:16px 20px;border-bottom:1px solid var(--color-border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;font-size:15px;"><?= __('import_from_quotation') ?></h3>
            <button onclick="closeQtModal()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div style="padding:16px 20px;">
            <input type="text" id="qt-search" class="form-input" placeholder="<?= __('search_quotation_placeholder') ?>"
                   onkeyup="searchQt()" style="margin-bottom:12px;">
            <div id="qt-results" style="max-height:400px;overflow-y:auto;"></div>
        </div>
    </div>
</div>

<form id="form-import-qt" method="POST" action="/cost-sheets/<?= e($s['cost_sheet_id']) ?>/from-quotation" style="display:none;">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="quotation_id" id="import-qt-id">
</form>

<style>
.budget-info td { padding:5px 8px; border-bottom:1px solid #eee; }
.budget-info .lbl { color:var(--color-text-muted); font-size:12px; white-space:nowrap; }
.budget-info .val { font-size:13px; }
</style>

<script>
function openQtModal() {
    document.getElementById('qt-modal').style.display = 'flex';
    document.getElementById('qt-search').value = '';
    searchQt();
    setTimeout(function() { document.getElementById('qt-search').focus(); }, 100);
}
function closeQtModal() { document.getElementById('qt-modal').style.display = 'none'; }

var _qtTimer = null;
function searchQt() {
    clearTimeout(_qtTimer);
    _qtTimer = setTimeout(function() {
        var q = document.getElementById('qt-search').value;
        fetch('/cost-sheets/<?= e($s['cost_sheet_id']) ?>/quotations/search?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) { renderQtResults(data); });
    }, 300);
}

function renderQtResults(data) {
    var box = document.getElementById('qt-results');
    if (!data || data.length === 0) {
        box.innerHTML = '<div style="text-align:center;padding:30px;color:var(--color-text-muted);"><?= __("no_data") ?></div>';
        return;
    }
    var html = '<table class="data-table" style="font-size:12px;"><thead><tr>'
        + '<th>QT No.</th><th><?= __("project_name") ?></th><th class="text-right"><?= __("amount") ?></th>'
        + '<th class="text-right">Lines</th><th style="width:80px;"></th></tr></thead><tbody>';
    data.forEach(function(qt) {
        html += '<tr>'
            + '<td style="font-weight:600;">' + (qt.quotation_no || '') + '</td>'
            + '<td>' + (qt.project_name || '') + '<div style="font-size:10px;color:#888;">' + (qt.customer_name || '') + '</div></td>'
            + '<td class="text-right">' + Number(qt.grand_total_thb || 0).toLocaleString('en', {minimumFractionDigits:2}) + '</td>'
            + '<td class="text-right">' + (qt.line_count || 0) + '</td>'
            + '<td class="text-center"><button type="button" class="btn btn-primary btn-sm" style="font-size:11px;padding:4px 10px;" '
            + 'onclick="doImportQt(' + qt.quotation_id + ')"><?= __("select") ?></button></td></tr>';
    });
    html += '</tbody></table>';
    box.innerHTML = html;
}

function doImportQt(qtId) {
    if (!confirm('<?= __("confirm_import_quotation") ?>')) return;
    document.getElementById('import-qt-id').value = qtId;
    document.getElementById('form-import-qt').submit();
}
</script>
