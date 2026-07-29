<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Public self-registration support on `companyguestlists`:
 *   FullConfToken            — token for the Full Conference (Professional) form
 *   ExhibitorToken           — token for the Exhibitor Staff form
 *   CcPrimaryOnRegistration  — CC the primary manager (StaffID) on PUBLIC
 *                              self-registration confirmations only
 *
 * Runs on the 'registration' DB group. Backfills tokens for existing rows.
 */
class AddCompanyGuestListTokens extends Migration
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
        if (!$this->db->fieldExists('FullConfToken', 'companyguestlists')) {
            $add['FullConfToken'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true];
        }
        if (!$this->db->fieldExists('ExhibitorToken', 'companyguestlists')) {
            $add['ExhibitorToken'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true];
        }
        if (!$this->db->fieldExists('CcPrimaryOnRegistration', 'companyguestlists')) {
            $add['CcPrimaryOnRegistration'] = ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1];
        }
        if ($add !== []) {
            $this->forge->addColumn('companyguestlists', $add);
        }

        // Backfill tokens for existing rows.
        $rows = $this->db->table('companyguestlists')
            ->select('CompanyID, FullConfToken, ExhibitorToken')
            ->get()->getResultArray();
        foreach ($rows as $row) {
            $patch = [];
            if (empty($row['FullConfToken']))  $patch['FullConfToken']  = bin2hex(random_bytes(16));
            if (empty($row['ExhibitorToken'])) $patch['ExhibitorToken'] = bin2hex(random_bytes(16));
            if ($patch !== []) {
                $this->db->table('companyguestlists')
                    ->where('CompanyID', (int) $row['CompanyID'])
                    ->update($patch);
            }
        }

        foreach (['FullConfToken', 'ExhibitorToken'] as $col) {
            try {
                $this->db->query("CREATE UNIQUE INDEX cgl_{$col}_uniq ON companyguestlists ({$col})");
            } catch (\Throwable $e) {
                // already exists — ignore
            }
        }
    }

    public function down()
    {
        foreach (['FullConfToken', 'ExhibitorToken', 'CcPrimaryOnRegistration'] as $col) {
            if ($this->db->fieldExists($col, 'companyguestlists')) {
                $this->forge->dropColumn('companyguestlists', $col);
            }
        }
    }
}
