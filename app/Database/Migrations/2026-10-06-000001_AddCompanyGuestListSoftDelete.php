<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/** Soft delete for guest lists: companyguestlists.DeletedAt / DeletedBy (registration DB). */
class AddCompanyGuestListSoftDelete extends Migration
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
        if (!$this->db->fieldExists('DeletedAt', 'companyguestlists')) {
            $this->db->query('ALTER TABLE companyguestlists ADD COLUMN DeletedAt DATETIME NULL DEFAULT NULL');
        }
        if (!$this->db->fieldExists('DeletedBy', 'companyguestlists')) {
            $this->db->query('ALTER TABLE companyguestlists ADD COLUMN DeletedBy INT UNSIGNED NULL DEFAULT NULL');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('DeletedBy', 'companyguestlists')) $this->forge->dropColumn('companyguestlists', 'DeletedBy');
        if ($this->db->fieldExists('DeletedAt', 'companyguestlists')) $this->forge->dropColumn('companyguestlists', 'DeletedAt');
    }
}
