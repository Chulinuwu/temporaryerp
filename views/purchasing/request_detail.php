<?php
/** Variables: $pr, $lines, $attachments, $quotes, $suppliers,
 *             $canPurchasingApprove, $canManagerApprove, $canCeoApprove
 */
$statusClasses = [
    'DRAFT' => 'badge-draft', 'SUBMITTED' => 'badge-pending',
    'QUOTES_PENDING' => 'badge-pending',
    'PENDING_PURCHASING' => 'badge-pending', 'PENDING_MANAGER' => 'badge-open',
    'PENDING_CEO' => 'badge-open',
    'APPROVED' => 'badge-approved', 'REJECTED' => 'badge-rejected',
    'CONVERTED' => 'badge-paid', 'CANCELLED' => 'badge-rejected',
];
$reqName = $pr['requester_name_jp'] ?: $pr['requester_name_th'] ?: '-';

// Build comparison matrix: rows = PR lines, columns = up to 3 quotes
$lineWinnerByLine = [];
$cheapestUnitByLine = [];
foreach ($lines as $l) {
    $lid = (int)$l['pr_line_id'];
    $cheapestUnitByLine[$lid] = null;
    foreach ($quotes as $q) {
        $ql = $q['lines'][$lid] ?? null;
        if (!$ql) continue;
        $up = (float)$ql['unit_price'];
        if ($up > 0 && ($cheapestUnitByLine[$lid] === null || $up < $cheapestUnitByLine[$lid])) {
            $cheapestUnitByLine[$lid] = $up;
        }
        if (!empty($ql['is_winner'])) {
            $lineWinnerByLine[$lid] = (int)$q['quote_id'];
        }
    }
}
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($pr['pr_no']) ?>
            <span class="badge <?= $statusClasses[$pr['status']] ?? 'badge-draft' ?>" style="font-size:12px;vertical-align:middle;"><?= htmlspecialchars($pr['status']) ?></span>
        </h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/purchasing/requests"><?= _e('menu_purchase_requests') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= htmlspecialchars($pr['pr_no']) ?></span>
        </div>
    </div>
</div>

<!-- Approval flow stepper (always visible) -->
<div class="card" style="padding:14px 18px;margin-bottom:12px;">
    <h3 style="margin:0 0 6px 0;font-size:14px;color:#555;">🛣 <?= __('approval_flow_progress') ?></h3>
    <?php $stepper = ApprovalFlow::prStepper($pr); include __DIR__ . '/../partials/approval_stepper.php'; ?>
</div>

<!-- PR Header info -->
<div class="card" style="padding:20px;">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
        <div><strong><?= _e('requester') ?>:</strong><br><?= htmlspecialchars($reqName) ?></div>
        <div><strong><?= _e('department') ?>:</strong><br><?= htmlspecialchars($pr['department'] ?? '-') ?></div>
        <div><strong><?= _e('request_date') ?>:</strong><br><?= htmlspecialchars($pr['request_date']) ?></div>
        <div><strong><?= _e('needed_by') ?>:</strong><br><?= htmlspecialchars($pr['needed_by_date'] ?? '-') ?></div>
        <div style="grid-column:1/-1;"><strong><?= _e('justification') ?>:</strong><br><?= nl2br(htmlspecialchars($pr['justification'] ?? '')) ?></div>
        <?php if (!empty($pr['suggested_supplier_name'])): ?>
        <div><strong><?= _e('suggested_supplier') ?>:</strong><br><?= htmlspecialchars($pr['suggested_supplier_name']) ?></div>
        <?php endif; ?>
        <?php if (!empty($pr['pj_no'])): ?>
        <div><strong><?= _e('project') ?>:</strong><br>
            <a href="/projects/<?= (int)$pr['project_id'] ?>"><?= htmlspecialchars($pr['pj_no']) ?></a>
            — <?= htmlspecialchars($pr['pj_name']) ?>
        </div>
        <?php endif; ?>
        <div><strong><?= _e('est_total') ?>:</strong><br>฿<?= number_format((float)$pr['est_total_thb'], 2) ?></div>
    </div>
</div>

