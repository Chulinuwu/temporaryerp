<?php
/**
 * PEGASUS ERP - Balance Sheet
 * Variables: $bsData, $asOfDate
 */
$pageTitle = 'Balance Sheet - PEGASUS ERP';

$currentAssets = $bsData['current_assets'] ?? [];
$nonCurrentAssets = $bsData['non_current_assets'] ?? [];
$currentLiabilities = $bsData['current_liabilities'] ?? [];
$nonCurrentLiabilities = $bsData['non_current_liabilities'] ?? [];
$equity = $bsData['equity'] ?? [];

$totalCurrentAssets = $bsData['total_current_assets'] ?? 0;
$totalNonCurrentAssets = $bsData['total_non_current_assets'] ?? 0;
$totalAssets = $totalCurrentAssets + $totalNonCurrentAssets;

$totalCurrentLiab = $bsData['total_current_liabilities'] ?? 0;
$totalNonCurrentLiab = $bsData['total_non_current_liabilities'] ?? 0;
$totalLiabilities = $totalCurrentLiab + $totalNonCurrentLiab;

$totalEquity = $bsData['total_equity'] ?? 0;
$totalLiabEquity = $totalLiabilities + $totalEquity;
$isBalanced = abs($totalAssets - $totalLiabEquity) < 0.01;
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('balance_sheet') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/accounting/journal"><?= _e('accounting') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('balance_sheet') ?></span>
        </div>
    </div>
</div>

<!-- Date Selector -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/accounting/bs" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" style="margin-bottom:0;margin-right:8px;display:inline;">As of Date</label>
            <input type="date" name="as_of_date" class="form-input" style="width:180px;display:inline-block;" value="<?= htmlspecialchars($asOfDate ?? date('Y-m-d')) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('generate') ?></button>
    </form>
</div>

<!-- Balance Sheet -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Balance Sheet as of <?= htmlspecialchars($asOfDate ?? date('Y-m-d')) ?></h3>
        <?php if (!$isBalanced): ?>
            <span class="badge badge-rejected">OUT OF BALANCE</span>
        <?php else: ?>
            <span class="badge badge-approved">BALANCED</span>
        <?php endif; ?>
    </div>
    <div class="card-body" style="max-width:700px;">

        <!-- ========== ASSETS ========== -->
        <div style="font-weight:700;font-size:16px;color:var(--color-primary);margin-bottom:12px;border-bottom:2px solid var(--color-primary);padding-bottom:4px;"><?= _e('assets') ?></div>

        <!-- Current Assets (1xxxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:600;font-size:14px;margin-bottom:6px;">Current Assets</div>
            <?php foreach ($currentAssets as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['balance']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span>Total Current Assets</span>
                <span><?= formatMoney($totalCurrentAssets) ?></span>
            </div>
        </div>

        <!-- Non-Current Assets (15xxx-17xxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:600;font-size:14px;margin-bottom:6px;">Non-Current Assets</div>
            <?php foreach ($nonCurrentAssets as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['balance']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span>Total Non-Current Assets</span>
                <span><?= formatMoney($totalNonCurrentAssets) ?></span>
            </div>
        </div>

        <!-- Total Assets -->
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:2px solid var(--color-text-primary);font-weight:700;font-size:15px;margin-bottom:24px;">
            <span><?= _e('total_assets') ?></span>
            <span><?= formatMoney($totalAssets) ?></span>
        </div>

        <!-- ========== LIABILITIES ========== -->
        <div style="font-weight:700;font-size:16px;color:var(--color-primary);margin-bottom:12px;border-bottom:2px solid var(--color-primary);padding-bottom:4px;"><?= _e('liabilities') ?></div>

        <!-- Current Liabilities (2xxxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:600;font-size:14px;margin-bottom:6px;">Current Liabilities</div>
            <?php foreach ($currentLiabilities as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['balance']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span>Total Current Liabilities</span>
                <span><?= formatMoney($totalCurrentLiab) ?></span>
            </div>
        </div>

        <!-- Non-Current Liabilities (26xxx) -->
        <div style="margin-bottom:16px;">
            <div style="font-weight:600;font-size:14px;margin-bottom:6px;">Non-Current Liabilities</div>
            <?php foreach ($nonCurrentLiabilities as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['balance']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span>Total Non-Current Liabilities</span>
                <span><?= formatMoney($totalNonCurrentLiab) ?></span>
            </div>
        </div>

        <!-- Total Liabilities -->
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:2px solid var(--color-border);font-weight:700;font-size:15px;margin-bottom:16px;">
            <span><?= _e('total_liabilities') ?></span>
            <span><?= formatMoney($totalLiabilities) ?></span>
        </div>

        <!-- ========== EQUITY ========== -->
        <div style="font-weight:700;font-size:16px;color:var(--color-primary);margin-bottom:12px;border-bottom:2px solid var(--color-primary);padding-bottom:4px;"><?= _e('equity') ?></div>

        <div style="margin-bottom:16px;">
            <?php foreach ($equity as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0 3px 20px;font-size:13px;">
                    <span><?= htmlspecialchars($item['account_code'] . ' ' . $item['account_name']) ?></span>
                    <span><?= formatMoney($item['balance']) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--color-border);font-weight:600;font-size:13px;">
                <span><?= _e('total_equity') ?></span>
                <span><?= formatMoney($totalEquity) ?></span>
            </div>
        </div>

        <!-- Total Liabilities + Equity -->
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:3px double var(--color-text-primary);font-weight:700;font-size:17px;">
            <span><?= _e('total_liabilities_equity') ?></span>
            <span style="color:<?= $isBalanced ? 'var(--color-success)' : 'var(--color-danger)' ?>"><?= formatMoney($totalLiabEquity) ?></span>
        </div>

    </div>
</div>
