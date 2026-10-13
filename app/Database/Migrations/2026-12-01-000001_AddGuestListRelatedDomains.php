<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/** Manual related / excluded email domains per guest list (registration DB). */
class AddGuestListRelatedDomains extends Migration
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
        $add = [];
        if (!$this->db->fieldExists('RelatedDomains', 'companyguestlists')) {
            $add['RelatedDomains'] = ['type' => 'TEXT', 'null' => true];
        }
        if (!$this->db->fieldExists('ExcludedDomains', 'companyguestlists')) {
            $add['ExcludedDomains'] = ['type' => 'TEXT', 'null' => true];
        }
        if ($add) $this->forge->addColumn('companyguestlists', $add);
    }

    public function down()
    {
        foreach (['RelatedDomains', 'ExcludedDomains'] as $c) {
            if ($this->db->fieldExists($c, 'companyguestlists')) $this->forge->dropColumn('companyguestlists', $c);
        }
    }
}
