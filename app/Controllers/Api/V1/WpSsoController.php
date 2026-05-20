<?php

namespace App\Controllers\Api\V1;

use App\Models\AuthModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Database;

/**
 * WpSsoController — verifies WordPress/s2Member SSO tokens and returns a user record.
 *
 * Token format (from WP plugin):
 *   base64url(json_payload) + "." + hex(hmac_sha256(payload, WP_SSO_SHARED_SECRET))
 *
 * Payload fields: wp_user_id (int), username (str), email (str), display_name (str),
 *                 roles (str[]), s2_level (int), s2_ccaps (str[]),
 *                 iat (int, unix), exp (int, unix), nonce (32-hex), aud (str)
 *
 * Verification:
 *   - HMAC matches with shared secret (constant-time compare)
 *   - exp > now() (with ±60s skew)
 *   - aud === expected audience (env WP_SSO_AUDIENCE)
 *   - nonce hasn't been seen before (sso_used_nonces table)
 *   - expected_nonce from caller matches token nonce (binds token to this browser session)
 *
 * On success: upsert local user keyed by wp_user_id (auto-provision), return same
 * shape as POST /auth/login so the frontend reuses its session-establishment code.
 *
 * This endpoint sits behind the same HMAC service-key auth filter as /auth/login —
 * only our TanStack server can call it.
 */
class WpSsoController extends ResourceController
{
    protected AuthModel $authModel;

    public function __construct()
    {
        $this->authModel = new AuthModel();
    }

    /**
     * POST /api/v1/auth/wp-sso/exchange
     * Body: { token: string, expected_nonce: string }
     */
    public function exchange()
    {
        $token         = (string) $this->request->getJsonVar('token');
        $expectedNonce = (string) $this->request->getJsonVar('expected_nonce');

        if ($token === '' || $expectedNonce === '') {
            return $this->failValidationErrors(['token' => 'token and expected_nonce required']);
        }

        $secret = (string) (env('WP_SSO_SHARED_SECRET') ?? getenv('WP_SSO_SHARED_SECRET'));
        $audience = (string) (env('WP_SSO_AUDIENCE') ?? getenv('WP_SSO_AUDIENCE') ?: 'tcx-office');
        if ($secret === '') {
            log_message('error', '[WpSso] WP_SSO_SHARED_SECRET not configured');
            return $this->failServerError('SSO not configured');
        }

        // ---- 1. Parse + verify HMAC ----
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return $this->failUnauthorized('malformed_token');
        }
        [$payloadB64, $sigHex] = $parts;

        $expectedSig = hash_hmac('sha256', $payloadB64, $secret);
        if (!hash_equals($expectedSig, strtolower($sigHex))) {
            log_message('warning', '[WpSso] bad_signature');
            return $this->failUnauthorized('bad_signature');
        }

        // ---- 2. Decode payload ----
        $payloadJson = $this->b64urlDecode($payloadB64);
        $payload = $payloadJson ? json_decode($payloadJson, true) : null;
        if (!is_array($payload)) {
            return $this->failUnauthorized('bad_payload');
        }

        // ---- 3. Audience check ----
        if (($payload['aud'] ?? '') !== $audience) {
            log_message('warning', '[WpSso] aud_mismatch: ' . ($payload['aud'] ?? 'null'));
            return $this->failUnauthorized('aud_mismatch');
        }

        // ---- 4. Expiry check (±60s skew) ----
        $now = time();
        $exp = (int) ($payload['exp'] ?? 0);
        $iat = (int) ($payload['iat'] ?? 0);
        if ($exp <= 0 || $exp < $now - 60) {
            return $this->failUnauthorized('token_expired');
        }
        if ($iat > $now + 60) {
            return $this->failUnauthorized('token_not_yet_valid');
        }
        if ($exp - $iat > 600) {
            // Refuse tokens with more than 10 minute lifetime — WP plugin should issue ≤120s
            return $this->failUnauthorized('token_lifetime_too_long');
        }

        // ---- 5. Nonce binding + replay protection ----
        $tokenNonce = (string) ($payload['nonce'] ?? '');
        if ($tokenNonce === '' || strlen($tokenNonce) !== 32 || !ctype_xdigit($tokenNonce)) {
            return $this->failUnauthorized('bad_nonce_format');
        }
        if (!hash_equals($tokenNonce, $expectedNonce)) {
            // Caller's expected nonce (from state cookie) doesn't match what WP signed.
            // This blocks an attacker who steals only the URL token from completing login
            // in a different browser.
            return $this->failUnauthorized('nonce_mismatch');
        }

        $db = Database::connect('control');
        // Lazy cleanup: drop nonces older than 1 hour
        $db->query('DELETE FROM sso_used_nonces WHERE used_at < (NOW() - INTERVAL 1 HOUR)');

