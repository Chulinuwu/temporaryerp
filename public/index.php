<?php
/**
 * PEGASUS ERP - Entry Point
 * All requests are routed through this file
 */

// --- PHP built-in server: serve static files directly ---
if (php_sapi_name() === 'cli-server') {
    $requested = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($requested)) {
        // Set correct Content-Type for known extensions
        $ext = pathinfo($requested, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'json' => 'application/json',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        return false; // Let PHP built-in server serve the file
    }
}

session_start();

// Base path
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload core files
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/Env.php';
require_once BASE_PATH . '/core/Logger.php';

// Load optional .env (real environment wins; config/*.php hold the defaults)
Env::load(BASE_PATH . '/.env');

// Bootstrap logging before anything else so errors during setup are captured
Logger::init(require BASE_PATH . '/config/logging.php');
Logger::registerHandlers();
Logger::logRequest();

require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/core/Form.php';
require_once BASE_PATH . '/core/Router.php';
require_once BASE_PATH . '/core/ApprovalFlow.php';

// Initialize router
$router = new Router();

// ============================================================
// PUBLIC ROUTES (no auth required)
// ============================================================
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// Language switch (no auth required)
$router->get('/lang/{code}', 'AuthController@switchLang');

// ============================================================
// PROTECTED ROUTES
// ============================================================

