<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\UserModel;
use App\Models\UserModuleModel;

/**
 * Admin-only listing of wiki attachments joined with their owning wiki / page
 * and uploader. Lives in the `wiki` DB group; uploader names come from
 * `control.users` via a separate query (no cross-DB join).
 */
class WikiAttachmentsAdminController extends BaseApiController
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

    /**
     * POST /api/v1/admin/wiki-attachments/list
     * Body: { page?, per_page?, bucket?, wiki_id?, q? }
     */
    public function listAttachments()
    {
        if (!$this->requireAdminActor()) return $this->response;

        $page    = max(1, (int) ($this->request->getJsonVar('page') ?: 1));
        $perPage = min(500, max(1, (int) ($this->request->getJsonVar('per_page') ?: 100)));
        $bucket  = trim((string) $this->request->getJsonVar('bucket'));
        $wikiId  = (int) ($this->request->getJsonVar('wiki_id') ?: 0);
        $q       = trim((string) $this->request->getJsonVar('q'));

        $b = db_connect('wiki')->table('wiki_attachments a')
            ->select('a.AttachmentID, a.WikiID, a.PageID, a.StorageBucket, a.StorageKey,
                      a.OriginalName, a.MimeType, a.SizeBytes, a.Width, a.Height,
                      a.UploadedBy, a.CreatedAt,
                      w.Slug AS WikiSlug, w.Name AS WikiName,
                      p.Slug AS PageSlug, p.Title AS PageTitle')
            ->join('wikis w', 'w.WikiID = a.WikiID', 'left')
            ->join('wiki_pages p', 'p.PageID = a.PageID', 'left');

        if ($bucket !== '') $b->where('a.StorageBucket', $bucket);
        if ($wikiId > 0)    $b->where('a.WikiID', $wikiId);
        if ($q !== '')      $b->like('a.OriginalName', $q);

        $total = (clone $b)->countAllResults(false);
        $rows  = $b->orderBy('a.AttachmentID', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()->getResultArray();

        // Resolve uploader usernames from the control DB in one query.
        $uploaderIds = array_values(array_unique(array_filter(array_map(
            fn($r) => $r['UploadedBy'] !== null ? (int) $r['UploadedBy'] : null,
            $rows
        ))));
        $usernames = [];
        if ($uploaderIds) {
            $userRows = (new UserModel())->whereIn('UserID', $uploaderIds)
                ->select('UserID, UserName')->findAll();
            foreach ($userRows as $u) $usernames[(int) $u['UserID']] = $u['UserName'];
        }

        $data = array_map(function ($r) use ($usernames) {
            $uid = $r['UploadedBy'] !== null ? (int) $r['UploadedBy'] : null;
            return [
                'id'                   => (int) $r['AttachmentID'],
                'wiki_id'              => (int) $r['WikiID'],
                'wiki_slug'            => $r['WikiSlug'],
                'wiki_name'            => $r['WikiName'],
                'page_id'              => $r['PageID'] !== null ? (int) $r['PageID'] : null,
                'page_slug'            => $r['PageSlug'],
                'page_title'           => $r['PageTitle'],
                'storage_bucket'       => $r['StorageBucket'],
                'storage_key'          => $r['StorageKey'],
                'original_name'        => $r['OriginalName'],
                'mime_type'            => $r['MimeType'],
                'size_bytes'           => $r['SizeBytes'] !== null ? (int) $r['SizeBytes'] : null,
                'width'                => $r['Width'] !== null ? (int) $r['Width'] : null,
                'height'               => $r['Height'] !== null ? (int) $r['Height'] : null,
                'uploaded_by_id'       => $uid,
                'uploaded_by_username' => $uid !== null ? ($usernames[$uid] ?? null) : null,
                'created_at'           => $r['CreatedAt'] ?? null,
            ];
        }, $rows);

        return $this->respond([
            'data' => $data, 'total' => $total, 'page' => $page, 'per_page' => $perPage,
        ]);
    }
}
