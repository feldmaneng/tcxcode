<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\ModuleAccess;
use App\Models\ExpoDirectoryTagModel;
use App\Models\ExpoTagModel;
use App\Models\UserModuleModel;

/**
 * Global exhibitor tag list (sponsorship levels / advertising packages).
 *
 * Reads: any signed-in user (badges are visible to coordinators too).
 * Writes: admins and explicit `expo` module holders only.
 */
class ExpoTagsController extends BaseApiController
{
    private function actorId(): ?int
    {
        return ApiAuthContext::actingUserId();
    }

    /** Admin or explicit `expo` module grant. Service calls are trusted. */
    private function requirePrivileged()
    {
        $userId = $this->actorId();
        if ($userId === null) return null;
        $codes = ModuleAccess::codesForUser($userId);
        if (in_array('admin', $codes, true)) return null;
        if ((new UserModuleModel())->userHasModule($userId, 'expo')) return null;
        return $this->jsonError(403, 'forbidden');
    }

    private function toApi(array $row): array
    {
        return [
            'id'       => (int) $row['TagID'],
            'name'     => (string) $row['Name'],
            'category' => (string) ($row['Category'] ?? 'sponsorship'),
            'sort'     => (int) ($row['Sort'] ?? 0),
            'active'   => (int) ($row['Active'] ?? 1),
        ];
    }

    /** GET /api/v1/expo-tags?active_only=1 */
    public function index()
    {
        $activeOnly = in_array((string) ($this->request->getGet('active_only') ?? ''), ['1', 'true'], true);
        $rows = (new ExpoTagModel())->allSorted($activeOnly);
        return $this->response->setJSON(['data' => array_map(fn($r) => $this->toApi($r), $rows)]);
    }

    /** POST /api/v1/expo-tags  { name, category?, sort?, active? } */
    public function create()
    {
        if ($deny = $this->requirePrivileged()) return $deny;

        $payload  = (array) $this->request->getJSON(true);
        $name     = trim((string) ($payload['name'] ?? ''));
        if ($name === '') return $this->jsonError(422, 'validation_failed', ['required' => ['name']]);

        $model = new ExpoTagModel();
        if ($existing = $model->findByName($name)) {
            return $this->jsonError(409, 'tag_exists', ['tag' => $this->toApi($existing)]);
        }

        $id = (int) $model->insert([
            'Name'     => mb_substr($name, 0, 60),
            'Category' => mb_substr(trim((string) ($payload['category'] ?? 'sponsorship')) ?: 'sponsorship', 0, 40),
            'Sort'     => (int) ($payload['sort'] ?? 0),
            'Active'   => (int) ($payload['active'] ?? 1) === 0 ? 0 : 1,
        ], true);
        if ($id <= 0) return $this->jsonError(500, 'insert_failed');

        return $this->response->setStatusCode(201)->setJSON(['data' => $this->toApi($model->find($id))]);
    }

    /** PUT /api/v1/expo-tags/{id} */
    public function update(int $id)
    {
        if ($deny = $this->requirePrivileged()) return $deny;

        $model = new ExpoTagModel();
        $row   = $model->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');

        $payload = (array) $this->request->getJSON(true);
        $patch   = [];
        if (array_key_exists('name', $payload)) {
            $name = trim((string) $payload['name']);
            if ($name === '') return $this->jsonError(422, 'validation_failed', ['required' => ['name']]);
            $dupe = $model->findByName($name);
            if ($dupe && (int) $dupe['TagID'] !== $id) return $this->jsonError(409, 'tag_exists');
            $patch['Name'] = mb_substr($name, 0, 60);
        }
        if (array_key_exists('category', $payload)) {
            $patch['Category'] = mb_substr(trim((string) $payload['category']) ?: 'sponsorship', 0, 40);
        }
        if (array_key_exists('sort', $payload))   $patch['Sort']   = (int) $payload['sort'];
        if (array_key_exists('active', $payload)) $patch['Active'] = ((int) $payload['active']) === 0 ? 0 : 1;

        if ($patch) $model->update($id, $patch);
        return $this->response->setJSON(['data' => $this->toApi($model->find($id))]);
    }

    /**
     * DELETE /api/v1/expo-tags/{id}
     * A tag still applied to exhibitors is retired (Active = 0) instead of
     * deleted so historical assignments survive.
     */
    public function delete(int $id)
    {
        if ($deny = $this->requirePrivileged()) return $deny;

        $model = new ExpoTagModel();
        $row   = $model->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');

        $inUse = (new ExpoDirectoryTagModel())->builder()->where('TagID', $id)->countAllResults() > 0;
        if ($inUse) {
            $model->update($id, ['Active' => 0]);
            return $this->response->setJSON(['data' => $this->toApi($model->find($id)), 'retired' => true]);
        }

        $model->delete($id);
        return $this->response->setStatusCode(204);
    }

    /** CORS preflight. */
    public function options()
    {
        return $this->response->setStatusCode(204);
    }
}
