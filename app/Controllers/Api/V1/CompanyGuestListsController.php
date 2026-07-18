<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\CompanyGuestListsModel;
use App\Models\CompanyGuestListsManagerModel;
use App\Models\UserModuleModel;

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
        'staff_id'       => 'StaffID',
    ];
    private const READONLY_API_FIELDS = ['id'];
    private const FILTERABLE = ['year', 'staff_id'];
    private const SORTABLE   = ['id', 'year', 'name', 'company'];

    private function dbToApi(array $row): array
    {
        $out = [];
        foreach (self::FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
        }
        foreach (['id', 'year', 'invite_count', 'employee_count', 'banquet_count', 'staff_id'] as $k) {
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

    public function show(int $id)
    {
        $actorId = $this->requireActor();
        if (!$actorId) return $this->response;
        $row = (new CompanyGuestListsModel())->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');
        if (!$this->isAdmin($actorId)) {
            if (!(new CompanyGuestListsManagerModel())->userManages($actorId, $id)) {
                return $this->jsonError(403, 'forbidden');
            }
        }
        return $this->response->setJSON(['data' => $this->dbToApi($row)]);
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
        if (empty($row['EventYear']))     $row['EventYear']     = (string) $row['Year'];
        if (empty($row['SecretKey']))     $row['SecretKey']     = substr(bin2hex(random_bytes(8)), 0, 10);
        foreach (['InviteCount', 'EmployeeCount', 'BanquetCount'] as $c) {
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
        if (!$model->find($id)) return $this->jsonError(404, 'not_found');
        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);
        if (!$model->update($id, $row)) return $this->jsonError(422, 'update_failed', $model->errors());
        return $this->response->setJSON(['data' => $this->dbToApi($model->find($id))]);
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
