<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-event language toggles for the public guest registration form.
 * English is always shown; these layer Chinese / Korean labels on top.
 *
 * The `events` table lives in the DEFAULT database group.
 */
class AddEventGuestFormLanguages extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $add = [];
        if (!$this->db->fieldExists('GuestFormChinese', 'events')) {
            $add['GuestFormChinese'] = ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0];
        }
        if (!$this->db->fieldExists('GuestFormKorean', 'events')) {
            $add['GuestFormKorean'] = ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0];
        }
        if ($add !== []) {
            $this->forge->addColumn('events', $add);
        }
    }

    public function down()
    {
        foreach (['GuestFormChinese', 'GuestFormKorean'] as $col) {
            if ($this->db->fieldExists($col, 'events')) {
                $this->forge->dropColumn('events', $col);
            }
        }
    }
}
