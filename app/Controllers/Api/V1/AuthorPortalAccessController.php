<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\EventModel;
use App\Models\PresentationModel;
use App\Models\UserModel;
use App\Models\UserModuleModel;
use Config\Database;

/**
 * Returns the effective Author Portal access scopes for the acting user.
 *
 * Roles are DERIVED, not stored — see migration AuthorPortalSchema for the
 * source columns:
 *   - isAdmin                 → user has the `admin` module
 *   - managedEventIds         → events.EventManagerID  = UserID
 *   - chairedEventIds         → events.EventChair{1,2}ID = UserID
 *   - coordinatedSessionIds   → sessions.Coordinator{1,2}ID = UserID
 *   - authoredPresentationIds → authors.ContactID = user's ContactID
 *
 * Admin auto-grants access to every event/session/presentation — clients
 * should still gate UI on the explicit arrays for non-admins. Every WRITE
 * endpoint that touches Author Portal data MUST re-check via this controller
 * (or its underlying queries) — never trust client-supplied scopes.
 */
class AuthorPortalAccessController extends BaseApiController
{
    public function me()
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) {
            return $this->jsonError(401, 'acting_user_required');
        }

        $db      = Database::connect();
        $isAdmin = (new UserModuleModel())->userHasModule($actorId, 'admin');

        // Resolve ContactID for the acting user (Authors = CRM contacts).
        $user      = (new UserModel())->find($actorId);
        $contactId = is_array($user) && isset($user['ContactID']) ? (int) $user['ContactID'] : null;

        // managedEventIds
        $managed = $db->table('events')
            ->select('EventID')
            ->where('EventManagerID', $actorId)
            ->get()->getResultArray();

        // chairedEventIds (chair1 or chair2)
        $chaired = $db->table('events')
            ->select('EventID')
            ->groupStart()
                ->where('EventChair1ID', $actorId)
                ->orWhere('EventChair2ID', $actorId)
            ->groupEnd()
            ->get()->getResultArray();

        // coordinatedSessionIds (coord1 or coord2)
        $coordinated = $db->table('sessions')
            ->select('SessionID')
            ->groupStart()
                ->where('Coordinator1ID', $actorId)
                ->orWhere('Coordinator2ID', $actorId)
            ->groupEnd()
            ->get()->getResultArray();

        // authoredPresentationIds — only Status='active' are surfaced to authors.
        $authored = [];
        if ($contactId) {
            $authored = $db->table('authors')
                ->select('authors.PresentationID')
                ->join('presentations', 'presentations.PresentationID = authors.PresentationID', 'left')
                ->where('authors.ContactID', $contactId)
                ->groupStart()
                    ->where('presentations.Status', 'active')
                    ->orWhere('presentations.Status IS NULL', null, false)
                ->groupEnd()
                ->groupBy('authors.PresentationID')
                ->get()->getResultArray();
        }

        $lockedEventIds        = (new EventModel())->lockedEventIds();
        $hiddenPresentationIds = (new PresentationModel())->hiddenPresentationIds();

        return $this->response->setJSON([
            'data' => [
                'user_id'                   => $actorId,
                'contact_id'                => $contactId,
                'is_admin'                  => $isAdmin,
                'managed_event_ids'         => array_map(fn($r) => (int) $r['EventID'], $managed),
                'chaired_event_ids'         => array_map(fn($r) => (int) $r['EventID'], $chaired),
                'coordinated_session_ids'   => array_map(fn($r) => (int) $r['SessionID'], $coordinated),
                'authored_presentation_ids' => array_map(fn($r) => (int) $r['PresentationID'], $authored),
                'locked_event_ids'          => $lockedEventIds,
                'hidden_presentation_ids'   => $hiddenPresentationIds,
            ],
        ]);
    }
}
