-- Pegasus ERP - consolidated init (generated from prod DDL via DBeaver)
SET client_min_messages = warning;

CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE OR REPLACE FUNCTION public.fn_audit_trigger()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
    v_old JSONB := NULL;
    v_new JSONB := NULL;
    v_changed JSONB := '{}';
    v_key TEXT;
    v_record_id BIGINT;
BEGIN
    IF TG_OP = 'DELETE' THEN
        v_old := to_jsonb(OLD);
        v_record_id := COALESCE(
            (v_old->>TG_ARGV[0])::BIGINT,
            (v_old->>'id')::BIGINT,
            0
        );
        INSERT INTO audit_logs(table_name, record_id, operation, old_values, changed_at)
        VALUES (TG_TABLE_NAME, v_record_id, 'D', v_old, NOW());
        RETURN OLD;
    END IF;

    v_new := to_jsonb(NEW);
    v_record_id := COALESCE(
        (v_new->>TG_ARGV[0])::BIGINT,
        (v_new->>'id')::BIGINT,
        0
    );

    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit_logs(table_name, record_id, operation, new_values, changed_at)
        VALUES (TG_TABLE_NAME, v_record_id, 'I', v_new, NOW());
    ELSIF TG_OP = 'UPDATE' THEN
        v_old := to_jsonb(OLD);
        FOR v_key IN SELECT key FROM jsonb_each(v_new) LOOP
            IF v_old->v_key IS DISTINCT FROM v_new->v_key THEN
                v_changed := v_changed || jsonb_build_object(v_key,
                    jsonb_build_object('old', v_old->v_key, 'new', v_new->v_key));
            END IF;
        END LOOP;
        IF v_changed != '{}' THEN
            INSERT INTO audit_logs(table_name, record_id, operation, old_values, new_values, changed_fields, changed_at)
            VALUES (TG_TABLE_NAME, v_record_id, 'U', v_old, v_new, v_changed, NOW());
        END IF;
    END IF;
    RETURN NEW;
END; $function$
;
-- public.activity_categories definition

-- Drop table

-- DROP TABLE public.activity_categories;

CREATE TABLE public.activity_categories (
	category_id serial4 NOT NULL,
	category_name varchar(50) NOT NULL,
	category_name_jp varchar(50) NULL,
	category_name_th varchar(50) NULL,
	icon varchar(10) DEFAULT ''::character varying NULL,
	sort_order int4 DEFAULT 0 NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT activity_categories_category_name_key UNIQUE (category_name),
	CONSTRAINT activity_categories_pkey PRIMARY KEY (category_id)
);


-- public.audit_logs definition

-- Drop table

-- DROP TABLE public.audit_logs;

CREATE TABLE public.audit_logs (
	log_id bigserial NOT NULL,
	table_name varchar(100) NOT NULL,
	record_id int8 NOT NULL,
	operation bpchar(1) NOT NULL,
	changed_fields jsonb NULL,
	old_values jsonb NULL,
	new_values jsonb NULL,
	changed_by int4 NULL,
	changed_at timestamptz DEFAULT now() NOT NULL,
	ip_address inet NULL,
	session_id varchar(100) NULL,
	reason text NULL,
	CONSTRAINT audit_logs_operation_check CHECK ((operation = ANY (ARRAY['I'::bpchar, 'U'::bpchar, 'D'::bpchar]))),
	CONSTRAINT audit_logs_pkey PRIMARY KEY (log_id)
);
CREATE INDEX idx_audit_changed_at ON public.audit_logs USING btree (changed_at);
CREATE INDEX idx_audit_table_record ON public.audit_logs USING btree (table_name, record_id);


-- public.banks definition

-- Drop table

-- DROP TABLE public.banks;

CREATE TABLE public.banks (
	bank_id serial4 NOT NULL,
	bank_code varchar(10) NOT NULL,
	bank_name varchar(100) NOT NULL,
	bank_name_th varchar(100) NULL,
	swift_code varchar(20) NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT banks_bank_code_key UNIQUE (bank_code),
	CONSTRAINT banks_pkey PRIMARY KEY (bank_id)
);


-- public.company_bank_accounts definition

-- Drop table

-- DROP TABLE public.company_bank_accounts;

CREATE TABLE public.company_bank_accounts (
	cba_id serial4 NOT NULL,
	bank_name varchar(100) NOT NULL,
	bank_name_th varchar(100) NULL,
	branch varchar(100) NULL,
	branch_th varchar(100) NULL,
	account_type varchar(30) DEFAULT 'CURRENT'::character varying NULL,
	account_no varchar(40) NOT NULL,
	account_name varchar(150) NOT NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	swift_code varchar(20) NULL,
	notes text NULL,
	is_default bool DEFAULT false NOT NULL,
	is_active bool DEFAULT true NOT NULL,
	sort_order int4 DEFAULT 0 NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT company_bank_accounts_pkey PRIMARY KEY (cba_id)
);
CREATE UNIQUE INDEX uniq_default_per_currency ON public.company_bank_accounts USING btree (currency_code) WHERE ((is_default = true) AND (is_active = true));


-- public.currencies definition

-- Drop table

-- DROP TABLE public.currencies;

CREATE TABLE public.currencies (
	currency_code bpchar(3) NOT NULL,
	currency_name varchar(50) NOT NULL,
	currency_name_jp varchar(50) NULL,
	currency_name_th varchar(50) NULL,
	symbol varchar(10) NULL,
	sort_order int4 DEFAULT 0 NOT NULL,
	is_active bool DEFAULT true NOT NULL,
	CONSTRAINT currencies_pkey PRIMARY KEY (currency_code)
);


-- public.deal_statuses definition

-- Drop table

-- DROP TABLE public.deal_statuses;

CREATE TABLE public.deal_statuses (
	status_id serial4 NOT NULL,
	status_name varchar(50) NOT NULL,
	status_name_jp varchar(50) NULL,
	status_name_th varchar(50) NULL,
	win_pct numeric(5, 2) DEFAULT 0 NOT NULL,
	sort_order int4 DEFAULT 0 NOT NULL,
	color varchar(20) DEFAULT '#757575'::character varying NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	phase_description varchar(200) NULL,
	stage varchar(50) NULL,
	CONSTRAINT deal_statuses_pkey PRIMARY KEY (status_id),
	CONSTRAINT deal_statuses_status_name_key UNIQUE (status_name)
);


-- public.exchange_rates definition

-- Drop table

-- DROP TABLE public.exchange_rates;

CREATE TABLE public.exchange_rates (
	rate_id serial4 NOT NULL,
	from_currency bpchar(3) NOT NULL,
	to_currency bpchar(3) NOT NULL,
	rate numeric(18, 8) NOT NULL,
	effective_from date NOT NULL,
	effective_to date NULL,
	notes text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT exchange_rates_pkey PRIMARY KEY (rate_id)
);
CREATE INDEX idx_fx_effective ON public.exchange_rates USING btree (effective_from, effective_to) WHERE (is_deleted = false);
CREATE INDEX idx_fx_from_to ON public.exchange_rates USING btree (from_currency, to_currency) WHERE (is_deleted = false);


-- public.number_sequences definition

-- Drop table

-- DROP TABLE public.number_sequences;

CREATE TABLE public.number_sequences (
	seq_name varchar(30) NOT NULL,
	prefix varchar(20) NOT NULL,
	current_no int4 DEFAULT 0 NOT NULL,
	fiscal_year int2 NULL,
	fiscal_month int2 NULL,
	format_pattern varchar(50) DEFAULT '{PREFIX}-{YYYY}-{NNNN}'::character varying NOT NULL,
	CONSTRAINT number_sequences_pkey PRIMARY KEY (seq_name)
);


-- public.permissions definition

-- Drop table

-- DROP TABLE public.permissions;

CREATE TABLE public.permissions (
	permission_code varchar(80) NOT NULL,
	"module" varchar(40) NOT NULL,
	description text NULL,
	description_jp text NULL,
	description_th text NULL,
	CONSTRAINT permissions_pkey PRIMARY KEY (permission_code)
);


-- public.roles definition

-- Drop table

-- DROP TABLE public.roles;

CREATE TABLE public.roles (
	role_code varchar(50) NOT NULL,
	role_name varchar(100) NOT NULL,
	role_name_jp varchar(100) NULL,
	role_name_th varchar(100) NULL,
	description text NULL,
	is_system bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT roles_pkey PRIMARY KEY (role_code)
);


-- public.solution_categories definition

-- Drop table

-- DROP TABLE public.solution_categories;

CREATE TABLE public.solution_categories (
	category_id serial4 NOT NULL,
	category_name varchar(100) NOT NULL,
	category_name_jp varchar(100) NULL,
	category_name_th varchar(100) NULL,
	sort_order int4 DEFAULT 0 NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	classification varchar(10) DEFAULT '-'::character varying NULL,
	eval_profit_rate numeric(5, 2) DEFAULT 0 NULL,
	category_group varchar(30) NULL,
	evaluation_profit_pct numeric(5, 2) DEFAULT 100 NULL,
	CONSTRAINT solution_categories_category_name_key UNIQUE (category_name),
	CONSTRAINT solution_categories_pkey PRIMARY KEY (category_id)
);


-- public.work_schedules definition

-- Drop table

-- DROP TABLE public.work_schedules;

CREATE TABLE public.work_schedules (
	schedule_id serial4 NOT NULL,
	schedule_code varchar(20) NOT NULL,
	schedule_name varchar(100) NOT NULL,
	work_start_time time DEFAULT '08:00:00'::time without time zone NOT NULL,
	work_end_time time DEFAULT '17:00:00'::time without time zone NOT NULL,
	break_minutes int4 DEFAULT 60 NOT NULL,
	work_days_per_week int4 DEFAULT 5 NOT NULL,
	std_hours_per_day numeric(4, 2) DEFAULT 8.0 NOT NULL,
	std_hours_per_week numeric(5, 2) DEFAULT 40.0 NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT work_schedules_pkey PRIMARY KEY (schedule_id),
	CONSTRAINT work_schedules_schedule_code_key UNIQUE (schedule_code)
);


-- public.divisions definition

-- Drop table

-- DROP TABLE public.divisions;

CREATE TABLE public.divisions (
	division_id serial4 NOT NULL,
	division_code varchar(20) NOT NULL,
	division_name varchar(100) NOT NULL,
	division_name_jp varchar(100) NULL,
	parent_id int4 NULL,
	division_type varchar(20) NULL,
	country_code bpchar(2) DEFAULT 'TH'::bpchar NOT NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	tax_id varchar(50) NULL,
	is_deleted bool DEFAULT false NOT NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	division_name_th varchar(100) NULL,
	CONSTRAINT divisions_division_code_key UNIQUE (division_code),
	CONSTRAINT divisions_division_type_check CHECK (((division_type)::text = ANY (ARRAY[('COMPANY'::character varying)::text, ('BRANCH'::character varying)::text, ('DEPARTMENT'::character varying)::text, ('SECTION'::character varying)::text]))),
	CONSTRAINT divisions_pkey PRIMARY KEY (division_id),
	CONSTRAINT divisions_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES public.divisions(division_id)
);

-- Table Triggers

create trigger trg_audit_divisions after
insert
    or
delete
    or
update
    on
    public.divisions for each row execute function fn_audit_trigger('division_id');


-- public.expense_account_mapping definition

-- Drop table

-- DROP TABLE public.expense_account_mapping;

CREATE TABLE public.expense_account_mapping (
	mapping_id serial4 NOT NULL,
	expense_category varchar(30) NOT NULL,
	account_code varchar(20) NOT NULL,
	division_id int4 NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT expense_account_mapping_pkey PRIMARY KEY (mapping_id),
	CONSTRAINT expense_account_mapping_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id)
);


-- public.items definition

-- Drop table

-- DROP TABLE public.items;

CREATE TABLE public.items (
	item_id serial4 NOT NULL,
	item_code varchar(50) NOT NULL,
	division_id int4 NOT NULL,
	item_name varchar(200) NOT NULL,
	item_name_jp varchar(200) NULL,
	item_name_th varchar(200) NULL,
	item_type varchar(20) NOT NULL,
	unit varchar(20) DEFAULT 'EA'::character varying NOT NULL,
	unit_cost_std numeric(18, 4) DEFAULT 0 NULL,
	unit_price_std numeric(18, 4) DEFAULT 0 NULL,
	safety_stock numeric(14, 4) DEFAULT 0 NULL,
	reorder_point numeric(14, 4) DEFAULT 0 NULL,
	lead_time_days int4 DEFAULT 7 NULL,
	lot_managed bool DEFAULT false NOT NULL,
	serial_managed bool DEFAULT false NOT NULL,
	tax_code varchar(20) NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	version_no int4 DEFAULT 1 NOT NULL,
	is_current bool DEFAULT true NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	deleted_reason text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT items_item_code_division_id_effective_from_key UNIQUE (item_code, division_id, effective_from),
	CONSTRAINT items_item_type_check CHECK (((item_type)::text = ANY (ARRAY[('RAW'::character varying)::text, ('WIP'::character varying)::text, ('FINISHED'::character varying)::text, ('MERCHANDISE'::character varying)::text, ('SERVICE'::character varying)::text, ('SPARE'::character varying)::text]))),
	CONSTRAINT items_pkey PRIMARY KEY (item_id),
	CONSTRAINT items_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id)
);

-- Table Triggers

create trigger trg_audit_items after
insert
    or
delete
    or
update
    on
    public.items for each row execute function fn_audit_trigger('item_id');


-- public.mrp_snapshots definition

-- Drop table

-- DROP TABLE public.mrp_snapshots;

CREATE TABLE public.mrp_snapshots (
	snapshot_id serial4 NOT NULL,
	snapshot_date date DEFAULT CURRENT_DATE NOT NULL,
	period_from date NOT NULL,
	period_to date NOT NULL,
	division_id int4 NOT NULL,
	status varchar(20) DEFAULT 'CALCULATING'::character varying NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT mrp_snapshots_pkey PRIMARY KEY (snapshot_id),
	CONSTRAINT mrp_snapshots_status_check CHECK (((status)::text = ANY (ARRAY[('CALCULATING'::character varying)::text, ('COMPLETED'::character varying)::text, ('FAILED'::character varying)::text]))),
	CONSTRAINT mrp_snapshots_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id)
);


-- public.payment_terms definition

-- Drop table

-- DROP TABLE public.payment_terms;

CREATE TABLE public.payment_terms (
	term_id serial4 NOT NULL,
	term_code varchar(30) NOT NULL,
	division_id int4 NULL,
	term_name_en varchar(200) NOT NULL,
	term_name_jp varchar(200) NULL,
	term_name_th varchar(200) NULL,
	installment_count int4 DEFAULT 1 NOT NULL,
	credit_days int4 NULL,
	display_order int4 DEFAULT 0 NOT NULL,
	notes text NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	version_no int4 DEFAULT 1 NOT NULL,
	is_current bool DEFAULT true NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	deleted_reason text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT payment_terms_pkey PRIMARY KEY (term_id),
	CONSTRAINT payment_terms_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id)
);


-- public.public_holidays definition

-- Drop table

