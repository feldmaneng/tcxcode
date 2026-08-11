<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\WpLookupClient;
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

        // Hydrate joined contact labels in one query so the list can show
        // "Linked CRM contact" without N+1 round-trips.
        $contactIds = array_values(array_filter(array_map(
            fn($r) => isset($r['ContactID']) && $r['ContactID'] !== null ? (int) $r['ContactID'] : null,
            $res['data']
        )));
        $contactsById = [];
        if ($contactIds) {
            $rows = (new \App\Models\ContactModel())
                ->select('ContactID, GivenName, FamilyName, Email')
                ->whereIn('ContactID', array_unique($contactIds))
                ->findAll();
            foreach ($rows as $r) {
                $contactsById[(int) $r['ContactID']] = $r;
            }
        }

        return $this->respond([
            'data'     => array_map(function($u) use ($modulesByUser, $contactsById) {
                $cid = isset($u['ContactID']) && $u['ContactID'] !== null ? (int) $u['ContactID'] : null;
                $c   = $cid && isset($contactsById[$cid]) ? $contactsById[$cid] : null;
                $cLabel = $c
                    ? trim(($c['GivenName'] ?? '') . ' ' . ($c['FamilyName'] ?? ''))
                    : null;
                return [
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
                    'contact_id'           => $cid,
                    'contact_label'        => $cLabel !== null && $cLabel !== ''
                        ? ($cLabel . ($c['Email'] ? ' <' . $c['Email'] . '>' : ''))
                        : null,
                ];
            }, $res['data']),
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

        // Resolve linked CRM contact (live ContactID).
        $contact = null;
        $contactId = isset($u['ContactID']) && $u['ContactID'] !== null ? (int) $u['ContactID'] : null;
        if ($contactId) {
            $c = (new \App\Models\ContactModel())
                ->select('ContactID, GivenName, FamilyName, Email, Company')
                ->find($contactId);
            if ($c) {
                $contact = [
                    'id'          => (int) $c['ContactID'],
                    'given_name'  => (string) ($c['GivenName'] ?? ''),
                    'family_name' => (string) ($c['FamilyName'] ?? ''),
                    'email'       => $c['Email'] ?: null,
                    'company'     => $c['Company'] ?: null,
                ];
            }
        }

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
                'contact_id'           => $contactId,
                'contact'              => $contact,
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

    /**
     * POST /api/v1/admin/users/set-contact
     * Body: { user_id, contact_id: int | null }
     * Links/unlinks the live CRM contact on this user.
     */
    public function setContact()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;

        $payload = $this->request->getJSON(true);
        if (!is_array($payload)) {
            return $this->jsonError(400, 'invalid_json_body');
        }

        $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : 0;
        if ($userId <= 0) {
            return $this->jsonError(400, 'invalid_user_id');
        }

        // Important: malformed/missing contact ids must NOT be interpreted as
        // NULL. Clearing the link now requires an explicit clear=true flag.
        $hasContactId = array_key_exists('contact_id', $payload);
        if (!$hasContactId && array_key_exists('contactId', $payload)) {
            // Backward-compatible tolerance for camelCase callers, but keep the
            // canonical API contract as contact_id.
            $payload['contact_id'] = $payload['contactId'];
            $hasContactId = true;
        }
        if (!$hasContactId) {
            return $this->jsonError(400, 'contact_id_required');
        }

        $contactRaw = $payload['contact_id'];
        $clear = array_key_exists('clear', $payload) && filter_var($payload['clear'], FILTER_VALIDATE_BOOLEAN);
        if ($contactRaw === null || $contactRaw === '') {
            if (!$clear) {
                return $this->jsonError(400, 'contact_id_required_for_link');
            }
            $contactId = null;
        } else {
            $contactId = (int) $contactRaw;
        }

        $userModel = new UserModel();
        $u = $userModel->find($userId);
        if (!$u) return $this->jsonError(404, 'user_not_found');

        if ($contactId !== null) {
            if ($contactId <= 0) return $this->jsonError(400, 'invalid_contact_id');
            $c = (new \App\Models\ContactModel())->find($contactId);
            if (!$c) return $this->jsonError(404, 'contact_not_found');

            // Enforce uniqueness manually so we can return a friendly error
            // (the column also has a UNIQUE constraint as a safety net).
            // Use a *separate* model instance so any lingering builder state
            // (WHERE clauses) does not leak into the update() below — in CI4
            // the model's builder is shared across calls.
            $lookupModel = new UserModel();
            $already = $lookupModel->where('ContactID', $contactId)
                ->where('UserID !=', $userId)
                ->first();
            if ($already) {
                return $this->jsonError(409, 'contact_already_linked', [
                    'linked_user_id'       => (int) $already['UserID'],
                    'linked_user_username' => $already['UserName'],
                ]);
            }
        }

        // Use the control DB builder directly for this one-field write. In
        // production this has proven more reliable than reusing the model
        // instance after reads/lookups, and lets us verify the persisted value
        // before returning success to the UI.
        $db = db_connect('control');
        $updated = $db->table('users')
            ->where('UserID', $userId)
            ->update(['ContactID' => $contactId]);

        if ($updated === false) {
            return $this->jsonError(500, 'contact_update_failed', [
                'db_error' => $db->error(),
            ]);
        }

        $fresh = (new UserModel())->select('UserID, ContactID')->find($userId);
        $savedContactId = isset($fresh['ContactID']) && $fresh['ContactID'] !== null
            ? (int) $fresh['ContactID']
            : null;
        if ($savedContactId !== $contactId) {
            return $this->jsonError(500, 'contact_update_not_persisted', [
                'requested_contact_id' => $contactId,
                'saved_contact_id'     => $savedContactId,
                'db_error'             => $db->error(),
            ]);
        }

        $this->audit($actorId, 'user.set_contact', 'user', (string) $userId, [
            'contact_id' => $contactId,
            'previous'   => isset($u['ContactID']) && $u['ContactID'] !== null ? (int) $u['ContactID'] : null,
        ]);

        return $this->respond(['ok' => true, 'contact_id' => $savedContactId]);
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
            'closed_at'   => $w['ClosedAt'] ?? null,
        ], $rows)]);
    }

    /** POST /api/v1/admin/wikis/update  Body: { id, slug?, name?, description? } */
    public function updateWiki()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $id = (int) $this->request->getJsonVar('id');
        if ($id <= 0) return $this->jsonError(400, 'validation');

        $model = new WikiModel();
        $wiki  = $model->find($id);
        if (!$wiki) return $this->jsonError(404, 'wiki_not_found');

        $patch = [];
        $slug = $this->request->getJsonVar('slug');
        if ($slug !== null) {
            $slug = strtolower(trim((string) $slug));
            $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
            $slug = trim((string) $slug, '-');
            if ($slug === '') return $this->jsonError(400, 'invalid_slug');
            $dupe = $model->where('Slug', $slug)->where('WikiID !=', $id)->first();
            if ($dupe) return $this->jsonError(409, 'slug_taken');
            $patch['Slug'] = $slug;
        }
        $name = $this->request->getJsonVar('name');
        if ($name !== null) {
            $name = trim((string) $name);
            if ($name === '') return $this->jsonError(400, 'validation');
            $patch['Name'] = $name;
        }
        if ($this->request->getJsonVar('description') !== null) {
            $desc = trim((string) $this->request->getJsonVar('description'));
            $patch['Description'] = $desc === '' ? null : $desc;
        }
        if (!$patch) return $this->respond(['ok' => true]);

        $model->update($id, $patch);
        $this->audit($actorId, 'wiki.update', 'wiki', (string) $id, $patch);
        return $this->respond(['ok' => true]);
    }

    /** POST /api/v1/admin/wikis/set-closed  Body: { id, closed: bool } */
    public function setWikiClosed()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $id     = (int) $this->request->getJsonVar('id');
        $closed = (bool) $this->request->getJsonVar('closed');
        if ($id <= 0) return $this->jsonError(400, 'validation');

        $model = new WikiModel();
        if (!$model->find($id)) return $this->jsonError(404, 'wiki_not_found');

        $model->update($id, ['ClosedAt' => $closed ? date('Y-m-d H:i:s') : null]);
        $this->audit($actorId, $closed ? 'wiki.close' : 'wiki.reopen', 'wiki', (string) $id, null);
        return $this->respond(['ok' => true, 'closed' => $closed]);
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

        // Grant write_edit to all admin users by default (including the creator).
        $adminRows = db_connect('control')->table('user_modules um')
            ->select('um.UserID')
            ->join('modules m', 'm.ModuleID = um.ModuleID')
            ->where('m.Code', 'admin')
            ->get()->getResultArray();
        $perms = new UserWikiPermissionModel();
        foreach ($adminRows as $r) {
            $perms->setPermission((int) $r['UserID'], (int) $id, 'write_edit');
        }

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

        // Look up the WP user by contact email so the UI can show whether we already
        // know which WP account to link, and surface "WP is offline" cleanly.
        $wpClient = new WpLookupClient();
        $wpMatch = null;
        $wpStatus = 'unconfigured';
        if ($email !== '') {
            $r = $wpClient->lookupByEmailWithStatus($email);
            $wpStatus = $r['status'];
            if ($r['user']) {
                $wpMatch = $this->shapeWpUser($r['user']);
                // If this WP user is already linked to a different CI4 user, flag it as a match too.
                $alreadyLinked = $userModel->where('wp_user_id', (int) $wpMatch['wp_user_id'])->first();
                if ($alreadyLinked) {
                    $matches[(int) $alreadyLinked['UserID']] = [
                        'id'            => (int) $alreadyLinked['UserID'],
                        'username'      => $alreadyLinked['UserName'],
                        'email'         => $alreadyLinked['Email'],
                        'auth_provider' => $alreadyLinked['auth_provider'] ?? 'local',
                        'wp_user_id'    => $alreadyLinked['wp_user_id'] !== null ? (int) $alreadyLinked['wp_user_id'] : null,
                        'reason'        => 'wp_user_id',
                    ];
                }
            }
        } elseif ($wpClient->isConfigured()) {
            $wpStatus = 'not_found';
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
            'wp_match'           => $wpMatch,
            'wp_lookup_status'   => $wpStatus,
        ]);
    }

    /**
     * GET /api/v1/admin/users/wp-lookup?email=...&username=...&q=...&wp_user_id=...
     * Server-to-server proxy to the WP plugin's signed lookup/search endpoints.
     * Always returns 200 with whatever was found (or nulls) — never leaks WP errors.
     */
    public function wpLookup()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;

        $email    = trim((string) $this->request->getGet('email'));
        $username = trim((string) $this->request->getGet('username'));
        $q        = trim((string) $this->request->getGet('q'));
        $wpUserId = (int) $this->request->getGet('wp_user_id');
        $limit    = max(1, min(25, (int) ($this->request->getGet('limit') ?: 10)));

        $client = new WpLookupClient();
        if (!$client->isConfigured()) {
            return $this->respond([
                'wp_user'    => null,
                'candidates' => [],
                'status'     => 'unconfigured',
            ]);
        }

        $wpUser = null;
        $status = 'not_found';
        if ($wpUserId > 0) {
            $u = $client->lookupById($wpUserId);
            if ($u) { $wpUser = $this->shapeWpUser($u); $status = 'found'; }
        } elseif ($email !== '') {
            $r = $client->lookupByEmailWithStatus($email);
            $status = $r['status'];
            if ($r['user']) $wpUser = $this->shapeWpUser($r['user']);
        } elseif ($username !== '') {
            $u = $client->lookupByUsername($username);
            if ($u) { $wpUser = $this->shapeWpUser($u); $status = 'found'; }
        }

        $candidates = [];
        if ($q !== '') {
            foreach ($client->search($q, $limit) as $u) {
                $candidates[] = $this->shapeWpUser($u);
            }
        }

        return $this->respond([
            'wp_user'    => $wpUser,
            'candidates' => $candidates,
            'status'     => $status,
        ]);
    }

    /** Reduce the WP plugin payload to the fields the frontend needs. */
    private function shapeWpUser(array $u): array
    {
        return [
            'wp_user_id'   => (int) ($u['wp_user_id'] ?? 0),
            'user_login'   => (string) ($u['user_login'] ?? $u['username'] ?? ''),
            'user_email'   => (string) ($u['user_email'] ?? $u['email'] ?? ''),
            'display_name' => (string) ($u['display_name'] ?? ''),
            'first_name'   => (string) ($u['first_name'] ?? ''),
            'last_name'    => (string) ($u['last_name'] ?? ''),
            'roles'        => array_values((array) ($u['roles'] ?? [])),
        ];
    }

    /**
     * POST /api/v1/admin/users/preprovision-from-contact
     * Body: { contact_id, username, email, given_name, family_name, modules: string[], wp_user_id?: int|null }
     */
    public function preprovisionFromContact()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;

        $contactId = (int) $this->request->getJsonVar('contact_id');
        $username  = trim((string) $this->request->getJsonVar('username'));
        $email     = trim((string) $this->request->getJsonVar('email'));
        $given     = trim((string) $this->request->getJsonVar('given_name'));
        $family    = trim((string) $this->request->getJsonVar('family_name'));
        $modulesRaw = $this->request->getJsonVar('modules');
        // An empty array is meaningful: guest-list managers need no module rows.
        $modules   = $modulesRaw === null ? ['crm', 'wiki'] : $modulesRaw;
        $wpUserIdRaw = $this->request->getJsonVar('wp_user_id');
        $wpUserId  = $wpUserIdRaw === null || $wpUserIdRaw === '' ? null : (int) $wpUserIdRaw;

        if ($contactId <= 0 || $username === '' || $email === '' || $given === '') {
            return $this->jsonError(400, 'validation', [
                'fields' => 'contact_id, username, email, given_name required',
            ]);
        }
        if (!is_array($modules)) return $this->jsonError(400, 'modules_array_required');

        $contact = $this->fetchContact($contactId);
        if (!$contact) return $this->jsonError(404, 'contact_not_found');

        $userModel = new UserModel();
        if ($userModel->where('LOWER(UserName)', strtolower($username))->first()) {
            return $this->jsonError(409, 'username_taken');
        }
        if ($userModel->where('LOWER(Email)', strtolower($email))->first()) {
            return $this->jsonError(409, 'email_taken');
        }

        // If a wp_user_id is supplied, verify it against WP and reject duplicates.
        if ($wpUserId !== null && $wpUserId > 0) {
            if ($userModel->where('wp_user_id', $wpUserId)->first()) {
                return $this->jsonError(409, 'wp_user_already_linked');
            }
            $client = new WpLookupClient();
            if ($client->isConfigured()) {
                $wpUser = $client->lookupById($wpUserId);
                if (!$wpUser) {
                    return $this->jsonError(400, 'wp_user_not_found');
                }
                // Soft email-mismatch warning: log it but don't block — admin may be
                // intentionally pointing the WP id at a different email (rename in flight).
                $wpEmail = strtolower((string) ($wpUser['user_email'] ?? ''));
                if ($wpEmail !== '' && $wpEmail !== strtolower($email)) {
                    log_message('warning', '[Preprovision] wp_email_mismatch wp_user_id=' . $wpUserId
                        . ' wp_email=' . $wpEmail . ' ci_email=' . strtolower($email));
                }
            }
        } else {
            $wpUserId = null;
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
            'wp_user_id'               => $wpUserId,
            'ProvisionedFromContactID' => $contactId,
            'ContactID'                => $contactId,
        ], true);

        (new UserModuleModel())->setUserModules((int) $userId, $modules);

        $this->audit($actorId, 'user.preprovision_wp', 'user', (string) $userId, [
            'contact_id' => $contactId,
            'username'   => $username,
            'email'      => $email,
            'modules'    => $modules,
            'wp_user_id' => $wpUserId,
        ]);

        return $this->respond(['user_id' => (int) $userId], 201);
    }
}
