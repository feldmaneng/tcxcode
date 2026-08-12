<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\UserModuleModel;
use App\Models\UserWikiPermissionModel;

/**
 * MeController — returns information about the currently signed-in user.
 *
 * Identity comes from the X-Acting-User header (verified by HmacAuthFilter).
 */
class MeController extends BaseApiController
{
    /** POST /api/v1/me/modules */
    public function modules()
    {
        $userId = ApiAuthContext::actingUserId();
        if (!$userId) return $this->jsonError(401, 'acting_user_required');

        $user = db_connect('control')->table('users')->where('UserID', $userId)->get()->getRowArray();
        if (!$user) return $this->jsonError(404, 'user_not_found');

        $ctrl = db_connect('control');

        // Admin role auto-grants visibility of ALL modules (the user still
        // has to do per-module work to actually use them, but they appear
        // in the switcher without an explicit user_modules row).
        $isAdmin = (new UserModuleModel())->userHasModule($userId, 'admin');

        if ($isAdmin) {
            $rows = $ctrl->table('modules')
                ->select('Code AS code, Name AS name, Description AS description, SortOrder AS sort_order')
                ->orderBy('SortOrder', 'ASC')
                ->get()->getResultArray();
        } else {
            $rows = $ctrl->table('user_modules um')
                ->select('m.Code AS code, m.Name AS name, m.Description AS description, m.SortOrder AS sort_order')
                ->join('modules m', 'm.ModuleID = um.ModuleID')
                ->where('um.UserID', $userId)
                ->orderBy('m.SortOrder', 'ASC')
                ->get()->getResultArray();

            // Auto-grant `guests` module if the user is assigned as a
            // guest-list manager on at least one companyguestlists row.
            $hasGuests = false;
            foreach ($rows as $r) { if (($r['code'] ?? '') === 'guests') { $hasGuests = true; break; } }
            if (!$hasGuests) {
                // companyguestlists_managers lives in the 'registration' DB group,
                // not the default connection.
                $mgrCount = 0;
                try {
                    $mgrCount = db_connect('registration')->table('companyguestlists_managers')
                        ->where('UserID', $userId)->countAllResults();
                } catch (\Throwable $e) {
                    log_message('error', '[me/modules] guest-list manager lookup failed: ' . $e->getMessage());
                }
                if ($mgrCount > 0) {
                    $mod = $ctrl->table('modules')
                        ->select('Code AS code, Name AS name, Description AS description, SortOrder AS sort_order')
                        ->where('Code', 'guests')->get()->getRowArray();
                    if ($mod) $rows[] = $mod;
                }
            }


            // Auto-grant `author-portal` module if the user has any author-portal
            // role: event manager, event chair, session coordinator, or author on
            // an active presentation (Authors = CRM contacts, matched via ContactID).
            $hasAuthorPortal = false;
            foreach ($rows as $r) { if (($r['code'] ?? '') === 'author-portal') { $hasAuthorPortal = true; break; } }
            if (!$hasAuthorPortal) {
                $appDb    = db_connect();
                $isCandidate = false;

                // Event manager / chair
                $eventCount = $appDb->table('events')
                    ->groupStart()
                        ->where('EventManagerID', $userId)
                        ->orWhere('EventChair1ID', $userId)
                        ->orWhere('EventChair2ID', $userId)
                    ->groupEnd()
                    ->countAllResults();
                if ($eventCount > 0) $isCandidate = true;

                // Session coordinator
                if (!$isCandidate) {
                    $sessionCount = $appDb->table('sessions')
                        ->groupStart()
                            ->where('Coordinator1ID', $userId)
                            ->orWhere('Coordinator2ID', $userId)
                        ->groupEnd()
                        ->countAllResults();
                    if ($sessionCount > 0) $isCandidate = true;
                }

                // Author on an active presentation (via ContactID)
                if (!$isCandidate) {
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
                        if ($authorCount > 0) $isCandidate = true;
                    }
                }

                if ($isCandidate) {
                    $mod = $ctrl->table('modules')
                        ->select('Code AS code, Name AS name, Description AS description, SortOrder AS sort_order')
                        ->where('Code', 'author-portal')->get()->getRowArray();
                    if ($mod) $rows[] = $mod;
                }
            }
        }

        // contacts (default DB) is the source of truth for nicknames; users
        // (control DB) links to it via ContactID.
        $nickname = PresentationRecipientsController::nicknameForContact(
            isset($user['ContactID']) ? (int) $user['ContactID'] : null
        );

        return $this->respond([
            'user' => [
                'id'                    => (int) $user['UserID'],
                'username'              => $user['UserName'],
                'given_name'            => $user['GivenName'] ?? $user['UserName'],
                'family_name'           => $user['FamilyName'] ?? '',
                'nickname'              => $nickname,
                'email'                 => $user['Email'] ?? null,
                'auth_provider'         => $user['auth_provider'] ?? 'local',
                'must_change_password'  => (bool) ($user['MustChangePassword'] ?? false),
                'totp_enabled'          => (bool) ($user['TOTPEnabled'] ?? false),
            ],
            'modules' => $rows,
        ]);
    }

    /** POST /api/v1/me/wikis */
    public function wikis()
    {
        $userId = ApiAuthContext::actingUserId();
        if (!$userId) return $this->jsonError(401, 'acting_user_required');

        $isAdmin = (new UserModuleModel())->userHasModule($userId, 'admin');
        $rows = (new UserWikiPermissionModel())->wikisForUser($userId, $isAdmin);

        return $this->respond([
            'wikis' => array_map(fn($r) => [
                'id'          => (int) $r['WikiID'],
                'slug'        => $r['Slug'],
                'name'        => $r['Name'],
                'description' => $r['Description'] ?? null,
                'permission'  => $r['Permission'],
                'closed'      => !empty($r['ClosedAt']),
            ], $rows),
        ]);
    }
}
