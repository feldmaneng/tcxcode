<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Add lifecycle Status to presentations.
 *   Status VARCHAR(20)  — 'active' (default), 'not_selected', 'withdrawn'
 *   StatusChangedAt DATETIME NULL — stamped on every Status transition
 *
 * 'not_selected' and 'withdrawn' make the presentation invisible to authors
 * and coordinators in the Author Portal, and read-only for chairs.
 */
class AddPresentationStatus extends Migration
{
    public function up()
    {
        $forge = $this->forge;
        $db    = Database::connect();

        try {
            $forge->addColumn('presentations', [
                'Status' => [
                    'type' => 'VARCHAR', 'constraint' => 20,
                    'null' => false, 'default' => 'active',
                ],
                'StatusChangedAt' => ['type' => 'DATETIME', 'null' => true],
            ]);
        } catch (\Throwable $e) {
            log_message('warning', 'presentations.Status add skipped: ' . $e->getMessage());
        }

        try { $db->query('ALTER TABLE presentations ADD KEY idx_presentations_status (Status)'); } catch (\Throwable $e) {}
    }

    public function down()
    {
        try { $this->forge->dropColumn('presentations', ['Status', 'StatusChangedAt']); } catch (\Throwable $e) {}
    }
}
