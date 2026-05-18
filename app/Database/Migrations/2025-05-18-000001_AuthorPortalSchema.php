<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Author Portal schema changes:
 *
 *  1. events:
 *     - Fix FullName (currently int(11), should be varchar)
 *     - Add EventChair1ID, EventChair2ID, EventManagerID (FK users.UserID)
 *
 *  2. presentations:
 *     - Add SessionID (FK sessions.SessionID, NULL)
 *
 *  3. sessions (new table):
 *     - SessionID, EventID, SessionNumber, SessionName,
 *       Coordinator1ID, Coordinator2ID, StartTime, EndTime, Room
 *
 * Roles for the Author Portal are derived (NOT stored in a roles table):
 *   - Admin       = existing global Admin module
 *   - EventMgr    = events.EventManagerID  = UserID
 *   - Chair       = events.EventChair{1,2}ID = UserID
 *   - Coordinator = sessions.Coordinator{1,2}ID = UserID
 *   - Author      = authors.ContactID matches the user's ContactID
 */
class AuthorPortalSchema extends Migration
{
    public function up()
    {
        $forge = $this->forge;
        $db    = Database::connect();

        // ---- 1. events ----------------------------------------------------
        try {
            $forge->modifyColumn('events', [
                'FullName' => [
                    'name'       => 'FullName',
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('warning', 'events.FullName modify skipped: ' . $e->getMessage());
        }

        $forge->addColumn('events', [
            'EventChair1ID'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'FacilityAddress'],
            'EventChair2ID'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'EventChair1ID'],
            'EventManagerID' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'EventChair2ID'],
        ]);

        try { $db->query('ALTER TABLE events ADD KEY idx_events_chair1   (EventChair1ID)'); } catch (\Throwable $e) {}
        try { $db->query('ALTER TABLE events ADD KEY idx_events_chair2   (EventChair2ID)'); } catch (\Throwable $e) {}
        try { $db->query('ALTER TABLE events ADD KEY idx_events_manager  (EventManagerID)'); } catch (\Throwable $e) {}

        // ---- 2. sessions (new) -------------------------------------------
        $forge->addField([
            'SessionID'      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'EventID'        => ['type' => 'INT', 'unsigned' => true],
            'SessionNumber'  => ['type' => 'VARCHAR', 'constraint' => 20],
            'SessionName'    => ['type' => 'VARCHAR', 'constraint' => 200],
            'Coordinator1ID' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'Coordinator2ID' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'StartTime'      => ['type' => 'DATETIME', 'null' => true],
            'EndTime'        => ['type' => 'DATETIME', 'null' => true],
            'Room'           => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'Added'          => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'Updated'        => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $forge->addPrimaryKey('SessionID');
        $forge->addUniqueKey(['EventID', 'SessionNumber'], 'uq_sessions_event_number');
        $forge->addKey(['EventID']);
        $forge->addKey(['Coordinator1ID']);
        $forge->addKey(['Coordinator2ID']);
        $forge->createTable('sessions', true);

        // FK to events (best-effort; legacy data may block)
        try {
            $db->query('ALTER TABLE sessions ADD CONSTRAINT fk_sessions_event FOREIGN KEY (EventID) REFERENCES events(EventID) ON DELETE CASCADE ON UPDATE CASCADE');
        } catch (\Throwable $e) {
            log_message('warning', 'fk_sessions_event not added: ' . $e->getMessage());
        }

        // ---- 3. presentations.SessionID ----------------------------------
        try {
            $forge->addColumn('presentations', [
                'SessionID' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'Session'],
            ]);
            $db->query('ALTER TABLE presentations ADD KEY idx_presentations_session_id (SessionID)');
        } catch (\Throwable $e) {
            log_message('warning', 'presentations.SessionID add skipped: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $forge = $this->forge;
        $db    = Database::connect();

        try { $db->query('ALTER TABLE presentations DROP COLUMN SessionID'); } catch (\Throwable $e) {}
        try { $forge->dropTable('sessions', true); } catch (\Throwable $e) {}
        try { $forge->dropColumn('events', ['EventChair1ID', 'EventChair2ID', 'EventManagerID']); } catch (\Throwable $e) {}
    }
}
