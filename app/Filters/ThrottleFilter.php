<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = Services::throttler();
        $key       = 'api:' . ($request->getHeaderLine('X-Api-Key') ?: $request->getIPAddress());
        // 60 requests per 60 seconds
        if ($throttler->check($key, 60, MINUTE) === false) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON(['error' => 'rate_limited', 'retry_after' => $throttler->getTokenTime()]);
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
