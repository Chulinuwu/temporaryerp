<?php
/**
 * PEGASUS ERP — Sales Customer List
 * Variables: $customers, $filters, $salesPersons
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('menu_sales_customers') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/deals"><?= _e('sales') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('menu_sales_customers') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/master/customers" class="btn btn-cancel"><?= _e('customer_master') ?></a>
    </div>
</div>

<!-- Filters -->
<div class="card" style="padding:12px 20px;margin-bottom:16px;">
    <form method="GET" action="/sales/customers" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input type="text" name="q" class="form-input" style="width:220px;"
               value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= __('search') ?> (<?= __('name') ?> / <?= __('email') ?>)">
        <select name="sales_rep" class="form-select" style="width:180px;">
            <option value=""><?= _e('all_sales_persons') ?></option>
            <?php foreach ($salesPersons as $sp): ?>
                <option value="<?= e($sp['employee_id']) ?>" <?= ($filters['sales_rep'] ?? '') == $sp['employee_id'] ? 'selected' : '' ?>>
                    <?= e($sp['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label style="font-size:12px;display:flex;align-items:center;gap:4px;">
            <input type="checkbox" name="has_deals" value="1" <?= ($filters['has_deals'] ?? '') === '1' ? 'checked' : '' ?>>
            <?= __('has_deals') ?>
        </label>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/sales/customers" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Summary -->
<?php
$totalPipeline = array_sum(array_map(fn($c) => floatval($c['pipeline_amount'] ?? 0), $customers));
$totalDeals = array_sum(array_map(fn($c) => intval($c['deal_count'] ?? 0), $customers));
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:16px;">
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('total_customers') ?></div>
        <div style="font-size:24px;font-weight:700;color:var(--color-primary);"><?= count($customers) ?></div>
    </div>
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('total_deals') ?></div>
        <div style="font-size:24px;font-weight:700;"><?= $totalDeals ?></div>
    </div>
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('total_pipeline') ?></div>
        <div style="font-size:22px;font-weight:700;color:#4CAF50;"><?= formatMoney($totalPipeline) ?></div>
    </div>
</div>

<div class="table-wrapper" style="overflow-x:auto;">
<table class="data-table" style="min-width:1100px;">
<thead>
<tr>
    <th style="width:110px;"><?= _e('code') ?></th>
    <th><?= _e('customer_name') ?></th>
    <th style="width:140px;"><?= _e('contact_person') ?></th>
    <th style="width:70px;" class="text-center"><?= __('contacts') ?></th>
    <th style="width:70px;" class="text-center"><?= __('deals') ?></th>
    <th style="width:130px;" class="text-right"><?= __('pipeline') ?></th>
    <th style="width:130px;"><?= _e('sales_person') ?></th>
    <th style="width:110px;"><?= __('last_activity') ?></th>
</tr>
</thead>
<tbody>
<?php if (empty($customers)): ?>
<tr><td colspan="8" class="text-center" style="padding:40px;color:var(--color-text-muted);">
    <?= __('no_customers_found') ?>
</td></tr>
<?php else: foreach ($customers as $c): ?>
<tr>
    <td><?= e($c['customer_code'] ?? '') ?></td>
    <td>
        <a href="/sales/customers/<?= e($c['customer_id']) ?>" style="font-weight:500;">
            <?= e($c['customer_name'] ?? '') ?>
        </a>
        <?php if (!empty($c['email'])): ?>
            <div style="font-size:11px;color:var(--color-text-muted);"><?= e($c['email']) ?></div>
        <?php endif; ?>
    </td>
    <td style="font-size:12px;"><?= e($c['contact_person'] ?? '') ?></td>
    <td class="text-center"><?= intval($c['contact_count']) ?></td>
    <td class="text-center"><?= intval($c['deal_count']) ?></td>
    <td class="text-right"><?= formatMoney($c['pipeline_amount']) ?></td>
    <td style="font-size:12px;"><?= e($c['sales_rep_name'] ?? '') ?></td>
    <td style="font-size:11px;color:var(--color-text-muted);">
        <?= $c['last_activity_at'] ? e(date('Y-m-d', strtotime($c['last_activity_at']))) : '—' ?>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
