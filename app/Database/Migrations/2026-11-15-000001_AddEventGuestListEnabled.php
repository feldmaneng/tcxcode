<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Add GuestListEnabled (TINYINT default 0) to events.
 * Controls whether the /guests UI exposes this event.
 */
class AddEventGuestListEnabled extends Migration
{
    // events table lives in the default DB group (bitswork_contac2).
    protected $DBGroup = 'default';

    public function up()
    {
        $forge = Database::forge('default');
        try {
            $forge->addColumn('events', [
                'GuestListEnabled' => [
                    'type' => 'TINYINT', 'constraint' => 1,
                    'null' => false, 'default' => 0,
                    'after' => 'GeneralChairID',
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('warning', 'events.GuestListEnabled add skipped: ' . $e->getMessage());
        }
    }

    public function down()
    {
        try { Database::forge('default')->dropColumn('events', ['GuestListEnabled']); } catch (\Throwable $e) {}
    }
}
