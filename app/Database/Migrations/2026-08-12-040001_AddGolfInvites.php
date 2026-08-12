<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Golf invites — mirrors the existing Banquet flag.
 *
 * companyguestlists.GolfCount   (registration group / bitswork_registration)
 * guests.GolfCompanyID          (registration group / bitswork_registration)
 * events.GolfEnabled            (wherever `events` actually lives — checked in
 *                                the registration group first, then default)
 *
 * All adds are guarded so the migration is safe to re-run.
 */
class AddGolfInvites extends Migration
{
    /** The guest tables live in the registration database. */
    protected $DBGroup = 'registration';

    public function __construct()
    {
        parent::__construct();
        $this->db    = Database::connect($this->DBGroup);
        $this->forge = Database::forge($this->DBGroup);
    }

    public function up()
    {
        // 1) registration DB group: companyguestlists.GolfCount + guests.GolfCompanyID
        try {
            if (!$this->db->fieldExists('GolfCount', 'companyguestlists')) {
                $this->forge->addColumn('companyguestlists', [
                    'GolfCount' => ['type' => 'INT', 'null' => true, 'after' => 'BanquetCount'],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'companyguestlists.GolfCount add skipped: ' . $e->getMessage());
        }

        try {
            if (!$this->db->fieldExists('GolfCompanyID', 'guests')) {
                $this->forge->addColumn('guests', [
                    'GolfCompanyID' => ['type' => 'INT', 'null' => true, 'after' => 'BanquetCompanyID'],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'guests.GolfCompanyID add skipped: ' . $e->getMessage());
        }

        // 2) events.GolfEnabled — find the group that actually holds `events`.
        foreach (['registration', 'default'] as $group) {
            try {
                $db = Database::connect($group);
                if (!$db->tableExists('events')) continue;
                if ($db->fieldExists('GolfEnabled', 'events')) break;

                $col = ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0];
                if ($db->fieldExists('GuestListEnabled', 'events')) {
                    $col['after'] = 'GuestListEnabled';
                }
                Database::forge($group)->addColumn('events', ['GolfEnabled' => $col]);
                break;
            } catch (\Throwable $e) {
                log_message('warning', "events.GolfEnabled add skipped ({$group}): " . $e->getMessage());
            }
        }
    }

    public function down()
    {
        try { $this->forge->dropColumn('companyguestlists', ['GolfCount']); } catch (\Throwable $e) {}
        try { $this->forge->dropColumn('guests', ['GolfCompanyID']); } catch (\Throwable $e) {}
        foreach (['registration', 'default'] as $group) {
            try {
                $db = Database::connect($group);
                if ($db->tableExists('events') && $db->fieldExists('GolfEnabled', 'events')) {
                    Database::forge($group)->dropColumn('events', ['GolfEnabled']);
                    break;
                }
            } catch (\Throwable $e) {}
        }
    }
}
