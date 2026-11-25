<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\PresentationAssistants;
use App\Libraries\ProgramAccess;
use App\Models\EventModel;
use App\Models\UserModuleModel;
use Config\Database;

class PresentationAssistantsController extends BaseApiController
{
    private array $actor = [];
    private array $presentation = [];
    private bool $staff = false;

    private function guard(int $pid, bool $write = false)
    {
        $uid = ApiAuthContext::actingUserId();
        if (!$uid) return $this->jsonError(401, 'not_authenticated');
        if (!PresentationAssistants::available()) return $this->jsonError(503, 'assistant_setup_required');
        $db = Database::connect();
        $p = $db->table('presentations')->where('PresentationID', $pid)->get()->getRowArray();
        if (!$p) return $this->jsonError(404, 'presentation_not_found');
        $this->presentation = $p;
        $this->actor = Database::connect('control')->table('users')->where('UserID', $uid)->get()->getRowArray() ?? [];
        if (!$this->actor || empty($this->actor['Active'])) return $this->jsonError(403, 'inactive_user');
        $cid = (int) ($this->actor['ContactID'] ?? 0);
        $eid = ProgramAccess::eventIdForSession((int) ($p['SessionID'] ?? 0));
        $admin = (new UserModuleModel())->userHasModule($uid, 'admin');
        $event = $eid ? $db->table('events')->where('EventID', $eid)->get()->getRowArray() : null;
        $manager = (int) ($event['EventManagerID'] ?? 0) === $uid;
        $this->staff = ProgramAccess::canManageProgram($uid, $eid)
            || in_array($uid, ProgramAccess::presentationCoordinatorIds($pid), true);
        $author = $cid > 0 && $db->table('authors')->where('PresentationID', $pid)->where('ContactID', $cid)->countAllResults() > 0;
        $assistant = in_array($pid, PresentationAssistants::presentationIds($cid), true);
        if (!$this->staff && !$author && !(!$write && $assistant)) return $this->jsonError(403, 'no_access');
        if (in_array($p['Status'] ?? 'active', ['withdrawn', 'not_selected'], true) && !$admin && !$manager)
            return $this->jsonError(404, 'presentation_not_found');
        if ($write && $eid && in_array($eid, (new EventModel())->lockedEventIds(), true) && !$admin && !$manager)
            return $this->jsonError(423, 'event_locked');
        return null;
    }

    private function shape(array $r): array
    {
        return [
            'id' => (int) $r['AssistantID'], 'presentation_id' => (int) $r['PresentationID'],
            'contact_id' => isset($r['ContactID']) ? (int) $r['ContactID'] : null,
            'given_name' => $r['GivenName'], 'family_name' => $r['FamilyName'],
            'email' => $r['CrmEmail'] ?? $r['Email'], 'status' => $r['Status'],
            'office_notified_at' => $r['OfficeNotifiedAt'], 'office_notification_error' => $r['OfficeNotificationError'],
            'can_remove' => $this->staff || (int) $r['CreatedByUserID'] === (int) $this->actor['UserID'],
        ];
    }

    public function index(int $pid)
    {
        if ($deny = $this->guard($pid)) return $deny;
        return $this->response->setJSON(['data' => array_map(fn($r) => $this->shape($r), PresentationAssistants::rows($pid))]);
    }

    public function create(int $pid)
    {
        if ($deny = $this->guard($pid, true)) return $deny;
        $b = $this->request->getJSON(true) ?? [];
        $given = trim((string) ($b['given_name'] ?? ''));
        $family = trim((string) ($b['family_name'] ?? ''));
        $email = strtolower(trim((string) ($b['email'] ?? '')));
        if (!$given || !$family || mb_strlen($given) > 100 || mb_strlen($family) > 100
            || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL))
            return $this->jsonError(422, 'Name and valid email are required');
        $db = Database::connect();
        $existing = $db->table('presentation_assistants')->where('PresentationID', $pid)->where('Email', $email)->get()->getRowArray();
        if ($existing) return $this->jsonError(409, 'This assistant is already linked');
        $cid = PresentationAssistants::contactForEmail($email);
        $r = ['PresentationID' => $pid, 'ContactID' => $cid, 'GivenName' => $given, 'FamilyName' => $family,
            'Email' => $email, 'Status' => $cid ? 'approved' : 'pending_crm',
            'CreatedByUserID' => (int) $this->actor['UserID'], 'CreatedByContactID' => $this->actor['ContactID'] ?: null,
            'CreatedAt' => date('Y-m-d H:i:s'), 'OfficeNotifiedAt' => null, 'OfficeNotificationError' => null];
        if (!$db->table('presentation_assistants')->insert($r)) return $this->jsonError(500, 'assistant_save_failed');
        $r['AssistantID'] = (int) $db->insertID();
        return $this->response->setStatusCode(201)->setJSON(['data' => $this->shape($r)]);
    }

    public function delete(int $pid, int $id)
    {
        if ($deny = $this->guard($pid, true)) return $deny;
        $q = Database::connect()->table('presentation_assistants')->where('PresentationID', $pid)->where('AssistantID', $id);
        if (!$this->staff) $q->where('CreatedByUserID', $this->actor['UserID']);
        if (!(clone $q)->countAllResults()) return $this->jsonError(404, 'assistant_not_found');
        $q->delete();
        return $this->response->setJSON(['ok' => true]);
    }

    /** Notification details are resolved server-side, never supplied by the browser. */
    public function notification(int $pid, int $id)
    {
        if ($deny = $this->guard($pid, true)) return $deny;
        $db = Database::connect();
        $row = $db->table('presentation_assistants')->where('PresentationID', $pid)->where('AssistantID', $id)->get()->getRowArray();
        if (!$row || (!$this->staff && (int) $row['CreatedByUserID'] !== (int) $this->actor['UserID']))
            return $this->jsonError(404, 'assistant_not_found');
        if (strtoupper($this->request->getMethod()) === 'PUT') {
            $body = $this->request->getJSON(true) ?? [];
            $db->table('presentation_assistants')->where('AssistantID', $id)->update([
                'OfficeNotifiedAt' => !empty($body['sent']) ? date('Y-m-d H:i:s') : null,
                'OfficeNotificationError' => !empty($body['sent']) ? null : mb_substr((string) ($body['error'] ?? 'Email not sent'), 0, 1000),
            ]);
            return $this->response->setJSON(['ok' => true]);
        }
        $requester = Database::connect('control')->table('users')->where('UserID', $row['CreatedByUserID'])->get()->getRowArray();
        $requester = \App\Libraries\CrmEmail::applyOne($requester ?: null);
        $session = $db->table('sessions')->where('SessionID', (int) ($this->presentation['SessionID'] ?? 0))->get()->getRowArray();
        $event = $session ? $db->table('events')->where('EventID', $session['EventID'])->get()->getRowArray() : null;
        return $this->response->setJSON(['data' => [
            'assistant' => $this->shape($row), 'requester_email' => $requester['Email'] ?? '',
            'requester_name' => trim(($requester['GivenName'] ?? '') . ' ' . ($requester['FamilyName'] ?? '')),
            'presentation_title' => $this->presentation['Title'] ?? '', 'presentation_number' => $this->presentation['PresentationNumber'] ?? '',
            'session_name' => $session['SessionName'] ?? '', 'event_name' => $event['FullName'] ?? $event['Name'] ?? $this->presentation['Event'] ?? '',
            'event_year' => $event['Year'] ?? $this->presentation['Year'] ?? '',
        ]]);
    }
}
