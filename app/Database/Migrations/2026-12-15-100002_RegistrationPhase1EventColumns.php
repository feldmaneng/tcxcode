<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Registration module, phase 1: bitswork_contac2 events/presentations columns.
 *  - events.RegistrationOpen (default 0 = closed for every existing event)
 *  - events.RegistrationShortName (unique, e.g. mesa2027 for /register/mesa2027)
 *  - presentations.CompRegistrationsAllowed (first-come author discounts, default 1)
 *  - presentations.CompRegistrationsOverride (Admin/Event Planner only)
 *
 * Run: php spark migrate
 */
class RegistrationPhase1EventColumns extends Migration
{
    public function up()
    {
        $db = Database::connect();
        $forge = Database::forge();

        $ev = [];
        if (!$db->fieldExists('RegistrationOpen', 'events')) {
            $ev['RegistrationOpen'] = ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0];
        }
        if (!$db->fieldExists('RegistrationShortName', 'events')) {
            $ev['RegistrationShortName'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true];
        }
        if ($ev) $forge->addColumn('events', $ev);
        if (!array_key_exists('uq_events_reg_shortname', $db->getIndexData('events'))) {
            $db->query('ALTER TABLE events ADD UNIQUE INDEX uq_events_reg_shortname (RegistrationShortName)');
        }

        $pr = [];
        if (!$db->fieldExists('CompRegistrationsAllowed', 'presentations')) {
            $pr['CompRegistrationsAllowed'] = ['type' => 'TINYINT', 'null' => false, 'default' => 1];
        }
        if (!$db->fieldExists('CompRegistrationsOverride', 'presentations')) {
            $pr['CompRegistrationsOverride'] = ['type' => 'TINYINT', 'null' => true];
        }
        if ($pr) $forge->addColumn('presentations', $pr);
    }

    public function down()
    {
        $db = Database::connect();
        if (array_key_exists('uq_events_reg_shortname', $db->getIndexData('events'))) {
            $db->query('ALTER TABLE events DROP INDEX uq_events_reg_shortname');
        }
        foreach (['events' => ['RegistrationOpen', 'RegistrationShortName'], 'presentations' => ['CompRegistrationsAllowed', 'CompRegistrationsOverride']] as $t => $cols) {
            $drop = array_values(array_filter($cols, fn ($c) => $db->fieldExists($c, $t)));
            if ($drop) Database::forge()->dropColumn($t, $drop);
        }
    }
}
