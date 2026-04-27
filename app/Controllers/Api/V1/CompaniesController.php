<?php
namespace App\Controllers\Api\V1;

use App\Libraries\MarketTree;
use App\Models\CompanyModel;
use App\Models\MarketModel;
use Config\Database;

class CompaniesController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'             => 'CompanyID',
        'name'           => 'Name',
        'parent_id'      => 'ParentID',
        'is_parent'      => 'IsParent',
        'active'         => 'Active',
        'cn_name'        => 'CN_Name',
        'url'            => 'URL',
        'stock_market'   => 'Stock_Market',
        'ticker_symbol'  => 'Ticker_Symbol',
        'research_link'  => 'Research_link',
        'notes'          => 'Notes',
        'added'          => 'Added',
        'stamp'          => 'Stamp',
    ];

    private const READONLY_API_FIELDS = ['id', 'added', 'stamp', 'is_parent'];

    private const FILTERABLE = ['active', 'parent_id', 'stock_market'];
    private const SORTABLE   = ['id', 'name', 'added', 'stamp'];

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

    /** Attach market_ids array to a company row */
    private function attachMarkets(array &$row): void
    {
        $db = Database::connect();
        $rows = $db->table('company_markets')
            ->select('MarketID')
            ->where('CompanyID', (int) $row['id'])
            ->get()->getResultArray();
        $row['market_ids'] = array_map(fn($r) => (int) $r['MarketID'], $rows);
    }

    /** GET /api/v1/companies */
    public function index()
    {
        $req = $this->request;
        $page    = max(1, (int) $req->getGet('page') ?: 1);
        $perPage = max(1, min(100, (int) ($req->getGet('per_page') ?: 25)));
        $q       = trim((string) $req->getGet('q'));
        $sort    = (string) ($req->getGet('sort') ?: 'name');

        $builder = (new CompanyModel())->builder();

        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
        }

        if ($q !== '') {
            $builder->groupStart()
                ->like('Name', $q)
                ->orLike('CN_Name', $q)
                ->orLike('Ticker_Symbol', $q)
                ->groupEnd();
        }

        // Markets filter — supports comma-separated list with trailing '+' for include-descendants.
        // e.g. market_id=12,45+ → exact 12 OR subtree of 45
        $marketParam = trim((string) $req->getGet('market_id'));
        if ($marketParam !== '') {
            $ids = [];
            foreach (explode(',', $marketParam) as $tok) {
                $tok = trim($tok);
                if ($tok === '') continue;
                $includeDesc = str_ends_with($tok, '+');
                $base = (int) rtrim($tok, '+');
                if ($base <= 0) continue;
                if ($includeDesc) {
                    foreach (MarketTree::subtreeIds($base) as $i) $ids[] = $i;
                } else {
                    $ids[] = $base;
                }
            }
            // Single-flag fallback: ?market_id=12&include_descendants=1
            if ((int) $req->getGet('include_descendants') === 1 && count($ids) === 1) {
                $ids = MarketTree::subtreeIds($ids[0]);
            }
            $ids = array_values(array_unique($ids));
            if (!empty($ids)) {
                $sub = Database::connect()->table('company_markets')
                    ->select('CompanyID')
                    ->whereIn('MarketID', $ids)
                    ->getCompiledSelect();
                $builder->where("CompanyID IN ($sub)", null, false);
            } else {
                $builder->where('1=0', null, false);
            }
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

        $data = [];
        foreach ($rows as $r) {
            $api = $this->dbToApi($r);
            $this->attachMarkets($api);
            $data[] = $api;
        }

        return $this->response->setJSON([
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /** GET /api/v1/companies/{id} */
    public function show($id = null)
    {
        $row = (new CompanyModel())->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');
        $api = $this->dbToApi($row);
        $this->attachMarkets($api);

        // Embed contacts at this company (id, name, email, active)
        $db = Database::connect();
        $contacts = $db->table('contacts')
            ->select('ContactID, GivenName, FamilyName, Email, Active')
            ->where('CompanyID', (int) $id)
            ->orderBy('FamilyName', 'ASC')
            ->limit(500)
            ->get()->getResultArray();
        $api['contacts'] = array_map(fn($c) => [
            'id'          => (int) $c['ContactID'],
            'given_name'  => $c['GivenName'],
            'family_name' => $c['FamilyName'],
            'email'       => $c['Email'],
            'active'      => (int) $c['Active'],
        ], $contacts);

        return $this->response->setJSON(['data' => $api]);
    }

    /** GET /api/v1/companies/{id}/contacts */
    public function contacts($id = null)
    {
        $db = Database::connect();
        $rows = $db->table('contacts')
            ->select('ContactID, GivenName, FamilyName, Email, Active, Title')
            ->where('CompanyID', (int) $id)
            ->orderBy('FamilyName', 'ASC')
            ->get()->getResultArray();
        return $this->response->setJSON([
            'data' => array_map(fn($c) => [
                'id'          => (int) $c['ContactID'],
                'given_name'  => $c['GivenName'],
                'family_name' => $c['FamilyName'],
                'email'       => $c['Email'],
                'active'      => (int) $c['Active'],
                'title'       => $c['Title'],
            ], $rows),
        ]);
    }

    /** POST /api/v1/companies */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        if (!$this->validateData($payload, $this->validationRules(false))) {
            return $this->jsonError(422, 'validation_failed', $this->validator->getErrors());
        }
        $marketIds = $this->extractMarketIds($payload);
        $dbRow = $this->apiToDb($payload);
        if (!array_key_exists('Active', $dbRow)) $dbRow['Active'] = 1;
        if (!array_key_exists('Added', $dbRow)) $dbRow['Added'] = date('Y-m-d H:i:s');
        $dbRow['Stamp'] = date('Y-m-d H:i:s');

        $model = new CompanyModel();
        $id = $model->insert($dbRow, true);
        if (!$id) return $this->jsonError(500, 'insert_failed', $model->errors());

        $this->syncMarkets((int) $id, $marketIds);
        $this->refreshIsParent((int) $id);
        if (!empty($dbRow['ParentID'])) $this->refreshIsParent((int) $dbRow['ParentID']);

        return $this->show((int) $id)->setStatusCode(201);
    }

    /** PUT /api/v1/companies/{id} */
    public function update($id = null)
    {
        $model = new CompanyModel();
        $existing = $model->find((int) $id);
        if (!$existing) return $this->jsonError(404, 'not_found');

        $payload = $this->request->getJSON(true) ?? [];
        if (!$this->validateData($payload, $this->validationRules(true, (int) $id))) {
            return $this->jsonError(422, 'validation_failed', $this->validator->getErrors());
        }

        $hasMarketKey = array_key_exists('market_ids', $payload);
        $marketIds = $this->extractMarketIds($payload);
        $dbRow = $this->apiToDb($payload);
        if (empty($dbRow) && !$hasMarketKey) return $this->jsonError(400, 'no_updatable_fields');

        // Cannot deactivate parent if it has active children
        if (array_key_exists('Active', $dbRow) && (int) $dbRow['Active'] === 0) {
            $childCount = $model->builder()
                ->where('ParentID', (int) $id)
                ->where('Active', 1)
                ->countAllResults();
            if ($childCount > 0) {
                return $this->jsonError(409, 'has_active_children', [
                    'message' => 'Cannot deactivate a parent with active children. Deactivate the children first.',
                    'active_children' => $childCount,
                ]);
            }
        }

        if (!empty($dbRow)) {
            $dbRow['Stamp'] = date('Y-m-d H:i:s');
            if (!$model->update((int) $id, $dbRow)) {
                return $this->jsonError(500, 'update_failed', $model->errors());
            }
        }

        if ($hasMarketKey) $this->syncMarkets((int) $id, $marketIds);

        // Refresh IsParent flags for old & new parent
        $this->refreshIsParent((int) $id);
        $oldParent = (int) ($existing['ParentID'] ?? 0);
        $newParent = array_key_exists('ParentID', $dbRow) ? (int) $dbRow['ParentID'] : $oldParent;
        if ($oldParent) $this->refreshIsParent($oldParent);
        if ($newParent && $newParent !== $oldParent) $this->refreshIsParent($newParent);

        return $this->show((int) $id);
    }

    /** DELETE /api/v1/companies/{id} — soft-delete (Active=0) */
    public function delete($id = null)
    {
        $model = new CompanyModel();
        $row = $model->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');

        // Block if it is a parent with any active child
        $activeChildren = $model->builder()
            ->where('ParentID', (int) $id)
            ->where('Active', 1)
            ->countAllResults();
        if ($activeChildren > 0) {
            return $this->jsonError(409, 'has_active_children', [
                'message' => 'Cannot archive a parent company while it has active children.',
                'active_children' => $activeChildren,
            ]);
        }

        $model->update((int) $id, ['Active' => 0, 'Stamp' => date('Y-m-d H:i:s')]);
        if (!empty($row['ParentID'])) $this->refreshIsParent((int) $row['ParentID']);

        return $this->response->setJSON(['data' => ['id' => (int) $id, 'active' => 0, 'archived' => true]]);
    }

    private function extractMarketIds(array $payload): array
    {
        if (!isset($payload['market_ids']) || !is_array($payload['market_ids'])) return [];
        $ids = [];
        foreach ($payload['market_ids'] as $v) {
            $i = (int) $v;
            if ($i > 0) $ids[] = $i;
        }
        return array_values(array_unique($ids));
    }

    private function syncMarkets(int $companyId, array $marketIds): void
    {
        $db = Database::connect();
        // Validate all IDs exist (avoid orphan junction rows)
        if (!empty($marketIds)) {
            $existing = $db->table('markets')
                ->select('MarketID')
                ->whereIn('MarketID', $marketIds)
                ->get()->getResultArray();
            $valid = array_map(fn($r) => (int) $r['MarketID'], $existing);
            $marketIds = array_values(array_intersect($marketIds, $valid));
        }
        $db->transStart();
        $db->table('company_markets')->where('CompanyID', $companyId)->delete();
        if (!empty($marketIds)) {
            $rows = array_map(fn($mid) => [
                'CompanyID' => $companyId,
                'MarketID'  => $mid,
                'Added'     => date('Y-m-d H:i:s'),
            ], $marketIds);
            $db->table('company_markets')->insertBatch($rows);
        }
        $db->transComplete();
    }

    private function refreshIsParent(int $companyId): void
    {
        $model = new CompanyModel();
        $childCount = $model->builder()->where('ParentID', $companyId)->countAllResults();
        $model->update($companyId, ['IsParent' => $childCount > 0 ? 1 : 0]);
    }

    private function validationRules(bool $isUpdate, ?int $idForUnique = null): array
    {
        return [
            'name'           => ($isUpdate ? 'permit_empty' : 'required') . '|string|max_length[100]',
            'parent_id'      => 'permit_empty|is_natural',
            'active'         => 'permit_empty|in_list[0,1]',
            'cn_name'        => 'permit_empty|string|max_length[50]',
            'url'            => 'permit_empty|max_length[200]',
            'stock_market'   => 'permit_empty|string|max_length[10]',
            'ticker_symbol'  => 'permit_empty|string|max_length[10]',
            'research_link'  => 'permit_empty|max_length[200]',
            'notes'          => 'permit_empty|string',
        ];
    }
}
