<?php
$y = (int) date('Y'); $m = (int) date('n');
?>
<h1 class="kt-page-title">工数管理</h1>

<div class="kt-filter">
    <form method="GET">
        <div class="kt-filter-row">
            <label>対象月度</label>
            <span class="kt-date-group">
                <select class="kt-spinner" name="year">
                    <?php for ($yy = $y - 1; $yy <= $y + 1; $yy++): ?>
                        <option <?= $yy == $y ? 'selected' : '' ?>><?= $yy ?></option>
                    <?php endfor; ?>
                </select><span class="unit">年</span>
                <select class="kt-spinner" name="month">
                    <?php for ($mm = 1; $mm <= 12; $mm++): ?>
                        <option value="<?= $mm ?>" <?= $mm == $m ? 'selected' : '' ?>><?= sprintf('%02d', $mm) ?></option>
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
                <th>日付</th>
                <th>プロジェクト</th>
                <th>作業内容</th>
                <th style="width:100px;">工数 (時間)</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5" style="text-align:center;color:#999;padding:32px;">工数データはありません</td>
            </tr>
        </tbody>
    </table>
</div>
