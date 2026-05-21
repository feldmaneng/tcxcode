<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\EventModel;
use App\Models\UserModuleModel;

/**
 * Events CRUD — used by the Admin module and (read-only) by the Author Portal.
 * All writes require the `admin` module on the acting user.
 */
class EventsController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'                => 'EventID',
        'year'              => 'Year',
        'name'              => 'Name',
        'full_name'         => 'FullName',
        'start_date'        => 'StartDate',
        'end_date'          => 'EndDate',
        'city'              => 'City',
        'facility'          => 'Facility',
        'facility_address'  => 'FacilityAddress',
        'event_chair1_id'   => 'EventChair1ID',
        'event_chair2_id'   => 'EventChair2ID',
        'event_manager_id'  => 'EventManagerID',
        'is_closed'         => 'IsClosed',
        'closed_at'         => 'ClosedAt',
    ];

    private const READONLY_API_FIELDS = ['id'];
    private const FILTERABLE = ['year', 'event_manager_id', 'event_chair1_id', 'event_chair2_id'];
    private const SORTABLE   = ['id', 'year', 'name', 'start_date'];

    private function dbToApi(array $row): array
    {
        $out = [];
        foreach (self::FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
        }
        // Normalize IsClosed (TINYINT comes back as string from MySQL) to int|null
        if (array_key_exists('is_closed', $out)) {
            $v = $out['is_closed'];
            $out['is_closed'] = ($v === null || $v === '') ? null : (int) $v;
        }
        // Coerce numeric id fields to int for strict-equality checks on the client
        foreach (['id', 'year', 'event_chair1_id', 'event_chair2_id', 'event_manager_id'] as $k) {
            if (array_key_exists($k, $out) && $out[$k] !== null) $out[$k] = (int) $out[$k];
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

    private function requireAdmin(): bool
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) {
            $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']);
            return false;
        }
        if (!(new UserModuleModel())->userHasModule($actorId, 'admin')) {
            $this->response->setStatusCode(403)->setJSON(['error' => 'admin_required']);
            return false;
        }
        return true;
    }

    public function index()
    {
        $req     = $this->request;
        $page    = max(1, (int) $req->getGet('page') ?: 1);
        $perPage = max(1, min(100, (int) ($req->getGet('per_page') ?: 50)));
        $q       = trim((string) $req->getGet('q'));
        $sort    = (string) ($req->getGet('sort') ?: '-year');

        $builder = (new EventModel())->builder();

        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('Name', $q)
                ->orLike('FullName', $q)
                ->orLike('City', $q)
                ->groupEnd();
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
            'data'  => array_map(fn($r) => $this->dbToApi($r), $rows),
            'page'  => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    public function show(int $id)
    {
        $row = (new EventModel())->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');
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
        $model = new EventModel();
        $id    = $model->insert($row, true);
        if (!$id) return $this->jsonError(422, 'insert_failed', $model->errors());
        return $this->response->setStatusCode(201)->setJSON(['data' => $this->dbToApi($model->find($id))]);
    }

    public function update(int $id)
    {
        if (!$this->requireAdmin()) return $this->response;
        $model    = new EventModel();
        $existing = $model->find($id);
        if (!$existing) return $this->jsonError(404, 'not_found');
        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);

        // Stamp ClosedAt when transitioning into the closed state.
        if (array_key_exists('IsClosed', $row)) {
            $newClosed = $row['IsClosed'];
            $wasClosed = (int) ($existing['IsClosed'] ?? 0) === 1;
            $nowClosed = (int) $newClosed === 1;
            if ($nowClosed && !$wasClosed && !array_key_exists('ClosedAt', $row)) {
                $row['ClosedAt'] = date('Y-m-d H:i:s');
            }
            if (!$nowClosed && $wasClosed) {
                $row['ClosedAt'] = null;
            }
            // Normalize NULL (auto), 0 (forced open), 1 (forced closed).
            if ($newClosed === '' || $newClosed === 'auto') $row['IsClosed'] = null;
        }

        if (!$model->update($id, $row)) return $this->jsonError(422, 'update_failed', $model->errors());
        return $this->response->setJSON(['data' => $this->dbToApi($model->find($id))]);
    }

    public function delete(int $id)
    {
        if (!$this->requireAdmin()) return $this->response;
        $model = new EventModel();
        if (!$model->find($id)) return $this->jsonError(404, 'not_found');
        $model->delete($id);
        return $this->response->setStatusCode(204);
    }
}
