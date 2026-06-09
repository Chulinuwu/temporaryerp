<?php
/**
 * PEGASUS ERP - General Ledger
 * Variables: $ledgerEntries, $accountSummary, $accounts, $filters
 */
$pageTitle = 'General Ledger - PEGASUS ERP';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('general_ledger') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/accounting/journal"><?= _e('accounting') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('general_ledger') ?></span>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/accounting/ledger" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="account_id" class="form-select" style="width:280px;">
                <option value="">-- All Accounts --</option>
                <?php foreach ($accounts ?? [] as $acc): ?>
                    <option value="<?= htmlspecialchars($acc['account_id']) ?>" <?= ($filters['account_id'] ?? '') == $acc['account_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_from" class="form-input" style="width:150px;" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" placeholder="From">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_to" class="form-input" style="width:150px;" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" placeholder="To">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/accounting/ledger" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Account Summary -->
<?php if (!empty($accountSummary)): ?>
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue">&#128203;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($accountSummary['opening_balance'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('opening_balance') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green">&#128200;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($accountSummary['total_debit'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('total_debit') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon orange">&#128201;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($accountSummary['total_credit'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('total_credit') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon blue">&#128176;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($accountSummary['closing_balance'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('closing_balance') ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Ledger Detail Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th class="sortable"><?= _e('date') ?></th>
                <th><?= _e('je_no') ?></th>
                <th><?= _e('account') ?></th>
                <th><?= _e('description') ?></th>
                <th class="text-right"><?= _e('debit') ?></th>
                <th class="text-right"><?= _e('credit') ?></th>
                <th class="text-right"><?= _e('balance') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($accountSummary) && isset($accountSummary['opening_balance'])): ?>
                <tr style="background:#F5F5F5;font-weight:600;">
                    <td colspan="6"><?= _e('opening_balance') ?></td>
                    <td class="text-right"><?= formatMoney($accountSummary['opening_balance']) ?></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($ledgerEntries)): ?>
                <?php foreach ($ledgerEntries as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['je_date']) ?></td>
                        <td><a href="/accounting/journal/<?= htmlspecialchars($entry['je_id']) ?>"><?= htmlspecialchars($entry['je_no']) ?></a></td>
                        <td><?= htmlspecialchars(($entry['account_code'] ?? '') . ' ' . ($entry['account_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($entry['description'] ?? '') ?></td>
                        <td class="text-right"><?= ($entry['debit_amount'] ?? 0) > 0 ? formatMoney($entry['debit_amount']) : '-' ?></td>
                        <td class="text-right"><?= ($entry['credit_amount'] ?? 0) > 0 ? formatMoney($entry['credit_amount']) : '-' ?></td>
                        <td class="text-right"><strong><?= formatMoney($entry['running_balance'] ?? 0) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('no_entries_found') ?></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($accountSummary) && isset($accountSummary['closing_balance'])): ?>
                <tr style="background:#F5F5F5;font-weight:700;">
                    <td colspan="4"><?= _e('closing_balance') ?></td>
                    <td class="text-right"><?= formatMoney($accountSummary['total_debit'] ?? 0) ?></td>
                    <td class="text-right"><?= formatMoney($accountSummary['total_credit'] ?? 0) ?></td>
                    <td class="text-right"><?= formatMoney($accountSummary['closing_balance']) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
