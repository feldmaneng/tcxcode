<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\UserWikiPermissionModel;
use App\Models\WikiCommentModel;
use App\Models\WikiPageModel;

class WikiCommentsController extends BaseApiController
{
    /** Returns ['page' => ..., 'user_id' => int, 'permission' => str] or null. */
    private function pageAndUser(int $pageId, bool $needWrite = false): ?array
    {
        $page = (new WikiPageModel())->find($pageId);
        if (!$page || $page['DeletedAt']) {
            $this->response->setStatusCode(404)->setJSON(['error' => 'page_not_found']);
            return null;
        }
        $userId = ApiAuthContext::actingUserId();
        if (!$userId) {
            $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']);
            return null;
        }
        $perm = (new UserWikiPermissionModel())->permissionFor($userId, (int) $page['WikiID']);
        if ($perm === null) {
            $this->response->setStatusCode(403)->setJSON(['error' => 'no_wiki_access']);
            return null;
        }
        if ($needWrite && $perm !== 'write_edit') {
            $this->response->setStatusCode(403)->setJSON(['error' => 'write_required']);
            return null;
        }
        return ['page' => $page, 'user_id' => $userId, 'permission' => $perm];
    }

    /** GET /api/v1/wiki-pages/(:num)/comments */
    public function listForPage(int $pageId)
    {
        $ctx = $this->pageAndUser($pageId);
        if (!$ctx) return $this->response;

        // wiki_comments lives in the `wiki` DB; users lives in `control`.
        // Cross-database joins aren't safe to assume, so query separately
        // and stitch the author fields in PHP.
        $rows = db_connect('wiki')->table('wiki_comments')
            ->where('PageID', $pageId)
            ->where('DeletedAt', null)
            ->orderBy('CreatedAt', 'ASC')
            ->get()->getResultArray();

        $userIds = array_values(array_unique(array_filter(array_map(
            fn($r) => $r['AuthorUserID'] !== null ? (int) $r['AuthorUserID'] : null,
            $rows,
        ))));
        $usersById = [];
        if (!empty($userIds)) {
            $userRows = db_connect('control')->table('users')
                ->select('UserID, UserName, GivenName')
                ->whereIn('UserID', $userIds)
                ->get()->getResultArray();
            foreach ($userRows as $u) {
                $usersById[(int) $u['UserID']] = $u;
            }
        }

        return $this->respond(['data' => array_map(function ($r) use ($usersById) {
            $aid = $r['AuthorUserID'] !== null ? (int) $r['AuthorUserID'] : null;
            $u   = $aid !== null ? ($usersById[$aid] ?? null) : null;
            return [
                'id'                => (int) $r['CommentID'],
                'parent_comment_id' => $r['ParentCommentID'] !== null ? (int) $r['ParentCommentID'] : null,
                'body_markdown'     => $r['BodyMarkdown'],
                'author_user_id'    => $aid,
                'author_username'   => $u['UserName']  ?? null,
                'author_given_name' => $u['GivenName'] ?? null,
                'created_at'        => $r['CreatedAt'],
                'updated_at'        => $r['UpdatedAt'],
            ];
        }, $rows)]);
    }

    /** POST /api/v1/wiki-pages/(:num)/comments  Body: { body_markdown, parent_comment_id? } */
    public function create(int $pageId)
    {
        $ctx = $this->pageAndUser($pageId, false);
        if (!$ctx) return $this->response;
        $body = trim((string) $this->request->getJsonVar('body_markdown'));
        if ($body === '') return $this->jsonError(400, 'body_required');

        $id = (new WikiCommentModel())->insert([
            'PageID'          => $pageId,
            'ParentCommentID' => $this->request->getJsonVar('parent_comment_id') ?: null,
            'BodyMarkdown'    => $body,
            'AuthorUserID'    => $ctx['user_id'],
        ], true);
        return $this->respond(['id' => (int) $id], 201);
    }

    /** PUT /api/v1/wiki-comments/(:num)  Body: { body_markdown } */
    public function update(int $commentId)
    {
        $c = (new WikiCommentModel())->find($commentId);
        if (!$c || $c['DeletedAt']) return $this->jsonError(404, 'not_found');
        $ctx = $this->pageAndUser((int) $c['PageID'], false);
        if (!$ctx) return $this->response;
        if ((int) $c['AuthorUserID'] !== $ctx['user_id']) {
            return $this->jsonError(403, 'not_author');
        }
        $body = trim((string) $this->request->getJsonVar('body_markdown'));
        if ($body === '') return $this->jsonError(400, 'body_required');
        (new WikiCommentModel())->update($commentId, ['BodyMarkdown' => $body]);
        return $this->respond(['ok' => true]);
    }

    /** DELETE /api/v1/wiki-comments/(:num) */
    public function delete($commentId = null)
    {
        $c = (new WikiCommentModel())->find($commentId);
        if (!$c || $c['DeletedAt']) return $this->jsonError(404, 'not_found');
        $ctx = $this->pageAndUser((int) $c['PageID'], false);
        if (!$ctx) return $this->response;
        if ((int) $c['AuthorUserID'] !== $ctx['user_id']) {
            return $this->jsonError(403, 'not_author');
        }
        (new WikiCommentModel())->update($commentId, ['DeletedAt' => date('Y-m-d H:i:s')]);
        return $this->respond(['ok' => true]);
    }
}