        // Atomic claim
        try {
            $db->query('INSERT INTO sso_used_nonces (nonce) VALUES (?)', [$tokenNonce]);
        } catch (\Throwable $e) {
            // Duplicate key -> already used
            log_message('warning', '[WpSso] nonce_replay: ' . $tokenNonce);
            return $this->failUnauthorized('nonce_already_used');
        }

        // ---- 6. Required identity fields ----
        $wpUserId = (int) ($payload['wp_user_id'] ?? 0);
        $wpUsername = trim((string) ($payload['username'] ?? ''));
        $wpEmail = trim((string) ($payload['email'] ?? ''));
        $wpDisplayName = trim((string) ($payload['display_name'] ?? $wpUsername));

        if ($wpUserId <= 0 || $wpUsername === '') {
            return $this->failUnauthorized('missing_identity');
        }

        // ---- 7. Find or auto-provision local user ----
        $userBuilder = $db->table('users');
        $user = $userBuilder->where('wp_user_id', $wpUserId)->get()->getRowArray();

        if (!$user) {
            // ---- 7a. Pre-provisioned (claim) lookup ----
            // An admin may have created a placeholder row with
            // auth_provider='wordpress' AND wp_user_id IS NULL. Claim it on
            // first login by matching email (case-insensitive) or username.
            // Also claim any unclaimed row regardless of auth_provider so that
            // a previously-local row with the same email is linked rather than
            // duplicated.
            $wpEmailLc = strtolower($wpEmail);
            $claimBuilder = $db->table('users');
            $claimBuilder->where('wp_user_id', null)->groupStart();
            if ($wpEmailLc !== '') {
                $claimBuilder->where('LOWER(Email)', $wpEmailLc)
                             ->orWhere('LOWER(UserName)', strtolower($wpUsername));
            } else {
                $claimBuilder->where('LOWER(UserName)', strtolower($wpUsername));
            }
            $claim = $claimBuilder->groupEnd()->get()->getRowArray();

            // Refuse to claim accounts that have the Admin module — admins must
            // authenticate locally (password + TOTP), never via WordPress SSO.
            if ($claim && $this->userHasAdminModule($db, (int) $claim['UserID'])) {
                log_message('warning', '[WpSso] admin_claim_blocked user_id=' . $claim['UserID'] . ' wp_user_id=' . $wpUserId);
                return $this->failUnauthorized('admin_must_use_local_auth');
            }

            if ($claim) {
                $claimUpdates = [
                    'wp_user_id'    => $wpUserId,
                    'auth_provider' => 'wordpress',
                ];
                // Sync UserName from WP (source of truth). WP allows spaces;
                // preserve them verbatim — UserName is only used as an identifier
                // in JSON bodies and DB lookups, never in URLs or cache keys.
                if ($wpUsername !== '' && $wpUsername !== ($claim['UserName'] ?? '')) {
                    $claimUpdates['UserName'] = $wpUsername;
                }
                if ($wpEmail !== '' && $wpEmail !== ($claim['Email'] ?? '')) {
                    $claimUpdates['Email'] = $wpEmail;
                }
                if (empty($claim['GivenName']) && $wpDisplayName !== '') {
                    $g = $wpDisplayName; $f = '';
                    if (strpos($wpDisplayName, ' ') !== false) {
                        [$g, $f] = explode(' ', $wpDisplayName, 2);
                    }
                    $claimUpdates['GivenName']  = $g;
                    $claimUpdates['FamilyName'] = $f;
                }
                try {
                    $db->table('users')->where('UserID', $claim['UserID'])->update($claimUpdates);
                } catch (\Throwable $e) {
                    // UserName unique collision with another row — fall back to
                    // claiming without renaming so login still succeeds.
                    log_message('warning', '[WpSso] claim_username_conflict user_id=' . $claim['UserID'] . ' wp_username=' . $wpUsername . ' err=' . $e->getMessage());
                    unset($claimUpdates['UserName']);
                    $db->table('users')->where('UserID', $claim['UserID'])->update($claimUpdates);
                }
                $user = array_merge($claim, $claimUpdates);
                log_message('info', '[WpSso] wp_claim user_id=' . $user['UserID'] . ' wp_user_id=' . $wpUserId);
            } else {
                // Block silent merge with ANY existing account that shares email
                // or username (case-insensitive) — prevents duplicate-user bug.
                $conflict = null;
                if ($wpEmailLc !== '') {
                    $conflict = $db->table('users')->where('LOWER(Email)', $wpEmailLc)->get()->getRowArray();
                }
                if (!$conflict) {
                    $conflict = $db->table('users')->where('LOWER(UserName)', strtolower($wpUsername))->get()->getRowArray();
                }
                if ($conflict) {
                    log_message('warning', '[WpSso] account_collision wp_user_id=' . $wpUserId . ' username=' . $wpUsername . ' existing_user_id=' . ($conflict['UserID'] ?? '?'));
                    return $this->failUnauthorized('account_collision');
                }

                // Auto-provision (per product decision: any WP user is allowed)
                $given = $wpDisplayName !== '' ? $wpDisplayName : $wpUsername;
                $family = '';
                // Naive split on first space
                if (strpos($wpDisplayName, ' ') !== false) {
                    [$given, $family] = explode(' ', $wpDisplayName, 2);
                }

                try {
                    $db->table('users')->insert([
                        'UserName'      => $wpUsername,
                        'GivenName'     => $given,
                        'FamilyName'    => $family,
                        'Email'         => $wpEmail,
                        'PasswordHash'  => null,
                        'Active'        => 1,
                        'auth_provider' => 'wordpress',
                        'wp_user_id'    => $wpUserId,
                    ]);
                } catch (\Throwable $e) {
                    // UNIQUE constraint violation -> race condition, refuse rather than duplicate
                    log_message('warning', '[WpSso] insert_failed_unique wp_user_id=' . $wpUserId . ' err=' . $e->getMessage());
                    return $this->failUnauthorized('account_collision');
                }
                $user = $userBuilder->where('wp_user_id', $wpUserId)->get()->getRowArray();
            }
        } else {
            // Refuse login if a previously-linked WP user has since been granted
            // the Admin module — admins must use local auth only.
            if ($this->userHasAdminModule($db, (int) $user['UserID'])) {
                log_message('warning', '[WpSso] admin_login_blocked user_id=' . $user['UserID'] . ' wp_user_id=' . $wpUserId);
                return $this->failUnauthorized('admin_must_use_local_auth');
            }
            // Refresh email + UserName + ensure auth_provider is marked 'wordpress'
            // on every login (WP is source of truth for SSO-linked accounts).
            $updates = [];
            if ($wpUsername !== '' && $wpUsername !== ($user['UserName'] ?? '')) {
                $updates['UserName'] = $wpUsername;
            }
            if ($wpEmail !== '' && $wpEmail !== ($user['Email'] ?? '')) {
                $updates['Email'] = $wpEmail;
            }
            if (($user['auth_provider'] ?? 'local') !== 'wordpress') {
                $updates['auth_provider'] = 'wordpress';
            }
            if (!empty($updates)) {
                try {
                    $db->table('users')->where('UserID', $user['UserID'])->update($updates);
                    log_message('info', '[WpSso] sync_user user_id=' . $user['UserID'] . ' fields=' . implode(',', array_keys($updates)) . ' wp_username=' . $wpUsername);
                    $user = array_merge($user, $updates);
                } catch (\Throwable $e) {
                    // UserName unique collision — retry without rename so login still works.
                    log_message('warning', '[WpSso] sync_username_conflict user_id=' . $user['UserID'] . ' wp_username=' . $wpUsername . ' err=' . $e->getMessage());
                    unset($updates['UserName']);
                    if (!empty($updates)) {
                        $db->table('users')->where('UserID', $user['UserID'])->update($updates);
                        $user = array_merge($user, $updates);
                    }
                }
            } else {
                log_message('debug', '[WpSso] sync_noop user_id=' . $user['UserID'] . ' current_username=' . ($user['UserName'] ?? '') . ' wp_username=' . $wpUsername);
            }
        }

