<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\ModuleAccess;
use App\Models\EventModel;
use App\Models\ExpoDirectoryCoordinatorModel;
use App\Models\ExpoDirectoryModel;
use App\Models\ExpoDirectoryTagModel;
use App\Models\ExpoTagModel;
use App\Models\UserModuleModel;
use Config\Database;

/**
 * Recipient resolution for the portal messaging module.
 *
 *   GET /api/v1/messaging/recipients/exhibitors?event_id=
 *   GET /api/v1/messaging/recipients/authors?event_id=
 *
 * Restricted to admins, expo-module event planners, and the chairs/managers
 * of the event (same rule as the exhibiting-history report). Exhibitor
 * coordinators and session coordinators never see recipient lists.
 */
class MessagingController extends BaseApiController
{
    private function canMessage(?int $userId, int $eventId): bool
    {
        if ($userId === null) return true; // trusted service-to-service call
        if (in_array('admin', ModuleAccess::codesForUser($userId), true)) return true;
        if ((new UserModuleModel())->userHasModule($userId, 'expo')) return true;
        $row = (new EventModel())
            ->select('EventChair1ID, EventChair2ID, EventManagerID, GeneralChairID')
            ->find($eventId);
        if (!$row) return false;
        foreach ($row as $val) {
            if ((int) $val === $userId) return true;
        }
        return false;
    }

    /** @return \CodeIgniter\HTTP\ResponseInterface|null */
    private function guard(int $eventId)
    {
        if ($eventId <= 0) return $this->jsonError(400, 'invalid_event_id');
        if (!$this->canMessage(ApiAuthContext::actingUserId(), $eventId)) {
            return $this->jsonError(403, 'forbidden');
        }
        return null;
    }

