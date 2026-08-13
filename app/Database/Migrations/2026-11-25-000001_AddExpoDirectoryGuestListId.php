<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Links an exhibitor directory entry to its company guest list.
 *
 * Both tables live in the `registration` group (bitswork_registration), but the
 * link is optional in both directions: a guest list may exist without an
 * exhibitor, and an exhibitor may have no guest list — so no foreign key.
 */
class AddExpoDirectoryGuestListId extends Migration
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
        if (!$this->db->fieldExists('CompanyGuestListsID', 'expodirectory')) {
            $this->forge->addColumn('expodirectory', [
                'CompanyGuestListsID' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('CompanyGuestListsID', 'expodirectory')) {
            $this->forge->dropColumn('expodirectory', 'CompanyGuestListsID');
        }
    }
}
