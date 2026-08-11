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

        return self::$cache[$userId] = $codes;
    }

    public static function has(int $userId, string $code): bool
    {
        return in_array($code, self::codesForUser($userId), true);
    }

    private static function isGuestListManager(int $userId): bool
    {
        try {
            return db_connect()->table('companyguestlists_managers')
                ->where('UserID', $userId)->countAllResults() > 0;
        } catch (\Throwable $e) {
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
            return false;
        }

        return false;
    }
}
