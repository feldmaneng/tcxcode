-- Run this against the `control` database group.
-- Adds support for admin pre-provisioning of WordPress SSO users from a contact.
--
-- Idempotency: every statement uses IF NOT EXISTS / IF EXISTS where supported.
-- After applying:
--   * Existing wp_user_id UNIQUE key still allows multiple rows where wp_user_id IS NULL
--     (MySQL's UNIQUE treats NULL as distinct), so multiple unclaimed pre-provisioned
--     rows can coexist as long as Email / UserName remain unique.
--   * `auth_provider='wordpress' AND wp_user_id IS NULL` represents an unclaimed
--     pre-provisioned account waiting for first WP-SSO login.

ALTER TABLE users
    ADD COLUMN ProvisionedFromContactID INT UNSIGNED NULL AFTER wp_user_id;

-- Speeds up the WP-SSO claim lookup that filters by auth_provider + Email/UserName.
CREATE INDEX idx_users_provider_email    ON users (auth_provider, Email);
CREATE INDEX idx_users_provider_username ON users (auth_provider, UserName);
