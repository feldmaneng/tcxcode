-- Run this against the `control` database group.
--
-- Adds a live, mutable users.ContactID column so the Author Portal can
-- resolve the acting WP-SSO user to a CRM contact (and from there to their
-- authored presentations via the `authors` table).
--
-- Why a separate column from ProvisionedFromContactID:
--   * ProvisionedFromContactID is an *audit pointer* — "this user was
--     created from contact X at provisioning time" — and it should never be
--     repurposed by admins after the fact.
--   * ContactID is the *live* link an admin can change/clear later (e.g.
--     when a contact is merged or replaced).
--
-- Backfill: for existing pre-provisioned rows we seed ContactID from
-- ProvisionedFromContactID, since at provisioning time those are the same
-- person.
--
-- Idempotency: re-running is safe — ALTER and UPDATE both no-op when the
-- column / values are already present.

ALTER TABLE users
    ADD COLUMN ContactID INT UNSIGNED NULL AFTER ProvisionedFromContactID,
    ADD UNIQUE KEY uniq_users_contact_id (ContactID);

UPDATE users
   SET ContactID = ProvisionedFromContactID
 WHERE ContactID IS NULL
   AND ProvisionedFromContactID IS NOT NULL;
