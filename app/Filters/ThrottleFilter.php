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
        //
        // All calls from the portal arrive under ONE service API key, so
        // keying on X-Api-Key would put every signed-in user in a single
        // shared bucket — a busy page easily exhausts it and legitimate
        // users see rate_limited errors. Key on the acting end user when
        // present (each user gets their own bucket), falling back to the
        // API key / IP for unauthenticated service calls.
        $actingUser = $request->getHeaderLine('X-Acting-User');
        if ($actingUser !== '') {
            $identifier = 'user:' . $actingUser;
            $capacity   = 240; // 240 requests per 60 seconds per user
        } else {
            $identifier = $request->getHeaderLine('X-Api-Key') ?: $request->getIPAddress();
            $capacity   = 600; // service-to-service bucket (login, refresh, etc.)
        }
        $key = 'api_throttle_' . hash('sha256', $identifier);

        if ($throttler->check($key, $capacity, MINUTE) === false) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON(['error' => 'rate_limited', 'retry_after' => $throttler->getTokenTime()]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
