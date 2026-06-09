<?php
/**
 * PEGASUS ERP - Deal Detail (案件詳細)
 * Variables: $deal, $activities, $quotations, $activityCategories
 */
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($deal['deal_no']) ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/deals"><?= _e('deals') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= e($deal['deal_no']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/sales/deals/<?= e($deal['deal_id']) ?>/edit" class="btn btn-cancel"><?= _e('edit') ?></a>
        <a href="/sales/deals" class="btn btn-cancel"><?= _e('back') ?></a>
    </div>
</div>

<!-- Deal Summary -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;">
    <!-- Main Info -->
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <h3 style="margin:0 0 16px;font-size:18px;"><?= e($deal['deal_name']) ?></h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('customer') ?>:</span>
                    <strong><?= e($deal['customer_name'] ?? '') ?></strong>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('customer_staff') ?>:</span>
                    <?= e($deal['customer_staff'] ?? '-') ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('solution') ?>:</span>
                    <?= e($deal['solution_name'] ?? '-') ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('touch_point') ?>:</span>
                    <?= e($deal['touch_point'] ?? '-') ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('sales_person') ?>:</span>
                    <?= e($deal['sales_person_name'] ?? '-') ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('pj_no') ?>:</span>
                    <?= e($deal['pj_no'] ?? '-') ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('expected_close') ?>:</span>
                    <?= e($deal['expected_close'] ?? '-') ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('related_projects') ?>:</span>
                    <?= e($deal['related_projects'] ?? '-') ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('first_contact_date') ?>:</span>
                    <?= e($deal['first_contact_date'] ?? '-') ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('budget_status') ?>:</span>
                    <?= e($deal['budget_status'] ?? '-') ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('budget_amount') ?>:</span>
                    <?= ($deal['budget_amount'] ?? 0) > 0 ? formatMoney($deal['budget_amount']) : '-' ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('win_rate') ?>:</span>
                    <?= e($deal['win_rate'] ?? 0) ?>%
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('est_revenue') ?>:</span>
                    <?= ($deal['est_revenue'] ?? 0) > 0 ? formatMoney($deal['est_revenue']) : '-' ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('est_profit') ?>:</span>
                    <?= ($deal['est_profit'] ?? 0) > 0 ? formatMoney($deal['est_profit']) : '-' ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('eval_profit') ?>:</span>
                    <?= ($deal['eval_profit'] ?? 0) > 0 ? number_format($deal['eval_profit'], 2) . '%' : '-' ?>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('due_date') ?>:</span>
                    <?= e($deal['due_date'] ?? '-') ?>
                </div>
                <?php if (!empty($deal['next_action'])): ?>
                <div style="grid-column:1/3;">
                    <span style="color:var(--color-text-muted);"><?= _e('next_action') ?>:</span>
                    <span style="color:var(--color-primary);"><?= e($deal['next_action']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($deal['meeting_notes'])): ?>
                <div style="grid-column:1/3;">
                    <span style="color:var(--color-text-muted);"><?= _e('meeting_notes') ?>:</span>
                    <?= nl2br(e($deal['meeting_notes'])) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($deal['note'])): ?>
                <div style="grid-column:1/3;">
                    <span style="color:var(--color-text-muted);"><?= _e('note') ?>:</span>
                    <?= nl2br(e($deal['note'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status & Amount -->
    <div>
        <div class="card" style="margin-bottom:12px;">
            <div class="card-body" style="padding:20px;text-align:center;">
                <div style="margin-bottom:12px;">
                    <span class="badge" style="background:<?= e($deal['color'] ?? '#757575') ?>;color:#fff;font-size:14px;padding:6px 16px;">
                        <?= e($deal['status_name'] ?? '') ?> (<?= $deal['win_pct'] ?? 0 ?>%)
                    </span>
                </div>
                <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= _e('expected_amount') ?></div>
                <div style="font-size:28px;font-weight:700;color:var(--color-primary);">
                    <?= formatMoney($deal['expected_amount'] ?? 0) ?>
                </div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:4px;">
                    <?= _e('weighted') ?>: <?= formatMoney(($deal['expected_amount'] ?? 0) * ($deal['win_pct'] ?? 0) / 100) ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card">
            <div class="card-body" style="padding:16px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                    <span style="color:var(--color-text-muted);"><?= _e('activities') ?></span>
                    <strong><?= count($activities) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--color-text-muted);"><?= _e('quotations') ?></span>
                    <strong><?= count($quotations) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs: Activities / Quotations -->
<div class="card">
    <div style="border-bottom:1px solid var(--color-border);padding:0 20px;">
        <button class="tab-btn active" onclick="showTab('activities')" id="tab-activities"
                style="padding:12px 20px;border:none;background:none;font-weight:600;font-size:13px;cursor:pointer;border-bottom:2px solid var(--color-primary);color:var(--color-primary);">
            <?= _e('activity_log') ?> (<?= count($activities) ?>)
        </button>
        <button class="tab-btn" onclick="showTab('quotations')" id="tab-quotations"
                style="padding:12px 20px;border:none;background:none;font-weight:600;font-size:13px;cursor:pointer;color:var(--color-text-muted);">
            <?= _e('quotations') ?> (<?= count($quotations) ?>)
        </button>
    </div>

    <!-- Activities Tab -->
    <div id="panel-activities" class="tab-panel" style="padding:20px;">
        <!-- Add Activity Form -->
        <div style="background:var(--color-bg-secondary);border-radius:8px;padding:16px;margin-bottom:20px;">
            <h4 style="margin:0 0 12px;font-size:14px;"><?= _e('new_activity') ?></h4>
            <form method="POST" action="/sales/deals/<?= e($deal['deal_id']) ?>/activities">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label"><?= _e('activity_type') ?></label>
                        <select name="activity_category_id" class="form-select" required>
                            <option value="">-- <?= __('select') ?> --</option>
                            <?php foreach ($activityCategories as $ac): ?>
                                <option value="<?= e($ac['category_id']) ?>"><?= e($ac['icon'] . ' ' . $ac['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label"><?= _e('date') ?></label>
                        <input type="date" name="activity_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label"><?= _e('contact_person') ?> <span class="required">*</span></label>
                        <select name="contact_id" class="form-select" required>
                            <option value="">-- <?= __('select_contact') ?> --</option>
                            <?php foreach (($dealContacts ?? []) as $cc): ?>
                                <option value="<?= e($cc['contact_id']) ?>">
                                    <?= e($cc['full_name']) ?><?= !empty($cc['title']) ? ' (' . e($cc['title']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($dealContacts ?? [])): ?>
                            <div style="font-size:11px;color:#D32F2F;margin-top:2px;">
                                <a href="/sales/customers/<?= e($deal['customer_id'] ?? '') ?>" target="_blank">
                                    <?= __('register_contact_first') ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:10px;margin-top:10px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label"><?= _e('subject') ?> <span class="required">*</span></label>
                        <input type="text" name="subject" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label"><?= __('start_time') ?></label>
                        <input type="time" name="start_time" class="form-input" id="actStart" onchange="calcDur()">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label"><?= __('end_time') ?></label>
                        <input type="time" name="end_time" class="form-input" id="actEnd" onchange="calcDur()">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label"><?= _e('duration') ?> (<?= __('minutes') ?>)</label>
                        <input type="number" name="duration_min" id="actDur" class="form-input" min="0" readonly style="background:#f5f5f5;">
                    </div>
                </div>
                <div class="form-group" style="margin-top:10px;margin-bottom:0;">
                    <label class="form-label"><?= __('minutes_record') ?> (議事)</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="<?= __('minutes_placeholder') ?>"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;margin-top:10px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label"><?= _e('next_action') ?> <span class="required">*</span></label>
                        <input type="text" name="next_action" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label"><?= _e('next_action_date') ?> <span class="required">*</span></label>
                        <input type="date" name="next_action_date" class="form-input" required>
                    </div>
                </div>
                <script>
                    function calcDur() {
                        var s = document.getElementById('actStart').value;
                        var e = document.getElementById('actEnd').value;
                        if (s && e) {
                            var [sh, sm] = s.split(':').map(Number);
                            var [eh, em] = e.split(':').map(Number);
                            var diff = (eh*60+em) - (sh*60+sm);
                            if (diff > 0) document.getElementById('actDur').value = diff;
                        }
                    }
                </script>
                <div style="margin-top:12px;text-align:right;">
                    <button type="submit" class="btn btn-primary btn-sm"><?= _e('add_activity') ?></button>
                </div>
            </form>
        </div>

        <!-- Activity Timeline -->
        <?php if (!empty($activities)): ?>
            <div class="activity-timeline">
                <?php foreach ($activities as $act): ?>
                    <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--color-border);">
                        <div style="font-size:20px;width:36px;text-align:center;"><?= e($act['activity_icon'] ?? '📝') ?></div>
                        <div style="flex:1;">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                                <div>
                                    <strong style="font-size:13px;"><?= e($act['subject']) ?></strong>
                                    <span class="badge badge-draft" style="margin-left:6px;font-size:10px;">
                                        <?= e($act['activity_type'] ?? '') ?>
                                    </span>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="font-size:11px;color:var(--color-text-muted);"><?= e($act['activity_date']) ?></span>
                                    <form method="POST" action="/sales/deals/<?= e($deal['deal_id']) ?>/activities/<?= e($act['activity_id']) ?>/delete" style="display:inline;">
                                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                        <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')"
                                                style="background:none;border:none;cursor:pointer;font-size:12px;color:#999;" title="<?= __('delete') ?>">&#128465;</button>
                                    </form>
                                </div>
                            </div>
                            <?php if (!empty($act['description'])): ?>
                                <div style="font-size:12px;color:var(--color-text-secondary);margin-top:4px;">
                                    <?= nl2br(e($act['description'])) ?>
                                </div>
                            <?php endif; ?>
                            <div style="font-size:11px;color:var(--color-text-muted);margin-top:6px;display:flex;gap:16px;">
                                <?php if (!empty($act['contact_person'])): ?>
                                    <span>&#128100; <?= e($act['contact_person']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($act['duration_min'])): ?>
                                    <span>&#9200; <?= e($act['duration_min']) ?><?= __('minutes') ?></span>
                                <?php endif; ?>
                                <?php if (!empty($act['next_action'])): ?>
                                    <span style="color:var(--color-primary);">&#9654; <?= e($act['next_action']) ?>
                                        <?php if (!empty($act['next_action_date'])): ?>
                                            (<?= e($act['next_action_date']) ?>)
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center;padding:40px;color:var(--color-text-muted);">
                <?= _e('no_activities') ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quotations Tab -->
    <div id="panel-quotations" class="tab-panel" style="padding:20px;display:none;">
        <!-- Action buttons -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-primary btn-sm" onclick="openQuotationModal()">+ <?= __('add_quotation') ?></button>
            </div>
            <?php if (!empty($quotations)): ?>
            <div>
                <form method="POST" action="/sales/deals/<?= e($deal['deal_id']) ?>/convert-order" style="display:inline;" id="form-convert-order">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-sm" style="background:#2196F3;color:#fff;border:none;"
                            onclick="return confirm('<?= __('confirm_convert_order') ?>')">
                        &#128230; <?= __('convert_to_order') ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Linked quotation totals -->
        <?php if (!empty($quotations)):
            $qtTotal = 0;
            foreach ($quotations as $q) { $qtTotal += floatval($q['grand_total_thb'] ?? 0); }
        ?>
        <div style="background:#f0f7ff;border:1px solid #bbd4f0;border-radius:6px;padding:10px 16px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:13px;color:#003366;">
                <?= count($quotations) ?> <?= __('quotations') ?> <?= __('linked') ?>
            </span>
            <span style="font-size:16px;font-weight:700;color:#003366;">
                <?= __('total') ?>: <?= formatMoney($qtTotal) ?>
            </span>
        </div>
        <?php endif; ?>

        <?php if (!empty($quotations)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= _e('quotation_no') ?></th>
                        <th><?= _e('date') ?></th>
                        <th><?= _e('project') ?></th>
                        <th class="text-right"><?= _e('subtotal') ?></th>
                        <th class="text-right"><?= _e('grand_total') ?></th>
                        <th class="text-center"><?= _e('status') ?></th>
                        <th class="text-center"><?= _e('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotations as $q): ?>
                        <tr>
                            <td><a href="/sales/quotations/<?= e($q['quotation_id']) ?>" style="font-weight:500;"><?= e($q['quotation_no']) ?></a></td>
                            <td><?= e($q['issue_date'] ?? '') ?></td>
                            <td style="font-size:12px;"><?= e($q['project_name'] ?? '-') ?></td>
                            <td class="text-right"><?= formatMoney($q['subtotal_thb'] ?? 0) ?></td>
                            <td class="text-right"><strong><?= formatMoney($q['grand_total_thb'] ?? 0) ?></strong></td>
                            <td class="text-center">
                                <?php
                                    $qStatusCls = [
                                        'DRAFT'=>'badge-draft','SUBMITTED'=>'badge-open','NEGOTIATING'=>'badge-pending',
                                        'WON'=>'badge-approved','LOST'=>'badge-rejected','APPROVED'=>'badge-approved',
                                    ];
                                ?>
                                <span class="badge <?= $qStatusCls[$q['status']] ?? 'badge-draft' ?>"><?= e($q['status'] ?? '') ?></span>
                            </td>
                            <td class="text-center actions" style="white-space:nowrap;">
                                <a href="/sales/quotations/<?= e($q['quotation_id']) ?>" title="<?= __('view') ?>">&#128065;</a>
                                <a href="/pdf/quotation/<?= e($q['quotation_id']) ?>" target="_blank" title="PDF">&#128424;</a>
                                <form method="POST" action="/sales/deals/<?= e($deal['deal_id']) ?>/quotations/<?= e($q['quotation_id']) ?>/unlink" style="display:inline;">
                                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" onclick="return confirm('<?= __('confirm_unlink') ?>')"
                                            style="background:none;border:none;cursor:pointer;font-size:13px;color:#F44336;" title="<?= __('unlink') ?>">&#10005;</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align:center;padding:40px;color:var(--color-text-muted);">
                <?= _e('msg_no_quotations') ?>
                <div style="margin-top:12px;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="openQuotationModal()">+ <?= __('add_quotation') ?></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== Quotation Search Modal ===== -->
<div id="qt-modal-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:8px;width:800px;max-width:90vw;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
        <!-- Modal Header -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--color-border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;font-size:16px;"><?= __('select_quotations') ?></h3>
            <button onclick="closeQuotationModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#999;">&times;</button>
        </div>

        <!-- Search -->
        <div style="padding:12px 20px;border-bottom:1px solid var(--color-border);">
            <div style="display:flex;gap:8px;">
                <input type="text" id="qt-search-input" class="form-input" placeholder="<?= __('search_quotation_placeholder') ?>" style="flex:1;" oninput="searchQuotationsDebounced()">
                <span style="font-size:12px;color:var(--color-text-muted);align-self:center;"><?= __('customer') ?>: <strong><?= e($deal['customer_name'] ?? '') ?></strong></span>
            </div>
        </div>

        <!-- Results -->
        <div style="flex:1;overflow-y:auto;padding:0;" id="qt-search-results">
            <div style="text-align:center;padding:40px;color:var(--color-text-muted);"><?= __('msg_search_quotations') ?></div>
        </div>

        <!-- Footer -->
        <div style="padding:12px 20px;border-top:1px solid var(--color-border);display:flex;justify-content:space-between;align-items:center;">
            <span id="qt-selected-count" style="font-size:12px;color:var(--color-text-muted);">0 <?= __('selected') ?></span>
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-cancel btn-sm" onclick="closeQuotationModal()"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="submitLinkedQuotations()" id="btn-link-qt" disabled><?= __('link_quotations') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for linking quotations -->
<form id="form-link-quotations" method="POST" action="/sales/deals/<?= e($deal['deal_id']) ?>/quotations" style="display:none;">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
</form>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.style.display = 'none'; });
    document.querySelectorAll('.tab-btn').forEach(function(b) {
        b.style.borderBottom = 'none';
        b.style.color = 'var(--color-text-muted)';
    });
    document.getElementById('panel-' + name).style.display = 'block';
    var btn = document.getElementById('tab-' + name);
    btn.style.borderBottom = '2px solid var(--color-primary)';
    btn.style.color = 'var(--color-primary)';
}

// Auto-switch to quotations tab if URL hash is #quotations
if (window.location.hash === '#quotations') {
    showTab('quotations');
}

/* ── Quotation Search Modal ── */
var searchTimer = null;
var selectedQtIds = new Set();

function openQuotationModal() {
    document.getElementById('qt-modal-overlay').style.display = 'flex';
    document.getElementById('qt-search-input').value = '';
    selectedQtIds.clear();
    updateSelectedCount();
    searchQuotations();
    setTimeout(function() { document.getElementById('qt-search-input').focus(); }, 100);
}

function closeQuotationModal() {
    document.getElementById('qt-modal-overlay').style.display = 'none';
}

function searchQuotationsDebounced() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(searchQuotations, 300);
}

function searchQuotations() {
    var q = document.getElementById('qt-search-input').value;
    var dealId = <?= intval($deal['deal_id']) ?>;
    var url = '/sales/deals/' + dealId + '/quotations/search?q=' + encodeURIComponent(q);

    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            renderQuotationResults(data);
        })
        .catch(function(err) {
            document.getElementById('qt-search-results').innerHTML =
                '<div style="padding:20px;text-align:center;color:#F44336;">Error loading quotations</div>';
        });
}

