-- ============================================================
-- PEGASUS ERP - Migration: Update project_code for grouping
-- Groups quotations by customer + base project name
-- Run this ONLY if you want to keep existing data without re-import
-- ============================================================

-- Update project_code to enable display grouping
-- Strip percentage suffix from project_name and combine with customer_id
UPDATE quotation_headers
SET project_code = customer_id || '|||' || REGEXP_REPLACE(COALESCE(project_name, ''), '\s*\d+%\s*$', '')
WHERE project_code IS NULL
  AND project_name IS NOT NULL
  AND project_name != ''
  AND is_deleted = FALSE;
