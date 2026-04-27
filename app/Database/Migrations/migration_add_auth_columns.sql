-- Migration: Add authentication columns to control.users
-- Run this against your CI4 control database.
-- Only adds columns that don't already exist (TOTPSecret, TOTPEnabled, WebAuthn* were added previously).

-- If these columns are already present (per DESCRIBE output), this migration is already applied.
-- Keeping for reference / fresh installs:

-- ALTER TABLE `users`
--   ADD COLUMN `TOTPSecret` CHAR(64) NULL DEFAULT NULL AFTER `PasswordHash`,
--   ADD COLUMN `TOTPEnabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `TOTPSecret`,
--   ADD COLUMN `WebAuthnCredentialID` VARCHAR(255) NULL DEFAULT NULL AFTER `TOTPEnabled`,
--   ADD COLUMN `WebAuthnPublicKey` TEXT NULL DEFAULT NULL AFTER `WebAuthnCredentialID`,
--   ADD COLUMN `WebAuthnCounter` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `WebAuthnPublicKey`,
--   ADD COLUMN `WebAuthnTransports` VARCHAR(255) NULL DEFAULT NULL AFTER `WebAuthnCounter`;

-- Index for passkey lookups by credential ID (run only if not yet created)
-- ALTER TABLE `users`
--   ADD INDEX `idx_webauthn_credential` (`WebAuthnCredentialID`);