    /**
     * GET /api/v1/messaging/access?event_id= — permission probe; also returns
     * the event manager (events.EventManagerID) for the "Cc event manager" option.
     */
    public function access()
    {
        $eventId = (int) ($this->request->getGet('event_id') ?? 0);
        if ($deny = $this->guard($eventId)) return $deny;
        $manager = null;
        try {
            $row = (new EventModel())->select('EventManagerID')->find($eventId);
            $mid = (int) ($row['EventManagerID'] ?? 0);
            if ($mid > 0) {
                $u = $this->usersById([$mid])[$mid] ?? null;
                if ($u) {
                    $email = trim((string) ($u['Email'] ?? ''));
                    $manager = [
                        'user_id' => $mid,
                        'name'    => self::displayName($u['GivenName'] ?? null, $u['FamilyName'] ?? null, $u['UserName'] ?? null),
                        'email'   => $email !== '' ? $email : null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[messaging] event manager lookup failed: ' . $e->getMessage());
        }
        return $this->response->setJSON(['allowed' => true, 'event_manager' => $manager]);
    }

    /** @param int[] $ids @return array<int,array> keyed by ContactID */
    private function contactsById(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) return [];
        try {
            $rows = db_connect()->table('contacts')
                ->select('ContactID, GivenName, FamilyName, Nickname, Email, Company, Phone')
                ->whereIn('ContactID', $ids)->get()->getResultArray();
        } catch (\Throwable $e) {
            try {
                $rows = db_connect()->table('contacts')
                    ->select('ContactID, GivenName, FamilyName, Email')
                    ->whereIn('ContactID', $ids)->get()->getResultArray();
            } catch (\Throwable $e2) {
                log_message('error', '[messaging] contact lookup failed: ' . $e2->getMessage());
                return [];
            }
        }
        $out = [];
        foreach ($rows as $r) $out[(int) $r['ContactID']] = $r;
        return $out;
    }

    /** Control-DB users keyed by UserID (name + email for coordinator cc). */
    private function usersById(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) return [];
        try {
            $rows = db_connect('control')->table('users')
                ->select('UserID, GivenName, FamilyName, UserName, Email')
                ->whereIn('UserID', $ids)->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', '[messaging] user lookup failed: ' . $e->getMessage());
            return [];
        }
        $out = [];
        foreach ($rows as $u) $out[(int) $u['UserID']] = $u;
        return $out;
    }

    private static function displayName(?string $given, ?string $family, ?string $fallback): ?string
    {
        $name = trim(((string) $given) . ' ' . ((string) $family));
        if ($name !== '') return $name;
        $fb = trim((string) $fallback);
        return $fb !== '' ? $fb : null;
    }

    /** GET /api/v1/messaging/recipients/exhibitors?event_id= */
    public function exhibitorRecipients()
    {
        $eventId = (int) ($this->request->getGet('event_id') ?? 0);
        if ($deny = $this->guard($eventId)) return $deny;

        $rows = (new ExpoDirectoryModel())->builder()
            ->select('EntryID, CompanyID, CompanyName, DirectoryName, BoothNumber, BoothType, Status')
            ->where('EventID', $eventId)
            ->where('DeletedAt', null)
            ->orderBy('CompanyName', 'ASC')
            ->get()->getResultArray();

        $entryIds = array_map(fn($r) => (int) $r['EntryID'], $rows);
        $coordsByEntry = $entryIds ? (new ExpoDirectoryCoordinatorModel())->forEntries($entryIds) : [];

        $contactIds = [];
        foreach ($coordsByEntry as $list) {
            foreach ($list as $c) $contactIds[(int) $c['ContactID']] = true;
        }
        $contacts = $this->contactsById(array_keys($contactIds));

        $tagIdsByEntry = $entryIds ? (new ExpoDirectoryTagModel())->tagIdsForEntries($entryIds) : [];
        $tagsById = [];
        foreach ((new ExpoTagModel())->allSorted(false) as $t) {
            $tagsById[(int) $t['TagID']] = [
                'id'       => (int) $t['TagID'],
                'name'     => (string) $t['Name'],
                'category' => (string) ($t['Category'] ?? 'sponsorship'),
            ];
        }

        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r['EntryID'];
            $coords = array_values(array_map(function (array $c) use ($contacts) {
                $cid = (int) $c['ContactID'];
                $ct  = $contacts[$cid] ?? null;
                return [
                    'contact_id'  => $cid,
                    'given_name'  => $ct['GivenName'] ?? null,
                    'family_name' => $ct['FamilyName'] ?? null,
                    'name'        => self::displayName($ct['GivenName'] ?? null, $ct['FamilyName'] ?? null, $ct['Nickname'] ?? null),
                    'email'       => $ct['Email'] ?? null,
                    'phone'       => $ct['Phone'] ?? null,
                    'is_primary'  => (int) ($c['IsPrimary'] ?? 0),
                ];
            }, $coordsByEntry[$id] ?? []));

            $tags = array_values(array_filter(array_map(
                fn($tid) => $tagsById[(int) $tid] ?? null,
                $tagIdsByEntry[$id] ?? []
            )));

            $out[] = [
                'entry_id'       => $id,
                'company_id'     => !empty($r['CompanyID']) ? (int) $r['CompanyID'] : null,
                'company_name'   => $r['CompanyName'],
                'directory_name' => $r['DirectoryName'] ?? null,
                'booth_number'   => $r['BoothNumber'] ?? null,
                'booth_type'     => $r['BoothType'] ?? null,
                'status'         => $r['Status'],
                'tags'           => $tags,
                'coordinators'   => $coords,
            ];
        }

        return $this->response->setJSON(['data' => $out]);
    }

