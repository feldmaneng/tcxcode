<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Binds an exhibitor-directory entry to a specific event.
 *
 * `expodirectory` lives in the REGISTRATION database (bitswork_registration);
 * `events` lives in the default conference database, so this is a plain int
 * reference with an index — no cross-database foreign key.
 *
 * Historic rows are matched to an event by Year + Event (see
 * ci4-bundle/sql/backfill-expodirectory.sql).
 */
class AddExpoDirectoryEventId extends Migration
{
    protected $DBGroup = 'registration';

    public function __construct()
    {
        parent::__construct();
        $this->db    = Database::connect($this->DBGroup);
        $this->forge = Database::forge($this->DBGroup);
    }

    public function up()
    {
        if (!$this->db->fieldExists('EventID', 'expodirectory')) {
            $this->forge->addColumn('expodirectory', [
                'EventID' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            ]);
            try {
                $this->db->query('ALTER TABLE expodirectory ADD INDEX idx_expodirectory_event (EventID)');
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('EventID', 'expodirectory')) {
            $this->forge->dropColumn('expodirectory', 'EventID');
        }
    }
}