-- DROP TABLE public.public_holidays;

CREATE TABLE public.public_holidays (
	holiday_id serial4 NOT NULL,
	holiday_date date NOT NULL,
	holiday_name varchar(100) NOT NULL,
	holiday_name_jp varchar(100) NULL,
	holiday_type varchar(20) NOT NULL,
	fiscal_year int2 NOT NULL,
	division_id int4 NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT public_holidays_holiday_type_check CHECK (((holiday_type)::text = ANY (ARRAY[('NATIONAL'::character varying)::text, ('BANK'::character varying)::text, ('COMPANY'::character varying)::text]))),
	CONSTRAINT public_holidays_pkey PRIMARY KEY (holiday_id),
	CONSTRAINT public_holidays_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id)
);


-- public.role_permissions definition

-- Drop table

-- DROP TABLE public.role_permissions;

CREATE TABLE public.role_permissions (
	role_code varchar(50) NOT NULL,
	permission_code varchar(80) NOT NULL,
	"granted" bool DEFAULT true NOT NULL,
	CONSTRAINT role_permissions_pkey PRIMARY KEY (role_code, permission_code),
	CONSTRAINT role_permissions_permission_code_fkey FOREIGN KEY (permission_code) REFERENCES public.permissions(permission_code) ON DELETE CASCADE,
	CONSTRAINT role_permissions_role_code_fkey FOREIGN KEY (role_code) REFERENCES public.roles(role_code) ON DELETE CASCADE
);


-- public.standard_costs definition

-- Drop table

-- DROP TABLE public.standard_costs;

CREATE TABLE public.standard_costs (
	std_cost_id serial4 NOT NULL,
	item_id int4 NOT NULL,
	division_id int4 NULL,
	material_cost numeric(18, 4) DEFAULT 0 NOT NULL,
	labor_cost numeric(18, 4) DEFAULT 0 NOT NULL,
	overhead_cost numeric(18, 4) DEFAULT 0 NOT NULL,
	total_std_cost numeric(18, 4) DEFAULT 0 NOT NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	is_current bool DEFAULT true NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT standard_costs_pkey PRIMARY KEY (std_cost_id),
	CONSTRAINT standard_costs_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id),
	CONSTRAINT standard_costs_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id)
);


-- public.warehouses definition

-- Drop table

-- DROP TABLE public.warehouses;

CREATE TABLE public.warehouses (
	warehouse_id serial4 NOT NULL,
	warehouse_code varchar(20) NOT NULL,
	division_id int4 NOT NULL,
	warehouse_name varchar(100) NOT NULL,
	address text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT warehouses_pkey PRIMARY KEY (warehouse_id),
	CONSTRAINT warehouses_warehouse_code_division_id_key UNIQUE (warehouse_code, division_id),
	CONSTRAINT warehouses_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id)
);


-- public.accounts definition

-- Drop table

-- DROP TABLE public.accounts;

CREATE TABLE public.accounts (
	account_id serial4 NOT NULL,
	account_code varchar(20) NOT NULL,
	division_id int4 NULL,
	account_name varchar(200) NOT NULL,
	account_name_jp varchar(200) NULL,
	account_name_th varchar(200) NULL,
	account_type varchar(20) NOT NULL,
	bs_pl bpchar(2) NOT NULL,
	parent_code varchar(20) NULL,
	is_tax_relevant bool DEFAULT false NOT NULL,
	tax_form varchar(20) NULL,
	default_tax_rate numeric(5, 2) NULL,
	is_control_acct bool DEFAULT false NOT NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	version_no int4 DEFAULT 1 NOT NULL,
	is_current bool DEFAULT true NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	deleted_reason text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT accounts_account_code_division_id_effective_from_key UNIQUE (account_code, division_id, effective_from),
	CONSTRAINT accounts_account_type_check CHECK (((account_type)::text = ANY (ARRAY[('ASSET'::character varying)::text, ('LIABILITY'::character varying)::text, ('EQUITY'::character varying)::text, ('REVENUE'::character varying)::text, ('COGS'::character varying)::text, ('EXPENSE'::character varying)::text]))),
	CONSTRAINT accounts_bs_pl_check CHECK ((bs_pl = ANY (ARRAY['BS'::bpchar, 'PL'::bpchar]))),
	CONSTRAINT accounts_pkey PRIMARY KEY (account_id),
	CONSTRAINT accounts_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id)
);

-- Table Triggers

create trigger trg_audit_accounts after
insert
    or
delete
    or
update
    on
    public.accounts for each row execute function fn_audit_trigger('account_id');


-- public.bom_headers definition

-- Drop table

-- DROP TABLE public.bom_headers;

CREATE TABLE public.bom_headers (
	bom_id serial4 NOT NULL,
	item_id int4 NOT NULL,
	division_id int4 NOT NULL,
	bom_code varchar(30) NOT NULL,
	bom_name varchar(200) NULL,
	revision int2 DEFAULT 1 NOT NULL,
	yield_qty numeric(14, 4) DEFAULT 1 NOT NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	is_current bool DEFAULT true NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT bom_headers_bom_code_division_id_revision_key UNIQUE (bom_code, division_id, revision),
	CONSTRAINT bom_headers_pkey PRIMARY KEY (bom_id),
	CONSTRAINT bom_headers_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id),
	CONSTRAINT bom_headers_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id)
);


-- public.bom_lines definition

-- Drop table

-- DROP TABLE public.bom_lines;

CREATE TABLE public.bom_lines (
	bom_line_id serial4 NOT NULL,
	bom_id int4 NOT NULL,
	line_no int2 NOT NULL,
	component_item_id int4 NOT NULL,
	quantity_per numeric(14, 6) NOT NULL,
	unit varchar(20) DEFAULT 'EA'::character varying NOT NULL,
	scrap_rate numeric(5, 4) DEFAULT 0 NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT bom_lines_bom_id_line_no_key UNIQUE (bom_id, line_no),
	CONSTRAINT bom_lines_pkey PRIMARY KEY (bom_line_id),
	CONSTRAINT bom_lines_bom_id_fkey FOREIGN KEY (bom_id) REFERENCES public.bom_headers(bom_id) ON DELETE CASCADE,
	CONSTRAINT bom_lines_component_item_id_fkey FOREIGN KEY (component_item_id) REFERENCES public.items(item_id)
);


-- public.inventory_transactions definition

-- Drop table

-- DROP TABLE public.inventory_transactions;

CREATE TABLE public.inventory_transactions (
	txn_id serial4 NOT NULL,
	txn_no varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	warehouse_id int4 NOT NULL,
	txn_type varchar(20) NOT NULL,
	txn_date date DEFAULT CURRENT_DATE NOT NULL,
	reference_type varchar(20) NULL,
	reference_id int4 NULL,
	item_id int4 NOT NULL,
	quantity numeric(14, 4) NOT NULL,
	unit varchar(20) DEFAULT 'EA'::character varying NOT NULL,
	unit_cost numeric(18, 4) DEFAULT 0 NULL,
	total_cost numeric(18, 2) DEFAULT 0 NULL,
	lot_no varchar(50) NULL,
	serial_no varchar(50) NULL,
	notes text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT inventory_transactions_pkey PRIMARY KEY (txn_id),
	CONSTRAINT inventory_transactions_txn_no_key UNIQUE (txn_no),
	CONSTRAINT inventory_transactions_txn_type_check CHECK (((txn_type)::text = ANY (ARRAY[('RECEIPT'::character varying)::text, ('ISSUE'::character varying)::text, ('TRANSFER'::character varying)::text, ('ADJUSTMENT'::character varying)::text, ('RETURN'::character varying)::text]))),
	CONSTRAINT inventory_transactions_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id),
	CONSTRAINT inventory_transactions_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id),
	CONSTRAINT inventory_transactions_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES public.warehouses(warehouse_id)
);


-- public.mrp_items definition

-- Drop table

-- DROP TABLE public.mrp_items;

CREATE TABLE public.mrp_items (
	mrp_item_id serial4 NOT NULL,
	snapshot_id int4 NOT NULL,
	item_id int4 NOT NULL,
	item_code varchar(50) NOT NULL,
	bom_level int2 DEFAULT 0 NOT NULL,
	bom_ratio numeric(12, 6) DEFAULT 1 NOT NULL,
	production_leadtime int4 DEFAULT 0 NOT NULL,
	rm_leadtime int4 DEFAULT 0 NOT NULL,
	order_point numeric(14, 4) DEFAULT 0 NOT NULL,
	stock_base_date numeric(14, 4) DEFAULT 0 NOT NULL,
	is_checked bool DEFAULT false NOT NULL,
	has_action bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT mrp_items_pkey PRIMARY KEY (mrp_item_id),
	CONSTRAINT mrp_items_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id),
	CONSTRAINT mrp_items_snapshot_id_fkey FOREIGN KEY (snapshot_id) REFERENCES public.mrp_snapshots(snapshot_id)
);


-- public.payment_term_installments definition

-- Drop table

-- DROP TABLE public.payment_term_installments;

CREATE TABLE public.payment_term_installments (
	installment_id serial4 NOT NULL,
	term_id int4 NOT NULL,
	seq_no int2 NOT NULL,
	percentage numeric(5, 2) NOT NULL,
	description_en text NULL,
	description_jp text NULL,
	description_th text NULL,
	trigger_type varchar(20) DEFAULT 'CUSTOM'::character varying NOT NULL,
	credit_days int4 NULL,
	CONSTRAINT payment_term_installments_pkey PRIMARY KEY (installment_id),
	CONSTRAINT payment_term_installments_term_id_seq_no_key UNIQUE (term_id, seq_no),
	CONSTRAINT payment_term_installments_trigger_type_check CHECK (((trigger_type)::text = ANY (ARRAY[('PO'::character varying)::text, ('DESIGN'::character varying)::text, ('DELIVERY'::character varying)::text, ('INSTALLATION'::character varying)::text, ('COMPLETION'::character varying)::text, ('FAT'::character varying)::text, ('SAT'::character varying)::text, ('INVOICE'::character varying)::text, ('CUSTOM'::character varying)::text]))),
	CONSTRAINT payment_term_installments_term_id_fkey FOREIGN KEY (term_id) REFERENCES public.payment_terms(term_id) ON DELETE CASCADE
);


-- public.stock_balances definition

-- Drop table

-- DROP TABLE public.stock_balances;

CREATE TABLE public.stock_balances (
	balance_id serial4 NOT NULL,
	warehouse_id int4 NOT NULL,
	item_id int4 NOT NULL,
	quantity_on_hand numeric(14, 4) DEFAULT 0 NOT NULL,
	quantity_reserved numeric(14, 4) DEFAULT 0 NOT NULL,
	quantity_available numeric(14, 4) DEFAULT 0 NOT NULL,
	avg_unit_cost numeric(18, 4) DEFAULT 0 NOT NULL,
	last_updated timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT stock_balances_pkey PRIMARY KEY (balance_id),
	CONSTRAINT stock_balances_warehouse_id_item_id_key UNIQUE (warehouse_id, item_id),
	CONSTRAINT stock_balances_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id),
	CONSTRAINT stock_balances_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES public.warehouses(warehouse_id)
);


-- public.mrp_daily_quantities definition

-- Drop table

-- DROP TABLE public.mrp_daily_quantities;

CREATE TABLE public.mrp_daily_quantities (
	daily_qty_id serial4 NOT NULL,
	mrp_item_id int4 NOT NULL,
	calc_date date NOT NULL,
	order_forecast_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	order_point_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	current_stock_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	inbound_schedule_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	stock_incl_expected numeric(14, 4) DEFAULT 0 NOT NULL,
	diff_stock_orderpt numeric(14, 4) DEFAULT 0 NOT NULL,
	required_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	order_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	po_issued_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	CONSTRAINT mrp_daily_quantities_mrp_item_id_calc_date_key UNIQUE (mrp_item_id, calc_date),
	CONSTRAINT mrp_daily_quantities_pkey PRIMARY KEY (daily_qty_id),
	CONSTRAINT mrp_daily_quantities_mrp_item_id_fkey FOREIGN KEY (mrp_item_id) REFERENCES public.mrp_items(mrp_item_id)
);


-- public.ap_invoice_lines definition

-- Drop table

-- DROP TABLE public.ap_invoice_lines;

CREATE TABLE public.ap_invoice_lines (
	ap_line_id serial4 NOT NULL,
	ap_invoice_id int4 NOT NULL,
	line_no int2 NOT NULL,
	item_id int4 NULL,
	item_description varchar(500) NOT NULL,
	quantity numeric(14, 4) NOT NULL,
	unit varchar(30) DEFAULT 'EA'::character varying NOT NULL,
	unit_price numeric(18, 4) NOT NULL,
	ext_price numeric(18, 2) DEFAULT 0 NULL,
	account_code varchar(20) NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT ap_invoice_lines_ap_invoice_id_line_no_key UNIQUE (ap_invoice_id, line_no),
	CONSTRAINT ap_invoice_lines_pkey PRIMARY KEY (ap_line_id)
);


-- public.ap_invoices definition

-- Drop table

-- DROP TABLE public.ap_invoices;

CREATE TABLE public.ap_invoices (
	ap_invoice_id serial4 NOT NULL,
	ap_invoice_no varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	supplier_id int4 NOT NULL,
	po_id int4 NULL,
	supplier_invoice_no varchar(100) NULL,
	invoice_date date DEFAULT CURRENT_DATE NOT NULL,
	due_date date NOT NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	subtotal_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	vat_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	wht_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	grand_total_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	paid_amount_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	balance_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	status varchar(20) DEFAULT 'OPEN'::character varying NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT ap_invoices_ap_invoice_no_key UNIQUE (ap_invoice_no),
	CONSTRAINT ap_invoices_pkey PRIMARY KEY (ap_invoice_id),
	CONSTRAINT ap_invoices_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('OPEN'::character varying)::text, ('PARTIAL'::character varying)::text, ('PAID'::character varying)::text, ('OVERDUE'::character varying)::text, ('CANCELLED'::character varying)::text])))
);


-- public.ap_payment_allocations definition

-- Drop table

-- DROP TABLE public.ap_payment_allocations;

CREATE TABLE public.ap_payment_allocations (
	allocation_id serial4 NOT NULL,
	payment_id int4 NOT NULL,
	ap_invoice_id int4 NOT NULL,
	allocated_amount numeric(18, 2) NOT NULL,
	CONSTRAINT ap_payment_allocations_pkey PRIMARY KEY (allocation_id)
);


-- public.ap_payments definition

-- Drop table

-- DROP TABLE public.ap_payments;

CREATE TABLE public.ap_payments (
	payment_id serial4 NOT NULL,
	payment_no varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	supplier_id int4 NOT NULL,
	payment_date date DEFAULT CURRENT_DATE NOT NULL,
	amount_thb numeric(18, 2) NOT NULL,
	payment_method varchar(20) NOT NULL,
	wht_amount numeric(18, 2) DEFAULT 0 NULL,
	bank_name varchar(100) NULL,
	cheque_no varchar(50) NULL,
	reference_no varchar(100) NULL,
	notes text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT ap_payments_payment_method_check CHECK (((payment_method)::text = ANY (ARRAY[('CASH'::character varying)::text, ('BANK_TRANSFER'::character varying)::text, ('CHEQUE'::character varying)::text, ('OTHER'::character varying)::text]))),
	CONSTRAINT ap_payments_payment_no_key UNIQUE (payment_no),
	CONSTRAINT ap_payments_pkey PRIMARY KEY (payment_id)
);


