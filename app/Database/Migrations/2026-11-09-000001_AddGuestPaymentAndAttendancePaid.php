<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Post-Event Audit payment columns.
 *
 *  - bitswork_registration.guests:      PaidAmount DECIMAL(12,2) NULL, PaidCurrency VARCHAR(3) NULL default 'USD'
 *  - bitswork_contac2.attendance:       PaidAmount DECIMAL(12,2) NULL, PaidCurrency VARCHAR(3) NULL
 *
 * attendance.Payment keeps its existing data but is DEPRECATED (no longer written).
 * down() removes only the columns added here.
 *
 * Run: php spark migrate
 */
class AddGuestPaymentAndAttendancePaid extends Migration
{
    public function up()
    {
        $reg = Database::forge('registration');
        $regDb = Database::connect('registration');
        if (!$regDb->fieldExists('PaidAmount', 'guests')) {
            $reg->addColumn('guests', [
                'PaidAmount'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
                'PaidCurrency' => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true, 'default' => 'USD'],
            ]);
        }

        $def = Database::forge();
        $defDb = Database::connect();
        if (!$defDb->fieldExists('PaidAmount', 'attendance')) {
            $def->addColumn('attendance', [
                'PaidAmount'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
                'PaidCurrency' => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true],
            ]);
        }
    }

    public function down()
    {
        $regDb = Database::connect('registration');
        if ($regDb->fieldExists('PaidAmount', 'guests')) {
            Database::forge('registration')->dropColumn('guests', ['PaidAmount', 'PaidCurrency']);
        }
        $defDb = Database::connect();
        if ($defDb->fieldExists('PaidAmount', 'attendance')) {
            Database::forge()->dropColumn('attendance', ['PaidAmount', 'PaidCurrency']);
        }
    }
}
