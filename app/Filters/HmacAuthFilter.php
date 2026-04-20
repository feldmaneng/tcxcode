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
        // We store BCRYPT(secret). Verifier needs the raw secret. We keep it
        // server-side in an env-derived per-client secret? No: bcrypt is one-way.
        // For HMAC we must store the secret recoverable. Store secret encrypted
        // with app key, or use api_clients.secret column as raw secret + restrict
        // table access. Here we store as encrypted via CI4 Encryption.
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

        // Stash auth context for downstream
        $request->apiAuth = ['type' => 'hmac', 'client_id' => $client['id'], 'client_name' => $client['name']];
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
