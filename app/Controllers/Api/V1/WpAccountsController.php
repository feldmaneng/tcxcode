<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\WpLookupClient;
use App\Models\AttendanceModel;
use App\Models\CompanyGuestListsModel;
use App\Models\ContactModel;
use App\Models\EventGuestModel;
use App\Models\EventModel;
use App\Models\UserModuleModel;

/**
 * Guest / attendee TestConX.org accounts (admins only).
 *
 *   GET  /api/v1/wp-accounts/{eventId}/settings      settings + templates (null = use app default)
 *   PUT  /api/v1/wp-accounts/{eventId}/settings
 *   GET  /api/v1/wp-accounts/{eventId}/people?source=guests|attendance&show_only=1
 *   POST /api/v1/wp-accounts/{eventId}/process        {mode: check|apply, level, ccap, send_existing, items:[{key}]}
 *   POST /api/v1/wp-accounts/{eventId}/email-result   {log_id, status, message_id?, error?}
 *   POST /api/v1/wp-accounts/{eventId}/resend/{logId} returns data to re-send (fresh link for new accounts)
 *   GET  /api/v1/wp-accounts/{eventId}/log
 *   POST /api/v1/wp-accounts/email-failed             service call from the Mailgun webhook {message_id, reason}
 *
 * Matching: contacts.WordPressID first, then WordPress email. A match by email
 * stores the WordPress ID on the contact. Level is only raised; ccap is added.
 * WpAccountProvision (unique EventID + WpUserID) is the single record of which
 * email went to whom, so re-runs from guest lists and then attendance never
 * email the same person twice.
 */
