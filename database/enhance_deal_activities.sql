-- ============================================================
-- #6 Enhance deal_activities:
--   - contact_person_id (required, FK to customer_contacts)
--   - start_time / end_time
--   - rename description → minutes (just label change in app; column stays)
-- ============================================================

BEGIN;

ALTER TABLE deal_activities
    ADD COLUMN IF NOT EXISTS start_time      TIME,
    ADD COLUMN IF NOT EXISTS end_time        TIME,
    ADD COLUMN IF NOT EXISTS contact_id      INT REFERENCES customer_contacts(contact_id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_deal_activities_contact ON deal_activities(contact_id);

-- Verify
\d deal_activities

COMMIT;
