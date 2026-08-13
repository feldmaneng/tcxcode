<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Soft delete for exhibitor directory entries.
 *
 * Mirrors the `guests` pattern: DeletedAt / DeletedBy / DeletedIP, filtered
 * manually in the controller (no CI model soft-delete, which would surprise
 * the builder-based queries). Runs on the 'registration' DB group.
 * Guarded by fieldExists() so it is safe to re-run.
 */
class AddExpoDirectorySoftDelete extends Migration
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

        if (!$this->db->fieldExists('DeletedAt', 'expodirectory')) {
            $add['DeletedAt'] = ['type' => 'DATETIME', 'null' => true];
        }
        if (!$this->db->fieldExists('DeletedBy', 'expodirectory')) {
            $add['DeletedBy'] = ['type' => 'INT', 'null' => true];
        }
        if (!$this->db->fieldExists('DeletedIP', 'expodirectory')) {
            $add['DeletedIP'] = ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true];
        }

        if ($add !== []) {
            $this->forge->addColumn('expodirectory', $add);
        }

        try {
            $this->db->query('CREATE INDEX expodirectory_deletedat_idx ON expodirectory (DeletedAt)');
        } catch (\Throwable $e) {
            // index already exists — ignore
        }
    }

    public function down()
    {
        foreach (['DeletedAt', 'DeletedBy', 'DeletedIP'] as $col) {
            if ($this->db->fieldExists($col, 'expodirectory')) {
                $this->forge->dropColumn('expodirectory', $col);
            }
        }
    }
}