<!-- PR Line items -->
<div class="card" style="margin-top:16px;padding:20px;">
    <h3 style="margin-top:0;"><?= _e('pr_items') ?></h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th><th><?= _e('description') ?></th><th><?= _e('qty') ?></th>
                <th><?= _e('unit') ?></th><th><?= _e('unit_price') ?> (req.)</th>
                <th style="text-align:right;"><?= _e('line_total') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lines as $l): ?>
            <tr>
                <td><?= (int)$l['line_no'] ?></td>
                <td><?= htmlspecialchars($l['item_description']) ?></td>
                <td><?= rtrim(rtrim(number_format((float)$l['quantity'], 3),'0'),'.') ?></td>
                <td><?= htmlspecialchars($l['unit']) ?></td>
                <td>฿<?= number_format((float)$l['est_unit_price'], 4) ?></td>
                <td style="text-align:right;">฿<?= number_format((float)$l['est_line_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 3-quote comparison matrix -->
<div class="card" style="margin-top:16px;padding:20px;">
    <h3 style="margin-top:0;">📊 <?= _e('pr_3quote_comparison') ?>
        <span style="font-size:13px;color:#888;font-weight:400;">
            (<?= count($quotes) ?>/3 <?= _e('quotes_collected') ?>)
        </span>
    </h3>

    <?php if (count($quotes) === 0): ?>
        <p style="color:#888;"><?= _e('pr_no_quotes_yet') ?></p>
    <?php else: ?>
        <?php $needSelect = ($pr['status'] === 'QUOTES_PENDING' && $canPurchasingApprove); ?>
        <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/select-winners">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:240px;"><?= _e('description') ?></th>
                        <th style="text-align:right;"><?= _e('qty') ?></th>
                        <?php foreach ($quotes as $q): ?>
                            <th style="text-align:center;min-width:170px;<?= !empty($q['is_overall_winner']) ? 'background:#E8F5E9;' : '' ?>">
                                <strong>#<?= (int)$q['position'] ?>: <?= htmlspecialchars($q['supplier_name'] ?: $q['supplier_name_text'] ?: '—') ?></strong><br>
                                <small style="color:#666;"><?= htmlspecialchars($q['quote_no'] ?? '') ?>
                                    <?= $q['quote_date'] ? ('@' . htmlspecialchars($q['quote_date'])) : '' ?></small>
                                <?php if (!empty($q['attachment_id'])): ?>
                                    <br><a href="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/attachments/<?= (int)$q['attachment_id'] ?>" target="_blank">📎 <?= _e('view_pdf') ?? 'PDF' ?></a>
                                <?php endif; ?>
                                <?php if ($pr['status'] === 'QUOTES_PENDING' && $canPurchasingApprove): ?>
                                <br><form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/quotes/<?= (int)$q['quote_id'] ?>/delete"
                                          style="display:inline;" onsubmit="return confirm('<?= _e('confirm_delete') ?>');">
                                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                    <button class="btn btn-sm btn-danger" style="font-size:10px;padding:1px 6px;">×</button>
                                </form>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $l):
                        $lid = (int)$l['pr_line_id'];
                        $cheapest = $cheapestUnitByLine[$lid] ?? null;
                    ?>
                    <tr>
                        <td><?= (int)$l['line_no'] ?>. <?= htmlspecialchars($l['item_description']) ?></td>
                        <td style="text-align:right;"><?= rtrim(rtrim(number_format((float)$l['quantity'], 3),'0'),'.') ?> <?= htmlspecialchars($l['unit']) ?></td>
                        <?php foreach ($quotes as $q):
                            $ql = $q['lines'][$lid] ?? null;
                            $up = $ql ? (float)$ql['unit_price'] : null;
                            $lt = $ql ? (float)$ql['line_total'] : null;
                            $isCheapest = $up !== null && $cheapest !== null && abs($up - $cheapest) < 0.0001 && $up > 0;
                            $isWinner = $ql && !empty($ql['is_winner']);
                            $bg = $isCheapest ? 'background:#FFF9C4;' : '';
                            if ($isWinner) $bg = 'background:#A5D6A7;font-weight:700;';
                        ?>
                        <td style="text-align:right;<?= $bg ?>">
                            <?php if ($up !== null): ?>
                                ฿<?= number_format($up, 4) ?>
                                <br><small style="color:#666;">= ฿<?= number_format($lt, 2) ?></small>
                                <?php if ($isCheapest): ?><br><small style="color:#F57C00;">🏆 <?= _e('cheapest') ?></small><?php endif; ?>
                                <?php if ($needSelect): ?>
                                    <br><label style="font-size:11px;font-weight:400;">
                                        <input type="radio" name="winners[<?= $lid ?>]" value="<?= (int)$q['quote_id'] ?>"
                                            <?= $isWinner ? 'checked' : '' ?>> <?= _e('pick') ?>
                                    </label>
                                <?php elseif ($isWinner): ?>
                                    <br><small style="color:#2E7D32;">✓ <?= _e('selected') ?></small>
                                <?php endif; ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <!-- Totals row -->
                    <tr style="background:#F5F5F5;font-weight:700;">
                        <td colspan="2" style="text-align:right;"><?= _e('total') ?>:</td>
                        <?php foreach ($quotes as $q): ?>
                        <td style="text-align:right;<?= !empty($q['is_overall_winner']) ? 'background:#A5D6A7;' : '' ?>">
                            ฿<?= number_format((float)$q['total_amount_thb'], 2) ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
            <?php if ($needSelect): ?>
                <div style="margin-top:12px;">
                    <button class="btn btn-primary"><?= _e('pr_save_winners') ?></button>
                    <small style="color:#666;margin-left:12px;"><?= _e('pr_winners_hint') ?></small>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>

    <!-- Add Quote form (purchasing officer only, only in QUOTES_PENDING) -->
    <?php if ($pr['status'] === 'QUOTES_PENDING' && $canPurchasingApprove && count($quotes) < 3): ?>
    <hr style="margin:20px 0;">
    <h4>➕ <?= _e('pr_add_quote') ?></h4>
    <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/quotes" enctype="multipart/form-data">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:12px;">
            <div>
                <label><?= _e('quote_position') ?></label>
                <select name="position" class="form-select">
                    <?php for ($p=1;$p<=3;$p++):
                        $taken = false;
                        foreach ($quotes as $q) if ((int)$q['position']===$p) $taken = true;
                        if ($taken) continue;
                    ?>
                    <option value="<?= $p ?>">#<?= $p ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label><?= _e('supplier') ?></label>
                <select name="supplier_id" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= (int)$s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_code'].' / '.$s['supplier_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><?= _e('supplier_name_text') ?> <small style="color:#888;">(<?= _e('if_not_in_master') ?>)</small></label>
                <input type="text" name="supplier_name_text" class="form-input" maxlength="200">
            </div>
            <div>
                <label><?= _e('quote_no') ?></label>
                <input type="text" name="quote_no" class="form-input" maxlength="100">
            </div>
            <div>
                <label><?= _e('quote_date') ?></label>
                <input type="date" name="quote_date" class="form-input">
            </div>
            <div>
                <label><?= _e('lead_time_days') ?></label>
                <input type="number" min="0" name="lead_time_days" class="form-input">
            </div>
            <div>
                <label><?= _e('payment_terms') ?></label>
                <input type="text" name="payment_terms" class="form-input" maxlength="120">
            </div>
            <div>
                <label><?= _e('quote_pdf') ?> <span style="color:red;">*</span></label>
                <input type="file" name="quote_pdf" accept=".pdf,.jpg,.jpeg,.png" required class="form-input">
            </div>
        </div>
        <h4 style="margin-top:16px;"><?= _e('per_line_prices') ?></h4>
        <table class="data-table">
            <thead><tr><th>#</th><th><?= _e('description') ?></th><th><?= _e('qty') ?></th><th><?= _e('unit_price') ?></th></tr></thead>
            <tbody>
            <?php foreach ($lines as $l): ?>
                <tr>
                    <td><?= (int)$l['line_no'] ?></td>
                    <td><?= htmlspecialchars($l['item_description']) ?></td>
                    <td><?= rtrim(rtrim(number_format((float)$l['quantity'], 3),'0'),'.') ?> <?= htmlspecialchars($l['unit']) ?></td>
                    <td><input type="number" step="0.0001" min="0" name="prices[<?= (int)$l['pr_line_id'] ?>]" class="form-input" style="width:140px;" required></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div>
            <label><?= _e('notes') ?></label>
            <textarea name="notes" class="form-input" rows="2"></textarea>
        </div>
        <div style="margin-top:12px;">
            <button class="btn btn-primary"><?= _e('pr_save_quote') ?></button>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- General Attachments -->
<div class="card" style="margin-top:16px;padding:20px;">
    <h3 style="margin-top:0;"><?= _e('pr_attachments') ?>
        <span style="font-size:13px;color:#888;font-weight:400;">(<?= count($attachments ?? []) ?>)</span>
    </h3>
    <?php if (!empty($attachments)): ?>
    <table class="data-table">
        <thead>
            <tr><th><?= _e('file') ?></th><th><?= _e('description') ?></th>
                <th><?= _e('size') ?></th><th><?= _e('uploaded_by') ?></th>
                <th><?= _e('uploaded_at') ?></th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($attachments as $a): ?>
            <tr>
                <td><a href="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/attachments/<?= (int)$a['attachment_id'] ?>" target="_blank">📎 <?= htmlspecialchars($a['file_name']) ?></a></td>
                <td><?= htmlspecialchars($a['description'] ?? '') ?></td>
                <td><?= number_format((int)$a['file_size'] / 1024, 1) ?> KB</td>
                <td><?= htmlspecialchars($a['uploaded_by_email'] ?? '-') ?></td>
                <td><?= htmlspecialchars($a['uploaded_at']) ?></td>
                <td>
                    <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/attachments/<?= (int)$a['attachment_id'] ?>/delete"
                          style="display:inline;" onsubmit="return confirm('<?= _e('confirm_delete') ?>');">
                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                        <button class="btn btn-sm btn-danger">×</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#888;"><?= _e('no_attachments') ?></p>
    <?php endif; ?>

    <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/attachments" enctype="multipart/form-data"
          style="margin-top:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <input type="file" name="attachments[]" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx" required>
        <input type="text" name="attachment_descriptions[]" class="form-input" placeholder="<?= _e('description') ?>" style="width:280px;">
        <button class="btn btn-primary"><?= _e('upload') ?></button>
    </form>
</div>

<!-- Approval trace -->
<div class="card" style="margin-top:16px;padding:20px;">
    <h3 style="margin-top:0;"><?= _e('approval_history') ?></h3>
    <table class="data-table">
        <tr>
            <th style="width:30%;"><?= _e('pr_step_purchasing') ?></th>
            <td>
                <?php if ($pr['purchasing_approved_at']): ?>
                    ✓ <?= htmlspecialchars($pr['purchasing_approver_email'] ?? '') ?> @ <?= htmlspecialchars($pr['purchasing_approved_at']) ?>
                    <?php if (!empty($pr['purchasing_note'])): ?><br><small><?= nl2br(htmlspecialchars($pr['purchasing_note'])) ?></small><?php endif; ?>
                <?php else: ?><span style="color:#888;">— pending —</span><?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><?= _e('pr_step_manager') ?></th>
            <td>
                <?php if ($pr['manager_approved_at']): ?>
                    ✓ <?= htmlspecialchars($pr['manager_approver_email'] ?? '') ?> @ <?= htmlspecialchars($pr['manager_approved_at']) ?>
                    <?php if (!empty($pr['manager_note'])): ?><br><small><?= nl2br(htmlspecialchars($pr['manager_note'])) ?></small><?php endif; ?>
                <?php else: ?><span style="color:#888;">— pending —</span><?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><?= _e('pr_step_ceo') ?></th>
            <td>
                <?php if (!empty($pr['ceo_approved_at'])): ?>
                    ✓ CEO @ <?= htmlspecialchars($pr['ceo_approved_at']) ?>
                    <?php if (!empty($pr['ceo_note'])): ?><br><small><?= nl2br(htmlspecialchars($pr['ceo_note'])) ?></small><?php endif; ?>
                <?php else: ?><span style="color:#888;">— pending —</span><?php endif; ?>
            </td>
        </tr>
        <?php if ($pr['status'] === 'REJECTED'): ?>
        <tr>
            <th><?= _e('rejected') ?></th>
            <td>
                ✗ <?= htmlspecialchars($pr['rejected_by_email'] ?? '') ?> @ <?= htmlspecialchars($pr['rejected_at']) ?><br>
                <strong><?= _e('reason') ?>:</strong> <?= nl2br(htmlspecialchars($pr['rejection_reason'] ?? '')) ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php if ($pr['status'] === 'CONVERTED' && $pr['converted_po_id']): ?>
        <tr>
            <th>PO</th>
            <td>→ <a href="/purchasing/orders/<?= (int)$pr['converted_po_id'] ?>">PO #<?= (int)$pr['converted_po_id'] ?></a></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<!-- Action buttons (state-dependent) -->
<div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
    <?php if ($pr['status'] === 'DRAFT'): ?>
        <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/submit" style="display:inline;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button class="btn btn-primary" onclick="return confirm('<?= _e('confirm_submit') ?>');"><?= _e('pr_submit') ?></button>
        </form>
    <?php endif; ?>

    <?php if ($pr['status'] === 'SUBMITTED' && $canPurchasingApprove): ?>
        <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/start-quotes" style="display:inline;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button class="btn btn-primary"><?= _e('pr_start_quotes') ?></button>
        </form>
    <?php endif; ?>

    <?php if ($pr['status'] === 'QUOTES_PENDING' && $canPurchasingApprove && count($quotes) >= 3): ?>
        <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/submit-manager" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="text" name="note" class="form-input" placeholder="<?= _e('note_optional') ?>" style="width:240px;">
            <button class="btn btn-success"><?= _e('pr_submit_manager') ?></button>
        </form>
    <?php endif; ?>

    <?php if ($pr['status'] === 'PENDING_MANAGER' && $canManagerApprove): ?>
        <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/approve-manager" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="text" name="note" class="form-input" placeholder="<?= _e('note_optional') ?>" style="width:240px;">
            <button class="btn btn-success"><?= _e('pr_approve_manager') ?></button>
        </form>
    <?php endif; ?>

    <?php if ($pr['status'] === 'PENDING_CEO' && $canCeoApprove): ?>
        <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/approve-ceo" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="text" name="note" class="form-input" placeholder="<?= _e('note_optional') ?>" style="width:240px;">
            <button class="btn btn-success" style="background:#1B5E20;"><?= _e('pr_approve_ceo') ?></button>
        </form>
    <?php endif; ?>

    <?php
    $active = ['SUBMITTED','QUOTES_PENDING','PENDING_MANAGER','PENDING_CEO'];
    $canReject = (
        ($pr['status'] === 'PENDING_CEO' && $canCeoApprove) ||
        ($pr['status'] === 'PENDING_MANAGER' && $canManagerApprove) ||
        (in_array($pr['status'], ['SUBMITTED','QUOTES_PENDING'], true) && $canPurchasingApprove)
    );
    if (in_array($pr['status'], $active, true) && $canReject): ?>
        <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/reject" style="display:flex;gap:8px;align-items:center;"
              onsubmit="return confirm('<?= _e('confirm_reject') ?>');">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="text" name="reason" class="form-input" placeholder="<?= _e('rejection_reason') ?> *" required style="width:240px;">
            <button class="btn btn-danger"><?= _e('reject') ?></button>
        </form>
    <?php endif; ?>

    <?php /* PR-side "Create PO" button removed: PO creation must be initiated from
              /purchasing/orders/create (which has the mandatory PR picker). */ ?>

    <?php $cancelable = ['DRAFT','SUBMITTED','QUOTES_PENDING','PENDING_MANAGER','PENDING_CEO'];
    if (in_array($pr['status'], $cancelable, true)): ?>
        <form method="POST" action="/purchasing/requests/<?= (int)$pr['pr_id'] ?>/cancel" style="display:inline;"
              onsubmit="return confirm('<?= _e('confirm_cancel') ?>');">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button class="btn btn-secondary"><?= _e('cancel') ?></button>
        </form>
    <?php endif; ?>

    <a href="/purchasing/requests" class="btn btn-secondary">← <?= _e('back') ?></a>
</div>
