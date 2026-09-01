<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\CompanyGuestListsModel;
use App\Models\CompanyGuestListsManagerModel;
use App\Models\UserModuleModel;
use App\Models\EventModel;

/**
 * Per-event, per-company invite limits + primary contact assignment.
 *
 * Access:
 *   - Admin (module 'admin')  → full CRUD.
 *   - Assigned manager        → read only (their assigned rows).
 *   - Everyone else           → 403.
 */
class CompanyGuestListsController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'             => 'CompanyID',
        'event_year'     => 'EventYear',
        'year'           => 'Year',
        'name'           => 'Name',
        'secret_key'     => 'SecretKey',
        'company'        => 'Company',
        'invite_count'   => 'InviteCount',
        'employee_count' => 'EmployeeCount',
        'banquet_count'  => 'BanquetCount',
        'golf_count'     => 'GolfCount',
        'staff_id'       => 'StaffID',
        'event_id'       => 'EventID',
        'full_conf_token'  => 'FullConfToken',
        'exhibitor_token'  => 'ExhibitorToken',
        'cc_primary_on_registration' => 'CcPrimaryOnRegistration',
    ];
    private const READONLY_API_FIELDS = ['id', 'full_conf_token', 'exhibitor_token'];

    private const FILTERABLE = ['year', 'staff_id'];
    private const SORTABLE   = ['id', 'year', 'name', 'company'];

    private function dbToApi(array $row): array
    {
        $out = [];
        foreach (self::FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
        }
        foreach (['id', 'year', 'invite_count', 'employee_count', 'banquet_count', 'golf_count', 'staff_id', 'event_id', 'cc_primary_on_registration'] as $k) {
            if (array_key_exists($k, $out) && $out[$k] !== null && $out[$k] !== '') {
                $out[$k] = (int) $out[$k];
            }
        }

        return $out;
    }


    private function apiToDb(array $payload): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            if (in_array($k, self::READONLY_API_FIELDS, true)) continue;
            if (!isset(self::FIELD_MAP[$k])) continue;
            $out[self::FIELD_MAP[$k]] = $v;
        }
        return $out;
    }

    private function actorId(): ?int
    {
        return ApiAuthContext::actingUserId();
    }

    private function isAdmin(int $actorId): bool
    {
        return (new UserModuleModel())->userHasModule($actorId, 'admin');
    }

    private function requireActor(): ?int
    {
        $id = $this->actorId();
        if (!$id) { $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']); return null; }
        return $id;
    }

    private function requireAdmin(): bool
    {
        $id = $this->requireActor();
        if (!$id) return false;
        if (!$this->isAdmin($id)) {
            $this->response->setStatusCode(403)->setJSON(['error' => 'admin_required']);
            return false;
        }
        return true;
    }

    public function index()
    {
        $actorId = $this->requireActor();
        if (!$actorId) return $this->response;
        $isAdmin = $this->isAdmin($actorId);

        $req     = $this->request;
        $page    = max(1, (int) $req->getGet('page') ?: 1);
        $perPage = max(1, min(200, (int) ($req->getGet('per_page') ?: 100)));
        $q       = trim((string) $req->getGet('q'));
        $sort    = (string) ($req->getGet('sort') ?: '-year,company');

        $builder = (new CompanyGuestListsModel())->builder();
        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
        }
        // Several events can share a Year, so scope by EventID when given.
        // Legacy rows with no EventID still match on the event's Year until
        // they are backfilled.
        $eventId = (int) $req->getGet('event_id');
        if ($eventId > 0) {
            $event     = (new EventModel())->where('EventID', $eventId)->first();
            $eventYear = (int) ($event['Year'] ?? 0);
            $builder->groupStart()
                ->where('EventID', $eventId);
            if ($eventYear > 0) {
                $builder->orGroupStart()
                    ->groupStart()->where('EventID IS NULL', null, false)->orWhere('EventID', 0)->groupEnd()
                    ->where('Year', $eventYear)
                    ->groupEnd();
            }
            $builder->groupEnd();
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('Company', $q)
                ->orLike('Name', $q)
                ->groupEnd();
        }

        if (!$isAdmin) {
            $ids = (new CompanyGuestListsManagerModel())->companyIdsForUser($actorId);
            if (!$ids) {
                return $this->response->setJSON(['data' => [], 'page' => $page, 'per_page' => $perPage, 'total' => 0]);
            }
            $builder->whereIn('CompanyID', $ids);
        }
        foreach (explode(',', $sort) as $s) {
            $s = trim($s);
            if ($s === '') continue;
            $dir = 'ASC';
            if (str_starts_with($s, '-')) { $dir = 'DESC'; $s = substr($s, 1); }
            if (!in_array($s, self::SORTABLE, true)) continue;
            $builder->orderBy(self::FIELD_MAP[$s], $dir);
        }
        $total = $builder->countAllResults(false);
        $rows  = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->response->setJSON([
            'data'     => array_map(fn($r) => $this->dbToApi($r), $rows),
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
        ]);
    }

    /**
     * GET /api/v1/company-guest-lists/guest-counts?ids=1,2,3
     *
     * Live (non-deleted) guest counts per guest list, so the event summary
     * page can show used-vs-limit without loading every list separately.
     * Returns { data: { "<companyGuestListsId>": {professional, exhibitor, banquet, golf} } }
     */
    public function guestCounts()
    {
        $actorId = $this->requireActor();
        if (!$actorId) return $this->response;

        $raw = (string) $this->request->getGet('ids');
        $ids = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $raw)),
            fn($n) => $n > 0,
        )));
        if (!$ids) return $this->response->setJSON(['data' => (object) []]);

        if (!$this->isAdmin($actorId)) {
            $mine = array_map('intval', (new CompanyGuestListsManagerModel())->companyIdsForUser($actorId));
            $ids  = array_values(array_intersect($ids, $mine));
            if (!$ids) return $this->response->setJSON(['data' => (object) []]);
        }

        $out = [];
        foreach ($ids as $id) {
            $out[(string) $id] = ['professional' => 0, 'exhibitor' => 0, 'banquet' => 0, 'golf' => 0];
        }

        $rows = (new \App\Models\EventGuestModel())->builder()
            ->select('InvitedByCompanyID, Type, BanquetCompanyID, GolfCompanyID')
            ->whereIn('InvitedByCompanyID', $ids)
            ->where('DeletedAt', null)
            ->get()->getResultArray();

        foreach ($rows as $r) {
            $key = (string) (int) $r['InvitedByCompanyID'];
            if (!isset($out[$key])) continue;
            if (\App\Models\EventGuestModel::normalizeType($r['Type'] ?? null) === \App\Models\EventGuestModel::TYPE_EXHIBITOR) {
                $out[$key]['exhibitor']++;
            } else {
                $out[$key]['professional']++;
            }
            if (!empty($r['BanquetCompanyID'])) $out[$key]['banquet']++;
            if (!empty($r['GolfCompanyID']))    $out[$key]['golf']++;
        }

        return $this->response->setJSON(['data' => $out]);
    }

    public function show(int $id)
    {
        $actorId = $this->requireActor();
        if (!$actorId) return $this->response;
        $model = new CompanyGuestListsModel();
        $row = $model->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');
        if (!$this->isAdmin($actorId)) {
            if (!(new CompanyGuestListsManagerModel())->userManages($actorId, $id)) {
                return $this->jsonError(403, 'forbidden');
            }
        }
        $row = $this->ensureTokens($model, $row);
        $row = $this->bindEventId($model, $row);
        return $this->response->setJSON(['data' => $this->dbToApi($row)]);
    }

    /**
     * Records which event this guest list belongs to, using the ?event_id= the
     * internal guest-list page always sends. Public registration links then
     * resolve the correct event (and its language toggles) even when the link
     * itself carries no event hint.
     */
    private function bindEventId(CompanyGuestListsModel $model, array $row): array
    {
        $eventId = (int) $this->request->getGet('event_id');
        if ($eventId <= 0 || (int) ($row['EventID'] ?? 0) === $eventId) return $row;
        $model->update((int) $row['CompanyID'], ['EventID' => $eventId]);
        $row['EventID'] = $eventId;
        return $row;
    }


    /** Lazily backfills public registration tokens for legacy rows. */
    private function ensureTokens(CompanyGuestListsModel $model, array $row): array
    {
        $patch = [];
        if (empty($row['FullConfToken']))  $patch['FullConfToken']  = CompanyGuestListsModel::newToken();
        if (empty($row['ExhibitorToken'])) $patch['ExhibitorToken'] = CompanyGuestListsModel::newToken();
        if ($patch === []) return $row;
        $model->update((int) $row['CompanyID'], $patch);
        return array_merge($row, $patch);
    }

    /**
     * POST /api/v1/company-guest-lists/{id}/rotate-token  body: { kind: 'full_conf'|'exhibitor' }
     * Invalidates the old public registration link. Admin / event manager only.
     */
    public function rotateToken(int $id)
    {
        $actorId = $this->requireActor();
        if (!$actorId) return $this->response;
        $model = new CompanyGuestListsModel();
        $row = $model->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');

        $year = (int) ($row['Year'] ?? 0);
        $privileged = $this->isAdmin($actorId)
            || ($year > 0 && (new EventModel())->isEventManagerForYear((int) $actorId, $year));
        if (!$privileged) return $this->jsonError(403, 'admin_required');

        $body = (array) $this->request->getJSON(true);
        $kind = (string) ($body['kind'] ?? '');
        $col  = $kind === 'exhibitor' ? 'ExhibitorToken' : 'FullConfToken';
        $model->update($id, [$col => CompanyGuestListsModel::newToken()]);
        return $this->response->setJSON(['data' => $this->dbToApi($model->find($id))]);
    }


    /**
     * Legacy EventYear format: event short name + 4-digit year, e.g. "TestConX2026".
     * Falls back to the bare year when no matching event row is found.
     */
    private function buildEventYear(int $eventId, int $year): string
    {
        $events = new EventModel();
        $event  = null;
        if ($eventId > 0) {
            $event = $events->where('EventID', $eventId)->first();
        }
        if (!$event && $year > 0) {
            $event = $events->where('Year', $year)->where('GuestListEnabled', 1)->first()
                ?: $events->where('Year', $year)->first();
        }
        $name = trim((string) ($event['Name'] ?? ''));
        $yr   = (int) ($event['Year'] ?? $year);
        if ($name === '' || $yr <= 0) return (string) $year;
        return $name . $yr;
    }

    public function create()
    {
        if (!$this->requireAdmin()) return $this->response;
        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);
        if (empty($row['Year']) || empty($row['Name'])) {
            return $this->jsonError(422, 'validation_failed', ['required' => ['year', 'name']]);
        }
        // Legacy table has NOT NULL columns w/o defaults — fill sensible defaults.
        // EventYear is the event short name concatenated with the 4-digit year
        // (e.g. TestConX2026); fall back to the bare year only if no event matches.
        if (empty($row['EventYear'])) {
            $row['EventYear'] = $this->buildEventYear(
                isset($row['EventID']) ? (int) $row['EventID'] : 0,
                (int) $row['Year']
            );
        }
        if (empty($row['SecretKey']))     $row['SecretKey']     = substr(bin2hex(random_bytes(8)), 0, 10);
        foreach (['InviteCount', 'EmployeeCount', 'BanquetCount', 'GolfCount'] as $c) {
            if (!isset($row[$c]) || $row[$c] === '' || $row[$c] === null) $row[$c] = 0;
        }
        if (!array_key_exists('StaffID', $row)) $row['StaffID'] = 0;

        $model = new CompanyGuestListsModel();
        try {
            $id = $model->insert($row, true);
        } catch (\Throwable $e) {
            log_message('error', 'companyguestlists insert failed: ' . $e->getMessage());
            return $this->jsonError(422, 'db_insert_failed', ['message' => $e->getMessage()]);
        }
        if (!$id) return $this->jsonError(422, 'insert_failed', $model->errors());
        return $this->response->setStatusCode(201)->setJSON(['data' => $this->dbToApi($model->find($id))]);
    }


    public function update(int $id)
    {
        if (!$this->requireAdmin()) return $this->response;
        $model = new CompanyGuestListsModel();
        $existing = $model->find($id);
        if (!$existing) return $this->jsonError(404, 'not_found');
        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);

        // Primary contact must have an email: they are cc'd on registration
        // emails and pre-provisioned into a user account by email.
        $newStaffId = array_key_exists('StaffID', $row) ? (int) $row['StaffID'] : null;
        $staffChanged = $newStaffId !== null && $newStaffId !== (int) ($existing['StaffID'] ?? 0);
        $contact = null;
        if ($staffChanged && $newStaffId > 0) {
            $provisioner = new \App\Libraries\ContactUserProvisioner();
            $contact = $provisioner->contact($newStaffId);
            if (!$contact) return $this->jsonError(404, 'contact_not_found');
            if (trim((string) ($contact['Email'] ?? '')) === '') {
                return $this->jsonError(422, 'primary_contact_email_required');
            }
        }

        if (!$model->update($id, $row)) return $this->jsonError(422, 'update_failed', $model->errors());

        $managerStatus = null;
        if ($contact) {
            $managerStatus = $this->ensurePrimaryContactManager($id, $contact);
        }

        $out = ['data' => $this->dbToApi($model->find($id))];
        if ($managerStatus !== null) $out['primary_contact_manager'] = $managerStatus;
        return $this->response->setJSON($out);
    }

    /**
     * Adds the primary contact as a guest-list manager, pre-provisioning a user
     * account when the contact has none. Never fails the StaffID save.
     *
     * @return string one of: added | created_and_added | already | limit_reached | failed
     */
    private function ensurePrimaryContactManager(int $companyGuestListsId, array $contact): string
    {
        try {
            $provisioner = new \App\Libraries\ContactUserProvisioner();
            $user   = $provisioner->findUserForContact($contact);
            $status = 'added';
            if ($user) {
                $userId = (int) $user['UserID'];
            } else {
                $userId = $provisioner->createUserForContact($contact, []);
                $status = 'created_and_added';
            }
            if ($userId <= 0) return 'failed';

            $mgrs    = new CompanyGuestListsManagerModel();
            $current = $mgrs->userIdsForCompany($companyGuestListsId);
            if (in_array($userId, $current, true)) return 'already';
            if (count($current) >= 4) return 'limit_reached';

            $mgrs->insert([
                'CompanyGuestListsID' => $companyGuestListsId,
                'UserID'              => $userId,
                'AddedBy'             => $this->actorId(),
            ]);

            (new \App\Models\AdminAuditLogModel())->log(
                (int) $this->actorId(),
                'guestlist.primary_contact_manager_added',
                'companyguestlists',
                (string) $companyGuestListsId,
                ['user_id' => $userId, 'contact_id' => (int) ($contact['ContactID'] ?? 0), 'created' => $status === 'created_and_added'],
                $this->request->getIPAddress()
            );
            return $status;
        } catch (\Throwable $e) {
            log_message('error', '[CompanyGuestLists] primary contact manager failed: ' . $e->getMessage());
            return 'failed';
        }
    }


    public function delete(int $id)
    {
        if (!$this->requireAdmin()) return $this->response;
        $model = new CompanyGuestListsModel();
        if (!$model->find($id)) return $this->jsonError(404, 'not_found');
        $model->delete($id);
        return $this->response->setStatusCode(204);
    }
}
