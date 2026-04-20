<?php
namespace App\Filters;

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
    	//log_message('error', 'HMAC: method=' . $request->getMethod() . ' path=' . $request->getUri()->getPath() . ' bodyLen=' . strlen($request->getBody() ?? ''));

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

        // HMAC verification requires the raw shared secret (cannot use a one-way hash).
        // The secret is stored encrypted at rest with CI4's Encrypter (app.encryption.key)
        // and decrypted in-memory only for signature verification.
        $encrypter = service('encrypter');
        try {
            $secret = $encrypter->decrypt(base64_decode($client['secret_encrypted']));
        } catch (\Throwable $e) {
            return service('response')->setStatusCode(500)->setJSON(['error' => 'secret_decrypt_failed']);
        }

        [$ok, $reason] = HmacVerifier::verify($request->getMethod(), $path, $ts, $body, $sig, $secret, $skew);
        if (!$ok) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'hmac_' . $reason]);
        }


        // Expose the authenticated client to controllers via request headers
        // instead of a dynamic property (deprecated in PHP 8.2+, fatal in PHP 9).
        $request->setHeader('X-Auth-Client-Id', (string) $client['id']);
        $request->setHeader('X-Auth-Client-Name', (string) $client['name']);
        $request->setHeader('X-Auth-Type', 'hmac');
        
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
