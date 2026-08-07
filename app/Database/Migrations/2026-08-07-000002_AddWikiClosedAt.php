<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Adds a "closed" flag to wikis. A closed wiki is hidden from every
 * non-admin wiki list and access is denied, but the per-user permission
 * rows are left untouched so reopening restores the previous access set.
 */
class AddWikiClosedAt extends Migration
{
    protected $DBGroup = 'wiki';

    public function __construct()
    {
        parent::__construct();
        $this->db    = Database::connect($this->DBGroup);
        $this->forge = Database::forge($this->DBGroup);
    }

    public function up()
    {
        if (!$this->db->fieldExists('ClosedAt', 'wikis')) {
            $this->forge->addColumn('wikis', [
                'ClosedAt' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('ClosedAt', 'wikis')) {
            $this->forge->dropColumn('wikis', 'ClosedAt');
        }
    }
}
