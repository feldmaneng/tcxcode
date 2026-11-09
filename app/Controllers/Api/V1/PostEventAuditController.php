<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\AttendanceModel;
use App\Models\CompanyGuestListsModel;
use App\Models\EventGuestModel;
use App\Models\EventModel;
use App\Models\UserModuleModel;

/**
 * Post-Event Registration Audit (admins / event managers of the event only).
 *
 *   GET  /api/v1/post-event/{eventId}/adjustments-list   find (or create) the "Post-Event Adjustments" list
 *   POST /api/v1/post-event/{eventId}/add-guest          add a lucky-draw card person who was not registered
 *   GET  /api/v1/post-event/{eventId}/attendance         preview rows Finalize would insert / update
 *   POST /api/v1/post-event/{eventId}/attendance         Finalize: upsert attendance rows (re-runnable)
 *
 * Guests live in bitswork_registration; attendance lives in the default
 * conference DB (bitswork_contac2). Attendance rows are keyed on
 * Event + Year + EventRegID (GuestID), falling back to ContactID, then Email,
 * so re-running Finalize updates instead of duplicating.
 *
 * The legacy attendance.Payment column is DEPRECATED: it is never written
 * here; PaidAmount / PaidCurrency replace it.
 */
class PostEventAuditController extends BaseApiController
{
    public const ADJUSTMENTS_COMPANY = 'Post-Event Adjustments';
    private const ADJUSTMENTS_CODE   = 'POSTEVENT';
    private const CURRENCIES         = ['USD', 'CNY', 'KRW', 'NTD'];

    /** @return array{actorId:int,event:array}|\CodeIgniter\HTTP\ResponseInterface */
    private function authorize(int $eventId)
    {
        $actorId = (int) ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');
        $isAdmin = (new UserModuleModel())->userHasModule($actorId, 'admin');
        if (!$isAdmin && !(new EventModel())->isEventManagerForEvent($actorId, $eventId)) {
            return $this->jsonError(403, 'forbidden');
        }
        $event = (new EventModel())->find($eventId);
        if (!$event) return $this->jsonError(404, 'event_not_found');
        return ['actorId' => $actorId, 'event' => $event];
    }

    private function clientIp(): string
    {
        return substr((string) $this->request->getIPAddress(), 0, 45);
    }

    private function findOrCreateAdjustmentsList(int $eventId, array $event, int $actorId): array
    {
        $model = new CompanyGuestListsModel();
        $row = $model->where('EventID', $eventId)->where('Company', self::ADJUSTMENTS_COMPANY)->first();
        if ($row) return $row;
        $id = (int) $model->insert([
            'EventID'        => $eventId,
            'Year'           => (int) $event['Year'],
            'EventYear'      => trim((string) ($event['Name'] ?? '')) . (int) $event['Year'],
            'Name'           => self::ADJUSTMENTS_CODE,
            'Company'        => self::ADJUSTMENTS_COMPANY,
            'SecretKey'      => substr(bin2hex(random_bytes(8)), 0, 10),
            'InviteCount'    => null, // no limit
            'EmployeeCount'  => null,
            'BanquetCount'   => 0,
            'GolfCount'      => 0,
            'StaffID'        => 0,
            'FullConfToken'  => CompanyGuestListsModel::newToken(),
            'ExhibitorToken' => CompanyGuestListsModel::newToken(),
            'Notes'          => 'Created automatically by the Post-Event Audit for guests found only in lucky draw business cards.',
        ], true);
        return $model->find($id);
    }

    private function listPayload(array $l): array
    {
        return ['id' => (int) $l['CompanyID'], 'name' => (string) ($l['Company'] ?: $l['Name'])];
    }

    /** GET /api/v1/post-event/{eventId}/adjustments-list */
    public function adjustmentsList(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        try {
            $list = $this->findOrCreateAdjustmentsList($eventId, $auth['event'], $auth['actorId']);
        } catch (\Throwable $e) {
            log_message('error', 'post-event adjustments list failed: ' . $e->getMessage());
            return $this->jsonError(422, 'list_create_failed', ['message' => $e->getMessage()]);
        }
        return $this->response->setJSON(['data' => $this->listPayload($list)]);
    }

