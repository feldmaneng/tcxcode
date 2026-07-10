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
        $counts = (new EventGuestModel())->countsForCompany($companyGuestListsId);
        return $this->response->setJSON([
            'data'   => array_map(fn($r) => $this->dbToApi($r), $rows),
            'counts' => $counts,
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

        $simulated = array_merge($existing, $row);
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
        $counts  = (new EventGuestModel())->countsForCompany($companyGuestListsId, $excludeId);
        $type    = $simulatedRow['Type'] ?? 'EXPO';
        $banquet = !empty($simulatedRow['BanquetCompanyID']);

        if ($type === 'EXPO') {
            $limit = $company['InviteCount'];
            if ($limit !== null && ($counts['expo'] + 1) > (int) $limit) {
                return $this->jsonError(422, 'invite_limit_reached', ['limit' => (int) $limit, 'current' => $counts['expo']]);
            }
        } else {
            $limit = $company['EmployeeCount'];
            if ($limit !== null && ($counts['professional'] + 1) > (int) $limit) {
                return $this->jsonError(422, 'employee_limit_reached', ['limit' => (int) $limit, 'current' => $counts['professional']]);
            }
        }
        if ($banquet) {
            $limit = $company['BanquetCount'];
            if ($limit !== null && ($counts['banquet'] + 1) > (int) $limit) {
                return $this->jsonError(422, 'banquet_limit_reached', ['limit' => (int) $limit, 'current' => $counts['banquet']]);
            }
        }
        return null;
    }
}
