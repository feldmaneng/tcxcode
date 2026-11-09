<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Guest / attendee TestConX.org account provisioning (default DB, bitswork_contac2).
 *  - WpAccountProvision: one row per Event + WordPress account. Records what was
 *    done and which email was sent, so re-runs never email the same person twice.
 *  - WpAccountSettings: per-event level, ccap, program URL and the two email templates.
 */
class AddWpAccountProvision extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('WpAccountProvision')) {
            $this->forge->addField([
                'ID'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'EventID'        => ['type' => 'INT', 'unsigned' => true],
                'Year'           => ['type' => 'INT'],
                'WpUserID'       => ['type' => 'INT', 'unsigned' => true],
                'ContactID'      => ['type' => 'INT', 'null' => true],
                'GuestID'        => ['type' => 'INT', 'null' => true],
                'Email'          => ['type' => 'VARCHAR', 'constraint' => 120],
                'GivenName'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'FamilyName'     => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'Username'       => ['type' => 'VARCHAR', 'constraint' => 60],
                'Action'         => ['type' => 'VARCHAR', 'constraint' => 12], // created | granted | unchanged
                'Level'          => ['type' => 'TINYINT', 'null' => true],
                'Ccap'           => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'EmailKind'      => ['type' => 'VARCHAR', 'constraint' => 10], // new | existing
                'EmailStatus'    => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'pending'], // pending | sent | failed | skipped
                'EmailMessageID' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
                'EmailSentAt'    => ['type' => 'DATETIME', 'null' => true],
                'EmailError'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'CreatedBy'      => ['type' => 'INT', 'null' => true],
                'CreatedAt'      => ['type' => 'DATETIME', 'null' => true],
                'UpdatedAt'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('ID', true);
            $this->forge->addUniqueKey(['EventID', 'WpUserID'], 'uq_wpacct_event_user');
            $this->forge->addKey('EmailMessageID');
            $this->forge->createTable('WpAccountProvision');
        }
        if (!$this->db->tableExists('WpAccountSettings')) {
            $this->forge->addField([
                'EventID'         => ['type' => 'INT', 'unsigned' => true],
                'Level'           => ['type' => 'TINYINT', 'null' => true],
                'Ccap'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'ProgramUrl'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'NewSubject'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
                'NewBody'         => ['type' => 'TEXT', 'null' => true],
                'ExistingSubject' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
                'ExistingBody'    => ['type' => 'TEXT', 'null' => true],
                'UpdatedBy'       => ['type' => 'INT', 'null' => true],
                'UpdatedAt'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('EventID', true);
            $this->forge->createTable('WpAccountSettings');
        }
    }

    public function down()
    {
        $this->forge->dropTable('WpAccountProvision', true);
        $this->forge->dropTable('WpAccountSettings', true);
    }
}
