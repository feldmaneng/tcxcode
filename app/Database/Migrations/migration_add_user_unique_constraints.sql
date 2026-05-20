-- Run against the `control` database group.
-- Enforces unique UserName and Email so a WP-SSO login can never silently
-- create a duplicate user when the claim lookup misses (the Rachel bug:
-- pre-provisioned UserID 18 was not claimed and UserID 19 was created with
-- the same Email).
--
-- BEFORE RUNNING: resolve any existing duplicates. Example for Rachel:
--   1. Decide which row is canonical (usually the pre-provisioned one, UserID 18).
--   2. Re-point dependent rows (user_modules, sessions, audit, etc.) from
--      the duplicate (19) to the canonical (18).
--   3. DELETE the duplicate row.
--   4. UPDATE users SET wp_user_id = <wp_id> WHERE UserID = 18;
--   Then run this migration.
--
-- Detect remaining duplicates first:
--   SELECT LOWER(Email) e, COUNT(*) c FROM users GROUP BY e HAVING c > 1;
--   SELECT LOWER(UserName) u, COUNT(*) c FROM users GROUP BY u HAVING c > 1;

-- Case-insensitive uniqueness via generated columns (MySQL 5.7+/8.0).
ALTER TABLE users
    ADD COLUMN email_lc    VARCHAR(255) GENERATED ALWAYS AS (LOWER(Email))    STORED,
    ADD COLUMN username_lc VARCHAR(255) GENERATED ALWAYS AS (LOWER(UserName)) STORED;

ALTER TABLE users
    ADD UNIQUE KEY uniq_users_email_lc    (email_lc),
    ADD UNIQUE KEY uniq_users_username_lc (username_lc);
