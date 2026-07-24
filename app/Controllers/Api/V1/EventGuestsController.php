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
 * Read/write allowed to admins and any user in companyguestlists_managers for the row.
 * Enforces InviteCount / EmployeeCount / BanquetCount on writes.
 * Locked when the matching event (by Year) is locked (auto or forced).
 */
class EventGuestsController extends BaseApiController
{
    /** API field => DB column on `guests` */
    private const FIELD_MAP = [
        'id'                     => 'GuestID',
        'company_guest_lists_id' => 'InvitedByCompanyID',
        'given_name'             => 'GivenName',
        'family_name'            => 'FamilyName',
        'email'                  => 'Email',
        'guest_type'             => 'Type',
        'notes'                  => 'OfficeNotes',
        'event_year'             => 'EventYear',
        'added_by'               => 'AddedBy',
        'updated_by'             => 'UpdatedBy',
        'updated'                => 'Stamp',
    ];
    private const READONLY_API_FIELDS = ['id', 'company_guest_lists_id', 'event_year', 'added_by', 'updated_by', 'updated'];

    private function dbToApi(array $row): array
    {
        $out = [];
        foreach (self::FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
        }
        // banquet derived from BanquetCompanyID
        if (array_key_exists('BanquetCompanyID', $row)) {
            $out['banquet'] = ((int) $row['BanquetCompanyID']) > 0 ? 1 : 0;
        }
        foreach (['id', 'company_guest_lists_id', 'banquet', 'added_by', 'updated_by'] as $k) {
            if (array_key_exists($k, $out) && $out[$k] !== null && $out[$k] !== '') {
                $out[$k] = (int) $out[$k];
            }
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

    /**
     * Prefer email when present; otherwise use normalized given/family name.
     * Used to collapse legacy duplicate rows in the displayed guest list.
     */
    private function guestIdentityKey(array $row): string
    {
        $email = $this->guestEmailKey($row);
        if ($email !== '') return 'email:' . $email;
        $name = $this->guestNameKey($row);
        return $name !== '' ? 'name:' . $name : 'row:' . (string) ($row['GuestID'] ?? spl_object_id((object) $row));
    }

    /**
     * Collapse duplicate legacy rows, keeping the highest GuestID as the visible/latest row.
     */
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

    /** @return array{expo:int,professional:int,banquet:int} */
    private function countsForRows(array $rows): array
    {
        $counts = ['expo' => 0, 'professional' => 0, 'banquet' => 0];
        foreach ($this->dedupeGuestRows($rows) as $row) {
            if (($row['Type'] ?? 'EXPO') === 'PROFESSIONAL') $counts['professional']++;
            else $counts['expo']++;
            if (!empty($row['BanquetCompanyID'])) $counts['banquet']++;
        }
        return $counts;
    }

    private function duplicateExists(array $row, int $companyGuestListsId, ?int $excludeGuestId = null): bool
    {
        $eventYear = (string) ($row['EventYear'] ?? '');
        $emailNorm = $this->guestEmailKey($row);
        $nameKey = $emailNorm === '' ? $this->guestNameKey($row) : '';

        if ($emailNorm === '' && $nameKey === '') return false;

        $q = (new EventGuestModel())->builder();
        if ($eventYear !== '') $q->where('EventYear', $eventYear);
        else $q->where('InvitedByCompanyID', $companyGuestListsId);
        if ($excludeGuestId !== null) $q->where('GuestID !=', $excludeGuestId);

        $q->groupStart();
        $hasCondition = false;
        if ($emailNorm !== '') {
            $q->where('LOWER(TRIM(Email))', $emailNorm);
            $hasCondition = true;
        }
        if ($nameKey !== '') {
            [$given, $family] = explode('|', $nameKey, 2);
            if ($hasCondition) $q->orGroupStart();
            else $q->groupStart();
            $q->where('LOWER(TRIM(GivenName))', $given)
                ->where('LOWER(TRIM(FamilyName))', $family)
                ->groupEnd();
        }
        $q->groupEnd();

        return $q->limit(1)->get()->getRowArray() !== null;
    }

    /** @return array{ok:bool,actorId?:int,isAdmin?:bool,company?:array,eventLocked?:bool}|null */
    private function loadContext(int $companyGuestListsId)
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) { $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']); return null; }
        $company = (new CompanyGuestListsModel())->find($companyGuestListsId);
        if (!$company) { $this->response->setStatusCode(404)->setJSON(['error' => 'not_found']); return null; }
        $isAdmin = (new UserModuleModel())->userHasModule($actorId, 'admin');
        if (!$isAdmin && !(new CompanyGuestListsManagerModel())->userManages($actorId, $companyGuestListsId)) {
            $this->response->setStatusCode(403)->setJSON(['error' => 'forbidden']);
            return null;
        }
        $eventLocked = false;
        $year = (int) ($company['Year'] ?? 0);
        if ($year > 0) {
            $event = (new EventModel())->where('Year', $year)->first();
            if ($event) $eventLocked = (new EventModel())->isLocked((int) $event['EventID']);
        }
        return ['ok' => true, 'actorId' => $actorId, 'isAdmin' => $isAdmin, 'company' => $company, 'eventLocked' => $eventLocked];
    }

    /** GET /api/v1/company-guest-lists/{id}/guests */
    public function index(int $companyGuestListsId)
    {
        $ctx = $this->loadContext($companyGuestListsId);
        if (!$ctx) return $this->response;
        $rows = (new EventGuestModel())
            ->where('InvitedByCompanyID', $companyGuestListsId)
            ->orderBy('FamilyName', 'ASC')->orderBy('GivenName', 'ASC')
            ->findAll();
        $visibleRows = $this->dedupeGuestRows($rows);
        $counts = $this->countsForRows($visibleRows);
        return $this->response->setJSON([
            'data'   => array_map(fn($r) => $this->dbToApi($r), $visibleRows),
            'counts' => $counts,
            'duplicate_count' => max(0, count($rows) - count($visibleRows)),
            'limits' => [
                'invite_count'   => $ctx['company']['InviteCount'] !== null ? (int) $ctx['company']['InviteCount'] : null,
                'employee_count' => $ctx['company']['EmployeeCount'] !== null ? (int) $ctx['company']['EmployeeCount'] : null,
                'banquet_count'  => $ctx['company']['BanquetCount'] !== null ? (int) $ctx['company']['BanquetCount'] : null,
            ],
            'event_locked' => $ctx['eventLocked'],
        ]);
    }

    /** POST /api/v1/company-guest-lists/{id}/guests */
    public function create(int $companyGuestListsId)
    {
        $ctx = $this->loadContext($companyGuestListsId);
        if (!$ctx) return $this->response;
        if ($ctx['eventLocked'] && !$ctx['isAdmin']) return $this->jsonError(423, 'event_locked');

        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);
        $row['InvitedByCompanyID'] = $companyGuestListsId;
        // Populate legacy metadata from the guest list.
        $row['EventYear'] = (string) ($ctx['company']['EventYear'] ?? $ctx['company']['Year'] ?? '');
        if (empty($row['Company']) && !empty($ctx['company']['Company'])) {
            $row['Company'] = $ctx['company']['Company'];
        }

        if (empty($row['GivenName']) && empty($row['FamilyName'])) {
            return $this->jsonError(422, 'validation_failed', ['required' => ['given_name or family_name']]);
        }
        $type = $row['Type'] ?? 'EXPO';
        if (!in_array($type, ['EXPO', 'PROFESSIONAL'], true)) {
            return $this->jsonError(422, 'validation_failed', ['guest_type' => 'must be EXPO or PROFESSIONAL']);
        }
        $row['Type'] = $type;
        // Email is NOT NULL on guests; default to empty string when omitted.
        if (!array_key_exists('Email', $row) || $row['Email'] === null) $row['Email'] = '';
        $row['Email'] = $this->guestEmailKey($row);

        // Prevent duplicate guests within the same event (across all company guest lists).
        if ($this->duplicateExists($row, $companyGuestListsId, null)) {
            return $this->jsonError(409, 'already_attending', ['message' => 'Guest is already attending this event']);
        }

        $banquet = isset($payload['banquet']) && (int) $payload['banquet'] === 1;
        $row['BanquetCompanyID'] = $banquet ? $companyGuestListsId : null;
        $row['AddedBy']   = (int) $ctx['actorId'];
        $row['UpdatedBy'] = (int) $ctx['actorId'];

        $check = $this->checkLimits($companyGuestListsId, $ctx['company'], $row, null);
        if ($check !== null) return $check;


        $model = new EventGuestModel();
        $id    = $model->insert($row, true);
        if (!$id) return $this->jsonError(422, 'insert_failed', $model->errors());
        return $this->response->setStatusCode(201)->setJSON(['data' => $this->dbToApi($model->find($id))]);
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
        if ($ctx['eventLocked'] && !$ctx['isAdmin']) return $this->jsonError(423, 'event_locked');

        $payload = (array) $this->request->getJSON(true);
        $row     = $this->apiToDb($payload);
        if (isset($row['Type']) && !in_array($row['Type'], ['EXPO', 'PROFESSIONAL'], true)) {
            return $this->jsonError(422, 'validation_failed', ['guest_type' => 'must be EXPO or PROFESSIONAL']);
        }
        if (array_key_exists('banquet', $payload)) {
            $row['BanquetCompanyID'] = ((int) $payload['banquet']) === 1 ? $companyId : null;
        }
        $row['UpdatedBy'] = (int) $ctx['actorId'];

        if (array_key_exists('Email', $row)) {
            $row['Email'] = $this->guestEmailKey($row);
        }
        $candidate = array_merge($existing, $row);
        if ($this->duplicateExists($candidate, $companyId, $guestId)) {
            return $this->jsonError(409, 'already_attending', ['message' => 'Guest is already attending this event']);
        }

        $simulated = $candidate;
        $check = $this->checkLimits($companyId, $ctx['company'], $simulated, $guestId);
        if ($check !== null) return $check;

        if (!$model->update($guestId, $row)) return $this->jsonError(422, 'update_failed', $model->errors());
        return $this->response->setJSON(['data' => $this->dbToApi($model->find($guestId))]);
    }

