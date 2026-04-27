<?php
namespace App\Controllers\Api\V1;

use App\Libraries\MarketTree;
use App\Models\MarketModel;
use Config\Database;

class MarketsController extends BaseApiController
{
    private function row(array $r): array
    {
        return [
            'id'        => (int) $r['MarketID'],
            'parent_id' => isset($r['ParentID']) ? (int) $r['ParentID'] : null,
            'name'      => $r['Name'],
            'slug'      => $r['Slug'],
            'path'      => $r['Path'],
            'depth'     => (int) $r['Depth'],
            'active'    => (int) $r['Active'],
            'sort'      => (int) ($r['Sort'] ?? 0),
        ];
    }

    /** GET /api/v1/markets[?tree=1][&parent_id=ID][&include_inactive=1] */
    public function index()
    {
        $req = $this->request;
        $tree = (int) $req->getGet('tree') === 1;
        $parentId = $req->getGet('parent_id');
        $includeInactive = (int) $req->getGet('include_inactive') === 1;

        $b = (new MarketModel())->builder();
        if (!$includeInactive) $b->where('Active', 1);
        if ($parentId !== null && $parentId !== '') {
            $pid = (int) $parentId;
            if ($pid === 0) {
                $b->where('ParentID', null);
            } else {
                $b->where('ParentID', $pid);
            }
        }
        $b->orderBy('Sort', 'ASC')->orderBy('Name', 'ASC');
        $rows = $b->get()->getResultArray();
        $items = array_map(fn($r) => $this->row($r), $rows);

        if (!$tree || ($parentId !== null && $parentId !== '')) {
            return $this->response->setJSON(['data' => $items]);
        }

        // Build full tree (re-fetch unfiltered by parent)
        $all = (new MarketModel())->builder();
        if (!$includeInactive) $all->where('Active', 1);
        $all->orderBy('Depth', 'ASC')->orderBy('Sort', 'ASC')->orderBy('Name', 'ASC');
        $allRows = array_map(fn($r) => $this->row($r), $all->get()->getResultArray());

        $byId = [];
        foreach ($allRows as $r) { $r['children'] = []; $byId[$r['id']] = $r; }
        $roots = [];
        foreach ($byId as $id => &$node) {
            if ($node['parent_id'] && isset($byId[$node['parent_id']])) {
                $byId[$node['parent_id']]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);

        return $this->response->setJSON(['data' => $roots]);
    }

    /** GET /api/v1/markets/{id} */
    public function show($id = null)
    {
        $row = (new MarketModel())->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');
        return $this->response->setJSON(['data' => $this->row($row)]);
    }

    /** GET /api/v1/markets/{id}/descendants */
    public function descendants($id = null)
    {
        $ids = MarketTree::subtreeIds((int) $id);
        return $this->response->setJSON(['data' => ['ids' => $ids]]);
    }

    /** POST /api/v1/markets */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $rules = [
            'name'      => 'required|string|max_length[80]',
            'parent_id' => 'permit_empty|is_natural',
            'active'    => 'permit_empty|in_list[0,1]',
            'sort'      => 'permit_empty|integer',
        ];
        if (!$this->validateData($payload, $rules)) {
            return $this->jsonError(422, 'validation_failed', $this->validator->getErrors());
        }

        $name = trim((string) $payload['name']);
        $slug = MarketTree::slugify($name);
        $parentId = isset($payload['parent_id']) && $payload['parent_id'] !== '' ? (int) $payload['parent_id'] : null;

        // Unique within parent
        $dup = (new MarketModel())->builder()
            ->where('ParentID', $parentId)
            ->where('Slug', $slug)
            ->countAllResults();
        if ($dup > 0) {
            return $this->jsonError(409, 'duplicate_slug', ['message' => 'A sibling tag with the same name already exists.']);
        }

        [$path, $depth] = MarketTree::computePath($parentId, $slug);

        $model = new MarketModel();
        $id = $model->insert([
            'ParentID' => $parentId,
            'Name'     => $name,
            'Slug'     => $slug,
            'Path'     => $path,
            'Depth'    => $depth,
            'Active'   => array_key_exists('active', $payload) ? (int) $payload['active'] : 1,
            'Sort'     => (int) ($payload['sort'] ?? 0),
            'Added'    => date('Y-m-d H:i:s'),
            'Stamp'    => date('Y-m-d H:i:s'),
        ], true);
        if (!$id) return $this->jsonError(500, 'insert_failed', $model->errors());

        return $this->response->setStatusCode(201)->setJSON(['data' => $this->row($model->find((int) $id))]);
    }

