<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Registration module, phase 1: bitswork_registration.guests columns.
 * Additive and re-runnable. guests has no EventID; the guest list
 * (InvitedByCompanyID) scopes a row to an event, so indexes use it.
 *
 * Run: php spark migrate
 */
class RegistrationPhase1GuestColumns extends Migration
{
    private const COLS = [
        'Source'                => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        'ConfirmationStatus'    => ['type' => 'ENUM', 'constraint' => ['not_required', 'pending', 'confirmed'], 'null' => false, 'default' => 'not_required'],
        'ConfirmationToken'     => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
        'ConfirmationSentAt'    => ['type' => 'DATETIME', 'null' => true],
        'ConfirmedAt'           => ['type' => 'DATETIME', 'null' => true],
        'RegistrationOrderID'   => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
        'RegisteredByType'      => ['type' => 'ENUM', 'constraint' => ['self', 'exhibitor_coordinator', 'list_manager', 'staff'], 'null' => true],
        'RegisteredByContactID' => ['type' => 'INT', 'null' => true],
        'ComplimentaryReason'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        'ClaimedPresentationID' => ['type' => 'INT', 'null' => true],
        // Badge name = Nickname when set, otherwise GivenName (skipped if already present).
        'Nickname'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        'NameOnBadge'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        // Swap tracking: outgoing row is soft-deleted and points at its replacement.
        'ReplacedByGuestID'     => ['type' => 'INT', 'null' => true],
        'ReplacesGuestID'       => ['type' => 'INT', 'null' => true],
    ];

    private const INDEXES = [
        'uq_guests_confirmation_token' => 'ADD UNIQUE INDEX uq_guests_confirmation_token (ConfirmationToken)',
        'ix_guests_reg_order'          => 'ADD INDEX ix_guests_reg_order (RegistrationOrderID)',
        'ix_guests_claimed_pres'       => 'ADD INDEX ix_guests_claimed_pres (ClaimedPresentationID)',
        'ix_guests_list_confirm'       => 'ADD INDEX ix_guests_list_confirm (InvitedByCompanyID, ConfirmationStatus)',
        'ix_guests_replaced_by'        => 'ADD INDEX ix_guests_replaced_by (ReplacedByGuestID)',
        'ix_guests_list_source'        => 'ADD INDEX ix_guests_list_source (InvitedByCompanyID, Source)',
    ];

    public function up()
    {
        $db = Database::connect('registration');
        $forge = Database::forge('registration');
        $add = [];
        foreach (self::COLS as $name => $def) {
            if (!$db->fieldExists($name, 'guests')) $add[$name] = $def;
        }
        if ($add) $forge->addColumn('guests', $add);

        $existing = array_keys($db->getIndexData('guests'));
        foreach (self::INDEXES as $name => $sql) {
            if (!in_array($name, $existing, true)) $db->query("ALTER TABLE guests $sql");
        }
    }

    public function down()
    {
        $db = Database::connect('registration');
        $existing = array_keys($db->getIndexData('guests'));
        foreach (array_keys(self::INDEXES) as $name) {
            if (in_array($name, $existing, true)) $db->query("ALTER TABLE guests DROP INDEX $name");
        }
        $drop = array_values(array_filter(array_keys(self::COLS), fn ($c) => $db->fieldExists($c, 'guests')));
        if ($drop) Database::forge('registration')->dropColumn('guests', $drop);
    }
}
