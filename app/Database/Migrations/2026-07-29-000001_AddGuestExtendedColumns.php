<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Guest list expansion — additive columns on the legacy `guests` table.
 *
 * - WeChatID / KakaoID       : messaging handles (shown per event language)
 * - SignupType               : how the row was created
 * - DeletedAt/By/IP          : soft delete
 * - AddedIP / UpdatedIP      : audit IP (AddedBy/UpdatedBy = 0 => public writer)
 * - Type widened to VARCHAR  : now stores 'Professional' | 'Exhibitor'
 *
 * Runs on the 'registration' DB group. Guarded by fieldExists() so it is
 * safe to re-run.
 */
class AddGuestExtendedColumns extends Migration
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
        $forge = $this->forge;

        $add = [];

        if (!$this->db->fieldExists('WeChatID', 'guests')) {
            $add['WeChatID'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true];
        }
        if (!$this->db->fieldExists('KakaoID', 'guests')) {
            $add['KakaoID'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true];
        }
        if (!$this->db->fieldExists('SignupType', 'guests')) {
            $add['SignupType'] = [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => false,
                'default'    => 'Other',
            ];
        }
        if (!$this->db->fieldExists('DeletedAt', 'guests')) {
            $add['DeletedAt'] = ['type' => 'DATETIME', 'null' => true];
        }
        if (!$this->db->fieldExists('DeletedBy', 'guests')) {
            $add['DeletedBy'] = ['type' => 'INT', 'null' => true];
        }
        if (!$this->db->fieldExists('DeletedIP', 'guests')) {
            $add['DeletedIP'] = ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true];
        }
        if (!$this->db->fieldExists('AddedIP', 'guests')) {
            $add['AddedIP'] = ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true];
        }
        if (!$this->db->fieldExists('UpdatedIP', 'guests')) {
            $add['UpdatedIP'] = ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true];
        }

        if ($add !== []) {
            $forge->addColumn('guests', $add);
        }

        // Widen Type so it can hold 'Professional' / 'Exhibitor'.
        // (MySQL ENUM cannot hold both 'PROFESSIONAL' and 'Professional' under
        //  a case-insensitive collation, so VARCHAR is used instead.)
        $forge->modifyColumn('guests', [
            'Type' => [
                'name'       => 'Type',
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'Professional',
            ],
        ]);

        try {
            $this->db->query("CREATE INDEX guests_deletedat_idx ON guests (DeletedAt)");
        } catch (\Throwable $e) {
            // index already exists — ignore
        }

    }

    public function down()
    {
        $forge = $this->forge;
        foreach (['WeChatID', 'KakaoID', 'SignupType', 'DeletedAt', 'DeletedBy', 'DeletedIP', 'AddedIP', 'UpdatedIP'] as $col) {
            if ($this->db->fieldExists($col, 'guests')) {
                $forge->dropColumn('guests', $col);
            }
        }
    }
}
