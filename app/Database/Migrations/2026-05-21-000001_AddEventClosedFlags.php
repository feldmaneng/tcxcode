<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Add closed/locked tracking to events.
 *   IsClosed TINYINT(1) NULL — NULL = auto (lock 7d after EndDate),
 *                              1    = forced closed,
 *                              0    = forced open (suppresses auto-lock)
 *   ClosedAt DATETIME NULL  — set when an admin closes manually.
 */
class AddEventClosedFlags extends Migration
{
    public function up()
    {
        $forge = $this->forge;
        $db    = Database::connect();

        try {
            $forge->addColumn('events', [
                'IsClosed' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'after' => 'EventManagerID'],
                'ClosedAt' => ['type' => 'DATETIME', 'null' => true, 'after' => 'IsClosed'],
            ]);
        } catch (\Throwable $e) {
            log_message('warning', 'events.IsClosed/ClosedAt add skipped: ' . $e->getMessage());
        }

        try { $db->query('ALTER TABLE events ADD KEY idx_events_isclosed (IsClosed)'); } catch (\Throwable $e) {}
    }

    public function down()
    {
        try { $this->forge->dropColumn('events', ['IsClosed', 'ClosedAt']); } catch (\Throwable $e) {}
    }
}