    /** GET /api/v1/messaging/recipients/authors?event_id= */
    public function authorRecipients()
    {
        $eventId = (int) ($this->request->getGet('event_id') ?? 0);
        if ($deny = $this->guard($eventId)) return $deny;

        $db = Database::connect();

        $sessions = $db->table('sessions')
            ->select('SessionID, SessionNumber, SessionName, Coordinator1ID, Coordinator2ID')
            ->where('EventID', $eventId)
            ->orderBy('SessionNumber', 'ASC')
            ->get()->getResultArray();
        if (!$sessions) return $this->response->setJSON(['data' => []]);

        $sessionsById = [];
        foreach ($sessions as $s) $sessionsById[(int) $s['SessionID']] = $s;

        // Withdrawn / not-selected presentations never appear as recipients.
        $presentations = $db->table('presentations')
            ->select('PresentationID, SessionID, PresentationNumber, Title, Status, Coordinator1ID, Coordinator2ID, CoordinatorsPinned')
            ->whereIn('SessionID', array_keys($sessionsById))
            ->groupStart()->where('Status', null)->orWhereNotIn('Status', ['withdrawn', 'not_selected'])->groupEnd()
            ->orderBy('PresentationNumber', 'ASC')
            ->get()->getResultArray();

        $pids = array_map(fn($p) => (int) $p['PresentationID'], $presentations);

        $authorsByPres = [];
        if ($pids) {
            $rows = $db->table('authors')
                ->select('authors.AuthorID, authors.PresentationID, authors.AuthorNumber, authors.Presenter, authors.ContactID, authors.GivenName, authors.FamilyName, authors.Company, contacts.Email AS ContactEmail, contacts.GivenName AS ContactGiven, contacts.FamilyName AS ContactFamily, contacts.Nickname AS ContactNick')
                ->join('contacts', 'contacts.ContactID = authors.ContactID', 'left')
                ->whereIn('authors.PresentationID', $pids)
                ->orderBy('authors.AuthorNumber', 'ASC')
                ->orderBy('authors.AuthorID', 'ASC')
                ->get()->getResultArray();
            foreach ($rows as $a) {
                $authorsByPres[(int) $a['PresentationID']][] = [
                    'author_id'     => (int) $a['AuthorID'],
                    'author_number' => $a['AuthorNumber'] === null ? null : (int) $a['AuthorNumber'],
                    'presenter'     => (int) ($a['Presenter'] ?? 0),
                    'contact_id'    => $a['ContactID'] === null ? null : (int) $a['ContactID'],
                    'given_name'    => ($a['GivenName'] ?? '') !== '' ? $a['GivenName'] : ($a['ContactGiven'] ?? null),
                    'family_name'   => ($a['FamilyName'] ?? '') !== '' ? $a['FamilyName'] : ($a['ContactFamily'] ?? null),
                    'company'       => $a['Company'] ?? null,
                    'email'         => $a['ContactEmail'] ?? null,
                ];
            }
        }

        // Coordinator identity: per-presentation override wins when pinned,
        // otherwise the session coordinators apply.
        $userIds = [];
        foreach ($presentations as $p) {
            $s = $sessionsById[(int) ($p['SessionID'] ?? 0)] ?? null;
            $pinned = (int) ($p['CoordinatorsPinned'] ?? 0) === 1;
            $c1 = $pinned ? ($p['Coordinator1ID'] ?? null) : ($s['Coordinator1ID'] ?? $p['Coordinator1ID'] ?? null);
            $c2 = $pinned ? ($p['Coordinator2ID'] ?? null) : ($s['Coordinator2ID'] ?? $p['Coordinator2ID'] ?? null);
            foreach ([$c1, $c2] as $cid) { if ($cid) $userIds[(int) $cid] = true; }
        }
        $users = $this->usersById(array_keys($userIds));
        $userShape = function ($id) use ($users) {
            $u = $id ? ($users[(int) $id] ?? null) : null;
            if (!$u) return null;
            return [
                'user_id' => (int) $u['UserID'],
                'name'    => self::displayName($u['GivenName'] ?? null, $u['FamilyName'] ?? null, $u['UserName'] ?? null),
                'email'   => $u['Email'] ?? null,
            ];
        };

        $out = [];
        foreach ($presentations as $p) {
            $pid = (int) $p['PresentationID'];
            $s = $sessionsById[(int) ($p['SessionID'] ?? 0)] ?? [];
            $pinned = (int) ($p['CoordinatorsPinned'] ?? 0) === 1;
            $c1 = $pinned ? ($p['Coordinator1ID'] ?? null) : ($s['Coordinator1ID'] ?? $p['Coordinator1ID'] ?? null);
            $c2 = $pinned ? ($p['Coordinator2ID'] ?? null) : ($s['Coordinator2ID'] ?? $p['Coordinator2ID'] ?? null);
            $out[] = [
                'presentation_id'     => $pid,
                'presentation_number' => $p['PresentationNumber'] === null ? null : (int) $p['PresentationNumber'],
                'title'               => $p['Title'] ?? null,
                'status'              => $p['Status'] ?? null,
                'session_id'          => isset($p['SessionID']) ? (int) $p['SessionID'] : null,
                'session_number'      => $s['SessionNumber'] ?? null,
                'session_name'        => $s['SessionName'] ?? null,
                'coordinator1'        => $userShape($c1),
                'coordinator2'        => $userShape($c2),
                'authors'             => $authorsByPres[$pid] ?? [],
            ];
        }

        return $this->response->setJSON(['data' => $out]);
    }
}
