<?php
/**
 * PEGASUS ERP - Quotation List
 * Supports project_code grouping: same project_code = 1 aggregated row (expandable)
 * Variables: $quotations, $filters, $pagination
 */
$pageTitle = __('quotations') . ' - PEGASUS ERP';

// ── Sort helper ──
$currentSort = $sort ?? 'date';
$currentDir  = $dir ?? 'DESC';

function sortUrl($col) {
    global $currentSort, $currentDir, $filters;
    $newDir = ($currentSort === $col && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'sort'        => $col,
        'dir'         => $newDir,
        'status'      => $filters['status'] ?? '',
        'possibility' => $filters['possibility'] ?? '',
        'date_from'   => $filters['date_from'] ?? '',
        'date_to'     => $filters['date_to'] ?? '',
        'customer'    => $filters['customer'] ?? '',
        'group_by'    => $filters['group_by'] ?? '',
    ]);
    return '/sales/quotations?' . http_build_query($params);
}

function sortIcon($col) {
    global $currentSort, $currentDir;
    if ($currentSort !== $col) return '<span style="color:#ccc;font-size:10px;">&#9650;&#9660;</span>';
    return $currentDir === 'ASC'
        ? '<span style="color:#003366;font-size:10px;">&#9650;</span>'
        : '<span style="color:#003366;font-size:10px;">&#9660;</span>';
}

// ── Pagination helper ──
function paginationUrl($page) {
    global $currentSort, $currentDir, $filters;
    $params = array_filter([
        'page'        => $page,
        'sort'        => $currentSort,
        'dir'         => $currentDir,
        'status'      => $filters['status'] ?? '',
        'possibility' => $filters['possibility'] ?? '',
        'date_from'   => $filters['date_from'] ?? '',
        'date_to'     => $filters['date_to'] ?? '',
        'customer'    => $filters['customer'] ?? '',
        'group_by'    => $filters['group_by'] ?? '',
    ]);
    return '/sales/quotations?' . http_build_query($params);
}

$statusClasses = [
    'DRAFT'       => 'badge-draft',
    'SUBMITTED'   => 'badge-open',
    'NEGOTIATING' => 'badge-pending',
    'WON'         => 'badge-approved',
    'LOST'        => 'badge-rejected',
    'INTERNAL_REVIEW' => 'badge-open',
    'APPROVED'    => 'badge-approved',
    'EXPIRED'     => 'badge-draft',
    'CANCELLED'   => 'badge-rejected',
    'AWAIT_APPROVAL' => 'badge-pending',
];

