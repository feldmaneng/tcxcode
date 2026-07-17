<?php
namespace App\Controllers\Api\V1;

use App\Models\UserModuleModel;
use Config\Database;

/**
 * Resolves email-recipient lists and inbound-sender lookups for the
 * author-portal notification flow. Called by the TanStack notification
 * helper and the Mailgun inbound webhook. Both endpoints authenticate via
 * the standard HMAC service key; no acting user required.
 */
class PresentationRecipientsController extends BaseApiController
{
    /**
     * GET /api/v1/author-portal/presentations/{id}/recipients
     *   ?scope=public|internal
     *   &include_general_chair=0|1
     *   &actor_user_id=<int>          (excluded from recipients)
     *
     * Returns:
     *   { data: {
     *       event_id: int,
     *       recipients: [ { email, display, user_id?, contact_id? }, ... ]
     *   } }
     * Recipients are de-duplicated by lowercased email.
     */
    public function recipients(int $presentationId)
    {
        $req    = $this->request;
        $scope  = strtolower((string) $req->getGet('scope')) === 'internal' ? 'internal' : 'public';
        $inclGc = ((string) $req->getGet('include_general_chair')) === '1';
        $actor  = (int) ($req->getGet('actor_user_id') ?? 0) ?: null;

        $db = Database::connect();

        // Resolve presentation -> session -> event
        $pres = $db->table('presentations')
            ->select('PresentationID, SessionID')
            ->where('PresentationID', $presentationId)
            ->get()->getRowArray();
        if (!$pres) return $this->jsonError(404, 'presentation_not_found');

        $eventId = null;
        $sessionId = $pres['SessionID'] ? (int) $pres['SessionID'] : null;
        if ($sessionId) {
            $sess = $db->table('sessions')->select('EventID, Coordinator1ID, Coordinator2ID')
                ->where('SessionID', $sessionId)->get()->getRowArray();
            if ($sess) $eventId = (int) $sess['EventID'];
        }

        $collected = []; // key = lowercased email

        $addUser = function (?int $userId) use (&$collected, $db, $actor) {
            if (!$userId || $userId === $actor) return;
            $u = $db->table('users')
                ->select('UserID, UserName, GivenName, FamilyName, Email')
                ->where('UserID', $userId)->get()->getRowArray();
            if (!$u || !$u['Email']) return;
            $key = strtolower(trim((string) $u['Email']));
            if (!$key || isset($collected[$key])) return;
            $display = trim(trim(($u['GivenName'] ?? '') . ' ' . ($u['FamilyName'] ?? ''))) ?: ($u['UserName'] ?? '');
            $collected[$key] = [
                'email'   => $u['Email'],
                'display' => $display,
                'user_id' => (int) $u['UserID'],
            ];
        };

        // Load event once for chairs / general chair / manager
        $event = $eventId ? $db->table('events')
            ->select('EventID, EventChair1ID, EventChair2ID, EventManagerID, GeneralChairID')
            ->where('EventID', $eventId)->get()->getRowArray() : null;

        if ($scope === 'public') {
            // Authors (contacts.Email) + session coordinators
            $rows = $db->table('authors')
                ->select('authors.ContactID, contacts.Email, contacts.GivenName, contacts.FamilyName')
                ->join('contacts', 'contacts.ContactID = authors.ContactID', 'left')
                ->where('authors.PresentationID', $presentationId)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                if (empty($r['Email'])) continue;
                // exclude actor if actor's user has this contact_id
                if ($actor) {
                    $u = $db->table('users')->select('ContactID')->where('UserID', $actor)->get()->getRowArray();
                    if ($u && (int) $u['ContactID'] === (int) $r['ContactID']) continue;
                }
                $key = strtolower(trim((string) $r['Email']));
                if (!$key || isset($collected[$key])) continue;
                $display = trim(trim(($r['GivenName'] ?? '') . ' ' . ($r['FamilyName'] ?? ''))) ?: $r['Email'];
                $collected[$key] = [
                    'email'      => $r['Email'],
                    'display'    => $display,
                    'contact_id' => (int) $r['ContactID'],
                ];
            }
            if ($sessionId && isset($sess)) {
                $addUser(isset($sess['Coordinator1ID']) ? (int) $sess['Coordinator1ID'] : null);
                $addUser(isset($sess['Coordinator2ID']) ? (int) $sess['Coordinator2ID'] : null);
            }
        } else {
            // internal: session coordinators + event chairs
            if ($sessionId && isset($sess)) {
                $addUser(isset($sess['Coordinator1ID']) ? (int) $sess['Coordinator1ID'] : null);
                $addUser(isset($sess['Coordinator2ID']) ? (int) $sess['Coordinator2ID'] : null);
            }
            if ($event) {
                $addUser(isset($event['EventChair1ID']) ? (int) $event['EventChair1ID'] : null);
                $addUser(isset($event['EventChair2ID']) ? (int) $event['EventChair2ID'] : null);
            }
        }

