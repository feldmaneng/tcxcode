<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Binds a company guest list to a specific event.
 *
 * Several events can share the same Year (e.g. Mesa / China / Korea 2026), so
 * resolving the event behind a public registration token by Year alone can pick
 * the wrong row — and with it the wrong guest-form language toggles.
 *
 * `companyguestlists` lives in the REGISTRATION database group; `events` lives in
 * the default group, so this is a plain int reference (no FK across databases).
 */
class AddCompanyGuestListEventId extends Migration
{
    protected $DBGroup = 'registration';

    public function up()
    {
        if (!$this->db->fieldExists('EventID', 'companyguestlists')) {
            $this->forge->addColumn('companyguestlists', [
                'EventID' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('EventID', 'companyguestlists')) {
            $this->forge->dropColumn('companyguestlists', 'EventID');
        }
    }
}
