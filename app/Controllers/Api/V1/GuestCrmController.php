<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\CompanyGuestListsModel;
use App\Models\ContactModel;
use App\Models\EventGuestModel;
use App\Models\EventModel;
use App\Models\UserModuleModel;

/**
 * Guest list ↔ CRM reconciliation (admins + the event's Event Manager only).
 *
 * GET  /api/v1/guest-crm/event/{eventId}   → every live guest with match status, CRM candidates
 * POST /api/v1/guest-crm/{guestId}/apply   → {contact_id|create, fields:{api_key:value}, email_mode}
 * POST /api/v1/guest-crm/{guestId}/skip    → mark reviewed, no CRM change
 *
 * Matching: stored ContactID → email → mobile (last 8+ digits) → name suggestions
 * (Given+Family either order, or NativeName). After review a snapshot of the
 * guest fields is stored; later differences flag "changed since review".
 */
class GuestCrmController extends BaseApiController
{
    /** api key => [guests column, contacts column] */
    private const FIELDS = [
        'given_name'  => ['GivenName', 'GivenName'],
        'family_name' => ['FamilyName', 'FamilyName'],
        'native_name' => ['NativeName', 'NativeName'],
        'email'       => ['Email', 'Email'],
        'company'     => ['Company', 'Company'],
        'cn_company'  => ['CN_Company', 'CN_Company'],
        'title'       => ['Title', 'Title'],
        'mobile'      => ['Mobile', 'Mobile'],
    ];

