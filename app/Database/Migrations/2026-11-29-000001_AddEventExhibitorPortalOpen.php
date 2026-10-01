<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * events.ExhibitorPortalOpen — exhibitors can use the event's Exhibitor
 * Portal only when this is 1 (and the event isn't closed). New events
 * default to 0; existing events are switched on so nobody is locked out.
 *
 * Equivalent SQL (conference DB, bitswork_contac2):
 *   ALTER TABLE events ADD COLUMN ExhibitorPortalOpen TINYINT(1) NOT NULL DEFAULT 0;
 *   UPDATE events SET ExhibitorPortalOpen = 1;
 */
class AddEventExhibitorPortalOpen extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('ExhibitorPortalOpen', 'events')) {
            $this->forge->addColumn('events', [
                'ExhibitorPortalOpen' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            ]);
            $this->db->query('UPDATE events SET ExhibitorPortalOpen = 1');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('ExhibitorPortalOpen', 'events')) {
            $this->forge->dropColumn('events', 'ExhibitorPortalOpen');
        }
    }
}