        if (!$user) {
            return $this->failServerError('user_provision_failed');
        }

        if (isset($user['Active']) && (int) $user['Active'] === 0) {
            return $this->failUnauthorized('account_disabled');
        }

        // ---- 8. Return same shape as /auth/login ----
        return $this->respond([
            'user' => [
                'id'                   => (int) $user['UserID'],
                'username'             => $user['UserName'],
                'given_name'           => $user['GivenName'] ?? $user['UserName'],
                'totp_enabled'         => (bool) ($user['TOTPEnabled'] ?? false),
                'must_change_password' => false, // never force password change on WP-SSO users
                'auth_provider'        => 'wordpress',
                'wp_user_id'           => $wpUserId,
            ],
        ]);
    }

    /**
     * Returns true if the given user has the 'admin' module assigned.
     * Admins are required to authenticate locally (password + TOTP), never via WP SSO.
     */
    private function userHasAdminModule($db, int $userId): bool
    {
        $row = $db->table('user_modules um')
            ->select('1', false)
            ->join('modules m', 'm.ModuleID = um.ModuleID')
            ->where('um.UserID', $userId)
            ->where('m.Code', 'admin')
            ->limit(1)
            ->get()
            ->getRowArray();
        return $row !== null;
    }

    private function b64urlDecode(string $s): string
    {
        $s = strtr($s, '-_', '+/');
        $pad = strlen($s) % 4;
        if ($pad) $s .= str_repeat('=', 4 - $pad);
        $r = base64_decode($s, true);
        return $r === false ? '' : $r;
    }
}