-- public.ar_invoice_lines definition

-- Drop table

-- DROP TABLE public.ar_invoice_lines;

CREATE TABLE public.ar_invoice_lines (
	ar_line_id serial4 NOT NULL,
	invoice_id int4 NOT NULL,
	line_no int2 NOT NULL,
	item_id int4 NULL,
	item_description varchar(500) NOT NULL,
	quantity numeric(14, 4) NOT NULL,
	unit varchar(30) DEFAULT 'EA'::character varying NOT NULL,
	unit_price numeric(18, 4) NOT NULL,
	discount_rate numeric(5, 2) DEFAULT 0 NULL,
	ext_price numeric(18, 2) DEFAULT 0 NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT ar_invoice_lines_invoice_id_line_no_key UNIQUE (invoice_id, line_no),
	CONSTRAINT ar_invoice_lines_pkey PRIMARY KEY (ar_line_id)
);


-- public.ar_invoices definition

-- Drop table

-- DROP TABLE public.ar_invoices;

CREATE TABLE public.ar_invoices (
	invoice_id serial4 NOT NULL,
	invoice_no varchar(30) NOT NULL,
	invoice_type varchar(20) DEFAULT 'TAX_INVOICE'::character varying NOT NULL,
	is_copy bool DEFAULT false NOT NULL,
	copy_purpose varchar(50) NULL,
	division_id int4 NOT NULL,
	so_id int4 NULL,
	shipment_id int4 NULL,
	po_reference varchar(100) NULL,
	our_quotation_id int4 NULL,
	payment_term_id int4 NULL,
	installment_seq int4 NULL,
	customer_id int4 NOT NULL,
	bill_to_name varchar(200) NULL,
	bill_to_tax_id varchar(20) NULL,
	bill_to_branch varchar(50) NULL,
	bill_to_address text NULL,
	bill_to_phone varchar(200) NULL,
	salesperson_id int4 NULL,
	invoice_date date DEFAULT CURRENT_DATE NOT NULL,
	invoice_date_be varchar(20) NULL,
	due_date date NOT NULL,
	due_date_be varchar(20) NULL,
	credit_term_text varchar(100) NULL,
	credit_days int4 DEFAULT 30 NOT NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	exchange_rate numeric(18, 6) DEFAULT 1 NOT NULL,
	subtotal_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	special_discount numeric(18, 2) DEFAULT 0 NOT NULL,
	total_after_discount numeric(18, 2) DEFAULT 0 NOT NULL,
	vat_rate numeric(5, 2) DEFAULT 7.00 NOT NULL,
	vat_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	grand_total_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	paid_amount_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	balance_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	payment_method varchar(20) NULL,
	bank_name varchar(100) NULL,
	cheque_no varchar(50) NULL,
	cheque_date date NULL,
	payment_note text NULL,
	status varchar(20) DEFAULT 'OPEN'::character varying NOT NULL,
	sig_bill_collector_name varchar(100) NULL,
	sig_bill_collector_date date NULL,
	sig_goods_deliver_name varchar(100) NULL,
	sig_goods_deliver_date date NULL,
	sig_goods_receiver_name varchar(100) NULL,
	sig_goods_receiver_date date NULL,
	sig_authorized_name varchar(100) NULL,
	sig_authorized_date date NULL,
	sig_authorized_url varchar(500) NULL,
	payment_received_note text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	deleted_reason text NULL,
	cancel_reason text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	installment_seqs varchar(100) NULL,
	CONSTRAINT ar_invoices_invoice_no_key UNIQUE (invoice_no),
	CONSTRAINT ar_invoices_invoice_type_check CHECK (((invoice_type)::text = ANY (ARRAY[('TAX_INVOICE'::character varying)::text, ('RECEIPT_TAX'::character varying)::text, ('RECEIPT'::character varying)::text, ('DEBIT_NOTE'::character varying)::text, ('CREDIT_NOTE'::character varying)::text, ('PROFORMA'::character varying)::text]))),
	CONSTRAINT ar_invoices_payment_method_check CHECK (((payment_method)::text = ANY (ARRAY[('CASH'::character varying)::text, ('BANK_TRANSFER'::character varying)::text, ('CHEQUE'::character varying)::text, ('OTHER'::character varying)::text]))),
	CONSTRAINT ar_invoices_pkey PRIMARY KEY (invoice_id),
	CONSTRAINT ar_invoices_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('OPEN'::character varying)::text, ('PARTIAL'::character varying)::text, ('PAID'::character varying)::text, ('OVERDUE'::character varying)::text, ('CANCELLED'::character varying)::text, ('VOID'::character varying)::text])))
);

-- Table Triggers

create trigger trg_audit_ar_invoices after
insert
    or
delete
    or
update
    on
    public.ar_invoices for each row execute function fn_audit_trigger('invoice_id');


-- public.ar_payment_allocations definition

-- Drop table

-- DROP TABLE public.ar_payment_allocations;

CREATE TABLE public.ar_payment_allocations (
	allocation_id serial4 NOT NULL,
	payment_id int4 NOT NULL,
	invoice_id int4 NOT NULL,
	allocated_amount numeric(18, 2) NOT NULL,
	CONSTRAINT ar_payment_allocations_pkey PRIMARY KEY (allocation_id)
);


-- public.ar_payments definition

-- Drop table

-- DROP TABLE public.ar_payments;

CREATE TABLE public.ar_payments (
	payment_id serial4 NOT NULL,
	payment_no varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	customer_id int4 NOT NULL,
	payment_date date DEFAULT CURRENT_DATE NOT NULL,
	amount_thb numeric(18, 2) NOT NULL,
	payment_method varchar(20) NOT NULL,
	bank_name varchar(100) NULL,
	cheque_no varchar(50) NULL,
	reference_no varchar(100) NULL,
	notes text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT ar_payments_payment_method_check CHECK (((payment_method)::text = ANY (ARRAY[('CASH'::character varying)::text, ('BANK_TRANSFER'::character varying)::text, ('CHEQUE'::character varying)::text, ('OTHER'::character varying)::text]))),
	CONSTRAINT ar_payments_payment_no_key UNIQUE (payment_no),
	CONSTRAINT ar_payments_pkey PRIMARY KEY (payment_id)
);


-- public.attendance_records definition

-- Drop table

-- DROP TABLE public.attendance_records;

CREATE TABLE public.attendance_records (
	attendance_id serial4 NOT NULL,
	employee_id int4 NOT NULL,
	attendance_date date NOT NULL,
	clock_in timestamptz NULL,
	clock_out timestamptz NULL,
	break_start timestamptz NULL,
	break_end timestamptz NULL,
	regular_hours numeric(5, 2) DEFAULT 0 NULL,
	overtime_hours numeric(5, 2) DEFAULT 0 NULL,
	holiday_hours numeric(5, 2) DEFAULT 0 NULL,
	night_hours numeric(5, 2) DEFAULT 0 NULL,
	status varchar(20) DEFAULT 'PRESENT'::character varying NOT NULL,
	late_minutes int4 DEFAULT 0 NULL,
	note text NULL,
	clock_in_method varchar(20) NULL,
	clock_out_method varchar(20) NULL,
	approved_by int4 NULL,
	approved_at timestamptz NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT attendance_records_employee_id_attendance_date_key UNIQUE (employee_id, attendance_date),
	CONSTRAINT attendance_records_pkey PRIMARY KEY (attendance_id),
	CONSTRAINT attendance_records_status_check CHECK (((status)::text = ANY (ARRAY[('PRESENT'::character varying)::text, ('ABSENT'::character varying)::text, ('LATE'::character varying)::text, ('HALF_DAY'::character varying)::text, ('LEAVE'::character varying)::text, ('HOLIDAY'::character varying)::text, ('ON_DUTY_TRAVEL'::character varying)::text])))
);


-- public.business_cards definition

-- Drop table

-- DROP TABLE public.business_cards;

CREATE TABLE public.business_cards (
	card_id serial4 NOT NULL,
	contact_id int4 NULL,
	customer_id int4 NULL,
	file_path varchar(500) NOT NULL,
	file_name varchar(255) NULL,
	file_size int4 NULL,
	mime_type varchar(80) NULL,
	ocr_raw_text text NULL,
	uploaded_by int4 NULL,
	uploaded_at timestamptz DEFAULT now() NOT NULL,
	thumbnail_path varchar(500) NULL,
	CONSTRAINT business_cards_pkey PRIMARY KEY (card_id)
);
CREATE INDEX idx_business_cards_contact ON public.business_cards USING btree (contact_id);
CREATE INDEX idx_business_cards_customer ON public.business_cards USING btree (customer_id);


-- public.cost_sheets definition

-- Drop table

-- DROP TABLE public.cost_sheets;

CREATE TABLE public.cost_sheets (
	cost_sheet_id serial4 NOT NULL,
	sheet_no varchar(30) NOT NULL,
	sheet_name varchar(500) NOT NULL,
	customer_id int4 NULL,
	quotation_id int4 NULL,
	project_id int4 NULL,
	total_cost numeric(18, 2) DEFAULT 0 NULL,
	status varchar(20) DEFAULT 'DRAFT'::character varying NOT NULL,
	notes text NULL,
	source_file varchar(500) NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT cost_sheets_pkey PRIMARY KEY (cost_sheet_id),
	CONSTRAINT cost_sheets_sheet_no_key UNIQUE (sheet_no),
	CONSTRAINT cost_sheets_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('CONFIRMED'::character varying)::text, ('LINKED'::character varying)::text])))
);
CREATE INDEX idx_cost_sheets_customer ON public.cost_sheets USING btree (customer_id);
CREATE INDEX idx_cost_sheets_project ON public.cost_sheets USING btree (project_id);
CREATE INDEX idx_cost_sheets_quotation ON public.cost_sheets USING btree (quotation_id);


-- public.customer_contacts definition

-- Drop table

-- DROP TABLE public.customer_contacts;

CREATE TABLE public.customer_contacts (
	contact_id serial4 NOT NULL,
	customer_id int4 NULL,
	full_name varchar(150) NOT NULL,
	full_name_local varchar(150) NULL,
	title varchar(120) NULL,
	department varchar(120) NULL,
	company_name varchar(200) NULL,
	email varchar(200) NULL,
	phone varchar(50) NULL,
	mobile varchar(50) NULL,
	fax varchar(50) NULL,
	address text NULL,
	website varchar(200) NULL,
	is_primary bool DEFAULT false NOT NULL,
	notes text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT customer_contacts_pkey PRIMARY KEY (contact_id)
);
CREATE INDEX idx_customer_contacts_customer ON public.customer_contacts USING btree (customer_id) WHERE (is_deleted = false);
CREATE INDEX idx_customer_contacts_email ON public.customer_contacts USING btree (lower((email)::text));


-- public.customers definition

-- Drop table

-- DROP TABLE public.customers;

CREATE TABLE public.customers (
	customer_id serial4 NOT NULL,
	customer_code varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	customer_name varchar(200) NOT NULL,
	customer_name_jp varchar(200) NULL,
	customer_name_th varchar(200) NULL,
	country bpchar(2) DEFAULT 'TH'::bpchar NOT NULL,
	address text NULL,
	tax_id varchar(20) NULL,
	contact_person varchar(100) NULL,
	email varchar(200) NULL,
	phone varchar(50) NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	payment_terms int4 DEFAULT 30 NOT NULL,
	credit_limit numeric(18, 2) DEFAULT 0 NULL,
	sales_rep_id int4 NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	version_no int4 DEFAULT 1 NOT NULL,
	is_current bool DEFAULT true NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	deleted_reason text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	approval_status varchar(20) DEFAULT 'APPROVED'::character varying NULL,
	approved_by int4 NULL,
	approved_at timestamptz NULL,
	submitted_by int4 NULL,
	submitted_at timestamptz NULL,
	manager_approved_by int4 NULL,
	manager_approved_at timestamptz NULL,
	ceo_approved_by int4 NULL,
	ceo_approved_at timestamptz NULL,
	rejected_by int4 NULL,
	rejected_at timestamptz NULL,
	rejection_reason text NULL,
	CONSTRAINT customers_approval_status_check CHECK (((approval_status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('PENDING'::character varying)::text, ('PENDING_MANAGER'::character varying)::text, ('PENDING_CEO'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text]))),
	CONSTRAINT customers_customer_code_division_id_effective_from_key UNIQUE (customer_code, division_id, effective_from),
	CONSTRAINT customers_pkey PRIMARY KEY (customer_id)
);
CREATE UNIQUE INDEX uniq_customer_code_active ON public.customers USING btree (customer_code) WHERE ((is_deleted = false) AND (is_current = true));

-- Table Triggers

create trigger trg_audit_customers after
insert
    or
delete
    or
update
    on
    public.customers for each row execute function fn_audit_trigger('customer_id');


-- public.deal_activities definition

-- Drop table

-- DROP TABLE public.deal_activities;

CREATE TABLE public.deal_activities (
	activity_id serial4 NOT NULL,
	deal_id int4 NULL,
	activity_category_id int4 NULL,
	activity_date date DEFAULT CURRENT_DATE NOT NULL,
	subject text NOT NULL,
	description text NULL,
	contact_person varchar(100) NULL,
	duration_min int4 NULL,
	next_action text NULL,
	next_action_date date NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	customer_id int4 NULL,
	company_name varchar(200) NULL,
	sales_person_name varchar(100) NULL,
	sales_person_id int4 NULL,
	parent_activity_id int4 NULL,
	contact_id int4 NULL,
	start_time time NULL,
	end_time time NULL,
	CONSTRAINT deal_activities_pkey PRIMARY KEY (activity_id)
);
CREATE INDEX idx_deal_activities_date ON public.deal_activities USING btree (activity_date);
CREATE INDEX idx_deal_activities_deal ON public.deal_activities USING btree (deal_id);
CREATE INDEX idx_deal_activities_parent ON public.deal_activities USING btree (parent_activity_id);
CREATE INDEX idx_deal_activities_sales ON public.deal_activities USING btree (sales_person_id);


-- public.deals definition

-- Drop table

-- DROP TABLE public.deals;