// Dashboard
$router->get('/', 'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// -- MASTER MANAGEMENT --
$router->get('/master/divisions', 'MasterController@divisions');
$router->post('/master/divisions', 'MasterController@saveDivision');
$router->get('/master/divisions/{id}/edit', 'MasterController@editDivision');
$router->post('/master/divisions/{id}/delete', 'MasterController@deleteDivision');

$router->get('/master/accounts', 'MasterController@accounts');
$router->post('/master/accounts', 'MasterController@saveAccount');
$router->get('/master/accounts/{id}/edit', 'MasterController@editAccount');

$router->get('/master/items', 'MasterController@items');
$router->post('/master/items', 'MasterController@saveItem');
$router->get('/master/items/{id}/edit', 'MasterController@editItem');
$router->post('/master/items/{id}/delete', 'MasterController@deleteItem');

$router->get('/master/customers', 'CustomerController@index');
$router->post('/master/customers', 'CustomerController@save');
$router->get('/master/customers/{id}/edit', 'CustomerController@edit');
$router->post('/master/customers/{id}/delete', 'CustomerController@delete');
$router->post('/master/customers/{id}/approve', 'CustomerController@approve');
$router->post('/master/customers/{id}/reject', 'CustomerController@reject');

$router->get('/master/suppliers', 'SupplierController@index');
$router->post('/master/suppliers', 'SupplierController@save');
$router->get('/master/suppliers/{id}/edit', 'SupplierController@edit');
$router->post('/master/suppliers/{id}/delete', 'SupplierController@delete');
$router->post('/master/suppliers/{id}/approve', 'SupplierController@approve');
$router->post('/master/suppliers/{id}/reject', 'SupplierController@reject');
$router->post('/master/suppliers/{id}/attachments',          'SupplierController@uploadAttachments');
$router->get ('/master/suppliers/{id}/attachments/{attId}',  'SupplierController@downloadAttachment');
$router->post('/master/suppliers/{id}/attachments/{attId}/delete','SupplierController@deleteAttachment');

$router->get('/master/payment-terms', 'MasterController@paymentTerms');
$router->post('/master/payment-terms', 'MasterController@savePaymentTerm');
$router->get('/master/payment-terms/{id}/edit', 'MasterController@editPaymentTerm');
$router->post('/master/payment-terms/{id}/delete', 'MasterController@deletePaymentTerm');

$router->get('/master/banks', 'MasterController@banks');
$router->post('/master/banks', 'MasterController@saveBank');
$router->post('/master/banks/{id}/delete', 'MasterController@deleteBank');

$router->get('/master/deal-statuses', 'MasterController@dealStatuses');
$router->post('/master/deal-statuses', 'MasterController@saveDealStatus');
$router->post('/master/deal-statuses/{id}/delete', 'MasterController@deleteDealStatus');

$router->get('/master/solution-categories', 'MasterController@solutionCategories');
$router->post('/master/solution-categories', 'MasterController@saveSolutionCategory');
$router->post('/master/solution-categories/{id}/delete', 'MasterController@deleteSolutionCategory');

// Quotation line category master
$router->get ('/master/quotation-categories',              'MasterController@quotationCategories');
$router->post('/master/quotation-categories',              'MasterController@saveQuotationCategory');
$router->post('/master/quotation-categories/{id}/delete',  'MasterController@deleteQuotationCategory');

// -- COMPANY BANK ACCOUNTS (own bank accounts for invoicing) --
$router->get('/master/company-bank', 'CompanyBankController@index');
$router->post('/master/company-bank', 'CompanyBankController@save');
$router->post('/master/company-bank/{id}/delete', 'CompanyBankController@delete');

// -- EXCHANGE RATES (#10) --
$router->get('/master/exchange-rates', 'ExchangeRateController@index');
$router->post('/master/exchange-rates', 'ExchangeRateController@save');
$router->post('/master/exchange-rates/{id}/delete', 'ExchangeRateController@delete');
$router->get('/api/exchange-rate', 'ExchangeRateController@apiLatestRate');

// -- AUDIT LOG (#1) — admin only --
$router->get('/admin/audit-logs', 'AuditLogController@index');
$router->get('/admin/audit-logs/{id}', 'AuditLogController@show');

// -- ANALYTICS DASHBOARDS (#19/#20) --
$router->get('/analytics/quotations', 'AnalyticsController@quotations');
$router->get('/analytics/purchasing', 'AnalyticsController@purchasing');

// -- PERMISSION MASTER (#18) --
$router->get('/admin/permissions', 'PermissionController@index');
$router->post('/admin/permissions', 'PermissionController@save');

// -- APPROVAL CENTER (4 separate queues, manager/admin only) --
$router->get('/approvals/customers',       'ApprovalController@customers');
$router->get('/approvals/suppliers',       'ApprovalController@suppliers');
$router->get('/approvals/quotations',      'ApprovalController@quotations');
$router->get('/approvals/purchase-requests','ApprovalController@purchaseRequests');
$router->get('/approvals/purchase-orders', 'ApprovalController@purchaseOrders');
$router->post('/approvals/customers/{id}/approve',       'ApprovalController@approveCustomer');
$router->post('/approvals/customers/{id}/reject',        'ApprovalController@rejectCustomer');
$router->post('/approvals/suppliers/{id}/approve',       'ApprovalController@approveSupplier');
$router->post('/approvals/suppliers/{id}/reject',        'ApprovalController@rejectSupplier');
$router->post('/approvals/quotations/{id}/approve',      'ApprovalController@approveQuotation');
$router->post('/approvals/quotations/{id}/reject',       'ApprovalController@rejectQuotation');
$router->post('/approvals/purchase-orders/{id}/approve', 'ApprovalController@approvePo');
$router->post('/approvals/purchase-orders/{id}/reject',  'ApprovalController@rejectPo');

// -- USER MANAGEMENT (admin only) --
$router->get('/admin/users', 'UserController@index');
$router->post('/admin/users', 'UserController@store');
$router->post('/admin/users/bulk-reset', 'UserController@bulkReset');
$router->post('/admin/users/{id}/update', 'UserController@update');
$router->post('/admin/users/{id}/reset-password', 'UserController@resetPassword');
$router->post('/admin/users/{id}/delete', 'UserController@delete');

// -- SALES --
$router->get('/sales/quotations', 'QuotationController@index');
$router->get('/sales/quotations/create', 'QuotationController@create');
$router->post('/sales/quotations', 'QuotationController@store');
$router->get('/sales/quotations/{id}', 'QuotationController@show');
$router->get('/sales/quotations/{id}/edit', 'QuotationController@edit');
$router->post('/sales/quotations/{id}', 'QuotationController@update');
$router->post('/sales/quotations/{id}/delete', 'QuotationController@delete');
$router->post('/sales/quotations/{id}/submit',  'QuotationController@submitForApproval');
$router->post('/sales/quotations/{id}/approve', 'QuotationController@approve');
$router->post('/sales/quotations/{id}/reject',  'QuotationController@reject');
$router->post('/sales/quotations/{id}/copy',    'QuotationController@copy');

$router->get('/sales/orders', 'SalesOrderController@index');
$router->get('/sales/orders/create', 'SalesOrderController@create');
$router->post('/sales/orders', 'SalesOrderController@store');
$router->get('/sales/orders/{id}', 'SalesOrderController@show');
$router->post('/sales/orders/{id}', 'SalesOrderController@update');
$router->post('/sales/orders/{id}/cancel', 'SalesOrderController@cancel');

$router->get('/sales/pipeline', 'SalesOrderController@pipeline');

// -- PDF --
$router->get('/pdf/quotation/{id}', 'PDFController@quotationPdf');
$router->get('/pdf/salesorder/{id}', 'PDFController@salesOrderPdf');
$router->get('/pdf/invoice/{id}', 'PDFController@invoicePdf');
$router->get('/pdf/po/{id}', 'PDFController@purchaseOrderPdf');
$router->post('/pdf/bulk', 'PDFController@bulkPdf');

// -- SALES KPI --
$router->get('/sales/kpi', 'SalesKpiController@dashboard');
$router->get('/sales/kpi/master', 'SalesKpiController@master');
$router->post('/sales/kpi/master/save', 'SalesKpiController@saveTarget');

// -- ACTIVITY LOG (standalone) --
$router->get('/sales/activities', 'DealController@activityList');
$router->post('/sales/activities', 'DealController@storeStandaloneActivity');
$router->post('/sales/activities/{id}/delete', 'DealController@deleteStandaloneActivity');

// -- SALES CUSTOMER MANAGEMENT (顧客管理) --
$router->get('/sales/customers', 'SalesCustomerController@index');
$router->get('/sales/customers/{id}', 'SalesCustomerController@show');
$router->post('/sales/customers/{id}/contacts', 'SalesCustomerController@saveContact');
$router->post('/sales/customers/{id}/contacts/{contactId}/delete', 'SalesCustomerController@deleteContact');
$router->post('/sales/customers/{id}/cards/upload', 'SalesCustomerController@uploadCard');
$router->post('/sales/customers/{id}/cards/{cardId}/delete', 'SalesCustomerController@deleteCard');

// -- DEALS (CRM) --
$router->get('/sales/deals', 'DealController@index');
$router->get('/sales/deals/kanban', 'DealController@kanban');
$router->get('/sales/deals/create', 'DealController@create');
$router->post('/sales/deals', 'DealController@store');
$router->get('/sales/deals/{id}', 'DealController@show');
$router->get('/sales/deals/{id}/edit', 'DealController@edit');
$router->post('/sales/deals/{id}', 'DealController@update');
$router->post('/sales/deals/{id}/delete', 'DealController@delete');
$router->post('/sales/deals/{id}/status', 'DealController@updateStatus');
$router->post('/sales/deals/{id}/activities', 'DealController@storeActivity');
$router->post('/sales/deals/{id}/activities/{activityId}/delete', 'DealController@deleteActivity');
$router->get('/sales/deals/{id}/quotations/search', 'DealController@searchQuotations');
$router->post('/sales/deals/{id}/quotations', 'DealController@linkQuotations');
$router->post('/sales/deals/{id}/quotations/{qid}/unlink', 'DealController@unlinkQuotation');
$router->post('/sales/deals/{id}/convert-order', 'DealController@convertToOrder');

// -- PURCHASING --
// Purchase Requests (PR) — user-initiated, two-step approval
$router->get ('/purchasing/requests',                        'PurchaseRequestController@index');
$router->get ('/purchasing/requests/create',                 'PurchaseRequestController@create');
$router->post('/purchasing/requests',                        'PurchaseRequestController@store');
$router->get ('/purchasing/requests/{id}',                   'PurchaseRequestController@show');
$router->post('/purchasing/requests/{id}/submit',            'PurchaseRequestController@submit');
$router->post('/purchasing/requests/{id}/start-quotes',      'PurchaseRequestController@startQuotes');
$router->post('/purchasing/requests/{id}/quotes',            'PurchaseRequestController@addQuote');
$router->post('/purchasing/requests/{id}/quotes/{qid}/delete','PurchaseRequestController@deleteQuote');
$router->post('/purchasing/requests/{id}/select-winners',    'PurchaseRequestController@selectWinners');
$router->post('/purchasing/requests/{id}/submit-manager',    'PurchaseRequestController@submitForManagerReview');
$router->post('/purchasing/requests/{id}/approve-manager',   'PurchaseRequestController@approveManager');
$router->post('/purchasing/requests/{id}/approve-ceo',       'PurchaseRequestController@approveCeo');
$router->post('/purchasing/requests/{id}/reject',            'PurchaseRequestController@reject');
$router->post('/purchasing/requests/{id}/cancel',            'PurchaseRequestController@cancel');
$router->post('/purchasing/requests/{id}/convert-to-po',     'PurchaseRequestController@convertToPo');
$router->post('/purchasing/requests/{id}/attachments',       'PurchaseRequestController@uploadAttachments');
$router->get ('/purchasing/requests/{id}/attachments/{attId}','PurchaseRequestController@downloadAttachment');
$router->post('/purchasing/requests/{id}/attachments/{attId}/delete','PurchaseRequestController@deleteAttachment');

$router->get('/purchasing/orders', 'PurchaseOrderController@index');
$router->get('/purchasing/orders/create', 'PurchaseOrderController@create');
$router->post('/purchasing/orders', 'PurchaseOrderController@store');
$router->get('/purchasing/orders/{id}', 'PurchaseOrderController@show');
$router->get('/purchasing/orders/{id}/edit', 'PurchaseOrderController@edit');
$router->post('/purchasing/orders/{id}', 'PurchaseOrderController@update');
$router->post('/purchasing/orders/{id}/delete', 'PurchaseOrderController@delete');
$router->post('/purchasing/orders/{id}/submit',          'PurchaseOrderController@submitForApproval');
$router->post('/purchasing/orders/{id}/approve',         'PurchaseOrderController@approve'); // legacy alias = manager step
$router->post('/purchasing/orders/{id}/approve-manager', 'PurchaseOrderController@approveManager');
$router->post('/purchasing/orders/{id}/approve-ceo',     'PurchaseOrderController@approveCeo');
$router->post('/purchasing/orders/{id}/cancel',  'PurchaseOrderController@cancel');
$router->post('/purchasing/orders/{id}/reject',  'PurchaseOrderController@reject');
$router->post('/purchasing/orders/{id}/copy',    'PurchaseOrderController@copy');

// -- PROJECT MANAGEMENT --
$router->get('/projects', 'ProjectController@index');
$router->get('/projects/create', 'ProjectController@create');
$router->post('/projects', 'ProjectController@store');
$router->get('/projects/{id}', 'ProjectController@show');
$router->get('/projects/{id}/edit', 'ProjectController@edit');
$router->post('/projects/{id}', 'ProjectController@update');
$router->post('/projects/{id}/invoices', 'ProjectController@storeInvoice');
$router->post('/projects/{id}/invoices/{invId}/delete', 'ProjectController@deleteInvoice');
$router->post('/projects/{id}/purchases', 'ProjectController@storePurchase');
$router->post('/projects/{id}/purchases/{purId}/delete', 'ProjectController@deletePurchase');
$router->get('/projects/{id}/purchase-orders/search', 'ProjectController@searchPurchaseOrders');

// -- COST SHEETS (原価算出) --
$router->get('/cost-sheets', 'CostSheetController@index');
$router->get('/cost-sheets/create', 'CostSheetController@create');
$router->post('/cost-sheets', 'CostSheetController@store');
$router->post('/cost-sheets/import-new', 'CostSheetController@storeWithImport');
$router->get('/cost-sheets/{id}', 'CostSheetController@show');
$router->post('/cost-sheets/{id}', 'CostSheetController@update');
$router->post('/cost-sheets/{id}/delete', 'CostSheetController@delete');
$router->post('/cost-sheets/{id}/items', 'CostSheetController@storeItem');
$router->post('/cost-sheets/{id}/items/{itemId}/delete', 'CostSheetController@deleteItem');
$router->post('/cost-sheets/{id}/import', 'CostSheetController@importExcel');
$router->post('/cost-sheets/{id}/from-quotation', 'CostSheetController@importFromQuotation');
$router->get('/cost-sheets/{id}/quotations/search', 'CostSheetController@searchQuotations');

// -- INVENTORY --
$router->get('/inventory/stock', 'InventoryController@stock');
$router->get('/inventory/warehouses', 'InventoryController@warehouses');
$router->post('/inventory/receive', 'InventoryController@receive');
$router->post('/inventory/issue', 'InventoryController@issue');

// -- ACCOUNTING --
$router->get('/accounting/journal/new', 'AccountingController@newJournal');
$router->post('/accounting/journal', 'AccountingController@storeJournal');
$router->get('/accounting/journal', 'AccountingController@journalList');
$router->get('/accounting/journal/{id}', 'AccountingController@showJournal');
$router->post('/accounting/journal/{id}/post', 'AccountingController@postJournal');

$router->get('/accounting/ledger', 'AccountingController@ledger');
$router->get('/accounting/pl', 'AccountingController@profitLoss');
$router->get('/accounting/bs', 'AccountingController@balanceSheet');

// -- AR --
$router->get('/ar/invoices', 'ARController@invoices');
$router->get('/ar/invoices/create', 'ARController@createInvoice');
$router->post('/ar/invoices', 'ARController@storeInvoice');
$router->get('/ar/invoices/{id}', 'ARController@showInvoice');
$router->post('/ar/invoices/{id}', 'ARController@updateInvoice');

$router->get('/ar/payments', 'ARController@payments');
$router->post('/ar/payments', 'ARController@storePayment');

// -- AP --
$router->get('/ap/invoices', 'APController@invoices');
$router->get('/ap/invoices/create', 'APController@createInvoice');
$router->post('/ap/invoices', 'APController@storeInvoice');
$router->get('/ap/invoices/{id}', 'APController@showInvoice');

$router->get('/ap/payments', 'APController@payments');
$router->post('/ap/payments', 'APController@storePayment');

// -- CASH FLOW --
$router->get('/cashflow/actual', 'CashflowController@actual');
$router->get('/cashflow/forecast', 'CashflowController@forecast');
// Legacy JSON API endpoints (for dashboard widgets)
$router->get('/api/cashflow/actual', 'AccountingController@cashFlowActual');
$router->get('/api/cashflow/forecast', 'AccountingController@cashFlowForecast');

// -- HR --
$router->get('/hr/employees', 'HRController@employees');
$router->get('/hr/employees/create', 'HRController@createEmployee');
$router->post('/hr/employees', 'HRController@storeEmployee');
$router->get('/hr/employees/{id}', 'HRController@showEmployee');
$router->get('/hr/employees/{id}/edit', 'HRController@editEmployee');
$router->post('/hr/employees/{id}', 'HRController@updateEmployee');

$router->get('/hr/attendance', 'HRController@attendance');
$router->post('/hr/attendance/clock-in', 'HRController@clockIn');
$router->post('/hr/attendance/clock-out', 'HRController@clockOut');

$router->get('/hr/leave', 'HRController@leaveRequests');
$router->post('/hr/leave', 'HRController@storeLeave');
$router->post('/hr/leave/{id}/approve', 'HRController@approveLeave');
$router->post('/hr/leave/{id}/reject', 'HRController@rejectLeave');

// -- PAYROLL — REMOVED (feature not used) --

// -- EXPENSE --
$router->get('/expense/claims', 'ExpenseController@index');
$router->get('/expense/claims/create', 'ExpenseController@create');
$router->post('/expense/claims', 'ExpenseController@store');
$router->get('/expense/claims/{id}', 'ExpenseController@show');
$router->post('/expense/claims/{id}/submit', 'ExpenseController@submit');
$router->post('/expense/claims/{id}/approve', 'ExpenseController@approve');
$router->post('/expense/claims/{id}/reject', 'ExpenseController@reject');
$router->post('/expense/calculate-mileage', 'ExpenseController@calculateMileage');

// -- PRODUCTION --
$router->get('/production/orders', 'ProductionController@orders');
$router->get('/production/orders/create', 'ProductionController@createOrder');
$router->post('/production/orders', 'ProductionController@storeOrder');
$router->get('/production/orders/{id}', 'ProductionController@showOrder');
$router->post('/production/orders/{id}', 'ProductionController@updateOrder');

$router->get('/production/bom', 'ProductionController@bomList');
$router->get('/production/bom/create', 'ProductionController@createBOM');
$router->post('/production/bom', 'ProductionController@storeBOM');
$router->get('/production/bom/{id}', 'ProductionController@showBOM');

$router->get('/production/mrp', 'MRPController@index');
$router->post('/production/mrp/calculate', 'MRPController@calculate');
$router->get('/production/mrp/{id}', 'MRPController@showSnapshot');

$router->get('/production/cost', 'ProductionController@costAccounting');

// -- REPORTS --
$router->get('/reports', 'ReportController@index');
$router->get('/reports/stub', 'ReportController@stub');

// -- DOCS (system requirements etc.) --
$router->get('/docs/requirements', 'DocsController@requirements');

// -- KINTAI PORTAL (attendance / HR self-service, JobCan-style) --
$router->get('/kintai',                      'KintaiController@index');
$router->get('/kintai/attendance',           'KintaiController@attendance');
$router->get('/kintai/clock-edit',           'KintaiController@clockEdit');
$router->get('/kintai/kosu',                 'KintaiController@kosu');
$router->get('/kintai/leave/new',            'KintaiController@newLeave');
$router->get('/kintai/overtime',             'KintaiController@overtimeList');
$router->get('/kintai/overtime/new',         'KintaiController@newOvertime');
$router->get('/kintai/overtime/early/new',   'KintaiController@newEarlyOvertime');
$router->get('/kintai/staff-settings',       'KintaiController@staffSettings');

// -- BI DASHBOARDS --
$router->get('/bi/dashboards', 'BIController@index');
$router->get('/bi/dashboards/create', 'BIController@create');
$router->post('/bi/dashboards', 'BIController@store');
$router->get('/bi/dashboards/{id}', 'BIController@show');
$router->get('/bi/dashboards/{id}/edit', 'BIController@edit');
$router->post('/bi/dashboards/{id}', 'BIController@update');
$router->post('/bi/dashboards/{id}/delete', 'BIController@delete');

// -- API ENDPOINTS (JSON) --
$router->get('/api/dashboard/kpi', 'DashboardController@kpiData');
$router->get('/api/dashboard/cashflow', 'DashboardController@cashflowData');
$router->get('/api/dashboard/pipeline', 'DashboardController@pipelineData');
$router->get('/api/customers/search', 'CustomerController@search');
$router->get('/api/suppliers/search', 'SupplierController@search');
$router->get('/api/items/search', 'MasterController@searchItems');
$router->get('/api/accounts/search', 'MasterController@searchAccounts');
$router->get('/api/deals', 'DealController@apiDeals');
$router->get('/api/solution-categories', 'MasterController@apiSolutionCategories');
$router->get('/api/master/next-code', 'MasterController@nextCode');
$router->get('/api/payment-terms/{id}/installments', 'MasterController@apiPaymentTermInstallments');
$router->get('/api/translate', 'MasterController@apiTranslate');
$router->get('/api/translate-batch', 'MasterController@apiTranslateBatch');
$router->get('/api/cost-sheets/search', 'CostSheetController@apiSearch');
$router->get('/api/cost-sheets/{id}/items', 'CostSheetController@apiItems');

// BI API
$router->get('/api/bi/schema', 'BIController@apiSchema');
$router->post('/api/bi/query', 'BIController@apiQueryData');
$router->post('/api/bi/widgets', 'BIController@apiSaveWidget');
$router->post('/api/bi/widgets/{id}/delete', 'BIController@apiDeleteWidget');
$router->post('/api/bi/dashboards/{id}/layout', 'BIController@apiSaveLayout');

// Dispatch the request
$router->dispatch();
