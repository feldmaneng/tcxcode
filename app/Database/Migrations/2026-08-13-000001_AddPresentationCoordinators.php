<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Per-presentation coordinators.
 *
 * presentations.Coordinator1ID / Coordinator2ID — when set, these users act as
 * coordinators for that single presentation (used when a presentation is moved
 * to a different session but keeps its original reviewer(s)).
 * presentations.CoordinatorsPinned — 1 means "ignore the session's own
 * coordinators for this presentation".
 *
 * The presentations table lives in the default group (bitswork_contac2).
 * All adds are guarded so the migration is safe to re-run.
 */
class AddPresentationCoordinators extends Migration
{
    public function up()
    {
        $db    = Database::connect();
        $forge = Database::forge();

        if (!$db->tableExists('presentations')) return;

        $cols = [];
        if (!$db->fieldExists('Coordinator1ID', 'presentations')) {
            $cols['Coordinator1ID'] = ['type' => 'INT', 'null' => true];
        }
        if (!$db->fieldExists('Coordinator2ID', 'presentations')) {
            $cols['Coordinator2ID'] = ['type' => 'INT', 'null' => true];
        }
        if (!$db->fieldExists('CoordinatorsPinned', 'presentations')) {
            $cols['CoordinatorsPinned'] = ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0];
        }
        if ($cols) {
            try {
                $forge->addColumn('presentations', $cols);
            } catch (\Throwable $e) {
                log_message('warning', 'presentations coordinator columns add skipped: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        $forge = Database::forge();
        foreach (['Coordinator1ID', 'Coordinator2ID', 'CoordinatorsPinned'] as $c) {
            try { $forge->dropColumn('presentations', [$c]); } catch (\Throwable $e) {}
        }
    }
}
