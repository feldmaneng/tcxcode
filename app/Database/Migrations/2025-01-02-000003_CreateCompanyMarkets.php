<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Junction table between company and markets. Companies can be tagged at any
 * level of the markets tree (leaf or branch).
 */
class CreateCompanyMarkets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'CompanyID' => ['type' => 'INT', 'unsigned' => true],
            'MarketID'  => ['type' => 'INT', 'unsigned' => true],
            'Added'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey(['CompanyID', 'MarketID']);
        $this->forge->addKey(['MarketID']);
        $this->forge->addForeignKey('CompanyID', 'company', 'CompanyID', '', 'CASCADE');
        $this->forge->addForeignKey('MarketID', 'markets', 'MarketID', '', 'CASCADE');
        $this->forge->createTable('company_markets');
    }

    public function down()
    {
        $this->forge->dropTable('company_markets');
    }
}
