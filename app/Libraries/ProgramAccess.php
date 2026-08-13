<?php
namespace App\Libraries;

use App\Models\UserModuleModel;
use Config\Database;

/**
 * Shared helpers for "who runs the program" checks and for resolving the
 * effective coordinator(s) of a presentation.
 *
 * A presentation normally inherits its coordinators from its session
 * (sessions.Coordinator1ID / Coordinator2ID). When a presentation is moved to
 * another session the mover may keep the original reviewer(s): those are then
 * stored on the presentation row itself (presentations.Coordinator1ID /
 * Coordinator2ID). CoordinatorsPinned = 1 means the session's own coordinators
 * no longer apply to that presentation.
 */
class ProgramAccess
{
    /** Effective coordinator user ids for a presentation. */
    public static function presentationCoordinatorIds(int $presentationId): array
    {
        $db = Database::connect();
        $pres = $db->table('presentations')
            ->select('SessionID' . (self::hasCoordinatorColumns()
                ? ', Coordinator1ID, Coordinator2ID, CoordinatorsPinned' : ''))
            ->where('PresentationID', $presentationId)
            ->get()->getRowArray();
        if (!$pres) return [];
        return self::coordinatorIdsFromRow($pres);
    }

    /**
     * Same as presentationCoordinatorIds() but from an already-loaded row.
     * The row must contain SessionID and (when the columns exist)
     * Coordinator1ID / Coordinator2ID / CoordinatorsPinned.
     */
    public static function coordinatorIdsFromRow(array $pres): array
    {
        $db  = Database::connect();
        $own = [];
        foreach (['Coordinator1ID', 'Coordinator2ID'] as $c) {
            $v = isset($pres[$c]) ? (int) $pres[$c] : 0;
            if ($v > 0) $own[] = $v;
        }
        $pinned = !empty($pres['CoordinatorsPinned']);

        $fromSession = [];
        if (!$pinned && !empty($pres['SessionID'])) {
            $sess = $db->table('sessions')
                ->select('Coordinator1ID, Coordinator2ID')
                ->where('SessionID', (int) $pres['SessionID'])
                ->get()->getRowArray();
            if ($sess) {
                foreach (['Coordinator1ID', 'Coordinator2ID'] as $c) {
                    $v = isset($sess[$c]) ? (int) $sess[$c] : 0;
                    if ($v > 0) $fromSession[] = $v;
                }
            }
        }

        return array_values(array_unique(array_merge($own, $fromSession)));
    }

    /** True when the presentation columns for per-presentation coordinators exist. */
    public static function hasCoordinatorColumns(): bool
    {
        static $has = null;
        if ($has !== null) return $has;
        try {
            $db  = Database::connect();
            $has = $db->fieldExists('Coordinator1ID', 'presentations');
        } catch (\Throwable $e) {
            $has = false;
        }
        return $has;
    }

    /**
     * Admin, event manager, event chair or general chair for the given event —
     * the roles allowed to move presentations between sessions and to reorder
     * a session's running order.
     */
    public static function canManageProgram(int $userId, ?int $eventId): bool
    {
        if ($userId <= 0) return false;
        if ((new UserModuleModel())->userHasModule($userId, 'admin')) return true;
        if (!$eventId) return false;

        $ev = Database::connect()->table('events')
            ->select('EventChair1ID, EventChair2ID, EventManagerID, GeneralChairID')
            ->where('EventID', $eventId)->get()->getRowArray();
        if (!$ev) return false;

        foreach (['EventChair1ID', 'EventChair2ID', 'EventManagerID', 'GeneralChairID'] as $c) {
            if ((int) ($ev[$c] ?? 0) === $userId) return true;
        }
        return false;
    }

    /** Event id for a session (null when unknown). */
    public static function eventIdForSession(?int $sessionId): ?int
    {
        if (!$sessionId) return null;
        $row = Database::connect()->table('sessions')
            ->select('EventID')->where('SessionID', $sessionId)->get()->getRowArray();
        return $row ? (int) $row['EventID'] : null;
    }
}