function renderQuotationResults(quotations) {
    var container = document.getElementById('qt-search-results');
    if (!quotations || quotations.length === 0) {
        container.innerHTML = '<div style="padding:40px;text-align:center;color:var(--color-text-muted);"><?= __("msg_no_quotations") ?></div>';
        return;
    }

    // Already linked quotation IDs for this deal
    var linkedIds = [<?= implode(',', array_map(function($q) { return intval($q['quotation_id']); }, $quotations)) ?>];

    var html = '<table class="data-table" style="margin:0;font-size:12px;">';
    html += '<thead><tr>';
    html += '<th style="width:30px;" class="text-center"></th>';
    html += '<th><?= __("quotation_no") ?></th>';
    html += '<th><?= __("date") ?></th>';
    html += '<th><?= __("project") ?></th>';
    html += '<th class="text-right"><?= __("grand_total") ?></th>';
    html += '<th class="text-center"><?= __("status") ?></th>';
    html += '</tr></thead><tbody>';

    quotations.forEach(function(q) {
        var isLinked = linkedIds.indexOf(q.quotation_id) >= 0;
        var isLinkedOther = q.deal_id && q.deal_id != <?= intval($deal['deal_id']) ?>;
        var isChecked = selectedQtIds.has(q.quotation_id);
        var disabled = isLinked || isLinkedOther;

        html += '<tr style="' + (isLinked ? 'background:#f0f7ff;' : '') + (isLinkedOther ? 'opacity:0.5;' : '') + '">';
        html += '<td class="text-center">';
        if (disabled) {
            html += '<input type="checkbox" disabled ' + (isLinked ? 'checked' : '') + '>';
        } else {
            html += '<input type="checkbox" class="qt-check" value="' + q.quotation_id + '" ' + (isChecked ? 'checked' : '') + ' onchange="toggleQtSelection(this)">';
        }
        html += '</td>';
        html += '<td><strong>' + escHtml(q.quotation_no) + '</strong></td>';
        html += '<td>' + escHtml(q.issue_date || '') + '</td>';
        html += '<td>' + escHtml(q.project_name || '-') + '</td>';
        html += '<td class="text-right">' + formatNum(q.grand_total_thb) + '</td>';
        html += '<td class="text-center"><span class="badge badge-draft" style="font-size:10px;">' + escHtml(q.status || '') + '</span>';
        if (isLinked) html += ' <span style="font-size:10px;color:#4CAF50;">&#10003; <?= __("linked") ?></span>';
        if (isLinkedOther) html += ' <span style="font-size:10px;color:#999;"><?= __("linked_other") ?></span>';
        html += '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

function toggleQtSelection(cb) {
    var id = parseInt(cb.value);
    if (cb.checked) {
        selectedQtIds.add(id);
    } else {
        selectedQtIds.delete(id);
    }
    updateSelectedCount();
}

function updateSelectedCount() {
    var count = selectedQtIds.size;
    document.getElementById('qt-selected-count').textContent = count + ' <?= __("selected") ?>';
    document.getElementById('btn-link-qt').disabled = count === 0;
}

function submitLinkedQuotations() {
    if (selectedQtIds.size === 0) return;
    var form = document.getElementById('form-link-quotations');
    // Remove old inputs
    form.querySelectorAll('input[name="quotation_ids[]"]').forEach(function(el) { el.remove(); });
    // Add selected IDs
    selectedQtIds.forEach(function(id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'quotation_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    form.submit();
}

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function formatNum(val) {
    if (!val) return '฿0.00';
    return '฿' + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeQuotationModal();
});
// Close modal on overlay click
document.getElementById('qt-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) closeQuotationModal();
});
</script>
