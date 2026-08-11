<?php
namespace App\Controllers\Api\V1;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;

class BaseApiController extends Controller
{
    use ResponseTrait;

    protected $format = 'json';

    public function options()
    {
        return $this->response->setStatusCode(204);
    }

    protected function jsonError(int $status, string $code, $details = null)
    {
        $body = ['error' => $code];
        if ($details !== null) $body['details'] = $details;
        return $this->response->setStatusCode($status)->setJSON($body);
    }

    /**
     * Module authorization.
     *
     * When the request carries an acting end-user (X-Acting-User), that user
     * MUST hold one of $codes (or the 'admin' module). Pure service-to-service
     * calls (no acting user, HMAC-signed by a trusted server) are allowed.
     *
     * Usage: if ($deny = $this->requireModule(['crm'])) return $deny;
     *
     * @param string[] $codes
     */
    protected function requireModule(array $codes)
    {
        $userId = \App\Libraries\ApiAuthContext::actingUserId();
        if ($userId === null) return null; // trusted service call

        $model = new \App\Models\UserModuleModel();
        $held  = $model->codesForUser($userId);
        if (in_array('admin', $held, true)) return null;
        foreach ($codes as $c) {
            if (in_array($c, $held, true)) return null;
        }
        return $this->jsonError(403, 'forbidden', ['required_modules' => array_values($codes)]);
    }
}
