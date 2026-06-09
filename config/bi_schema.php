<?php
/**
 * PEGASUS ERP - BI Schema Whitelist
 * Defines which tables/columns are available for BI queries.
 * Security: Only tables and columns listed here can be queried.
 * Sensitive columns (passwords, bank accounts, salaries) are excluded.
 */

return [
    'tables' => [

        // ── Sales: Quotations ──
        'quotation_headers' => [
            'label'          => 'Quotations',
            'label_key'      => 'bi_tbl_quotations',
            'primary_key'    => 'quotation_id',
            'access_section' => 'sales',
            'columns' => [
                'quotation_id'    => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'quotation_no'    => ['type' => 'string',   'label_key' => 'bi_col_doc_no'],
                'revision_no'     => ['type' => 'integer',  'label_key' => 'bi_col_revision'],
                'customer_id'     => ['type' => 'integer',  'label_key' => 'bi_col_customer', 'join_to' => 'customers'],
                'project_name'    => ['type' => 'string',   'label_key' => 'bi_col_project_name'],
                'issue_date'      => ['type' => 'date',     'label_key' => 'bi_col_issue_date',    'groupable' => true],
                'currency_code'   => ['type' => 'string',   'label_key' => 'bi_col_currency'],
                'subtotal_thb'    => ['type' => 'numeric',  'label_key' => 'bi_col_subtotal',      'aggregatable' => true],
                'discount_amount' => ['type' => 'numeric',  'label_key' => 'bi_col_discount',      'aggregatable' => true],
                'vat_amount'      => ['type' => 'numeric',  'label_key' => 'bi_col_vat',           'aggregatable' => true],
                'grand_total_thb' => ['type' => 'numeric',  'label_key' => 'bi_col_grand_total',   'aggregatable' => true],
                'status'          => ['type' => 'string',   'label_key' => 'bi_col_status'],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [
                'customers' => [
                    'fk' => 'customer_id', 'pk' => 'customer_id', 'table' => 'customers',
                    'columns' => [
                        'customer_name'    => ['type' => 'string', 'label_key' => 'bi_col_customer_name'],
                        'customer_name_jp' => ['type' => 'string', 'label_key' => 'bi_col_customer_name_jp'],
                    ],
                ],
            ],
        ],

        // ── Sales: Sales Orders ──
        'sales_order_headers' => [
            'label'          => 'Sales Orders',
            'label_key'      => 'bi_tbl_sales_orders',
            'primary_key'    => 'so_id',
            'access_section' => 'sales',
            'columns' => [
                'so_id'           => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'so_no'           => ['type' => 'string',   'label_key' => 'bi_col_doc_no'],
                'customer_id'     => ['type' => 'integer',  'label_key' => 'bi_col_customer', 'join_to' => 'customers'],
                'order_date'      => ['type' => 'date',     'label_key' => 'bi_col_order_date',    'groupable' => true],
                'currency_code'   => ['type' => 'string',   'label_key' => 'bi_col_currency'],
                'subtotal_thb'    => ['type' => 'numeric',  'label_key' => 'bi_col_subtotal',      'aggregatable' => true],
                'discount_amount' => ['type' => 'numeric',  'label_key' => 'bi_col_discount',      'aggregatable' => true],
                'vat_amount'      => ['type' => 'numeric',  'label_key' => 'bi_col_vat',           'aggregatable' => true],
                'grand_total_thb' => ['type' => 'numeric',  'label_key' => 'bi_col_grand_total',   'aggregatable' => true],
                'status'          => ['type' => 'string',   'label_key' => 'bi_col_status'],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [
                'customers' => [
                    'fk' => 'customer_id', 'pk' => 'customer_id', 'table' => 'customers',
                    'columns' => [
                        'customer_name'    => ['type' => 'string', 'label_key' => 'bi_col_customer_name'],
                    ],
                ],
            ],
        ],

        // ── Sales: Deals ──
        'deals' => [
            'label'          => 'Deals',
            'label_key'      => 'bi_tbl_deals',
            'primary_key'    => 'deal_id',
            'access_section' => 'sales',
            'columns' => [
                'deal_id'         => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'deal_name'       => ['type' => 'string',   'label_key' => 'bi_col_deal_name'],
                'customer_id'     => ['type' => 'integer',  'label_key' => 'bi_col_customer', 'join_to' => 'customers'],
                'deal_value'      => ['type' => 'numeric',  'label_key' => 'bi_col_deal_value',    'aggregatable' => true],
                'probability'     => ['type' => 'numeric',  'label_key' => 'bi_col_probability',   'aggregatable' => true],
                'weighted_value'  => ['type' => 'numeric',  'label_key' => 'bi_col_weighted_value', 'aggregatable' => true],
                'expected_close_date' => ['type' => 'date', 'label_key' => 'bi_col_close_date',    'groupable' => true],
                'status_id'       => ['type' => 'integer',  'label_key' => 'bi_col_status'],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [
                'customers' => [
                    'fk' => 'customer_id', 'pk' => 'customer_id', 'table' => 'customers',
                    'columns' => [
                        'customer_name' => ['type' => 'string', 'label_key' => 'bi_col_customer_name'],
                    ],
                ],
                'deal_statuses' => [
                    'fk' => 'status_id', 'pk' => 'status_id', 'table' => 'deal_statuses',
                    'columns' => [
                        'status_name' => ['type' => 'string', 'label_key' => 'bi_col_status_name'],
                    ],
                ],
            ],
        ],

        // ── AR Invoices ──
        'ar_invoices' => [
            'label'          => 'AR Invoices',
            'label_key'      => 'bi_tbl_ar_invoices',
            'primary_key'    => 'ar_invoice_id',
            'access_section' => 'ar',
            'columns' => [
                'ar_invoice_id'   => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'invoice_no'      => ['type' => 'string',   'label_key' => 'bi_col_doc_no'],
                'customer_id'     => ['type' => 'integer',  'label_key' => 'bi_col_customer', 'join_to' => 'customers'],
                'invoice_date'    => ['type' => 'date',     'label_key' => 'bi_col_invoice_date',  'groupable' => true],
                'due_date'        => ['type' => 'date',     'label_key' => 'bi_col_due_date',      'groupable' => true],
                'currency_code'   => ['type' => 'string',   'label_key' => 'bi_col_currency'],
                'subtotal_thb'    => ['type' => 'numeric',  'label_key' => 'bi_col_subtotal',      'aggregatable' => true],
                'vat_amount'      => ['type' => 'numeric',  'label_key' => 'bi_col_vat',           'aggregatable' => true],
                'grand_total_thb' => ['type' => 'numeric',  'label_key' => 'bi_col_grand_total',   'aggregatable' => true],
                'paid_amount'     => ['type' => 'numeric',  'label_key' => 'bi_col_paid_amount',   'aggregatable' => true],
                'balance_due'     => ['type' => 'numeric',  'label_key' => 'bi_col_balance_due',   'aggregatable' => true],
                'status'          => ['type' => 'string',   'label_key' => 'bi_col_status'],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [
                'customers' => [
                    'fk' => 'customer_id', 'pk' => 'customer_id', 'table' => 'customers',
                    'columns' => [
                        'customer_name' => ['type' => 'string', 'label_key' => 'bi_col_customer_name'],
                    ],
                ],
            ],
        ],

        // ── AP Invoices ──
        'ap_invoices' => [
            'label'          => 'AP Invoices',
            'label_key'      => 'bi_tbl_ap_invoices',
            'primary_key'    => 'ap_invoice_id',
            'access_section' => 'ap',
            'columns' => [
                'ap_invoice_id'   => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'invoice_no'      => ['type' => 'string',   'label_key' => 'bi_col_doc_no'],
                'supplier_id'     => ['type' => 'integer',  'label_key' => 'bi_col_supplier', 'join_to' => 'suppliers'],
                'invoice_date'    => ['type' => 'date',     'label_key' => 'bi_col_invoice_date',  'groupable' => true],
                'due_date'        => ['type' => 'date',     'label_key' => 'bi_col_due_date',      'groupable' => true],
                'currency_code'   => ['type' => 'string',   'label_key' => 'bi_col_currency'],
                'subtotal_thb'    => ['type' => 'numeric',  'label_key' => 'bi_col_subtotal',      'aggregatable' => true],
                'vat_amount'      => ['type' => 'numeric',  'label_key' => 'bi_col_vat',           'aggregatable' => true],
                'grand_total_thb' => ['type' => 'numeric',  'label_key' => 'bi_col_grand_total',   'aggregatable' => true],
                'paid_amount'     => ['type' => 'numeric',  'label_key' => 'bi_col_paid_amount',   'aggregatable' => true],
                'balance_due'     => ['type' => 'numeric',  'label_key' => 'bi_col_balance_due',   'aggregatable' => true],
                'status'          => ['type' => 'string',   'label_key' => 'bi_col_status'],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [
                'suppliers' => [
                    'fk' => 'supplier_id', 'pk' => 'supplier_id', 'table' => 'suppliers',
                    'columns' => [
                        'supplier_name' => ['type' => 'string', 'label_key' => 'bi_col_supplier_name'],
                    ],
                ],
            ],
        ],

        // ── Purchase Orders ──
        'purchase_order_headers' => [
            'label'          => 'Purchase Orders',
            'label_key'      => 'bi_tbl_purchase_orders',
            'primary_key'    => 'po_id',
            'access_section' => 'purchasing',
            'columns' => [
                'po_id'           => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'po_no'           => ['type' => 'string',   'label_key' => 'bi_col_doc_no'],
                'supplier_id'     => ['type' => 'integer',  'label_key' => 'bi_col_supplier', 'join_to' => 'suppliers'],
                'order_date'      => ['type' => 'date',     'label_key' => 'bi_col_order_date',    'groupable' => true],
                'currency_code'   => ['type' => 'string',   'label_key' => 'bi_col_currency'],
                'subtotal_thb'    => ['type' => 'numeric',  'label_key' => 'bi_col_subtotal',      'aggregatable' => true],
                'vat_amount'      => ['type' => 'numeric',  'label_key' => 'bi_col_vat',           'aggregatable' => true],
                'payment_amount'  => ['type' => 'numeric',  'label_key' => 'bi_col_payment_amount','aggregatable' => true],
                'status'          => ['type' => 'string',   'label_key' => 'bi_col_status'],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [
                'suppliers' => [
                    'fk' => 'supplier_id', 'pk' => 'supplier_id', 'table' => 'suppliers',
                    'columns' => [
                        'supplier_name' => ['type' => 'string', 'label_key' => 'bi_col_supplier_name'],
                    ],
                ],
            ],
        ],

        // ── Inventory Transactions ──
        'inventory_transactions' => [
            'label'          => 'Inventory Transactions',
            'label_key'      => 'bi_tbl_inventory_txn',
            'primary_key'    => 'txn_id',
            'access_section' => 'inventory',
            'columns' => [
                'txn_id'          => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'item_id'         => ['type' => 'integer',  'label_key' => 'bi_col_item', 'join_to' => 'items'],
                'warehouse_id'    => ['type' => 'integer',  'label_key' => 'bi_col_warehouse'],
                'txn_type'        => ['type' => 'string',   'label_key' => 'bi_col_txn_type'],
                'quantity'        => ['type' => 'numeric',  'label_key' => 'bi_col_quantity',      'aggregatable' => true],
                'unit_cost'       => ['type' => 'numeric',  'label_key' => 'bi_col_unit_cost',     'aggregatable' => true],
                'total_cost'      => ['type' => 'numeric',  'label_key' => 'bi_col_total_cost',    'aggregatable' => true],
                'txn_date'        => ['type' => 'date',     'label_key' => 'bi_col_txn_date',      'groupable' => true],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [
                'items' => [
                    'fk' => 'item_id', 'pk' => 'item_id', 'table' => 'items',
                    'columns' => [
                        'item_name' => ['type' => 'string', 'label_key' => 'bi_col_item_name'],
                        'item_code' => ['type' => 'string', 'label_key' => 'bi_col_item_code'],
                    ],
                ],
            ],
        ],

        // ── Stock Balances ──
        'stock_balances' => [
            'label'          => 'Stock Balances',
            'label_key'      => 'bi_tbl_stock_balances',
            'primary_key'    => 'balance_id',
            'access_section' => 'inventory',
            'columns' => [
                'balance_id'      => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'item_id'         => ['type' => 'integer',  'label_key' => 'bi_col_item', 'join_to' => 'items'],
                'warehouse_id'    => ['type' => 'integer',  'label_key' => 'bi_col_warehouse'],
                'quantity_on_hand'=> ['type' => 'numeric',  'label_key' => 'bi_col_qty_on_hand',   'aggregatable' => true],
                'quantity_reserved'=>['type' => 'numeric',  'label_key' => 'bi_col_qty_reserved',  'aggregatable' => true],
                'quantity_available'=>['type'=> 'numeric',  'label_key' => 'bi_col_qty_available', 'aggregatable' => true],
                'updated_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_updated_at',    'groupable' => true],
            ],
            'joins' => [
                'items' => [
                    'fk' => 'item_id', 'pk' => 'item_id', 'table' => 'items',
                    'columns' => [
                        'item_name' => ['type' => 'string', 'label_key' => 'bi_col_item_name'],
                        'item_code' => ['type' => 'string', 'label_key' => 'bi_col_item_code'],
                    ],
                ],
            ],
        ],

        // ── Employees (safe columns only) ──
        'employees' => [
            'label'          => 'Employees',
            'label_key'      => 'bi_tbl_employees',
            'primary_key'    => 'employee_id',
            'access_section' => 'hr',
            'columns' => [
                'employee_id'      => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'emp_code'         => ['type' => 'string',   'label_key' => 'bi_col_emp_code'],
                'full_name'        => ['type' => 'string',   'label_key' => 'bi_col_full_name'],
                'department_id'    => ['type' => 'integer',  'label_key' => 'bi_col_department'],
                'division_id'      => ['type' => 'integer',  'label_key' => 'bi_col_division'],
                'nationality'      => ['type' => 'string',   'label_key' => 'bi_col_nationality'],
                'hire_date'        => ['type' => 'date',     'label_key' => 'bi_col_hire_date',     'groupable' => true],
                'employment_type'  => ['type' => 'string',   'label_key' => 'bi_col_employment_type'],
                'position_title'   => ['type' => 'string',   'label_key' => 'bi_col_position'],
                'position_level'   => ['type' => 'string',   'label_key' => 'bi_col_position_level'],
                'is_current'       => ['type' => 'string',   'label_key' => 'bi_col_is_current'],
                'created_at'       => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [],
        ],

        // ── Journal Entries ──
        'journal_entries' => [
            'label'          => 'Journal Entries',
            'label_key'      => 'bi_tbl_journal_entries',
            'primary_key'    => 'je_id',
            'access_section' => 'accounting',
            'columns' => [
                'je_id'           => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'je_no'           => ['type' => 'string',   'label_key' => 'bi_col_doc_no'],
                'entry_date'      => ['type' => 'date',     'label_key' => 'bi_col_entry_date',    'groupable' => true],
                'total_debit'     => ['type' => 'numeric',  'label_key' => 'bi_col_total_debit',   'aggregatable' => true],
                'total_credit'    => ['type' => 'numeric',  'label_key' => 'bi_col_total_credit',  'aggregatable' => true],
                'source_type'     => ['type' => 'string',   'label_key' => 'bi_col_source_type'],
                'status'          => ['type' => 'string',   'label_key' => 'bi_col_status'],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [],
        ],

        // ── Expense Claims ──
        'expense_claims' => [
            'label'          => 'Expense Claims',
            'label_key'      => 'bi_tbl_expense_claims',
            'primary_key'    => 'claim_id',
            'access_section' => 'expense',
            'columns' => [
                'claim_id'        => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'claim_no'        => ['type' => 'string',   'label_key' => 'bi_col_doc_no'],
                'employee_id'     => ['type' => 'integer',  'label_key' => 'bi_col_employee'],
                'claim_date'      => ['type' => 'date',     'label_key' => 'bi_col_claim_date',    'groupable' => true],
                'total_amount'    => ['type' => 'numeric',  'label_key' => 'bi_col_total_amount',  'aggregatable' => true],
                'status'          => ['type' => 'string',   'label_key' => 'bi_col_status'],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [],
        ],

        // ── Projects ──
        'projects' => [
            'label'          => 'Projects',
            'label_key'      => 'bi_tbl_projects',
            'primary_key'    => 'project_id',
            'access_section' => 'sales',
            'columns' => [
                'project_id'      => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'project_code'    => ['type' => 'string',   'label_key' => 'bi_col_project_code'],
                'project_name'    => ['type' => 'string',   'label_key' => 'bi_col_project_name'],
                'customer_id'     => ['type' => 'integer',  'label_key' => 'bi_col_customer', 'join_to' => 'customers'],
                'status'          => ['type' => 'string',   'label_key' => 'bi_col_status'],
                'budget_amount'   => ['type' => 'numeric',  'label_key' => 'bi_col_budget',        'aggregatable' => true],
                'actual_amount'   => ['type' => 'numeric',  'label_key' => 'bi_col_actual_amount', 'aggregatable' => true],
                'start_date'      => ['type' => 'date',     'label_key' => 'bi_col_start_date',    'groupable' => true],
                'end_date'        => ['type' => 'date',     'label_key' => 'bi_col_end_date',      'groupable' => true],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [
                'customers' => [
                    'fk' => 'customer_id', 'pk' => 'customer_id', 'table' => 'customers',
                    'columns' => [
                        'customer_name' => ['type' => 'string', 'label_key' => 'bi_col_customer_name'],
                    ],
                ],
            ],
        ],

        // ── Manufacturing Orders ──
        'mo_headers' => [
            'label'          => 'Manufacturing Orders',
            'label_key'      => 'bi_tbl_mo',
            'primary_key'    => 'mo_id',
            'access_section' => 'production',
            'columns' => [
                'mo_id'           => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'mo_no'           => ['type' => 'string',   'label_key' => 'bi_col_doc_no'],
                'item_id'         => ['type' => 'integer',  'label_key' => 'bi_col_item', 'join_to' => 'items'],
                'planned_qty'     => ['type' => 'numeric',  'label_key' => 'bi_col_planned_qty',   'aggregatable' => true],
                'completed_qty'   => ['type' => 'numeric',  'label_key' => 'bi_col_completed_qty', 'aggregatable' => true],
                'planned_start'   => ['type' => 'date',     'label_key' => 'bi_col_planned_start', 'groupable' => true],
                'planned_end'     => ['type' => 'date',     'label_key' => 'bi_col_planned_end',   'groupable' => true],
                'status'          => ['type' => 'string',   'label_key' => 'bi_col_status'],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [
                'items' => [
                    'fk' => 'item_id', 'pk' => 'item_id', 'table' => 'items',
                    'columns' => [
                        'item_name' => ['type' => 'string', 'label_key' => 'bi_col_item_name'],
                    ],
                ],
            ],
        ],

        // ── Customers (standalone) ──
        'customers' => [
            'label'          => 'Customers',
            'label_key'      => 'bi_tbl_customers',
            'primary_key'    => 'customer_id',
            'access_section' => 'sales',
            'columns' => [
                'customer_id'     => ['type' => 'integer',  'label_key' => 'bi_col_id'],
                'customer_code'   => ['type' => 'string',   'label_key' => 'bi_col_customer_code'],
                'customer_name'   => ['type' => 'string',   'label_key' => 'bi_col_customer_name'],
                'country'         => ['type' => 'string',   'label_key' => 'bi_col_country'],
                'currency_code'   => ['type' => 'string',   'label_key' => 'bi_col_currency'],
                'credit_limit'    => ['type' => 'numeric',  'label_key' => 'bi_col_credit_limit',  'aggregatable' => true],
                'created_at'      => ['type' => 'datetime', 'label_key' => 'bi_col_created_at',    'groupable' => true],
            ],
            'joins' => [],
        ],
    ],

    // Allowed aggregation functions
    'aggregates' => ['SUM', 'COUNT', 'AVG', 'MIN', 'MAX'],

    // Allowed filter operators
    'operators' => ['=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'LIKE', 'BETWEEN'],

    // Allowed date transforms for grouping
    'date_transforms' => ['day', 'week', 'month', 'quarter', 'year'],

    // Hard query limit
    'max_rows' => 5000,
];
