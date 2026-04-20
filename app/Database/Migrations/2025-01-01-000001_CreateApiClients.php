<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiClients extends Migration
{
    protected $DBGroup = 'control';

    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'api_key'           => ['type' => 'VARCHAR', 'constraint' => 64],
            // Raw HMAC secret encrypted with CI4 Encrypter (app.encryption.key).
            // HMAC verification needs the original secret, so a one-way hash will not work here.
            'secret_encrypted'  => ['type' => 'TEXT'],
            'active'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME'],
            'rotated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('api_key');
        $this->forge->createTable('api_clients');
    }

    public function down() { $this->forge->dropTable('api_clients'); }
}
