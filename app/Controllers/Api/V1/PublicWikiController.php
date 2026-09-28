<?php
namespace App\Controllers\Api\V1;

use App\Models\WikiAttachmentModel;
use App\Models\WikiModel;
use App\Models\WikiPageModel;
use App\Models\WikiRevisionModel;
use App\Models\WikiShareModel;

/**
 * PublicWikiController — read-only access to wiki pages via a share token.
 *
 * The endpoints sit behind the same HMAC service-key filter as the rest of the
 * API, so only the TanStack server can call them. The token is the bearer of
 * authority: it does NOT require X-Acting-User. The TanStack server in turn
 * exposes these to anonymous visitors over its own /p/{token} routes.
 */
class PublicWikiController extends BaseApiController
{
    private function shapePageMeta(array $page): array
    {
        return [
            'id'         => (int) $page['PageID'],
            'wiki_id'    => (int) $page['WikiID'],
            'parent_id'  => $page['ParentID'] !== null ? (int) $page['ParentID'] : null,
            'slug'       => $page['Slug'],
            'title'      => $page['Title'],
            'sort_order' => (int) $page['SortOrder'],
        ];
    }

    /**
     * GET /api/v1/public/wiki/share/(:token)
     *
     * Returns wiki + root page + (if include_children) the descendant tree.
     */
    public function getShare(string $token)
    {
        $share = (new WikiShareModel())->findActiveByToken($token);
        if (!$share) return $this->jsonError(404, 'share_not_found');

        $page = (new WikiPageModel())->find((int) $share['PageID']);
        if (!$page || $page['DeletedAt']) return $this->jsonError(404, 'page_not_found');

        $wiki = (new WikiModel())->find((int) $page['WikiID']);
        if (!$wiki) return $this->jsonError(404, 'wiki_not_found');

        $tree = [];
        if ((int) $share['IncludeChildren'] === 1) {
            $tree = $this->descendantsOf((int) $page['PageID']);
        }

        return $this->respond([
            'wiki'             => ['id' => (int) $wiki['WikiID'], 'slug' => $wiki['Slug'], 'name' => $wiki['Name']],
            'root_page'        => $this->shapePageMeta($page),
            'include_children' => (int) $share['IncludeChildren'] === 1,
            'expires_at'       => $share['ExpiresAt'],
            'tree'             => $tree,
        ]);
    }

    /**
     * GET /api/v1/public/wiki/share/(:token)/page/(:num)
     */
    public function getSharePage(string $token, int $pageId)
    {
        $share = (new WikiShareModel())->findActiveByToken($token);
        if (!$share) return $this->jsonError(404, 'share_not_found');

        if (!$this->pageWithinShare((int) $pageId, $share)) {
            return $this->jsonError(404, 'page_not_in_share');
        }

        $page = (new WikiPageModel())->find($pageId);
        if (!$page || $page['DeletedAt']) return $this->jsonError(404, 'page_not_found');

        $rev = $page['CurrentRevisionID']
            ? (new WikiRevisionModel())->find((int) $page['CurrentRevisionID'])
            : null;

        return $this->respond([
            'page' => array_merge($this->shapePageMeta($page), [
                'body_markdown' => $rev['BodyMarkdown'] ?? '',
                'body_html'     => $rev['BodyHtml'] ?? '',
                'updated_at'    => $page['UpdatedAt'],
            ]),
        ]);
    }

    /**
     * GET /api/v1/public/wiki/share/(:token)/attachment/(:num)
     *
     * Returns enough info for the TanStack server to mint a short-lived
     * signed URL. The PHP layer NEVER mints the signed URL itself — that's
     * the TanStack server's job (it owns the Supabase service key).
     */
    public function getShareAttachment(string $token, int $attachmentId)
    {
        $share = (new WikiShareModel())->findActiveByToken($token);
        if (!$share) return $this->jsonError(404, 'share_not_found');

        $att = (new WikiAttachmentModel())->find($attachmentId);
        if (!$att || !$att['PageID']) return $this->jsonError(404, 'attachment_not_found');

        if (!$this->pageWithinShare((int) $att['PageID'], $share)) {
            return $this->jsonError(404, 'attachment_not_in_share');
        }

        return $this->respond([
            'storage_bucket' => $att['StorageBucket'],
            'storage_key'    => $att['StorageKey'],
            'original_name'  => $att['OriginalName'],
            'mime_type'      => $att['MimeType'],
        ]);
    }

    private function pageWithinShare(int $pageId, array $share): bool
    {
        $rootId = (int) $share['PageID'];
        if ($pageId === $rootId) return true;
        if ((int) $share['IncludeChildren'] !== 1) return false;

        // Walk up from $pageId until we hit the root or run out (capped at 32).
        $db = db_connect('wiki');
        $current = $pageId;
        for ($depth = 0; $depth < 32; $depth++) {
            $row = $db->table('wiki_pages')
                ->select('ParentID, DeletedAt')
                ->where('PageID', $current)
                ->get()->getRowArray();
            if (!$row || $row['DeletedAt']) return false;
            if ($row['ParentID'] === null) return false;
            if ((int) $row['ParentID'] === $rootId) return true;
            $current = (int) $row['ParentID'];
        }
        return false;
    }

    /** Recursive (BFS) descendants of a page, excluding soft-deleted. */
    private function descendantsOf(int $rootId): array
    {
        $db = db_connect('wiki');
        $out = [];
        $queue = [$rootId];
        // Cap total nodes to keep payload bounded.
        $maxNodes = 1000;
        while ($queue && count($out) < $maxNodes) {
            $batch = $queue;
            $queue = [];
            $rows = $db->table('wiki_pages')
                ->select('PageID, WikiID, ParentID, Slug, Title, SortOrder')
                ->whereIn('ParentID', $batch)
                ->where('DeletedAt', null)
                ->orderBy('SortOrder', 'ASC')
                ->orderBy('Title', 'ASC')
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $out[] = $this->shapePageMeta($r);
                $queue[] = (int) $r['PageID'];
                if (count($out) >= $maxNodes) break;
            }
        }
        return $out;
    }
}
