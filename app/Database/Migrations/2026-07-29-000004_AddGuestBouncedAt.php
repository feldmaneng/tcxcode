<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds BouncedAt to the guests table so the Mailgun event webhook can mark
 * registrations whose confirmation email later bounced.
 */
class AddGuestBouncedAt extends Migration
{
    protected $DBGroup = 'registration';

    public function up()
    {
        if (!$this->db->fieldExists('BouncedAt', 'guests')) {
            $this->forge->addColumn('guests', [
                'BouncedAt' => ['type' => 'DATETIME', 'null' => true],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('BouncedAt', 'guests')) {
            $this->forge->dropColumn('guests', 'BouncedAt');
        }
    }
}