    /** POST /api/v1/post-event/{eventId}/add-guest */
    public function addGuest(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $p = (array) $this->request->getJSON(true);

        $listId = (int) ($p['list_id'] ?? 0);
        $lists  = new CompanyGuestListsModel();
        if ($listId > 0) {
            $list = $lists->find($listId);
            if (!$list || (int) $list['EventID'] !== $eventId) return $this->jsonError(422, 'list_not_in_event');
        } else {
            $list = $this->findOrCreateAdjustmentsList($eventId, $auth['event'], $auth['actorId']);
            $listId = (int) $list['CompanyID'];
        }

        $email = EventGuestModel::normalizeEmail((string) ($p['email'] ?? ''));
        $given = trim((string) ($p['given_name'] ?? ''));
        $family = trim((string) ($p['family_name'] ?? ''));
        if ($given === '' && $family === '') return $this->jsonError(422, 'validation_failed', ['given_name' => 'A name is required']);

        $eventYear = (string) ($list['EventYear'] ?: $list['Year']);
        $guests = new EventGuestModel();
        if ($email !== '') {
            $existing = $guests->liveByEmailInEvent($eventYear, $email, null, $listId);
            if ($existing) {
                return $this->jsonError(409, 'already_attending', [
                    'message'  => 'This email is already registered for this event',
                    'guest_id' => (int) $existing['GuestID'],
                ]);
            }
        }

        $note = 'Not originally registered - found in lucky draw business cards (' . date('Y-m-d') . ').';
        $type = EventGuestModel::normalizeType($p['guest_type'] ?? EventGuestModel::TYPE_PROFESSIONAL);
        $row = [
            'InvitedByCompanyID' => $listId,
            'EventYear'   => $eventYear,
            'GivenName'   => mb_substr($given, 0, 60),
            'FamilyName'  => mb_substr($family, 0, 60),
            'NativeName'  => mb_substr(trim((string) ($p['native_name'] ?? '')), 0, 60),
            'Email'       => $email,
            'Company'     => mb_substr(trim((string) ($p['company'] ?? '')), 0, 100),
            'CN_Company'  => mb_substr(trim((string) ($p['cn_company'] ?? '')), 0, 100),
            'Title'       => mb_substr(trim((string) ($p['title'] ?? '')), 0, 100),
            'Mobile'      => mb_substr(trim((string) ($p['mobile'] ?? '')), 0, 40),
            'Type'        => $type,
            'Related'     => $type === EventGuestModel::TYPE_EXHIBITOR ? 1 : 0,
            'SignupType'  => 'TestConX Office',
            'OfficeNotes' => $note,
            'NoShow'      => 'No',
            'BusinessCard'=> 'Yes',
            'AddedBy'     => $auth['actorId'],
            'UpdatedBy'   => $auth['actorId'],
            'AddedIP'     => $this->clientIp(),
            'UpdatedIP'   => $this->clientIp(),
            'DeletedAt'   => null,
        ];
        if (!empty($p['contact_id']) && (int) $p['contact_id'] > 0) $row['ContactID'] = (int) $p['contact_id'];

        try {
            $id = (int) $guests->insert($row, true);
        } catch (\Throwable $e) {
            log_message('error', 'post-event add guest failed: ' . $e->getMessage());
            return $this->jsonError(422, 'db_insert_failed', ['message' => $e->getMessage()]);
        }
        if ($id <= 0) return $this->jsonError(422, 'insert_failed', $guests->errors());
        return $this->response->setStatusCode(201)->setJSON([
            'data' => ['guest_id' => $id, 'list' => $this->listPayload($list)],
        ]);
    }

    // ------------------------------------------------------------------
    // Attendance
    // ------------------------------------------------------------------

    /** Show unless NoShow = Yes; a lucky-draw business card always means Show. */
    public static function showFor(array $g): int
    {
        if (strcasecmp(trim((string) ($g['BusinessCard'] ?? '')), 'Yes') === 0) return 1;
        return strcasecmp(trim((string) ($g['NoShow'] ?? '')), 'Yes') === 0 ? 0 : 1;
    }

    /** guests.Tutorial is VARCHAR(3); attendance.Tutorial is INT. Blank / non-numeric => 0. */
    public static function tutorialInt($v): int
    {
        $s = trim((string) $v);
        if ($s === '' || !preg_match('/^-?\d+/', $s, $m)) return 0;
        return max(0, min(99, (int) $m[0]));
    }

