<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\SessionModel;
use App\Models\UserModuleModel;

class SessionsController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'              => 'SessionID',
        'event_id'        => 'EventID',
        'session_number'  => 'SessionNumber',
        'session_name'    => 'SessionName',
        'coordinator1_id' => 'Coordinator1ID',
        'coordinator2_id' => 'Coordinator2ID',
        'start_time'      => 'StartTime',
        'end_time'        => 'EndTime',
        'room'            => 'Room',
    ];

    private const READONLY_API_FIELDS = ['id'];
    private const FILTERABLE = ['event_id', 'coordinator1_id', 'coordinator2_id'];
    private const SORTABLE   = ['id', 'event_id', 'session_number', 'start_time'];

    private function dbToApi(array $row): array
    {
        $out = [];
        foreach (self::FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
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
        $perPage = max(1, min(200, (int) ($req->getGet('per_page') ?: 100)));
        $q       = trim((string) $req->getGet('q'));
        $sort    = (string) ($req->getGet('sort') ?: 'event_id,session_number');

        $builder = (new SessionModel())->builder();
        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('SessionName', $q)
                ->orLike('SessionNumber', $q)
                ->orLike('Room', $q)
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
        $row = (new SessionModel())->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');
        return $this->response->setJSON(['data' => $this->dbToApi($row)]);
    }

    public function create()
    {
        if (!$this->requireAdmin()) return $this->response;
        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);
        if (empty($row['EventID']) || empty($row['SessionNumber']) || empty($row['SessionName'])) {
            return $this->jsonError(422, 'validation_failed', [
                'required' => ['event_id', 'session_number', 'session_name'],
            ]);
        }
        $model = new SessionModel();
        $id    = $model->insert($row, true);
        if (!$id) return $this->jsonError(422, 'insert_failed', $model->errors());
        return $this->response->setStatusCode(201)->setJSON(['data' => $this->dbToApi($model->find($id))]);
    }

    public function update(int $id)
    {
        if (!$this->requireAdmin()) return $this->response;
        $model = new SessionModel();
        if (!$model->find($id)) return $this->jsonError(404, 'not_found');
        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);
        if (!$model->update($id, $row)) return $this->jsonError(422, 'update_failed', $model->errors());
        return $this->response->setJSON(['data' => $this->dbToApi($model->find($id))]);
    }

    public function delete(int $id)
    {
        if (!$this->requireAdmin()) return $this->response;
        $model = new SessionModel();
        if (!$model->find($id)) return $this->jsonError(404, 'not_found');
        $model->delete($id);
        return $this->response->setStatusCode(204);
    }
}