    /** PUT /api/v1/markets/{id} */
    public function update($id = null)
    {
        $model = new MarketModel();
        $existing = $model->find((int) $id);
        if (!$existing) return $this->jsonError(404, 'not_found');

        $payload = $this->request->getJSON(true) ?? [];
        $rules = [
            'name'      => 'permit_empty|string|max_length[80]',
            'parent_id' => 'permit_empty|is_natural',
            'active'    => 'permit_empty|in_list[0,1]',
            'sort'      => 'permit_empty|integer',
        ];
        if (!$this->validateData($payload, $rules)) {
            return $this->jsonError(422, 'validation_failed', $this->validator->getErrors());
        }

        $update = ['Stamp' => date('Y-m-d H:i:s')];
        if (isset($payload['name'])) {
            $update['Name'] = trim((string) $payload['name']);
            $update['Slug'] = MarketTree::slugify($update['Name']);
        }
        if (array_key_exists('active', $payload)) $update['Active'] = (int) $payload['active'];
        if (array_key_exists('sort', $payload))   $update['Sort']   = (int) $payload['sort'];

        // If slug changed, recompute Path/Depth (under same parent unless reparent below)
        $parentChange = array_key_exists('parent_id', $payload);
        $newParentId  = $parentChange
            ? (($payload['parent_id'] === '' || $payload['parent_id'] === null) ? null : (int) $payload['parent_id'])
            : (isset($existing['ParentID']) ? (int) $existing['ParentID'] : null);

        if (isset($update['Slug']) && !$parentChange) {
            [$path, $depth] = MarketTree::computePath($newParentId, $update['Slug']);
            $update['Path'] = $path;
            $update['Depth'] = $depth;
        }

        $model->update((int) $id, $update);

        // Re-parent (this handles Path/Depth for self + descendants)
        if ($parentChange) {
            try {
                MarketTree::move((int) $id, $newParentId);
            } catch (\RuntimeException $e) {
                return $this->jsonError(409, 'cycle_detected', ['message' => $e->getMessage()]);
            }
        }

        return $this->response->setJSON(['data' => $this->row($model->find((int) $id))]);
    }

    /** DELETE /api/v1/markets/{id} — soft delete; blocked if active children or used by companies */
    public function delete($id = null)
    {
        $model = new MarketModel();
        $row = $model->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');

        $childCount = $model->builder()
            ->where('ParentID', (int) $id)
            ->where('Active', 1)
            ->countAllResults();
        if ($childCount > 0) {
            return $this->jsonError(409, 'has_active_children', [
                'message' => 'Deactivate or move child tags first.',
                'active_children' => $childCount,
            ]);
        }

        $usage = Database::connect()->table('company_markets')
            ->where('MarketID', (int) $id)
            ->countAllResults();
        if ($usage > 0) {
            return $this->jsonError(409, 'in_use', [
                'message' => 'This tag is in use by companies.',
                'company_count' => $usage,
            ]);
        }

        $model->update((int) $id, ['Active' => 0, 'Stamp' => date('Y-m-d H:i:s')]);
        return $this->response->setJSON(['data' => ['id' => (int) $id, 'active' => 0]]);
    }
}
