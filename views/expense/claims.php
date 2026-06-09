<?php
/**
 * PEGASUS ERP - Expense Claims List
 * Variables: $claims, $employees, $filters
 */
extract($viewData ?? []);
$claims    = $claims ?? [];
$employees = $employees ?? [];
$filters   = $filters ?? [];
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;"><?= _e('expense_claims') ?></h1>
    <a href="/expense/claims/create" class="btn btn-primary"><?= _e('new_claim') ?></a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 16px;">
        <form method="GET" action="/expense/claims" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;min-width:130px;">
                <label class="form-label"><?= _e('status') ?></label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="DRAFT" <?= ($filters['status'] ?? '') === 'DRAFT' ? 'selected' : '' ?>>Draft</option>
                    <option value="SUBMITTED" <?= ($filters['status'] ?? '') === 'SUBMITTED' ? 'selected' : '' ?>>Submitted</option>
                    <option value="APPROVED" <?= ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                    <option value="REJECTED" <?= ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
                    <option value="PAID" <?= ($filters['status'] ?? '') === 'PAID' ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:180px;">
                <label class="form-label"><?= _e('employee') ?></label>
                <select name="employee_id" class="form-select">
                    <option value=""><?= _e('all_employees') ?></option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= e($emp['employee_id']) ?>" <?= ($filters['employee_id'] ?? '') == $emp['employee_id'] ? 'selected' : '' ?>>
                            <?= e($emp['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:140px;">
                <label class="form-label"><?= _e('period') ?></label>
                <input type="month" name="period" class="form-input" value="<?= e($filters['period'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary"><?= _e('filter') ?></button>
            <a href="/expense/claims" class="btn btn-cancel"><?= _e('clear') ?></a>
        </form>
    </div>
</div>

<!-- Claims Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= _e('claim_no') ?></th>
                <th><?= _e('date') ?></th>
                <th><?= _e('employee') ?></th>
                <th>Title</th>
                <th class="text-right"><?= _e('amount') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($claims)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                        <?= _e('no_claims_found') ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($claims as $claim): ?>
                    <tr>
                        <td>
                            <a href="/expense/claims/<?= e($claim['claim_id']) ?>" style="font-weight:500;">
                                <?= e($claim['claim_no'] ?? '') ?>
                            </a>
                        </td>
                        <td><?= e(formatDate($claim['claim_date'] ?? '', 'd/m/Y')) ?></td>
                        <td><?= e($claim['full_name'] ?? '') ?></td>
                        <td><?= e($claim['title'] ?? '') ?></td>
                        <td class="text-right"><?= formatMoney($claim['total_amount_thb'] ?? 0) ?></td>
                        <td class="text-center">
                            <?php
                            $statusClass = match ($claim['status'] ?? '') {
                                'DRAFT'     => 'badge-draft',
                                'SUBMITTED' => 'badge-pending',
                                'APPROVED'  => 'badge-approved',
                                'REJECTED'  => 'badge-rejected',
                                'PAID'      => 'badge-paid',
                                default     => 'badge-draft',
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= e($claim['status'] ?? 'DRAFT') ?></span>
                        </td>
                        <td class="actions text-center">
                            <a href="/expense/claims/<?= e($claim['claim_id']) ?>" title="View">&#128065;</a>
                            <?php if (($claim['status'] ?? '') === 'DRAFT'): ?>
                                <a href="/expense/claims/<?= e($claim['claim_id']) ?>/edit" title="Edit">&#9998;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (!empty($claims)): ?>
        <div class="table-footer">
            <span><?= count($claims) ?> claim(s)</span>
        </div>
    <?php endif; ?>
</div>
