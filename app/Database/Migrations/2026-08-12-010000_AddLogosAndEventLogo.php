<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Adds the logos library and links it to events via events.LogoID.
 */
class AddLogosAndEventLogo extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'LogoID' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'Name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'Url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => false,
            ],
            'StorageKey' => [
                'type' => 'VARCHAR',
                'constraint' => 260,
                'null' => true,
            ],
            'MimeType' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'Width' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'Height' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'IsDefault' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
            ],
            'IsActive' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
            ],
            'CreatedAt' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'UpdatedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('LogoID', true);
        $this->forge->createTable('logos');

        $this->forge->addColumn('events', [
            'LogoID' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'GuestFormKorean',
            ],
        ]);
        $this->forge->addForeignKey('LogoID', 'logos', 'LogoID', 'CASCADE', 'SET NULL', 'fk_events_logo');
    }

    public function down()
    {
        $this->forge->dropForeignKey('events', 'fk_events_logo');
        $this->forge->dropColumn('events', 'LogoID');
        $this->forge->dropTable('logos');
    }
}
