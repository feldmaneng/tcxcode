<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\GuestBadge;
use App\Models\CompanyGuestListsModel;
use App\Models\EventGuestModel;
use App\Models\EventModel;
use App\Models\UserModuleModel;

/**
 * GET /api/v1/guests/{GuestID}/badge  → application/pdf (inline)
 *
 * Only HMAC-signed requests from the office site carrying a signed
 * X-Acting-User. The acting user must be an admin or the Event Manager of
 * the guest's event.
 */
class GuestBadgeController extends BaseApiController
{
    public function show($guestId = null)
    {
        $auth = ApiAuthContext::get();
        if (($auth['type'] ?? null) !== 'hmac') return $this->jsonError(401, 'signed_request_required');
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');

        $guestId = (int) $guestId;
        if ($guestId <= 0) return $this->jsonError(400, 'invalid_guest_id');

        $guest = (new EventGuestModel())->where('GuestID', $guestId)->where('DeletedAt', null)->first();
        if (!$guest) return $this->jsonError(404, 'not_found');

        $list = (new CompanyGuestListsModel())->find((int) ($guest['InvitedByCompanyID'] ?? 0));
        if (!$list || !empty($list['DeletedAt'])) return $this->jsonError(404, 'not_found');

        $isAdmin = (new UserModuleModel())->userHasModule($actorId, 'admin');
        $eventModel = new EventModel();
        $eventId = (int) ($list['EventID'] ?? 0);
        $year    = (int) ($list['Year'] ?? 0);
        $isEventManager = $eventId > 0
            ? $eventModel->isEventManagerForEvent($actorId, $eventId)
            : ($year > 0 && $eventModel->isEventManagerForYear($actorId, $year));
        if (!$isAdmin && !$isEventManager) return $this->jsonError(403, 'forbidden');

        $res = GuestBadge::fetch($guestId);
        if (!$res['ok']) return $this->jsonError(502, $res['error'] ?? 'badge_unavailable');

        try {
            (new \App\Models\AdminAuditLogModel())->log(
                $actorId, 'guest.badge_printed', 'guests', (string) $guestId,
                ['event_id' => $eventId], $this->request->getIPAddress()
            );
        } catch (\Throwable $e) {
            log_message('error', '[GuestBadge] audit failed: ' . $e->getMessage());
        }

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="badge-' . $guestId . '.pdf"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($res['pdf']);
    }
}
