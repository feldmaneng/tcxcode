<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/** CRM review marker + snapshot per guest (registration DB). */
class AddGuestCrmReview extends Migration
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
        if (!$this->db->fieldExists('CrmReviewedAt', 'guests')) $add['CrmReviewedAt'] = ['type' => 'DATETIME', 'null' => true];
        if (!$this->db->fieldExists('CrmReviewSnapshot', 'guests')) $add['CrmReviewSnapshot'] = ['type' => 'TEXT', 'null' => true];
        if ($add) $this->forge->addColumn('guests', $add);
    }

    public function down()
    {
        foreach (['CrmReviewedAt', 'CrmReviewSnapshot'] as $c) {
            if ($this->db->fieldExists($c, 'guests')) $this->forge->dropColumn('guests', $c);
        }
    }
}
