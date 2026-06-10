-- public.v_accounts_current source

CREATE OR REPLACE VIEW public.v_accounts_current
AS SELECT account_id,
    account_code,
    division_id,
    account_name,
    account_name_jp,
    account_name_th,
    account_type,
    bs_pl,
    parent_code,
    is_tax_relevant,
    tax_form,
    default_tax_rate,
    is_control_acct,
    effective_from,
    effective_to,
    version_no,
    is_current,
    is_deleted,
    deleted_reason,
    created_by,
    created_at,
    updated_by,
    updated_at
   FROM accounts
  WHERE is_current = true AND is_deleted = false AND effective_from <= CURRENT_DATE AND (effective_to IS NULL OR effective_to >= CURRENT_DATE);


-- public.v_customers_current source

CREATE OR REPLACE VIEW public.v_customers_current
AS SELECT customer_id,
    customer_code,
    division_id,
    customer_name,
    customer_name_jp,
    customer_name_th,
    country,
    address,
    tax_id,
    contact_person,
    email,
    phone,
    currency_code,
    payment_terms,
    credit_limit,
    sales_rep_id,
    effective_from,
    effective_to,
    version_no,
    is_current,
    is_deleted,
    deleted_reason,
    created_by,
    created_at,
    updated_by,
    updated_at
   FROM customers
  WHERE is_current = true AND is_deleted = false AND effective_from <= CURRENT_DATE AND (effective_to IS NULL OR effective_to >= CURRENT_DATE);


-- public.v_items_current source

CREATE OR REPLACE VIEW public.v_items_current
AS SELECT item_id,
    item_code,
    division_id,
    item_name,
    item_name_jp,
    item_name_th,
    item_type,
    unit,
    unit_cost_std,
    unit_price_std,
    safety_stock,
    reorder_point,
    lead_time_days,
    lot_managed,
    serial_managed,
    tax_code,
    effective_from,
    effective_to,
    version_no,
    is_current,
    is_deleted,
    deleted_reason,
    created_by,
    created_at,
    updated_by,
    updated_at
   FROM items
  WHERE is_current = true AND is_deleted = false AND effective_from <= CURRENT_DATE AND (effective_to IS NULL OR effective_to >= CURRENT_DATE);


-- public.v_suppliers_current source

CREATE OR REPLACE VIEW public.v_suppliers_current
AS SELECT supplier_id,
    supplier_code,
    division_id,
    supplier_name,
    supplier_name_jp,
    supplier_name_th,
    country,
    address,
    tax_id,
    contact_person,
    email,
    phone,
    currency_code,
    payment_terms,
    wht_rate,
    effective_from,
    effective_to,
    version_no,
    is_current,
    is_deleted,
    deleted_reason,
    created_by,
    created_at,
    updated_by,
    updated_at
   FROM suppliers
  WHERE is_current = true AND is_deleted = false AND effective_from <= CURRENT_DATE AND (effective_to IS NULL OR effective_to >= CURRENT_DATE);