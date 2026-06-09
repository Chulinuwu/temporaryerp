<?php
/** @var int $year */ /** @var int $month */ /** @var array $rows */
$year  = $year  ?? (int) date('Y');
$month = $month ?? (int) date('n');
$rows  = $rows  ?? [];
?>
<h1 class="kt-page-title">出勤簿</h1>

<div class="kt-header-actions">
    <button class="kt-btn kt-btn-primary">&#9658; 出勤</button>
    <button class="kt-btn kt-btn-outline">&#9632; 退勤</button>
</div>

<div class="kt-filter">
    <form method="GET" action="/kintai/attendance">
        <div class="kt-filter-row">
            <label>対象月度</label>
            <span class="kt-date-group">
                <select class="kt-spinner" name="year">
                    <?php for ($yy = $year - 2; $yy <= $year + 1; $yy++): ?>
                        <option <?= $yy == $year ? 'selected' : '' ?>><?= $yy ?></option>
                    <?php endfor; ?>
                </select><span class="unit">年</span>
                <select class="kt-spinner" name="month">
                    <?php for ($mm = 1; $mm <= 12; $mm++): ?>
                        <option value="<?= $mm ?>" <?= $mm == $month ? 'selected' : '' ?>><?= sprintf('%02d', $mm) ?></option>
                    <?php endfor; ?>
                </select><span class="unit">月</span>
            </span>
            <button type="submit" class="kt-btn kt-btn-outline" style="margin-left:12px;">表示</button>
        </div>
    </form>
</div>

<div style="margin-top:16px;">
    <table class="kt-table">
        <thead>
            <tr>
                <th style="width:120px;">日付</th>
                <th style="width:50px;">曜日</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>実労働時間</th>
                <th>残業時間</th>
                <th>ステータス</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                    $cellStyle = '';
                    if ($r['dow_idx'] === 0)      $cellStyle = 'color:#E53935;';
                    elseif ($r['dow_idx'] === 6)  $cellStyle = 'color:#1976D2;';
                ?>
                <tr>
                    <td style="<?= $cellStyle ?>"><?= htmlspecialchars($r['date']) ?></td>
                    <td style="<?= $cellStyle ?>"><?= htmlspecialchars($r['dow']) ?></td>
                    <td><?= htmlspecialchars($r['clock_in']) ?></td>
                    <td><?= htmlspecialchars($r['clock_out']) ?></td>
                    <td><?= htmlspecialchars($r['work_h']) ?></td>
                    <td><?= htmlspecialchars($r['ot_h']) ?></td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
