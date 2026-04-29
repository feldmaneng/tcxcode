<?php
namespace App\Filters;

use App\Libraries\ApiAuthContext;
use App\Libraries\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$request instanceof IncomingRequest) return;

        $auth = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.+)$/', $auth, $m)) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'jwt_missing']);
        }
        try {
            $claims = JwtService::verify($m[1]);
        } catch (\Throwable $e) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'jwt_invalid']);
        }
        ApiAuthContext::set(['type' => 'jwt', 'user_id' => $claims['sub'] ?? null, 'claims' => $claims]);
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
