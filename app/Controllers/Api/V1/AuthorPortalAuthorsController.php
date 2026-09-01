<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\AuthorModel;
use App\Models\PresentationModel;
use App\Models\UserModuleModel;
use Config\Database;

/**
 * Author Portal — narrow write endpoint for an author's affiliation.
 *
 * Only the `authors`.`Company` snapshot (the affiliation at the time of the
 * presentation) can be changed here; the linked CRM contact is never touched.
 *
 * Authorised for site admins and the event manager of the presentation's
 * event. The general authors endpoint requires the Presentations module,
 * which event managers do not necessarily have.
 */
class AuthorPortalAuthorsController extends BaseApiController
{
    /** PUT /api/v1/author-portal/authors/{id}/company  { company: string|null } */
    public function updateCompany(int $authorId)
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');

        $authors = new AuthorModel();
        $author  = $authors->find($authorId);
        if (!$author) return $this->jsonError(404, 'not_found');

        $eventId = $this->eventIdForPresentation((int) ($author['PresentationID'] ?? 0));
        if (!$this->canEdit($actorId, $eventId)) return $this->jsonError(403, 'forbidden');

        $payload = (array) $this->request->getJSON(true);
        if (!array_key_exists('company', $payload)) {
            return $this->jsonError(422, 'validation_failed', ['required' => ['company']]);
        }
        $company = $payload['company'] === null ? null : trim((string) $payload['company']);
        if ($company === '') $company = null;
        if ($company !== null && mb_strlen($company) > 100) $company = mb_substr($company, 0, 100);

        if (!$authors->update($authorId, ['Company' => $company])) {
            return $this->jsonError(422, 'update_failed', $authors->errors());
        }
        return $this->response->setJSON(['data' => $authors->find($authorId)]);
    }

    /** Site admin or the event manager of that event. */
    private function canEdit(int $actorId, ?int $eventId): bool
    {
        if ((new UserModuleModel())->userHasModule($actorId, 'admin')) return true;
        if (!$eventId) return false;
        $ev = Database::connect()->table('events')
            ->select('EventManagerID')->where('EventID', $eventId)->get()->getRowArray();
        return $ev && (int) ($ev['EventManagerID'] ?? 0) === $actorId;
    }

    /** Event id via the presentation's session, falling back to Event/Year. */
    private function eventIdForPresentation(int $presentationId): ?int
    {
        if ($presentationId <= 0) return null;
        $p = (new PresentationModel())->find($presentationId);
        if (!$p) return null;

        $sessionId = (int) ($p['SessionID'] ?? 0);
        if ($sessionId > 0) {
            $eid = \App\Libraries\ProgramAccess::eventIdForSession($sessionId);
            if ($eid) return $eid;
        }
        $name = trim((string) ($p['Event'] ?? ''));
        $year = (int) ($p['Year'] ?? 0);
        if ($name === '' || $year <= 0) return null;
        $row = Database::connect()->table('events')
            ->select('EventID')->where('Name', $name)->where('Year', $year)
            ->get()->getRowArray();
        return $row ? (int) $row['EventID'] : null;
    }
}
