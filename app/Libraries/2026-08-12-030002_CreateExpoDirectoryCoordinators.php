<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Up to four exhibitor coordinators per expodirectory entry.
 *
 * Lives in the REGISTRATION database (bitswork_registration) alongside
 * expodirectory. EntryID gets a real FK (same database); ContactID points at
 * `contacts` in the conference database and stays a plain int.
 */
class CreateExpoDirectoryCoordinators extends Migration
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
            'ID'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'EntryID'   => ['type' => 'INT', 'unsigned' => true],
            'ContactID' => ['type' => 'INT', 'unsigned' => true],
            'IsPrimary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'SortOrder' => ['type' => 'INT', 'default' => 0],
            'AddedBy'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'Created'   => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('ID');
        $this->forge->addUniqueKey(['EntryID', 'ContactID'], 'uq_expocoord_entry_contact');
        $this->forge->addKey(['ContactID']);
        $this->forge->createTable('expodirectory_coordinators', true);

        // Same-database FK — best effort in case the legacy engine rejects it.
        try {
            $this->db->query('ALTER TABLE expodirectory_coordinators ADD CONSTRAINT fk_expocoord_entry FOREIGN KEY (EntryID) REFERENCES expodirectory(EntryID) ON DELETE CASCADE');
        } catch (\Throwable $e) {}

        // Seed the legacy primary contact as coordinator #1.
        try {
            $this->db->query(
                'INSERT IGNORE INTO expodirectory_coordinators (EntryID, ContactID, IsPrimary, SortOrder) ' .
                'SELECT EntryID, ContactID, 1, 0 FROM expodirectory WHERE ContactID IS NOT NULL AND ContactID > 0'
            );
        } catch (\Throwable $e) {
            log_message('error', '[expo] coordinator seed failed: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $this->forge->dropTable('expodirectory_coordinators', true);
    }
}
