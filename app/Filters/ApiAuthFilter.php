<?php
namespace App\Filters;

// Tries HMAC first (if X-Api-Key header present), otherwise JWT.
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request->getHeaderLine('X-Api-Key')) {
            return (new HmacAuthFilter())->before($request, $arguments);
        }
        return (new JwtAuthFilter())->before($request, $arguments);
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
