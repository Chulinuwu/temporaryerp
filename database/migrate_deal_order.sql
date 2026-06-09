-- ============================================================
-- PEGASUS ERP - Migration: Add deal_id to sales_order_headers
-- Links sales orders to deals for traceability
-- ============================================================

-- Add deal_id column to sales_order_headers
ALTER TABLE sales_order_headers
    ADD COLUMN IF NOT EXISTS deal_id INT REFERENCES deals(deal_id);

-- Index for performance
CREATE INDEX IF NOT EXISTS idx_so_deal ON sales_order_headers(deal_id);
