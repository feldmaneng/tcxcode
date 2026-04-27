<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration is documentation-only — the existing `company` table is in the
 * legacy schema. Run only on fresh installs. The shape mirrors the production
 * column definitions provided by the product owner.
 */
class CreateCompany extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'CompanyID'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'Name'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'ParentID'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'IsParent'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'Active'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'CN_Name'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'URL'               => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'Stock_Market'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'Ticker_Symbol'     => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'Research_link'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'Notes'             => ['type' => 'TEXT', 'null' => true],
            'Added'             => ['type' => 'DATETIME', 'null' => true],
            'Stamp'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('CompanyID');
        $this->forge->addKey(['ParentID']);
        $this->forge->addKey(['Name']);
        $this->forge->createTable('company');
    }

    public function down()
    {
        $this->forge->dropTable('company');
    }
}
