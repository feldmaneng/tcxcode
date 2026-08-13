<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\ProgramAccess;
use App\Models\AdminAuditLogModel;
use App\Models\EventModel;
use App\Models\PresentationModel;
use App\Models\AuthorModel;
use App\Models\UserModuleModel;
use Config\Database;

class PresentationsController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'                   => 'PresentationID',
        'event'                => 'Event',
        'year'                 => 'Year',
        'session'              => 'Session',
        'session_id'           => 'SessionID',
        'presentation_number'  => 'PresentationNumber',
        'title'                => 'Title',
        'title_chinese'        => 'TitleChinese',
        'title_korean'         => 'TitleKorean',
        'wrangler'             => 'Wrangler',
        'topic'                => 'Topic',
        'award'                => 'Award',
        'url'                  => 'URL',
        'base_file_name'       => 'BaseFileName',
        'pdf_lock_code'        => 'PDFLockCode',
        'video_id'             => 'VideoID',
        'abstract_number'      => 'AbstractNumber',
        'early_bird'           => 'EarlyBird',
        'author_discount_code' => 'AuthorDiscountCode',
        'wrangler_id'          => 'WranglerID',
        'abstract_english'     => 'AbstractEnglish',
        'abstract_chinese'     => 'AbstractChinese',
        'abstract_korean'      => 'AbstractKorean',
        'bio_english'          => 'BioEnglish',
        'bio_chinese'          => 'BioChinese',
        'bio_korean'           => 'BioKorean',
        'status'               => 'Status',
        'status_changed_at'    => 'StatusChangedAt',
        'coordinator1_id'      => 'Coordinator1ID',
        'coordinator2_id'      => 'Coordinator2ID',
        'coordinators_pinned'  => 'CoordinatorsPinned',
    ];

    private const READONLY_API_FIELDS = ['id', 'status_changed_at'];
    private const FILTERABLE = ['event', 'year', 'session', 'session_id', 'topic', 'award', 'wrangler_id', 'status'];
    private const SORTABLE   = ['id', 'year', 'event', 'session', 'presentation_number', 'title', 'status'];

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
            if ($k === 'authors') continue;
            if (in_array($k, self::READONLY_API_FIELDS, true)) continue;
            if (!isset(self::FIELD_MAP[$k])) continue;
            $out[self::FIELD_MAP[$k]] = $v;
        }
        return $out;
    }

    private function attachAuthors(array &$row): void
    {
        $db = \Config\Database::connect();
        $rows = $db->table('authors')
            ->select('authors.*, contacts.Email AS ContactEmail, contacts.GivenName AS ContactGivenName, contacts.FamilyName AS ContactFamilyName, contacts.Nickname AS ContactNickname')
            ->join('contacts', 'contacts.ContactID = authors.ContactID', 'left')
            ->where('authors.PresentationID', (int) $row['id'])
            ->orderBy('authors.AuthorNumber', 'ASC')
            ->orderBy('authors.AuthorID', 'ASC')
            ->get()->getResultArray();
        $row['authors'] = array_map(function ($r) {
            $api = AuthorsController::dbToApi($r);
            if (array_key_exists('ContactEmail', $r)) $api['email'] = $r['ContactEmail'];
            // contacts is the source of truth for names; the author row's
            // GivenName/FamilyName are only a fallback snapshot.
            if (($r['ContactGivenName'] ?? null) !== null || ($r['ContactFamilyName'] ?? null) !== null) {
                $api['given_name']  = $r['ContactGivenName'] ?? $api['given_name'] ?? null;
                $api['family_name'] = $r['ContactFamilyName'] ?? $api['family_name'] ?? null;
            }
            $nick = trim((string) ($r['ContactNickname'] ?? ''));
            $api['nickname'] = $nick !== '' ? $nick : null;
            return $api;
        }, $rows);
    }


    /**
     * Lightweight author summaries keyed by presentation id, for list views.
     * Returns only the fields needed to render names under a title.
     */
    private function authorSummaries(array $presentationIds): array
    {
        if (!$presentationIds) return [];
        $db   = \Config\Database::connect();
        $rows = $db->table('authors')
            ->select('authors.AuthorID, authors.PresentationID, authors.AuthorNumber, authors.Presenter, authors.ContactID, authors.GivenName, authors.FamilyName, authors.Company, contacts.GivenName AS ContactGivenName, contacts.FamilyName AS ContactFamilyName, contacts.Nickname AS ContactNickname')
            ->join('contacts', 'contacts.ContactID = authors.ContactID', 'left')
            ->whereIn('authors.PresentationID', $presentationIds)
            ->orderBy('authors.AuthorNumber', 'ASC')
            ->orderBy('authors.AuthorID', 'ASC')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $pid = (int) $r['PresentationID'];
            $nick = trim((string) ($r['ContactNickname'] ?? ''));
            $out[$pid][] = [
                'id'            => (int) $r['AuthorID'],
                'author_number' => $r['AuthorNumber'] !== null ? (int) $r['AuthorNumber'] : null,
                'presenter'     => $r['Presenter'] !== null ? (int) $r['Presenter'] : null,
                'contact_id'    => $r['ContactID'] !== null ? (int) $r['ContactID'] : null,
                'given_name'    => $r['ContactGivenName'] ?? $r['GivenName'],
                'family_name'   => $r['ContactFamilyName'] ?? $r['FamilyName'],
                'nickname'      => $nick !== '' ? $nick : null,
                'company'       => $r['Company'],
                'company_id'    => null,
                'presentation_id' => $pid,
            ];
        }
        return $out;
    }


    public function index()
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $req     = $this->request;
        $page    = max(1, (int) $req->getGet('page') ?: 1);
        $perPage = max(1, min(100, (int) ($req->getGet('per_page') ?: 25)));
        $q       = trim((string) $req->getGet('q'));
        $sort    = (string) ($req->getGet('sort') ?: '-year');

        $builder = (new PresentationModel())->builder();

        // Optional: only presentations where a given contact is an author.
        $contactId = (int) ($req->getGet('contact_id') ?: 0);
        if ($contactId > 0) {
            $db  = \Config\Database::connect();
            $ids = $db->table('authors')->select('PresentationID')
                ->where('ContactID', $contactId)->distinct()->get()->getResultArray();
            $ids = array_values(array_unique(array_map(fn($r) => (int) $r['PresentationID'], $ids)));
            if (!$ids) {
                return $this->response->setJSON([
                    'data' => [],
                    'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => 0, 'total_pages' => 0],
                ]);
            }
            $builder->whereIn('PresentationID', $ids);
        }

        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
        }

        if ($q !== '') {
            $builder->groupStart()
                ->like('Title', $q)
                ->orLike('AbstractNumber', $q)
                ->orLike('BaseFileName', $q)
                ->orLike('Topic', $q);
            if (ctype_digit($q)) {
                $builder->orWhere('PresentationID', (int) $q);
            }
            $builder->groupEnd();
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
        $pageIds = array_map(fn($r) => (int) $r['PresentationID'], $rows);
        $authorsByPresentation = $this->authorSummaries($pageIds);
        foreach ($rows as $r) {
            $api = $this->dbToApi($r);
            $pid = (int) $api['id'];
            $api['authors']      = $authorsByPresentation[$pid] ?? [];
            $api['author_count'] = count($api['authors']);
            $data[] = $api;
        }

        return $this->response->setJSON([
            'data' => $data,
            'pagination' => [
                'page' => $page, 'per_page' => $perPage,
                'total' => $total, 'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function show($id = null)
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $row = (new PresentationModel())->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');
        $api = $this->dbToApi($row);
        $this->attachAuthors($api);
        return $this->response->setJSON(['data' => $api]);
    }

    /**
     * GET /api/v1/presentations/awards
     * Returns distinct non-null, non-blank Award values sorted alphabetically.
     */
    public function awards()
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $rows = (new PresentationModel())->builder()
            ->select('Award')
            ->distinct()
            ->where('Award IS NOT NULL', null, false)
            ->where("TRIM(Award) <> ''", null, false)
            ->orderBy('Award', 'ASC')
            ->get()->getResultArray();
        $data = array_values(array_map(fn($r) => (string) $r['Award'], $rows));
        return $this->response->setJSON(['data' => $data]);
    }

    public function create()
    {
        if ($deny = $this->requireModule(['crm'])) return $deny;
        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        $model = new PresentationModel();
        $db = Database::connect();

        // Auto-assign PresentationNumber when caller omitted it: next number
        // within the same Session. Prefer SessionID scoping; fall back to
        // (Event, Year, Session) when SessionID is missing. Row-level lock
        // prevents concurrent inserts from colliding.
        $needsAutoNumber = !array_key_exists('PresentationNumber', $dbRow)
            || $dbRow['PresentationNumber'] === null
            || $dbRow['PresentationNumber'] === '';
        $db->transStart();
        if ($needsAutoNumber) {
            $next = 1;
            if (!empty($dbRow['SessionID'])) {
                $q = $db->query(
                    'SELECT COALESCE(MAX(PresentationNumber), 0) + 1 AS next
                     FROM presentations WHERE SessionID = ? FOR UPDATE',
                    [(int) $dbRow['SessionID']]
                );
                $next = (int) ($q->getRowArray()['next'] ?? 1);
            } elseif (!empty($dbRow['Event']) && !empty($dbRow['Year'])) {
                $q = $db->query(
                    'SELECT COALESCE(MAX(PresentationNumber), 0) + 1 AS next
                     FROM presentations
                     WHERE Event = ? AND Year = ?
                       AND (Session <=> ?) FOR UPDATE',
                    [$dbRow['Event'], (int) $dbRow['Year'], $dbRow['Session'] ?? null]
                );
                $next = (int) ($q->getRowArray()['next'] ?? 1);
            }
            $dbRow['PresentationNumber'] = $next;
        }

        $id = $model->insert($dbRow, true);
        if (!$id) {
            $db->transRollback();
            return $this->jsonError(500, 'insert_failed', $model->errors());
        }
        if (isset($payload['authors']) && is_array($payload['authors'])) {
            $this->replaceAuthors((int) $id, $payload['authors']);
        }
        $db->transComplete();
        return $this->show((int) $id)->setStatusCode(201);
    }


    public function update($id = null)
    {
        if ($deny = $this->requireModule(['crm'])) return $deny;
        $model = new PresentationModel();
        $existing = $model->find((int) $id);
        if (!$existing) return $this->jsonError(404, 'not_found');
        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        $hasAuthors = array_key_exists('authors', $payload) && is_array($payload['authors']);
        if (empty($dbRow) && !$hasAuthors) return $this->jsonError(400, 'no_updatable_fields');

        // Stamp StatusChangedAt whenever Status changes.
        if (array_key_exists('Status', $dbRow) && $dbRow['Status'] !== ($existing['Status'] ?? null)) {
            $dbRow['StatusChangedAt'] = date('Y-m-d H:i:s');
        }

        if (!empty($dbRow)) {
            if (!$model->update((int) $id, $dbRow)) {
                return $this->jsonError(500, 'update_failed', $model->errors());
            }
        }
        if ($hasAuthors) {
            $this->replaceAuthors((int) $id, $payload['authors']);
        }
        return $this->show((int) $id);
    }

    public function delete($id = null)
    {
        if ($deny = $this->requireModule(['crm'])) return $deny;
        $model = new PresentationModel();
        if (!$model->find((int) $id)) return $this->jsonError(404, 'not_found');
        $db = Database::connect();
        $db->table('authors')->where('PresentationID', (int) $id)->delete();
        if (!$model->delete((int) $id)) return $this->jsonError(500, 'delete_failed', $model->errors());
        return $this->response->setJSON(['data' => ['id' => (int) $id, 'deleted' => true]]);
    }

    /**
     * GET /api/v1/presentations/{id}/move-options?session_id=X
     *
     * Returns the coordinators currently attached to the presentation and the
     * ones belonging to the prospective target session, with display names, so
     * the move dialog can offer "keep original" vs "use target session".
     */
    public function moveOptions($id = null)
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');

        $db   = Database::connect();
        $pres = $db->table('presentations')
            ->select('PresentationID, SessionID')
            ->where('PresentationID', (int) $id)->get()->getRowArray();
        if (!$pres) return $this->jsonError(404, 'not_found');

        $sourceEventId = ProgramAccess::eventIdForSession($pres['SessionID'] ? (int) $pres['SessionID'] : null);
        if (!ProgramAccess::canManageProgram($actorId, $sourceEventId)) return $this->jsonError(403, 'forbidden');

        $targetSid  = (int) ($this->request->getGet('session_id') ?? 0);
        $targetIds  = [];
        if ($targetSid > 0) {
            $t = $db->table('sessions')->select('Coordinator1ID, Coordinator2ID')
                ->where('SessionID', $targetSid)->get()->getRowArray();
            foreach (['Coordinator1ID', 'Coordinator2ID'] as $c) {
                $v = (int) ($t[$c] ?? 0);
                if ($v > 0) $targetIds[] = $v;
            }
        }

        $dbC    = Database::connect('control');
        $lookup = function (int $uid) use ($dbC): array {
            $u = $dbC->table('users')->select('UserID, UserName, GivenName, FamilyName, Email')
                ->where('UserID', $uid)->get()->getRowArray();
            $name = trim(($u['GivenName'] ?? '') . ' ' . ($u['FamilyName'] ?? ''));
            return [
                'user_id' => $uid,
                'name'    => $name !== '' ? $name : ($u['UserName'] ?? ('User #' . $uid)),
                'email'   => $u['Email'] ?? '',
            ];
        };

        return $this->response->setJSON([
            'data' => [
                'current'   => array_map($lookup, ProgramAccess::presentationCoordinatorIds((int) $id)),
                'target'    => array_map($lookup, array_values(array_unique($targetIds))),
                'source_session_id' => $pres['SessionID'] ? (int) $pres['SessionID'] : null,
            ],
        ]);
    }

    /**
     * POST /api/v1/presentations/{id}/move
     *   body: {
     *     session_id: int,                 target session
     *     position?: int,                  1-based slot in the target session
     *     coordinator_ids?: int[],         who keeps/gains review access
     *     pin?: bool                       force per-presentation coordinators
     *   }
     *
     * Allowed for admins, the event manager and the event's chairs / general
     * chair — for BOTH the source and the target event.
     */
    public function move($id = null)
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');

        $model = new PresentationModel();
        $pres  = $model->find((int) $id);
        if (!$pres) return $this->jsonError(404, 'not_found');

        $body      = (array) ($this->request->getJSON(true) ?? []);
        $targetSid = (int) ($body['session_id'] ?? 0);
        if ($targetSid <= 0) return $this->jsonError(422, 'validation_failed', ['required' => ['session_id']]);

        $db     = Database::connect();
        $target = $db->table('sessions')
            ->select('SessionID, EventID, SessionNumber, Coordinator1ID, Coordinator2ID')
            ->where('SessionID', $targetSid)->get()->getRowArray();
        if (!$target) return $this->jsonError(404, 'session_not_found');

        $sourceSid     = !empty($pres['SessionID']) ? (int) $pres['SessionID'] : null;
        $sourceEventId = ProgramAccess::eventIdForSession($sourceSid);
        $targetEventId = (int) $target['EventID'];

        if (!ProgramAccess::canManageProgram($actorId, $sourceEventId)
            || !ProgramAccess::canManageProgram($actorId, $targetEventId)) {
            return $this->jsonError(403, 'forbidden');
        }
        if ($deny = $this->denyWhenLocked($actorId, [$sourceEventId, $targetEventId])) return $deny;

        // --- coordinators -------------------------------------------------
        $sourceCoords = ProgramAccess::presentationCoordinatorIds((int) $id);
        $targetCoords = [];
        foreach (['Coordinator1ID', 'Coordinator2ID'] as $c) {
            $v = (int) ($target[$c] ?? 0);
            if ($v > 0) $targetCoords[] = $v;
        }
        $requested = array_values(array_unique(array_filter(array_map(
            'intval',
            is_array($body['coordinator_ids'] ?? null) ? $body['coordinator_ids'] : $targetCoords
        ))));
        $allowed = array_merge($sourceCoords, $targetCoords);
        foreach ($requested as $uid) {
            if (!in_array($uid, $allowed, true)) {
                return $this->jsonError(422, 'coordinator_not_allowed', ['user_id' => $uid]);
            }
        }
        if (count($requested) > 2) return $this->jsonError(422, 'too_many_coordinators');

        // No override needed when the kept set is exactly the target session's.
        $sameAsTarget = !array_diff($requested, $targetCoords) && !array_diff($targetCoords, $requested);
        $pin = !empty($body['pin']) || !$sameAsTarget;

        $update = [
            'SessionID' => $targetSid,
            'Session'   => $target['SessionNumber'],
        ];
        $ev = $db->table('events')->select('Name, Year')->where('EventID', $targetEventId)->get()->getRowArray();
        if ($ev) {
            $update['Event'] = $ev['Name'];
            $update['Year']  = (int) $ev['Year'];
        }
        if (ProgramAccess::hasCoordinatorColumns()) {
            $update['Coordinator1ID']     = $pin ? ($requested[0] ?? null) : null;
            $update['Coordinator2ID']     = $pin ? ($requested[1] ?? null) : null;
            $update['CoordinatorsPinned'] = $pin ? 1 : 0;
        }

        $position = isset($body['position']) ? max(1, (int) $body['position']) : null;

        $db->transStart();
        if (!$model->update((int) $id, $update)) {
            $db->transRollback();
            return $this->jsonError(500, 'update_failed', $model->errors());
        }
        $this->resequenceSession($targetSid, (int) $id, $position);
        if ($sourceSid && $sourceSid !== $targetSid) {
            $this->resequenceSession($sourceSid, null, null);
        }
        $db->transComplete();

        (new AdminAuditLogModel())->log(
            $actorId,
            'presentation.move',
            'presentation',
            (string) $id,
            [
                'from_session_id' => $sourceSid,
                'to_session_id'   => $targetSid,
                'position'        => $position,
                'coordinators'    => $requested,
                'pinned'          => $pin ? 1 : 0,
            ],
            $this->request->getIPAddress()
        );

        return $this->show((int) $id);
    }

    /**
     * POST /api/v1/sessions/{id}/presentation-order
     *   body: { presentation_ids: int[] }
     *
     * Renumbers a session's presentations 1..n in the given order. Coordinators
     * are never touched. Same roles as move().
     */
    public function reorder($sessionId = null)
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');

        $sid = (int) $sessionId;
        $db  = Database::connect();
        if (!$db->table('sessions')->where('SessionID', $sid)->countAllResults()) {
            return $this->jsonError(404, 'session_not_found');
        }
        $eventId = ProgramAccess::eventIdForSession($sid);
        if (!ProgramAccess::canManageProgram($actorId, $eventId)) return $this->jsonError(403, 'forbidden');
        if ($deny = $this->denyWhenLocked($actorId, [$eventId])) return $deny;

        $body  = (array) ($this->request->getJSON(true) ?? []);
        $order = array_values(array_unique(array_map('intval', (array) ($body['presentation_ids'] ?? []))));
        if (!$order) return $this->jsonError(422, 'validation_failed', ['required' => ['presentation_ids']]);

        $existing = array_map(
            fn($r) => (int) $r['PresentationID'],
            $db->table('presentations')->select('PresentationID')->where('SessionID', $sid)->get()->getResultArray()
        );
        sort($existing);
        $check = $order; sort($check);
        if ($existing !== $check) return $this->jsonError(422, 'order_mismatch');

        $db->transStart();
        $n = 1;
        foreach ($order as $pid) {
            $db->table('presentations')->where('PresentationID', $pid)->update(['PresentationNumber' => $n++]);
        }
        $db->transComplete();

        (new AdminAuditLogModel())->log(
            $actorId,
            'session.reorder',
            'session',
            (string) $sid,
            ['presentation_ids' => $order],
            $this->request->getIPAddress()
        );

        return $this->response->setJSON(['data' => ['session_id' => $sid, 'presentation_ids' => $order]]);
    }

    /** 423 when any of the events is closed and the actor is not an admin. */
    private function denyWhenLocked(int $actorId, array $eventIds)
    {
        if ((new UserModuleModel())->userHasModule($actorId, 'admin')) return null;
        $locked = (new EventModel())->lockedEventIds();
        foreach (array_filter($eventIds) as $eid) {
            if (in_array((int) $eid, $locked, true)) {
                return $this->jsonError(423, 'event_locked');
            }
        }
        return null;
    }

    /**
     * Renumber a session's presentations 1..n by their current order, optionally
     * forcing $moveId into 1-based slot $position. Must run inside a transaction.
     */
    private function resequenceSession(int $sessionId, ?int $moveId, ?int $position): void
    {
        $db = Database::connect();
        $db->query('SELECT PresentationID FROM presentations WHERE SessionID = ? FOR UPDATE', [$sessionId]);
        $rows = $db->table('presentations')
            ->select('PresentationID')
            ->where('SessionID', $sessionId)
            ->orderBy('PresentationNumber', 'ASC')
            ->orderBy('PresentationID', 'ASC')
            ->get()->getResultArray();
        $ids = array_map(fn($r) => (int) $r['PresentationID'], $rows);

        if ($moveId !== null && $position !== null) {
            $ids = array_values(array_filter($ids, fn($i) => $i !== $moveId));
            $slot = max(0, min(count($ids), $position - 1));
            array_splice($ids, $slot, 0, [$moveId]);
        }

        $n = 1;
        foreach ($ids as $pid) {
            $db->table('presentations')->where('PresentationID', $pid)->update(['PresentationNumber' => $n++]);
        }
    }


    /**
     * Replace the author set for a presentation. Each entry in $authors should be
     * { contact_id, presenter?, author_number? }. Snapshot of name + company is
     * captured from the contacts table at save time. Existing authors are wiped
     * and re-inserted (simpler + matches the "snapshot at add time" semantics).
     */
    private function replaceAuthors(int $presentationId, array $authors): void
    {
        $db = Database::connect();
        $db->transStart();
        $db->table('authors')->where('PresentationID', $presentationId)->delete();

        $idx = 1;
        foreach ($authors as $a) {
            if (!is_array($a)) continue;
            $contactId = isset($a['contact_id']) ? (int) $a['contact_id'] : 0;
            if ($contactId <= 0) { $idx++; continue; }
            $row = [
                'PresentationID' => $presentationId,
                'ContactID'      => $contactId,
                'AuthorNumber'   => isset($a['author_number']) ? (int) $a['author_number'] : $idx,
                'Presenter'      => !empty($a['presenter']) ? 1 : 0,
            ];
            AuthorsController::snapshotFromContact($row, $contactId);
            $db->table('authors')->insert($row);
            $idx++;
        }
        $db->transComplete();
    }
}