CREATE TABLE public.deals (
	deal_id serial4 NOT NULL,
	deal_no varchar(30) NOT NULL,
	deal_name varchar(200) NOT NULL,
	customer_id int4 NULL,
	customer_staff varchar(100) NULL,
	touch_point varchar(100) NULL,
	status_id int4 NULL,
	solution_category_id int4 NULL,
	expected_amount numeric(18, 2) DEFAULT 0 NULL,
	expected_close date NULL,
	sales_person_id int4 NULL,
	pj_no varchar(50) NULL,
	related_projects text NULL,
	note text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	first_contact_date date NULL,
	budget_status varchar(20) DEFAULT 'No'::character varying NULL,
	budget_amount numeric(18, 2) DEFAULT 0 NULL,
	win_rate int4 DEFAULT 0 NULL,
	est_revenue numeric(18, 2) DEFAULT 0 NULL,
	est_profit numeric(18, 2) DEFAULT 0 NULL,
	eval_profit numeric(18, 2) DEFAULT 0 NULL,
	next_action text NULL,
	due_date date NULL,
	meeting_notes text NULL,
	sales_person_name varchar(100) NULL,
	evaluation_profit_pct numeric(5, 2) NULL,
	CONSTRAINT deals_deal_no_key UNIQUE (deal_no),
	CONSTRAINT deals_pkey PRIMARY KEY (deal_id)
);
CREATE INDEX idx_deals_customer ON public.deals USING btree (customer_id);
CREATE INDEX idx_deals_sales ON public.deals USING btree (sales_person_id);
CREATE INDEX idx_deals_status ON public.deals USING btree (status_id);


-- public.departments definition

-- Drop table

-- DROP TABLE public.departments;

CREATE TABLE public.departments (
	department_id serial4 NOT NULL,
	department_code varchar(20) NOT NULL,
	division_id int4 NOT NULL,
	department_name varchar(100) NOT NULL,
	department_name_jp varchar(100) NULL,
	manager_id int4 NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	department_name_th varchar(100) NULL,
	CONSTRAINT departments_department_code_division_id_key UNIQUE (department_code, division_id),
	CONSTRAINT departments_pkey PRIMARY KEY (department_id)
);


-- public.employees definition

-- Drop table

-- DROP TABLE public.employees;

CREATE TABLE public.employees (
	employee_id serial4 NOT NULL,
	emp_code varchar(20) NOT NULL,
	division_id int4 NOT NULL,
	department_id int4 NULL,
	full_name varchar(100) NOT NULL,
	full_name_jp varchar(100) NULL,
	full_name_th varchar(100) NULL,
	nickname varchar(50) NULL,
	nationality bpchar(2) DEFAULT 'TH'::bpchar NOT NULL,
	thai_id varchar(13) NULL,
	passport_no varchar(30) NULL,
	work_permit_no varchar(50) NULL,
	work_permit_expiry date NULL,
	visa_type varchar(20) NULL,
	visa_expiry date NULL,
	hire_date date NOT NULL,
	probation_end_date date NULL,
	employment_type varchar(20) DEFAULT 'FULL_TIME'::character varying NOT NULL,
	position_title varchar(100) NULL,
	position_level varchar(20) NULL,
	email varchar(200) NULL,
	phone varchar(50) NULL,
	annual_leave_days numeric(5, 1) DEFAULT 6 NOT NULL,
	sick_leave_days numeric(5, 1) DEFAULT 30 NOT NULL,
	leave_balance_annual numeric(5, 1) DEFAULT 0 NOT NULL,
	leave_balance_sick numeric(5, 1) DEFAULT 0 NOT NULL,
	sso_enrolled bool DEFAULT true NOT NULL,
	sso_no varchar(20) NULL,
	"role" varchar(50) NULL,
	approval_limit numeric(18, 2) DEFAULT 0 NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	version_no int4 DEFAULT 1 NOT NULL,
	is_current bool DEFAULT true NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	deleted_reason text NULL,
	termination_date date NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT employees_emp_code_key UNIQUE (emp_code),
	CONSTRAINT employees_employment_type_check CHECK (((employment_type)::text = ANY (ARRAY[('FULL_TIME'::character varying)::text, ('PART_TIME'::character varying)::text, ('CONTRACT'::character varying)::text, ('DAILY'::character varying)::text]))),
	CONSTRAINT employees_pkey PRIMARY KEY (employee_id)
);

-- Table Triggers

create trigger trg_audit_employees after
insert
    or
delete
    or
update
    on
    public.employees for each row execute function fn_audit_trigger('employee_id');


-- public.expense_claim_lines definition

-- Drop table

-- DROP TABLE public.expense_claim_lines;

CREATE TABLE public.expense_claim_lines (
	line_id serial4 NOT NULL,
	claim_id int4 NOT NULL,
	line_no int2 NOT NULL,
	expense_date date NOT NULL,
	expense_category varchar(30) NOT NULL,
	description text NOT NULL,
	account_code varchar(20) NULL,
	amount_thb numeric(14, 2) DEFAULT 0 NOT NULL,
	vat_amount numeric(14, 2) DEFAULT 0 NOT NULL,
	wht_amount numeric(14, 2) DEFAULT 0 NOT NULL,
	receipt_url varchar(500) NULL,
	is_mileage_claim bool DEFAULT false NOT NULL,
	origin_address text NULL,
	destination_address text NULL,
	waypoints text NULL,
	distance_km numeric(8, 2) NULL,
	rate_per_km numeric(6, 2) DEFAULT 5.00 NULL,
	calculated_amount numeric(14, 2) DEFAULT 0 NULL,
	gmaps_response jsonb NULL,
	CONSTRAINT expense_claim_lines_claim_id_line_no_key UNIQUE (claim_id, line_no),
	CONSTRAINT expense_claim_lines_expense_category_check CHECK (((expense_category)::text = ANY (ARRAY[('TRANSPORT_MILEAGE'::character varying)::text, ('TRANSPORT_PUBLIC'::character varying)::text, ('TRANSPORT_TAXI'::character varying)::text, ('ACCOMMODATION'::character varying)::text, ('MEAL'::character varying)::text, ('ENTERTAINMENT'::character varying)::text, ('COMMUNICATION'::character varying)::text, ('STATIONERY'::character varying)::text, ('POSTAGE'::character varying)::text, ('REGISTRATION'::character varying)::text, ('OTHER'::character varying)::text]))),
	CONSTRAINT expense_claim_lines_pkey PRIMARY KEY (line_id)
);


-- public.expense_claims definition

-- Drop table

-- DROP TABLE public.expense_claims;

CREATE TABLE public.expense_claims (
	claim_id serial4 NOT NULL,
	claim_no varchar(30) NOT NULL,
	employee_id int4 NOT NULL,
	division_id int4 NOT NULL,
	claim_date date DEFAULT CURRENT_DATE NOT NULL,
	"period" bpchar(7) NOT NULL,
	title varchar(200) NOT NULL,
	purpose text NULL,
	total_amount_thb numeric(14, 2) DEFAULT 0 NOT NULL,
	status varchar(20) DEFAULT 'DRAFT'::character varying NOT NULL,
	submitted_at timestamptz NULL,
	approved_by int4 NULL,
	approved_at timestamptz NULL,
	paid_at timestamptz NULL,
	payment_method varchar(20) NULL,
	reject_reason text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT expense_claims_claim_no_key UNIQUE (claim_no),
	CONSTRAINT expense_claims_pkey PRIMARY KEY (claim_id),
	CONSTRAINT expense_claims_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('SUBMITTED'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text, ('PAID'::character varying)::text, ('CANCELLED'::character varying)::text])))
);

-- Table Triggers

create trigger trg_audit_expense after
insert
    or
delete
    or
update
    on
    public.expense_claims for each row execute function fn_audit_trigger('claim_id');


-- public.journal_entries definition

-- Drop table

-- DROP TABLE public.journal_entries;

CREATE TABLE public.journal_entries (
	je_id serial4 NOT NULL,
	je_no varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	je_date date DEFAULT CURRENT_DATE NOT NULL,
	"period" bpchar(7) NOT NULL,
	je_type varchar(20) DEFAULT 'MANUAL'::character varying NOT NULL,
	description text NULL,
	reference_type varchar(20) NULL,
	reference_id int4 NULL,
	total_debit numeric(18, 2) DEFAULT 0 NOT NULL,
	total_credit numeric(18, 2) DEFAULT 0 NOT NULL,
	status varchar(20) DEFAULT 'DRAFT'::character varying NOT NULL,
	posted_by int4 NULL,
	posted_at timestamptz NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT journal_entries_je_no_key UNIQUE (je_no),
	CONSTRAINT journal_entries_je_type_check CHECK (((je_type)::text = ANY (ARRAY[('MANUAL'::character varying)::text, ('AUTO_AR'::character varying)::text, ('AUTO_AP'::character varying)::text, ('AUTO_PAYROLL'::character varying)::text, ('AUTO_EXPENSE'::character varying)::text, ('AUTO_INVENTORY'::character varying)::text, ('REVERSAL'::character varying)::text]))),
	CONSTRAINT journal_entries_pkey PRIMARY KEY (je_id),
	CONSTRAINT journal_entries_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('POSTED'::character varying)::text, ('REVERSED'::character varying)::text, ('CANCELLED'::character varying)::text])))
);

-- Table Triggers

create trigger trg_audit_journal after
insert
    or
delete
    or
update
    on
    public.journal_entries for each row execute function fn_audit_trigger('je_id');


-- public.journal_lines definition

-- Drop table

-- DROP TABLE public.journal_lines;

CREATE TABLE public.journal_lines (
	jl_id serial4 NOT NULL,
	je_id int4 NOT NULL,
	line_no int2 NOT NULL,
	account_code varchar(20) NOT NULL,
	description varchar(500) NULL,
	debit_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	credit_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	cost_center varchar(50) NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT journal_lines_je_id_line_no_key UNIQUE (je_id, line_no),
	CONSTRAINT journal_lines_pkey PRIMARY KEY (jl_id)
);


-- public.leave_requests definition

-- Drop table

-- DROP TABLE public.leave_requests;

