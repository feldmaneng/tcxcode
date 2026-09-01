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
        // Coerce numeric id fields to int for strict-equality checks on the client
        foreach (['id', 'event_id', 'coordinator1_id', 'coordinator2_id'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = ($out[$k] === null || $out[$k] === '') ? null : (int) $out[$k];
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
            'data'  => $this->attachCoordinatorNames(array_map(fn($r) => $this->dbToApi($r), $rows)),
            'page'  => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    /**
     * Adds coordinator1_name / coordinator2_name (Given "Nickname" Family)
     * to already-mapped API rows. `users` lives in the control DB while
     * nicknames live on `contacts` in the default DB, so both are resolved
     * with two batched lookups rather than a cross-DB join.
     *
     * @param array<int,array<string,mixed>> $apiRows
     * @return array<int,array<string,mixed>>
     */
    private function attachCoordinatorNames(array $apiRows): array
    {
        $userIds = [];
        foreach ($apiRows as $r) {
            foreach (['coordinator1_id', 'coordinator2_id'] as $k) {
                $id = $r[$k] ?? null;
                if ($id) $userIds[(int) $id] = true;
            }
        }
        $names = [];
        if ($userIds) {
            $ids   = array_keys($userIds);
            $users = \Config\Database::connect('control')->table('users')
                ->select('UserID, GivenName, FamilyName, ContactID')
                ->whereIn('UserID', $ids)
                ->get()->getResultArray();

            $contactIds = [];
            foreach ($users as $u) {
                if (!empty($u['ContactID'])) $contactIds[(int) $u['ContactID']] = true;
            }
            $nicknames = [];
            if ($contactIds) {
                $rows = \Config\Database::connect()->table('contacts')
                    ->select('ContactID, Nickname')
                    ->whereIn('ContactID', array_keys($contactIds))
                    ->get()->getResultArray();
                foreach ($rows as $c) {
                    $nick = trim((string) ($c['Nickname'] ?? ''));
                    if ($nick !== '') $nicknames[(int) $c['ContactID']] = $nick;
                }
            }
            foreach ($users as $u) {
                $nick = $nicknames[(int) ($u['ContactID'] ?? 0)] ?? null;
                $name = PresentationRecipientsController::formatName(
                    $u['GivenName'] ?? null,
                    $nick,
                    $u['FamilyName'] ?? null,
                );
                if ($name !== '') $names[(int) $u['UserID']] = $name;
            }
        }
        foreach ($apiRows as &$r) {
            $r['coordinator1_name'] = $names[(int) ($r['coordinator1_id'] ?? 0)] ?? null;
            $r['coordinator2_name'] = $names[(int) ($r['coordinator2_id'] ?? 0)] ?? null;
        }
        unset($r);
        return $apiRows;
    }

    public function show(int $id)
    {
        $row = (new SessionModel())->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');
        $out = $this->attachCoordinatorNames([$this->dbToApi($row)]);
        return $this->response->setJSON(['data' => $out[0]]);
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
        $model    = new SessionModel();
        $existing = $model->find($id);
        if (!$existing) return $this->jsonError(404, 'not_found');
        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);
        if (!$model->update($id, $row)) return $this->jsonError(422, 'update_failed', $model->errors());

        // Presentations mirror the session number in their legacy Session
        // column; keep them in sync when the number changes.
        if (array_key_exists('SessionNumber', $row)
            && (string) $row['SessionNumber'] !== (string) ($existing['SessionNumber'] ?? '')) {
            \Config\Database::connect()->table('presentations')
                ->where('SessionID', $id)
                ->update(['Session' => (string) $row['SessionNumber']]);
        }

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
