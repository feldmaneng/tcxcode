<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\ContactUserProvisioner;
use App\Libraries\ModuleAccess;
use App\Models\EventModel;
use App\Models\ExpoDirectoryCoordinatorModel;
use App\Models\ExpoDirectoryModel;
use App\Models\UserModuleModel;

/**
 * Exhibitor directory (expodirectory) — one row per company per event.
 *
 * DATABASES
 *   expodirectory + expodirectory_coordinators -> `registration` group
 *                                                 (bitswork_registration)
 *   events / contacts / companies              -> default (conference) group
 *   users / modules                            -> `control` group
 *
 * Because those live in different databases, NOTHING here joins across groups.
 * Event names and coordinator names/emails are fetched with a second query and
 * merged in PHP.
 *
 * Access:
 *   - module 'admin' or 'expo'  → full CRUD across all exhibitors.
 *   - assigned coordinator      → read/write their own rows on open events
 *                                 (implicit; no module grant needed).
 *   - everyone else             → 403.
 */
class ExpoDirectoryController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'                  => 'EntryID',
        'secret_key'          => 'SecretKey',
        'year'                => 'Year',
        'event'               => 'Event',
        'event_id'            => 'EventID',
        'status'              => 'Status',
        'company_id'          => 'CompanyID',
        'company_name'        => 'CompanyName',
        'directory_name'      => 'DirectoryName',
        'sample_entry'        => 'SampleEntry',
        'line1'               => 'Line1',
        'line2'               => 'Line2',
        'line3'               => 'Line3',
        'line4'               => 'Line4',
        'line5'               => 'Line5',
        'line6'               => 'Line6',
        'description'         => 'Description',
        'url'                 => 'URL',
        'logo_file'           => 'LogoFile',
        'upload'              => 'Upload',
        'expo_application'    => 'EXPOApplication',
        'registration_date'   => 'RegistrationDate',
        'booth_number'        => 'BoothNumber',
        'booth_type'          => 'BoothType',
        'staff_quantity'      => 'StaffQuantity',
        'staff_reg_code'      => 'StaffRegCode',
        'attendee_code'       => 'AttendeeCode',
        'contact_id'          => 'ContactID',
        'contact_given_name'  => 'ContactGivenName',
        'contact_family_name' => 'ContactFamilyName',
        'contact_email'       => 'ContactEmail',
        'cc_email'            => 'CCEmail',
        'notes'               => 'Notes',
        'company_guest_lists_id' => 'CompanyGuestListsID',
    ];

    /** Never writable through the API. */
    private const READONLY_API_FIELDS = ['id', 'secret_key'];

    /** Only admins / expo-module users may change these. */
    private const PRIVILEGED_API_FIELDS = ['status', 'notes', 'booth_number', 'booth_type', 'staff_quantity', 'staff_reg_code', 'attendee_code', 'event_id', 'year', 'event', 'company_guest_lists_id'];

    private const INT_FIELDS = ['id', 'year', 'event_id', 'company_id', 'staff_quantity', 'contact_id', 'company_guest_lists_id'];

    private const SORTABLE = ['company_name', 'directory_name', 'status', 'booth_number', 'year'];

    // ---------------------------------------------------------------- mapping

    private function dbToApi(array $row): array
    {
        $out = [];
        foreach (self::FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
        }
        foreach (self::INT_FIELDS as $k) {
            if (array_key_exists($k, $out) && $out[$k] !== null && $out[$k] !== '') $out[$k] = (int) $out[$k];
        }
        if (array_key_exists('Updated', $row)) $out['updated'] = $row['Updated'];
        return $out;
    }

    private function apiToDb(array $payload, bool $privileged): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            if (in_array($k, self::READONLY_API_FIELDS, true)) continue;
            if (!$privileged && in_array($k, self::PRIVILEGED_API_FIELDS, true)) continue;
            if (!isset(self::FIELD_MAP[$k])) continue;
            $out[self::FIELD_MAP[$k]] = $v;
        }
        return $out;
    }

    // ------------------------------------------------------------------ auth

    private function actorId(): ?int
    {
        return ApiAuthContext::actingUserId();
    }

    /** ContactID of the acting user (control DB), 0 when unlinked. */
    private function actorContactId(int $userId): int
    {
        try {
            $row = db_connect('control')->table('users')->select('ContactID')
                ->where('UserID', $userId)->get()->getRowArray();
            return (int) ($row['ContactID'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Admin or holder of the `expo` module = full CRUD. */
    private function isPrivileged(int $userId): bool
    {
        $codes = ModuleAccess::codesForUser($userId);
        if (in_array('admin', $codes, true)) return true;
        // An implicit `expo` grant (coordinator) must NOT confer full CRUD, so
        // check the explicit user_modules rows for `expo`.
        return (new UserModuleModel())->userHasModule($userId, 'expo');
    }

    /**
     * @return array{0:?int,1:bool,2:int} [userId, privileged, contactId]
     *         userId null = trusted service-to-service call.
     */
    private function actorContext(): array
    {
        $userId = $this->actorId();
        if ($userId === null) return [null, true, 0];
        return [$userId, $this->isPrivileged($userId), $this->actorContactId($userId)];
    }

    private function eventIsOpen(?int $eventId): bool
    {
        if (!$eventId) return false;
        try {
            return !(new EventModel())->isLocked($eventId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // -------------------------------------------------------------- hydration

    /** @param array<int,array> $rows api-shaped rows */
    private function hydrate(array $rows): array
    {
        if (!$rows) return $rows;

        $entryIds = array_values(array_filter(array_map(fn($r) => (int) ($r['id'] ?? 0), $rows)));
        $coordsByEntry = (new ExpoDirectoryCoordinatorModel())->forEntries($entryIds);

        // Collect all contact ids across coordinators (conference DB lookup).
        $contactIds = [];
        foreach ($coordsByEntry as $list) {
            foreach ($list as $c) $contactIds[(int) $c['ContactID']] = true;
        }
        $contacts = $this->contactsById(array_keys($contactIds));

        $eventIds = [];
        foreach ($rows as $r) { if (!empty($r['event_id'])) $eventIds[(int) $r['event_id']] = true; }
        $events = $this->eventsById(array_keys($eventIds));

        foreach ($rows as &$r) {
            $id = (int) ($r['id'] ?? 0);
            $r['coordinators'] = array_values(array_map(function (array $c) use ($contacts) {
                $cid = (int) $c['ContactID'];
                $ct  = $contacts[$cid] ?? null;
                return [
                    'contact_id'  => $cid,
                    'given_name'  => $ct['GivenName'] ?? null,
                    'family_name' => $ct['FamilyName'] ?? null,
                    'nickname'    => $ct['Nickname'] ?? null,
                    'email'       => $ct['Email'] ?? null,
                    'is_primary'  => (int) ($c['IsPrimary'] ?? 0),
                ];
            }, $coordsByEntry[$id] ?? []));

            $ev = $events[(int) ($r['event_id'] ?? 0)] ?? null;
            $r['event_name'] = $ev['Name'] ?? null;
            $r['event_year'] = isset($ev['Year']) ? (int) $ev['Year'] : null;
        }
        unset($r);
        return $rows;
    }

    /** @param int[] $ids @return array<int,array> keyed by ContactID */
    private function contactsById(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) return [];
        try {
            $rows = db_connect()->table('contacts')
                ->select('ContactID, GivenName, FamilyName, Nickname, Email')
                ->whereIn('ContactID', $ids)->get()->getResultArray();
        } catch (\Throwable $e) {
            // Older schemas may not have Nickname.
            try {
                $rows = db_connect()->table('contacts')
                    ->select('ContactID, GivenName, FamilyName, Email')
                    ->whereIn('ContactID', $ids)->get()->getResultArray();
            } catch (\Throwable $e2) {
                log_message('error', '[expo] contact hydration failed: ' . $e2->getMessage());
                return [];
            }
        }
        $out = [];
        foreach ($rows as $r) $out[(int) $r['ContactID']] = $r;
        return $out;
    }

    /** @param int[] $ids @return array<int,array> keyed by EventID */
    private function eventsById(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) return [];
        try {
            $rows = db_connect()->table('events')
                ->select('EventID, Year, Name, IsClosed, EndDate, GuestListEnabled')
                ->whereIn('EventID', $ids)->get()->getResultArray();
        } catch (\Throwable $e) {
            // Older schemas may not have GuestListEnabled.
            try {
                $rows = db_connect()->table('events')
                    ->select('EventID, Year, Name, IsClosed, EndDate')
                    ->whereIn('EventID', $ids)->get()->getResultArray();
            } catch (\Throwable $e2) {
                return [];
            }
        }

        $out = [];
        foreach ($rows as $r) $out[(int) $r['EventID']] = $r;
        return $out;
    }

    // ------------------------------------------------------------------ CRUD

    /** GET /api/v1/expo-directory */
    public function index()
    {
        [$userId, $privileged, $contactId] = $this->actorContext();
        if ($userId !== null && !$privileged) {
            $ownIds = (new ExpoDirectoryCoordinatorModel())->entryIdsForContact($contactId);
            if (!$ownIds) return $this->response->setJSON(['data' => [], 'page' => 1, 'per_page' => 0, 'total' => 0]);
        } else {
            $ownIds = null;
        }

        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(500, max(1, (int) ($this->request->getGet('per_page') ?? 200)));
        $sort    = (string) ($this->request->getGet('sort') ?? 'company_name');
        $desc    = str_starts_with($sort, '-');
        $sortKey = ltrim($sort, '-');
        if (!in_array($sortKey, self::SORTABLE, true)) $sortKey = 'company_name';

        $model   = new ExpoDirectoryModel();
        $builder = $model->builder();

        if ($ownIds !== null) $builder->whereIn('EntryID', $ownIds);

        $eventId = (int) ($this->request->getGet('event_id') ?? 0);
        if ($eventId > 0) $builder->where('EventID', $eventId);

        $year = (int) ($this->request->getGet('year') ?? 0);
        if ($year > 0) $builder->where('Year', $year);

        $status = trim((string) ($this->request->getGet('status') ?? ''));
        if ($status !== '' && in_array($status, ExpoDirectoryModel::STATUSES, true)) {
            $builder->where('Status', $status);
        }

        $q = trim((string) ($this->request->getGet('q') ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('CompanyName', $q)
                ->orLike('DirectoryName', $q)
                ->orLike('BoothNumber', $q)
                ->groupEnd();
        }

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->orderBy(self::FIELD_MAP[$sortKey], $desc ? 'DESC' : 'ASC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()->getResultArray();

        $data = $this->hydrate(array_map(fn($r) => $this->dbToApi($r), $rows));

        return $this->response->setJSON([
            'data' => $data, 'page' => $page, 'per_page' => $perPage, 'total' => $total,
            'is_privileged' => $privileged ? 1 : 0,
        ]);
    }

    /** GET /api/v1/expo-directory/prior-entries?q=&exclude_event_id= */
    public function priorEntries()
    {
        [$userId, $privileged] = $this->actorContext();
        if ($userId !== null && !$privileged) return $this->jsonError(403, 'forbidden');

        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $builder = (new ExpoDirectoryModel())->builder()
            ->select('EntryID, Year, Event, EventID, CompanyName, DirectoryName, BoothNumber, BoothType, Description, Status');
        if ($q !== '') {
            $builder->groupStart()->like('CompanyName', $q)->orLike('DirectoryName', $q)->groupEnd();
        }
        $excludeEvent = (int) ($this->request->getGet('exclude_event_id') ?? 0);
        if ($excludeEvent > 0) $builder->where('(EventID IS NULL OR EventID <> ' . $excludeEvent . ')', null, false);

        $rows = $builder->orderBy('Year', 'DESC')->orderBy('CompanyName', 'ASC')->limit(50)->get()->getResultArray();

        return $this->response->setJSON([
            'data' => array_map(function (array $r) {
                $d = (string) ($r['Description'] ?? '');
                return [
                    'id'             => (int) $r['EntryID'],
                    'year'           => (int) $r['Year'],
                    'event'          => $r['Event'],
                    'event_id'       => $r['EventID'] === null ? null : (int) $r['EventID'],
                    'company_name'   => $r['CompanyName'],
                    'directory_name' => $r['DirectoryName'],
                    'booth_number'   => $r['BoothNumber'] ?? null,
                    'booth_type'     => $r['BoothType'] ?? null,
                    'status'         => $r['Status'],
                    'description'    => mb_substr($d, 0, 140),
                ];
            }, $rows),
        ]);
    }

    /** GET /api/v1/expo-directory/{id} */
    public function show(int $id)
    {
        [$userId, $privileged, $contactId] = $this->actorContext();
        $row = (new ExpoDirectoryModel())->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');

        $canWrite = $privileged;
        if ($userId !== null && !$privileged) {
            $coords = new ExpoDirectoryCoordinatorModel();
            if (!$coords->isCoordinator($contactId, $id)) return $this->jsonError(403, 'forbidden');
            $canWrite = $this->eventIsOpen($row['EventID'] === null ? null : (int) $row['EventID']);
        }

        $data = $this->hydrate([$this->dbToApi($row)])[0];
        return $this->response->setJSON([
            'data'          => $data,
            'can_write'     => $canWrite ? 1 : 0,
            'is_privileged' => $privileged ? 1 : 0,
            'event_locked'  => $this->eventIsOpen($row['EventID'] === null ? null : (int) $row['EventID']) ? false : true,
            'guest_list_info' => $this->guestListPayload($row),
        ]);
    }

    /**
     * POST /api/v1/expo-directory
     * Body: { event_id, company_name?, company_id?, copy_from_entry_id? }
     */
    public function create()
    {
        [$userId, $privileged] = $this->actorContext();
        if ($userId !== null && !$privileged) return $this->jsonError(403, 'forbidden');

        $payload = (array) $this->request->getJSON(true);
        $eventId = (int) ($payload['event_id'] ?? 0);
        if ($eventId <= 0) return $this->jsonError(422, 'validation_failed', ['required' => ['event_id']]);

        $event = $this->eventsById([$eventId])[$eventId] ?? null;
        if (!$event) return $this->jsonError(422, 'unknown_event');

        $model = new ExpoDirectoryModel();
        $insert = [
            'EventID'   => $eventId,
            'Year'      => (int) $event['Year'],
            'Event'     => (string) ($event['Name'] ?? ''),
            'Status'    => 'Draft',
            'SecretKey' => null,
        ];

        $copyFrom = (int) ($payload['copy_from_entry_id'] ?? 0);
        $source   = null;
        if ($copyFrom > 0) {
            $source = $model->find($copyFrom);
            if (!$source) return $this->jsonError(422, 'copy_source_not_found');
            foreach (ExpoDirectoryModel::COPY_FIELDS as $f) {
                if (array_key_exists($f, $source)) $insert[$f] = $source[$f];
            }
        }

        // Explicit payload values win over copied ones.
        foreach ($this->apiToDb($payload, true) as $k => $v) {
            if (in_array($k, ['EntryID', 'SecretKey', 'Status', 'EventID', 'Year', 'Event'], true)) continue;
            $insert[$k] = $v;
        }

        if (trim((string) ($insert['CompanyName'] ?? '')) === '') {
            return $this->jsonError(422, 'validation_failed', ['required' => ['company_name']]);
        }
        // Booth / registration data never carries forward.
        foreach (['BoothNumber', 'BoothType', 'StaffQuantity', 'StaffRegCode', 'AttendeeCode', 'RegistrationDate', 'EXPOApplication', 'Upload', 'Notes'] as $f) {
            $insert[$f] = null;
        }

        $newId = (int) $model->insert($this->filterExisting($insert, 'expodirectory'), true);
        if ($newId <= 0) return $this->jsonError(500, 'insert_failed');

        // Carry coordinators forward from the source entry.
        if ($source) {
            $coords = new ExpoDirectoryCoordinatorModel();
            foreach ($coords->forEntry($copyFrom) as $c) {
                try {
                    $coords->insert([
                        'EntryID'   => $newId,
                        'ContactID' => (int) $c['ContactID'],
                        'IsPrimary' => (int) ($c['IsPrimary'] ?? 0),
                        'SortOrder' => (int) ($c['SortOrder'] ?? 0),
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        $row  = $model->find($newId);
        $data = $this->hydrate([$this->dbToApi($row)])[0];
        return $this->response->setStatusCode(201)->setJSON(['data' => $data]);
    }

    /** PUT /api/v1/expo-directory/{id} */
    public function update(int $id)
    {
        [$userId, $privileged, $contactId] = $this->actorContext();
        $model = new ExpoDirectoryModel();
        $row   = $model->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');

        if ($userId !== null && !$privileged) {
            if (!(new ExpoDirectoryCoordinatorModel())->isCoordinator($contactId, $id)) {
                return $this->jsonError(403, 'forbidden');
            }
            if (!$this->eventIsOpen($row['EventID'] === null ? null : (int) $row['EventID'])) {
                return $this->jsonError(403, 'event_closed');
            }
        }

        $payload = (array) $this->request->getJSON(true);
        $patch   = $this->apiToDb($payload, $privileged);

        if (isset($patch['Status']) && !in_array($patch['Status'], ExpoDirectoryModel::STATUSES, true)) {
            return $this->jsonError(422, 'validation_failed', ['status' => 'invalid']);
        }
        // Keep the legacy Year/Event strings in sync when the event changes.
        if (isset($patch['EventID'])) {
            $ev = $this->eventsById([(int) $patch['EventID']])[(int) $patch['EventID']] ?? null;
            if (!$ev) return $this->jsonError(422, 'unknown_event');
            $patch['Year']  = (int) $ev['Year'];
            $patch['Event'] = (string) ($ev['Name'] ?? '');
        }
        // ContactID stays the source of truth; mirror the legacy name/email columns.
        if (array_key_exists('ContactID', $patch)) {
            $cid = (int) $patch['ContactID'];
            $ct  = $cid > 0 ? ($this->contactsById([$cid])[$cid] ?? null) : null;
            $patch['ContactGivenName']  = $ct['GivenName'] ?? null;
            $patch['ContactFamilyName'] = $ct['FamilyName'] ?? null;
            $patch['ContactEmail']      = $ct['Email'] ?? null;
        }

        if ($patch) $model->update($id, $this->filterExisting($patch, 'expodirectory'));

        $data = $this->hydrate([$this->dbToApi($model->find($id))])[0];
        return $this->response->setJSON(['data' => $data]);
    }

    /** DELETE /api/v1/expo-directory/{id} */
    public function delete(int $id)
    {
        [$userId, $privileged] = $this->actorContext();
        if ($userId !== null && !$privileged) return $this->jsonError(403, 'forbidden');
        (new ExpoDirectoryCoordinatorModel())->where('EntryID', $id)->delete();
        (new ExpoDirectoryModel())->delete($id);
        return $this->response->setStatusCode(204);
    }

    // ---------------------------------------------------------- coordinators

    /** GET /api/v1/expo-directory/{id}/coordinators */
    public function coordinators(int $id)
    {
        [$userId, $privileged, $contactId] = $this->actorContext();
        if ($userId !== null && !$privileged
            && !(new ExpoDirectoryCoordinatorModel())->isCoordinator($contactId, $id)) {
            return $this->jsonError(403, 'forbidden');
        }
        $entry = (new ExpoDirectoryModel())->find($id);
        if (!$entry) return $this->jsonError(404, 'not_found');
        $data = $this->hydrate([$this->dbToApi($entry)])[0];
        return $this->response->setJSON(['data' => $data['coordinators']]);
    }

    /**
     * POST /api/v1/expo-directory/{id}/coordinators  { contact_id, is_primary? }
     * Pre-provisions a user account for the contact when none exists.
     */
    public function addCoordinator(int $id)
    {
        [$userId, $privileged] = $this->actorContext();
        if ($userId !== null && !$privileged) return $this->jsonError(403, 'forbidden');

        $model = new ExpoDirectoryModel();
        $entry = $model->find($id);
        if (!$entry) return $this->jsonError(404, 'not_found');

        $payload   = (array) $this->request->getJSON(true);
        $contactId = (int) ($payload['contact_id'] ?? 0);
        $isPrimary = (int) ($payload['is_primary'] ?? 0) === 1;
        if ($contactId <= 0) return $this->jsonError(422, 'validation_failed', ['required' => ['contact_id']]);

        $prov    = new ContactUserProvisioner();
        $contact = $prov->contact($contactId);
        if (!$contact) return $this->jsonError(422, 'contact_not_found');
        if (trim((string) ($contact['Email'] ?? '')) === '') {
            return $this->jsonError(422, 'contact_email_required');
        }

        $coords  = new ExpoDirectoryCoordinatorModel();
        $current = $coords->forEntry($id);
        foreach ($current as $c) {
            if ((int) $c['ContactID'] === $contactId) {
                return $this->response->setJSON(['data' => ['contact_id' => $contactId, 'already' => true]]);
            }
        }
        if (count($current) >= ExpoDirectoryCoordinatorModel::MAX_COORDINATORS) {
            return $this->jsonError(422, 'max_coordinators_reached', ['max' => ExpoDirectoryCoordinatorModel::MAX_COORDINATORS]);
        }

        // Pre-provision an (unclaimed) WP-SSO account — access is implicit, so
        // no module grant is needed.
        $provisioned = 'existing';
        try {
            if (!$prov->findUserForContact($contact)) {
                $prov->createUserForContact($contact, []);
                $provisioned = 'created';
            }
        } catch (\Throwable $e) {
            log_message('error', '[expo] coordinator provisioning failed: ' . $e->getMessage());
            $provisioned = 'failed';
        }

        $makePrimary = $isPrimary || count($current) === 0;
        if ($makePrimary) {
            $coords->where('EntryID', $id)->set('IsPrimary', 0)->update();
        }
        $coords->insert([
            'EntryID'   => $id,
            'ContactID' => $contactId,
            'IsPrimary' => $makePrimary ? 1 : 0,
            'SortOrder' => count($current),
            'AddedBy'   => $userId,
        ]);

        if ($makePrimary) $this->syncLegacyContactColumns($id, $contactId);

        // Coordinators are guest-list managers by default when this exhibitor
        // is linked to a guest list.
        try {
            $gl = $this->resolveGuestList((new ExpoDirectoryModel())->find($id));
            if ($gl) {
                (new \App\Libraries\GuestListManagerSync())
                    ->ensureContactIsManager((int) $gl['CompanyID'], $contact, $userId);
            }
        } catch (\Throwable $e) {
            log_message('error', '[expo] manager sync on coordinator add failed: ' . $e->getMessage());
        }

        return $this->response->setStatusCode(201)->setJSON([
            'data' => ['contact_id' => $contactId, 'is_primary' => $makePrimary ? 1 : 0, 'provisioned' => $provisioned],
        ]);
    }

    /** POST /api/v1/expo-directory/{id}/coordinators/{contactId}/primary */
    public function setPrimaryCoordinator(int $id, int $contactId)
    {
        [$userId, $privileged] = $this->actorContext();
        if ($userId !== null && !$privileged) return $this->jsonError(403, 'forbidden');

        $coords = new ExpoDirectoryCoordinatorModel();
        if (!$coords->isCoordinator($contactId, $id)) return $this->jsonError(404, 'not_found');
        $coords->where('EntryID', $id)->set('IsPrimary', 0)->update();
        $coords->where('EntryID', $id)->where('ContactID', $contactId)->set('IsPrimary', 1)->update();
        $this->syncLegacyContactColumns($id, $contactId);
        return $this->response->setJSON(['data' => ['contact_id' => $contactId, 'is_primary' => 1]]);
    }

    /** DELETE /api/v1/expo-directory/{id}/coordinators/{contactId} */
    public function removeCoordinator(int $id, int $contactId)
    {
        [$userId, $privileged] = $this->actorContext();
        if ($userId !== null && !$privileged) return $this->jsonError(403, 'forbidden');

        $coords = new ExpoDirectoryCoordinatorModel();
        $row = $coords->where('EntryID', $id)->where('ContactID', $contactId)->first();
        if (!$row) return $this->response->setStatusCode(204);
        $wasPrimary = (int) ($row['IsPrimary'] ?? 0) === 1;
        $coords->where('EntryID', $id)->where('ContactID', $contactId)->delete();

        if ($wasPrimary) {
            $remaining = $coords->forEntry($id);
            if ($remaining) {
                $next = (int) $remaining[0]['ContactID'];
                $coords->where('EntryID', $id)->where('ContactID', $next)->set('IsPrimary', 1)->update();
                $this->syncLegacyContactColumns($id, $next);
            } else {
                (new ExpoDirectoryModel())->update($id, [
                    'ContactID' => null, 'ContactGivenName' => null,
                    'ContactFamilyName' => null, 'ContactEmail' => null,
                ]);
            }
        }
        return $this->response->setStatusCode(204);
    }

    // ------------------------------------------------------------ guest list

    /**
     * Resolves the guest list for an exhibitor entry.
     *   1. explicit CompanyGuestListsID (must still exist)
     *   2. case-insensitive match on companyguestlists.Company / .Name within
     *      the same EventID
     * When matched by name, the link is persisted and coordinators are synced
     * as managers.
     */
    private function resolveGuestList(array $row, bool $persist = true): ?array
    {
        $model   = new \App\Models\CompanyGuestListsModel();
        $entryId = (int) ($row['EntryID'] ?? 0);
        $eventId = ($row['EventID'] ?? null) === null ? 0 : (int) $row['EventID'];

        $explicit = (int) ($row['CompanyGuestListsID'] ?? 0);
        if ($explicit > 0) {
            $gl = $model->find($explicit);
            if ($gl) return $gl;
        }

        $name = trim((string) ($row['CompanyName'] ?? ''));
        if ($name === '' || $eventId <= 0) return null;

        try {
            $db    = $model->db;
            $lower = $db->escape(mb_strtolower($name));
            $gl    = $model->builder()
                ->where('EventID', $eventId)
                ->groupStart()
                    ->where('LOWER(Company) = ' . $lower, null, false)
                    ->orWhere('LOWER(Name) = ' . $lower, null, false)
                ->groupEnd()
                ->get()->getRowArray();
        } catch (\Throwable $e) {
            log_message('error', '[expo] guest list resolution failed: ' . $e->getMessage());
            return null;
        }
        if (!$gl) return null;

        if ($persist && $entryId > 0) {
            $this->linkGuestList($entryId, (int) $gl['CompanyID']);
        }
        return $gl;
    }

    /** Persists the link and syncs coordinators as guest-list managers. */
    private function linkGuestList(int $entryId, int $companyGuestListsId): void
    {
        try {
            (new ExpoDirectoryModel())->update($entryId, ['CompanyGuestListsID' => $companyGuestListsId]);
        } catch (\Throwable $e) {
            log_message('error', '[expo] guest list link failed: ' . $e->getMessage());
            return;
        }
        try {
            $contactIds = array_map(
                fn($c) => (int) $c['ContactID'],
                (new ExpoDirectoryCoordinatorModel())->forEntry($entryId)
            );
            if ($contactIds) {
                (new \App\Libraries\GuestListManagerSync())
                    ->syncContacts($companyGuestListsId, $contactIds, $this->actorId());
            }
        } catch (\Throwable $e) {
            log_message('error', '[expo] coordinator manager sync failed: ' . $e->getMessage());
        }
    }

    private function guestListPayload(array $row): array
    {
        $eventId = ($row['EventID'] ?? null) === null ? 0 : (int) $row['EventID'];
        $event   = $eventId > 0 ? ($this->eventsById([$eventId])[$eventId] ?? null) : null;
        $gl      = $this->resolveGuestList($row);
        return [
            'guest_list_enabled' => (int) ($event['GuestListEnabled'] ?? 0) === 1,
            'event_id'           => $eventId ?: null,
            'guest_list'         => $gl ? [
                'id'      => (int) $gl['CompanyID'],
                'name'    => $gl['Name'] ?? null,
                'company' => $gl['Company'] ?? null,
            ] : null,
        ];
    }

    /** GET /api/v1/expo-directory/{id}/guest-list */
    public function guestList(int $id)
    {
        [$userId, $privileged, $contactId] = $this->actorContext();
        $row = (new ExpoDirectoryModel())->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');
        if ($userId !== null && !$privileged
            && !(new ExpoDirectoryCoordinatorModel())->isCoordinator($contactId, $id)) {
            return $this->jsonError(403, 'forbidden');
        }
        return $this->response->setJSON(['data' => $this->guestListPayload($row)]);
    }

    /**
     * POST /api/v1/expo-directory/{id}/guest-list
     * Creates a guest list for this exhibitor's company/event (admin or expo
     * module only) and syncs the coordinators as managers.
     */
    public function createGuestList(int $id)
    {
        [$userId, $privileged] = $this->actorContext();
        if ($userId !== null && !$privileged) return $this->jsonError(403, 'forbidden');

        $row = (new ExpoDirectoryModel())->find($id);
        if (!$row) return $this->jsonError(404, 'not_found');

        $existing = $this->resolveGuestList($row);
        if ($existing) {
            return $this->response->setJSON(['data' => $this->guestListPayload((new ExpoDirectoryModel())->find($id))]);
        }

        $eventId = ($row['EventID'] ?? null) === null ? 0 : (int) $row['EventID'];
        if ($eventId <= 0) return $this->jsonError(422, 'entry_has_no_event');
        $event = $this->eventsById([$eventId])[$eventId] ?? null;
        if (!$event) return $this->jsonError(422, 'unknown_event');
        if ((int) ($event['GuestListEnabled'] ?? 0) !== 1) return $this->jsonError(422, 'guest_lists_disabled');

        $companyName = trim((string) ($row['CompanyName'] ?? ''));
        if ($companyName === '') return $this->jsonError(422, 'company_name_required');

        $model = new \App\Models\CompanyGuestListsModel();
        $insert = [
            'EventID'       => $eventId,
            'Year'          => (int) $event['Year'],
            'EventYear'     => trim((string) ($event['Name'] ?? '')) . (int) $event['Year'],
            'Name'          => $this->uniqueGuestListCode($companyName, $eventId),
            'Company'       => mb_substr($companyName, 0, 50),
            'SecretKey'     => substr(bin2hex(random_bytes(8)), 0, 10),
            'InviteCount'   => 0,
            'EmployeeCount' => (int) ($row['StaffQuantity'] ?? 0),
            'BanquetCount'  => 0,
            'GolfCount'     => 0,
            'StaffID'       => (int) ($row['ContactID'] ?? 0),
            'FullConfToken'  => \App\Models\CompanyGuestListsModel::newToken(),
            'ExhibitorToken' => \App\Models\CompanyGuestListsModel::newToken(),
        ];
        try {
            $newId = (int) $model->insert($insert, true);
        } catch (\Throwable $e) {
            log_message('error', '[expo] guest list create failed: ' . $e->getMessage());
            return $this->jsonError(422, 'db_insert_failed', ['message' => $e->getMessage()]);
        }
        if ($newId <= 0) return $this->jsonError(422, 'insert_failed');

        $this->linkGuestList($id, $newId);

        return $this->response->setStatusCode(201)->setJSON([
            'data' => $this->guestListPayload((new ExpoDirectoryModel())->find($id)),
        ]);
    }

    /** Short legacy code for companyguestlists.Name, unique within the event. */
    private function uniqueGuestListCode(string $companyName, int $eventId): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $companyName) ?? '');
        if ($base === '') $base = 'EXPO';
        $base = substr($base, 0, 12);
        $model = new \App\Models\CompanyGuestListsModel();
        $candidate = $base;
        $n = 1;
        while ($model->where('EventID', $eventId)->where('Name', $candidate)->first()) {
            $n++;
            $candidate = substr($base, 0, 10) . $n;
            if ($n > 50) { $candidate = substr($base, 0, 8) . strtoupper(bin2hex(random_bytes(2))); break; }
        }
        return $candidate;
    }


    /** Mirrors the primary coordinator into the legacy Contact* columns. */
    private function syncLegacyContactColumns(int $entryId, int $contactId): void
    {
        $ct = $this->contactsById([$contactId])[$contactId] ?? null;
        (new ExpoDirectoryModel())->update($entryId, [
            'ContactID'         => $contactId,
            'ContactGivenName'  => $ct['GivenName'] ?? null,
            'ContactFamilyName' => $ct['FamilyName'] ?? null,
            'ContactEmail'      => $ct['Email'] ?? null,
        ]);
    }

    /** Drops keys that don't exist on the legacy table (schemas vary by year). */
    private function filterExisting(array $data, string $table): array
    {
        try {
            $cols = db_connect('registration')->getFieldNames($table);
        } catch (\Throwable $e) {
            return $data;
        }
        return array_intersect_key($data, array_flip($cols));
    }
}
