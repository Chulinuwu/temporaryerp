<?php
/**
 * Sales KPI Dashboard — mirrors SUMMARY sheet
 * Vars: $fy, $monthList, $employees, $categories, $counts, $targets, $profit
 */
// Helper: monthly target = annual × pct / 100
$monthlyTarget = function($annual, $pctMap, $monthNo) {
    return ($annual > 0 && !empty($pctMap))
        ? round($annual * (floatval($pctMap[$monthNo] ?? 0)) / 100)
        : 0;
};
$fmt = fn($n) => $n == 0 ? '—' : number_format($n, 0);
$rate = function($actual, $target) {
    if ($target <= 0) return '—';
    $r = ($actual / $target) * 100;
    return number_format($r, 1) . '%';
};
$rateColor = function($actual, $target) {
    if ($target <= 0) return '#999';
    $r = $actual / $target;
    if ($r >= 1.0) return '#2E7D32';
    if ($r >= 0.8) return '#F57C00';
    if ($r >= 0.5) return '#1976D2';
    return '#D32F2F';
};
?>
<div class="page-header">
    <div>
        <h1 class="page-title">🎯 <?= _e('sales_kpi_dashboard') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/deals"><?= _e('sales') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('sales_kpi_dashboard') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if (Auth::isManagerOrAbove()): ?>
            <a href="/sales/kpi/master?fy=<?= $fy ?>" class="btn btn-cancel">⚙ <?= __('kpi_target_master') ?></a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter bar -->
<div class="card" style="padding:12px 20px;margin-bottom:12px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
        <label><?= __('fiscal_year') ?>:</label>
        <select name="fy" class="form-select" style="width:140px;" onchange="this.form.submit()">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $fy == $y ? 'selected' : '' ?>>FY <?= $y ?> (Apr<?= $y ?>–Mar<?= $y+1 ?>)</option>
            <?php endfor; ?>
        </select>
        <span style="font-size:12px;color:#888;"><?= __('kpi_dashboard_hint') ?></span>
    </form>
</div>

<?php if (empty($employees)): ?>
    <div class="card" style="padding:30px;text-align:center;color:#888;">
        <?= __('no_kpi_data') ?>
        <?php if (Auth::isManagerOrAbove()): ?>
            <br><br><a href="/sales/kpi/master?fy=<?= $fy ?>" class="btn btn-primary btn-sm">⚙ <?= __('set_kpi_targets') ?></a>
        <?php endif; ?>
    </div>
<?php else: ?>

<?php foreach ($employees as $emp):
    $empId = (int)$emp['employee_id'];
    $t = $targets[$empId] ?? null;
    $pctMap = $t['monthly_pct'] ?? [];

    // Compute totals
    $contactTotal = 0;
    $meetingTotal = 0;
    $profitTotal  = 0;
    // Categories: treat anything NOT matching 'Meeting' as Contact (phone/walk-in/posting/referral/other/meeting-sum)
    foreach ($categories as $c) {
        $cid = (int)$c['category_id'];
        foreach ($monthList as $m) {
            $n = $counts[$empId][$cid][$m['key']] ?? 0;
            $contactTotal += $n;
            if (stripos($c['category_name'], 'meeting') !== false) $meetingTotal += $n;
        }
    }
    foreach ($monthList as $m) {
        $profitTotal += $profit[$empId][$m['key']] ?? 0;
    }
