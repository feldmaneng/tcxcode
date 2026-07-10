<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Add AddedBy / UpdatedBy audit columns to the legacy `guests` table so the
 * new guest-list module can record who created/updated each row. Safe to
 * re-run: guarded by fieldExists(). Runs on the 'registration' DB group,
 * where the `guests` and `companyguestlists` tables live.
 */
class AddGuestsAuditColumns extends Migration
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
        $forge = $this->forge;

        if (!$this->db->fieldExists('AddedBy', 'guests')) {
            $forge->addColumn('guests', [
                'AddedBy' => [
                    'type'    => 'INT',
                    'null'    => true,
                    'after'   => 'OfficeNotes',
                ],
            ]);
        }
        if (!$this->db->fieldExists('UpdatedBy', 'guests')) {
            $forge->addColumn('guests', [
                'UpdatedBy' => [
                    'type'    => 'INT',
                    'null'    => true,
                    'after'   => 'AddedBy',
                ],
            ]);
        }
    }

    public function down()
    {
        $forge = $this->forge;
        if ($this->db->fieldExists('UpdatedBy', 'guests')) {
            $forge->dropColumn('guests', 'UpdatedBy');
        }
        if ($this->db->fieldExists('AddedBy', 'guests')) {
            $forge->dropColumn('guests', 'AddedBy');
        }
    }
}
