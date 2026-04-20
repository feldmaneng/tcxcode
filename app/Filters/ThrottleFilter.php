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

        // CI4 cache keys cannot contain reserved characters: { } ( ) / \ @ :
        // API keys and IPv6 addresses commonly contain ":" so we hash the
        // identifier before using it as part of the cache key.
        $identifier = $request->getHeaderLine('X-Api-Key') ?: $request->getIPAddress();
        $key        = 'api_throttle_' . hash('sha256', $identifier);

        // 60 requests per 60 seconds
        if ($throttler->check($key, 60, MINUTE) === false) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON(['error' => 'rate_limited', 'retry_after' => $throttler->getTokenTime()]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
