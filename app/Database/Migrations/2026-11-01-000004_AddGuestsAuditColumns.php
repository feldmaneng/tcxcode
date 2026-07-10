<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add AddedBy / UpdatedBy audit columns to the legacy `guests` table so the
 * new guest-list module can record who created/updated each row. Safe to
 * re-run: guarded by fieldExists().
 */
class AddGuestsAuditColumns extends Migration
{
    public function up()
    {
        $forge = \Config\Database::forge();

        if (!$this->db->fieldExists('AddedBy', 'guests')) {
            $forge->addColumn('guests', [
                'AddedBy' => [
                    'type'    => 'INT',
                    'null'    => true,
                    'after'   => 'OfficeNotes',
                ],
            ]);
        }
        if (!$this->db->fieldExists('UpdatedBy', 'guests')) {
            $forge->addColumn('guests', [
                'UpdatedBy' => [
                    'type'    => 'INT',
                    'null'    => true,
                    'after'   => 'AddedBy',
                ],
            ]);
        }
    }

    public function down()
    {
        $forge = \Config\Database::forge();
        if ($this->db->fieldExists('UpdatedBy', 'guests')) {
            $forge->dropColumn('guests', 'UpdatedBy');
        }
        if ($this->db->fieldExists('AddedBy', 'guests')) {
            $forge->dropColumn('guests', 'AddedBy');
        }
    }
}
