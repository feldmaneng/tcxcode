<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Seed the `author-portal` row into control.modules so it shows up in the
 * module switcher. Admin-role users see it automatically (see MeController);
 * non-admins must be granted access via user_modules.
 */
class AuthorPortalModuleSeed extends Migration
{
    public function up()
    {
        $db = Database::connect('control');
        $existing = $db->table('modules')->where('Code', 'author-portal')->get()->getRowArray();
        if ($existing) return;

        $maxSort = (int) ($db->table('modules')->selectMax('SortOrder', 'mx')->get()->getRowArray()['mx'] ?? 0);
        $db->table('modules')->insert([
            'Code'        => 'author-portal',
            'Name'        => 'Author Portal',
            'Description' => 'Conference authors, sessions, deliverables.',
            'SortOrder'   => $maxSort + 10,
        ]);
    }

    public function down()
    {
        Database::connect('control')->table('modules')->where('Code', 'author-portal')->delete();
    }
}
