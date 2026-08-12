<?php
namespace App\Controllers\Api\V1;

use App\Models\LogoModel;
use App\Models\UserModuleModel;

/**
 * Logo library admin endpoints. Soft-deletes and default-logo management.
 */
class LogosController extends BaseApiController
{
    private const DB_TO_API = [
        'LogoID' => 'id',
        'Name' => 'name',
        'Url' => 'url',
        'StorageKey' => 'storage_key',
        'MimeType' => 'mime_type',
        'Width' => 'width',
        'Height' => 'height',
        'IsDefault' => 'is_default',
        'IsActive' => 'is_active',
        'CreatedAt' => 'created_at',
    ];

    private function dbToApi(array $row): array
    {
        $out = [];
        foreach (self::DB_TO_API as $db => $api) {
            if (array_key_exists($db, $row)) {
                $out[$api] = $row[$db];
            }
        }
        if (isset($out['id'])) {
            $out['id'] = (int) $out['id'];
        }
        foreach (['is_default', 'is_active'] as $k) {
            if (isset($out[$k])) {
                $out[$k] = (int) $out[$k];
            }
        }
        foreach (['width', 'height'] as $k) {
            if (isset($out[$k]) && $out[$k] !== null) {
                $out[$k] = (int) $out[$k];
            }
        }
        return $out;
    }

    private function apiToDb(array $payload): array
    {
        $out = [];
        if (array_key_exists('name', $payload)) {
            $out['Name'] = substr(trim((string) $payload['name']), 0, 120);
        }
        if (array_key_exists('url', $payload)) {
            $out['Url'] = substr((string) $payload['url'], 0, 500);
        }
        if (array_key_exists('storage_key', $payload)) {
            $out['StorageKey'] = substr((string) $payload['storage_key'], 0, 260) ?: null;
        }
        if (array_key_exists('mime_type', $payload)) {
            $out['MimeType'] = substr((string) $payload['mime_type'], 0, 60) ?: null;
        }
        if (array_key_exists('width', $payload) && $payload['width'] !== null) {
            $out['Width'] = (int) $payload['width'];
        }
        if (array_key_exists('height', $payload) && $payload['height'] !== null) {
            $out['Height'] = (int) $payload['height'];
        }
        if (array_key_exists('is_active', $payload)) {
            $out['IsActive'] = (int) $payload['is_active'];
        }
        return $out;
    }

    private function requireAdmin()
    {
        $actorId = \App\Libraries\ApiAuthContext::actingUserId();
        if (!$actorId) {
            return $this->jsonError(401, 'acting_user_required');
        }
        if (!(new UserModuleModel())->userHasModule($actorId, 'admin')) {
            return $this->jsonError(403, 'admin_required');
        }
        return null;
    }

    public function index()
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }
        $rows = (new LogoModel())->where('IsActive', 1)->orderBy('LogoID', 'DESC')->findAll();
        return $this->response->setJSON([
            'data' => array_map(fn($r) => $this->dbToApi($r), $rows),
        ]);
    }

    public function create()
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }
        $payload = (array) $this->request->getJSON(true);
        $row = $this->apiToDb($payload);
        if (empty($row['Name']) || empty($row['Url'])) {
            return $this->jsonError(422, 'validation_failed', ['required' => ['name', 'url']]);
        }

        $model = new LogoModel();
        if (!empty($payload['is_default'])) {
            $model->clearDefault();
        }
        $id = $model->insert($row, true);
        if (!$id) {
            return $this->jsonError(422, 'insert_failed', $model->errors());
        }
        return $this->response->setStatusCode(201)->setJSON([
            'data' => $this->dbToApi($model->find($id)),
        ]);
    }

    public function update(int $id)
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }
        $model = new LogoModel();
        if (!$model->find($id)) {
            return $this->jsonError(404, 'not_found');
        }
        $payload = (array) $this->request->getJSON(true);
        $row = $this->apiToDb($payload);
        if (!empty($payload['is_default'])) {
            $model->clearDefault();
        }
        if (!$model->update($id, $row)) {
            return $this->jsonError(422, 'update_failed', $model->errors());
        }
        return $this->response->setJSON([
            'data' => $this->dbToApi($model->find($id)),
        ]);
    }

    public function delete(int $id)
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }
        $model = new LogoModel();
        if (!$model->find($id)) {
            return $this->jsonError(404, 'not_found');
        }
        $model->update($id, ['IsActive' => 0]);
        return $this->response->setStatusCode(204);
    }

    public function setDefault(int $id)
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }
        $model = new LogoModel();
        if (!$model->find($id)) {
            return $this->jsonError(404, 'not_found');
        }
        $model->clearDefault();
        $model->update($id, ['IsDefault' => 1]);
        return $this->response->setJSON([
            'data' => $this->dbToApi($model->find($id)),
        ]);
    }

    public function options()
    {
        return $this->response->setStatusCode(204);
    }
}
