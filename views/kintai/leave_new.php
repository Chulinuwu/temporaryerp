<?php
/** @var array $leaveTypes */
$leaveTypes = $leaveTypes ?? [];
$y = (int) date('Y'); $m = (int) date('n'); $d = (int) date('j');
?>
<h1 class="kt-page-title">新規休暇申請</h1>

<div class="kt-card">
    <form action="/kintai/leave" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= function_exists('csrf_token') ? csrf_token() : '' ?>">
        <div class="kt-card-body">

            <div class="kt-form-row">
                <div class="kt-form-label">
                    休暇名
                    <span class="req">(必須)</span>
                </div>
                <div class="kt-form-control">
                    <select class="kt-select" name="leave_type" required style="min-width:520px;">
                        <?php foreach ($leaveTypes as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="kt-form-row">
                <div class="kt-form-label">
                    休暇希望日
                    <span class="req">(必須)</span>
                </div>
                <div class="kt-form-control">
                    <div class="kt-date-group">
                        <select class="kt-spinner" name="year">
                            <?php for ($yy = $y - 2; $yy <= $y + 1; $yy++): ?>
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
                <div class="kt-form-label">休暇範囲</div>
                <div class="kt-form-control">
                    <span style="color:#999;">-</span>
                </div>
            </div>

            <div class="kt-form-row">
                <div class="kt-form-label">休暇理由</div>
                <div class="kt-form-control">
                    <div style="color:var(--kt-required);font-size:12px;margin-bottom:6px;">
                        (必須。管理者により必須モードが選択されています。)
                    </div>
                    <textarea class="kt-textarea" name="reason" rows="6"></textarea>
                </div>
            </div>

        </div>
        <div class="kt-card-footer">
            <button type="submit" class="kt-btn kt-btn-primary">確認画面に進む</button>
        </div>
    </form>
</div>
