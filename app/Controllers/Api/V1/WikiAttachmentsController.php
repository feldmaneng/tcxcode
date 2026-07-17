<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\UserWikiPermissionModel;
use App\Models\WikiAttachmentModel;
use App\Models\WikiPageModel;

/**
 * WikiAttachmentsController — registers attachment metadata after the file is
 * uploaded to Lovable Cloud Storage. The actual upload is performed by a
 * frontend server function that issues a signed upload URL.
 *
 * Acting user identity comes from the X-Acting-User header (verified by
 * HmacAuthFilter and exposed via ApiAuthContext).
 */
class WikiAttachmentsController extends BaseApiController
{
    private function checkWikiWrite(int $wikiId): ?int
    {
        $userId = ApiAuthContext::actingUserId();
        if (!$userId) {
            $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']);
            return null;
        }
        $perm = (new UserWikiPermissionModel())->permissionFor($userId, $wikiId);
        if ($perm !== 'write_edit') {
            $this->response->setStatusCode(403)->setJSON(['error' => 'write_required']);
            return null;
        }
        return $userId;
    }

    /** POST /api/v1/wiki-attachments  Body: { wiki_id, page_id?, storage_bucket, storage_key, original_name, mime_type?, size_bytes?, width?, height? } */
    public function create()
    {
        $wikiId = (int) $this->request->getJsonVar('wiki_id');
        $userId = $this->checkWikiWrite($wikiId);
        if (!$userId) return $this->response;

        $id = (new WikiAttachmentModel())->insert([
            'WikiID'        => $wikiId,
            'PageID'        => $this->request->getJsonVar('page_id') ?: null,
            'StorageBucket' => (string) $this->request->getJsonVar('storage_bucket'),
            'StorageKey'    => (string) $this->request->getJsonVar('storage_key'),
            'OriginalName'  => (string) $this->request->getJsonVar('original_name'),
            'MimeType'      => $this->request->getJsonVar('mime_type'),
            'SizeBytes'     => $this->request->getJsonVar('size_bytes'),
            'Width'         => $this->request->getJsonVar('width'),
            'Height'        => $this->request->getJsonVar('height'),
            'UploadedBy'    => $userId,
        ], true);
        return $this->respond(['id' => (int) $id], 201);
    }

    /** GET /api/v1/wiki-pages/(:num)/attachments */
    public function listForPage(int $pageId)
    {
        $page = (new WikiPageModel())->find($pageId);
        if (!$page) return $this->jsonError(404, 'page_not_found');
        $userId = ApiAuthContext::actingUserId();
        if (!$userId) return $this->jsonError(401, 'acting_user_required');
        if ((new UserWikiPermissionModel())->permissionFor($userId, (int) $page['WikiID']) === null) {
            return $this->jsonError(403, 'no_wiki_access');
        }
        $rows = (new WikiAttachmentModel())->where('PageID', $pageId)->findAll();
        return $this->respond(['data' => $rows]);
    }

    /** POST /api/v1/wiki-attachments/find  Body: { storage_key } */
    public function find()
    {
        $key = (string) $this->request->getJsonVar('storage_key');
        if ($key === '') return $this->jsonError(400, 'storage_key_required');
        $userId = ApiAuthContext::actingUserId();
        if (!$userId) return $this->jsonError(401, 'acting_user_required');
        $row = (new WikiAttachmentModel())->where('StorageKey', $key)->first();
        if (!$row) return $this->jsonError(404, 'attachment_not_found');
        $wikiId = (int) $row['WikiID'];
        if ((new UserWikiPermissionModel())->permissionFor($userId, $wikiId) === null) {
            return $this->jsonError(403, 'no_wiki_access');
        }
        return $this->respond([
            'wiki_id'        => $wikiId,
            'page_id'        => $row['PageID'] !== null ? (int) $row['PageID'] : null,
            'storage_bucket' => $row['StorageBucket'],
            'storage_key'    => $row['StorageKey'],
        ]);
    }
}