?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="background:#E3F2FD;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0;font-size:16px;">👤 <?= e($emp['full_name']) ?>
            <?php if ($t): ?>
                <span style="font-size:12px;color:#555;font-weight:400;margin-left:10px;">
                    Contact: <?= number_format($t['annual_contact_target']) ?>
                    / Meeting: <?= number_format($t['annual_meeting_target']) ?>
                    / Profit: ฿<?= number_format($t['annual_profit_target']) ?>
                </span>
            <?php else: ?>
                <span style="font-size:12px;color:#D32F2F;font-weight:400;margin-left:10px;">
                    ⚠ <?= __('no_target_set') ?>
                </span>
            <?php endif; ?>
        </h3>
    </div>
    <div class="card-body" style="padding:0;overflow:auto;">
        <table class="data-table" style="font-size:11.5px;min-width:1100px;margin:0;">
            <thead>
            <tr style="background:#fafafa;">
                <th style="min-width:240px;text-align:left;padding:6px 10px;"><?= __('category_metric') ?></th>
                <?php foreach ($monthList as $m): ?>
                    <th class="text-center" style="min-width:60px;"><?= e($m['label']) ?></th>
                <?php endforeach; ?>
                <th class="text-center" style="background:#E8F5E9;min-width:80px;"><?= __('total') ?></th>
            </tr>
            </thead>
            <tbody>
            <!-- Activity categories -->
            <?php foreach ($categories as $c):
                $cid = (int)$c['category_id'];
                $rowSum = 0;
            ?>
                <tr>
                    <td style="padding:4px 10px;"><?= e(($c['icon'] ?? '') . ' ' . $c['category_name']) ?></td>
                    <?php foreach ($monthList as $m):
                        $n = $counts[$empId][$cid][$m['key']] ?? 0;
                        $rowSum += $n;
                    ?>
                        <td class="text-center" style="padding:3px;<?= $n > 0 ? '' : 'color:#ccc;' ?>"><?= $n ?: '' ?></td>
                    <?php endforeach; ?>
                    <td class="text-center" style="background:#E8F5E9;font-weight:600;"><?= $rowSum ?: '—' ?></td>
                </tr>
            <?php endforeach; ?>

            <!-- Contact Total (sum of all categories) -->
            <tr style="background:#F3F6FA;font-weight:700;">
                <td style="padding:6px 10px;">── <?= __('contact_total') ?> ──</td>
                <?php
                $mContactTotal = 0;
                foreach ($monthList as $m):
                    $sum = 0;
                    foreach ($categories as $c) {
                        $sum += $counts[$empId][(int)$c['category_id']][$m['key']] ?? 0;
                    }
                    $mContactTotal += $sum;
                ?>
                    <td class="text-center" style="padding:4px;"><?= $fmt($sum) ?></td>
                <?php endforeach; ?>
                <td class="text-center" style="background:#E8F5E9;"><?= $fmt($mContactTotal) ?></td>
            </tr>

            <!-- Contact Target -->
            <tr style="color:#1976D2;">
                <td style="padding:4px 10px;">📎 <?= __('contact_target') ?></td>
                <?php $annualContactT = $t['annual_contact_target'] ?? 0; ?>
                <?php foreach ($monthList as $m):
                    $tgt = $monthlyTarget($annualContactT, $pctMap, $m['no']);
                ?>
                    <td class="text-center" style="padding:3px;"><?= $fmt($tgt) ?></td>
                <?php endforeach; ?>
                <td class="text-center" style="background:#E8F5E9;"><?= $fmt($annualContactT) ?></td>
            </tr>

            <!-- Contact Achievement % -->
            <tr>
                <td style="padding:4px 10px;">📊 <?= __('achievement_contact') ?></td>
                <?php foreach ($monthList as $m):
                    $tgt = $monthlyTarget($annualContactT, $pctMap, $m['no']);
                    $a = 0;
                    foreach ($categories as $c) $a += $counts[$empId][(int)$c['category_id']][$m['key']] ?? 0;
                    $color = $rateColor($a, $tgt);
                ?>
                    <td class="text-center" style="color:<?= e($color) ?>;font-weight:600;"><?= $rate($a, $tgt) ?></td>
                <?php endforeach; ?>
                <td class="text-center" style="background:#E8F5E9;color:<?= e($rateColor($contactTotal, $annualContactT)) ?>;font-weight:700;">
                    <?= $rate($contactTotal, $annualContactT) ?>
                </td>
            </tr>

            <!-- Meeting Target -->
            <tr style="color:#1976D2;">
                <td style="padding:4px 10px;">🤝 <?= __('meeting_target') ?></td>
                <?php $annualMeetingT = $t['annual_meeting_target'] ?? 0; ?>
                <?php foreach ($monthList as $m):
                    $tgt = $monthlyTarget($annualMeetingT, $pctMap, $m['no']);
                ?>
                    <td class="text-center"><?= $fmt($tgt) ?></td>
                <?php endforeach; ?>
                <td class="text-center" style="background:#E8F5E9;"><?= $fmt($annualMeetingT) ?></td>
            </tr>

            <!-- Meeting Achievement -->
            <tr>
                <td style="padding:4px 10px;">📊 <?= __('achievement_meeting') ?></td>
                <?php foreach ($monthList as $m):
                    $tgt = $monthlyTarget($annualMeetingT, $pctMap, $m['no']);
                    $a = 0;
                    foreach ($categories as $c) {
                        if (stripos($c['category_name'], 'meeting') !== false) {
                            $a += $counts[$empId][(int)$c['category_id']][$m['key']] ?? 0;
                        }
                    }
                    $color = $rateColor($a, $tgt);
                ?>
                    <td class="text-center" style="color:<?= e($color) ?>;font-weight:600;"><?= $rate($a, $tgt) ?></td>
                <?php endforeach; ?>
                <td class="text-center" style="background:#E8F5E9;color:<?= e($rateColor($meetingTotal, $annualMeetingT)) ?>;font-weight:700;">
                    <?= $rate($meetingTotal, $annualMeetingT) ?>
                </td>
            </tr>

            <!-- Order target + rates line (informational) -->
            <?php if ($t): ?>
            <tr style="background:#F3E5F5;">
                <td style="padding:6px 10px;font-weight:600;">🎯 <?= __('annual_order_target') ?> / <?= __('rates') ?></td>
                <td colspan="<?= count($monthList) + 1 ?>" style="padding:6px 10px;font-size:11px;">
                    <strong><?= number_format($t['annual_order_target'] ?? 0) ?></strong> <?= __('orders') ?>
                    (<?= __('profit_per_order') ?>: <strong>฿<?= number_format($t['annual_profit_target'] / max(1, ($t['annual_order_target'] ?? 1)), 0) ?></strong>)
                    &nbsp; | &nbsp; <?= __('close_rate_pct') ?>: <strong><?= number_format(floatval($t['annual_order_target'] ?? 0) * 100 / max(1, floatval($t['annual_meeting_target'] ?? 1)), 1) ?>%</strong>
                    &nbsp; | &nbsp; <?= __('appt_rate_pct') ?>: <strong><?= number_format(floatval($t['annual_meeting_target'] ?? 0) * 100 / max(1, floatval($t['annual_contact_target'] ?? 1)), 1) ?>%</strong>
                </td>
            </tr>
            <?php endif; ?>

            <!-- Profit (THB) actuals -->
            <tr style="background:#FFF8E1;">
                <td style="padding:6px 10px;font-weight:600;">💰 <?= __('profit_actual') ?> (THB)</td>
                <?php foreach ($monthList as $m):
                    $p = $profit[$empId][$m['key']] ?? 0;
                ?>
                    <td class="text-center" style="padding:3px;<?= $p > 0 ? '' : 'color:#ccc;' ?>"><?= $p > 0 ? number_format($p, 0) : '' ?></td>
                <?php endforeach; ?>
                <td class="text-center" style="background:#E8F5E9;font-weight:700;"><?= number_format($profitTotal, 0) ?></td>
            </tr>

            <!-- Profit Achievement -->
            <?php $annualProfitT = $t['annual_profit_target'] ?? 0; ?>
            <tr>
                <td style="padding:4px 10px;">🎯 <?= __('profit_target') ?></td>
                <?php foreach ($monthList as $m):
                    $tgt = $monthlyTarget($annualProfitT, $pctMap, $m['no']);
                ?>
                    <td class="text-center" style="color:#1976D2;"><?= number_format($tgt, 0) ?></td>
                <?php endforeach; ?>
                <td class="text-center" style="background:#E8F5E9;color:#1976D2;font-weight:700;"><?= number_format($annualProfitT, 0) ?></td>
            </tr>
            <tr>
                <td style="padding:4px 10px;">📊 <?= __('achievement_profit') ?></td>
                <?php foreach ($monthList as $m):
                    $tgt = $monthlyTarget($annualProfitT, $pctMap, $m['no']);
                    $a = $profit[$empId][$m['key']] ?? 0;
                    $color = $rateColor($a, $tgt);
                ?>
                    <td class="text-center" style="color:<?= e($color) ?>;font-weight:600;"><?= $rate($a, $tgt) ?></td>
                <?php endforeach; ?>
                <td class="text-center" style="background:#E8F5E9;color:<?= e($rateColor($profitTotal, $annualProfitT)) ?>;font-weight:700;font-size:13px;">
                    <?= $rate($profitTotal, $annualProfitT) ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<div style="font-size:11px;color:#888;margin-top:8px;">
    <?= __('kpi_dashboard_footer') ?>
</div>
