<?php
/**
 * PEGASUS ERP - Profit & Loss Statement
 * Variables: $plData, $filters, $periodLabel
 */
$pageTitle = 'Profit & Loss - PEGASUS ERP';

// Helper to render a section
function renderPLSection(array $items, string $label, string $indent = ''): float {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['amount'] ?? 0;
    }
    return $total;
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('pl_statement') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/accounting/journal"><?= _e('accounting') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('pl_statement') ?></span>
        </div>
    </div>
</div>

<!-- Period Selector -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/accounting/pl" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="period_type" class="form-select" style="width:140px;">
                <option value="month" <?= ($filters['period_type'] ?? 'month') === 'month' ? 'selected' : '' ?>>Monthly</option>
                <option value="range" <?= ($filters['period_type'] ?? '') === 'range' ? 'selected' : '' ?>>Date Range</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="month" name="month" class="form-input" style="width:160px;" value="<?= htmlspecialchars($filters['month'] ?? date('Y-m')) ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_from" class="form-input" style="width:150px;" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" placeholder="From">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_to" class="form-input" style="width:150px;" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" placeholder="To">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('generate') ?></button>
    </form>
</div>

<!-- P&L Statement -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Profit &amp; Loss for <?= htmlspecialchars($periodLabel ?? date('F Y')) ?></h3>
    </div>
    <div class="card-body" style="max-width:700px;">

        <?php
        $revenue = $plData['revenue'] ?? [];
        $cogs = $plData['cogs'] ?? [];
        $sellingExp = $plData['selling_expenses'] ?? [];
        $adminExp = $plData['admin_expenses'] ?? [];
        $otherIncome = $plData['other_income'] ?? [];
        $otherExp = $plData['other_expenses'] ?? [];
        $incomeTax = $plData['income_tax'] ?? [];

        $totalRevenue = $plData['total_revenue'] ?? 0;
        $totalCogs = $plData['total_cogs'] ?? 0;
        $grossProfit = $totalRevenue - $totalCogs;
        $totalSelling = $plData['total_selling_expenses'] ?? 0;
        $totalAdmin = $plData['total_admin_expenses'] ?? 0;
        $operatingProfit = $grossProfit - $totalSelling - $totalAdmin;
        $totalOtherIncome = $plData['total_other_income'] ?? 0;
        $totalOtherExp = $plData['total_other_expenses'] ?? 0;
        $profitBeforeTax = $operatingProfit + $totalOtherIncome - $totalOtherExp;
        $totalTax = $plData['total_income_tax'] ?? 0;
        $netProfit = $profitBeforeTax - $totalTax;
        ?>

        <!-- Revenue (4xxxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:700;font-size:14px;color:var(--color-text-primary);margin-bottom:6px;"><?= _e('revenue') ?></div>
            <?php foreach ($revenue as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['amount']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:14px;">
                <span><?= _e('total_revenue') ?></span>
                <span><?= formatMoney($totalRevenue) ?></span>
            </div>
        </div>

        <!-- COGS (5xxxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:700;font-size:14px;color:var(--color-text-primary);margin-bottom:6px;"><?= _e('cogs') ?></div>
            <?php foreach ($cogs as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['amount']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:14px;">
                <span><?= _e('total_cogs') ?></span>
                <span>(<?= formatMoney($totalCogs) ?>)</span>
            </div>
        </div>

        <!-- Gross Profit -->
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:2px solid var(--color-primary);border-bottom:2px solid var(--color-primary);font-weight:700;font-size:15px;color:var(--color-primary);margin-bottom:16px;">
            <span><?= _e('gross_profit') ?></span>
            <span><?= formatMoney($grossProfit) ?></span>
        </div>

        <!-- Selling Expenses (61xxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:700;font-size:14px;color:var(--color-text-primary);margin-bottom:6px;"><?= _e('selling_expenses') ?></div>
            <?php foreach ($sellingExp as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['amount']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span><?= _e('selling_expenses') ?></span>
                <span>(<?= formatMoney($totalSelling) ?>)</span>
            </div>
        </div>

        <!-- Admin Expenses (62xxx-64xxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:700;font-size:14px;color:var(--color-text-primary);margin-bottom:6px;"><?= _e('admin_expenses') ?></div>
            <?php foreach ($adminExp as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['amount']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span><?= _e('admin_expenses') ?></span>
                <span>(<?= formatMoney($totalAdmin) ?>)</span>
            </div>
        </div>

        <!-- Operating Profit -->
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:2px solid var(--color-border);font-weight:700;font-size:15px;margin-bottom:16px;">
            <span><?= _e('operating_profit') ?></span>
            <span style="color:<?= $operatingProfit >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>"><?= formatMoney($operatingProfit) ?></span>
        </div>

        <!-- Other Income (7xxxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:700;font-size:14px;color:var(--color-text-primary);margin-bottom:6px;"><?= _e('other_income') ?></div>
            <?php foreach ($otherIncome as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['amount']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span><?= _e('other_income') ?></span>
                <span><?= formatMoney($totalOtherIncome) ?></span>
            </div>
        </div>

        <!-- Other Expenses (8xxxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:700;font-size:14px;color:var(--color-text-primary);margin-bottom:6px;"><?= _e('other_expenses') ?></div>
            <?php foreach ($otherExp as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['amount']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span><?= _e('other_expenses') ?></span>
                <span>(<?= formatMoney($totalOtherExp) ?>)</span>
            </div>
        </div>

        <!-- Profit Before Tax -->
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:2px solid var(--color-border);font-weight:700;font-size:15px;margin-bottom:16px;">
            <span><?= _e('ebt') ?></span>
            <span style="color:<?= $profitBeforeTax >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>"><?= formatMoney($profitBeforeTax) ?></span>
        </div>

        <!-- Income Tax (9xxxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:700;font-size:14px;color:var(--color-text-primary);margin-bottom:6px;"><?= _e('income_tax') ?></div>
            <?php foreach ($incomeTax as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['amount']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span><?= _e('income_tax') ?></span>
                <span>(<?= formatMoney($totalTax) ?>)</span>
            </div>
        </div>

        <!-- Net Profit -->
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:3px double var(--color-text-primary);font-weight:700;font-size:17px;">
            <span><?= _e('net_profit') ?></span>
            <span style="color:<?= $netProfit >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>"><?= formatMoney($netProfit) ?></span>
        </div>

    </div>
</div>
