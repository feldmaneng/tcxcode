<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Seed the `guests` module into control.modules so it appears in the module
 * switcher. Admin-role users see all modules automatically; other users
 * appear only when granted via user_modules or when they are assigned as a
 * guest-list manager for at least one companyguestlists row (controlled in the UI).
 */
class GuestsModuleSeed extends Migration
{
    public function up()
    {
        $db = Database::connect('control');
        $existing = $db->table('modules')->where('Code', 'guests')->get()->getRowArray();
        if ($existing) return;
        $maxSort = (int) ($db->table('modules')->selectMax('SortOrder', 'mx')->get()->getRowArray()['mx'] ?? 0);
        $db->table('modules')->insert([
            'Code'        => 'guests',
            'Name'        => 'Guest Lists',
            'Description' => 'Manage per-company guest lists for events.',
            'SortOrder'   => $maxSort + 10,
        ]);
    }

    public function down()
    {
        Database::connect('control')->table('modules')->where('Code', 'guests')->delete();
    }
}
