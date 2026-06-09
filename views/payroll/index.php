<?php
/**
 * PEGASUS ERP - Payroll Management
 * Variables: $payrollPeriods, $divisions
 */
extract($viewData ?? []);
$payrollPeriods = $payrollPeriods ?? [];
$divisions      = $divisions ?? [];
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;">Payroll</h1>
    <button class="btn btn-primary" onclick="document.getElementById('calcModal').classList.add('active')">Calculate Payroll</button>
</div>

<!-- Payroll Periods Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Period</th>
                <th>Division</th>
                <th>Pay Date</th>
                <th class="text-right">Employees</th>
                <th class="text-right">Gross Total</th>
                <th class="text-right">Net Total</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payrollPeriods)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                        No payroll periods found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($payrollPeriods as $pp): ?>
                    <tr>
                        <td>
                            <a href="/payroll/<?= e($pp['payroll_id']) ?>" style="font-weight:500;">
                                <?= e($pp['period'] ?? '') ?>
                            </a>
                        </td>
                        <td><?= e($pp['division_name'] ?? 'All') ?></td>
                        <td><?= e(formatDate($pp['pay_date'] ?? '', 'd/m/Y')) ?></td>
                        <td class="text-right">-</td>
                        <td class="text-right"><?= formatMoney($pp['total_gross_thb'] ?? 0) ?></td>
                        <td class="text-right"><?= formatMoney($pp['total_net_thb'] ?? 0) ?></td>
                        <td class="text-center">
                            <?php
                            $statusClass = match ($pp['status'] ?? '') {
                                'DRAFT'     => 'badge-draft',
                                'CALCULATED' => 'badge-pending',
                                'APPROVED'  => 'badge-approved',
                                'PAID'      => 'badge-paid',
                                default     => 'badge-draft',
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= e($pp['status'] ?? 'DRAFT') ?></span>
                        </td>
                        <td class="actions text-center">
                            <a href="/payroll/<?= e($pp['payroll_id']) ?>" title="View Detail">&#128065;</a>
                            <?php if (($pp['status'] ?? '') === 'DRAFT'): ?>
                                <a href="/payroll/<?= e($pp['payroll_id']) ?>/edit" title="Edit">&#9998;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (!empty($payrollPeriods)): ?>
        <div class="table-footer">
            <span><?= count($payrollPeriods) ?> period(s)</span>
        </div>
    <?php endif; ?>
</div>

<!-- Calculate Payroll Modal -->
<div class="modal-overlay" id="calcModal">
    <div class="modal" style="width:480px;">
        <div class="modal-header">
            <h3 class="modal-title">Calculate Payroll</h3>
            <button class="modal-close" onclick="document.getElementById('calcModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" action="/payroll/calculate">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Period (YYYY-MM) <span class="required">*</span></label>
                    <input type="month" name="period" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Pay Date <span class="required">*</span></label>
                    <input type="date" name="pay_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Division</label>
                    <select name="division_id" class="form-select">
                        <option value="">All Divisions</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?= e($div['division_id']) ?>"><?= e($div['division_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="document.getElementById('calcModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Calculate</button>
            </div>
        </form>
    </div>
</div>
