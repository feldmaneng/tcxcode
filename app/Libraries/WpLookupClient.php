<?php
namespace App\Libraries;

use Config\Services;

/**
 * WpLookupClient — signed server-to-server client for the tcx-sso WP plugin
 * REST endpoints (/wp-json/tcx/v1/user/lookup, /user/search).
 *
 * Auth: HMAC-SHA256 over canonical string
 *   METHOD\nPATH_WITH_QUERY\nTIMESTAMP\nSHA256_HEX(body)\n
 * Headers:
 *   X-TCX-Timestamp, X-TCX-Signature
 *
 * Env:
 *   WP_LOOKUP_BASE_URL    e.g. https://www.testconx.org  (required)
 *   WP_SSO_SHARED_SECRET  (shared with WP plugin)        (required)
 *
 * Failure mode: returns null / [] on network errors and logs a warning,
 * so callers can fall back to the legacy "claim on first SSO login" path.
 * Throws RuntimeException only on misconfiguration (missing env).
 */
class WpLookupClient
{
    private string $baseUrl;     // e.g. https://www.testconx.org/premium  (no trailing slash)
    private string $basePath;    // e.g. /premium  (path portion of baseUrl, no trailing slash)
    private string $secret;

    public function __construct(?string $baseUrl = null, ?string $secret = null)
    {
        $this->baseUrl = rtrim((string) ($baseUrl ?? env('WP_LOOKUP_BASE_URL') ?? getenv('WP_LOOKUP_BASE_URL')), '/');
        $this->secret  = (string) ($secret ?? env('WP_SSO_SHARED_SECRET') ?? getenv('WP_SSO_SHARED_SECRET'));

        // Extract the URL path so subdirectory WP installs (e.g. /premium) sign
        // the same REQUEST_URI the WP plugin reconstructs server-side.
        $parsedPath = $this->baseUrl !== '' ? (parse_url($this->baseUrl, PHP_URL_PATH) ?? '') : '';
        $this->basePath = rtrim((string) $parsedPath, '/');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->secret !== '';
    }

    /** Returns the user payload array, or null if not found / unavailable. */
    public function lookupByEmail(string $email): ?array
    {
        $email = trim($email);
        if ($email === '') return null;
        $res = $this->get('/wp-json/tcx/v1/user/lookup', ['email' => $email]);
        return $res['user'] ?? null;
    }

    public function lookupByUsername(string $username): ?array
    {
        $username = trim($username);
        if ($username === '') return null;
        $res = $this->get('/wp-json/tcx/v1/user/lookup', ['username' => $username]);
        return $res['user'] ?? null;
    }

    public function lookupById(int $wpUserId): ?array
    {
        if ($wpUserId <= 0) return null;
        $res = $this->get('/wp-json/tcx/v1/user/lookup', ['wp_user_id' => (string) $wpUserId]);
        return $res['user'] ?? null;
    }

    /** Returns an array of user payloads (possibly empty). */
    public function search(string $q, int $limit = 10): array
    {
        $q = trim($q);
        if ($q === '') return [];
        $res = $this->get('/wp-json/tcx/v1/user/search', ['q' => $q, 'limit' => (string) $limit]);
        return is_array($res['users'] ?? null) ? $res['users'] : [];
    }

    /**
     * Status helper used by /check-contact-availability so the UI can distinguish
     * "WP says no such user" from "WP is unreachable".
     *
     * @return array{status: 'found'|'not_found'|'unavailable'|'unconfigured', user: ?array}
     */
    public function lookupByEmailWithStatus(string $email): array
    {
        if (!$this->isConfigured()) return ['status' => 'unconfigured', 'user' => null];
        $email = trim($email);
        if ($email === '') return ['status' => 'not_found', 'user' => null];

        $res = $this->getRaw('/wp-json/tcx/v1/user/lookup', ['email' => $email]);
        if ($res === null) return ['status' => 'unavailable', 'user' => null];
        $user = $res['user'] ?? null;
        return ['status' => $user ? 'found' : 'not_found', 'user' => $user];
    }

    // -------- internals --------

    /** @return array<string,mixed>|null Decoded JSON or null on failure. */
    private function get(string $path, array $query): ?array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('WpLookupClient: WP_LOOKUP_BASE_URL or WP_SSO_SHARED_SECRET not configured');
        }
        return $this->getRaw($path, $query);
    }

    private function getRaw(string $path, array $query): ?array
    {
        if (!$this->isConfigured()) return null;

        $qs = http_build_query($query);
        $pathWithQuery = $path . ($qs !== '' ? ('?' . $qs) : '');
        $url = $this->baseUrl . $pathWithQuery;

        // Canonical must match what the WP plugin sees in $_SERVER['REQUEST_URI'],
        // which includes the subdirectory prefix on installs like /premium.
        $signedPath = $this->basePath . $pathWithQuery;

        $ts = (string) time();
        $bodyHash = hash('sha256', '');
        $canonical = "GET\n" . $signedPath . "\n" . $ts . "\n" . $bodyHash . "\n";
        $sig = hash_hmac('sha256', $canonical, $this->secret);

        try {
            $client = Services::curlrequest([
                'timeout'         => 10,
                'connect_timeout' => 5,
                'http_errors'     => false,
            ]);
            $resp = $client->request('GET', $url, [
                'headers' => [
                    'Accept'           => 'application/json',
                    'X-TCX-Timestamp'  => $ts,
                    'X-TCX-Signature'  => $sig,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('warning', '[WpLookup] request_failed url=' . $url . ' err=' . $e->getMessage());
            return null;
        }

        $status = $resp->getStatusCode();
        $body = (string) $resp->getBody();
        if ($status < 200 || $status >= 300) {
            log_message('warning', '[WpLookup] http_' . $status . ' url=' . $url . ' body=' . substr($body, 0, 200));
            return null;
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            log_message('warning', '[WpLookup] bad_json url=' . $url);
            return null;
        }
        return $decoded;
    }
}
