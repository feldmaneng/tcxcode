<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Models\CompanyGuestListsManagerModel;
use App\Models\CompanyGuestListsModel;
use App\Models\UserModel;
use App\Models\UserModuleModel;

/**
 * Manage the up-to-4 guest-list managers assigned to a companyguestlists row.
 * Admin only.
 */
class CompanyGuestListsManagersController extends BaseApiController
{
    private const MAX_MANAGERS = 4;

    private function requireAdmin(): bool
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) {
            $this->response->setStatusCode(401)->setJSON(['error' => 'acting_user_required']);
            return false;
        }
        if (!(new UserModuleModel())->userHasModule($actorId, 'admin')) {
            $this->response->setStatusCode(403)->setJSON(['error' => 'admin_required']);
            return false;
        }
        return true;
    }

    /** GET /api/v1/company-guest-lists/{id}/managers */
    public function index(int $companyGuestListsId)
    {
        $actorId = ApiAuthContext::actingUserId();
        if (!$actorId) return $this->jsonError(401, 'acting_user_required');

        $isAdmin = (new UserModuleModel())->userHasModule($actorId, 'admin');
        $manager = (new CompanyGuestListsManagerModel())->userManages($actorId, $companyGuestListsId);
        if (!$isAdmin && !$manager) return $this->jsonError(403, 'forbidden');

        $userIds = (new CompanyGuestListsManagerModel())->userIdsForCompany($companyGuestListsId);
        $users = [];
        if ($userIds) {
            $rows = (new UserModel())->builder()
                ->select('UserID, UserName, GivenName, FamilyName, Email')
                ->whereIn('UserID', $userIds)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $users[] = [
                    'id'          => (int) $r['UserID'],
                    'username'    => $r['UserName'],
                    'given_name'  => $r['GivenName'],
                    'family_name' => $r['FamilyName'],
                    'email'       => $r['Email'] ?? null,
                ];
            }
        }
        return $this->response->setJSON(['data' => $users]);
    }

    /** POST /api/v1/company-guest-lists/{id}/managers  {user_id} */
    public function add(int $companyGuestListsId)
    {
        if (!$this->requireAdmin()) return $this->response;
        if (!(new CompanyGuestListsModel())->find($companyGuestListsId)) return $this->jsonError(404, 'not_found');
        $payload = (array) $this->request->getJSON(true);
        $userId  = (int) ($payload['user_id'] ?? 0);
        if ($userId <= 0) return $this->jsonError(422, 'validation_failed', ['required' => ['user_id']]);

        $mgrs = new CompanyGuestListsManagerModel();
        $current = $mgrs->userIdsForCompany($companyGuestListsId);
        if (in_array($userId, $current, true)) {
            return $this->response->setJSON(['data' => ['user_id' => $userId, 'already' => true]]);
        }
        if (count($current) >= self::MAX_MANAGERS) {
            return $this->jsonError(422, 'max_managers_reached', ['max' => self::MAX_MANAGERS]);
        }
        $mgrs->insert([
            'CompanyGuestListsID' => $companyGuestListsId,
            'UserID'         => $userId,
            'AddedBy'        => ApiAuthContext::actingUserId(),
        ]);
        return $this->response->setStatusCode(201)->setJSON(['data' => ['user_id' => $userId]]);
    }

    /** DELETE /api/v1/company-guest-lists/{id}/managers/{userId} */
    public function remove(int $companyGuestListsId, int $userId)
    {
        if (!$this->requireAdmin()) return $this->response;
        (new CompanyGuestListsManagerModel())
            ->where('CompanyGuestListsID', $companyGuestListsId)
            ->where('UserID', $userId)
            ->delete();
        return $this->response->setStatusCode(204);
    }
}
