<?php
/**
 * Placeholder for reports that have not been implemented yet.
 * Vars: $reportName
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">🚧 <?= e($reportName ?? 'Report') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/reports"><?= _e('report_list') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= e($reportName ?? 'Report') ?></span>
        </div>
    </div>
    <a href="/reports" class="btn btn-cancel">← <?= __('back_to_reports') ?? 'Back to Reports' ?></a>
</div>

<div class="card" style="padding:40px;text-align:center;background:#FFF8E1;border-left:4px solid #FB8C00;">
    <div style="font-size:48px;margin-bottom:16px;">🚧</div>
    <h2 style="margin-bottom:8px;color:#E65100;">
        <?= e($reportName ?? 'Report') ?> — <?= __('coming_soon') ?? 'Coming Soon' ?>
    </h2>
    <p style="color:#666;max-width:600px;margin:0 auto 20px;">
        <?= __('report_coming_soon_note') ?? 'このレポートは現在開発中です。既存の分析機能をご利用ください。' ?>
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:20px;">
        <a href="/analytics/quotations" class="btn btn-primary">📊 Quotation Analytics</a>
        <a href="/analytics/purchasing" class="btn btn-primary">📊 Purchasing Analytics</a>
        <a href="/cashflow/forecast" class="btn btn-primary">💰 Cashflow Forecast</a>
        <a href="/accounting/pl" class="btn btn-primary">📈 P&L</a>
        <a href="/accounting/bs" class="btn btn-primary">📒 Balance Sheet</a>
    </div>
</div>