CREATE TABLE public.leave_requests (
	leave_id serial4 NOT NULL,
	employee_id int4 NOT NULL,
	leave_type varchar(20) NOT NULL,
	start_date date NOT NULL,
	end_date date NOT NULL,
	days_requested numeric(4, 1) NOT NULL,
	half_day bool DEFAULT false NOT NULL,
	half_day_period varchar(5) NULL,
	reason text NULL,
	attachment_url varchar(500) NULL,
	status varchar(20) DEFAULT 'PENDING'::character varying NOT NULL,
	approved_by int4 NULL,
	approved_at timestamptz NULL,
	reject_reason text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT leave_requests_leave_type_check CHECK (((leave_type)::text = ANY (ARRAY[('ANNUAL'::character varying)::text, ('SICK'::character varying)::text, ('PERSONAL'::character varying)::text, ('MATERNITY'::character varying)::text, ('PATERNITY'::character varying)::text, ('ORDINATION'::character varying)::text, ('MILITARY'::character varying)::text, ('COMPASSIONATE'::character varying)::text, ('UNPAID'::character varying)::text]))),
	CONSTRAINT leave_requests_pkey PRIMARY KEY (leave_id),
	CONSTRAINT leave_requests_status_check CHECK (((status)::text = ANY (ARRAY[('PENDING'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text, ('CANCELLED'::character varying)::text])))
);


-- public.mo_headers definition

-- Drop table

-- DROP TABLE public.mo_headers;

CREATE TABLE public.mo_headers (
	mo_id serial4 NOT NULL,
	mo_no varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	item_id int4 NOT NULL,
	bom_id int4 NULL,
	so_id int4 NULL,
	planned_qty numeric(14, 4) NOT NULL,
	completed_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	unit varchar(20) DEFAULT 'EA'::character varying NOT NULL,
	planned_start date NOT NULL,
	planned_end date NULL,
	actual_start date NULL,
	actual_end date NULL,
	status varchar(20) DEFAULT 'PLANNED'::character varying NOT NULL,
	notes text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT mo_headers_mo_no_key UNIQUE (mo_no),
	CONSTRAINT mo_headers_pkey PRIMARY KEY (mo_id),
	CONSTRAINT mo_headers_status_check CHECK (((status)::text = ANY (ARRAY[('PLANNED'::character varying)::text, ('RELEASED'::character varying)::text, ('IN_PROGRESS'::character varying)::text, ('COMPLETED'::character varying)::text, ('CLOSED'::character varying)::text, ('CANCELLED'::character varying)::text])))
);


-- public.mo_lines definition

-- Drop table

-- DROP TABLE public.mo_lines;

CREATE TABLE public.mo_lines (
	mo_line_id serial4 NOT NULL,
	mo_id int4 NOT NULL,
	component_item_id int4 NOT NULL,
	required_qty numeric(14, 4) NOT NULL,
	issued_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	unit varchar(20) DEFAULT 'EA'::character varying NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT mo_lines_pkey PRIMARY KEY (mo_line_id)
);


-- public.mrp_purchase_recommendations definition

-- Drop table

-- DROP TABLE public.mrp_purchase_recommendations;

CREATE TABLE public.mrp_purchase_recommendations (
	recommendation_id serial4 NOT NULL,
	snapshot_id int4 NOT NULL,
	item_id int4 NOT NULL,
	supplier_id int4 NULL,
	recommended_date date NOT NULL,
	required_date date NOT NULL,
	recommended_qty numeric(14, 4) NOT NULL,
	unit varchar(20) NOT NULL,
	unit_price numeric(18, 4) NULL,
	estimated_amount numeric(18, 2) NULL,
	bom_level int2 DEFAULT 0 NOT NULL,
	source_so_id int4 NULL,
	source_mo_id int4 NULL,
	action_status varchar(20) DEFAULT 'PENDING'::character varying NOT NULL,
	po_id int4 NULL,
	notes text NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT mrp_purchase_recommendations_action_status_check CHECK (((action_status)::text = ANY (ARRAY[('PENDING'::character varying)::text, ('APPROVED'::character varying)::text, ('PO_ISSUED'::character varying)::text, ('IGNORED'::character varying)::text]))),
	CONSTRAINT mrp_purchase_recommendations_pkey PRIMARY KEY (recommendation_id)
);


-- public.project_cost_items definition

-- Drop table

-- DROP TABLE public.project_cost_items;

CREATE TABLE public.project_cost_items (
	cost_item_id serial4 NOT NULL,
	project_id int4 NULL,
	line_no int4 DEFAULT 0 NOT NULL,
	category varchar(200) NULL,
	description varchar(500) NULL,
	supplier varchar(200) NULL,
	brand varchar(100) NULL,
	lead_time varchar(50) NULL,
	unit_price numeric(18, 4) DEFAULT 0 NULL,
	quantity numeric(14, 4) DEFAULT 0 NULL,
	total_amount numeric(18, 2) DEFAULT 0 NULL,
	unit varchar(30) NULL,
	remark varchar(500) NULL,
	is_category_row bool DEFAULT false NOT NULL,
	quotation_id int4 NULL,
	"source" varchar(30) DEFAULT 'MANUAL'::character varying NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	cost_sheet_id int4 NULL,
	CONSTRAINT project_cost_items_pkey PRIMARY KEY (cost_item_id)
);
CREATE INDEX idx_cost_items_project ON public.project_cost_items USING btree (project_id);
CREATE INDEX idx_cost_items_quotation ON public.project_cost_items USING btree (quotation_id);
CREATE INDEX idx_cost_items_sheet ON public.project_cost_items USING btree (cost_sheet_id);


-- public.project_invoices definition

-- Drop table

-- DROP TABLE public.project_invoices;

CREATE TABLE public.project_invoices (
	invoice_id serial4 NOT NULL,
	project_id int4 NOT NULL,
	line_no int4 NOT NULL,
	invoice_date date NULL,
	invoice_no varchar(100) NULL,
	customer_name varchar(300) NULL,
	amount numeric(18, 2) DEFAULT 0 NULL,
	remark text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT project_invoices_pkey PRIMARY KEY (invoice_id)
);
CREATE INDEX idx_project_invoices_pj ON public.project_invoices USING btree (project_id);


-- public.project_payment_schedules definition

-- Drop table

-- DROP TABLE public.project_payment_schedules;

CREATE TABLE public.project_payment_schedules (
	schedule_id serial4 NOT NULL,
	project_id int4 NOT NULL,
	seq_no int2 NOT NULL,
	description text NULL,
	percentage numeric(5, 2) DEFAULT 0 NULL,
	credit_days int4 DEFAULT 0 NULL,
	plan_date date NULL,
	actual_date date NULL,
	amount numeric(18, 2) DEFAULT 0 NULL,
	remark text NULL,
	CONSTRAINT project_payment_schedules_pkey PRIMARY KEY (schedule_id),
	CONSTRAINT project_payment_schedules_project_id_seq_no_key UNIQUE (project_id, seq_no)
);
CREATE INDEX idx_project_payment_schedules_pj ON public.project_payment_schedules USING btree (project_id);


-- public.project_progress definition

-- Drop table

-- DROP TABLE public.project_progress;

CREATE TABLE public.project_progress (
	progress_id serial4 NOT NULL,
	project_id int4 NOT NULL,
	month_date date NOT NULL,
	plan_pct numeric(5, 2) DEFAULT 0 NULL,
	actual_pct numeric(5, 2) DEFAULT 0 NULL,
	CONSTRAINT project_progress_pkey PRIMARY KEY (progress_id),
	CONSTRAINT project_progress_project_id_month_date_key UNIQUE (project_id, month_date)
);


-- public.project_purchases definition

-- Drop table

-- DROP TABLE public.project_purchases;

CREATE TABLE public.project_purchases (
	purchase_id serial4 NOT NULL,
	project_id int4 NOT NULL,
	line_no int4 NOT NULL,
	purchase_date date NULL,
	purchase_invoice_no varchar(100) NULL,
	description varchar(500) NULL,
	amount numeric(18, 2) DEFAULT 0 NULL,
	payment_terms varchar(200) NULL,
	po_no varchar(100) NULL,
	po_id int4 NULL,
	supplier_name varchar(300) NULL,
	remark text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT project_purchases_pkey PRIMARY KEY (purchase_id)
);
CREATE INDEX idx_project_purchases_pj ON public.project_purchases USING btree (project_id);


-- public.projects definition

-- Drop table

-- DROP TABLE public.projects;

CREATE TABLE public.projects (
	project_id serial4 NOT NULL,
	pj_no varchar(20) NOT NULL,
	related_pj_no varchar(20) NULL,
	pj_segment varchar(200) NULL,
	pj_category varchar(200) NULL,
	pj_classification varchar(100) NULL,
	pj_name varchar(500) NOT NULL,
	customer_id int4 NULL,
	end_user_customer varchar(300) NULL,
	total_revenue numeric(18, 2) DEFAULT 0 NULL,
	cost_total numeric(18, 2) DEFAULT 0 NULL,
	sales_hardware numeric(18, 2) DEFAULT 0 NULL,
	sales_software numeric(18, 2) DEFAULT 0 NULL,
	sales_sw_development numeric(18, 2) DEFAULT 0 NULL,
	sales_sw_license numeric(18, 2) DEFAULT 0 NULL,
	sales_installation numeric(18, 2) DEFAULT 0 NULL,
	sales_sw_installation numeric(18, 2) DEFAULT 0 NULL,
	sales_hw_wiring numeric(18, 2) DEFAULT 0 NULL,
	total_cost numeric(18, 2) DEFAULT 0 NULL,
	gross_profit numeric(18, 2) DEFAULT 0 NULL,
	profit_pct numeric(5, 2) DEFAULT 0 NULL,
	service_cost numeric(18, 2) DEFAULT 0 NULL,
	engineer_cost numeric(18, 2) DEFAULT 0 NULL,
	mm_programming numeric(6, 2) DEFAULT 0 NULL,
	mm_design numeric(6, 2) DEFAULT 0 NULL,
	mm_testing numeric(6, 2) DEFAULT 0 NULL,
	unit_price_programming numeric(18, 2) DEFAULT 0 NULL,
	unit_price_design numeric(18, 2) DEFAULT 0 NULL,
	unit_price_testing numeric(18, 2) DEFAULT 0 NULL,
	purchase_estimate numeric(18, 2) DEFAULT 0 NULL,
	purchase_target numeric(18, 2) DEFAULT 0 NULL,
	purchase_actual numeric(18, 2) DEFAULT 0 NULL,
	gp_estimate numeric(18, 2) DEFAULT 0 NULL,
	gp_target numeric(18, 2) DEFAULT 0 NULL,
	gp_actual numeric(18, 2) DEFAULT 0 NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NULL,
	po_date date NULL,
	start_work_date date NULL,
	finished_work_date date NULL,
	plan_delivery_date date NULL,
	delivery_date date NULL,
	inspection_date date NULL,
	complete_date date NULL,
	delivery_place varchar(300) NULL,
	payment1_plan_date date NULL,
	payment1_actual_date date NULL,
	payment1_amount numeric(18, 2) DEFAULT 0 NULL,
	payment2_plan_date date NULL,
	payment2_actual_date date NULL,
	payment2_amount numeric(18, 2) DEFAULT 0 NULL,
	payment3_plan_date date NULL,
	payment3_actual_date date NULL,
	payment3_amount numeric(18, 2) DEFAULT 0 NULL,
	so_id int4 NULL,
	deal_id int4 NULL,
	sales_person_id int4 NULL,
	sales_name varchar(100) NULL,
	status varchar(30) DEFAULT 'ACTIVE'::character varying NOT NULL,
	progress_pct numeric(5, 2) DEFAULT 0 NULL,
	remark text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	payment_term_id int4 NULL,
	CONSTRAINT projects_pj_no_key UNIQUE (pj_no),
	CONSTRAINT projects_pkey PRIMARY KEY (project_id),
	CONSTRAINT projects_status_check CHECK (((status)::text = ANY (ARRAY[('ACTIVE'::character varying)::text, ('IN_PROGRESS'::character varying)::text, ('COMPLETED'::character varying)::text, ('ON_HOLD'::character varying)::text, ('CANCELLED'::character varying)::text])))
);
CREATE INDEX idx_projects_customer ON public.projects USING btree (customer_id);
CREATE INDEX idx_projects_deal ON public.projects USING btree (deal_id);
CREATE INDEX idx_projects_pj_no ON public.projects USING btree (pj_no);
CREATE INDEX idx_projects_so ON public.projects USING btree (so_id);
CREATE INDEX idx_projects_status ON public.projects USING btree (status);


-- public.purchase_order_headers definition

-- Drop table

-- DROP TABLE public.purchase_order_headers;

CREATE TABLE public.purchase_order_headers (
	po_id serial4 NOT NULL,
	po_no varchar(100) NOT NULL,
	division_id int4 NOT NULL,
	reference_no varchar(100) NULL,
	supplier_quotation_no varchar(100) NULL,
	our_quotation_id int4 NULL,
	supplier_id int4 NOT NULL,
	order_date date DEFAULT CURRENT_DATE NOT NULL,
	requested_date date NULL,
	delivery_date date NULL,
	contact_person_name varchar(100) NULL,
	contact_person_id int4 NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	exchange_rate numeric(18, 6) DEFAULT 1 NOT NULL,
	subtotal_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	discount_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	vat_rate numeric(5, 2) DEFAULT 7.00 NOT NULL,
	vat_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	total_before_wht numeric(18, 2) DEFAULT 0 NOT NULL,
	wht_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	payment_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	amount_in_words_th text NULL,
	payment_term_id int4 NULL,
	payment_term_text varchar(200) NULL,
	status varchar(20) DEFAULT 'DRAFT'::character varying NOT NULL,
	approved_by int4 NULL,
	approved_at timestamptz NULL,
	signature_url varchar(500) NULL,
	approval_date date NULL,
	qr_code_url varchar(500) NULL,
	qr_code_data text NULL,
	notes text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	deleted_reason text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	project_id int4 NULL,
	approval_status varchar(20) DEFAULT 'APPROVED'::character varying NOT NULL,
	pr_id int4 NULL,
	manager_approved_by int4 NULL,
	manager_approved_at timestamptz NULL,
	ceo_approved_by int4 NULL,
	ceo_approved_at timestamptz NULL,
	rejection_reason text NULL,
	CONSTRAINT purchase_order_headers_pkey PRIMARY KEY (po_id),
	CONSTRAINT purchase_order_headers_po_no_key UNIQUE (po_no),
	CONSTRAINT purchase_order_headers_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('PENDING'::character varying)::text, ('PENDING_APPROVAL'::character varying)::text, ('PENDING_MANAGER'::character varying)::text, ('PENDING_CEO'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text, ('SENT'::character varying)::text, ('RECEIVED'::character varying)::text, ('PARTIAL_RECEIVED'::character varying)::text, ('FULLY_RECEIVED'::character varying)::text, ('CLOSED'::character varying)::text, ('CANCELLED'::character varying)::text])))
);
CREATE INDEX idx_po_pr_id ON public.purchase_order_headers USING btree (pr_id) WHERE (pr_id IS NOT NULL);
CREATE INDEX idx_po_project ON public.purchase_order_headers USING btree (project_id) WHERE (is_deleted = false);

-- Table Triggers

create trigger trg_audit_po after
insert
    or
delete
    or
update
    on
    public.purchase_order_headers for each row execute function fn_audit_trigger('po_id');


-- public.purchase_order_lines definition

-- Drop table

-- DROP TABLE public.purchase_order_lines;

CREATE TABLE public.purchase_order_lines (
	po_line_id serial4 NOT NULL,
	po_id int4 NOT NULL,
	line_no int4 NOT NULL,
	item_id int4 NULL,
	item_description varchar(2000) NOT NULL,
	quantity numeric(14, 4) NOT NULL,
	unit varchar(30) DEFAULT 'EA'::character varying NOT NULL,
	unit_price numeric(18, 4) NOT NULL,
	discount_rate numeric(18, 2) DEFAULT 0 NULL,
	ext_price numeric(18, 2) DEFAULT 0 NULL,
	received_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT purchase_order_lines_pkey PRIMARY KEY (po_line_id),
	CONSTRAINT purchase_order_lines_po_id_line_no_key UNIQUE (po_id, line_no)
);


-- public.purchase_request_attachments definition

-- Drop table

-- DROP TABLE public.purchase_request_attachments;

CREATE TABLE public.purchase_request_attachments (
	attachment_id serial4 NOT NULL,
	pr_id int4 NOT NULL,
	file_name varchar(255) NOT NULL,
	stored_path varchar(500) NOT NULL,
	file_size int4 NOT NULL,
	mime_type varchar(120) NULL,
	description varchar(255) NULL,
	uploaded_by int4 NULL,
	uploaded_at timestamptz DEFAULT now() NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT purchase_request_attachments_pkey PRIMARY KEY (attachment_id)
);
CREATE INDEX idx_pr_attach_pr ON public.purchase_request_attachments USING btree (pr_id) WHERE (is_deleted = false);


-- public.purchase_request_lines definition

-- Drop table

-- DROP TABLE public.purchase_request_lines;

CREATE TABLE public.purchase_request_lines (
	pr_line_id serial4 NOT NULL,
	pr_id int4 NOT NULL,
	line_no int4 NOT NULL,
	item_code varchar(50) NULL,
	item_description text NOT NULL,
	quantity numeric(18, 3) DEFAULT 1 NOT NULL,
	unit varchar(20) DEFAULT 'PCS'::character varying NOT NULL,
	est_unit_price numeric(18, 4) DEFAULT 0 NOT NULL,
	est_line_total numeric(18, 2) DEFAULT 0 NOT NULL,
	suggested_supplier_id int4 NULL,
	needed_by_date date NULL,
	remark text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT purchase_request_lines_pkey PRIMARY KEY (pr_line_id),
	CONSTRAINT purchase_request_lines_pr_id_line_no_key UNIQUE (pr_id, line_no)
);


-- public.purchase_request_quote_lines definition

-- Drop table

-- DROP TABLE public.purchase_request_quote_lines;

CREATE TABLE public.purchase_request_quote_lines (
	quote_line_id serial4 NOT NULL,
	quote_id int4 NOT NULL,
	pr_line_id int4 NOT NULL,
	unit_price numeric(18, 4) DEFAULT 0 NOT NULL,
	line_total numeric(18, 2) DEFAULT 0 NOT NULL,
	lead_time_days int4 NULL,
	is_winner bool DEFAULT false NOT NULL,
	remark varchar(255) NULL,
	CONSTRAINT purchase_request_quote_lines_pkey PRIMARY KEY (quote_line_id),
	CONSTRAINT purchase_request_quote_lines_quote_id_pr_line_id_key UNIQUE (quote_id, pr_line_id)
);
CREATE INDEX idx_prql_pr_line ON public.purchase_request_quote_lines USING btree (pr_line_id);


-- public.purchase_request_quotes definition

-- Drop table

-- DROP TABLE public.purchase_request_quotes;

CREATE TABLE public.purchase_request_quotes (
	quote_id serial4 NOT NULL,
	pr_id int4 NOT NULL,
	"position" int2 NOT NULL,
	supplier_id int4 NULL,
	supplier_name_text varchar(200) NULL,
	quote_no varchar(100) NULL,
	quote_date date NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	total_amount_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	payment_terms varchar(120) NULL,
	lead_time_days int4 NULL,
	notes text NULL,
	attachment_id int4 NULL,
	is_overall_winner bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT purchase_request_quotes_pkey PRIMARY KEY (quote_id)
);
CREATE INDEX idx_prq_pr ON public.purchase_request_quotes USING btree (pr_id) WHERE (is_deleted = false);
CREATE UNIQUE INDEX uniq_prq_pr_position_active ON public.purchase_request_quotes USING btree (pr_id, "position") WHERE (is_deleted = false);


-- public.purchase_requests definition

-- Drop table

-- DROP TABLE public.purchase_requests;