    /** DELETE /api/v1/guests/{id} */
    public function delete(int $guestId)
    {
        $model = new EventGuestModel();
        $existing = $model->find($guestId);
        if (!$existing) return $this->jsonError(404, 'not_found');
        $ctx = $this->loadContext((int) ($existing['InvitedByCompanyID'] ?? 0));
        if (!$ctx) return $this->response;
        if ($ctx['eventLocked'] && !$ctx['isAdmin']) return $this->jsonError(423, 'event_locked');
        $model->delete($guestId);
        return $this->response->setStatusCode(204);
    }

    /**
     * Returns a Response with 422 when the write would exceed a limit; null when OK.
     * $simulatedRow uses DB column names on `guests`.
     */
    private function checkLimits(int $companyGuestListsId, array $company, array $simulatedRow, ?int $excludeId)
    {
        $model = new EventGuestModel();
        $rows = $model->where('InvitedByCompanyID', $companyGuestListsId)->findAll();
        if ($excludeId !== null) {
            $rows = array_values(array_filter($rows, fn($row) => (int) ($row['GuestID'] ?? 0) !== $excludeId));
        }
        $simulatedRow['GuestID'] = $excludeId ?? PHP_INT_MAX;
        $rows[] = $simulatedRow;
        $counts  = $this->countsForRows($rows);
        $type    = $simulatedRow['Type'] ?? 'EXPO';
        $banquet = !empty($simulatedRow['BanquetCompanyID']);

        if ($type === 'EXPO') {
            $limit = $company['InviteCount'];
            if ($limit !== null && $counts['expo'] > (int) $limit) {
                return $this->jsonError(422, 'invite_limit_reached', ['limit' => (int) $limit, 'current' => $counts['expo']]);
            }
        } else {
            $limit = $company['EmployeeCount'];
            if ($limit !== null && $counts['professional'] > (int) $limit) {
                return $this->jsonError(422, 'employee_limit_reached', ['limit' => (int) $limit, 'current' => $counts['professional']]);
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
