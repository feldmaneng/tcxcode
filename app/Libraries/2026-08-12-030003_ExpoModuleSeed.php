<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Seed the `expo` module into control.modules so it appears in the module
 * switcher. Admin users see all modules automatically; other users see it when
 * granted via user_modules, or implicitly when assigned as an exhibitor
 * coordinator on at least one expodirectory row.
 */
class ExpoModuleSeed extends Migration
{
    protected $DBGroup = 'control';

    public function up()
    {
        $db = Database::connect('control');
        if ($db->table('modules')->where('Code', 'expo')->get()->getRowArray()) return;
        $maxSort = (int) ($db->table('modules')->selectMax('SortOrder', 'mx')->get()->getRowArray()['mx'] ?? 0);
        $db->table('modules')->insert([
            'Code'        => 'expo',
            'Name'        => 'Exhibitor Portal',
            'Description' => 'Manage the exhibitor directory for an event.',
            'SortOrder'   => $maxSort + 10,
        ]);
    }

    public function down()
    {
        Database::connect('control')->table('modules')->where('Code', 'expo')->delete();
    }
}
