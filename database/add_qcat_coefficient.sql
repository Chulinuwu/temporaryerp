-- Add a markup coefficient to quotation_categories.
-- When items are entered under a category in a quotation, the unit price is
-- automatically computed as cost × coefficient (= default markup).

BEGIN;

ALTER TABLE quotation_categories
    ADD COLUMN IF NOT EXISTS cost_coefficient NUMERIC(8,4) NOT NULL DEFAULT 1.0;

-- Seed defaults per business rule
UPDATE quotation_categories SET cost_coefficient = 1.2 WHERE category_code = 'HW';
UPDATE quotation_categories SET cost_coefficient = 1.5 WHERE category_code = 'SW';
UPDATE quotation_categories SET cost_coefficient = 1.5 WHERE category_code = 'SERVICE';
UPDATE quotation_categories SET cost_coefficient = 1.2 WHERE category_code = 'LICENSE';
UPDATE quotation_categories SET cost_coefficient = 1.5 WHERE category_code = 'MAINT';
UPDATE quotation_categories SET cost_coefficient = 1.5 WHERE category_code = 'TRAINING';
UPDATE quotation_categories SET cost_coefficient = 1.0 WHERE category_code = 'OTHER';

COMMIT;
