-- ============================================================================
-- Migration: Modules (CRM/Wiki/Admin), Wikis, per-Wiki permissions, Admin audit
-- ============================================================================
-- Run against the `control` database (same DB as `users`, `api_clients`).
-- Idempotent where possible — re-running on an already-migrated DB is safe.
-- ============================================================================

-- ---------- 1. Extend users table ----------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `Email`               VARCHAR(190) NULL DEFAULT NULL AFTER `FamilyName`,
  ADD COLUMN IF NOT EXISTS `Active`              TINYINT(1)   NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `MustChangePassword`  TINYINT(1)   NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `PasswordChangedAt`   DATETIME     NULL DEFAULT NULL;

-- ---------- 2. Modules ----------
CREATE TABLE IF NOT EXISTS `modules` (
  `ModuleID`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Code`        VARCHAR(32)  NOT NULL,
  `Name`        VARCHAR(100) NOT NULL,
  `Description` VARCHAR(255) NULL,
  `SortOrder`   INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`ModuleID`),
  UNIQUE KEY `uniq_modules_code` (`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `modules` (`Code`, `Name`, `Description`, `SortOrder`) VALUES
  ('crm',   'CRM',   'Contacts, companies, presentations, attendance', 10),
  ('wiki',  'Wiki',  'Knowledge base and documentation',                20),
  ('admin', 'Admin', 'User management and system administration',       30);

CREATE TABLE IF NOT EXISTS `user_modules` (
  `UserID`   INT UNSIGNED NOT NULL,
  `ModuleID` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`UserID`, `ModuleID`),
  KEY `idx_user_modules_module` (`ModuleID`),
  CONSTRAINT `fk_user_modules_user`   FOREIGN KEY (`UserID`)   REFERENCES `users`(`UserID`)     ON DELETE CASCADE,
  CONSTRAINT `fk_user_modules_module` FOREIGN KEY (`ModuleID`) REFERENCES `modules`(`ModuleID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grant CRM + Wiki to every existing user
INSERT IGNORE INTO `user_modules` (`UserID`, `ModuleID`)
SELECT u.`UserID`, m.`ModuleID`
FROM `users` u
CROSS JOIN `modules` m
WHERE m.`Code` IN ('crm','wiki');

-- Grant Admin to UserName 'Ira'
INSERT IGNORE INTO `user_modules` (`UserID`, `ModuleID`)
SELECT u.`UserID`, m.`ModuleID`
FROM `users` u
JOIN `modules` m ON m.`Code` = 'admin'
WHERE u.`UserName` = 'Ira';

-- ---------- 3. Wikis ----------
CREATE TABLE IF NOT EXISTS `wikis` (
  `WikiID`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Slug`        VARCHAR(64)  NOT NULL,
  `Name`        VARCHAR(150) NOT NULL,
  `Description` VARCHAR(500) NULL,
  `CreatedBy`   INT UNSIGNED NULL,
  `CreatedAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`WikiID`),
  UNIQUE KEY `uniq_wikis_slug` (`Slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wiki_pages` (
  `PageID`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `WikiID`            INT UNSIGNED NOT NULL,
  `ParentID`          INT UNSIGNED NULL,
  `Slug`              VARCHAR(120) NOT NULL,
  `Title`             VARCHAR(255) NOT NULL,
  `SortOrder`         INT UNSIGNED NOT NULL DEFAULT 0,
  `CurrentRevisionID` INT UNSIGNED NULL,
  `CreatedBy`         INT UNSIGNED NULL,
  `CreatedAt`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `DeletedAt`         DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (`PageID`),
  KEY `idx_wiki_pages_wiki_parent` (`WikiID`, `ParentID`),
  KEY `idx_wiki_pages_slug` (`WikiID`, `Slug`),
  CONSTRAINT `fk_wiki_pages_wiki`   FOREIGN KEY (`WikiID`)   REFERENCES `wikis`(`WikiID`)     ON DELETE CASCADE,
  CONSTRAINT `fk_wiki_pages_parent` FOREIGN KEY (`ParentID`) REFERENCES `wiki_pages`(`PageID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wiki_revisions` (
  `RevisionID`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `PageID`       INT UNSIGNED NOT NULL,
  `Title`        VARCHAR(255) NOT NULL,
  `BodyMarkdown` LONGTEXT     NOT NULL,
  `BodyHtml`     LONGTEXT     NULL,
  `EditedBy`     INT UNSIGNED NULL,
  `EditedAt`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `EditSummary`  VARCHAR(500) NULL,
  PRIMARY KEY (`RevisionID`),
  KEY `idx_wiki_revisions_page` (`PageID`, `EditedAt`),
  CONSTRAINT `fk_wiki_revisions_page` FOREIGN KEY (`PageID`) REFERENCES `wiki_pages`(`PageID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wiki_attachments` (
  `AttachmentID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `WikiID`       INT UNSIGNED NOT NULL,
  `PageID`       INT UNSIGNED NULL,
  `StorageBucket` VARCHAR(64) NOT NULL,
  `StorageKey`    VARCHAR(500) NOT NULL,
  `OriginalName` VARCHAR(255) NOT NULL,
  `MimeType`     VARCHAR(100) NULL,
  `SizeBytes`    BIGINT UNSIGNED NULL,
  `Width`        INT UNSIGNED NULL,
  `Height`       INT UNSIGNED NULL,
  `UploadedBy`   INT UNSIGNED NULL,
  `UploadedAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`AttachmentID`),
  KEY `idx_wiki_attachments_wiki` (`WikiID`),
  KEY `idx_wiki_attachments_page` (`PageID`),
  CONSTRAINT `fk_wiki_attachments_wiki` FOREIGN KEY (`WikiID`) REFERENCES `wikis`(`WikiID`)     ON DELETE CASCADE,
  CONSTRAINT `fk_wiki_attachments_page` FOREIGN KEY (`PageID`) REFERENCES `wiki_pages`(`PageID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wiki_comments` (
  `CommentID`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `PageID`          INT UNSIGNED NOT NULL,
  `ParentCommentID` INT UNSIGNED NULL,
  `BodyMarkdown`    TEXT         NOT NULL,
  `AuthorUserID`    INT UNSIGNED NULL,
  `CreatedAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `DeletedAt`       DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (`CommentID`),
  KEY `idx_wiki_comments_page` (`PageID`, `CreatedAt`),
  KEY `idx_wiki_comments_parent` (`ParentCommentID`),
  CONSTRAINT `fk_wiki_comments_page`   FOREIGN KEY (`PageID`)          REFERENCES `wiki_pages`(`PageID`)    ON DELETE CASCADE,
  CONSTRAINT `fk_wiki_comments_parent` FOREIGN KEY (`ParentCommentID`) REFERENCES `wiki_comments`(`CommentID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_wiki_permissions` (
  `UserID`     INT UNSIGNED NOT NULL,
  `WikiID`     INT UNSIGNED NOT NULL,
  `Permission` ENUM('read_comment','write_edit') NOT NULL,
  PRIMARY KEY (`UserID`, `WikiID`),
  KEY `idx_uwp_wiki` (`WikiID`),
  CONSTRAINT `fk_uwp_user` FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE CASCADE,
  CONSTRAINT `fk_uwp_wiki` FOREIGN KEY (`WikiID`) REFERENCES `wikis`(`WikiID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: default Wiki "General" + grant write_edit to all existing users
INSERT IGNORE INTO `wikis` (`Slug`, `Name`, `Description`)
VALUES ('general', 'General', 'Default wiki space');

INSERT IGNORE INTO `user_wiki_permissions` (`UserID`, `WikiID`, `Permission`)
SELECT u.`UserID`, w.`WikiID`, 'write_edit'
FROM `users` u
CROSS JOIN `wikis` w
WHERE w.`Slug` = 'general';

-- ---------- 4. Admin audit log ----------
CREATE TABLE IF NOT EXISTS `admin_audit_log` (
  `AuditID`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ActorUserID` INT UNSIGNED NULL,
  `Action`      VARCHAR(64)  NOT NULL,
  `TargetType`  VARCHAR(32)  NULL,
  `TargetID`    VARCHAR(64)  NULL,
  `Details`     JSON         NULL,
  `IpAddress`   VARCHAR(45)  NULL,
  `CreatedAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`AuditID`),
  KEY `idx_admin_audit_actor`  (`ActorUserID`, `CreatedAt`),
  KEY `idx_admin_audit_target` (`TargetType`, `TargetID`),
  KEY `idx_admin_audit_action` (`Action`, `CreatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
