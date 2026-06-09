<?php
/**
 * Reusable approval stepper widget.
 *
 * Expected $stepper variable (array):
 *   [
 *     'current_status' => 'PENDING_CEO',   // current status string
 *     'rejected'       => false,           // bool
 *     'reject_reason'  => null,
 *     'steps' => [
 *        ['key'=>'DRAFT',           'label'=>'起票', 'at'=>'2026-05-17 10:00', 'by'=>'tanaka@x'],
 *        ['key'=>'PENDING_MANAGER', 'label'=>'マネージャー承認', 'at'=>null, 'by'=>null],
 *        ['key'=>'PENDING_CEO',     'label'=>'CEO 最終承認',    'at'=>null, 'by'=>null],
 *        ['key'=>'APPROVED',        'label'=>'完了',           'at'=>null, 'by'=>null],
 *     ],
 *   ]
 *
 * Renders a small horizontal stepper with:
 *   - ● Filled green     : completed step (has $step['at'])
 *   - ◐ Animated yellow  : current pending step (= matches current_status)
 *   - ○ Outline grey      : not yet
 *   - ✗ Red filled       : if 'rejected' is true, the current pending step is marked rejected
 */

if (empty($stepper) || empty($stepper['steps'])) return;

$current = $stepper['current_status'] ?? '';
$rejected = !empty($stepper['rejected']);

// Determine which step indexes are completed / current / pending
$indexCurrent = -1;
foreach ($stepper['steps'] as $i => $st) {
    if ($st['key'] === $current) { $indexCurrent = $i; break; }
}
// Fallback: if APPROVED isn't in steps but status=APPROVED → all done
$allDone = ($current === 'APPROVED' || ($indexCurrent === count($stepper['steps']) - 1 && !empty($stepper['steps'][$indexCurrent]['at'])));
?>
<div class="approval-stepper-wrap" style="margin:8px 0 14px 0;">
    <div class="approval-stepper" style="display:flex;align-items:stretch;font-size:11.5px;line-height:1.3;">
    <?php foreach ($stepper['steps'] as $i => $st):
        $isCompleted = !empty($st['at']);
        $isCurrent   = ($i === $indexCurrent) && !$isCompleted;
        $isRejectedHere = $rejected && $isCurrent;
        // Visual state
        if ($isRejectedHere) { $bg='#D32F2F'; $fg='#fff'; $mark='✗'; $line='#D32F2F'; }
        elseif ($isCompleted) { $bg='#2E7D32'; $fg='#fff'; $mark='✓'; $line='#2E7D32'; }
        elseif ($isCurrent)   { $bg='#FFA000'; $fg='#fff'; $mark=(string)($i+1); $line='#E0E0E0'; }
        else                  { $bg='#fff';    $fg='#9E9E9E'; $mark=(string)($i+1); $line='#E0E0E0'; }
        $border = ($isCurrent && !$isRejectedHere) ? '2px solid #FFA000' : '2px solid '.$bg;
    ?>
        <div style="display:flex;flex-direction:column;align-items:center;min-width:90px;flex:1;">
            <div style="display:flex;align-items:center;width:100%;">
                <div style="flex:1;height:2px;background:<?= $i===0 ? 'transparent' : $line ?>;"></div>
                <div style="width:26px;height:26px;border-radius:50%;background:<?= $bg ?>;color:<?= $fg ?>;
                            display:flex;align-items:center;justify-content:center;font-weight:700;
                            border:<?= $border ?>;
                            <?= $isCurrent && !$isRejectedHere ? 'box-shadow:0 0 0 4px rgba(255,160,0,0.15);' : '' ?>">
                    <?= $mark ?>
                </div>
                <div style="flex:1;height:2px;background:<?= $i===count($stepper['steps'])-1 ? 'transparent' : ($isCompleted ? '#2E7D32' : '#E0E0E0') ?>;"></div>
            </div>
            <div style="margin-top:4px;text-align:center;color:<?= $isCompleted || $isCurrent ? '#212121' : '#9E9E9E' ?>;font-weight:<?= $isCurrent ? '700' : '500' ?>;">
                <?= htmlspecialchars($st['label']) ?>
            </div>
            <div style="font-size:10px;color:#666;text-align:center;min-height:14px;">
                <?php if ($isCompleted): ?>
                    <?= htmlspecialchars(substr((string)$st['at'], 0, 16)) ?>
                    <?php if (!empty($st['by'])): ?>
                        <br><span style="color:#888;"><?= htmlspecialchars($st['by']) ?></span>
                    <?php endif; ?>
                <?php elseif ($isRejectedHere): ?>
                    <span style="color:#D32F2F;font-weight:700;">REJECTED</span>
                <?php elseif ($isCurrent): ?>
                    <span style="color:#FF8F00;font-weight:600;">⏳ <?= __('in_review') ?? 'in review' ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php if ($rejected && !empty($stepper['reject_reason'])): ?>
        <div style="margin-top:8px;background:#FFEBEE;border-left:4px solid #D32F2F;padding:6px 10px;font-size:12px;color:#B71C1C;">
            <strong>✗ <?= __('rejection_reason') ?>:</strong>
            <?= nl2br(htmlspecialchars($stepper['reject_reason'])) ?>
        </div>
    <?php endif; ?>
</div>
