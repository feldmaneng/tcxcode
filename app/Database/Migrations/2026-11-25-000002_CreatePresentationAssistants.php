<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

/** Default conference DB only; no cross-database foreign keys. */
class CreatePresentationAssistants extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('presentation_assistants')) return;
        $this->forge->addField([
            'AssistantID' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'PresentationID' => ['type' => 'INT'],
            'ContactID' => ['type' => 'INT', 'null' => true],
            'GivenName' => ['type' => 'VARCHAR', 'constraint' => 100],
            'FamilyName' => ['type' => 'VARCHAR', 'constraint' => 100],
            'Email' => ['type' => 'VARCHAR', 'constraint' => 100],
            'Status' => ['type' => 'ENUM', 'constraint' => ['approved', 'pending_crm'], 'default' => 'pending_crm'],
            'CreatedByUserID' => ['type' => 'INT'],
            'CreatedByContactID' => ['type' => 'INT', 'null' => true],
            'OfficeNotifiedAt' => ['type' => 'DATETIME', 'null' => true],
            'OfficeNotificationError' => ['type' => 'TEXT', 'null' => true],
            'CreatedAt' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('AssistantID', true);
        $this->forge->addKey('ContactID');
        $this->forge->addUniqueKey(['PresentationID', 'Email'], 'uq_presentation_assistant_email');
        $this->forge->createTable('presentation_assistants');
    }
    public function down() { $this->forge->dropTable('presentation_assistants', true); }
}
