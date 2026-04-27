<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hierarchical markets (tags). Uses materialized path so subtree queries are
 * a single LIKE instead of a recursive CTE. Path/Depth are maintained by the
 * controller (via App\Libraries\MarketTree).
 */
class CreateMarkets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'MarketID'   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ParentID'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'Name'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'Slug'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'Path'       => ['type' => 'VARCHAR', 'constraint' => 500],
            'Depth'      => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'Active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'Sort'       => ['type' => 'INT', 'default' => 0],
            'Added'      => ['type' => 'DATETIME', 'null' => true],
            'Stamp'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('MarketID');
        $this->forge->addUniqueKey(['ParentID', 'Slug'], 'uk_markets_slug_parent');
        $this->forge->addKey(['ParentID']);
        $this->forge->addKey(['Path']);
        $this->forge->addForeignKey('ParentID', 'markets', 'MarketID', '', 'RESTRICT');
        $this->forge->createTable('markets');
    }

    public function down()
    {
        $this->forge->dropTable('markets');
    }
}
