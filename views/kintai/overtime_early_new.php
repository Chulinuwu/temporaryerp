<?php
$y = (int) date('Y'); $m = (int) date('n'); $d = (int) date('j');
?>
<h1 class="kt-page-title">新規早出残業申請</h1>

<div class="kt-card">
    <form action="/kintai/overtime/early" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= function_exists('csrf_token') ? csrf_token() : '' ?>">
        <div class="kt-card-body">

            <div class="kt-form-row">
                <div class="kt-form-label">
                    早出残業希望日
                    <span class="req">(必須)</span>
                </div>
                <div class="kt-form-control">
                    <div class="kt-date-group">
                        <select class="kt-spinner" name="year">
                            <?php for ($yy = $y - 1; $yy <= $y + 1; $yy++): ?>
                                <option <?= $yy == $y ? 'selected' : '' ?>><?= $yy ?></option>
                            <?php endfor; ?>
                        </select><span class="unit">年</span>
                        <select class="kt-spinner" name="month">
                            <?php for ($mm = 1; $mm <= 12; $mm++): ?>
                                <option value="<?= sprintf('%02d', $mm) ?>" <?= $mm == $m ? 'selected' : '' ?>><?= sprintf('%02d', $mm) ?></option>
                            <?php endfor; ?>
                        </select><span class="unit">月</span>
                        <select class="kt-spinner" name="day">
                            <?php for ($dd = 1; $dd <= 31; $dd++): ?>
                                <option value="<?= sprintf('%02d', $dd) ?>" <?= $dd == $d ? 'selected' : '' ?>><?= sprintf('%02d', $dd) ?></option>
                            <?php endfor; ?>
                        </select><span class="unit">日</span>
                        <button type="button" class="kt-icon-btn" title="カレンダー">&#128197;</button>
                    </div>
                </div>
            </div>

            <div class="kt-form-row">
                <div class="kt-form-label">
                    早出残業開始時刻
                    <span class="req">(必須)</span>
                </div>
                <div class="kt-form-control">
                    <div class="kt-time-group">
                        <select class="kt-spinner" name="start_hour">
                            <?php for ($hh = 0; $hh <= 23; $hh++): ?>
                                <option><?= sprintf('%02d', $hh) ?></option>
                            <?php endfor; ?>
                        </select><span class="unit">時</span>
                        <select class="kt-spinner" name="start_min">
                            <?php for ($mi = 0; $mi < 60; $mi += 1): ?>
                                <option><?= sprintf('%02d', $mi) ?></option>
                            <?php endfor; ?>
                        </select><span class="unit">分</span>
                        <span class="unit">～</span>
                    </div>
                </div>
            </div>

            <div class="kt-form-row">
                <div class="kt-form-label">
                    早出残業理由
                    <span class="req">(必須)</span>
                </div>
                <div class="kt-form-control">
                    <textarea class="kt-textarea" name="reason" required rows="6"></textarea>
                </div>
            </div>

        </div>
        <div class="kt-card-footer">
            <button type="submit" class="kt-btn kt-btn-primary">確認画面に進む</button>
        </div>
    </form>
</div>
