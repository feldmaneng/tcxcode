<?php
namespace App\Controllers\Api\V1;

use App\Models\CompanyGuestListsManagerModel;
use App\Models\CompanyGuestListsModel;
use App\Models\ContactModel;
use App\Models\EventGuestModel;
use App\Models\EventModel;
use App\Models\UserModel;

/**
 * Public guest registration — accessed via a unique token, no acting user.
 *
 * The endpoints sit behind the HMAC service-key filter so only the TanStack
 * server can call them. The token itself is the only credential.
 */
class PublicGuestRegistrationController extends BaseApiController
{
    private const KIND_FULL_CONF = 'full-conference';
    private const KIND_EXHIBITOR = 'exhibitor-staff';
    private const KINDS = [self::KIND_FULL_CONF, self::KIND_EXHIBITOR];

    private const INPUT_FIELD_MAP = [
        'given_name'  => 'GivenName',
        'family_name' => 'FamilyName',
        'native_name' => 'NativeName',
        'email'       => 'Email',
        'company'     => 'Company',
        'cn_company'  => 'CN_Company',
        'title'       => 'Title',
        'mobile'      => 'Mobile',
        'wechat_id'   => 'WeChatID',
        'kakao_id'    => 'KakaoID',
    ];

    private const OUTPUT_FIELD_MAP = [
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
    ];

    private function guestDbToApi(array $row): array
    {
        $out = [];
        foreach (self::OUTPUT_FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
        }
        if (array_key_exists('Type', $row)) {
            $out['guest_type'] = EventGuestModel::normalizeType($row['Type']);
        }
        if (array_key_exists('BanquetCompanyID', $row)) {
            $out['banquet'] = ((int) $row['BanquetCompanyID']) > 0 ? 1 : 0;
        }
        $out['deleted'] = !empty($row['DeletedAt']) ? 1 : 0;
        foreach (['id', 'company_guest_lists_id', 'banquet', 'added_by', 'updated_by', 'deleted_by', 'related'] as $k) {
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
            if (!isset(self::INPUT_FIELD_MAP[$k])) continue;
            $out[self::INPUT_FIELD_MAP[$k]] = $v;
        }
        return $out;
    }