class WpAccountsController extends BaseApiController
{
    private function authorize(int $eventId)
    {
        $actorId = (int) ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');
        if (!(new UserModuleModel())->userHasModule($actorId, 'admin')) return $this->jsonError(403, 'admin_required');
        $event = (new EventModel())->find($eventId);
        if (!$event) return $this->jsonError(404, 'event_not_found');
        return ['actorId' => $actorId, 'event' => $event];
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    public function settings(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $row = $this->db()->table('WpAccountSettings')->where('EventID', $eventId)->get()->getRowArray();
        $wp = new WpLookupClient();
        return $this->response->setJSON(['data' => [
            'level'            => isset($row['Level']) ? (int) $row['Level'] : null,
            'ccap'             => $row['Ccap'] ?? null,
            'program_url'      => $row['ProgramUrl'] ?? null,
            'new_subject'      => $row['NewSubject'] ?? null,
            'new_body'         => $row['NewBody'] ?? null,
            'existing_subject' => $row['ExistingSubject'] ?? null,
            'existing_body'    => $row['ExistingBody'] ?? null,
            'wp_base_url'      => $wp->isConfigured() ? $wp->baseUrl() : null,
        ]]);
    }

    public function saveSettings(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $p = (array) $this->request->getJSON(true);
        $level = isset($p['level']) ? (int) $p['level'] : null;
        if ($level !== null && ($level < 1 || $level > 4)) return $this->jsonError(422, 'validation_failed', ['level' => 'Level must be 1–4']);
        $data = [
            'EventID'         => $eventId,
            'Level'           => $level,
            'Ccap'            => self::cleanCcap((string) ($p['ccap'] ?? '')) ?: null,
            'ProgramUrl'      => substr(trim((string) ($p['program_url'] ?? '')), 0, 255) ?: null,
            'NewSubject'      => substr(trim((string) ($p['new_subject'] ?? '')), 0, 200) ?: null,
            'NewBody'         => (string) ($p['new_body'] ?? '') ?: null,
            'ExistingSubject' => substr(trim((string) ($p['existing_subject'] ?? '')), 0, 200) ?: null,
            'ExistingBody'    => (string) ($p['existing_body'] ?? '') ?: null,
            'UpdatedBy'       => $auth['actorId'],
            'UpdatedAt'       => date('Y-m-d H:i:s'),
        ];
        $this->db()->table('WpAccountSettings')->replace($data);
        return $this->settings($eventId);
    }

    /** Tutorial registration: anything non-null and non-zero (INT on Attendance, VARCHAR on guests). */
    public static function hasTutorial($v): bool
    {
        if ($v === null) return false;
        $t = trim((string) $v);
        return $t !== '' && !(is_numeric($t) && (float) $t == 0.0);
    }

    /** Capabilities for one person: base cc_cap, plus "t" + base (tANN) when they took a tutorial. */
    public static function ccapsFor(string $ccap, bool $tutorial): array
    {
        if ($ccap === '') return [];
        return $tutorial ? [$ccap, 't' . $ccap] : [$ccap];
    }

    public static function cleanCcap(string $c): string
    {
        return substr(preg_replace('/[^a-z0-9_]/', '', strtolower(trim($c))), 0, 40);
    }

    /** Given-Family, ASCII only; falls back to the email local part. */
    public static function suggestUsername(?string $given, ?string $family, string $email): string
    {
        $clean = function (?string $s): string {
            $s = trim((string) $s);
            if ($s === '') return '';
            if (function_exists('transliterator_transliterate')) {
                $t = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
                if (is_string($t)) $s = $t;
            }
            $s = preg_replace('/[^A-Za-z0-9]+/', '', $s);
            return (string) $s;
        };
        $name = implode('-', array_filter([$clean($given), $clean($family)]));
        if ($name === '') $name = preg_replace('/[^A-Za-z0-9._-]+/', '', (string) strstr($email, '@', true));
        return substr($name !== '' ? $name : 'guest', 0, 50);
    }

    /** People for this event, deduplicated by ContactID then email. */
    private function people(int $eventId, array $event, string $source, bool $showOnly): array
    {
        $out = [];
        if ($source === 'attendance') {
            $q = (new AttendanceModel())->where('Event', (string) $event['Name'])->where('Year', (int) $event['Year']);
            if ($showOnly) $q->where('Show', 1);
            foreach ($q->findAll() as $a) {
                $out[] = ['contact_id' => (int) ($a['ContactID'] ?? 0), 'guest_id' => (int) ($a['EventRegID'] ?? 0) ?: null,
                          'email' => (string) ($a['Email'] ?? ''), 'given' => null, 'family' => null, 'type' => (string) ($a['Type'] ?? ''), 'tutorial' => self::hasTutorial($a['Tutorial'] ?? null)];
            }
        } else {
            $lists = (new CompanyGuestListsModel())->where('EventID', $eventId)->findAll();
            $ids = array_map(fn($l) => (int) $l['CompanyID'], $lists);
            $guests = $ids ? (new EventGuestModel())->whereIn('InvitedByCompanyID', $ids)->where('DeletedAt', null)->findAll() : [];
            foreach ($guests as $g) {
                $out[] = ['contact_id' => (int) ($g['ContactID'] ?? 0), 'guest_id' => (int) $g['GuestID'],
                          'email' => (string) ($g['Email'] ?? ''), 'given' => $g['GivenName'] ?? null, 'family' => $g['FamilyName'] ?? null,
                          'type' => (string) ($g['Type'] ?? ''), 'tutorial' => self::hasTutorial($g['Tutorial'] ?? null)];
            }
        }
        $cids = array_values(array_unique(array_filter(array_map(fn($p) => $p['contact_id'], $out))));
        $contacts = [];
        if ($cids) {
            foreach ((new ContactModel())->select('ContactID,GivenName,FamilyName,Email,WordPressID')->whereIn('ContactID', $cids)->findAll() as $c) {
                $contacts[(int) $c['ContactID']] = $c;
            }
        }
        $logs = [];
        foreach ($this->db()->table('WpAccountProvision')->where('EventID', $eventId)->get()->getResultArray() as $l) {
            if ((int) $l['ContactID'] > 0) $logs['c' . (int) $l['ContactID']] = $l;
            $logs['e' . strtolower((string) $l['Email'])] = $l;
        }

        $seen = []; $rows = [];
        foreach ($out as $p) {
            $c = $p['contact_id'] > 0 ? ($contacts[$p['contact_id']] ?? null) : null;
            $email = strtolower(trim((string) ($c['Email'] ?? '') ?: $p['email']));
            $given = (string) ($c['GivenName'] ?? '') ?: (string) $p['given'];
            $family = (string) ($c['FamilyName'] ?? '') ?: (string) $p['family'];
            $key = $c ? 'c' . $p['contact_id'] : ($email !== '' ? 'e' . $email : 'g' . (int) $p['guest_id']);
            $dup = $seen[$key] ?? ($email !== '' ? ($seen['e' . $email] ?? null) : null);
            if ($dup !== null) { if ($p['tutorial']) $rows[$dup]['tutorial'] = true; continue; }
            $seen[$key] = count($rows);
            if ($email !== '') $seen['e' . $email] = count($rows);
            $log = $logs[$key] ?? ($email !== '' ? ($logs['e' . $email] ?? null) : null);
            $rows[] = [
                'key'         => $key,
                'contact_id'  => $c ? $p['contact_id'] : null,
                'guest_id'    => $p['guest_id'],
                'reconciled'  => (bool) $c,
                'email'       => $email,
                'given_name'  => $given,
                'family_name' => $family,
                'type'        => $p['type'],
                'tutorial'    => (bool) $p['tutorial'],
                'wp_user_id'  => (int) ($c['WordPressID'] ?? 0) ?: null,
                'suggested_username' => self::suggestUsername($given, $family, $email),
                'log'         => $log ? $this->logPayload($log) : null,
            ];
        }
        return $rows;
    }

    private function logPayload(array $l): array
    {
        return [
            'id' => (int) $l['ID'], 'wp_user_id' => (int) $l['WpUserID'], 'username' => $l['Username'],
            'action' => $l['Action'], 'email_kind' => $l['EmailKind'], 'email_status' => $l['EmailStatus'],
            'email_sent_at' => $l['EmailSentAt'], 'email_error' => $l['EmailError'], 'email' => $l['Email'],
            'given_name' => $l['GivenName'], 'family_name' => $l['FamilyName'], 'contact_id' => $l['ContactID'] ? (int) $l['ContactID'] : null,
            'level' => $l['Level'] !== null ? (int) $l['Level'] : null, 'ccap' => $l['Ccap'],
        ];
    }

    public function peopleList(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $source = $this->request->getGet('source') === 'attendance' ? 'attendance' : 'guests';
        $showOnly = (string) $this->request->getGet('show_only') === '1';
        return $this->response->setJSON(['data' => $this->people($eventId, $auth['event'], $source, $showOnly)]);
    }

    /** Check (no changes) or apply a batch of people, keyed by people() keys. */
    public function process(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $p = (array) $this->request->getJSON(true);
        $apply = ($p['mode'] ?? 'check') === 'apply';
        $level = max(0, min(4, (int) ($p['level'] ?? 0)));
        $ccap = self::cleanCcap((string) ($p['ccap'] ?? ''));
        $sendExisting = !empty($p['send_existing']);
        $source = ($p['source'] ?? '') === 'attendance' ? 'attendance' : 'guests';
        $keys = array_slice(array_map('strval', (array) ($p['keys'] ?? [])), 0, 25);
        if ($apply && $level < 1) return $this->jsonError(422, 'validation_failed', ['level' => 'Choose a level 1–4']);

        $wp = new WpLookupClient();
        if (!$wp->isConfigured()) return $this->jsonError(503, 'wp_unconfigured', ['message' => 'TestConX.org connection is not configured on the server.']);

        $all = [];
        foreach ($this->people($eventId, $auth['event'], $source, !empty($p['show_only'])) as $row) $all[$row['key']] = $row;
        $results = [];
        foreach ($keys as $k) {
            $row = $all[$k] ?? null;
            if (!$row) { $results[] = ['key' => $k, 'outcome' => 'error', 'message' => 'Person no longer on this list']; continue; }
            try {
                $results[] = $this->processOne($eventId, $auth, $row, $wp, $apply, $level, $ccap, $sendExisting);
            } catch (\Throwable $e) {
                log_message('error', 'wp-accounts process failed: ' . $e->getMessage());
                $results[] = ['key' => $k, 'outcome' => 'error', 'message' => $e->getMessage()];
            }
        }
        return $this->response->setJSON(['data' => $results]);
    }

    private function processOne(int $eventId, array $auth, array $row, WpLookupClient $wp, bool $apply, int $level, string $ccap, bool $sendExisting): array
    {
        $base = ['key' => $row['key']];
        if ($row['email'] === '' || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) return $base + ['outcome' => 'skipped', 'message' => 'No valid email'];

        $user = null; $matchedBy = null;
        if ($row['wp_user_id']) { $user = $wp->lookupById((int) $row['wp_user_id']); $matchedBy = $user ? 'wordpress_id' : null; }
        if (!$user) {
            $r = $wp->lookupByEmailWithStatus($row['email']);
            if ($r['status'] === 'unavailable') return $base + ['outcome' => 'error', 'message' => 'TestConX.org did not respond'];
            $user = $r['user']; $matchedBy = $user ? 'email' : null;
        }

        $curLevel = $user ? (int) ($user['s2_level'] ?? 0) : null;
        $ccaps = $user ? array_map('strval', (array) ($user['s2_ccaps'] ?? [])) : [];
        $wantCaps = self::ccapsFor($ccap, !empty($row['tutorial']));
        $missingCaps = array_values(array_diff($wantCaps, $ccaps));
        $needsGrant = $user && (($level > (int) $curLevel) || $missingCaps);
        $ccapList = implode(',', $wantCaps);
        $wpId = $user ? (int) ($user['wp_user_id'] ?? 0) : 0;
        $username = $user ? (string) ($user['user_login'] ?? $user['username'] ?? '') : $row['suggested_username'];

        $log = $wpId ? $this->db()->table('WpAccountProvision')->where('EventID', $eventId)->where('WpUserID', $wpId)->get()->getRowArray() : null;
        $emailedAlready = $log && $log['EmailStatus'] === 'sent';

        if (!$apply) {
            return $base + [
                'outcome'    => $user ? ($needsGrant ? 'existing_grant' : 'existing_ok') : 'create',
                'matched_by' => $matchedBy, 'wp_user_id' => $wpId ?: null, 'username' => $username,
                'current_level' => $curLevel, 'already_emailed' => $emailedAlready, 'ccaps' => $wantCaps,
            ];
        }

        $action = 'unchanged'; $resetUrl = null;
        if (!$user) {
            $res = $wp->createUser(['email' => $row['email'], 'username' => $row['suggested_username'],
                'first_name' => $row['given_name'], 'last_name' => $row['family_name'], 'level' => $level, 'ccap' => $ccapList]);
            if ($res['status'] === 409 && !empty($res['body']['user'])) {
                $user = $res['body']['user']; // raced with another creator: treat as existing
                $wp->grantAccess((int) ($user['wp_user_id'] ?? 0), $level, $ccapList);
                $action = 'granted';
            } elseif ($res['status'] !== 201 || empty($res['body']['user'])) {
                return $base + ['outcome' => 'error', 'message' => 'Account creation failed: ' . ($res['body']['error'] ?? ('HTTP ' . $res['status']))];
            } else {
                $user = $res['body']['user']; $resetUrl = $res['body']['reset_url'] ?? null; $action = 'created';
            }
            $wpId = (int) ($user['wp_user_id'] ?? 0);
            $username = (string) ($user['user_login'] ?? $user['username'] ?? $username);
            $log = $this->db()->table('WpAccountProvision')->where('EventID', $eventId)->where('WpUserID', $wpId)->get()->getRowArray();
        } elseif ($needsGrant) {
            $res = $wp->grantAccess($wpId, $level, $ccapList);
            if ($res['status'] !== 200) return $base + ['outcome' => 'error', 'message' => 'Access update failed: ' . ($res['body']['error'] ?? ('HTTP ' . $res['status']))];
            $action = !empty($res['body']['changed']) ? 'granted' : 'unchanged';
        }

        if ($row['contact_id'] && $wpId) {
            $cm = new ContactModel();
            $c = $cm->select('WordPressID')->find($row['contact_id']);
            if ($c && (int) ($c['WordPressID'] ?? 0) <= 0) $cm->update($row['contact_id'], ['WordPressID' => $wpId]);
        }

        $kind = ($action === 'created' || ($log && $log['EmailKind'] === 'new')) ? 'new' : 'existing';
        $now = date('Y-m-d H:i:s');
        $data = [
            'EventID' => $eventId, 'Year' => (int) $auth['event']['Year'], 'WpUserID' => $wpId,
            'ContactID' => $row['contact_id'], 'GuestID' => $row['guest_id'], 'Email' => $row['email'],
            'GivenName' => substr((string) $row['given_name'], 0, 60), 'FamilyName' => substr((string) $row['family_name'], 0, 60),
            'Username' => $username, 'Level' => $level, 'Ccap' => $ccapList !== '' ? substr($ccapList, 0, 40) : null, 'EmailKind' => $kind, 'UpdatedAt' => $now,
        ];
        if ($log) {
            if ($action !== 'unchanged' && $log['Action'] !== 'created') $data['Action'] = $action;
            $this->db()->table('WpAccountProvision')->where('ID', $log['ID'])->update($data);
            $logId = (int) $log['ID'];
        } else {
            $data += ['Action' => $action, 'EmailStatus' => 'pending', 'CreatedBy' => $auth['actorId'], 'CreatedAt' => $now];
            $this->db()->table('WpAccountProvision')->insert($data);
            $logId = (int) $this->db()->insertID();
        }

        $shouldEmail = !$emailedAlready && ($kind === 'new' ? $resetUrl !== null : $sendExisting);
        // A previously created account whose email never went out needs a fresh link.
        if (!$emailedAlready && $kind === 'new' && $resetUrl === null) {
            $rl = $wp->resetLink($wpId);
            $resetUrl = $rl['body']['reset_url'] ?? null;
            $shouldEmail = $resetUrl !== null;
        }
        if (!$shouldEmail && !$emailedAlready) {
            $this->db()->table('WpAccountProvision')->where('ID', $logId)->update(['EmailStatus' => 'skipped']);
        }

        return $base + [
            'outcome' => $action, 'log_id' => $logId, 'wp_user_id' => $wpId, 'username' => $username,
            'email_kind' => $kind, 'should_email' => $shouldEmail, 'already_emailed' => $emailedAlready,
            'reset_url' => $shouldEmail ? $resetUrl : null,
            'email' => $row['email'], 'given_name' => $row['given_name'], 'family_name' => $row['family_name'],
        ];
    }

    public function emailResult(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $p = (array) $this->request->getJSON(true);
        $status = in_array($p['status'] ?? '', ['sent', 'failed'], true) ? $p['status'] : null;
        if (!$status) return $this->jsonError(422, 'validation_failed');
        $upd = ['EmailStatus' => $status, 'UpdatedAt' => date('Y-m-d H:i:s'),
                'EmailError' => $status === 'failed' ? substr((string) ($p['error'] ?? 'Send failed'), 0, 255) : null];
        if ($status === 'sent') { $upd['EmailSentAt'] = date('Y-m-d H:i:s'); $upd['EmailMessageID'] = substr((string) ($p['message_id'] ?? ''), 0, 190) ?: null; }
        $this->db()->table('WpAccountProvision')->where('ID', (int) ($p['log_id'] ?? 0))->where('EventID', $eventId)->update($upd);
        return $this->response->setJSON(['ok' => true]);
    }

    public function resend(int $eventId, int $logId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $log = $this->db()->table('WpAccountProvision')->where('ID', $logId)->where('EventID', $eventId)->get()->getRowArray();
        if (!$log) return $this->jsonError(404, 'not_found');
        $resetUrl = null;
        if ($log['EmailKind'] === 'new') {
            $rl = (new WpLookupClient())->resetLink((int) $log['WpUserID']);
            $resetUrl = $rl['body']['reset_url'] ?? null;
            if (!$resetUrl) return $this->jsonError(502, 'reset_link_failed', ['message' => 'TestConX.org did not issue a new set-password link']);
        }
        return $this->response->setJSON(['data' => $this->logPayload($log) + ['reset_url' => $resetUrl]]);
    }

    public function log(int $eventId)
    {
        $auth = $this->authorize($eventId);
        if (!is_array($auth)) return $auth;
        $rows = $this->db()->table('WpAccountProvision')->where('EventID', $eventId)->orderBy('ID', 'DESC')->get()->getResultArray();
        return $this->response->setJSON(['data' => array_map(fn($l) => $this->logPayload($l), $rows)]);
    }

    /** Service call from the Mailgun webhook. */
    public function emailFailed()
    {
        $p = (array) $this->request->getJSON(true);
        $mid = trim((string) ($p['message_id'] ?? ''), " <>");
        if ($mid === '') return $this->jsonError(400, 'message_id_required');
        $row = $this->db()->table('WpAccountProvision')
            ->groupStart()->where('EmailMessageID', $mid)->orWhere('EmailMessageID', '<' . $mid . '>')->groupEnd()
            ->get()->getRowArray();
        if (!$row) return $this->response->setJSON(['ok' => true, 'found' => false]);
        $this->db()->table('WpAccountProvision')->where('ID', $row['ID'])->update([
            'EmailStatus' => 'failed', 'EmailError' => substr((string) ($p['reason'] ?? 'Bounced'), 0, 255), 'UpdatedAt' => date('Y-m-d H:i:s'),
        ]);
        $event = (new EventModel())->find((int) $row['EventID']);
        return $this->response->setJSON(['ok' => true, 'found' => true, 'data' => $this->logPayload($row) + [
            'event_id' => (int) $row['EventID'], 'event_name' => $event ? trim(($event['Name'] ?? '') . ' ' . ($event['Year'] ?? '')) : null,
        ]]);
    }

    public function options()
    {
        return $this->response->setStatusCode(204);
    }
}
