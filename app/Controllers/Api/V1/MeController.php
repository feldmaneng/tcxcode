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
        }

        return $this->respond([
            'user' => [
                'id'                    => (int) $user['UserID'],
                'username'              => $user['UserName'],
                'given_name'            => $user['GivenName'] ?? $user['UserName'],
                'family_name'           => $user['FamilyName'] ?? '',
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

        $rows = (new UserWikiPermissionModel())->wikisForUser($userId);

        return $this->respond([
            'wikis' => array_map(fn($r) => [
                'id'          => (int) $r['WikiID'],
                'slug'        => $r['Slug'],
                'name'        => $r['Name'],
                'description' => $r['Description'] ?? null,
                'permission'  => $r['Permission'],
            ], $rows),
        ]);
    }
}
