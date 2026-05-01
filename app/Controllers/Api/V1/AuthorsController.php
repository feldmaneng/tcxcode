<?php
namespace App\Controllers\Api\V1;

use App\Models\AuthorModel;
use Config\Database;

class AuthorsController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'              => 'AuthorID',
        'author_number'   => 'AuthorNumber',
        'presenter'       => 'Presenter',
        'contact_id'      => 'ContactID',
        'given_name'      => 'GivenName',
        'family_name'     => 'FamilyName',
        'company'         => 'Company',
        'company_id'      => 'CompanyID',
        'presentation_id' => 'PresentationID',
    ];

    private const READONLY_API_FIELDS = ['id'];
    private const FILTERABLE = ['presentation_id', 'contact_id', 'company_id', 'presenter'];
    private const SORTABLE   = ['id', 'author_number', 'family_name'];

    public static function dbToApi(array $row): array
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
        $perPage = max(1, min(200, (int) ($req->getGet('per_page') ?: 50)));
        $sort    = (string) ($req->getGet('sort') ?: 'author_number');

        $builder = (new AuthorModel())->builder();
        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
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
            'data' => array_map(fn($r) => self::dbToApi($r), $rows),
            'pagination' => [
                'page' => $page, 'per_page' => $perPage,
                'total' => $total, 'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function show($id = null)
    {
        $row = (new AuthorModel())->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');
        return $this->response->setJSON(['data' => self::dbToApi($row)]);
    }

    /** GET /api/v1/presentations/{id}/authors */
    public function byPresentation($pid = null)
    {
        $rows = (new AuthorModel())->builder()
            ->where('PresentationID', (int) $pid)
            ->orderBy('AuthorNumber', 'ASC')
            ->orderBy('AuthorID', 'ASC')
            ->get()->getResultArray();
        return $this->response->setJSON(['data' => array_map(fn($r) => self::dbToApi($r), $rows)]);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        if (empty($dbRow['PresentationID'])) {
            return $this->jsonError(422, 'validation_failed', ['presentation_id' => 'required']);
        }
        // If contact_id supplied without snapshot fields, snapshot from contacts.
        if (!empty($dbRow['ContactID']) && (empty($dbRow['GivenName']) && empty($dbRow['FamilyName']))) {
            self::snapshotFromContact($dbRow, (int) $dbRow['ContactID']);
        }
        $model = new AuthorModel();
        $id = $model->insert($dbRow, true);
        if (!$id) return $this->jsonError(500, 'insert_failed', $model->errors());
        return $this->response->setStatusCode(201)->setJSON(['data' => self::dbToApi($model->find((int) $id))]);
    }

    public function update($id = null)
    {
        $model = new AuthorModel();
        if (!$model->find((int) $id)) return $this->jsonError(404, 'not_found');
        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        if (empty($dbRow)) return $this->jsonError(400, 'no_updatable_fields');
        if (!$model->update((int) $id, $dbRow)) {
            return $this->jsonError(500, 'update_failed', $model->errors());
        }
        return $this->response->setJSON(['data' => self::dbToApi($model->find((int) $id))]);
    }

    public function delete($id = null)
    {
        $model = new AuthorModel();
        if (!$model->find((int) $id)) return $this->jsonError(404, 'not_found');
        if (!$model->delete((int) $id)) return $this->jsonError(500, 'delete_failed', $model->errors());
        return $this->response->setJSON(['data' => ['id' => (int) $id, 'deleted' => true]]);
    }

    /**
     * Snapshot contact's name + company into the author row.
     * Used by both this controller and PresentationsController.
     */
    public static function snapshotFromContact(array &$dbRow, int $contactId): void
    {
        $contact = Database::connect()->table('contacts')
            ->select('GivenName, FamilyName, Company, CompanyID')
            ->where('ContactID', $contactId)
            ->get()->getRowArray();
        if (!$contact) return;
        $dbRow['GivenName']  = $contact['GivenName'];
        $dbRow['FamilyName'] = $contact['FamilyName'];
        $dbRow['Company']    = $contact['Company'];
        $dbRow['CompanyID']  = $contact['CompanyID'] !== null ? (int) $contact['CompanyID'] : null;
    }
}
