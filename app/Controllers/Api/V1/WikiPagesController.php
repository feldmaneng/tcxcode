<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\UserWikiPermissionModel;
use App\Models\WikiModel;
use App\Models\WikiPageModel;
use App\Models\WikiRevisionModel;

/**
 * WikiPagesController — wiki pages, revisions, hierarchy.
 *
 * Acting user identity comes from the X-Acting-User header (verified by
 * HmacAuthFilter and exposed via ApiAuthContext).
 */
class WikiPagesController extends BaseApiController
{
    /** Returns [userId, permission] or null if 401/403 has already been written. */
    private function checkAccess(int $wikiId, bool $needWrite): ?array
    {
        $userId = ApiAuthContext::actingUserId();
        if (!$userId) {
            $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']);
            return null;
        }
        $perm = (new UserWikiPermissionModel())->permissionFor($userId, $wikiId);
        if ($perm === null) {
            $this->response->setStatusCode(403)->setJSON(['error' => 'no_wiki_access']);
            return null;
        }
        if ($needWrite && $perm !== 'write_edit') {
            $this->response->setStatusCode(403)->setJSON(['error' => 'write_required']);
            return null;
        }
        return [$userId, $perm];
    }

    /** GET /api/v1/wikis  — list wikis the acting user can access */
    public function listAccessibleWikis()
    {
        $userId = ApiAuthContext::actingUserId();
        if (!$userId) return $this->jsonError(401, 'acting_user_required');

        $rows = (new UserWikiPermissionModel())->wikisForUser($userId);
        return $this->respond(['data' => array_map(fn($r) => [
            'id'          => (int) $r['WikiID'],
            'slug'        => $r['Slug'],
            'name'        => $r['Name'],
            'description' => $r['Description'] ?? null,
            'permission'  => $r['Permission'],
        ], $rows)]);
    }

    /** GET /api/v1/wikis/(:slug) */
    public function showWiki(string $slug)
    {
        $wiki = (new WikiModel())->where('Slug', $slug)->first();
        if (!$wiki) return $this->jsonError(404, 'wiki_not_found');
        if (!$this->checkAccess((int) $wiki['WikiID'], false)) return $this->response;

        return $this->respond(['wiki' => [
            'id'          => (int) $wiki['WikiID'],
            'slug'        => $wiki['Slug'],
            'name'        => $wiki['Name'],
            'description' => $wiki['Description'],
        ]]);
    }

    /** GET /api/v1/wikis/(:slug)/tree */
    public function tree(string $slug)
    {
        $wiki = (new WikiModel())->where('Slug', $slug)->first();
        if (!$wiki) return $this->jsonError(404, 'wiki_not_found');
        if (!$this->checkAccess((int) $wiki['WikiID'], false)) return $this->response;

        $pages = (new WikiPageModel())->treeForWiki((int) $wiki['WikiID']);
        return $this->respond(['data' => array_map(fn($p) => [
            'id'         => (int) $p['PageID'],
            'parent_id'  => $p['ParentID'] !== null ? (int) $p['ParentID'] : null,
            'slug'       => $p['Slug'],
            'title'      => $p['Title'],
            'sort_order' => (int) $p['SortOrder'],
        ], $pages)]);
    }

    /** GET /api/v1/wiki-pages/(:num) */
    public function show(int $pageId)
    {
        $page = (new WikiPageModel())->find($pageId);
        if (!$page || $page['DeletedAt']) return $this->jsonError(404, 'not_found');
        if (!$this->checkAccess((int) $page['WikiID'], false)) return $this->response;

        $rev = $page['CurrentRevisionID']
            ? (new WikiRevisionModel())->find((int) $page['CurrentRevisionID'])
            : null;

        return $this->respond(['page' => [
            'id'                  => (int) $page['PageID'],
            'wiki_id'             => (int) $page['WikiID'],
            'parent_id'           => $page['ParentID'] !== null ? (int) $page['ParentID'] : null,
            'slug'                => $page['Slug'],
            'title'               => $page['Title'],
            'sort_order'          => (int) $page['SortOrder'],
            'current_revision_id' => $page['CurrentRevisionID'] !== null ? (int) $page['CurrentRevisionID'] : null,
            'body_markdown'       => $rev['BodyMarkdown'] ?? '',
            'body_html'           => $rev['BodyHtml'] ?? '',
            'updated_at'          => $page['UpdatedAt'],
        ]]);
    }

    /** POST /api/v1/wiki-pages  Body: { wiki_id, parent_id?, slug, title, body_markdown, body_html?, edit_summary? } */
    public function create()
    {
        $wikiId = (int) $this->request->getJsonVar('wiki_id');
        $access = $this->checkAccess($wikiId, true);
        if (!$access) return $this->response;
        [$userId] = $access;

        $title = trim((string) $this->request->getJsonVar('title'));
        $slug  = trim((string) $this->request->getJsonVar('slug')) ?: $this->slugify($title);
        if ($title === '') return $this->jsonError(400, 'title_required');

        $db = db_connect('control');
        $db->transStart();

        $pageId = (new WikiPageModel())->insert([
            'WikiID'    => $wikiId,
            'ParentID'  => $this->request->getJsonVar('parent_id') ?: null,
            'Slug'      => $slug,
            'Title'     => $title,
            'SortOrder' => (int) ($this->request->getJsonVar('sort_order') ?: 0),
            'CreatedBy' => $userId,
        ], true);

        $revId = (new WikiRevisionModel())->insert([
            'PageID'       => (int) $pageId,
            'Title'        => $title,
            'BodyMarkdown' => (string) $this->request->getJsonVar('body_markdown'),
            'BodyHtml'     => $this->request->getJsonVar('body_html'),
            'EditedBy'     => $userId,
            'EditSummary'  => $this->request->getJsonVar('edit_summary'),
        ], true);

        (new WikiPageModel())->update((int) $pageId, ['CurrentRevisionID' => (int) $revId]);
        $db->transComplete();

        return $this->respond(['id' => (int) $pageId, 'revision_id' => (int) $revId], 201);
    }

