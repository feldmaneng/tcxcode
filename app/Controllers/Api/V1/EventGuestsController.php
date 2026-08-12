<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\CompanyGuestListsManagerModel;
use App\Models\CompanyGuestListsModel;
use App\Models\EventGuestModel;
use App\Models\EventModel;
use App\Models\UserModuleModel;

/**
 * Guest CRUD for one companyguestlists row, backed by the legacy `guests` table.
 *
 * Access:
 *   - Admin (module 'admin') or the event's Event Manager (events.EventManagerID)
 *     → full control, including Related edits, restore, and viewing removed rows.
 *   - Assigned guest-list managers → add/edit/remove their own list's guests.
 *
 * Types: 'Professional' (Invite / Full Conference-EXPO) and 'Exhibitor' (Exhibitor Staff).
 * Enforces InviteCount (Professional) / EmployeeCount (Exhibitor) / BanquetCount.
 * Locked when the matching event (by Year) is locked (auto or forced).
 * Deletes are soft (DeletedAt/DeletedBy/DeletedIP).
 */
class EventGuestsController extends BaseApiController
{
    /** API field => DB column on `guests` */
    private const FIELD_MAP = [
        'id'                     => 'GuestID',
        'company_guest_lists_id' => 'InvitedByCompanyID',
        'given_name'             => 'GivenName',
        'family_name'            => 'FamilyName',
        'native_name'            => 'NativeName',
        'email'                  => 'Email',
        'company'                => 'Company',
        'cn_company'             => 'CN_Company',
        'title'                  => 'Title',
        'mobile'                 => 'Mobile',
        'wechat_id'              => 'WeChatID',
        'kakao_id'               => 'KakaoID',
        'related'                => 'Related',
        'signup_type'            => 'SignupType',
        'guest_type'             => 'Type',
        'notes'                  => 'OfficeNotes',
        'event_year'             => 'EventYear',
        'added_by'               => 'AddedBy',
        'updated_by'             => 'UpdatedBy',
        'deleted_at'             => 'DeletedAt',
        'deleted_by'             => 'DeletedBy',
        'bounced_at'             => 'BouncedAt',
        'bounce_reason'          => 'BounceReason',
        'complained_at'          => 'ComplainedAt',
        'email_suppressed'       => 'EmailSuppressed',
        'updated'                => 'Stamp',
    ];
    private const READONLY_API_FIELDS = [
        'id', 'company_guest_lists_id', 'event_year',
        'added_by', 'updated_by', 'deleted_at', 'deleted_by', 'updated',
        'bounced_at', 'bounce_reason', 'complained_at', 'email_suppressed',
    ];
    /** Only admins / event managers may set these on update. */
    private const PRIVILEGED_API_FIELDS = ['related', 'signup_type', 'notes', 'guest_type'];

    private const SIGNUP_TYPES = ['URL', 'Exhibitor Coordinator', 'TestConX Office', 'Other'];

    private function dbToApi(array $row, bool $privileged = true): array
    {
        $out = [];
        foreach (self::FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
        }
        if (array_key_exists('Type', $row)) {
            $out['guest_type'] = EventGuestModel::normalizeType($row['Type']);
        }
        if (array_key_exists('BanquetCompanyID', $row)) {
            $out['banquet'] = ((int) $row['BanquetCompanyID']) > 0 ? 1 : 0;
        }
        $out['deleted'] = !empty($row['DeletedAt']) ? 1 : 0;
        foreach (['id', 'company_guest_lists_id', 'banquet', 'added_by', 'updated_by', 'deleted_by', 'related', 'email_suppressed'] as $k) {
            if (array_key_exists($k, $out) && $out[$k] !== null && $out[$k] !== '') {
                $out[$k] = (int) $out[$k];
            }
        }
        if (!$privileged) {
            unset($out['notes'], $out['signup_type'], $out['added_by'], $out['updated_by'], $out['deleted_by']);
        }
        return $out;
    }

