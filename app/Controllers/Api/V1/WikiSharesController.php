<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\AdminAuditLogModel;
use App\Models\UserModuleModel;
use App\Models\WikiPageModel;
use App\Models\WikiShareModel;

/**
 * WikiSharesController — admin-only management of public wiki page shares.
 *
 * Acting user identity comes from the X-Acting-User header (verified by
 * HmacAuthFilter). All endpoints require the actor to have the `admin` module.
 */
class WikiSharesController extends BaseApiController
{
    private function requireAdminActor(): ?int
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) {
            $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']);
            return null;
        }
        if (!(new UserModuleModel())->userHasModule($actorId, 'admin')) {
            $this->response->setStatusCode(403)->setJSON(['error' => 'admin_required']);
            return null;
        }
        return $actorId;
    }

    private function audit(int $actorId, string $action, ?string $type, ?string $id, ?array $details): void
    {
        (new AdminAuditLogModel())->log(
            $actorId, $action, $type, $id, $details, $this->request->getIPAddress()
        );
    }

    private function shapeShare(array $row): array
    {
        return [
            'id'               => (int) $row['ShareID'],
            'page_id'          => (int) $row['PageID'],
            'token'            => $row['Token'],
            'include_children' => (int) $row['IncludeChildren'] === 1,
            'expires_at'       => $row['ExpiresAt'],
            'revoked_at'       => $row['RevokedAt'],
            'created_by'       => $row['CreatedBy'] !== null ? (int) $row['CreatedBy'] : null,
            'created_at'       => $row['CreatedAt'],
        ];
    }

    /** POST /api/v1/admin/wiki-shares/list  Body: { wiki_id?, page_id? } */
    public function listShares()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;

        $b = (new WikiShareModel())->builder()->select('wiki_page_shares.*');
        $pageId = $this->request->getJsonVar('page_id');
        $wikiId = $this->request->getJsonVar('wiki_id');

        if ($pageId) {
            $b->where('PageID', (int) $pageId);
        } elseif ($wikiId) {
            // join to wiki_pages to filter by wiki
            $b->join('wiki_pages p', 'p.PageID = wiki_page_shares.PageID')
              ->where('p.WikiID', (int) $wikiId);
        }
        $rows = $b->orderBy('CreatedAt', 'DESC')->get()->getResultArray();

        return $this->respond([
            'data' => array_map(fn($r) => $this->shapeShare($r), $rows),
        ]);
    }

    /** POST /api/v1/admin/wiki-shares/create  Body: { page_id, include_children, expires_at? } */
    public function createShare()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;

        $pageId = (int) $this->request->getJsonVar('page_id');
        $page = (new WikiPageModel())->find($pageId);
        if (!$page || $page['DeletedAt']) return $this->jsonError(404, 'page_not_found');

        $includeChildren = $this->request->getJsonVar('include_children') ? 1 : 0;
        $expiresAt = $this->request->getJsonVar('expires_at') ?: null;
        if ($expiresAt) {
            $ts = strtotime((string) $expiresAt);
            if ($ts === false || $ts <= time()) {
                return $this->jsonError(400, 'invalid_expires_at');
            }
            $expiresAt = date('Y-m-d H:i:s', $ts);
        }

        $token = WikiShareModel::generateToken();
        $id = (new WikiShareModel())->insert([
            'PageID'          => $pageId,
            'Token'           => $token,
            'IncludeChildren' => $includeChildren,
            'ExpiresAt'       => $expiresAt,
            'CreatedBy'       => $actorId,
        ], true);

        $this->audit($actorId, 'wiki_share.create', 'wiki_page', (string) $pageId, [
            'token' => $token, 'include_children' => $includeChildren, 'expires_at' => $expiresAt,
        ]);

        return $this->respond([
            'id'               => (int) $id,
            'token'            => $token,
            'include_children' => (bool) $includeChildren,
            'expires_at'       => $expiresAt,
        ], 201);
    }

    /** POST /api/v1/admin/wiki-shares/update  Body: { share_id, include_children?, expires_at? (string|null) } */
    public function updateShare()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $shareId = (int) $this->request->getJsonVar('share_id');
        $share = (new WikiShareModel())->find($shareId);
        if (!$share) return $this->jsonError(404, 'not_found');

        $patch = [];
        $ic = $this->request->getJsonVar('include_children');
        if ($ic !== null) $patch['IncludeChildren'] = $ic ? 1 : 0;

        if ($this->request->getJsonVar('expires_at') !== null) {
            $ea = $this->request->getJsonVar('expires_at');
            if ($ea === '' || $ea === false) {
                $patch['ExpiresAt'] = null;
            } else {
                $ts = strtotime((string) $ea);
                if ($ts === false) return $this->jsonError(400, 'invalid_expires_at');
                $patch['ExpiresAt'] = date('Y-m-d H:i:s', $ts);
            }
        }

        if ($patch) (new WikiShareModel())->update($shareId, $patch);
        $this->audit($actorId, 'wiki_share.update', 'wiki_share', (string) $shareId, $patch);
        return $this->respond(['ok' => true]);
    }

    /** POST /api/v1/admin/wiki-shares/revoke  Body: { share_id } */
    public function revokeShare()
    {
        if (!($actorId = $this->requireAdminActor())) return $this->response;
        $shareId = (int) $this->request->getJsonVar('share_id');
        $share = (new WikiShareModel())->find($shareId);
        if (!$share) return $this->jsonError(404, 'not_found');

        (new WikiShareModel())->update($shareId, ['RevokedAt' => date('Y-m-d H:i:s')]);
        $this->audit($actorId, 'wiki_share.revoke', 'wiki_share', (string) $shareId, []);
        return $this->respond(['ok' => true]);
    }
}
