<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Add GeneralChairID (nullable FK-style ref to users.UserID) to events.
 * ON DELETE SET NULL — deleting a user drops the reference, keeps the event.
 * No backfill.
 */
class AddEventGeneralChair extends Migration
{
    protected $DBGroup = 'control';

    public function up()
    {
        $forge = $this->forge;
        $db    = Database::connect();

        try {
            $forge->addColumn('events', [
                'GeneralChairID' => [
                    'type' => 'INT', 'null' => true, 'after' => 'EventManagerID',
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('warning', 'events.GeneralChairID add skipped: ' . $e->getMessage());
        }

        try { $db->query('ALTER TABLE events ADD KEY idx_events_general_chair (GeneralChairID)'); } catch (\Throwable $e) {}
    }

    public function down()
    {
        try { $this->forge->dropColumn('events', ['GeneralChairID']); } catch (\Throwable $e) {}
    }
}
