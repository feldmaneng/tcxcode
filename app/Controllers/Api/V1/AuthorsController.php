<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\EmailNormalizer;
use App\Libraries\WpLookupClient;
use App\Models\AdminAuditLogModel;
use App\Models\AuthorModel;
use App\Models\ContactModel;
use Config\Database;

class AuthorsController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'              => 'AuthorID',
        'author_number'   => 'AuthorNumber',
        'presenter'       => 'Presenter',
        'contact_id'      => 'ContactID',
        'given_name'      => 'GivenName',
        'family_name'     => 'FamilyName',
        'company'         => 'Company',
        'company_id'      => 'CompanyID',
        'presentation_id' => 'PresentationID',
    ];

    private const READONLY_API_FIELDS = ['id'];
    private const FILTERABLE = ['presentation_id', 'contact_id', 'company_id', 'presenter'];
    private const SORTABLE   = ['id', 'author_number', 'family_name'];

    public static function dbToApi(array $row): array
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
            if (in_array($k, self::READONLY_API_FIELDS, true)) continue;
            if (!isset(self::FIELD_MAP[$k])) continue;
            $out[self::FIELD_MAP[$k]] = $v;
        }
        return $out;
    }

    public function index()
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $req     = $this->request;
        $page    = max(1, (int) $req->getGet('page') ?: 1);
        $perPage = max(1, min(200, (int) ($req->getGet('per_page') ?: 50)));
        $sort    = (string) ($req->getGet('sort') ?: 'author_number');

        $builder = (new AuthorModel())->builder();
        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
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

        return $this->response->setJSON([
            'data' => array_map(fn($r) => self::dbToApi($r), $rows),
            'pagination' => [
                'page' => $page, 'per_page' => $perPage,
                'total' => $total, 'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function show($id = null)
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $row = (new AuthorModel())->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');
        return $this->response->setJSON(['data' => self::dbToApi($row)]);
    }

    /** GET /api/v1/presentations/{id}/authors */
    public function byPresentation($pid = null)
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $rows = (new AuthorModel())->builder()
            ->where('PresentationID', (int) $pid)
            ->orderBy('AuthorNumber', 'ASC')
            ->orderBy('AuthorID', 'ASC')
            ->get()->getResultArray();
        return $this->response->setJSON(['data' => array_map(fn($r) => self::dbToApi($r), $rows)]);
    }

    public function create()
    {
        if ($deny = $this->requireModule(['crm'])) return $deny;
        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        if (empty($dbRow['PresentationID'])) {
            return $this->jsonError(422, 'validation_failed', ['presentation_id' => 'required']);
        }
        // If contact_id supplied without snapshot fields, snapshot from contacts.
        if (!empty($dbRow['ContactID']) && (empty($dbRow['GivenName']) && empty($dbRow['FamilyName']))) {
            self::snapshotFromContact($dbRow, (int) $dbRow['ContactID']);
        }
        $model = new AuthorModel();
        $id = $model->insert($dbRow, true);
        if (!$id) return $this->jsonError(500, 'insert_failed', $model->errors());
        return $this->response->setStatusCode(201)->setJSON(['data' => self::dbToApi($model->find((int) $id))]);
    }

    public function update($id = null)
    {
        if ($deny = $this->requireModule(['crm'])) return $deny;
        $model = new AuthorModel();
        if (!$model->find((int) $id)) return $this->jsonError(404, 'not_found');
        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        if (empty($dbRow)) return $this->jsonError(400, 'no_updatable_fields');
        if (!$model->update((int) $id, $dbRow)) {
            return $this->jsonError(500, 'update_failed', $model->errors());
        }
        return $this->response->setJSON(['data' => self::dbToApi($model->find((int) $id))]);
    }

    public function delete($id = null)
    {
        if ($deny = $this->requireModule(['crm'])) return $deny;
        $model = new AuthorModel();
        if (!$model->find((int) $id)) return $this->jsonError(404, 'not_found');
        if (!$model->delete((int) $id)) return $this->jsonError(500, 'delete_failed', $model->errors());
        return $this->response->setJSON(['data' => ['id' => (int) $id, 'deleted' => true]]);
    }

    /**
     * Snapshot contact's name + company into the author row.
     * Used by both this controller and PresentationsController.
     */
    public static function snapshotFromContact(array &$dbRow, int $contactId): void
    {
        $contact = Database::connect()->table('contacts')
            ->select('GivenName, FamilyName, Company, CompanyID')
            ->where('ContactID', $contactId)
            ->get()->getRowArray();
        if (!$contact) return;
        $dbRow['GivenName']  = $contact['GivenName'];
        $dbRow['FamilyName'] = $contact['FamilyName'];
        $dbRow['Company']    = $contact['Company'];
        $dbRow['CompanyID']  = $contact['CompanyID'] !== null ? (int) $contact['CompanyID'] : null;
        $dbRow['CompanyID']  = $contact['CompanyID'] !== null ? (int) $contact['CompanyID'] : null;
    }

    /**
     * POST /api/v1/authors/wordpress-status
     * Body: { contact_ids: int[] }  (max 50)
     *
     * For each contact returns the WP-account status so the author editor
     * can flag missing WordPress accounts:
     *
     *   - linked              contacts.WordPressID is already set
     *   - no_email            Email column is blank
     *   - invalid_email       Email is set but unparseable
     *   - lookup_unavailable  WP plugin is offline / not configured
     *   - no_wp_account       WP says no user with that email
     *   - auto_linked         WP found an exact match — we wrote
     *                         contacts.WordPressID = wp_user_id
     *
     * Auto-link is intentional: WP's get_user_by('email') is case-insensitive
     * (MySQL utf8mb4_*_ci), and we normalize the local string first, so an
     * exact case-folded match is safe to persist.
     */
    public function wordpressStatus()
    {
        if ($deny = $this->requireModule(['crm', 'author-portal'])) return $deny;
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');

        $raw = $this->request->getJsonVar('contact_ids');
        if (!is_array($raw)) return $this->jsonError(400, 'contact_ids_array_required');

        $ids = [];
        foreach ($raw as $v) {
            $n = (int) $v;
            if ($n > 0) $ids[$n] = true;
        }
        $ids = array_keys($ids);
        if (count($ids) === 0) return $this->respond(['data' => []]);
        if (count($ids) > 50) return $this->jsonError(400, 'too_many_contact_ids');

        $contactModel = new ContactModel();
        $rows = $contactModel
            ->select('ContactID, GivenName, FamilyName, Email, WordPressID')
            ->whereIn('ContactID', $ids)
            ->findAll();

        $wp = new WpLookupClient();
        $configured = $wp->isConfigured();
        $audit = new AdminAuditLogModel();

        $out = [];
        foreach ($rows as $r) {
            $contactId = (int) $r['ContactID'];
            $wpId = $r['WordPressID'] !== null && $r['WordPressID'] !== '' ? (int) $r['WordPressID'] : 0;
            if ($wpId > 0) {
                $out[] = [
                    'contact_id'  => $contactId,
                    'status'      => 'linked',
                    'wp_user_id'  => $wpId,
                ];
                continue;
            }

            $rawEmail = (string) ($r['Email'] ?? '');
            if (trim($rawEmail) === '') {
                $out[] = ['contact_id' => $contactId, 'status' => 'no_email'];
                continue;
            }
            $email = EmailNormalizer::normalize($rawEmail);
            if ($email === null) {
                $out[] = [
                    'contact_id' => $contactId,
                    'status'     => 'invalid_email',
                    'raw_email'  => $rawEmail,
                ];
                continue;
            }

            if (!$configured) {
                $out[] = [
                    'contact_id'       => $contactId,
                    'status'           => 'lookup_unavailable',
                    'normalized_email' => $email,
                ];
                continue;
            }

            $res = $wp->lookupByEmailWithStatus($email);
            $status = $res['status'];
            if ($status === 'found' && is_array($res['user'])) {
                $wpUserId = (int) ($res['user']['wp_user_id'] ?? 0);
                $userLogin = (string) ($res['user']['user_login'] ?? '');
                if ($wpUserId > 0) {
                    // Persist the numeric WP user id on the contact (same shape
                    // the contacts WordPressLink UI writes — wordpress_id is a
                    // non-negative integer).
                    $contactModel->update($contactId, ['WordPressID' => $wpUserId]);
                    $audit->log(
                        $actorId,
                        'contact.wordpress_id.auto_link',
                        'contact',
                        (string) $contactId,
                        [
                            'wp_user_id'       => $wpUserId,
                            'user_login'       => $userLogin,
                            'normalized_email' => $email,
                        ],
                        $this->request->getIPAddress()
                    );
                    $out[] = [
                        'contact_id'       => $contactId,
                        'status'           => 'auto_linked',
                        'wp_user_id'       => $wpUserId,
                        'user_login'       => $userLogin,
                        'normalized_email' => $email,
                    ];
                    continue;
                }
                // Fallthrough — found but malformed payload, treat as no match.
                $status = 'not_found';
            }

            if ($status === 'not_found') {
                $out[] = [
                    'contact_id'       => $contactId,
                    'status'           => 'no_wp_account',
                    'normalized_email' => $email,
                ];
                continue;
            }

            // unavailable / unconfigured
            $out[] = [
                'contact_id'       => $contactId,
                'status'           => 'lookup_unavailable',
                'normalized_email' => $email,
            ];
        }

        // Preserve contact_ids that had no contact row (deleted etc.) as
        // explicit not-found entries so the UI can render a stable list.
        $seen = [];
        foreach ($out as $o) $seen[$o['contact_id']] = true;
        foreach ($ids as $id) {
            if (!isset($seen[$id])) {
                $out[] = ['contact_id' => $id, 'status' => 'contact_missing'];
            }
        }

        return $this->respond(['data' => $out]);
    }
}