    private function buildPlan(int $eventId, array $event): array
    {
        $lists = (new CompanyGuestListsModel())->where('EventID', $eventId)->findAll();
        $listIds = array_map(fn($l) => (int) $l['CompanyID'], $lists);
        $guests = $listIds
            ? (new EventGuestModel())->whereIn('InvitedByCompanyID', $listIds)->where('DeletedAt', null)->findAll()
            : [];

        $evName = (string) $event['Name'];
        $evYear = (int) $event['Year'];
        $existing = (new AttendanceModel())->where('Event', $evName)->where('Year', $evYear)->findAll();
        $byReg = []; $byContact = []; $byEmail = [];
        foreach ($existing as $a) {
            if (trim((string) ($a['EventRegID'] ?? '')) !== '') $byReg[(string) $a['EventRegID']] = $a;
            if ((int) ($a['ContactID'] ?? 0) > 0) $byContact[(int) $a['ContactID']] = $a;
            $em = strtolower(trim((string) ($a['Email'] ?? '')));
            if ($em !== '') $byEmail[$em] = $a;
        }

        $rows = [];
        $warnings = ['no_contact' => 0];
        foreach ($guests as $g) {
            $gid = (int) $g['GuestID'];
            $cid = (int) ($g['ContactID'] ?? 0);
            $email = strtolower(trim((string) ($g['Email'] ?? '')));
            if ($cid <= 0) $warnings['no_contact']++;
            $cur = strtoupper(trim((string) ($g['PaidCurrency'] ?? '')));
            $data = [
                'ContactID'       => $cid > 0 ? $cid : null,
                'Email'           => $email,
                'Year'            => $evYear,
                'Event'           => $evName,
                'Type'            => EventGuestModel::normalizeType($g['Type'] ?? ''),
                'Show'            => self::showFor($g),
                'InviteCompanyID' => (int) $g['InvitedByCompanyID'],
                'EventRegID'      => (string) $gid,
                'Tutorial'        => self::tutorialInt($g['Tutorial'] ?? ''),
                'PaidAmount'      => ($g['PaidAmount'] ?? null) !== null && $g['PaidAmount'] !== '' ? (float) $g['PaidAmount'] : null,
                'PaidCurrency'    => in_array($cur, self::CURRENCIES, true) ? $cur : null,
            ];
            $match = $byReg[(string) $gid] ?? ($cid > 0 ? ($byContact[$cid] ?? null) : null) ?? ($email !== '' ? ($byEmail[$email] ?? null) : null);
            $action = 'insert';
            if ($match) {
                $changed = false;
                foreach ($data as $k => $v) {
                    if ((string) ($match[$k] ?? '') !== (string) ($v ?? '')) { $changed = true; break; }
                }
                $action = $changed ? 'update' : 'unchanged';
            }
            $rows[] = [
                'action'        => $action,
                'attendance_id' => $match ? (int) $match['AttendanceID'] : null,
                'guest_id'      => $gid,
                'name'          => trim(($g['GivenName'] ?? '') . ' ' . ($g['FamilyName'] ?? '')),
                'data'          => $data,
            ];
        }
        return ['rows' => $rows, 'warnings' => $warnings];
    }

    private function apiRow(array $r): array
    {
        $d = $r['data'];
        return [
            'action' => $r['action'], 'attendance_id' => $r['attendance_id'], 'guest_id' => $r['guest_id'],
            'name' => $r['name'], 'contact_id' => $d['ContactID'], 'email' => $d['Email'], 'type' => $d['Type'],
            'show' => $d['Show'], 'invite_company_id' => $d['InviteCompanyID'], 'tutorial' => $d['Tutorial'],
            'paid_amount' => $d['PaidAmount'], 'paid_currency' => $d['PaidCurrency'],
        ];
    }

    /** GET /api/v1/post-event/{eventId}/attendance */
    public function attendancePreview(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $plan = $this->buildPlan($eventId, $auth['event']);
        $counts = ['insert' => 0, 'update' => 0, 'unchanged' => 0];
        foreach ($plan['rows'] as $r) $counts[$r['action']]++;
        return $this->response->setJSON([
            'data' => array_map([$this, 'apiRow'], $plan['rows']),
            'counts' => $counts,
            'warnings' => $plan['warnings'],
        ]);
    }

    /** POST /api/v1/post-event/{eventId}/attendance */
    public function finalize(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $plan = $this->buildPlan($eventId, $auth['event']);
        $model = new AttendanceModel();
        $db = $model->db;
        $done = ['insert' => 0, 'update' => 0, 'unchanged' => 0, 'failed' => 0];
        $errors = [];
        $db->transStart();
        foreach ($plan['rows'] as $r) {
            try {
                if ($r['action'] === 'insert') { $model->insert($r['data']); $done['insert']++; }
                elseif ($r['action'] === 'update') { $model->update($r['attendance_id'], $r['data']); $done['update']++; }
                else $done['unchanged']++;
            } catch (\Throwable $e) {
                $done['failed']++;
                $errors[] = ['guest_id' => $r['guest_id'], 'message' => $e->getMessage()];
            }
        }
        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->jsonError(500, 'finalize_failed', ['errors' => array_slice($errors, 0, 20)]);
        }
        return $this->response->setJSON(['data' => $done, 'errors' => array_slice($errors, 0, 20)]);
    }
}