CREATE TABLE public.purchase_requests (
	pr_id serial4 NOT NULL,
	pr_no varchar(50) NOT NULL,
	requester_id int4 NOT NULL,
	department varchar(100) NULL,
	request_date date DEFAULT CURRENT_DATE NOT NULL,
	needed_by_date date NULL,
	justification text NULL,
	suggested_supplier_id int4 NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	est_total_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	status varchar(30) DEFAULT 'DRAFT'::character varying NOT NULL,
	purchasing_approved_by int4 NULL,
	purchasing_approved_at timestamptz NULL,
	purchasing_note text NULL,
	manager_approved_by int4 NULL,
	manager_approved_at timestamptz NULL,
	manager_note text NULL,
	rejected_by int4 NULL,
	rejected_at timestamptz NULL,
	rejection_reason text NULL,
	converted_po_id int4 NULL,
	converted_at timestamptz NULL,
	notes text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	project_id int4 NULL,
	ceo_approved_by int4 NULL,
	ceo_approved_at timestamptz NULL,
	ceo_note text NULL,
	CONSTRAINT purchase_requests_pkey PRIMARY KEY (pr_id),
	CONSTRAINT purchase_requests_pr_no_key UNIQUE (pr_no),
	CONSTRAINT purchase_requests_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('SUBMITTED'::character varying)::text, ('QUOTES_PENDING'::character varying)::text, ('PENDING_PURCHASING'::character varying)::text, ('PENDING_MANAGER'::character varying)::text, ('PENDING_CEO'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text, ('CONVERTED'::character varying)::text, ('CANCELLED'::character varying)::text])))
);
CREATE INDEX idx_pr_project ON public.purchase_requests USING btree (project_id) WHERE (is_deleted = false);
CREATE INDEX idx_pr_request_date ON public.purchase_requests USING btree (request_date DESC);
CREATE INDEX idx_pr_requester ON public.purchase_requests USING btree (requester_id);
CREATE INDEX idx_pr_status ON public.purchase_requests USING btree (status) WHERE (is_deleted = false);


-- public.quotation_categories definition

-- Drop table

-- DROP TABLE public.quotation_categories;

CREATE TABLE public.quotation_categories (
	category_id serial4 NOT NULL,
	category_code varchar(40) NOT NULL,
	name_jp varchar(120) NOT NULL,
	name_en varchar(120) NULL,
	name_th varchar(120) NULL,
	description text NULL,
	sort_order int4 DEFAULT 0 NOT NULL,
	is_active bool DEFAULT true NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	cost_coefficient numeric(8, 4) DEFAULT 1.0 NOT NULL,
	CONSTRAINT quotation_categories_category_code_key UNIQUE (category_code),
	CONSTRAINT quotation_categories_pkey PRIMARY KEY (category_id)
);
CREATE INDEX idx_qcat_active ON public.quotation_categories USING btree (sort_order, name_jp) WHERE ((is_active = true) AND (is_deleted = false));


-- public.quotation_headers definition

-- Drop table

-- DROP TABLE public.quotation_headers;

CREATE TABLE public.quotation_headers (
	quotation_id serial4 NOT NULL,
	quotation_no varchar(100) NOT NULL,
	revision_no int2 DEFAULT 1 NOT NULL,
	parent_quotation_id int4 NULL,
	document_template varchar(30) DEFAULT 'SYS-MN-Rev#01'::character varying NOT NULL,
	division_id int4 NOT NULL,
	customer_id int4 NOT NULL,
	attention_name varchar(100) NULL,
	attention_phone varchar(50) NULL,
	attention_email varchar(200) NULL,
	ship_to_address text NULL,
	project_name varchar(300) NULL,
	project_code varchar(50) NULL,
	rfq_reference varchar(100) NULL,
	issue_date date DEFAULT CURRENT_DATE NOT NULL,
	expiry_date date NULL,
	valid_days int4 NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	exchange_rate numeric(18, 6) DEFAULT 1 NOT NULL,
	subtotal_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	discount_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	vat_rate numeric(5, 2) DEFAULT 7.00 NOT NULL,
	vat_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	grand_total_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	payment_term_id int4 NULL,
	lead_time_text varchar(200) NULL,
	lead_time_days_min int4 NULL,
	lead_time_days_max int4 NULL,
	warranty_text varchar(200) NULL,
	warranty_months int4 NULL,
	tax_note varchar(100) DEFAULT 'Including VAT.'::character varying NULL,
	incoterms varchar(30) NULL,
	remark_text text NULL,
	note_text text NULL,
	status varchar(20) DEFAULT 'DRAFT'::character varying NOT NULL,
	won_so_id int4 NULL,
	lost_reason text NULL,
	competitor_name varchar(200) NULL,
	approved_by int4 NULL,
	approved_at timestamptz NULL,
	quoted_by int4 NULL,
	signature_url varchar(500) NULL,
	stamp_url varchar(500) NULL,
	stamp_date date NULL,
	is_deleted bool DEFAULT false NOT NULL,
	deleted_reason text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	deal_id int4 NULL,
	solution_category_id int4 NULL,
	possibility varchar(200) NULL,
	pj_no varchar(50) NULL,
	sales_person_id int4 NULL,
	customer_staff varchar(100) NULL,
	touch_point varchar(100) NULL,
	solution_name varchar(200) NULL,
	service_cost numeric(18, 2) DEFAULT 0 NULL,
	engineer_cost numeric(18, 2) DEFAULT 0 NULL,
	commission numeric(18, 2) DEFAULT 0 NULL,
	service_profit numeric(18, 2) DEFAULT 0 NULL,
	service_profit_pct numeric(5, 2) DEFAULT 0 NULL,
	gross_profit numeric(18, 2) DEFAULT 0 NULL,
	gross_profit_pct numeric(5, 2) DEFAULT 0 NULL,
	invoice_schedule varchar(200) NULL,
	income_schedule varchar(200) NULL,
	budget varchar(200) NULL,
	unique_key varchar(500) NULL,
	po_date date NULL,
	sales_name varchar(200) NULL,
	expected_invoice_date date NULL,
	expected_income_date date NULL,
	CONSTRAINT quotation_headers_pkey PRIMARY KEY (quotation_id),
	CONSTRAINT quotation_headers_quotation_no_key UNIQUE (quotation_no),
	CONSTRAINT quotation_headers_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('INTERNAL_REVIEW'::character varying)::text, ('PENDING_APPROVAL'::character varying)::text, ('AWAIT_APPROVAL'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text, ('SUBMITTED'::character varying)::text, ('NEGOTIATING'::character varying)::text, ('WON'::character varying)::text, ('LOST'::character varying)::text, ('EXPIRED'::character varying)::text, ('CANCELLED'::character varying)::text])))
);
CREATE INDEX idx_quotation_deal ON public.quotation_headers USING btree (deal_id);
CREATE INDEX idx_quotation_unique_key ON public.quotation_headers USING btree (unique_key);

-- Table Triggers

create trigger trg_audit_quotations after
insert
    or
delete
    or
update
    on
    public.quotation_headers for each row execute function fn_audit_trigger('quotation_id');


-- public.quotation_inspection_schedule definition

-- Drop table

-- DROP TABLE public.quotation_inspection_schedule;

CREATE TABLE public.quotation_inspection_schedule (
	qis_id serial4 NOT NULL,
	quotation_id int4 NOT NULL,
	seq_no int2 NOT NULL,
	description varchar(200) NULL,
	percentage numeric(5, 2) DEFAULT 0 NOT NULL,
	amount numeric(18, 2) DEFAULT 0 NOT NULL,
	expected_inspection_date date NULL,
	actual_inspection_date date NULL,
	status varchar(20) DEFAULT 'PENDING'::character varying NOT NULL,
	notes text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT quotation_inspection_schedule_pkey PRIMARY KEY (qis_id),
	CONSTRAINT quotation_inspection_schedule_quotation_id_seq_no_key UNIQUE (quotation_id, seq_no),
	CONSTRAINT quotation_inspection_schedule_status_check CHECK (((status)::text = ANY (ARRAY[('PENDING'::character varying)::text, ('IN_PROGRESS'::character varying)::text, ('DELIVERED'::character varying)::text, ('INSPECTED'::character varying)::text, ('CANCELLED'::character varying)::text])))
);
CREATE INDEX idx_qis_inspection_date ON public.quotation_inspection_schedule USING btree (expected_inspection_date);
CREATE INDEX idx_qis_quotation ON public.quotation_inspection_schedule USING btree (quotation_id);


-- public.quotation_lines definition

-- Drop table

-- DROP TABLE public.quotation_lines;

CREATE TABLE public.quotation_lines (
	quot_line_id serial4 NOT NULL,
	quotation_id int4 NOT NULL,
	line_no varchar(10) NOT NULL,
	parent_line_no varchar(10) NULL,
	is_category_row bool DEFAULT false NOT NULL,
	item_id int4 NULL,
	item_description varchar(500) NOT NULL,
	item_description_jp varchar(500) NULL,
	item_description_th varchar(500) NULL,
	item_note text NULL,
	quantity numeric(14, 4) NULL,
	unit varchar(30) NULL,
	unit_price numeric(18, 4) NULL,
	discount_rate numeric(5, 2) DEFAULT 0 NULL,
	ext_price numeric(18, 2) DEFAULT 0 NULL,
	cost_material numeric(18, 2) DEFAULT 0 NULL,
	cost_labor numeric(18, 2) DEFAULT 0 NULL,
	cost_outsource numeric(18, 2) DEFAULT 0 NULL,
	cost_overhead_rate numeric(5, 4) DEFAULT 0 NULL,
	cost_total numeric(18, 2) DEFAULT 0 NULL,
	delivery_days int4 NULL,
	sort_order int4 DEFAULT 0 NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT quotation_lines_pkey PRIMARY KEY (quot_line_id),
	CONSTRAINT quotation_lines_quotation_id_line_no_key UNIQUE (quotation_id, line_no)
);


-- public.sales_kpi_monthly_pct definition

-- Drop table

-- DROP TABLE public.sales_kpi_monthly_pct;

CREATE TABLE public.sales_kpi_monthly_pct (
	kpi_id int4 NOT NULL,
	month_no int4 NOT NULL,
	pct numeric(6, 3) DEFAULT 8.333 NOT NULL,
	CONSTRAINT sales_kpi_monthly_pct_month_no_check CHECK (((month_no >= 1) AND (month_no <= 12))),
	CONSTRAINT sales_kpi_monthly_pct_pkey PRIMARY KEY (kpi_id, month_no)
);


-- public.sales_kpi_targets definition

-- Drop table

-- DROP TABLE public.sales_kpi_targets;

CREATE TABLE public.sales_kpi_targets (
	kpi_id serial4 NOT NULL,
	employee_id int4 NOT NULL,
	fiscal_year int4 NOT NULL,
	annual_contact_target int4 DEFAULT 0 NOT NULL,
	annual_meeting_target int4 DEFAULT 0 NOT NULL,
	annual_profit_target numeric(18, 2) DEFAULT 0 NOT NULL,
	notes text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	profit_per_order numeric(18, 2) DEFAULT 100000 NOT NULL,
	close_rate_pct numeric(6, 3) DEFAULT 5.0 NOT NULL,
	appt_rate_pct numeric(6, 3) DEFAULT 10.0 NOT NULL,
	annual_order_target int4 DEFAULT 0 NOT NULL,
	CONSTRAINT sales_kpi_targets_employee_id_fiscal_year_key UNIQUE (employee_id, fiscal_year),
	CONSTRAINT sales_kpi_targets_pkey PRIMARY KEY (kpi_id)
);
CREATE INDEX idx_kpi_emp_year ON public.sales_kpi_targets USING btree (employee_id, fiscal_year);


-- public.sales_order_headers definition

-- Drop table

-- DROP TABLE public.sales_order_headers;

CREATE TABLE public.sales_order_headers (
	so_id serial4 NOT NULL,
	so_no varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	customer_id int4 NOT NULL,
	quotation_id int4 NULL,
	order_date date DEFAULT CURRENT_DATE NOT NULL,
	requested_date date NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	exchange_rate numeric(18, 6) DEFAULT 1 NOT NULL,
	subtotal_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	discount_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	vat_rate numeric(5, 2) DEFAULT 7.00 NOT NULL,
	vat_amount numeric(18, 2) DEFAULT 0 NOT NULL,
	grand_total_thb numeric(18, 2) DEFAULT 0 NOT NULL,
	payment_term_id int4 NULL,
	status varchar(20) DEFAULT 'DRAFT'::character varying NOT NULL,
	approved_by int4 NULL,
	approved_at timestamptz NULL,
	sales_rep_id int4 NULL,
	notes text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	deal_id int4 NULL,
	CONSTRAINT sales_order_headers_pkey PRIMARY KEY (so_id),
	CONSTRAINT sales_order_headers_so_no_key UNIQUE (so_no),
	CONSTRAINT sales_order_headers_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('CONFIRMED'::character varying)::text, ('IN_PRODUCTION'::character varying)::text, ('PARTIAL_SHIPPED'::character varying)::text, ('SHIPPED'::character varying)::text, ('INVOICED'::character varying)::text, ('CLOSED'::character varying)::text, ('CANCELLED'::character varying)::text])))
);
CREATE INDEX idx_so_deal ON public.sales_order_headers USING btree (deal_id);

-- Table Triggers

create trigger trg_audit_so after
insert
    or
delete
    or
update
    on
    public.sales_order_headers for each row execute function fn_audit_trigger('so_id');


-- public.sales_order_lines definition

-- Drop table

-- DROP TABLE public.sales_order_lines;

CREATE TABLE public.sales_order_lines (
	so_line_id serial4 NOT NULL,
	so_id int4 NOT NULL,
	line_no int2 NOT NULL,
	item_id int4 NULL,
	item_description varchar(500) NOT NULL,
	quantity numeric(14, 4) NOT NULL,
	unit varchar(30) DEFAULT 'EA'::character varying NOT NULL,
	unit_price numeric(18, 4) NOT NULL,
	discount_rate numeric(5, 2) DEFAULT 0 NULL,
	ext_price numeric(18, 2) DEFAULT 0 NULL,
	delivered_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	invoiced_qty numeric(14, 4) DEFAULT 0 NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT sales_order_lines_pkey PRIMARY KEY (so_line_id),
	CONSTRAINT sales_order_lines_so_id_line_no_key UNIQUE (so_id, line_no)
);


-- public.shipment_headers definition

-- Drop table

-- DROP TABLE public.shipment_headers;

CREATE TABLE public.shipment_headers (
	shipment_id serial4 NOT NULL,
	shipment_no varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	so_id int4 NULL,
	customer_id int4 NOT NULL,
	warehouse_id int4 NOT NULL,
	shipment_date date DEFAULT CURRENT_DATE NOT NULL,
	status varchar(20) DEFAULT 'DRAFT'::character varying NOT NULL,
	tracking_no varchar(100) NULL,
	carrier varchar(100) NULL,
	notes text NULL,
	is_deleted bool DEFAULT false NOT NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT shipment_headers_pkey PRIMARY KEY (shipment_id),
	CONSTRAINT shipment_headers_shipment_no_key UNIQUE (shipment_no),
	CONSTRAINT shipment_headers_status_check CHECK (((status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('PICKING'::character varying)::text, ('PACKED'::character varying)::text, ('SHIPPED'::character varying)::text, ('DELIVERED'::character varying)::text, ('CANCELLED'::character varying)::text])))
);


-- public.shipment_lines definition

-- Drop table

-- DROP TABLE public.shipment_lines;

CREATE TABLE public.shipment_lines (
	shipment_line_id serial4 NOT NULL,
	shipment_id int4 NOT NULL,
	so_line_id int4 NULL,
	item_id int4 NOT NULL,
	quantity numeric(14, 4) NOT NULL,
	unit varchar(20) DEFAULT 'EA'::character varying NOT NULL,
	lot_no varchar(50) NULL,
	serial_no varchar(50) NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT shipment_lines_pkey PRIMARY KEY (shipment_line_id)
);


