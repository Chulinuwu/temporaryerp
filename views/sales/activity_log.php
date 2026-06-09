<?php
/**
 * PEGASUS ERP - Activity Log (活動ログ)
 * Standalone activity log page matching Excel "Activity Log" sheet
 * Fields: Date, Sales Person, Action Type, Company Name, Contact Name, Action/Comment, Next Action, Due Date
 * Variables: $activities, $filters, $activityCategories, $customers, $salesPersons
 */
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('activity_log') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/deals"><?= _e('sales') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('activity_log') ?></span>
        </div>
    </div>
</div>

<!-- Add New Activity Form -->
<?php $pre = $prefill ?? null; ?>
<div class="card" style="margin-bottom:16px;<?= $pre ? 'border:2px solid #FB8C00;background:#FFF8E1;' : '' ?>" id="activityFormCard">
    <div class="card-body" style="padding:16px 20px;">
        <h3 style="margin:0 0 12px;font-size:14px;font-weight:600;">
            <?= $pre ? '🔁 ' . __('followup_activity') : _e('new_activity') ?>
            <?php if ($pre): ?>
                <span style="font-size:12px;color:#E65100;font-weight:400;">
                    — <?= __('from') ?>
                    <?= e($pre['activity_date']) ?> /
                    <?= e($pre['parent_customer_name'] ?? $pre['company_name']) ?>
                    / <?= e(mb_substr($pre['subject'] ?? '', 0, 40)) ?>
                </span>
                <a href="/sales/activities" style="margin-left:10px;font-size:12px;">✕ <?= __('cancel_followup') ?></a>
            <?php endif; ?>
        </h3>
        <form method="POST" action="/sales/activities">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <?php if ($pre): ?>
                <input type="hidden" name="parent_activity_id" value="<?= e($pre['activity_id']) ?>">
            <?php endif; ?>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
                <!-- Date -->
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label"><?= _e('date') ?> <span class="required">*</span></label>
                    <input type="date" name="activity_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                </div>
                <!-- Sales Person -->
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label"><?= _e('sales_person') ?></label>
                    <select name="sales_person_id" class="form-select" onchange="this.form.sales_person_name.value=this.options[this.selectedIndex].text">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($salesPersons as $sp): ?>
                            <option value="<?= e($sp['employee_id']) ?>" <?= $pre && $pre['sales_person_id'] == $sp['employee_id'] ? 'selected' : '' ?>>
                                <?= e($sp['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="sales_person_name" value="<?= e($pre['sales_person_name'] ?? '') ?>">
                </div>
                <!-- Action Type -->
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label"><?= _e('activity_type') ?> <span class="required">*</span></label>
                    <select name="activity_category_id" class="form-select" required>
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($activityCategories as $ac): ?>
                            <option value="<?= e($ac['category_id']) ?>" <?= $pre && $pre['activity_category_id'] == $ac['category_id'] ? 'selected' : '' ?>>
                                <?= e($ac['icon'] . ' ' . $ac['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Company Name -->
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label"><?= _e('company_name') ?></label>
                    <select name="customer_id" class="form-select" onchange="this.form.company_name.value=this.options[this.selectedIndex].text">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= e($c['customer_id']) ?>" <?= $pre && $pre['customer_id'] == $c['customer_id'] ? 'selected' : '' ?>>
                                <?= e($c['customer_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="company_name" value="<?= e($pre['company_name'] ?? '') ?>">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 2fr 2fr 1fr;gap:10px;margin-top:10px;">
                <!-- Contact Name -->
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label"><?= _e('contact_name') ?></label>
                    <input type="text" name="contact_person" class="form-input" value="<?= e($pre['contact_person'] ?? '') ?>">
                </div>
                <!-- Action / Comment -->
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label"><?= _e('action_comment') ?> <span class="required">*</span></label>
                    <input type="text" name="subject" class="form-input" required
                           value="<?= $pre ? e('[Follow-up] ' . ($pre['next_action'] ?? $pre['subject'] ?? '')) : '' ?>">
                </div>
                <!-- Next Action (required) -->
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label"><?= _e('next_action') ?> <span class="required">*</span></label>
                    <input type="text" name="next_action" class="form-input" required>
                </div>
                <!-- Due Date (required) -->
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label"><?= _e('due_date') ?> <span class="required">*</span></label>
                    <input type="date" name="next_action_date" class="form-input" required>
                </div>
            </div>
            <div style="margin-top:12px;text-align:right;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <?= $pre ? '🔁 ' . __('save_followup') : _e('add_activity') ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php if ($pre): ?>
<script>
// Auto-scroll to the pre-filled form when arriving from a follow-up link
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('activityFormCard');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card" style="padding:12px 20px;margin-bottom:16px;">
    <form method="GET" action="/sales/activities" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_from" class="form-input" style="width:140px;" value="<?= e($filters['date_from'] ?? '') ?>" placeholder="<?= __('from_date') ?>">
        </div>
        <span style="color:var(--color-text-muted);">~</span>
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_to" class="form-input" style="width:140px;" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <select name="type" class="form-select" style="width:150px;">
                <option value=""><?= _e('all_types') ?></option>
                <?php foreach ($activityCategories as $ac): ?>
                    <option value="<?= e($ac['category_name']) ?>" <?= ($filters['type'] ?? '') === $ac['category_name'] ? 'selected' : '' ?>>
                        <?= e($ac['icon'] . ' ' . $ac['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <select name="sales" class="form-select" style="width:150px;">
                <option value=""><?= _e('all_sales_persons') ?></option>
                <?php foreach ($salesPersons as $sp): ?>
                    <option value="<?= e($sp['employee_id']) ?>" <?= ($filters['sales'] ?? '') == $sp['employee_id'] ? 'selected' : '' ?>>
                        <?= e($sp['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="q" class="form-input" style="width:200px;" value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= __('search') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/sales/activities" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Activity Table -->
<div class="table-wrapper" style="overflow-x:auto;">
    <table class="data-table" style="min-width:1100px;">
        <thead>
            <tr>
                <th style="width:100px;"><?= _e('date') ?></th>
                <th style="width:110px;"><?= _e('sales_person') ?></th>
                <th style="width:120px;"><?= _e('activity_type') ?></th>
                <th style="min-width:140px;"><?= _e('company_name') ?></th>
                <th style="width:110px;"><?= _e('contact_name') ?></th>
                <th style="min-width:200px;"><?= _e('action_comment') ?></th>
                <th style="min-width:160px;"><?= _e('next_action') ?></th>
                <th style="width:100px;"><?= _e('due_date') ?></th>
                <th style="width:50px;" class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($activities)): ?>
                <?php foreach ($activities as $act): ?>
                    <?php
                    $isDueOverdue = false;
                    if (!empty($act['next_action_date'])) {
                        $isDueOverdue = strtotime($act['next_action_date']) < time();
                    }
                    ?>
                    <tr>
                        <td><?= e($act['activity_date'] ?? '') ?></td>
                        <td style="font-size:12px;"><?= e($act['sales_person_name'] ?? '') ?></td>
                        <td>
                            <span class="badge badge-draft" style="font-size:10px;">
                                <?= e(($act['action_icon'] ?? '') . ' ' . ($act['action_type'] ?? '')) ?>
                            </span>
                        </td>
                        <td>
                            <?= e($act['company_name'] ?? $act['customer_name'] ?? '') ?>
                            <?php if (!empty($act['deal_no'])): ?>
                                <div style="font-size:10px;color:var(--color-text-muted);">
                                    <a href="/sales/deals/<?= e($act['deal_id']) ?>"><?= e($act['deal_no']) ?></a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;"><?= e($act['contact_person'] ?? '') ?></td>
                        <td style="font-size:12px;"><?= e($act['subject'] ?? '') ?></td>
                        <td style="font-size:12px;">
                            <?php if (!empty($act['next_action'])): ?>
                                <span style="color:var(--color-primary);">&#9654; <?= e($act['next_action']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="<?= $isDueOverdue ? 'color:var(--color-danger);font-weight:600;' : '' ?>">
                            <?= e($act['next_action_date'] ?? '') ?>
                        </td>
                        <td class="text-center" style="white-space:nowrap;">
                            <?php $fc = $followCountMap[(int)$act['activity_id']] ?? 0; ?>
                            <a href="/sales/activities?followup_from=<?= e($act['activity_id']) ?>"
                               style="display:inline-block;padding:3px 8px;font-size:11px;background:#FFF3E0;color:#E65100;border:1px solid #FFB74D;border-radius:4px;text-decoration:none;font-weight:600;margin-right:4px;"
                               title="<?= __('create_followup') ?>">
                                🔁 <?= __('followup_short') ?>
                                <?php if ($fc > 0): ?>
                                    <span style="background:#E65100;color:#fff;padding:1px 6px;border-radius:8px;margin-left:3px;"><?= $fc ?></span>
                                <?php endif; ?>
                            </a>
                            <?php if (!empty($act['parent_activity_id'])): ?>
                                <span style="font-size:10px;color:#888;" title="<?= __('is_followup_hint') ?>">↳</span>
                            <?php endif; ?>
                            <form method="POST" action="/sales/activities/<?= e($act['activity_id']) ?>/delete" style="display:inline;">
                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')"
                                        style="background:none;border:none;cursor:pointer;font-size:13px;color:#999;" title="<?= __('delete') ?>">&#128465;</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('no_activities') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
