<?php
namespace App\Libraries;

use App\Models\UserModuleModel;

/**
 * Effective module membership for a user.
 *
 * Mirrors the logic used by MeController::modules() so that API-side module
 * authorization (BaseApiController::requireModule) agrees with what the module
 * switcher shows. Explicit user_modules rows are augmented with implicit grants:
 *
 *  - `guests`        when the user manages at least one company guest list
 *  - `author-portal` when the user is an event manager/chair, session
 *                    coordinator, or an author on a non-hidden presentation
 */
final class ModuleAccess
{
    /** @var array<int,string[]> */
    private static array $cache = [];

    /** @return string[] module codes (explicit + implicit) */
    public static function codesForUser(int $userId): array
    {
        if (isset(self::$cache[$userId])) return self::$cache[$userId];

        $codes = (new UserModuleModel())->codesForUser($userId);

        if (in_array('admin', $codes, true)) {
            return self::$cache[$userId] = $codes;
        }

        if (!in_array('guests', $codes, true) && self::isGuestListManager($userId)) {
            $codes[] = 'guests';
        }

        if (!in_array('author-portal', $codes, true) && self::isAuthorPortalMember($userId)) {
            $codes[] = 'author-portal';
        }

        if (!in_array('expo', $codes, true)
            && (self::isExpoCoordinator($userId) || self::isEventResponsible($userId))) {
            $codes[] = 'expo';
        }


        return self::$cache[$userId] = $codes;
    }

    public static function has(int $userId, string $code): bool
    {
        return in_array($code, self::codesForUser($userId), true);
    }

    private static function isGuestListManager(int $userId): bool
    {
        try {
            // companyguestlists_managers lives in the 'registration' DB group.
            return db_connect('registration')->table('companyguestlists_managers')
                ->where('UserID', $userId)->countAllResults() > 0;
        } catch (\Throwable $e) {
            log_message('error', '[modules] guest-list manager lookup failed: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * True when the user runs at least one event (event manager or general
     * chair). Event chairs are excluded: they only handle the program.
     */
    private static function isEventResponsible(int $userId): bool
    {
        try {
            return db_connect()->table('events')
                ->groupStart()
                    ->where('EventManagerID', $userId)
                    ->orWhere('GeneralChairID', $userId)
                ->groupEnd()
                ->countAllResults() > 0;
        } catch (\Throwable $e) {
            log_message('error', '[modules] event responsibility lookup failed: ' . $e->getMessage());
            return false;
        }
    }




    /**
     * True when the user is assigned as an exhibitor coordinator on at least
     * one expodirectory row. expodirectory_coordinators lives in the
     * `registration` DB group (bitswork_registration); the user -> contact link
     * lives in `control`, so this is two queries, never a join.
     */
    private static function isExpoCoordinator(int $userId): bool
    {
        try {
            $user = db_connect('control')->table('users')
                ->select('ContactID')->where('UserID', $userId)->get()->getRowArray();
            $contactId = (int) ($user['ContactID'] ?? 0);
            if ($contactId <= 0) return false;

            return db_connect('registration')->table('expodirectory_coordinators')
                ->where('ContactID', $contactId)->countAllResults() > 0;
        } catch (\Throwable $e) {
            log_message('error', '[modules] expo coordinator lookup failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function isAuthorPortalMember(int $userId): bool
    {
        try {
            $appDb = db_connect();

            $eventCount = $appDb->table('events')
                ->groupStart()
                    ->where('EventManagerID', $userId)
                    ->orWhere('EventChair1ID', $userId)
                    ->orWhere('EventChair2ID', $userId)
                ->groupEnd()
                ->countAllResults();
            if ($eventCount > 0) return true;

            $sessionCount = $appDb->table('sessions')
                ->groupStart()
                    ->where('Coordinator1ID', $userId)
                    ->orWhere('Coordinator2ID', $userId)
                ->groupEnd()
                ->countAllResults();
            if ($sessionCount > 0) return true;

            $user = db_connect('control')->table('users')
                ->where('UserID', $userId)->get()->getRowArray();
            $contactId = isset($user['ContactID']) ? (int) $user['ContactID'] : 0;
            if ($contactId > 0) {
                $authorCount = $appDb->table('authors')
                    ->join('presentations', 'presentations.PresentationID = authors.PresentationID', 'left')
                    ->where('authors.ContactID', $contactId)
                    ->groupStart()
                        ->where('presentations.Status', 'active')
                        ->orWhere('presentations.Status IS NULL', null, false)
                    ->groupEnd()
                    ->countAllResults();
                if ($authorCount > 0) return true;
            }
        } catch (\Throwable $e) {
            log_message('error', '[modules] author-portal lookup failed: ' . $e->getMessage());
            return false;
        }


        return false;
    }
}