-- public.supplier_attachments definition

-- Drop table

-- DROP TABLE public.supplier_attachments;

CREATE TABLE public.supplier_attachments (
	attachment_id serial4 NOT NULL,
	supplier_id int4 NOT NULL,
	doc_type varchar(40) DEFAULT 'OTHER'::character varying NOT NULL,
	file_name varchar(255) NOT NULL,
	stored_path varchar(500) NOT NULL,
	file_size int4 NOT NULL,
	mime_type varchar(120) NULL,
	description varchar(255) NULL,
	uploaded_by int4 NULL,
	uploaded_at timestamptz DEFAULT now() NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	CONSTRAINT supplier_attachments_pkey PRIMARY KEY (attachment_id)
);
CREATE INDEX idx_sup_attach_sup ON public.supplier_attachments USING btree (supplier_id) WHERE (is_deleted = false);


-- public.suppliers definition

-- Drop table

-- DROP TABLE public.suppliers;

CREATE TABLE public.suppliers (
	supplier_id serial4 NOT NULL,
	supplier_code varchar(30) NOT NULL,
	division_id int4 NOT NULL,
	supplier_name varchar(200) NOT NULL,
	supplier_name_jp varchar(200) NULL,
	supplier_name_th varchar(200) NULL,
	country bpchar(2) DEFAULT 'TH'::bpchar NOT NULL,
	address text NULL,
	tax_id varchar(20) NULL,
	contact_person varchar(100) NULL,
	email varchar(200) NULL,
	phone varchar(50) NULL,
	currency_code bpchar(3) DEFAULT 'THB'::bpchar NOT NULL,
	payment_terms int4 DEFAULT 30 NOT NULL,
	wht_rate numeric(5, 2) DEFAULT 3.00 NULL,
	effective_from date DEFAULT CURRENT_DATE NOT NULL,
	effective_to date NULL,
	version_no int4 DEFAULT 1 NOT NULL,
	is_current bool DEFAULT true NOT NULL,
	is_deleted bool DEFAULT false NOT NULL,
	deleted_reason text NULL,
	created_by int4 NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_by int4 NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	approval_status varchar(20) DEFAULT 'APPROVED'::character varying NULL,
	approved_by int4 NULL,
	approved_at timestamptz NULL,
	submitted_by int4 NULL,
	submitted_at timestamptz NULL,
	manager_approved_by int4 NULL,
	manager_approved_at timestamptz NULL,
	ceo_approved_by int4 NULL,
	ceo_approved_at timestamptz NULL,
	rejected_by int4 NULL,
	rejected_at timestamptz NULL,
	rejection_reason text NULL,
	CONSTRAINT suppliers_approval_status_check CHECK (((approval_status)::text = ANY (ARRAY[('DRAFT'::character varying)::text, ('PENDING'::character varying)::text, ('PENDING_MANAGER'::character varying)::text, ('PENDING_CEO'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text]))),
	CONSTRAINT suppliers_pkey PRIMARY KEY (supplier_id)
);
CREATE UNIQUE INDEX uniq_supplier_code_active ON public.suppliers USING btree (supplier_code) WHERE ((is_deleted = false) AND (is_current = true));

-- Table Triggers

create trigger trg_audit_suppliers after
insert
    or
delete
    or
update
    on
    public.suppliers for each row execute function fn_audit_trigger('supplier_id');


-- public.users definition

-- Drop table

-- DROP TABLE public.users;

CREATE TABLE public.users (
	user_id serial4 NOT NULL,
	username varchar(50) NOT NULL,
	password_hash varchar(255) NOT NULL,
	email varchar(200) NULL,
	"role" varchar(50) DEFAULT 'STAFF'::character varying NOT NULL,
	employee_id int4 NULL,
	is_active bool DEFAULT true NOT NULL,
	last_login timestamptz NULL,
	created_at timestamptz DEFAULT now() NOT NULL,
	updated_at timestamptz DEFAULT now() NOT NULL,
	CONSTRAINT users_email_key UNIQUE (email),
	CONSTRAINT users_pkey PRIMARY KEY (user_id),
	CONSTRAINT users_role_check CHECK (((role)::text = ANY (ARRAY[('ADMIN'::character varying)::text, ('MANAGER'::character varying)::text, ('ACCOUNTING'::character varying)::text, ('HR'::character varying)::text, ('SALES'::character varying)::text, ('PURCHASE'::character varying)::text, ('PRODUCTION'::character varying)::text, ('QA'::character varying)::text, ('STAFF'::character varying)::text]))),
	CONSTRAINT users_username_key UNIQUE (username)
);


-- public.ap_invoice_lines foreign keys

ALTER TABLE public.ap_invoice_lines ADD CONSTRAINT ap_invoice_lines_ap_invoice_id_fkey FOREIGN KEY (ap_invoice_id) REFERENCES public.ap_invoices(ap_invoice_id) ON DELETE CASCADE;
ALTER TABLE public.ap_invoice_lines ADD CONSTRAINT ap_invoice_lines_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id);


-- public.ap_invoices foreign keys

ALTER TABLE public.ap_invoices ADD CONSTRAINT ap_invoices_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.ap_invoices ADD CONSTRAINT ap_invoices_po_id_fkey FOREIGN KEY (po_id) REFERENCES public.purchase_order_headers(po_id);
ALTER TABLE public.ap_invoices ADD CONSTRAINT ap_invoices_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(supplier_id);


-- public.ap_payment_allocations foreign keys

ALTER TABLE public.ap_payment_allocations ADD CONSTRAINT ap_payment_allocations_ap_invoice_id_fkey FOREIGN KEY (ap_invoice_id) REFERENCES public.ap_invoices(ap_invoice_id);
ALTER TABLE public.ap_payment_allocations ADD CONSTRAINT ap_payment_allocations_payment_id_fkey FOREIGN KEY (payment_id) REFERENCES public.ap_payments(payment_id);


-- public.ap_payments foreign keys

ALTER TABLE public.ap_payments ADD CONSTRAINT ap_payments_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.ap_payments ADD CONSTRAINT ap_payments_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(supplier_id);


-- public.ar_invoice_lines foreign keys

ALTER TABLE public.ar_invoice_lines ADD CONSTRAINT ar_invoice_lines_invoice_id_fkey FOREIGN KEY (invoice_id) REFERENCES public.ar_invoices(invoice_id) ON DELETE CASCADE;
ALTER TABLE public.ar_invoice_lines ADD CONSTRAINT ar_invoice_lines_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id);


-- public.ar_invoices foreign keys

ALTER TABLE public.ar_invoices ADD CONSTRAINT ar_invoices_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id);
ALTER TABLE public.ar_invoices ADD CONSTRAINT ar_invoices_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.ar_invoices ADD CONSTRAINT ar_invoices_our_quotation_id_fkey FOREIGN KEY (our_quotation_id) REFERENCES public.quotation_headers(quotation_id);
ALTER TABLE public.ar_invoices ADD CONSTRAINT ar_invoices_payment_term_id_fkey FOREIGN KEY (payment_term_id) REFERENCES public.payment_terms(term_id);
ALTER TABLE public.ar_invoices ADD CONSTRAINT ar_invoices_salesperson_id_fkey FOREIGN KEY (salesperson_id) REFERENCES public.employees(employee_id);
ALTER TABLE public.ar_invoices ADD CONSTRAINT ar_invoices_shipment_id_fkey FOREIGN KEY (shipment_id) REFERENCES public.shipment_headers(shipment_id);
ALTER TABLE public.ar_invoices ADD CONSTRAINT ar_invoices_so_id_fkey FOREIGN KEY (so_id) REFERENCES public.sales_order_headers(so_id);


-- public.ar_payment_allocations foreign keys

ALTER TABLE public.ar_payment_allocations ADD CONSTRAINT ar_payment_allocations_invoice_id_fkey FOREIGN KEY (invoice_id) REFERENCES public.ar_invoices(invoice_id);
ALTER TABLE public.ar_payment_allocations ADD CONSTRAINT ar_payment_allocations_payment_id_fkey FOREIGN KEY (payment_id) REFERENCES public.ar_payments(payment_id);


-- public.ar_payments foreign keys

ALTER TABLE public.ar_payments ADD CONSTRAINT ar_payments_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id);
ALTER TABLE public.ar_payments ADD CONSTRAINT ar_payments_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);


-- public.attendance_records foreign keys

ALTER TABLE public.attendance_records ADD CONSTRAINT attendance_records_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.employees(employee_id);
ALTER TABLE public.attendance_records ADD CONSTRAINT attendance_records_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employees(employee_id);


-- public.business_cards foreign keys

ALTER TABLE public.business_cards ADD CONSTRAINT business_cards_contact_id_fkey FOREIGN KEY (contact_id) REFERENCES public.customer_contacts(contact_id) ON DELETE SET NULL;
ALTER TABLE public.business_cards ADD CONSTRAINT business_cards_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id) ON DELETE SET NULL;


-- public.cost_sheets foreign keys

ALTER TABLE public.cost_sheets ADD CONSTRAINT cost_sheets_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id);
ALTER TABLE public.cost_sheets ADD CONSTRAINT cost_sheets_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(project_id);
ALTER TABLE public.cost_sheets ADD CONSTRAINT cost_sheets_quotation_id_fkey FOREIGN KEY (quotation_id) REFERENCES public.quotation_headers(quotation_id);


-- public.customer_contacts foreign keys

ALTER TABLE public.customer_contacts ADD CONSTRAINT customer_contacts_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id) ON DELETE CASCADE;


-- public.customers foreign keys

ALTER TABLE public.customers ADD CONSTRAINT customers_ceo_approved_by_fkey FOREIGN KEY (ceo_approved_by) REFERENCES public.users(user_id);
ALTER TABLE public.customers ADD CONSTRAINT customers_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.customers ADD CONSTRAINT customers_manager_approved_by_fkey FOREIGN KEY (manager_approved_by) REFERENCES public.users(user_id);
ALTER TABLE public.customers ADD CONSTRAINT customers_rejected_by_fkey FOREIGN KEY (rejected_by) REFERENCES public.users(user_id);
ALTER TABLE public.customers ADD CONSTRAINT customers_sales_rep_id_fkey FOREIGN KEY (sales_rep_id) REFERENCES public.employees(employee_id);
ALTER TABLE public.customers ADD CONSTRAINT customers_submitted_by_fkey FOREIGN KEY (submitted_by) REFERENCES public.users(user_id);


-- public.deal_activities foreign keys

ALTER TABLE public.deal_activities ADD CONSTRAINT deal_activities_activity_category_id_fkey FOREIGN KEY (activity_category_id) REFERENCES public.activity_categories(category_id);
ALTER TABLE public.deal_activities ADD CONSTRAINT deal_activities_contact_id_fkey FOREIGN KEY (contact_id) REFERENCES public.customer_contacts(contact_id) ON DELETE SET NULL;
ALTER TABLE public.deal_activities ADD CONSTRAINT deal_activities_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id);
ALTER TABLE public.deal_activities ADD CONSTRAINT deal_activities_deal_id_fkey FOREIGN KEY (deal_id) REFERENCES public.deals(deal_id);
ALTER TABLE public.deal_activities ADD CONSTRAINT deal_activities_parent_activity_id_fkey FOREIGN KEY (parent_activity_id) REFERENCES public.deal_activities(activity_id) ON DELETE SET NULL;
ALTER TABLE public.deal_activities ADD CONSTRAINT deal_activities_sales_person_id_fkey FOREIGN KEY (sales_person_id) REFERENCES public.employees(employee_id);


-- public.deals foreign keys

ALTER TABLE public.deals ADD CONSTRAINT deals_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id);
ALTER TABLE public.deals ADD CONSTRAINT deals_sales_person_id_fkey FOREIGN KEY (sales_person_id) REFERENCES public.employees(employee_id);
ALTER TABLE public.deals ADD CONSTRAINT deals_solution_category_id_fkey FOREIGN KEY (solution_category_id) REFERENCES public.solution_categories(category_id);
ALTER TABLE public.deals ADD CONSTRAINT deals_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.deal_statuses(status_id);


-- public.departments foreign keys

ALTER TABLE public.departments ADD CONSTRAINT departments_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.departments ADD CONSTRAINT fk_dept_manager FOREIGN KEY (manager_id) REFERENCES public.employees(employee_id);


-- public.employees foreign keys

ALTER TABLE public.employees ADD CONSTRAINT employees_department_id_fkey FOREIGN KEY (department_id) REFERENCES public.departments(department_id);
ALTER TABLE public.employees ADD CONSTRAINT employees_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);


-- public.expense_claim_lines foreign keys

ALTER TABLE public.expense_claim_lines ADD CONSTRAINT expense_claim_lines_claim_id_fkey FOREIGN KEY (claim_id) REFERENCES public.expense_claims(claim_id) ON DELETE CASCADE;


-- public.expense_claims foreign keys

ALTER TABLE public.expense_claims ADD CONSTRAINT expense_claims_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.employees(employee_id);
ALTER TABLE public.expense_claims ADD CONSTRAINT expense_claims_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.expense_claims ADD CONSTRAINT expense_claims_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employees(employee_id);


-- public.journal_entries foreign keys

ALTER TABLE public.journal_entries ADD CONSTRAINT journal_entries_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.journal_entries ADD CONSTRAINT journal_entries_posted_by_fkey FOREIGN KEY (posted_by) REFERENCES public.employees(employee_id);


-- public.journal_lines foreign keys

ALTER TABLE public.journal_lines ADD CONSTRAINT journal_lines_je_id_fkey FOREIGN KEY (je_id) REFERENCES public.journal_entries(je_id) ON DELETE CASCADE;


-- public.leave_requests foreign keys

ALTER TABLE public.leave_requests ADD CONSTRAINT leave_requests_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.employees(employee_id);
ALTER TABLE public.leave_requests ADD CONSTRAINT leave_requests_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employees(employee_id);


-- public.mo_headers foreign keys

ALTER TABLE public.mo_headers ADD CONSTRAINT mo_headers_bom_id_fkey FOREIGN KEY (bom_id) REFERENCES public.bom_headers(bom_id);
ALTER TABLE public.mo_headers ADD CONSTRAINT mo_headers_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.mo_headers ADD CONSTRAINT mo_headers_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id);
ALTER TABLE public.mo_headers ADD CONSTRAINT mo_headers_so_id_fkey FOREIGN KEY (so_id) REFERENCES public.sales_order_headers(so_id);


-- public.mo_lines foreign keys

ALTER TABLE public.mo_lines ADD CONSTRAINT mo_lines_component_item_id_fkey FOREIGN KEY (component_item_id) REFERENCES public.items(item_id);
ALTER TABLE public.mo_lines ADD CONSTRAINT mo_lines_mo_id_fkey FOREIGN KEY (mo_id) REFERENCES public.mo_headers(mo_id) ON DELETE CASCADE;


-- public.mrp_purchase_recommendations foreign keys

