<?php
namespace App\Controllers\Api\V1;

use App\Models\AttendanceModel;

class AttendanceController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'                 => 'AttendanceID',
        'contact_id'         => 'ContactID',
        'email'              => 'Email',
        'year'               => 'Year',
        'event'              => 'Event',
        'type'               => 'Type',
        'payment'            => 'Payment',
        'show'               => 'Show',
        'invite_company_id'  => 'InviteCompanyID',
        'event_reg_id'       => 'EventRegID',
        'tutorial'           => 'Tutorial',
    ];

    private const READONLY_API_FIELDS = ['id'];
    private const FILTERABLE = ['contact_id', 'year', 'event', 'type', 'payment', 'show', 'invite_company_id'];
    private const SORTABLE   = ['id', 'year', 'event', 'type', 'contact_id'];

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

    public function index()
    {
        $req     = $this->request;
        $page    = max(1, (int) $req->getGet('page') ?: 1);
        $perPage = max(1, min(100, (int) ($req->getGet('per_page') ?: 25)));
        $q       = trim((string) $req->getGet('q'));
        $sort    = (string) ($req->getGet('sort') ?: '-year');

        $builder = (new AttendanceModel())->builder();

        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
        }

        if ($q !== '') {
            $builder->groupStart()
                ->like('Email', $q)
                ->orLike('EventRegID', $q);
            if (ctype_digit($q)) {
                $builder->orWhere('AttendanceID', (int) $q)
                        ->orWhere('ContactID', (int) $q);
            }
            $builder->groupEnd();
        }

        foreach (explode(',', $sort) as $s) {
            $s = trim($s);
            if ($s === '') continue;
            $dir = 'ASC';
            if (str_starts_with($s, '-')) { $dir = 'DESC'; $s = substr($s, 1); }
            if (in_array($s, self::SORTABLE, true)) {
                $builder->orderBy(self::FIELD_MAP[$s], $dir);
            }
        }

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->response->setJSON([
            'data' => array_map(fn($r) => $this->dbToApi($r), $rows),
            'pagination' => [
                'page' => $page, 'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function show($id = null)
    {
        $row = (new AttendanceModel())->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');
        return $this->response->setJSON(['data' => $this->dbToApi($row)]);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        if (empty($dbRow)) return $this->jsonError(400, 'no_fields');
        if (!isset($dbRow['ContactID']) || (int) $dbRow['ContactID'] <= 0) {
            return $this->jsonError(422, 'validation_failed', ['contact_id' => 'required']);
        }
        if (!isset($dbRow['Year']) || (int) $dbRow['Year'] <= 0) {
            return $this->jsonError(422, 'validation_failed', ['year' => 'required']);
        }
        if (!isset($dbRow['Event']) || $dbRow['Event'] === '') {
            return $this->jsonError(422, 'validation_failed', ['event' => 'required']);
        }
        $model = new AttendanceModel();
        $id = $model->insert($dbRow, true);
        if (!$id) return $this->jsonError(500, 'insert_failed', $model->errors());
        return $this->response->setStatusCode(201)->setJSON(['data' => $this->dbToApi($model->find((int) $id))]);
    }

    public function update($id = null)
    {
        $model = new AttendanceModel();
        $existing = $model->find((int) $id);
        if (!$existing) return $this->jsonError(404, 'not_found');

        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        if (empty($dbRow)) return $this->jsonError(400, 'no_updatable_fields');
        if (!$model->update((int) $id, $dbRow)) {
            return $this->jsonError(500, 'update_failed', $model->errors());
        }
        return $this->response->setJSON(['data' => $this->dbToApi($model->find((int) $id))]);
    }

    public function delete($id = null)
    {
        $model = new AttendanceModel();
        if (!$model->find((int) $id)) return $this->jsonError(404, 'not_found');
        if (!$model->delete((int) $id)) return $this->jsonError(500, 'delete_failed', $model->errors());
        return $this->response->setJSON(['data' => ['id' => (int) $id, 'deleted' => true]]);
    }
}