    /** PUT /api/v1/wiki-pages/(:num)  Body: { title?, slug?, parent_id?, sort_order?, body_markdown, body_html?, edit_summary? } */
    public function update(int $pageId)
    {
        $page = (new WikiPageModel())->find($pageId);
        if (!$page || $page['DeletedAt']) return $this->jsonError(404, 'not_found');
        $access = $this->checkAccess((int) $page['WikiID'], true);
        if (!$access) return $this->response;
        [$userId] = $access;

        $title = $this->request->getJsonVar('title') ?? $page['Title'];
        $body  = $this->request->getJsonVar('body_markdown');
        if ($body === null) return $this->jsonError(400, 'body_required');

        $db = db_connect('control');
        $db->transStart();

        $patch = ['Title' => $title];
        foreach (['slug' => 'Slug', 'parent_id' => 'ParentID', 'sort_order' => 'SortOrder'] as $in => $col) {
            $v = $this->request->getJsonVar($in);
            if ($v !== null) $patch[$col] = $v;
        }
        (new WikiPageModel())->update($pageId, $patch);

        $revId = (new WikiRevisionModel())->insert([
            'PageID'       => $pageId,
            'Title'        => $title,
            'BodyMarkdown' => (string) $body,
            'BodyHtml'     => $this->request->getJsonVar('body_html'),
            'EditedBy'     => $userId,
            'EditSummary'  => $this->request->getJsonVar('edit_summary'),
        ], true);

        (new WikiPageModel())->update($pageId, ['CurrentRevisionID' => (int) $revId]);
        $db->transComplete();

        return $this->respond(['ok' => true, 'revision_id' => (int) $revId]);
    }

    /** DELETE /api/v1/wiki-pages/(:num) */
    public function delete($pageId = null)
    {
        $page = (new WikiPageModel())->find($pageId);
        if (!$page) return $this->jsonError(404, 'not_found');
        if (!$this->checkAccess((int) $page['WikiID'], true)) return $this->response;
        (new WikiPageModel())->update($pageId, ['DeletedAt' => date('Y-m-d H:i:s')]);
        return $this->respond(['ok' => true]);
    }

    /** GET /api/v1/wiki-pages/(:num)/revisions */
    public function revisions(int $pageId)
    {
        $page = (new WikiPageModel())->find($pageId);
        if (!$page) return $this->jsonError(404, 'not_found');
        if (!$this->checkAccess((int) $page['WikiID'], false)) return $this->response;

        $rows = (new WikiRevisionModel())
            ->where('PageID', $pageId)
            ->orderBy('RevisionID', 'DESC')
            ->findAll();

        return $this->respond(['data' => array_map(fn($r) => [
            'id'           => (int) $r['RevisionID'],
            'title'        => $r['Title'],
            'edited_by'    => $r['EditedBy'] !== null ? (int) $r['EditedBy'] : null,
            'edited_at'    => $r['EditedAt'],
            'edit_summary' => $r['EditSummary'],
        ], $rows)]);
    }

    /** GET /api/v1/wiki-revisions/(:num) */
    public function showRevision(int $revisionId)
    {
        $rev = (new WikiRevisionModel())->find($revisionId);
        if (!$rev) return $this->jsonError(404, 'not_found');
        $page = (new WikiPageModel())->find((int) $rev['PageID']);
        if (!$page) return $this->jsonError(404, 'page_not_found');
        if (!$this->checkAccess((int) $page['WikiID'], false)) return $this->response;
        return $this->respond(['revision' => $rev]);
    }

    /** POST /api/v1/wiki-revisions/(:num)/restore */
    public function restoreRevision(int $revisionId)
    {
        $rev = (new WikiRevisionModel())->find($revisionId);
        if (!$rev) return $this->jsonError(404, 'not_found');
        $page = (new WikiPageModel())->find((int) $rev['PageID']);
        if (!$page) return $this->jsonError(404, 'page_not_found');
        $access = $this->checkAccess((int) $page['WikiID'], true);
        if (!$access) return $this->response;
        [$userId] = $access;

        $newId = (new WikiRevisionModel())->insert([
            'PageID'       => (int) $rev['PageID'],
            'Title'        => $rev['Title'],
            'BodyMarkdown' => $rev['BodyMarkdown'],
            'BodyHtml'     => $rev['BodyHtml'],
            'EditedBy'     => $userId,
            'EditSummary'  => 'Restored from revision #' . $rev['RevisionID'],
        ], true);
        (new WikiPageModel())->update((int) $rev['PageID'], [
            'Title' => $rev['Title'],
            'CurrentRevisionID' => (int) $newId,
        ]);
        return $this->respond(['ok' => true, 'revision_id' => (int) $newId]);
    }

    private function slugify(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim($s, '-') ?: 'page-' . substr(md5(uniqid()), 0, 6);
    }
}