ALTER TABLE public.mrp_purchase_recommendations ADD CONSTRAINT mrp_purchase_recommendations_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id);
ALTER TABLE public.mrp_purchase_recommendations ADD CONSTRAINT mrp_purchase_recommendations_po_id_fkey FOREIGN KEY (po_id) REFERENCES public.purchase_order_headers(po_id);
ALTER TABLE public.mrp_purchase_recommendations ADD CONSTRAINT mrp_purchase_recommendations_snapshot_id_fkey FOREIGN KEY (snapshot_id) REFERENCES public.mrp_snapshots(snapshot_id);
ALTER TABLE public.mrp_purchase_recommendations ADD CONSTRAINT mrp_purchase_recommendations_source_mo_id_fkey FOREIGN KEY (source_mo_id) REFERENCES public.mo_headers(mo_id);
ALTER TABLE public.mrp_purchase_recommendations ADD CONSTRAINT mrp_purchase_recommendations_source_so_id_fkey FOREIGN KEY (source_so_id) REFERENCES public.sales_order_headers(so_id);
ALTER TABLE public.mrp_purchase_recommendations ADD CONSTRAINT mrp_purchase_recommendations_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(supplier_id);


-- public.project_cost_items foreign keys

ALTER TABLE public.project_cost_items ADD CONSTRAINT project_cost_items_cost_sheet_id_fkey FOREIGN KEY (cost_sheet_id) REFERENCES public.cost_sheets(cost_sheet_id);
ALTER TABLE public.project_cost_items ADD CONSTRAINT project_cost_items_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(project_id);
ALTER TABLE public.project_cost_items ADD CONSTRAINT project_cost_items_quotation_id_fkey FOREIGN KEY (quotation_id) REFERENCES public.quotation_headers(quotation_id);


-- public.project_invoices foreign keys

ALTER TABLE public.project_invoices ADD CONSTRAINT project_invoices_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(project_id) ON DELETE CASCADE;


-- public.project_payment_schedules foreign keys

ALTER TABLE public.project_payment_schedules ADD CONSTRAINT project_payment_schedules_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(project_id) ON DELETE CASCADE;


-- public.project_progress foreign keys

ALTER TABLE public.project_progress ADD CONSTRAINT project_progress_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(project_id) ON DELETE CASCADE;


-- public.project_purchases foreign keys

ALTER TABLE public.project_purchases ADD CONSTRAINT project_purchases_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(project_id) ON DELETE CASCADE;


-- public.projects foreign keys

ALTER TABLE public.projects ADD CONSTRAINT projects_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id);
ALTER TABLE public.projects ADD CONSTRAINT projects_deal_id_fkey FOREIGN KEY (deal_id) REFERENCES public.deals(deal_id);
ALTER TABLE public.projects ADD CONSTRAINT projects_payment_term_id_fkey FOREIGN KEY (payment_term_id) REFERENCES public.payment_terms(term_id);
ALTER TABLE public.projects ADD CONSTRAINT projects_sales_person_id_fkey FOREIGN KEY (sales_person_id) REFERENCES public.employees(employee_id);
ALTER TABLE public.projects ADD CONSTRAINT projects_so_id_fkey FOREIGN KEY (so_id) REFERENCES public.sales_order_headers(so_id);


-- public.purchase_order_headers foreign keys

ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.employees(employee_id);
ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_ceo_approved_by_fkey FOREIGN KEY (ceo_approved_by) REFERENCES public.users(user_id);
ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_contact_person_id_fkey FOREIGN KEY (contact_person_id) REFERENCES public.employees(employee_id);
ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_manager_approved_by_fkey FOREIGN KEY (manager_approved_by) REFERENCES public.users(user_id);
ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_our_quotation_id_fkey FOREIGN KEY (our_quotation_id) REFERENCES public.quotation_headers(quotation_id);
ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_payment_term_id_fkey FOREIGN KEY (payment_term_id) REFERENCES public.payment_terms(term_id);
ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_pr_id_fkey FOREIGN KEY (pr_id) REFERENCES public.purchase_requests(pr_id);
ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(project_id);
ALTER TABLE public.purchase_order_headers ADD CONSTRAINT purchase_order_headers_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(supplier_id);


-- public.purchase_order_lines foreign keys

ALTER TABLE public.purchase_order_lines ADD CONSTRAINT purchase_order_lines_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id);
ALTER TABLE public.purchase_order_lines ADD CONSTRAINT purchase_order_lines_po_id_fkey FOREIGN KEY (po_id) REFERENCES public.purchase_order_headers(po_id) ON DELETE CASCADE;


-- public.purchase_request_attachments foreign keys

ALTER TABLE public.purchase_request_attachments ADD CONSTRAINT purchase_request_attachments_pr_id_fkey FOREIGN KEY (pr_id) REFERENCES public.purchase_requests(pr_id) ON DELETE CASCADE;
ALTER TABLE public.purchase_request_attachments ADD CONSTRAINT purchase_request_attachments_uploaded_by_fkey FOREIGN KEY (uploaded_by) REFERENCES public.users(user_id);


-- public.purchase_request_lines foreign keys

ALTER TABLE public.purchase_request_lines ADD CONSTRAINT purchase_request_lines_pr_id_fkey FOREIGN KEY (pr_id) REFERENCES public.purchase_requests(pr_id) ON DELETE CASCADE;
ALTER TABLE public.purchase_request_lines ADD CONSTRAINT purchase_request_lines_suggested_supplier_id_fkey FOREIGN KEY (suggested_supplier_id) REFERENCES public.suppliers(supplier_id);


-- public.purchase_request_quote_lines foreign keys

ALTER TABLE public.purchase_request_quote_lines ADD CONSTRAINT purchase_request_quote_lines_pr_line_id_fkey FOREIGN KEY (pr_line_id) REFERENCES public.purchase_request_lines(pr_line_id) ON DELETE CASCADE;
ALTER TABLE public.purchase_request_quote_lines ADD CONSTRAINT purchase_request_quote_lines_quote_id_fkey FOREIGN KEY (quote_id) REFERENCES public.purchase_request_quotes(quote_id) ON DELETE CASCADE;


-- public.purchase_request_quotes foreign keys

ALTER TABLE public.purchase_request_quotes ADD CONSTRAINT purchase_request_quotes_attachment_id_fkey FOREIGN KEY (attachment_id) REFERENCES public.purchase_request_attachments(attachment_id);
ALTER TABLE public.purchase_request_quotes ADD CONSTRAINT purchase_request_quotes_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(user_id);
ALTER TABLE public.purchase_request_quotes ADD CONSTRAINT purchase_request_quotes_pr_id_fkey FOREIGN KEY (pr_id) REFERENCES public.purchase_requests(pr_id) ON DELETE CASCADE;
ALTER TABLE public.purchase_request_quotes ADD CONSTRAINT purchase_request_quotes_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(supplier_id);


-- public.purchase_requests foreign keys

ALTER TABLE public.purchase_requests ADD CONSTRAINT purchase_requests_ceo_approved_by_fkey FOREIGN KEY (ceo_approved_by) REFERENCES public.users(user_id);
ALTER TABLE public.purchase_requests ADD CONSTRAINT purchase_requests_converted_po_id_fkey FOREIGN KEY (converted_po_id) REFERENCES public.purchase_order_headers(po_id);
ALTER TABLE public.purchase_requests ADD CONSTRAINT purchase_requests_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(user_id);
ALTER TABLE public.purchase_requests ADD CONSTRAINT purchase_requests_manager_approved_by_fkey FOREIGN KEY (manager_approved_by) REFERENCES public.users(user_id);
ALTER TABLE public.purchase_requests ADD CONSTRAINT purchase_requests_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(project_id);
ALTER TABLE public.purchase_requests ADD CONSTRAINT purchase_requests_purchasing_approved_by_fkey FOREIGN KEY (purchasing_approved_by) REFERENCES public.users(user_id);
ALTER TABLE public.purchase_requests ADD CONSTRAINT purchase_requests_rejected_by_fkey FOREIGN KEY (rejected_by) REFERENCES public.users(user_id);
ALTER TABLE public.purchase_requests ADD CONSTRAINT purchase_requests_requester_id_fkey FOREIGN KEY (requester_id) REFERENCES public.employees(employee_id);
ALTER TABLE public.purchase_requests ADD CONSTRAINT purchase_requests_suggested_supplier_id_fkey FOREIGN KEY (suggested_supplier_id) REFERENCES public.suppliers(supplier_id);


-- public.quotation_categories foreign keys

ALTER TABLE public.quotation_categories ADD CONSTRAINT quotation_categories_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(user_id);


-- public.quotation_headers foreign keys

ALTER TABLE public.quotation_headers ADD CONSTRAINT quotation_headers_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.employees(employee_id);
ALTER TABLE public.quotation_headers ADD CONSTRAINT quotation_headers_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id);
ALTER TABLE public.quotation_headers ADD CONSTRAINT quotation_headers_deal_id_fkey FOREIGN KEY (deal_id) REFERENCES public.deals(deal_id);
ALTER TABLE public.quotation_headers ADD CONSTRAINT quotation_headers_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.quotation_headers ADD CONSTRAINT quotation_headers_parent_quotation_id_fkey FOREIGN KEY (parent_quotation_id) REFERENCES public.quotation_headers(quotation_id);
ALTER TABLE public.quotation_headers ADD CONSTRAINT quotation_headers_payment_term_id_fkey FOREIGN KEY (payment_term_id) REFERENCES public.payment_terms(term_id);
ALTER TABLE public.quotation_headers ADD CONSTRAINT quotation_headers_quoted_by_fkey FOREIGN KEY (quoted_by) REFERENCES public.employees(employee_id);
ALTER TABLE public.quotation_headers ADD CONSTRAINT quotation_headers_sales_person_id_fkey FOREIGN KEY (sales_person_id) REFERENCES public.employees(employee_id);
ALTER TABLE public.quotation_headers ADD CONSTRAINT quotation_headers_solution_category_id_fkey FOREIGN KEY (solution_category_id) REFERENCES public.solution_categories(category_id);


-- public.quotation_inspection_schedule foreign keys

ALTER TABLE public.quotation_inspection_schedule ADD CONSTRAINT quotation_inspection_schedule_quotation_id_fkey FOREIGN KEY (quotation_id) REFERENCES public.quotation_headers(quotation_id) ON DELETE CASCADE;


-- public.quotation_lines foreign keys

ALTER TABLE public.quotation_lines ADD CONSTRAINT quotation_lines_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id);
ALTER TABLE public.quotation_lines ADD CONSTRAINT quotation_lines_quotation_id_fkey FOREIGN KEY (quotation_id) REFERENCES public.quotation_headers(quotation_id) ON DELETE CASCADE;


-- public.sales_kpi_monthly_pct foreign keys

ALTER TABLE public.sales_kpi_monthly_pct ADD CONSTRAINT sales_kpi_monthly_pct_kpi_id_fkey FOREIGN KEY (kpi_id) REFERENCES public.sales_kpi_targets(kpi_id) ON DELETE CASCADE;


-- public.sales_kpi_targets foreign keys

ALTER TABLE public.sales_kpi_targets ADD CONSTRAINT sales_kpi_targets_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employees(employee_id) ON DELETE CASCADE;


-- public.sales_order_headers foreign keys

ALTER TABLE public.sales_order_headers ADD CONSTRAINT sales_order_headers_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.employees(employee_id);
ALTER TABLE public.sales_order_headers ADD CONSTRAINT sales_order_headers_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id);
ALTER TABLE public.sales_order_headers ADD CONSTRAINT sales_order_headers_deal_id_fkey FOREIGN KEY (deal_id) REFERENCES public.deals(deal_id);
ALTER TABLE public.sales_order_headers ADD CONSTRAINT sales_order_headers_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.sales_order_headers ADD CONSTRAINT sales_order_headers_payment_term_id_fkey FOREIGN KEY (payment_term_id) REFERENCES public.payment_terms(term_id);
ALTER TABLE public.sales_order_headers ADD CONSTRAINT sales_order_headers_quotation_id_fkey FOREIGN KEY (quotation_id) REFERENCES public.quotation_headers(quotation_id);
ALTER TABLE public.sales_order_headers ADD CONSTRAINT sales_order_headers_sales_rep_id_fkey FOREIGN KEY (sales_rep_id) REFERENCES public.employees(employee_id);


-- public.sales_order_lines foreign keys

ALTER TABLE public.sales_order_lines ADD CONSTRAINT sales_order_lines_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id);
ALTER TABLE public.sales_order_lines ADD CONSTRAINT sales_order_lines_so_id_fkey FOREIGN KEY (so_id) REFERENCES public.sales_order_headers(so_id) ON DELETE CASCADE;


-- public.shipment_headers foreign keys

ALTER TABLE public.shipment_headers ADD CONSTRAINT shipment_headers_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(customer_id);
ALTER TABLE public.shipment_headers ADD CONSTRAINT shipment_headers_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.shipment_headers ADD CONSTRAINT shipment_headers_so_id_fkey FOREIGN KEY (so_id) REFERENCES public.sales_order_headers(so_id);
ALTER TABLE public.shipment_headers ADD CONSTRAINT shipment_headers_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES public.warehouses(warehouse_id);


-- public.shipment_lines foreign keys

ALTER TABLE public.shipment_lines ADD CONSTRAINT shipment_lines_item_id_fkey FOREIGN KEY (item_id) REFERENCES public.items(item_id);
ALTER TABLE public.shipment_lines ADD CONSTRAINT shipment_lines_shipment_id_fkey FOREIGN KEY (shipment_id) REFERENCES public.shipment_headers(shipment_id) ON DELETE CASCADE;
ALTER TABLE public.shipment_lines ADD CONSTRAINT shipment_lines_so_line_id_fkey FOREIGN KEY (so_line_id) REFERENCES public.sales_order_lines(so_line_id);


-- public.supplier_attachments foreign keys

ALTER TABLE public.supplier_attachments ADD CONSTRAINT supplier_attachments_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(supplier_id) ON DELETE CASCADE;
ALTER TABLE public.supplier_attachments ADD CONSTRAINT supplier_attachments_uploaded_by_fkey FOREIGN KEY (uploaded_by) REFERENCES public.users(user_id);


-- public.suppliers foreign keys

ALTER TABLE public.suppliers ADD CONSTRAINT suppliers_ceo_approved_by_fkey FOREIGN KEY (ceo_approved_by) REFERENCES public.users(user_id);
ALTER TABLE public.suppliers ADD CONSTRAINT suppliers_division_id_fkey FOREIGN KEY (division_id) REFERENCES public.divisions(division_id);
ALTER TABLE public.suppliers ADD CONSTRAINT suppliers_manager_approved_by_fkey FOREIGN KEY (manager_approved_by) REFERENCES public.users(user_id);
ALTER TABLE public.suppliers ADD CONSTRAINT suppliers_rejected_by_fkey FOREIGN KEY (rejected_by) REFERENCES public.users(user_id);
ALTER TABLE public.suppliers ADD CONSTRAINT suppliers_submitted_by_fkey FOREIGN KEY (submitted_by) REFERENCES public.users(user_id);


-- public.users foreign keys

ALTER TABLE public.users ADD CONSTRAINT fk_users_employee FOREIGN KEY (employee_id) REFERENCES public.employees(employee_id);-- public.v_accounts_current source

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