    private function clientIp(): string
    {
        return substr((string) $this->request->getIPAddress(), 0, 45);
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function normalizeGuestText($value): string
    {
        $value = strtolower(trim((string) $value));
        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function loadCompany(string $token, string $kind): ?array
    {
        $model = new CompanyGuestListsModel();
        $col = $kind === self::KIND_EXHIBITOR ? 'ExhibitorToken' : 'FullConfToken';
        $row = $model->where($col, $token)->first();
        return $row ?: null;
    }

    /**
     * Resolves the event behind a public registration token.
     *
     * Several events can share the same Year (e.g. Mesa / China / Korea 2026),
     * so a bare Year lookup can return the wrong row and, with it, the wrong
     * guest-form language toggles. Order of preference:
     *   1. explicit event_id from the caller (link generated with ?e=)
     *   2. companyguestlists.EventID recorded by the internal guest-list page
     *   3. the year's guest-list-enabled event, then the first match
     */
    private function loadEvent(int $year, ?int $eventId = null, ?array $company = null): ?array
    {
        $model = new EventModel();

        foreach ([$eventId, isset($company['EventID']) ? (int) $company['EventID'] : 0] as $candidate) {
            $candidate = (int) $candidate;
            if ($candidate <= 0) continue;
            $row = $model->where('EventID', $candidate)->first();
            // Year is only a sanity check; a recorded EventID wins when it matches.
            if ($row && ($year <= 0 || (int) ($row['Year'] ?? 0) === $year)) return $row;
        }

        if ($year <= 0) return null;

        $row = $model->where('Year', $year)->where('GuestListEnabled', 1)->first();
        if ($row) return $row;

        return $model->where('Year', $year)->first() ?: null;
    }


    /** Optional ?event_id= disambiguator sent by the public registration page. */
    private function requestedEventId(): ?int
    {
        $raw = $this->request->getGet('event_id');
        $id  = (int) $raw;
        return $id > 0 ? $id : null;
    }

    private function liveCounts(int $companyGuestListsId): array
    {
        return (new EventGuestModel())->countsForCompany($companyGuestListsId);
    }

    private function statusFor(string $kind, array $company, array $counts, ?array $manager = null): array
    {
        if ($kind === self::KIND_FULL_CONF) {
            $limit = $company['InviteCount'];
            if ($limit !== null && $limit !== '' && $counts['professional'] >= (int) ceil((int) $limit * 1.5)) {
                return ['status' => 'paused', 'message' => 'Registration for ' . ($company['Company'] ?? $company['Name']) . ' has been temporarily paused. Please contact Office@testconx.org for assistance.'];
            }
        } else {
            $limit = $company['EmployeeCount'];
            if ($limit !== null && $limit !== '' && $counts['exhibitor'] >= (int) $limit) {
                $name = $manager
                    ? trim(($manager['given_name'] ?? '') . ' ' . ($manager['family_name'] ?? ''))
                    : '';
                $mgr = $name !== '' && !empty($manager['email'])
                    ? $name . ' at ' . $manager['email']
                    : 'Office@testconx.org';
                return ['status' => 'exceeded', 'message' => 'Your company has exceeded their allotment of Exhibitor Staff badges. Please contact ' . $mgr . ' for assistance.'];
            }
        }
        return ['status' => 'open', 'message' => ''];
    }

    private function primaryManager(int $companyGuestListsId): ?array
    {
        $userIds = (new CompanyGuestListsManagerModel())->userIdsForCompany($companyGuestListsId);
        if (!$userIds) return null;
        $row = (new UserModel())->builder()
            ->select('UserID, UserName, GivenName, FamilyName, Email')
            ->whereIn('UserID', $userIds)
            ->limit(1)
            ->get()->getRowArray();
        return $row ? [
            'id'          => (int) $row['UserID'],
            'username'    => $row['UserName'],
            'given_name'  => $row['GivenName'],
            'family_name' => $row['FamilyName'],
            'email'       => $row['Email'] ?? null,
        ] : null;
    }

    /** Shapes event info for the public page. */
    private function shapeEvent(array $event): array
    {
        return [
            'name'             => $event['Name'] ?? null,
            'full_name'        => $event['FullName'] ?? null,
            'start_date'       => $event['StartDate'] ?? null,
            'end_date'         => $event['EndDate'] ?? null,
            'city'             => $event['City'] ?? null,
            'facility'         => $event['Facility'] ?? null,
            'facility_address' => $event['FacilityAddress'] ?? null,
            'guest_form_chinese' => (int) ($event['GuestFormChinese'] ?? 0),
            'guest_form_korean'  => (int) ($event['GuestFormKorean'] ?? 0),
        ];
    }

    /**
     * GET /api/v1/public/guest-reg/(:kind)/(:token)
     */
    public function getForm(string $kind, string $token)
    {
        if (!in_array($kind, self::KINDS, true)) {
            return $this->jsonError(404, 'not_found');
        }
        $company = $this->loadCompany($token, $kind);
        if (!$company) return $this->jsonError(404, 'not_found');

        $event = $this->loadEvent((int) ($company["Year"] ?? 0), $this->requestedEventId(), $company);
        if (!$event) return $this->jsonError(404, 'event_not_found');

        $manager = $this->primaryManager((int) $company['CompanyID']);
        $counts = $this->liveCounts((int) $company['CompanyID']);
        $status = $this->statusFor($kind, $company, $counts, $manager);

        return $this->response->setJSON([
            'status'       => $status['status'],
            'message'      => $status['message'],
            'company_name' => $company['Company'] ?? $company['Name'] ?? '',
            'event'        => $this->shapeEvent($event),
            'manager'      => $manager,
            'cc_primary'   => (int) ($company['CcPrimaryOnRegistration'] ?? 0),
            'counts'       => [
                'professional' => $counts['professional'],
                'exhibitor'    => $counts['exhibitor'],
            ],
            'limits'       => [
                'invite_count'   => $company['InviteCount'] !== null && $company['InviteCount'] !== '' ? (int) $company['InviteCount'] : null,
                'employee_count' => $company['EmployeeCount'] !== null && $company['EmployeeCount'] !== '' ? (int) $company['EmployeeCount'] : null,
            ],
        ]);
    }

    /**
     * POST /api/v1/public/guest-reg/(:kind)/(:token)
     */
    public function register(string $kind, string $token)
    {
        if (!in_array($kind, self::KINDS, true)) {
            return $this->jsonError(404, 'not_found');
        }
        $company = $this->loadCompany($token, $kind);
        if (!$company) return $this->jsonError(404, 'not_found');
        $companyId = (int) $company['CompanyID'];

        $event = $this->loadEvent((int) ($company["Year"] ?? 0), $this->requestedEventId(), $company);
        if (!$event) return $this->jsonError(404, 'event_not_found');

        $manager = $this->primaryManager($companyId);
        $counts = $this->liveCounts($companyId);
        $status = $this->statusFor($kind, $company, $counts, $manager);
        if ($status['status'] !== 'open') {
            return $this->jsonError(423, 'registration_closed', ['message' => $status['message']]);
        }

        $payload = (array) $this->request->getJSON(true);
        $row = $this->apiToDb($payload);

        $row['InvitedByCompanyID'] = $companyId;
        $row['EventYear'] = (string) ($company['EventYear'] ?? $company['Year'] ?? '');
        $row['Type'] = $kind === self::KIND_EXHIBITOR
            ? EventGuestModel::TYPE_EXHIBITOR
            : EventGuestModel::TYPE_PROFESSIONAL;
        $row['SignupType'] = 'URL';
        $row['Related'] = $kind === self::KIND_EXHIBITOR ? 1 : 0;
        $row['BanquetCompanyID'] = null;
        $row['AddedBy'] = 0;
        $row['UpdatedBy'] = 0;
        $row['AddedIP'] = $this->clientIp();
        $row['UpdatedIP'] = $row['AddedIP'];
        $row['DeletedAt'] = null;

        if (array_key_exists('Email', $row)) {
            $row['Email'] = $this->normalizeEmail((string) ($row['Email'] ?? ''));
        }

        // Validation
        $errors = $this->validateRequired($row);
        if ($errors !== []) return $this->jsonError(422, 'validation_failed', $errors);

        // Duplicate check across the event year
        if ($this->duplicateExists($row, $companyId)) {
            return $this->jsonError(409, 'already_attending', ['message' => 'You are already registered for this event.']);
        }

        // Contact match by email
        $contactId = $this->matchContactByEmail($row['Email'] ?? '');
        if ($contactId !== null) $row['ContactID'] = $contactId;

        $model = new EventGuestModel();
        try {
            $id = $model->insert($row, true);
        } catch (\Throwable $e) {
            log_message('error', 'public guest registration insert failed: ' . $e->getMessage());
            return $this->jsonError(422, 'db_insert_failed', ['message' => $e->getMessage()]);
        }
        if (!$id) return $this->jsonError(422, 'insert_failed', $model->errors());

        return $this->response->setStatusCode(201)
            ->setJSON(['data' => $this->guestDbToApi($model->find($id))]);
    }

    private function validateRequired(array $row): array
    {
        $errors = [];
        $val = fn(string $k) => trim((string) ($row[$k] ?? ''));

        if ($val('Email') === '') $errors['email'] = 'Email is required';
        elseif (!filter_var($row['Email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address';
        if ($val('Title') === '') $errors['title'] = 'Job title is required';
        if ($val('Mobile') === '') $errors['mobile'] = 'Mobile phone is required';
        if ($val('Company') === '' && $val('CN_Company') === '') $errors['company'] = 'Company is required';
        if ($val('NativeName') === '' && ($val('GivenName') === '' || $val('FamilyName') === '')) {
            $errors['name'] = 'Name is required';
        }
        return $errors;
    }

    private function duplicateExists(array $row, int $companyGuestListsId): bool
    {
        $eventYear = (string) ($row['EventYear'] ?? '');
        $emailNorm = $this->normalizeGuestText($row['Email'] ?? '');
        $nameKey = $emailNorm === '' ? $this->guestNameKey($row) : '';
        if ($emailNorm === '' && $nameKey === '') return false;

        $q = (new EventGuestModel())->builder();
        $q->where('DeletedAt', null);
        if ($eventYear !== '') $q->where('EventYear', $eventYear);
        else $q->where('InvitedByCompanyID', $companyGuestListsId);

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

    private function guestNameKey(array $row): string
    {
        $given = $this->normalizeGuestText($row['GivenName'] ?? '');
        $family = $this->normalizeGuestText($row['FamilyName'] ?? '');
        if ($given === '' && $family === '') return '';
        return $given . '|' . $family;
    }

    private function matchContactByEmail(string $email): ?int
    {
        $email = $this->normalizeGuestText($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return null;
        $row = (new ContactModel())
            ->where('LOWER(TRIM(Email))', $email)
            ->where('Active', 1)
            ->limit(1)
            ->get()->getRowArray();
        return $row ? (int) $row['ContactID'] : null;
    }

    public function options()
    {
        return $this->response->setStatusCode(204)
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
