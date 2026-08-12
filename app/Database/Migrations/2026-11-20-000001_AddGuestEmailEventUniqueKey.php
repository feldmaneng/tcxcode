<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Backstop for "one email per event".
 *
 * Adds a generated column that is NULL for soft-deleted or blank-email rows and
 * "<EventYear>|<lower(trim(Email))>" otherwise, then a UNIQUE index over it.
 * NULLs are not compared by MySQL unique indexes, so deleted rows and legacy
 * blank-email rows never collide.
 *
 * If historical years still contain live collisions, the unique index cannot be
 * created; the migration then falls back to a plain index and logs the problem
 * so the collisions can be cleaned up with tools/guest-email-collisions.sql.
 */
class AddGuestEmailEventUniqueKey extends Migration
{
    // guests lives in the registration database (bitswork_registration)
    protected $DBGroup = 'registration';

    public function up()
    {
        $db = $this->db;

        $exists = $db->query(
            "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'guests'
               AND COLUMN_NAME = 'EmailEventKey'"
        )->getRowArray();

        if ((int) ($exists['c'] ?? 0) === 0) {
            $db->query(
                "ALTER TABLE `guests`
                 ADD COLUMN `EmailEventKey` VARCHAR(320)
                 GENERATED ALWAYS AS (
                    CASE
                      WHEN `DeletedAt` IS NULL AND `Email` IS NOT NULL AND TRIM(`Email`) <> ''
                      THEN CONCAT(COALESCE(`EventYear`, ''), '|', LOWER(TRIM(`Email`)))
                      ELSE NULL
                    END
                 ) STORED"
            );
        }

        $collisions = $db->query(
            "SELECT COUNT(*) AS c FROM (
                SELECT `EmailEventKey` FROM `guests`
                WHERE `EmailEventKey` IS NOT NULL
                GROUP BY `EmailEventKey` HAVING COUNT(*) > 1
             ) d"
        )->getRowArray();

        $hasIndex = $db->query(
            "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'guests'
               AND INDEX_NAME = 'uniq_guests_email_event'"
        )->getRowArray();

        if ((int) ($hasIndex['c'] ?? 0) > 0) return;

        if ((int) ($collisions['c'] ?? 0) === 0) {
            $db->query("ALTER TABLE `guests` ADD UNIQUE KEY `uniq_guests_email_event` (`EmailEventKey`)");
        } else {
            log_message(
                'error',
                'AddGuestEmailEventUniqueKey: ' . $collisions['c']
                . ' live email/event collisions remain; created a non-unique index instead. '
                . 'Clean up with tools/guest-email-collisions.sql, then re-add the unique key.'
            );
            $db->query("ALTER TABLE `guests` ADD KEY `uniq_guests_email_event` (`EmailEventKey`)");
        }
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `guests` DROP INDEX `uniq_guests_email_event`");
        $this->db->query("ALTER TABLE `guests` DROP COLUMN `EmailEventKey`");
    }
}
