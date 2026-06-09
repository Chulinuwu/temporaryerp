<?php
/**
 * Actual CF — profit-sheet style
 * Vars: $months, $arPaid, $apPaid, $arIssued, $apIssued, $filters
 */
$fmt = fn($n) => $n == 0 ? '' : number_format($n, 0);
$labelMonth = function($m) {
    $ts = strtotime($m . '-01');
    return date('M ', $ts) . "<br><span style='font-size:9px;color:#777;'>" . date("'y", $ts) . '</span>';
};

$arPaidTotal = array_sum($arPaid);
$apPaidTotal = array_sum($apPaid);
$netTotal = $arPaidTotal - $apPaidTotal;
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('cashflow_actual') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('cashflow_actual') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/cashflow/forecast" class="btn btn-cancel">← <?= __('cashflow_forecast') ?></a>
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

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-bottom:12px;">
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:#666;text-transform:uppercase;"><?= __('cf_ar_received') ?></div>
        <div style="font-size:22px;font-weight:700;color:#2E7D32;">฿ <?= number_format($arPaidTotal, 0) ?></div>
    </div>
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:#666;text-transform:uppercase;"><?= __('cf_ap_paid') ?></div>
        <div style="font-size:22px;font-weight:700;color:#D32F2F;">฿ <?= number_format($apPaidTotal, 0) ?></div>
    </div>
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:#666;text-transform:uppercase;"><?= __('cf_net') ?></div>
        <div style="font-size:22px;font-weight:700;color:<?= $netTotal >= 0 ? '#2E7D32' : '#D32F2F' ?>;">฿ <?= number_format($netTotal, 0) ?></div>
    </div>
</div>

<div class="card" style="padding:0;overflow:auto;max-height:75vh;">
<table class="data-table" style="font-size:11px;min-width:1600px;border-collapse:collapse;">
    <thead>
    <tr style="position:sticky;top:0;z-index:3;background:#E3F2FD;">
        <th style="position:sticky;left:0;background:#E3F2FD;z-index:4;min-width:220px;text-align:left;padding:8px 10px;"><?= __('category') ?></th>
        <th style="position:sticky;left:220px;background:#E3F2FD;z-index:4;min-width:120px;text-align:right;padding:8px 10px;"><?= __('row_total') ?></th>
        <?php foreach ($months as $m): ?>
            <th class="text-center" style="min-width:90px;padding:6px 4px;"><?= $labelMonth($m) ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    $rows = [
        ['label' => __('cf_ar_received'),  'data' => $arPaid,   'color' => '#2E7D32', 'bold' => true],
        ['label' => __('cf_ar_issued'),    'data' => $arIssued, 'color' => '#888',    'bold' => false],
        ['label' => __('cf_ap_paid'),      'data' => $apPaid,   'color' => '#D32F2F', 'bold' => true],
        ['label' => __('cf_ap_issued'),    'data' => $apIssued, 'color' => '#888',    'bold' => false],
    ];
    foreach ($rows as $row):
        $total = array_sum($row['data']);
    ?>
        <tr>
            <td style="position:sticky;left:0;background:#fff;z-index:2;border-right:1px solid #ccc;padding:6px 10px;color:<?= e($row['color']) ?>;<?= $row['bold'] ? 'font-weight:700;' : '' ?>">
                <?= e($row['label']) ?>
            </td>
            <td style="position:sticky;left:220px;background:#FAFAFA;z-index:2;border-right:1px solid #ccc;text-align:right;padding:6px 10px;font-weight:600;">
                <?= $fmt($total) ?>
            </td>
            <?php foreach ($months as $m):
                $amt = $row['data'][$m] ?? 0;
            ?>
                <td class="text-right" style="padding:4px 6px;<?= $amt > 0 ? 'background:#F5FBFF;' : 'color:#ccc;' ?>">
                    <?= $fmt($amt) ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr style="background:#E8F5E9;font-weight:700;border-top:2px solid #4CAF50;">
        <td style="position:sticky;left:0;background:#E8F5E9;z-index:2;padding:8px 10px;"><?= __('cf_net_cash') ?></td>
        <td style="position:sticky;left:220px;background:#E8F5E9;z-index:2;text-align:right;padding:8px 10px;color:<?= $netTotal >= 0 ? '#2E7D32' : '#D32F2F' ?>;">
            <?= number_format($netTotal, 0) ?>
        </td>
        <?php foreach ($months as $m):
            $n = ($arPaid[$m] ?? 0) - ($apPaid[$m] ?? 0);
        ?>
            <td class="text-right" style="padding:6px;color:<?= $n >= 0 ? '#2E7D32' : '#D32F2F' ?>;"><?= $fmt($n) ?></td>
        <?php endforeach; ?>
    </tr>
    </tfoot>
</table>
</div>

<div style="font-size:11px;color:#888;margin-top:8px;">
    <?= __('cf_actual_note') ?>
</div>
