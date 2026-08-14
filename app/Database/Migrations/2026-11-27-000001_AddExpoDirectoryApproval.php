<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Directory-listing approval tracking + widened BoothType enum.
 *
 * ApprovedBy / ApprovedAt record which user (control DB UserID) approved the
 * exhibitor's directory listing and when. Runs on the 'registration' group.
 * Guarded so it is safe to re-run.
 */
class AddExpoDirectoryApproval extends Migration
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

        if (!$this->db->fieldExists('ApprovedBy', 'expodirectory')) {
            $add['ApprovedBy'] = ['type' => 'INT', 'null' => true];
        }
        if (!$this->db->fieldExists('ApprovedAt', 'expodirectory')) {
            $add['ApprovedAt'] = ['type' => 'DATETIME', 'null' => true];
        }

        if ($add !== []) {
            $this->forge->addColumn('expodirectory', $add);
        }

        // Widen BoothType to include the new 'single' / 'double' options.
        // Only touch the column when it really is an ENUM.
        try {
            $col = $this->db->query(
                "SHOW COLUMNS FROM expodirectory LIKE 'BoothType'"
            )->getRowArray();
            $type = strtolower((string) ($col['Type'] ?? ''));
            if (str_starts_with($type, 'enum(')) {
                $null = (($col['Null'] ?? 'YES') === 'YES') ? 'NULL' : 'NOT NULL';
                $this->db->query(
                    "ALTER TABLE expodirectory MODIFY BoothType "
                    . "ENUM('8','10','2x8','2x10','8+10','single','double') $null"
                );
            }
        } catch (\Throwable $e) {
            log_message('error', '[expo] BoothType enum widen failed: ' . $e->getMessage());
        }
    }

    public function down()
    {
        foreach (['ApprovedBy', 'ApprovedAt'] as $col) {
            if ($this->db->fieldExists($col, 'expodirectory')) {
                $this->forge->dropColumn('expodirectory', $col);
            }
        }
    }
}
