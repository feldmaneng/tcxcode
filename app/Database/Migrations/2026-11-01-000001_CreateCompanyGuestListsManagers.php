<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Guest-list managers per companyguestlists row.
 * Up to 4 managers per row is enforced in application code (CompanyGuestListsManagersController).
 * Users live in a different DB group (control.users), so we don't add a FK to users.
 */
class CreateCompanyGuestListsManagers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'CompanyGuestListsID'  => ['type' => 'INT', 'unsigned' => true],
            'UserID'          => ['type' => 'INT', 'unsigned' => true],
            'AddedBy'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'Added'           => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['CompanyGuestListsID', 'UserID'], 'uq_cgl_company_user');
        $this->forge->addKey(['CompanyGuestListsID']);
        $this->forge->addKey(['UserID']);
        $this->forge->createTable('companyguestlists_managers', true);

        // Best-effort FK to companyguestlists.CompanyID (legacy engine may reject).
        try {
            $this->db->query('ALTER TABLE companyguestlists_managers ADD CONSTRAINT fk_cgl_company FOREIGN KEY (CompanyGuestListsID) REFERENCES companyguestlists(CompanyID) ON DELETE CASCADE');
        } catch (\Throwable $e) {}
    }

    public function down()
    {
        $this->forge->dropTable('companyguestlists_managers', true);
    }
}
