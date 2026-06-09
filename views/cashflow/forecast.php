<?php
/**
 * Forecast CF — "profit sheet" style matrix
 * Vars: $months, $statuses, $matrix, $colTotals, $colWeighted, $filters
 */
$fmt = fn($n) => $n == 0 ? '' : number_format($n, 0);
$labelMonth = function($m) {
    $ts = strtotime($m . '-01');
    return date('M ', $ts) . "<br><span style='font-size:9px;color:#777;'>" . date("'y", $ts) . '</span>';
};
// Group months by fiscal year (Apr-Mar) for grand totals
$fiscalYear = function($m) {
    [$y, $mo] = explode('-', $m);
    return intval($mo) >= 4 ? intval($y) : intval($y) - 1;
};
// Row totals
$rowTotal = $rowWeighted = [];
foreach ($statuses as $s) {
    $sid = $s['status_id'];
    $sum = 0;
    foreach ($months as $m) { $sum += $matrix[$sid][$m]['amount'] ?? 0; }
    $rowTotal[$sid] = $sum;
    $rowWeighted[$sid] = $sum * floatval($s['win_pct']) / 100;
}
$grandTotal = array_sum($colTotals);
$grandWeighted = array_sum($colWeighted);
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('cashflow_forecast') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('cashflow_forecast') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/cashflow/actual" class="btn btn-cancel"><?= __('cashflow_actual') ?> →</a>
    </div>
</div>

<div class="card" style="padding:12px 20px;margin-bottom:12px;">
    <form method="GET" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <label><?= __('from') ?>:</label>
        <input type="month" name="from" class="form-input" style="width:150px;" value="<?= e(substr($filters['from'], 0, 7)) ?>">
        <label><?= __('to') ?>:</label>
        <input type="month" name="to" class="form-input" style="width:150px;" value="<?= e(substr($filters['to'], 0, 7)) ?>">
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
    </form>
</div>

<!-- KPI -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-bottom:12px;">
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:#666;text-transform:uppercase;"><?= __('total_pipeline') ?></div>
        <div style="font-size:22px;font-weight:700;color:#1976D2;">฿ <?= number_format($grandTotal, 0) ?></div>
    </div>
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:#666;text-transform:uppercase;"><?= __('weighted_pipeline') ?></div>
        <div style="font-size:22px;font-weight:700;color:#4CAF50;">฿ <?= number_format($grandWeighted, 0) ?></div>
    </div>
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:#666;text-transform:uppercase;"><?= __('period_months') ?></div>
        <div style="font-size:22px;font-weight:700;"><?= count($months) ?></div>
    </div>
</div>

<!-- Matrix -->
<div class="card" style="padding:0;overflow:auto;max-height:75vh;">
<table class="data-table" style="font-size:11px;min-width:1800px;border-collapse:collapse;">
    <thead>
    <tr style="position:sticky;top:0;z-index:3;background:#E3F2FD;">
        <th style="position:sticky;left:0;background:#E3F2FD;z-index:4;min-width:220px;text-align:left;padding:8px 10px;">
            <?= __('status_possibility') ?>
        </th>
        <th style="position:sticky;left:220px;background:#E3F2FD;z-index:4;min-width:110px;text-align:right;padding:8px 10px;">
            <?= __('row_total') ?>
        </th>
        <th style="position:sticky;left:330px;background:#E3F2FD;z-index:4;min-width:110px;text-align:right;padding:8px 10px;">
            <?= __('weighted') ?>
        </th>
        <?php foreach ($months as $m): ?>
            <th class="text-center" style="min-width:90px;padding:6px 4px;"><?= $labelMonth($m) ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($statuses as $s):
        $sid = $s['status_id'];
        if ($rowTotal[$sid] == 0) continue; // hide empty rows
    ?>
        <tr>
            <td style="position:sticky;left:0;background:#fff;z-index:2;border-right:1px solid #ccc;padding:6px 10px;">
                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= e($s['color'] ?? '#888') ?>;margin-right:6px;"></span>
                <strong><?= e($s['status_name']) ?></strong>
                <span style="color:#888;"> (<?= number_format(floatval($s['win_pct']), 0) ?>%)</span>
            </td>
            <td style="position:sticky;left:220px;background:#FAFAFA;z-index:2;border-right:1px solid #ccc;text-align:right;padding:6px 10px;font-weight:600;">
                <?= $fmt($rowTotal[$sid]) ?>
            </td>
            <td style="position:sticky;left:330px;background:#FAFAFA;z-index:2;border-right:1px solid #ccc;text-align:right;padding:6px 10px;font-weight:600;color:#2E7D32;">
                <?= $fmt($rowWeighted[$sid]) ?>
            </td>
            <?php foreach ($months as $m):
                $cell = $matrix[$sid][$m] ?? null;
                $amt = $cell ? $cell['amount'] : 0;
            ?>
                <td class="text-right" style="padding:4px 6px;<?= $amt > 0 ? 'background:#F5FBFF;' : 'color:#ccc;' ?>">
                    <?= $fmt($amt) ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr style="background:#E3F2FD;font-weight:700;border-top:2px solid #1976D2;">
        <td style="position:sticky;left:0;background:#E3F2FD;z-index:2;padding:8px 10px;"><?= __('column_total') ?></td>
        <td style="position:sticky;left:220px;background:#E3F2FD;z-index:2;text-align:right;padding:8px 10px;"><?= number_format($grandTotal, 0) ?></td>
        <td style="position:sticky;left:330px;background:#E3F2FD;z-index:2;text-align:right;padding:8px 10px;color:#2E7D32;"><?= number_format($grandWeighted, 0) ?></td>
        <?php foreach ($months as $m): ?>
            <td class="text-right" style="padding:6px;"><?= $fmt($colTotals[$m]) ?></td>
        <?php endforeach; ?>
    </tr>
    <tr style="background:#E8F5E9;font-weight:700;">
        <td style="position:sticky;left:0;background:#E8F5E9;z-index:2;padding:8px 10px;color:#2E7D32;"><?= __('weighted_total') ?></td>
        <td style="position:sticky;left:220px;background:#E8F5E9;z-index:2;"></td>
        <td style="position:sticky;left:330px;background:#E8F5E9;z-index:2;text-align:right;padding:8px 10px;color:#2E7D32;"><?= number_format($grandWeighted, 0) ?></td>
        <?php foreach ($months as $m): ?>
            <td class="text-right" style="padding:6px;color:#2E7D32;"><?= $fmt($colWeighted[$m]) ?></td>
        <?php endforeach; ?>
    </tr>
    </tfoot>
</table>
</div>

<div style="font-size:11px;color:#888;margin-top:8px;">
    <?= __('cf_forecast_note') ?>
</div>
