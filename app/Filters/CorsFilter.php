<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $origin  = $request->getHeaderLine('Origin');
        $allowed = array_filter(array_map('trim', explode(',', (string) env('app.CORS_ALLOWED_ORIGINS', ''))));
        $response = service('response');

        if ($origin && in_array($origin, $allowed, true)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
            $response->setHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
            $response->setHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Api-Key,X-Timestamp,X-Signature');
            $response->setHeader('Access-Control-Max-Age', '600');
        }

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $response->setStatusCode(204);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
