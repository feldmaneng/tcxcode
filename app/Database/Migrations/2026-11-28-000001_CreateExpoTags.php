<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Global exhibitor tags (sponsorship levels / advertising packages) and the
 * per-entry assignments.
 *
 * Lives in the REGISTRATION database (bitswork_registration) alongside
 * expodirectory. The tag list is global — the same tags are reused for every
 * event; only the assignments are per exhibitor entry.
 */
class CreateExpoTags extends Migration
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
        $this->forge->addField([
            'TagID'    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'Name'     => ['type' => 'VARCHAR', 'constraint' => 60],
            'Category' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'sponsorship'],
            'Sort'     => ['type' => 'INT', 'default' => 0],
            'Active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'Created'  => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'Updated'  => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('TagID');
        $this->forge->addUniqueKey(['Name'], 'uq_expotag_name');
        $this->forge->addKey(['Category', 'Sort']);
        $this->forge->createTable('expotags', true);

        $this->forge->addField([
            'EntryID' => ['type' => 'INT', 'unsigned' => true],
            'TagID'   => ['type' => 'INT', 'unsigned' => true],
            'AddedBy' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'Created' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey(['EntryID', 'TagID']);
        $this->forge->addKey(['TagID']);
        $this->forge->createTable('expodirectory_tags', true);

        // Same-database FKs — best effort in case the legacy engine rejects them.
        try {
            $this->db->query('ALTER TABLE expodirectory_tags ADD CONSTRAINT fk_expotag_entry FOREIGN KEY (EntryID) REFERENCES expodirectory(EntryID) ON DELETE CASCADE');
        } catch (\Throwable $e) {}
        try {
            $this->db->query('ALTER TABLE expodirectory_tags ADD CONSTRAINT fk_expotag_tag FOREIGN KEY (TagID) REFERENCES expotags(TagID) ON DELETE CASCADE');
        } catch (\Throwable $e) {}

        $seed = [
            ['Premier', 'sponsorship', 10],
            ['Emeritus', 'sponsorship', 20],
            ['Honored', 'sponsorship', 30],
            ['Distinguished', 'sponsorship', 40],
            ['Keynote', 'sponsorship', 50],
            ['Tutorial', 'sponsorship', 60],
            ['Full page ad', 'advertising', 10],
            ['Standing banner', 'advertising', 20],
            ['Hanging banner', 'advertising', 30],
        ];
        foreach ($seed as [$name, $category, $sort]) {
            try {
                $this->db->query(
                    'INSERT IGNORE INTO expotags (Name, Category, Sort, Active) VALUES (?, ?, ?, 1)',
                    [$name, $category, $sort]
                );
            } catch (\Throwable $e) {
                log_message('error', '[expo] tag seed failed: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('expodirectory_tags', true);
        $this->forge->dropTable('expotags', true);
    }
}