        if ($inclGc && $event && !empty($event['GeneralChairID'])) {
            $addUser((int) $event['GeneralChairID']);
        }

        return $this->response->setJSON([
            'data' => [
                'event_id'   => $eventId,
                'recipients' => array_values($collected),
            ],
        ]);
    }

    /**
     * POST /api/v1/author-portal/inbound/resolve
     *   body: { email: string, presentation_id: int }
     *
     * Look up a CI4 user by email and check whether they can post a comment
     * on the given presentation (author for that presentation, coordinator for
     * its session, chair/manager for its event, general chair for its event,
     * or admin). Returns 404 if no matching user, 403 if no access.
     */
    public function resolveInbound()
    {
        $body = (array) $this->request->getJSON(true);
        $email = trim((string) ($body['email'] ?? ''));
        $pid   = (int) ($body['presentation_id'] ?? 0);
        if ($email === '' || $pid <= 0) return $this->jsonError(422, 'validation_failed');

        $db = Database::connect();
        $user = $db->table('users')
            ->select('UserID, UserName, ContactID, Email, GivenName, FamilyName')
            ->where('LOWER(Email)', strtolower($email))
            ->where('Active', 1)
            ->get()->getRowArray();
        if (!$user) return $this->jsonError(404, 'user_not_found');

        $userId    = (int) $user['UserID'];
        $contactId = $user['ContactID'] ? (int) $user['ContactID'] : null;

        $isAdmin = (new UserModuleModel())->userHasModule($userId, 'admin');

        // Resolve presentation -> session -> event
        $pres = $db->table('presentations')->select('PresentationID, SessionID, Status')
            ->where('PresentationID', $pid)->get()->getRowArray();
        if (!$pres) return $this->jsonError(404, 'presentation_not_found');

        $eventId = null;
        $isCoord = false;
        if ($pres['SessionID']) {
            $sess = $db->table('sessions')->select('EventID, Coordinator1ID, Coordinator2ID')
                ->where('SessionID', (int) $pres['SessionID'])->get()->getRowArray();
            if ($sess) {
                $eventId = (int) $sess['EventID'];
                $isCoord = (int) ($sess['Coordinator1ID'] ?? 0) === $userId
                        || (int) ($sess['Coordinator2ID'] ?? 0) === $userId;
            }
        }

        $isChair = $isManager = $isGeneralChair = false;
        if ($eventId) {
            $ev = $db->table('events')
                ->select('EventChair1ID, EventChair2ID, EventManagerID, GeneralChairID')
                ->where('EventID', $eventId)->get()->getRowArray();
            if ($ev) {
                $isChair        = (int) ($ev['EventChair1ID'] ?? 0) === $userId
                               || (int) ($ev['EventChair2ID'] ?? 0) === $userId;
                $isManager      = (int) ($ev['EventManagerID'] ?? 0) === $userId;
                $isGeneralChair = (int) ($ev['GeneralChairID'] ?? 0) === $userId;
            }
        }

        $isAuthor = false;
        if ($contactId) {
            $a = $db->table('authors')->select('AuthorID')
                ->where('PresentationID', $pid)->where('ContactID', $contactId)
                ->get()->getRowArray();
            $isAuthor = !empty($a);
        }

        $hasAccess = $isAdmin || $isChair || $isManager || $isCoord || $isGeneralChair || $isAuthor;
        if (!$hasAccess) return $this->jsonError(403, 'no_access');

        $display = trim(trim(($user['GivenName'] ?? '') . ' ' . ($user['FamilyName'] ?? ''))) ?: ($user['UserName'] ?? '');

        return $this->response->setJSON([
            'data' => [
                'user_id'  => $userId,
                'username' => $user['UserName'],
                'display'  => $display,
                'event_id' => $eventId,
            ],
        ]);
    }
}
