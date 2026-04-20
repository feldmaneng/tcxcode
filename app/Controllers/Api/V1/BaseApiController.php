<?php
namespace App\Controllers\Api\V1;

use CodeIgniter\RESTful\ResourceController;

class BaseApiController extends ResourceController
{
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
}
