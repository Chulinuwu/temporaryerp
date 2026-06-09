<?php
/** @var int $year */ /** @var int $month */ /** @var array $records */
$year    = $year    ?? (int) date('Y');
$month   = $month   ?? (int) date('n');
$records = $records ?? [];
?>
<h1 class="kt-page-title">残業申請一覧</h1>

<div class="kt-header-actions">
    <a href="/kintai/overtime/new" class="kt-btn kt-btn-primary">新規残業申請</a>
    <a href="/kintai/overtime/early/new" class="kt-btn kt-btn-primary">新規早出申請</a>
</div>

<div class="kt-filter">
    <form method="GET" action="/kintai/overtime">
        <div class="kt-filter-row">
            <label><input type="radio" name="range" value="month" checked> 指定月</label>
            <span class="kt-date-group" style="margin-left:8px;">
                <button type="button" class="kt-icon-btn">&lsaquo;</button>
                <select class="kt-spinner" name="year">
                    <?php for ($yy = $year - 2; $yy <= $year + 1; $yy++): ?>
                        <option <?= $yy == $year ? 'selected' : '' ?>><?= $yy ?></option>
                    <?php endfor; ?>
                </select><span class="unit">年</span>
                <select class="kt-spinner" name="month">
                    <?php for ($mm = 1; $mm <= 12; $mm++): ?>
                        <option value="<?= sprintf('%02d', $mm) ?>" <?= $mm == $month ? 'selected' : '' ?>><?= sprintf('%02d', $mm) ?></option>
                    <?php endfor; ?>
                </select><span class="unit">月度</span>
                <button type="button" class="kt-icon-btn">&rsaquo;</button>
                <button type="button" class="kt-icon-btn">&#128197;</button>
            </span>
        </div>

        <div class="kt-filter-row">
            <label><input type="radio" name="range" value="period"> 指定期間</label>
            <span class="kt-date-group">
                <select class="kt-spinner"><option><?= $year ?></option></select><span class="unit">年</span>
                <select class="kt-spinner"><option>03</option></select><span class="unit">月</span>
                <select class="kt-spinner"><option>21</option></select><span class="unit">日</span>
                <button type="button" class="kt-icon-btn">&#128197;</button>
                <span class="unit">～</span>
                <select class="kt-spinner"><option><?= $year ?></option></select><span class="unit">年</span>
                <select class="kt-spinner"><option><?= sprintf('%02d', $month) ?></option></select><span class="unit">月</span>
                <select class="kt-spinner"><option>20</option></select><span class="unit">日</span>
                <button type="button" class="kt-icon-btn">&#128197;</button>
            </span>
        </div>

        <div class="kt-filter-actions">
            <button type="submit" class="kt-btn kt-btn-outline">表示</button>
        </div>
    </form>
</div>

<div style="margin-top:16px;">
    <table class="kt-table">
        <thead>
            <tr>
                <th>申請No</th>
                <th>残業希望日</th>
                <th>承認・却下</th>
                <th>申請日</th>
                <th>時刻</th>
                <th>残業理由</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr><td colspan="6" style="text-align:center;color:#999;padding:32px;">申請データはありません</td></tr>
            <?php else: ?>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['no']) ?></td>
                        <td><?= htmlspecialchars($r['date']) ?></td>
                        <td><?= htmlspecialchars($r['status']) ?></td>
                        <td><?= htmlspecialchars($r['applied']) ?></td>
                        <td><?= htmlspecialchars($r['time']) ?></td>
                        <td><?= htmlspecialchars($r['reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="kt-note">※申請内容を確認する場合は、「申請No.」をクリックして下さい。</div>
</div>
