<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiClients extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'api_key'           => ['type' => 'VARCHAR', 'constraint' => 64],
            'secret_encrypted'  => ['type' => 'TEXT'],   // base64( CI4 Encrypter ciphertext of raw secret )
            'secret_hash'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // optional bcrypt for spot checks
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
