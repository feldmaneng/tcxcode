<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\AdminAuditLogModel;
use App\Models\ModuleModel;
use App\Models\UserModel;
use App\Models\UserModuleModel;
use App\Models\UserWikiPermissionModel;
use App\Models\WikiModel;

/**
 * AdminUsersController — admin-only user management.
 *
 * Acting user identity comes from the X-Acting-User header (verified by
 * HmacAuthFilter and exposed via ApiAuthContext). The controller verifies
 * that user has the `admin` module before performing any mutation.
 */
class AdminUsersController extends BaseApiController
{
    /** Returns the actor's user id, or null if 401/403 has already been written. */
    private function requireAdminActor(): ?int
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) {
            $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']);
            return null;
        }
        if (!(new UserModuleModel())->userHasModule($actorId, 'admin')) {
            $this->response->setStatusCode(403)->setJSON(['error' => 'admin_required']);
            return null;
        }
        return $actorId;
    }

    private function audit(int $actorId, string $action, ?string $type, ?string $id, ?array $details): void
    {
        (new AdminAuditLogModel())->log(
            $actorId, $action, $type, $id, $details, $this->request->getIPAddress()
        );
    }

    /** POST /api/v1/admin/users/list  Body: { q?, page?, per_page? } */
    public function listUsers()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;

        $q       = trim((string) $this->request->getJsonVar('q'));
        $page    = max(1, (int) ($this->request->getJsonVar('page') ?: 1));
        $perPage = min(200, max(1, (int) ($this->request->getJsonVar('per_page') ?: 50)));

        $res = (new UserModel())->searchPaginated($q, $page, $perPage);

        $userIds = array_map(fn($r) => (int) $r['UserID'], $res['data']);
        $modulesByUser = [];
        if ($userIds) {
            $rows = db_connect('control')->table('user_modules um')
                ->select('um.UserID, m.Code')
                ->join('modules m', 'm.ModuleID = um.ModuleID')
                ->whereIn('um.UserID', $userIds)
                ->get()->getResultArray();
            foreach ($rows as $r) $modulesByUser[(int) $r['UserID']][] = $r['Code'];
        }

        return $this->respond([
            'data'     => array_map(fn($u) => [
                'id'                   => (int) $u['UserID'],
                'username'             => $u['UserName'],
                'given_name'           => $u['GivenName'],
                'family_name'          => $u['FamilyName'],
                'email'                => $u['Email'],
                'active'               => (bool) $u['Active'],
                'totp_enabled'         => (bool) $u['TOTPEnabled'],
                'must_change_password' => (bool) $u['MustChangePassword'],
                'modules'              => $modulesByUser[(int) $u['UserID']] ?? [],
                'auth_provider'        => $u['auth_provider'] ?? 'local',
                'wp_user_id'           => isset($u['wp_user_id']) && $u['wp_user_id'] !== null ? (int) $u['wp_user_id'] : null,
            ], $res['data']),
            'total'    => $res['total'],
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    }

    /** POST /api/v1/admin/users/get  Body: { user_id } */
    public function getUser()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $userId = (int) $this->request->getJsonVar('user_id');
        $u = (new UserModel())->find($userId);
        if (!$u) return $this->jsonError(404, 'not_found');

        $modules = (new UserModuleModel())->codesForUser($userId);
        $wikis   = (new UserWikiPermissionModel())->wikisForUser($userId);
        $passkeyCount = !empty($u['WebAuthnCredentialID']) ? 1 : 0;

        return $this->respond([
            'user' => [
                'id'                   => (int) $u['UserID'],
                'username'             => $u['UserName'],
                'given_name'           => $u['GivenName'],
                'family_name'          => $u['FamilyName'],
                'email'                => $u['Email'],
                'active'               => (bool) $u['Active'],
                'totp_enabled'         => (bool) $u['TOTPEnabled'],
                'passkey_count'        => $passkeyCount,
                'must_change_password' => (bool) $u['MustChangePassword'],
            ],
            'modules' => $modules,
            'wikis'   => array_map(fn($r) => [
                'id'         => (int) $r['WikiID'],
                'slug'       => $r['Slug'],
                'name'       => $r['Name'],
                'permission' => $r['Permission'],
            ], $wikis),
        ]);
    }

    /** POST /api/v1/admin/users/create  Body: { username, given_name, family_name, email?, modules?: string[] } */
    public function createUser()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;

        $data = [
            'UserName'   => trim((string) $this->request->getJsonVar('username')),
            'GivenName'  => trim((string) $this->request->getJsonVar('given_name')),
            'FamilyName' => trim((string) $this->request->getJsonVar('family_name')),
            'Email'      => $this->request->getJsonVar('email') ?: null,
            'Active'     => 1,
            'MustChangePassword' => 1,
        ];
        if ($data['UserName'] === '' || $data['GivenName'] === '') {
            return $this->jsonError(400, 'validation', ['fields' => 'username + given_name required']);
        }
        if ((new UserModel())->where('UserName', $data['UserName'])->first()) {
            return $this->jsonError(409, 'username_taken');
        }

        $tempPassword = bin2hex(random_bytes(6));
        $data['PasswordHash'] = password_hash($tempPassword, PASSWORD_BCRYPT);
        $data['PasswordChangedAt'] = date('Y-m-d H:i:s');

        $userId = (new UserModel())->insert($data, true);

        $modules = $this->request->getJsonVar('modules') ?: ['crm', 'wiki'];
        (new UserModuleModel())->setUserModules((int) $userId, $modules);

        $this->audit($actorId, 'user.create', 'user', (string) $userId, [
            'username' => $data['UserName'], 'modules' => $modules,
        ]);

        return $this->respond([
            'user_id'       => (int) $userId,
            'temp_password' => $tempPassword,
        ], 201);
    }

    /** POST /api/v1/admin/users/update  Body: { user_id, given_name?, family_name?, email?, active? } */
    public function updateUser()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $userId = (int) $this->request->getJsonVar('user_id');
        $u = (new UserModel())->find($userId);
        if (!$u) return $this->jsonError(404, 'not_found');

        $patch = [];
        foreach (['given_name' => 'GivenName', 'family_name' => 'FamilyName', 'email' => 'Email'] as $in => $col) {
            $v = $this->request->getJsonVar($in);
            if ($v !== null) $patch[$col] = $v === '' ? null : $v;
        }
        $active = $this->request->getJsonVar('active');
        if ($active !== null) $patch['Active'] = $active ? 1 : 0;

        if ($patch) (new UserModel())->update($userId, $patch);
        $this->audit($actorId, 'user.update', 'user', (string) $userId, $patch);

        return $this->respond(['ok' => true]);
    }

    /** POST /api/v1/admin/users/set-modules  Body: { user_id, modules: string[] } */
    public function setModules()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $userId  = (int) $this->request->getJsonVar('user_id');
        $modules = $this->request->getJsonVar('modules') ?: [];
        if (!is_array($modules)) return $this->jsonError(400, 'modules_array_required');

        (new UserModuleModel())->setUserModules($userId, $modules);
        $this->audit($actorId, 'user.set_modules', 'user', (string) $userId, ['modules' => $modules]);
        return $this->respond(['ok' => true]);
    }

    /** POST /api/v1/admin/users/set-wiki-permission  Body: { user_id, wiki_id, permission: 'read_comment'|'write_edit'|null } */
    public function setWikiPermission()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $userId = (int) $this->request->getJsonVar('user_id');
        $wikiId = (int) $this->request->getJsonVar('wiki_id');
        $perm   = $this->request->getJsonVar('permission');

        if ($perm !== null && !in_array($perm, ['read_comment', 'write_edit'], true)) {
            return $this->jsonError(400, 'invalid_permission');
        }

        (new UserWikiPermissionModel())->setPermission($userId, $wikiId, $perm);
        $this->audit($actorId, 'user.set_wiki_permission', 'user', (string) $userId, [
            'wiki_id' => $wikiId, 'permission' => $perm,
        ]);
        return $this->respond(['ok' => true]);
    }

    /** POST /api/v1/admin/users/reset-password  Body: { user_id }  Returns: { temp_password } */
    public function resetPassword()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $userId = (int) $this->request->getJsonVar('user_id');
        $u = (new UserModel())->find($userId);
        if (!$u) return $this->jsonError(404, 'not_found');

        $tempPassword = bin2hex(random_bytes(6));
        (new UserModel())->update($userId, [
            'PasswordHash'        => password_hash($tempPassword, PASSWORD_BCRYPT),
            'MustChangePassword'  => 1,
            'PasswordChangedAt'   => date('Y-m-d H:i:s'),
        ]);

        $this->audit($actorId, 'user.reset_password', 'user', (string) $userId, []);
        return $this->respond(['temp_password' => $tempPassword]);
    }

    /** POST /api/v1/admin/users/remove-2fa  Body: { user_id } */
    public function remove2fa()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $userId = (int) $this->request->getJsonVar('user_id');
        $u = (new UserModel())->find($userId);
        if (!$u) return $this->jsonError(404, 'not_found');

        (new UserModel())->update($userId, [
            'TOTPSecret'  => null,
            'TOTPEnabled' => 0,
        ]);
        $this->audit($actorId, 'user.remove_2fa', 'user', (string) $userId, []);
        return $this->respond(['ok' => true]);
    }

    /** POST /api/v1/admin/users/invalidate-passkeys  Body: { user_id } */
    public function invalidatePasskeys()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $userId = (int) $this->request->getJsonVar('user_id');
        $u = (new UserModel())->find($userId);
        if (!$u) return $this->jsonError(404, 'not_found');

        (new UserModel())->update($userId, [
            'WebAuthnCredentialID' => null,
            'WebAuthnPublicKey'    => null,
            'WebAuthnCounter'      => 0,
            'WebAuthnTransports'   => null,
        ]);
        $this->audit($actorId, 'user.invalidate_passkeys', 'user', (string) $userId, []);
        return $this->respond(['ok' => true]);
    }

    /** POST /api/v1/admin/audit  Body: { page?, per_page?, action?, target_type? } */
    public function audit_list()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $page    = max(1, (int) ($this->request->getJsonVar('page') ?: 1));
        $perPage = min(500, max(1, (int) ($this->request->getJsonVar('per_page') ?: 100)));
        $action  = $this->request->getJsonVar('action');
        $tType   = $this->request->getJsonVar('target_type');

        $b = db_connect('control')->table('admin_audit_log a')
            ->select('a.*, u.UserName AS actor_username')
            ->join('users u', 'u.UserID = a.ActorUserID', 'left');
        if ($action) $b->where('a.Action', $action);
        if ($tType)  $b->where('a.TargetType', $tType);

        $total = (clone $b)->countAllResults(false);
        $rows = $b->orderBy('a.AuditID', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()->getResultArray();

        return $this->respond([
            'data' => array_map(fn($r) => [
                'id'             => (int) $r['AuditID'],
                'actor_user_id'  => $r['ActorUserID'] !== null ? (int) $r['ActorUserID'] : null,
                'actor_username' => $r['actor_username'] ?? null,
                'action'         => $r['Action'],
                'target_type'    => $r['TargetType'],
                'target_id'      => $r['TargetID'],
                'details'        => $r['Details'] ? json_decode($r['Details'], true) : null,
                'ip_address'     => $r['IpAddress'],
                'created_at'     => $r['CreatedAt'],
            ], $rows),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    }

    /** POST /api/v1/admin/wikis/list */
    public function listWikis()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $rows = (new WikiModel())->orderBy('Name', 'ASC')->findAll();
        return $this->respond(['data' => array_map(fn($w) => [
            'id'          => (int) $w['WikiID'],
            'slug'        => $w['Slug'],
            'name'        => $w['Name'],
            'description' => $w['Description'],
            'created_at'  => $w['CreatedAt'],
        ], $rows)]);
    }

    /** POST /api/v1/admin/wikis/create  Body: { slug, name, description? } */
    public function createWiki()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $slug = trim((string) $this->request->getJsonVar('slug'));
        $name = trim((string) $this->request->getJsonVar('name'));
        if ($slug === '' || $name === '') return $this->jsonError(400, 'validation');

        $id = (new WikiModel())->insert([
            'Slug' => $slug, 'Name' => $name,
            'Description' => $this->request->getJsonVar('description') ?: null,
            'CreatedBy' => $actorId,
        ], true);

        $this->audit($actorId, 'wiki.create', 'wiki', (string) $id, ['slug' => $slug, 'name' => $name]);
        return $this->respond(['id' => (int) $id], 201);
    }

    // ===================================================================
    // Pre-provision a WordPress SSO user from a CRM contact
    // ===================================================================

    /** Look up a contact in the CRM database by id. Returns array|null. */
    private function fetchContact(int $contactId): ?array
    {
        try {
            $row = (new \App\Models\ContactModel())->find($contactId);
            return $row ?: null;
        } catch (\Throwable $e) {
            log_message('error', '[AdminUsers] fetchContact failed: ' . $e->getMessage());
            return null;
        }
    }

    /** Build a slugged username from a name + email when no override is provided. */
    private function suggestUsername(array $contact): string
    {
        $email = trim((string) ($contact['Email'] ?? ''));
        if ($email !== '' && strpos($email, '@') !== false) {
            $local = strtolower(substr($email, 0, strpos($email, '@')));
            $local = preg_replace('/[^a-z0-9._-]+/', '', $local);
            if ($local !== '') return $local;
        }
        $name = strtolower(trim(($contact['GivenName'] ?? '') . '.' . ($contact['FamilyName'] ?? ''), '.'));
        $name = preg_replace('/[^a-z0-9._-]+/', '', $name);
        return $name !== '' ? $name : ('user' . $contact['ContactID']);
    }

    /**
     * POST /api/v1/admin/users/check-contact-availability
     * Body: { contact_id }
     * Returns suggested defaults + collisions so admins see issues before submit.
     */
    public function checkContactAvailability()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $contactId = (int) $this->request->getJsonVar('contact_id');
        if ($contactId <= 0) return $this->jsonError(400, 'contact_id_required');

        $contact = $this->fetchContact($contactId);
        if (!$contact) return $this->jsonError(404, 'contact_not_found');

        $email = trim((string) ($contact['Email'] ?? ''));
        $given = (string) ($contact['GivenName'] ?? '');
        $family = (string) ($contact['FamilyName'] ?? '');
        $suggestedUsername = $this->suggestUsername($contact);

        $userModel = new UserModel();
        $matches = [];
        if ($email !== '') {
            $byEmail = $userModel->where('Email', $email)->findAll();
            foreach ($byEmail as $u) {
                $matches[(int) $u['UserID']] = [
                    'id'            => (int) $u['UserID'],
                    'username'      => $u['UserName'],
                    'email'         => $u['Email'],
                    'auth_provider' => $u['auth_provider'] ?? 'local',
                    'wp_user_id'    => $u['wp_user_id'] !== null ? (int) $u['wp_user_id'] : null,
                    'reason'        => 'email',
                ];
            }
        }
        $byUsername = $userModel->where('UserName', $suggestedUsername)->first();
        if ($byUsername) {
            $matches[(int) $byUsername['UserID']] = [
                'id'            => (int) $byUsername['UserID'],
                'username'      => $byUsername['UserName'],
                'email'         => $byUsername['Email'],
                'auth_provider' => $byUsername['auth_provider'] ?? 'local',
                'wp_user_id'    => $byUsername['wp_user_id'] !== null ? (int) $byUsername['wp_user_id'] : null,
                'reason'        => 'username',
            ];
        }

        return $this->respond([
            'contact' => [
                'id'          => (int) $contact['ContactID'],
                'given_name'  => $given,
                'family_name' => $family,
                'email'       => $email !== '' ? $email : null,
            ],
            'suggested_username' => $suggestedUsername,
            'suggested_email'    => $email !== '' ? $email : null,
            'matches'            => array_values($matches),
        ]);
    }

    /**
     * POST /api/v1/admin/users/preprovision-from-contact
     * Body: { contact_id, username, email, given_name, family_name, modules: string[] }
     */
    public function preprovisionFromContact()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;

        $contactId = (int) $this->request->getJsonVar('contact_id');
        $username  = trim((string) $this->request->getJsonVar('username'));
        $email     = trim((string) $this->request->getJsonVar('email'));
        $given     = trim((string) $this->request->getJsonVar('given_name'));
        $family    = trim((string) $this->request->getJsonVar('family_name'));
        $modules   = $this->request->getJsonVar('modules') ?: ['crm', 'wiki'];

        if ($contactId <= 0 || $username === '' || $email === '' || $given === '') {
            return $this->jsonError(400, 'validation', [
                'fields' => 'contact_id, username, email, given_name required',
            ]);
        }
        if (!is_array($modules)) return $this->jsonError(400, 'modules_array_required');

        $contact = $this->fetchContact($contactId);
        if (!$contact) return $this->jsonError(404, 'contact_not_found');

        $userModel = new UserModel();
        if ($userModel->where('UserName', $username)->first()) {
            return $this->jsonError(409, 'username_taken');
        }
        $existingByEmail = $userModel
            ->where('Email', $email)
            ->where('auth_provider', 'wordpress')
            ->first();
        if ($existingByEmail) {
            return $this->jsonError(409, 'wp_account_exists_for_email');
        }

        $userId = $userModel->insert([
            'UserName'                 => $username,
            'GivenName'                => $given,
            'FamilyName'               => $family,
            'Email'                    => $email,
            'PasswordHash'             => null,
            'Active'                   => 1,
            'MustChangePassword'       => 0,
            'auth_provider'            => 'wordpress',
            'wp_user_id'               => null,
            'ProvisionedFromContactID' => $contactId,
        ], true);

        (new UserModuleModel())->setUserModules((int) $userId, $modules);

        $this->audit($actorId, 'user.preprovision_wp', 'user', (string) $userId, [
            'contact_id' => $contactId,
            'username'   => $username,
            'email'      => $email,
            'modules'    => $modules,
        ]);

        return $this->respond(['user_id' => (int) $userId], 201);
    }
}