    private function authorize(int $eventId)
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');
        $isAdmin = (new UserModuleModel())->userHasModule($actorId, 'admin');
        if (!$isAdmin && !(new EventModel())->isEventManagerForEvent((int) $actorId, $eventId)) {
            return $this->jsonError(403, 'forbidden');
        }
        return null;
    }

    private static function digits(?string $s): string
    {
        return preg_replace('/\D+/', '', (string) $s) ?? '';
    }

    private static function norm(?string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $s) ?? ''));
    }

    private function guestValues(array $g): array
    {
        $o = [];
        foreach (self::FIELDS as $k => [$gc]) $o[$k] = trim((string) ($g[$gc] ?? ''));
        return $o;
    }

    private function contactValues(array $c): array
    {
        $o = ['id' => (int) $c['ContactID']];
        foreach (self::FIELDS as $k => [, $cc]) $o[$k] = trim((string) ($c[$cc] ?? ''));
        // CRM-only fields (not on guests)
        $o['nickname']     = trim((string) ($c['Nickname'] ?? ''));
        $o['company_id']   = !empty($c['CompanyID']) ? (int) $c['CompanyID'] : null;
        $o['company_name'] = null;
        if ($o['company_id']) {
            static $names = [];
            if (!array_key_exists($o['company_id'], $names)) {
                $r = $this->db()->table('company')->select('Name')->where('CompanyID', $o['company_id'])->get()->getRowArray();
                $names[$o['company_id']] = $r ? (string) $r['Name'] : null;
            }
            $o['company_name'] = $names[$o['company_id']];
        }
        return $o;
    }

    private function db()
    {
        return \Config\Database::connect(); // default = bitswork_contac2 (contacts)
    }

    public function eventIndex(int $eventId)
    {
        if ($err = $this->authorize($eventId)) return $err;

        $lists = (new CompanyGuestListsModel())->where('EventID', $eventId)->where('DeletedAt', null)->findAll();
        if (!$lists) return $this->response->setJSON(['data' => []]);
        $names = [];
        foreach ($lists as $l) $names[(int) $l['CompanyID']] = (string) ($l['Company'] ?: $l['Name']);

        $guests = (new EventGuestModel())->whereIn('InvitedByCompanyID', array_keys($names))
            ->where('DeletedAt', null)
            ->orderBy('FamilyName', 'ASC')->orderBy('GivenName', 'ASC')->findAll();

        $db = $this->db();
        $cols = 'ContactID, Nickname, CompanyID, ' . implode(', ', array_unique(array_map(fn($f) => $f[1], self::FIELDS)));

        // Batch: linked IDs + emails
        $ids = array_values(array_filter(array_map(fn($g) => (int) ($g['ContactID'] ?? 0), $guests)));
        $byId = [];
        if ($ids) foreach ($db->table('contacts')->select($cols)->whereIn('ContactID', array_unique($ids))->get()->getResultArray() as $c) $byId[(int) $c['ContactID']] = $c;
        $emails = array_values(array_unique(array_filter(array_map(fn($g) => EventGuestModel::normalizeEmail($g['Email'] ?? ''), $guests))));
        $byEmail = [];
        foreach (array_chunk($emails, 500) as $chunk) {
            foreach ($db->table('contacts')->select($cols)->whereIn('LOWER(TRIM(Email))', $chunk)->get()->getResultArray() as $c) {
                $byEmail[EventGuestModel::normalizeEmail($c['Email'])][] = $c;
            }
        }

        $out = [];
        foreach ($guests as $g) {
            $gv = $this->guestValues($g);
            $status = 'none';
            $reason = null;
            $contact = null;
            $candidates = [];

            $cid = (int) ($g['ContactID'] ?? 0);
            $email = EventGuestModel::normalizeEmail($g['Email'] ?? '');
            if ($cid && isset($byId[$cid])) {
                $contact = $byId[$cid]; $status = 'linked'; $reason = 'Linked';
            } elseif ($email !== '' && !empty($byEmail[$email])) {
                $contact = $byEmail[$email][0]; $status = 'email'; $reason = 'Same email';
            } else {
                $mob = self::digits($g['Mobile'] ?? '');
                if (strlen($mob) >= 8) {
                    $tail = substr($mob, -8);
                    $rows = $db->table('contacts')->select($cols)
                        ->like("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(Mobile,' ',''),'-',''),'+',''),'(',''),')','')", $tail, 'before')
                        ->limit(5)->get()->getResultArray();
                    if ($rows) { $contact = $rows[0]; $status = 'mobile'; $reason = 'Same mobile number'; }
                }
                if (!$contact) {
                    $gn = trim((string) ($g['GivenName'] ?? '')); $fn = trim((string) ($g['FamilyName'] ?? ''));
                    $nn = trim((string) ($g['NativeName'] ?? ''));
                    if (($gn !== '' && $fn !== '') || $nn !== '') {
                        $q = $db->table('contacts')->select($cols)->groupStart();
                        if ($gn !== '' && $fn !== '') {
                            $q->groupStart()->where('GivenName', $gn)->where('FamilyName', $fn)->groupEnd()
                              ->orGroupStart()->where('GivenName', $fn)->where('FamilyName', $gn)->groupEnd();
                        }
                        if ($nn !== '') {
                            if ($gn !== '' && $fn !== '') $q->orWhere('NativeName', $nn); else $q->where('NativeName', $nn);
                        }
                        $rows = $q->groupEnd()->limit(20)->get()->getResultArray();
                        $gc = self::norm($g['Company'] ?? '');
                        usort($rows, function ($a, $b) use ($gc) {
                            $sa = ($gc !== '' && self::norm($a['Company']) === $gc) ? 1 : 0;
                            $sb = ($gc !== '' && self::norm($b['Company']) === $gc) ? 1 : 0;
                            return $sb <=> $sa;
                        });
                        foreach (array_slice($rows, 0, 5) as $r) $candidates[] = $this->contactValues($r);
                        if ($candidates) $status = 'suggested';
                    }
                }
            }

            $snapshot = json_decode((string) ($g['CrmReviewSnapshot'] ?? ''), true);
            $changed = [];
            if (is_array($snapshot)) {
                foreach ($gv as $k => $v) if (($snapshot[$k] ?? '') !== $v) $changed[] = $k;
            }

            $out[] = [
                'guest_id'        => (int) $g['GuestID'],
                'list_id'         => (int) $g['InvitedByCompanyID'],
                'list_name'       => $names[(int) $g['InvitedByCompanyID']] ?? '',
                'guest'           => $gv,
                'status'          => $status,
                'reason'          => $reason,
                'contact'         => $contact ? $this->contactValues($contact) : null,
                'candidates'      => $candidates,
                'reviewed_at'     => $g['CrmReviewedAt'] ?? null,
                'changed_fields'  => $changed,
            ];
        }
        return $this->response->setJSON(['data' => $out]);
    }

    private function loadGuest(int $guestId)
    {
        $g = (new EventGuestModel())->where('GuestID', $guestId)->where('DeletedAt', null)->first();
        if (!$g) return [null, $this->jsonError(404, 'not_found')];
        $list = (new CompanyGuestListsModel())->find((int) $g['InvitedByCompanyID']);
        if (!$list || !empty($list['DeletedAt'])) return [null, $this->jsonError(404, 'not_found')];
        if ($err = $this->authorize((int) ($list['EventID'] ?? 0))) return [null, $err];
        return [$g, null];
    }

    private function markReviewed(array $g, ?int $contactId): void
    {
        $upd = [
            'CrmReviewedAt'     => date('Y-m-d H:i:s'),
            'CrmReviewSnapshot' => json_encode($this->guestValues($g), JSON_UNESCAPED_UNICODE),
        ];
        if ($contactId) $upd['ContactID'] = $contactId;
        (new EventGuestModel())->builder()->where('GuestID', (int) $g['GuestID'])->update($upd);
    }

    public function apply(int $guestId)
    {
        [$g, $err] = $this->loadGuest($guestId);
        if ($err) return $err;
        $body = $this->request->getJSON(true) ?? [];
        $fields = is_array($body['fields'] ?? null) ? $body['fields'] : [];
        $create = !empty($body['create']);
        $contactId = (int) ($body['contact_id'] ?? 0);
        $emailMode = ($body['email_mode'] ?? 'replace') === 'keep' ? 'keep' : 'replace';

        $upd = [];
        foreach ($fields as $k => $v) {
            if (!isset(self::FIELDS[$k])) continue;
            $v = trim((string) $v);
            if (mb_strlen($v) > 255) return $this->jsonError(422, 'too_long', ['field' => $k]);
            $upd[self::FIELDS[$k][1]] = $k === 'email' ? EventGuestModel::normalizeEmail($v) : $v;
        }
        // CRM-only fields
        if (array_key_exists('nickname', $fields)) {
            $v = trim((string) $fields['nickname']);
            if (mb_strlen($v) > 255) return $this->jsonError(422, 'too_long', ['field' => 'nickname']);
            $upd['Nickname'] = $v;
        }
        if (array_key_exists('company_id', $fields)) {
            $cid = $fields['company_id'] === null || $fields['company_id'] === '' ? null : (int) $fields['company_id'];
            if ($cid) {
                $co = $this->db()->table('company')->select('CompanyID, StandardName')->where('CompanyID', $cid)->get()->getRowArray();
                if (!$co) return $this->jsonError(422, 'company_not_found');
                $std = $co['StandardName'];
                if ($std !== null && $std !== '' && (int) $std === 0) return $this->jsonError(422, 'company_not_standard');
            }
            $upd['CompanyID'] = $cid;
        }
        $contacts = new ContactModel();

        $dbUser = mb_substr((string) (ApiAuthContext::actingUsername() ?: 'system'), 0, 50);
        try {
            if ($create) {
                if (empty($upd['Email']) && empty($upd['FamilyName'])) return $this->jsonError(422, 'email_or_name_required');
                if (!empty($upd['Email']) && $this->db()->table('contacts')->where('LOWER(TRIM(Email))', $upd['Email'])->countAllResults() > 0) {
                    return $this->jsonError(409, 'email_exists');
                }
                $list  = (new CompanyGuestListsModel())->find((int) $g['InvitedByCompanyID']);
                $event = $list ? (new EventModel())->find((int) ($list['EventID'] ?? 0)) : null;
                $label = trim(((string) ($event['Name'] ?? '')) . ' ' . ((string) ($event['Year'] ?? '')));
                $upd['Source'] = mb_substr($label !== '' ? $label . ' Guest List' : 'Guest List', 0, 50);
                $upd['Active'] = 1;
                $upd['DBuser'] = $dbUser;
                // Query builder so Active/DBuser aren't filtered out by model allowedFields.
                $this->db()->table('contacts')->insert($upd);
                $contactId = (int) $this->db()->insertID();
                if (!$contactId) return $this->jsonError(500, 'create_failed');
            } else {
                $contact = $contactId ? $contacts->find($contactId) : null;
                if (!$contact) return $this->jsonError(404, 'contact_not_found');
                $guestEmail = EventGuestModel::normalizeEmail($g['Email'] ?? '');
                if ($emailMode === 'keep') {
                    unset($upd['Email']);
                    if ($guestEmail !== '' && $guestEmail !== EventGuestModel::normalizeEmail($contact['Email'] ?? '')
                        && stripos((string) ($contact['Notes'] ?? ''), $guestEmail) === false) {
                        $upd['Notes'] = trim(((string) ($contact['Notes'] ?? '')) . "\nAlt email (guest list " . date('Y-m-d') . "): " . $guestEmail);
                    }
                } elseif (!empty($upd['Email']) && $upd['Email'] !== EventGuestModel::normalizeEmail($contact['Email'] ?? '')) {
                    $dup = $this->db()->table('contacts')->where('LOWER(TRIM(Email))', $upd['Email'])->where('ContactID !=', $contactId)->countAllResults();
                    if ($dup > 0) return $this->jsonError(409, 'email_exists');
                }
                // Use the query builder so a CompanyID-only (or NULL) change is never dropped by model data filtering.
                if ($upd) {
                    $upd['DBuser'] = $dbUser;
                    $this->db()->table('contacts')->where('ContactID', $contactId)->update($upd);
                }
            }
            $this->markReviewed($g, $contactId);
        } catch (\Throwable $e) {
            log_message('error', 'GuestCrm apply failed: ' . $e->getMessage());
            return $this->jsonError(500, 'update_failed', ['detail' => $e->getMessage()]);
        }
        return $this->response->setJSON(['ok' => true, 'contact_id' => $contactId]);
    }

    public function skip(int $guestId)
    {
        [$g, $err] = $this->loadGuest($guestId);
        if ($err) return $err;
        $this->markReviewed($g, null);
        return $this->response->setJSON(['ok' => true]);
    }

    public function options()
    {
        return $this->response->setStatusCode(204);
    }
}