// Build possibility colors from deal_statuses (DB-driven)
$possibilityColors = [];
$possibilityLabels = []; // status_name => "name_jp (win%)"
foreach ($dealStatuses ?? [] as $ds) {
    $possibilityColors[$ds['status_name']] = $ds['color'];
    $jp = $ds['status_name_jp'] ?? $ds['status_name'];
    $possibilityLabels[$ds['status_name']] = $jp . ' (' . intval($ds['win_pct']) . '%)';
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('quotations') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/deals"><?= _e('sales') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('quotations') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button type="button" onclick="bulkPdf('quotation')" class="btn btn-cancel" id="btn-bulk-pdf" style="display:none;">&#128424; <?= _e('bulk_print') ?></button>
        <a href="/sales/quotations/create" class="btn btn-primary"><?= _e('new_quotation') ?></a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 20px;margin-bottom:16px;">
    <form method="GET" action="/sales/quotations" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <input type="hidden" name="sort" value="<?= e($currentSort) ?>">
        <input type="hidden" name="dir" value="<?= e($currentDir) ?>">
        <div class="form-group" style="margin-bottom:0;">
            <select name="status" class="form-select" style="width:160px;">
                <option value=""><?= _e('all_statuses') ?></option>
                <?php foreach (['DRAFT','SUBMITTED','NEGOTIATING','WON','LOST'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <select name="possibility" class="form-select" style="width:200px;">
                <option value=""><?= _e('possibility') ?></option>
                <?php foreach ($dealStatuses ?? [] as $ds): ?>
                    <option value="<?= e($ds['status_name']) ?>" <?= ($filters['possibility'] ?? '') === $ds['status_name'] ? 'selected' : '' ?>>
                        <?= e($ds['status_name_jp'] ?? $ds['status_name']) ?> (<?= intval($ds['win_pct']) ?>%)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_from" class="form-input" style="width:140px;" value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_to" class="form-input" style="width:140px;" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="customer" class="form-input" style="width:180px;" value="<?= e($filters['customer'] ?? '') ?>" placeholder="<?= __('search_customer') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/sales/quotations" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Quotation Table -->
<div class="table-wrapper" style="overflow-x:auto;">
    <table class="data-table" style="min-width:1300px;" id="quotationTable">
        <thead>
            <tr>
                <th style="width:30px;" class="text-center">
                    <input type="checkbox" id="check-all" onchange="toggleAll(this)">
                </th>
                <th style="width:130px;" class="sortable-th"><a href="<?= sortUrl('quotation_no') ?>"><?= _e('quotation_no') ?> <?= sortIcon('quotation_no') ?></a></th>
                <th style="width:90px;" class="sortable-th"><a href="<?= sortUrl('date') ?>"><?= _e('date') ?> <?= sortIcon('date') ?></a></th>
                <th style="min-width:140px;" class="sortable-th"><a href="<?= sortUrl('customer') ?>"><?= _e('customer') ?> <?= sortIcon('customer') ?></a></th>
                <th style="min-width:180px;" class="sortable-th"><a href="<?= sortUrl('project') ?>"><?= _e('project') ?> <?= sortIcon('project') ?></a></th>
                <th style="width:90px;" class="sortable-th"><a href="<?= sortUrl('solution') ?>"><?= _e('solution') ?> <?= sortIcon('solution') ?></a></th>
                <th class="text-right sortable-th" style="width:120px;"><a href="<?= sortUrl('subtotal') ?>"><?= _e('subtotal') ?> <?= sortIcon('subtotal') ?></a></th>
                <th class="text-right sortable-th" style="width:110px;"><a href="<?= sortUrl('cost') ?>"><?= _e('cost_amount') ?> <?= sortIcon('cost') ?></a></th>
                <th class="text-right sortable-th" style="width:110px;"><a href="<?= sortUrl('gross_profit') ?>"><?= _e('gross_profit') ?> <?= sortIcon('gross_profit') ?></a></th>
                <th class="text-center sortable-th" style="width:80px;"><a href="<?= sortUrl('possibility') ?>"><?= _e('possibility') ?> <?= sortIcon('possibility') ?></a></th>
                <th class="text-center sortable-th" style="width:80px;"><a href="<?= sortUrl('status') ?>"><?= _e('status') ?> <?= sortIcon('status') ?></a></th>
                <th style="width:80px;" class="sortable-th"><a href="<?= sortUrl('sales_name') ?>"><?= _e('sales_name') ?> <?= sortIcon('sales_name') ?></a></th>
                <th class="text-center" style="width:80px;"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($quotations)): ?>
                <?php foreach ($quotations as $idx => $q):
                    $isGroup = !empty($q['_is_group']) && ($q['_child_count'] ?? 0) > 1;
                    $children = $q['_children'] ?? [];
                    $selling = floatval($q['subtotal_thb'] ?? 0);
                    $cost = floatval($q['total_line_cost'] ?? 0);
                    $gp = floatval($q['gross_profit'] ?? ($selling - $cost));
                    $gpPct = floatval($q['gross_profit_pct'] ?? ($selling > 0 ? $gp / $selling * 100 : 0));
                ?>

                <?php if ($isGroup): ?>
                    <!-- ===== PROJECT GROUP ROW ===== -->
                    <tr class="project-group-row" data-group="grp-<?= $idx ?>" onclick="toggleGroup('grp-<?= $idx ?>')" style="cursor:pointer;">
                        <td class="text-center">
                            <input type="checkbox" class="row-check" value="<?= e($q['quotation_id']) ?>" onchange="updateBulkBtn()" onclick="event.stopPropagation()">
                        </td>
                        <td>
                            <span class="grp-toggle" id="toggle-grp-<?= $idx ?>">&#9654;</span>
                            <span style="font-weight:600;color:#003366;">
                                <?= e($q['_qt_numbers'][0] ?? $q['quotation_no'] ?? '') ?>
                                <?php if ($q['_child_count'] > 1):
                                    $lastQt = $q['_qt_numbers'][count($q['_qt_numbers']) - 1] ?? '';
                                ?>
                                    ~ <?= e($lastQt) ?>
                                <?php endif; ?>
                            </span>
                            <div style="font-size:10px;color:#888;"><?= $q['_child_count'] ?> <?= __('quotations') ?></div>
                        </td>
                        <td><?= e($q['issue_date']) ?></td>
                        <td>
                            <?= e($q['customer_name'] ?? '') ?>
                            <?php if (!empty($q['customer_staff'])): ?>
                                <div style="font-size:11px;color:var(--color-text-muted);"><?= e($q['customer_staff']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600;color:#003366;">
                            <?= e($q['_project_base_name'] ?? $q['project_name'] ?? '-') ?>
                        </td>
                        <td style="font-size:11px;"><?= e($q['solution_name'] ?? $q['solution_cat_name'] ?? '') ?></td>
                        <td class="text-right" style="font-weight:700;"><?= formatMoney($selling) ?></td>
                        <td class="text-right" style="font-weight:700;"><?= formatMoney($cost) ?></td>
                        <td class="text-right">
                            <span style="color:<?= $gp >= 0 ? '#4CAF50' : '#F44336' ?>;font-weight:700;">
                                <?= formatMoney($gp) ?>
                            </span>
                            <div style="font-size:10px;color:var(--color-text-muted);"><?= number_format($gpPct, 1) ?>%</div>
                        </td>
                        <td class="text-center">
                            <?php $poss = $q['possibility'] ?? ''; ?>
                            <?php if ($poss): ?>
                                <span class="badge" style="background:<?= $possibilityColors[$poss] ?? '#757575' ?>;color:#fff;font-size:10px;" title="<?= e($poss) ?>">
                                    <?= e($possibilityLabels[$poss] ?? $poss) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $statusClasses[$q['status']] ?? 'badge-draft' ?>" style="font-size:10px;">
                                <?= e($q['status']) ?>
                            </span>
                        </td>
                        <td style="font-size:11px;"><?= e($q['sales_name'] ?? '') ?></td>
                        <td class="text-center actions">
                            <a href="/sales/quotations/<?= e($q['quotation_id']) ?>" title="<?= __('view') ?>" onclick="event.stopPropagation()">&#128065;</a>
                        </td>
                    </tr>
                    <!-- ===== CHILD ROWS (hidden by default) ===== -->
                    <?php foreach ($children as $ci => $child):
                        $cSelling = floatval($child['subtotal_thb'] ?? 0);
                        $cCost = floatval($child['total_line_cost'] ?? 0);
                        $cGp = floatval($child['gross_profit'] ?? ($cSelling - $cCost));
                        $cGpPct = floatval($child['gross_profit_pct'] ?? ($cSelling > 0 ? $cGp / $cSelling * 100 : 0));
                    ?>
                    <tr class="project-child-row grp-<?= $idx ?>" style="display:none;background:#f8f9ff;">
                        <td class="text-center">
                            <input type="checkbox" class="row-check" value="<?= e($child['quotation_id']) ?>" onchange="updateBulkBtn()">
                        </td>
                        <td style="padding-left:28px;">
                            <a href="/sales/quotations/<?= e($child['quotation_id']) ?>" style="font-weight:500;font-size:12px;"><?= e($child['quotation_no']) ?></a>
                        </td>
                        <td style="font-size:12px;"><?= e($child['issue_date']) ?></td>
                        <td style="font-size:12px;"><?= e($child['customer_name'] ?? '') ?></td>
                        <td style="font-size:12px;padding-left:16px;color:#555;">
                            <?= e($child['project_name'] ?? '-') ?>
                        </td>
                        <td style="font-size:11px;"><?= e($child['solution_name'] ?? $child['solution_cat_name'] ?? '') ?></td>
                        <td class="text-right" style="font-size:12px;"><?= formatMoney($cSelling) ?></td>
                        <td class="text-right" style="font-size:12px;"><?= formatMoney($cCost) ?></td>
                        <td class="text-right">
                            <span style="color:<?= $cGp >= 0 ? '#4CAF50' : '#F44336' ?>;font-size:12px;">
                                <?= formatMoney($cGp) ?>
                            </span>
                            <div style="font-size:10px;color:var(--color-text-muted);"><?= number_format($cGpPct, 1) ?>%</div>
                        </td>
                        <td class="text-center">
                            <?php $cPoss = $child['possibility'] ?? ''; ?>
                            <?php if ($cPoss): ?>
                                <span class="badge" style="background:<?= $possibilityColors[$cPoss] ?? '#757575' ?>;color:#fff;font-size:10px;" title="<?= e($cPoss) ?>">
                                    <?= e($possibilityLabels[$cPoss] ?? $cPoss) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $statusClasses[$child['status']] ?? 'badge-draft' ?>" style="font-size:10px;">
                                <?= e($child['status']) ?>
                            </span>
                        </td>
                        <td style="font-size:11px;"><?= e($child['sales_name'] ?? '') ?></td>
                        <td class="text-center actions" style="font-size:12px;">
                            <a href="/sales/quotations/<?= e($child['quotation_id']) ?>" title="<?= __('view') ?>">&#128065;</a>
                            <a href="/pdf/quotation/<?= e($child['quotation_id']) ?>" target="_blank" title="PDF">&#128424;</a>
                            <?php if ($child['status'] === 'DRAFT'): ?>
                                <a href="/sales/quotations/<?= e($child['quotation_id']) ?>/edit" title="<?= __('edit') ?>">&#9998;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <!-- ===== SINGLE ROW (no group) ===== -->
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="row-check" value="<?= e($q['quotation_id']) ?>" onchange="updateBulkBtn()">
                        </td>
                        <td><a href="/sales/quotations/<?= e($q['quotation_id']) ?>" style="font-weight:500;"><?= e($q['quotation_no']) ?></a></td>
                        <td><?= e($q['issue_date']) ?></td>
                        <td>
                            <?= e($q['customer_name'] ?? '') ?>
                            <?php if (!empty($q['customer_staff'])): ?>
                                <div style="font-size:11px;color:var(--color-text-muted);"><?= e($q['customer_staff']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;"><?= e($q['project_name'] ?? '-') ?></td>
                        <td style="font-size:11px;"><?= e($q['solution_name'] ?? $q['solution_cat_name'] ?? '') ?></td>
                        <td class="text-right"><?= formatMoney($selling) ?></td>
                        <td class="text-right"><?= formatMoney($cost) ?></td>
                        <td class="text-right">
                            <span style="color:<?= $gp >= 0 ? '#4CAF50' : '#F44336' ?>;">
                                <?= formatMoney($gp) ?>
                            </span>
                            <?php if ($gpPct): ?>
                                <div style="font-size:10px;color:var(--color-text-muted);"><?= number_format($gpPct, 1) ?>%</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php $poss = $q['possibility'] ?? ''; ?>
                            <?php if ($poss): ?>
                                <span class="badge" style="background:<?= $possibilityColors[$poss] ?? '#757575' ?>;color:#fff;font-size:10px;" title="<?= e($poss) ?>">
                                    <?= e($possibilityLabels[$poss] ?? $poss) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $statusClasses[$q['status']] ?? 'badge-draft' ?>" style="font-size:10px;">
                                <?= e($q['status']) ?>
                            </span>
                        </td>
                        <td style="font-size:11px;"><?= e($q['sales_name'] ?? '') ?></td>
                        <td class="text-center actions">
                            <a href="/sales/quotations/<?= e($q['quotation_id']) ?>" title="<?= __('view') ?>">&#128065;</a>
                            <a href="/pdf/quotation/<?= e($q['quotation_id']) ?>" target="_blank" title="PDF">&#128424;</a>
                            <?php if ($q['status'] === 'DRAFT'): ?>
                                <a href="/sales/quotations/<?= e($q['quotation_id']) ?>/edit" title="<?= __('edit') ?>">&#9998;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="13" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('msg_no_quotations') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
        <div class="table-footer" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid var(--color-border);background:#f9fafb;">
            <span style="font-size:13px;color:#666;">
                <?= $pagination['from'] ?>–<?= $pagination['to'] ?> / <?= number_format($pagination['total']) ?> <?= __('items') ?>
            </span>
            <div style="display:flex;gap:4px;align-items:center;">
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="<?= paginationUrl(1) ?>" class="btn btn-cancel btn-sm" title="<?= __('first') ?>">&laquo;</a>
                    <a href="<?= paginationUrl($pagination['current_page'] - 1) ?>" class="btn btn-cancel btn-sm">&lsaquo; <?= __('prev') ?></a>
                <?php endif; ?>

                <?php
                    $startPage = max(1, $pagination['current_page'] - 3);
                    $endPage = min($pagination['last_page'], $pagination['current_page'] + 3);
                    for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                    <?php if ($p === $pagination['current_page']): ?>
                        <span class="btn btn-primary btn-sm" style="min-width:32px;text-align:center;pointer-events:none;"><?= $p ?></span>
                    <?php else: ?>
                        <a href="<?= paginationUrl($p) ?>" class="btn btn-cancel btn-sm" style="min-width:32px;text-align:center;"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <a href="<?= paginationUrl($pagination['current_page'] + 1) ?>" class="btn btn-cancel btn-sm"><?= __('next') ?> &rsaquo;</a>
                    <a href="<?= paginationUrl($pagination['last_page']) ?>" class="btn btn-cancel btn-sm" title="<?= __('last') ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Bulk PDF Form (hidden) -->
<form id="bulk-pdf-form" method="POST" action="/pdf/bulk" target="_blank" style="display:none;">
    <input type="hidden" name="type" value="quotation">
</form>

<style>
/* Project group rows */
.project-group-row { background:#eef3ff !important; border-left:3px solid #003366; }
.project-group-row:hover { background:#e0eaff !important; }
.project-child-row { border-left:3px solid #aac4e8; }
.project-child-row:hover { background:#eef3ff !important; }
.grp-toggle { display:inline-block; width:16px; font-size:10px; color:#003366; transition:transform .2s; margin-right:4px; }
.grp-toggle.open { transform:rotate(90deg); }

/* Sortable header */
.sortable-th a {
    display:flex; align-items:center; gap:4px;
    text-decoration:none; color:inherit; white-space:nowrap;
    cursor:pointer;
}
.sortable-th a:hover { color:#003366; }
.sortable-th { user-select:none; }
</style>

<script>
function toggleAll(el) {
    document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = el.checked; });
    updateBulkBtn();
}
function updateBulkBtn() {
    var checked = document.querySelectorAll('.row-check:checked').length;
    document.getElementById('btn-bulk-pdf').style.display = checked > 0 ? 'inline-flex' : 'none';
}
function bulkPdf(type) {
    var form = document.getElementById('bulk-pdf-form');
    form.querySelectorAll('input[name="ids[]"]').forEach(function(el) { el.remove(); });
    document.querySelectorAll('.row-check:checked').forEach(function(cb) {
        var input = document.createElement('input');
        input.type = 'hidden'; input.name = 'ids[]'; input.value = cb.value;
        form.appendChild(input);
    });
    form.submit();
}

/* ── Toggle project group expand/collapse ── */
function toggleGroup(groupId) {
    var childRows = document.querySelectorAll('.project-child-row.' + groupId);
    var toggle = document.getElementById('toggle-' + groupId);
    var isOpen = toggle.classList.contains('open');

    childRows.forEach(function(row) {
        row.style.display = isOpen ? 'none' : 'table-row';
    });

    if (isOpen) {
        toggle.classList.remove('open');
    } else {
        toggle.classList.add('open');
    }
}
</script>