    private function apiToDb(array $payload): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            if (in_array($k, self::READONLY_API_FIELDS, true)) continue;
            if (!isset(self::FIELD_MAP[$k])) continue;
            $out[self::FIELD_MAP[$k]] = $v;
        }
        return $out;
    }

    private function clientIp(): string
    {
        return substr((string) $this->request->getIPAddress(), 0, 45);
    }

    private function normalizeGuestText($value): string
    {
        $value = strtolower(trim((string) $value));
        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function guestEmailKey(array $row): string
    {
        return $this->normalizeGuestText($row['Email'] ?? '');
    }

    private function guestNameKey(array $row): string
    {
        $given = $this->normalizeGuestText($row['GivenName'] ?? '');
        $family = $this->normalizeGuestText($row['FamilyName'] ?? '');
        if ($given === '' && $family === '') return '';
        return $given . '|' . $family;
    }

    private function guestIdentityKey(array $row): string
    {
        $email = $this->guestEmailKey($row);
        if ($email !== '') return 'email:' . $email;
        $name = $this->guestNameKey($row);
        return $name !== '' ? 'name:' . $name : 'row:' . (string) ($row['GuestID'] ?? spl_object_id((object) $row));
    }

    /** Collapse duplicate legacy rows, keeping the highest GuestID as visible. */
    private function dedupeGuestRows(array $rows): array
    {
        $byKey = [];
        foreach ($rows as $row) {
            $key = $this->guestIdentityKey($row);
            $currentId = (int) ($row['GuestID'] ?? 0);
            $existingId = isset($byKey[$key]) ? (int) ($byKey[$key]['GuestID'] ?? 0) : -1;
            if (!isset($byKey[$key]) || $currentId >= $existingId) {
                $byKey[$key] = $row;
            }
        }

        $deduped = array_values($byKey);
        usort($deduped, function (array $a, array $b): int {
            $family = strcasecmp((string) ($a['FamilyName'] ?? ''), (string) ($b['FamilyName'] ?? ''));
            if ($family !== 0) return $family;
            $given = strcasecmp((string) ($a['GivenName'] ?? ''), (string) ($b['GivenName'] ?? ''));
            if ($given !== 0) return $given;
            return ((int) ($a['GuestID'] ?? 0)) <=> ((int) ($b['GuestID'] ?? 0));
        });

        return $deduped;
    }

    /** @return array{professional:int,exhibitor:int,banquet:int} */
    private function countsForRows(array $rows): array
    {
        $counts = ['professional' => 0, 'exhibitor' => 0, 'banquet' => 0];
        foreach ($this->dedupeGuestRows($rows) as $row) {
            if (!empty($row['DeletedAt'])) continue;
            if (EventGuestModel::normalizeType($row['Type'] ?? '') === EventGuestModel::TYPE_EXHIBITOR) {
                $counts['exhibitor']++;
            } else {
                $counts['professional']++;
            }
            if (!empty($row['BanquetCompanyID'])) $counts['banquet']++;
        }
        return $counts;
    }




    /** @return array{ok:bool,actorId:int,isAdmin:bool,isPrivileged:bool,company:array,eventLocked:bool}|null */
    private function loadContext(int $companyGuestListsId)
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) { $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']); return null; }
        $company = (new CompanyGuestListsModel())->find($companyGuestListsId);
        if (!$company) { $this->response->setStatusCode(404)->setJSON(['error' => 'not_found']); return null; }
        $isAdmin = (new UserModuleModel())->userHasModule($actorId, 'admin');

        $eventModel = new EventModel();
        $year = (int) ($company['Year'] ?? 0);
        $isEventManager = $year > 0 && $eventModel->isEventManagerForYear((int) $actorId, $year);
        $isPrivileged = $isAdmin || $isEventManager;

        if (!$isPrivileged && !(new CompanyGuestListsManagerModel())->userManages($actorId, $companyGuestListsId)) {
            $this->response->setStatusCode(403)->setJSON(['error' => 'forbidden']);
            return null;
        }

        $eventLocked = false;
        if ($year > 0) {
            $event = $eventModel->where('Year', $year)->first();
            if ($event) $eventLocked = $eventModel->isLocked((int) $event['EventID']);
        }
        return [
            'ok' => true,
            'actorId' => (int) $actorId,
            'isAdmin' => $isAdmin,
            'isPrivileged' => $isPrivileged,
            'company' => $company,
            'eventLocked' => $eventLocked,
        ];
    }

    /**
     * Audit line recorded on OfficeNotes when a removed guest is re-added under
     * a different company list. Returns null when nothing needs noting.
     */
    private function restoreNote(array $deletedRow, int $newCompanyId): ?string
    {
        $orig = (int) ($deletedRow['InvitedByCompanyID'] ?? 0);
        if ($orig === $newCompanyId || $orig <= 0) return null;
        $when = trim((string) ($deletedRow['DeletedAt'] ?? '')) ?: 'an unknown date';
        $by   = (int) ($deletedRow['DeletedBy'] ?? 0);
        $who  = $by > 0 ? 'UserID ' . $by : 'the public form';
        return 'Originally registered to InvitedByCompanyID ' . $orig
            . ' but was deleted on ' . $when . ' by ' . $who . '.';
    }

    private function appendNote(?string $notes, string $line): string
    {
        $existing = trim((string) $notes);
        $stamped  = '[' . date('Y-m-d H:i:s') . '] ' . $line;
        return $existing === '' ? $stamped : $existing . "\n" . $stamped;
    }

    /**

     * Validates required fields on the merged row.
     * @return array<string,string> field => message (empty when valid)
     */
    private function validateRequired(array $row): array
    {
        $errors = [];
        $val = fn(string $k) => trim((string) ($row[$k] ?? ''));

        $email = EventGuestModel::normalizeEmail($val('Email'));
        if ($email === '') $errors['email'] = 'Email is required';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email is not a valid address';

        if ($val('Title') === '') $errors['title'] = 'Job title is required';
        if ($val('Mobile') === '') $errors['mobile'] = 'Mobile phone is required';
        if ($val('Company') === '' && $val('CN_Company') === '') {
            $errors['company'] = 'Company or Chinese company name is required';
        }
        if ($val('NativeName') === '' && ($val('GivenName') === '' || $val('FamilyName') === '')) {
            $errors['name'] = 'Native name, or both given and family name, is required';
        }
        return $errors;
    }

    /** GET /api/v1/company-guest-lists/{id}/guests */
    public function index(int $companyGuestListsId)
    {
        $ctx = $this->loadContext($companyGuestListsId);
        if (!$ctx) return $this->response;

        $includeDeleted = $ctx['isPrivileged']
            && in_array((string) $this->request->getGet('include_deleted'), ['1', 'true'], true);

        $model = new EventGuestModel();
        $model->where('InvitedByCompanyID', $companyGuestListsId);
        if (!$includeDeleted) $model->where('DeletedAt', null);
        $rows = $model->orderBy('FamilyName', 'ASC')->orderBy('GivenName', 'ASC')->findAll();

        $liveRows = array_values(array_filter($rows, fn($r) => empty($r['DeletedAt'])));
        $deletedRows = array_values(array_filter($rows, fn($r) => !empty($r['DeletedAt'])));

        $visibleLive = $this->dedupeGuestRows($liveRows);
        $counts = $this->countsForRows($visibleLive);
        $visibleRows = array_merge($visibleLive, $deletedRows);

        return $this->response->setJSON([
            'data'   => array_map(fn($r) => $this->dbToApi($r, $ctx['isPrivileged']), $visibleRows),
            'counts' => $counts,
            'duplicate_count' => max(0, count($liveRows) - count($visibleLive)),
            'limits' => [
                'invite_count'   => $ctx['company']['InviteCount'] !== null ? (int) $ctx['company']['InviteCount'] : null,
                'employee_count' => $ctx['company']['EmployeeCount'] !== null ? (int) $ctx['company']['EmployeeCount'] : null,
                'banquet_count'  => $ctx['company']['BanquetCount'] !== null ? (int) $ctx['company']['BanquetCount'] : null,
            ],
            'event_locked' => $ctx['eventLocked'],
            'is_privileged' => $ctx['isPrivileged'] ? 1 : 0,
        ]);
    }

    /** POST /api/v1/company-guest-lists/{id}/guests */
    public function create(int $companyGuestListsId)
    {
        $ctx = $this->loadContext($companyGuestListsId);
        if (!$ctx) return $this->response;
        if ($ctx['eventLocked'] && !$ctx['isPrivileged']) return $this->jsonError(423, 'event_locked');

        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);
        $row['InvitedByCompanyID'] = $companyGuestListsId;
        $row['EventYear'] = (string) ($ctx['company']['EventYear'] ?? $ctx['company']['Year'] ?? '');
        if (empty($row['Company']) && empty($row['CN_Company']) && !empty($ctx['company']['Company'])) {
            $row['Company'] = $ctx['company']['Company'];
        }

        $row['Type'] = EventGuestModel::normalizeType($row['Type'] ?? EventGuestModel::TYPE_PROFESSIONAL);

        // SignupType: privileged users may choose; everyone else is a coordinator entry.
        $signup = (string) ($row['SignupType'] ?? '');
        if (!$ctx['isPrivileged'] || !in_array($signup, self::SIGNUP_TYPES, true)) {
            $signup = 'Exhibitor Coordinator';
        }
        $row['SignupType'] = $signup;

        // Related is derived from Type: Exhibitor Staff => 1, Professional => 0.
        $row['Related'] = $row['Type'] === EventGuestModel::TYPE_EXHIBITOR ? 1 : 0;

        if (!array_key_exists('Email', $row) || $row['Email'] === null) $row['Email'] = '';
        $row['Email'] = EventGuestModel::normalizeEmail($row['Email']);

        $errors = $this->validateRequired($row);
        if ($errors !== []) return $this->jsonError(422, 'validation_failed', $errors);

        $model     = new EventGuestModel();
        $eventYear = (string) $row['EventYear'];

        // One person (by email) per event, across every company list.
        if ($model->liveByEmailInEvent($eventYear, $row['Email'], null, $companyGuestListsId)) {
            return $this->jsonError(409, 'already_attending', ['message' => 'This email is already registered for this event']);
        }

        $banquet = isset($payload['banquet']) && (int) $payload['banquet'] === 1;
        $row['BanquetCompanyID'] = $banquet ? $companyGuestListsId : null;
        $row['AddedBy']   = $ctx['actorId'];
        $row['UpdatedBy'] = $ctx['actorId'];
        $row['AddedIP']   = $this->clientIp();
        $row['UpdatedIP'] = $row['AddedIP'];
        $row['DeletedAt'] = null;

        $check = $this->checkLimits($companyGuestListsId, $ctx['company'], $row, null);
        if ($check !== null) return $check;

        // Re-adding a previously removed person restores their row instead of
        // creating a second one, keeping history and bounce state intact.
        $deleted = $model->deletedByEmailInEvent($eventYear, $row['Email'], $companyGuestListsId);
        if ($deleted) {
            $guestId  = (int) $deleted['GuestID'];
            $note     = $this->restoreNote($deleted, $companyGuestListsId);
            $update   = $row;
            unset($update['AddedBy'], $update['AddedIP']);
            $update['InvitedByCompanyID'] = $companyGuestListsId;
            $update['DeletedAt'] = null;
            $update['DeletedBy'] = null;
            $update['DeletedIP'] = null;
            if ($note !== null) {
                $update['OfficeNotes'] = $this->appendNote($deleted['OfficeNotes'] ?? null, $note);
            }
            if (!$model->update($guestId, $update)) {
                return $this->jsonError(422, 'restore_failed', $model->errors());
            }
            return $this->response->setStatusCode(200)
                ->setJSON(['data' => $this->dbToApi($model->find($guestId), $ctx['isPrivileged']), 'restored' => 1]);
        }

        try {
            $id = $model->insert($row, true);
        } catch (\Throwable $e) {
            log_message('error', 'guests insert failed: ' . $e->getMessage());
            return $this->jsonError(422, 'db_insert_failed', ['message' => $e->getMessage()]);
        }
        if (!$id) return $this->jsonError(422, 'insert_failed', $model->errors());
        return $this->response->setStatusCode(201)
            ->setJSON(['data' => $this->dbToApi($model->find($id), $ctx['isPrivileged'])]);

    }

    /** PUT /api/v1/guests/{id} */
    public function update(int $guestId)
    {
        $model = new EventGuestModel();
        $existing = $model->find($guestId);
        if (!$existing) return $this->jsonError(404, 'not_found');
        $companyId = (int) ($existing['InvitedByCompanyID'] ?? 0);
        if ($companyId <= 0) return $this->jsonError(422, 'guest_not_associated_with_company');
        $ctx = $this->loadContext($companyId);
        if (!$ctx) return $this->response;
        if ($ctx['eventLocked'] && !$ctx['isPrivileged']) return $this->jsonError(423, 'event_locked');
        if (!empty($existing['DeletedAt'])) return $this->jsonError(409, 'guest_removed');

        $payload = (array) $this->request->getJSON(true);

        // Guest-list managers cannot change privileged fields after creation.
        if (!$ctx['isPrivileged']) {
            foreach (self::PRIVILEGED_API_FIELDS as $f) unset($payload[$f]);
        }

        $row = $this->apiToDb($payload);

        if (array_key_exists('Type', $row)) {
            $row['Type'] = EventGuestModel::normalizeType($row['Type']);
        }
        if (array_key_exists('SignupType', $row) && !in_array((string) $row['SignupType'], self::SIGNUP_TYPES, true)) {
            unset($row['SignupType']);
        }
        // Related is derived from Type, never taken from the payload.
        if (array_key_exists('Type', $row)) {
            $row['Related'] = $row['Type'] === EventGuestModel::TYPE_EXHIBITOR ? 1 : 0;
        } else {
            unset($row['Related']);
        }
        if (array_key_exists('banquet', $payload)) {
            $row['BanquetCompanyID'] = ((int) $payload['banquet']) === 1 ? $companyId : null;
        }
        if (array_key_exists('Email', $row)) {
            $row['Email'] = EventGuestModel::normalizeEmail($row['Email']);
        }

        $row['UpdatedBy'] = $ctx['actorId'];
        $row['UpdatedIP'] = $this->clientIp();

        $candidate = array_merge($existing, $row);

        $errors = $this->validateRequired($candidate);
        if ($errors !== []) return $this->jsonError(422, 'validation_failed', $errors);

        $clash = $model->liveByEmailInEvent(
            (string) ($candidate['EventYear'] ?? ''),
            (string) $candidate['Email'],
            $guestId,
            $companyId
        );
        if ($clash) {
            return $this->jsonError(409, 'already_attending', ['message' => 'This email is already registered for this event']);
        }


        $check = $this->checkLimits($companyId, $ctx['company'], $candidate, $guestId);
        if ($check !== null) return $check;

        if (!$model->update($guestId, $row)) return $this->jsonError(422, 'update_failed', $model->errors());
        return $this->response->setJSON(['data' => $this->dbToApi($model->find($guestId), $ctx['isPrivileged'])]);
    }

    /** POST /api/v1/guests/{id}/banquet  body: { banquet: 0|1 } */
    public function banquet(int $guestId)
    {
        $model = new EventGuestModel();
        $existing = $model->find($guestId);
        if (!$existing) return $this->jsonError(404, 'not_found');
        $companyId = (int) ($existing['InvitedByCompanyID'] ?? 0);
        $ctx = $this->loadContext($companyId);
        if (!$ctx) return $this->response;
        if ($ctx['eventLocked'] && !$ctx['isPrivileged']) return $this->jsonError(423, 'event_locked');
        if (!empty($existing['DeletedAt'])) return $this->jsonError(409, 'guest_removed');

        $payload = (array) $this->request->getJSON(true);
        $on = (int) ($payload['banquet'] ?? 0) === 1;

        $candidate = $existing;
        $candidate['BanquetCompanyID'] = $on ? $companyId : null;
        $check = $this->checkLimits($companyId, $ctx['company'], $candidate, $guestId);
        if ($check !== null) return $check;

        $model->update($guestId, [
            'BanquetCompanyID' => $on ? $companyId : null,
            'UpdatedBy'        => $ctx['actorId'],
            'UpdatedIP'        => $this->clientIp(),
        ]);
        return $this->response->setJSON(['data' => $this->dbToApi($model->find($guestId), $ctx['isPrivileged'])]);
    }

    /** DELETE /api/v1/guests/{id} — soft delete */
    public function delete(int $guestId)
    {
        $model = new EventGuestModel();
        $existing = $model->find($guestId);
        if (!$existing) return $this->jsonError(404, 'not_found');
        $ctx = $this->loadContext((int) ($existing['InvitedByCompanyID'] ?? 0));
        if (!$ctx) return $this->response;
        if ($ctx['eventLocked'] && !$ctx['isPrivileged']) return $this->jsonError(423, 'event_locked');

        $model->update($guestId, [
            'DeletedAt' => date('Y-m-d H:i:s'),
            'DeletedBy' => $ctx['actorId'],
            'DeletedIP' => $this->clientIp(),
        ]);
        return $this->response->setStatusCode(204);
    }

    /** POST /api/v1/guests/{id}/restore — admin / event manager only */
    public function restore(int $guestId)
    {
        $model = new EventGuestModel();
        $existing = $model->find($guestId);
        if (!$existing) return $this->jsonError(404, 'not_found');
        $companyId = (int) ($existing['InvitedByCompanyID'] ?? 0);
        $ctx = $this->loadContext($companyId);
        if (!$ctx) return $this->response;
        if (!$ctx['isPrivileged']) return $this->jsonError(403, 'admin_required');
        if (empty($existing['DeletedAt'])) {
            return $this->response->setJSON(['data' => $this->dbToApi($existing, true)]);
        }

        $candidate = $existing;
        $candidate['DeletedAt'] = null;
        if ($model->liveByEmailInEvent((string) ($candidate['EventYear'] ?? ''), (string) ($candidate['Email'] ?? ''), $guestId, $companyId)) {
            return $this->jsonError(409, 'already_attending', ['message' => 'This email is already registered for this event']);
        }

        $check = $this->checkLimits($companyId, $ctx['company'], $candidate, $guestId);
        if ($check !== null) return $check;

        $model->update($guestId, [
            'DeletedAt' => null,
            'DeletedBy' => null,
            'DeletedIP' => null,
            'UpdatedBy' => $ctx['actorId'],
            'UpdatedIP' => $this->clientIp(),
        ]);
        return $this->response->setJSON(['data' => $this->dbToApi($model->find($guestId), true)]);
    }

    /**
     * POST /api/v1/guests/mark-bounced — service-only HMAC.
     * body: { email, message_id, event?: 'bounced'|'complained'|'unsubscribed',
     *         reason?: string, permanent?: bool }
     * Records the delivery state and returns the guest's list managers so the
     * caller can send them a notice.
     */
    public function markBounced()
    {
        $payload = (array) $this->request->getJSON(true);
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '') return $this->jsonError(400, 'email_required');

        $eventName = strtolower(trim((string) ($payload['event'] ?? 'bounced')));
        $reason    = substr(trim((string) ($payload['reason'] ?? '')), 0, 255);
        $permanent = !empty($payload['permanent']);

        $model = new EventGuestModel();
        $row = $model->where('LOWER(TRIM(Email))', $email)
            ->where('DeletedAt', null)
            ->orderBy('GuestID', 'DESC')
            ->first();
        if (!$row) {
            log_message('info', 'markBounced: no guest found for ' . $email);
            return $this->response->setJSON(['ok' => true, 'marked' => false]);
        }

        $now = date('Y-m-d H:i:s');
        $update = [];
        if ($eventName === 'complained') {
            $update['ComplainedAt'] = $now;
            $update['EmailSuppressed'] = 1;
            $update['BounceReason'] = $reason ?: 'Recipient marked the message as spam';
        } elseif ($eventName === 'unsubscribed') {
            $update['EmailSuppressed'] = 1;
            $update['BounceReason'] = $reason ?: 'Recipient unsubscribed';
        } else {
            $update['BouncedAt'] = $now;
            $update['BounceReason'] = $reason ?: 'Delivery failed';
            if ($permanent) $update['EmailSuppressed'] = 1;
        }
        $model->update((int) $row['GuestID'], $update);
        log_message('info', 'markBounced: GuestID ' . $row['GuestID'] . ' (' . $eventName . ') for ' . $email);

        $companyListId = (int) ($row['InvitedByCompanyID'] ?? 0);
        return $this->response->setJSON([
            'ok'        => true,
            'marked'    => true,
            'guest_id'  => (int) $row['GuestID'],
            'suppressed' => !empty($update['EmailSuppressed']) ? 1 : 0,
            'guest'     => [
                'given_name'  => $row['GivenName'] ?? null,
                'family_name' => $row['FamilyName'] ?? null,
                'native_name' => $row['NativeName'] ?? null,
                'email'       => $row['Email'] ?? null,
                'company'     => $row['Company'] ?? null,
            ],
            'company_guest_lists_id' => $companyListId ?: null,
            'company_name' => $companyListId ? $this->companyListName($companyListId) : null,
            'managers'  => $companyListId ? $this->managerContacts($companyListId) : [],
        ]);
    }

    /** @return array<int, array{name:string,email:string}> */
    private function managerContacts(int $companyGuestListsId): array
    {
        $userIds = (new \App\Models\CompanyGuestListsManagerModel())->userIdsForCompany($companyGuestListsId);
        if (!$userIds) return [];
        $rows = (new \App\Models\UserModel())->whereIn('UserID', $userIds)->findAll();
        $out = [];
        foreach ($rows as $u) {
            $email = trim((string) ($u['Email'] ?? ''));
            if ($email === '') continue;
            $name = trim(((string) ($u['GivenName'] ?? '')) . ' ' . ((string) ($u['FamilyName'] ?? '')));
            $out[] = ['name' => $name !== '' ? $name : $email, 'email' => $email];
        }
        return $out;
    }

    private function companyListName(int $companyGuestListsId): ?string
    {
        $row = (new \App\Models\CompanyGuestListsModel())->find($companyGuestListsId);
        if (!$row) return null;
        $name = $row['Company'] ?? $row['Name'] ?? null;
        return $name !== null && trim((string) $name) !== '' ? (string) $name : null;
    }

    /**
     * Returns a Response with 422 when the write would exceed a limit; null when OK.
     * $simulatedRow uses DB column names on `guests`.
     */
    private function checkLimits(int $companyGuestListsId, array $company, array $simulatedRow, ?int $excludeId)
    {
        if (!empty($simulatedRow['DeletedAt'])) return null;

        $model = new EventGuestModel();
        $rows = $model->where('InvitedByCompanyID', $companyGuestListsId)
            ->where('DeletedAt', null)
            ->findAll();
        if ($excludeId !== null) {
            $rows = array_values(array_filter($rows, fn($row) => (int) ($row['GuestID'] ?? 0) !== $excludeId));
        }
        $simulatedRow['GuestID'] = $excludeId ?? PHP_INT_MAX;
        $rows[] = $simulatedRow;
        $counts  = $this->countsForRows($rows);
        $type    = EventGuestModel::normalizeType($simulatedRow['Type'] ?? '');
        $banquet = !empty($simulatedRow['BanquetCompanyID']);

        if ($type === EventGuestModel::TYPE_PROFESSIONAL) {
            // Invite (Full Conference/EXPO) allows up to 150% of InviteCount, rounded up.
            $limit = $company['InviteCount'];
            if ($limit !== null && $limit !== '') {
                $hardCap = (int) ceil((int) $limit * 1.5);
                if ($counts['professional'] > $hardCap) {
                    return $this->jsonError(422, 'invite_limit_reached', ['limit' => $hardCap, 'current' => $counts['professional']]);
                }
            }
        } else {
            $limit = $company['EmployeeCount'];
            if ($limit !== null && $counts['exhibitor'] > (int) $limit) {
                return $this->jsonError(422, 'employee_limit_reached', ['limit' => (int) $limit, 'current' => $counts['exhibitor']]);
            }
        }
        if ($banquet) {
            $limit = $company['BanquetCount'];
            if ($limit !== null && $counts['banquet'] > (int) $limit) {
                return $this->jsonError(422, 'banquet_limit_reached', ['limit' => (int) $limit, 'current' => $counts['banquet']]);
            }
        }
        return null;
    }
}
