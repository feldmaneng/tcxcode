<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * events.GraphicDesignerID — the user who gets read-only Exhibitor Portal
 * access for this event and can run the directory changes report.
 *
 * Equivalent SQL (conference DB, bitswork_contac2):
 *   ALTER TABLE events ADD COLUMN GraphicDesignerID INT UNSIGNED NULL DEFAULT NULL;
 */
class AddEventGraphicDesigner extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('GraphicDesignerID', 'events')) {
            $this->forge->addColumn('events', [
                'GraphicDesignerID' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('GraphicDesignerID', 'events')) {
            $this->forge->dropColumn('events', 'GraphicDesignerID');
        }
    }
}
