<?php
namespace App\Filters;

use App\Libraries\ApiAuthContext;
use App\Libraries\HmacVerifier;
use App\Models\ApiClientModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class HmacAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$request instanceof IncomingRequest) return;

        $key  = $request->getHeaderLine('X-Api-Key');
        $ts   = $request->getHeaderLine('X-Timestamp');
        $sig  = $request->getHeaderLine('X-Signature');
        if (!$key || !$ts || !$sig) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'hmac_headers_missing']);
        }

        $client = (new ApiClientModel())->where('api_key', $key)->where('active', 1)->first();
        if (!$client) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'unknown_api_key']);
        }

        $body  = (string) $request->getBody();
        $path  = '/' . ltrim($request->getUri()->getPath(), '/');
        $skew  = (int) env('app.HMAC_MAX_SKEW_SECONDS', 300);

        // X-Acting-User: "<UserID>:<UserName>" — included in the HMAC canonical
        // string so a proxy cannot rewrite it. Empty when the call is service-to-service.
        $actingHeader = $request->getHeaderLine('X-Acting-User');

        $encrypter = service('encrypter');
        try {
            $secret = $encrypter->decrypt(base64_decode($client['secret_encrypted']));
        } catch (\Throwable $e) {
            return service('response')->setStatusCode(500)->setJSON(['error' => 'secret_decrypt_failed']);
        }

        [$ok, $reason] = HmacVerifier::verify(
            $request->getMethod(), $path, $ts, $body, $sig, $secret, $skew, $actingHeader
        );
        if (!$ok) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'hmac_' . $reason]);
        }

        $ctx = [
            'type'        => 'hmac',
            'client_id'   => $client['id'],
            'client_name' => $client['name'],
        ];

        if ($actingHeader !== '') {
            // Format "id:username". Username may itself contain a colon — split on the first only.
            $colon = strpos($actingHeader, ':');
            if ($colon !== false) {
                $idPart   = substr($actingHeader, 0, $colon);
                $namePart = substr($actingHeader, $colon + 1);
                if (ctype_digit($idPart) && $namePart !== '') {
                    $ctx['acting_user_id']  = (int) $idPart;
                    $ctx['acting_username'] = $namePart;
                }
            }
        }

        ApiAuthContext::set($ctx);
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
