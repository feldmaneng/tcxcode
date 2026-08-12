<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Golf invites — mirrors the existing Banquet flag.
 *
 * events.GolfEnabled            (default group)      — per-event feature switch
 * companyguestlists.GolfCount   (registration group) — per-company allotment
 * guests.GolfCompanyID          (registration group) — set = golf invitee
 *
 * All adds are guarded so the migration is safe to re-run.
 */
class AddGolfInvites extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        // 1) events.GolfEnabled (default DB group)
        try {
            $db = Database::connect('default');
            if (!$db->fieldExists('GolfEnabled', 'events')) {
                Database::forge('default')->addColumn('events', [
                    'GolfEnabled' => [
                        'type' => 'TINYINT', 'constraint' => 1,
                        'null' => false, 'default' => 0,
                        'after' => 'GuestListEnabled',
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'events.GolfEnabled add skipped: ' . $e->getMessage());
        }

        // 2) registration DB group columns
        try {
            $rdb   = Database::connect('registration');
            $forge = Database::forge('registration');

            if (!$rdb->fieldExists('GolfCount', 'companyguestlists')) {
                $forge->addColumn('companyguestlists', [
                    'GolfCount' => ['type' => 'INT', 'null' => true],
                ]);
            }
            if (!$rdb->fieldExists('GolfCompanyID', 'guests')) {
                $forge->addColumn('guests', [
                    'GolfCompanyID' => ['type' => 'INT', 'null' => true],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'golf registration columns add skipped: ' . $e->getMessage());
        }
    }

    public function down()
    {
        try { Database::forge('default')->dropColumn('events', ['GolfEnabled']); } catch (\Throwable $e) {}
        try { Database::forge('registration')->dropColumn('companyguestlists', ['GolfCount']); } catch (\Throwable $e) {}
        try { Database::forge('registration')->dropColumn('guests', ['GolfCompanyID']); } catch (\Throwable $e) {}
    }
}
