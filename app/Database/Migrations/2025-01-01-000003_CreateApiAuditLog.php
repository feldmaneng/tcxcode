<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiAuditLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'client_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'user_id'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'method'       => ['type' => 'VARCHAR', 'constraint' => 10],
            'path'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'payload_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'ip'           => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'status'       => ['type' => 'INT', 'constraint' => 4],
            'created_at'   => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['created_at']);
        $this->forge->addKey(['client_id']);
        $this->forge->createTable('api_audit_log');
    }
    public function down() { $this->forge->dropTable('api_audit_log'); }
}
