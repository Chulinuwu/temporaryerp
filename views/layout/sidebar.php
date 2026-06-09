<?php
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $currentPath = rtrim($currentPath, '/');

    function sidebarActive(string $path, string $current): string {
        return ($current === $path || strpos($current, $path . '/') === 0) ? ' active' : '';
    }
?>
<!-- Left Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-menu">

        <!-- MAIN (all roles) -->
        <div class="sidebar-section-label"><?= _e('nav_main') ?></div>
        <a href="/dashboard" class="sidebar-item<?= sidebarActive('/dashboard', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_dashboard') ?></span>
        </a>

        <?php if (Auth::canAccess('sales')): ?>
        <!-- SALES -->
        <div class="sidebar-section-label"><?= _e('nav_sales') ?></div>
        <a href="/sales/customers" class="sidebar-item<?= sidebarActive('/sales/customers', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_sales_customers') ?></span>
        </a>
        <a href="/sales/deals" class="sidebar-item<?= sidebarActive('/sales/deals', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_deals') ?></span>
        </a>
        <a href="/sales/quotations" class="sidebar-item<?= sidebarActive('/sales/quotations', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_quotations') ?></span>
        </a>
        <a href="/sales/orders" class="sidebar-item<?= sidebarActive('/sales/orders', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_sales_orders') ?></span>
        </a>
        <a href="/ar/invoices" class="sidebar-item<?= sidebarActive('/ar/invoices', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_invoices') ?></span>
        </a>
        <a href="/sales/activities" class="sidebar-item<?= sidebarActive('/sales/activities', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_activity_log') ?></span>
        </a>
        <a href="/sales/pipeline" class="sidebar-item<?= sidebarActive('/sales/pipeline', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_pipeline') ?></span>
        </a>
        <a href="/sales/kpi" class="sidebar-item<?= sidebarActive('/sales/kpi', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('sales_kpi_dashboard') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::canAccess('sales')): ?>
        <!-- PROJECT MANAGEMENT -->
        <div class="sidebar-section-label"><?= __('nav_project') ?></div>
        <a href="/projects" class="sidebar-item<?= sidebarActive('/projects', $currentPath) ?>">
            <span class="sidebar-item-text"><?= __('menu_project_list') ?></span>
        </a>
        <a href="/cost-sheets" class="sidebar-item<?= sidebarActive('/cost-sheets', $currentPath) ?>">
            <span class="sidebar-item-text"><?= __('menu_cost_sheets') ?></span>
        </a>
        <?php endif; ?>

        <!-- PURCHASING (PR open to all authenticated users; PO/suppliers gated by canAccess) -->
        <div class="sidebar-section-label"><?= _e('nav_purchasing') ?></div>
        <a href="/purchasing/requests" class="sidebar-item<?= sidebarActive('/purchasing/requests', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_purchase_requests') ?></span>
        </a>
        <?php if (Auth::canAccess('purchasing')): ?>
        <a href="/purchasing/orders" class="sidebar-item<?= sidebarActive('/purchasing/orders', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_purchase_orders') ?></span>
        </a>
        <a href="/master/suppliers" class="sidebar-item<?= sidebarActive('/master/suppliers', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_suppliers') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::canAccess('inventory')): ?>
        <!-- INVENTORY -->
        <div class="sidebar-section-label"><?= _e('nav_inventory') ?></div>
        <a href="/inventory/stock" class="sidebar-item<?= sidebarActive('/inventory/stock', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_stock') ?></span>
        </a>
        <a href="/inventory/warehouses" class="sidebar-item<?= sidebarActive('/inventory/warehouses', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_warehouses') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::canAccess('accounting')): ?>
        <!-- ACCOUNTING -->
        <div class="sidebar-section-label"><?= _e('nav_accounting') ?></div>
        <a href="/accounting/journal" class="sidebar-item<?= sidebarActive('/accounting/journal', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_journal') ?></span>
        </a>
        <a href="/accounting/ledger" class="sidebar-item<?= sidebarActive('/accounting/ledger', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_ledger') ?></span>
        </a>
        <a href="/accounting/pl" class="sidebar-item<?= sidebarActive('/accounting/pl', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_pl') ?></span>
        </a>
        <a href="/accounting/bs" class="sidebar-item<?= sidebarActive('/accounting/bs', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_bs') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::canAccess('ar')): ?>
        <a href="/ar/invoices" class="sidebar-item<?= sidebarActive('/ar', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_ar') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::canAccess('ap')): ?>
        <a href="/ap/invoices" class="sidebar-item<?= sidebarActive('/ap', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_ap') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::canAccess('cashflow')): ?>
        <!-- Cash Flow submenu -->
        <div class="sidebar-item sidebar-has-submenu" onclick="toggleSubmenu(this)">
            <span class="sidebar-item-text"><?= _e('menu_cashflow') ?></span>
            <span class="sidebar-item-arrow<?= (strpos($currentPath, '/cashflow') === 0) ? ' expanded' : '' ?>">&#9656;</span>
        </div>
        <div class="sidebar-submenu<?= (strpos($currentPath, '/cashflow') === 0) ? ' open' : '' ?>">
            <a href="/cashflow/actual" class="sidebar-item<?= sidebarActive('/cashflow/actual', $currentPath) ?>">
                <span class="sidebar-item-text"><?= _e('menu_cf_actual') ?></span>
            </a>
            <a href="/cashflow/forecast" class="sidebar-item<?= sidebarActive('/cashflow/forecast', $currentPath) ?>">
                <span class="sidebar-item-text"><?= _e('menu_cf_forecast') ?></span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (Auth::canAccess('hr') || Auth::canAccess('hr_self')): ?>
        <!-- HR -->
        <div class="sidebar-section-label"><?= _e('nav_hr') ?></div>
        <?php if (Auth::canAccess('hr')): ?>
        <a href="/hr/employees" class="sidebar-item<?= sidebarActive('/hr/employees', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_employees') ?></span>
        </a>
        <?php endif; ?>
        <a href="/hr/attendance" class="sidebar-item<?= sidebarActive('/hr/attendance', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_attendance') ?></span>
        </a>
        <a href="/hr/leave" class="sidebar-item<?= sidebarActive('/hr/leave', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_leave') ?></span>
        </a>
        <?php endif; ?>


        <?php if (Auth::canAccess('expense') || Auth::canAccess('hr')): ?>
        <a href="/expense/claims" class="sidebar-item<?= sidebarActive('/expense/claims', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_expense') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::canAccess('production')): ?>
        <!-- PRODUCTION -->
        <div class="sidebar-section-label"><?= _e('nav_production') ?></div>
        <a href="/production/orders" class="sidebar-item<?= sidebarActive('/production/orders', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_mo') ?></span>
        </a>
        <a href="/production/bom" class="sidebar-item<?= sidebarActive('/production/bom', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_bom') ?></span>
        </a>
        <a href="/production/mrp" class="sidebar-item<?= sidebarActive('/production/mrp', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_mrp') ?></span>
        </a>
        <a href="/production/cost" class="sidebar-item<?= sidebarActive('/production/cost', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_cost') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::canAccess('master')): ?>
        <!-- MASTER -->
        <div class="sidebar-section-label"><?= _e('nav_master') ?></div>
        <a href="/master/customers" class="sidebar-item<?= sidebarActive('/master/customers', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_customers') ?></span>
        </a>
        <a href="/master/suppliers" class="sidebar-item<?= sidebarActive('/master/suppliers', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_suppliers') ?></span>
        </a>
        <a href="/master/items" class="sidebar-item<?= sidebarActive('/master/items', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_items') ?></span>
        </a>
        <?php if (Auth::isAdmin()): ?>
        <a href="/master/accounts" class="sidebar-item<?= sidebarActive('/master/accounts', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_accounts') ?></span>
        </a>
        <a href="/master/payment-terms" class="sidebar-item<?= sidebarActive('/master/payment-terms', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_payment_terms') ?></span>
        </a>
        <a href="/master/banks" class="sidebar-item<?= sidebarActive('/master/banks', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_banks') ?></span>
        </a>
        <a href="/master/company-bank" class="sidebar-item<?= sidebarActive('/master/company-bank', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_company_bank') ?></span>
        </a>
        <a href="/master/divisions" class="sidebar-item<?= sidebarActive('/master/divisions', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_divisions') ?></span>
        </a>
        <a href="/master/deal-statuses" class="sidebar-item<?= sidebarActive('/master/deal-statuses', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_deal_statuses') ?></span>
        </a>
        <a href="/master/solution-categories" class="sidebar-item<?= sidebarActive('/master/solution-categories', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_solution_categories') ?></span>
        </a>
        <a href="/master/quotation-categories" class="sidebar-item<?= sidebarActive('/master/quotation-categories', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_quotation_categories') ?></span>
        </a>
        <a href="/master/exchange-rates" class="sidebar-item<?= sidebarActive('/master/exchange-rates', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_exchange_rates') ?></span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (Auth::isManagerOrAbove()): ?>
        <!-- APPROVAL CENTER (manager+ only) -->
        <div class="sidebar-section-label" style="color:#E65100;font-weight:700;"><?= _e('nav_approvals') ?? 'APPROVALS' ?></div>
        <a href="/approvals/customers" class="sidebar-item<?= sidebarActive('/approvals/customers', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('approval_queue_customers') ?></span>
        </a>
        <a href="/approvals/suppliers" class="sidebar-item<?= sidebarActive('/approvals/suppliers', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('approval_queue_suppliers') ?></span>
        </a>
        <a href="/approvals/quotations" class="sidebar-item<?= sidebarActive('/approvals/quotations', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('approval_queue_quotations') ?></span>
        </a>
        <a href="/approvals/purchase-requests" class="sidebar-item<?= sidebarActive('/approvals/purchase-requests', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('approval_queue_prs') ?></span>
        </a>
        <a href="/approvals/purchase-orders" class="sidebar-item<?= sidebarActive('/approvals/purchase-orders', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('approval_queue_pos') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::isAdmin()): ?>
        <!-- ADMIN section (top-level, visible at-a-glance) -->
        <div class="sidebar-section-label" style="color:#D32F2F;font-weight:700;"><?= _e('nav_admin') ?? 'ADMIN' ?></div>
        <a href="/admin/users" class="sidebar-item<?= sidebarActive('/admin/users', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_user_management') ?></span>
        </a>
        <a href="/admin/permissions" class="sidebar-item<?= sidebarActive('/admin/permissions', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_permissions') ?></span>
        </a>
        <a href="/admin/audit-logs" class="sidebar-item<?= sidebarActive('/admin/audit-logs', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_audit_log') ?></span>
        </a>
        <?php endif; ?>

        <?php if (Auth::canAccess('reports')): ?>
        <!-- REPORTS -->
        <div class="sidebar-section-label"><?= _e('nav_reports') ?></div>
        <a href="/reports" class="sidebar-item<?= sidebarActive('/reports', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_reports') ?></span>
        </a>
        <?php endif; ?>

        <!-- BI DASHBOARDS (all authenticated users) -->
        <div class="sidebar-section-label"><?= _e('nav_bi') ?></div>
        <a href="/bi/dashboards" class="sidebar-item<?= sidebarActive('/bi/dashboards', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('menu_bi_dashboards') ?></span>
        </a>
        <a href="/analytics/quotations" class="sidebar-item<?= sidebarActive('/analytics/quotations', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('analytics_quotations') ?></span>
        </a>
        <a href="/analytics/purchasing" class="sidebar-item<?= sidebarActive('/analytics/purchasing', $currentPath) ?>">
            <span class="sidebar-item-text"><?= _e('analytics_purchasing') ?></span>
        </a>

    </div><!-- /sidebar-menu -->

    <!-- Sidebar Bottom: Language + Profile -->
    <div class="sidebar-bottom">
        <div class="lang-switcher">
            <a href="/lang/ja" class="lang-btn<?= currentLang() === 'ja' ? ' active' : '' ?>">JA</a>
            <a href="/lang/en" class="lang-btn<?= currentLang() === 'en' ? ' active' : '' ?>">EN</a>
            <a href="/lang/th" class="lang-btn<?= currentLang() === 'th' ? ' active' : '' ?>">TH</a>
        </div>
        <div class="sidebar-profile">
            <div class="sidebar-profile-avatar">
                <?= strtoupper(substr($_SESSION['user']['full_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="sidebar-profile-info">
                <div class="sidebar-profile-name"><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?></div>
                <div class="sidebar-profile-role" style="font-size:11px;color:var(--color-text-muted);"><?= htmlspecialchars($_SESSION['user']['role'] ?? '') ?></div>
            </div>
        </div>
    </div>
</aside>

<script>
// Preserve sidebar scroll position across page navigations.
// The sidebar itself may be the scroll container (depending on CSS layout),
// so we watch multiple candidates and use whichever has a scroll position.
(function(){
    var KEY = 'pegasus_sidebar_scroll';
    function getContainers() {
        return [
            document.getElementById('sidebar'),
            document.querySelector('.sidebar-menu'),
        ].filter(function(el) { return !!el; });
    }
    function save() {
        var total = 0;
        getContainers().forEach(function(el){
            if (el.scrollTop > total) total = el.scrollTop;
        });
        sessionStorage.setItem(KEY, total);
    }
    function restore() {
        var saved = parseFloat(sessionStorage.getItem(KEY) || 0);
        getContainers().forEach(function(el){
            try { el.scrollTop = saved; } catch (e) {}
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else { init(); }
    function init() {
        restore();
        var t;
        getContainers().forEach(function(el){
            el.addEventListener('scroll', function(){
                clearTimeout(t);
                t = setTimeout(save, 100);
            }, { passive: true });
            el.querySelectorAll('a').forEach(function(a){
                a.addEventListener('click', save);
            });
        });
    }
    window.addEventListener('beforeunload', save);
})();
</script>
