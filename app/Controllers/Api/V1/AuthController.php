<?php

namespace App\Controllers\Api\V1;

use App\Models\AuthModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * AuthController — handles authentication endpoints.
 * All endpoints require HMAC service-key authentication (same as contacts).
 * No JWT issued — the TanStack frontend manages its own encrypted session cookies.
 *
 * Column mapping (control.users):
 *   UserID, UserName, PasswordHash, GivenName, FamilyName,
 *   TOTPSecret, TOTPEnabled, WebAuthnCredentialID, WebAuthnPublicKey,
 *   WebAuthnCounter, WebAuthnTransports
 */
class AuthController extends ResourceController
{
    protected AuthModel $authModel;

    public function __construct()
    {
        log_message('debug', '[AuthController] __construct called — controller instantiated');
        $this->authModel = new AuthModel();
    }

    /**
     * POST /api/v1/auth/login
     * Body: { username, password } or { username, skip_password: true }
     * Returns: { user: { id, username, given_name, totp_enabled } }
     */
    public function login()
    {
        log_message('debug', '[AuthController::login] *** HIT *** Method=' . $this->request->getMethod() . ' URI=' . current_url());
        log_message('debug', '[AuthController::login] Headers: ' . json_encode($this->request->headers()));
        log_message('debug', '[AuthController::login] Raw body: ' . $this->request->getBody());

        $username = $this->request->getJsonVar('username');
        $password = $this->request->getJsonVar('password');
        $skipPassword = $this->request->getJsonVar('skip_password');

        log_message('debug', '[AuthController::login] Parsed — username=' . ($username ?? 'NULL') . ' skip_password=' . ($skipPassword ? 'true' : 'false'));

        if (empty($username)) {
            return $this->failValidationErrors(['username' => 'Username is required']);
        }

        $user = $this->authModel->findByUsername($username);
        if (!$user) {
            return $this->failUnauthorized('Invalid credentials');
        }

        // skip_password is used after TOTP verification (second call)
        if (!$skipPassword) {
            if (empty($password) || !password_verify($password, $user['PasswordHash'])) {
                return $this->failUnauthorized('Invalid credentials');
            }
        }

        return $this->respond([
            'user' => [
                'id'           => (int) $user['UserID'],
                'username'     => $user['UserName'],
                'given_name'   => $user['GivenName'] ?? $user['UserName'],
                'totp_enabled' => (bool) ($user['TOTPEnabled'] ?? false),
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/totp/verify
     * Body: { username, totp_code }
     */
    public function totpVerify()
    {
        $username = $this->request->getJsonVar('username');
        $code     = $this->request->getJsonVar('totp_code');

        $user = $this->authModel->findByUsername($username);
        if (!$user || empty($user['TOTPSecret'])) {
            return $this->failUnauthorized('TOTP not configured');
        }

        $secret = $this->normalizeTotpSecret($user['TOTPSecret']);
        if ($secret === null) {
            log_message('error', '[AuthController::totpVerify] Invalid stored TOTP secret for user: ' . $username);
            return $this->respond(['verified' => false, 'message' => 'Stored TOTP secret is invalid. Reconfigure 2FA.'], 200);
        }

        try {
            $tfa = new \RobThree\Auth\TwoFactorAuth(
                new \RobThree\Auth\Providers\Qr\QRServerProvider(),
                'ContactsApp'
            );
            $valid = $tfa->verifyCode($secret, $code, 1);
        } catch (\Throwable $e) {
            log_message('error', '[AuthController::totpVerify] Verification failed: ' . $e->getMessage());
            return $this->respond(['verified' => false, 'message' => 'TOTP verification failed'], 200);
        }

        return $this->respond(['verified' => $valid]);
    }

    /**
     * POST /api/v1/auth/totp/setup
     * Body: { username, secret }
     * Stores the TOTP secret and enables TOTP for the user.
     */
    public function totpSetup()
    {
        $username = $this->request->getJsonVar('username');
        $secret   = $this->request->getJsonVar('secret');

        $user = $this->authModel->findByUsername($username);
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        // Store the base32 secret as-is (TanStack server handles generation/validation)
        $this->authModel->update($user['UserID'], [
            'TOTPSecret'  => $secret,
            'TOTPEnabled' => 1,
        ]);

        return $this->respond(['success' => true]);
    }

    /**
     * POST /api/v1/auth/totp/remove
     * Body: { username }
     */
    public function totpRemove()
    {
        $username = $this->request->getJsonVar('username');
        $user = $this->authModel->findByUsername($username);
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        $this->authModel->update($user['UserID'], [
            'TOTPSecret'  => null,
            'TOTPEnabled' => 0,
        ]);

        return $this->respond(['success' => true]);
    }

    /**
     * POST /api/v1/auth/passkey/store-challenge
     * Body: { username, challenge }
     */
    public function passkeyStoreChallenge()
    {
        $username  = $this->request->getJsonVar('username');
        $challenge = $this->request->getJsonVar('challenge');

        // Store challenge temporarily (cache or DB)
        cache()->save("webauthn_challenge_{$username}", $challenge, 300);

        return $this->respond(['success' => true]);
    }

    /**
     * POST /api/v1/auth/passkey/get-challenge
     * Body: { username }
     */
    public function passkeyGetChallenge()
    {
        $username = $this->request->getJsonVar('username');
        $challenge = cache("webauthn_challenge_{$username}");

        if (!$challenge) {
            return $this->failNotFound('No active challenge');
        }

        return $this->respond(['challenge' => $challenge]);
    }

    /**
     * POST /api/v1/auth/passkey/register-verify
     * Body: { username, credential_id, public_key, counter, transports }
     */
    public function passkeyRegisterVerify()
    {
        $username     = $this->request->getJsonVar('username');
        $credentialId = $this->request->getJsonVar('credential_id');
        $publicKey    = $this->request->getJsonVar('public_key');
        $counter      = $this->request->getJsonVar('counter');
        $transports   = $this->request->getJsonVar('transports');

        $user = $this->authModel->findByUsername($username);
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        $this->authModel->update($user['UserID'], [
            'WebAuthnCredentialID' => $credentialId,
            'WebAuthnPublicKey'    => $publicKey,
            'WebAuthnCounter'      => (int) $counter,
            'WebAuthnTransports'   => is_array($transports) ? implode(',', $transports) : $transports,
        ]);

        // Clean up challenge
        cache()->delete("webauthn_challenge_{$username}");

        return $this->respond(['success' => true]);
    }

    /**
     * POST /api/v1/auth/passkey/get-credentials
     * Body: { username }
     */
    public function passkeyGetCredentials()
    {
        $username = $this->request->getJsonVar('username');
        $user = $this->authModel->findByUsername($username);

        if (!$user || empty($user['WebAuthnCredentialID'])) {
            return $this->respond(['credentials' => []]);
        }

        return $this->respond([
            'credentials' => [
                [
                    'credential_id' => $user['WebAuthnCredentialID'],
                    'transports'    => $user['WebAuthnTransports']
                        ? explode(',', $user['WebAuthnTransports'])
                        : [],
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/passkey/auth-verify
     * Body: { credential_id, username? }
     * Returns user, credential data, and stored challenge for verification.
     */
    public function passkeyAuthVerify()
    {
        $credentialId = $this->request->getJsonVar('credential_id');
        $username     = $this->request->getJsonVar('username');

        $user = $this->authModel->findByCredentialId($credentialId);
        if (!$user) {
            return $this->failNotFound('Credential not found');
        }

        $challengeKey = $username ?: '__anonymous__';
        $challenge = cache("webauthn_challenge_{$challengeKey}");

        return $this->respond([
            'user' => [
                'id'         => (int) $user['UserID'],
                'username'   => $user['UserName'],
                'given_name' => $user['GivenName'] ?? $user['UserName'],
            ],
            'credential' => [
                'credential_id' => $user['WebAuthnCredentialID'],
                'public_key'    => $user['WebAuthnPublicKey'],
                'counter'       => (int) $user['WebAuthnCounter'],
                'transports'    => $user['WebAuthnTransports']
                    ? explode(',', $user['WebAuthnTransports'])
                    : [],
            ],
            'challenge' => $challenge,
        ]);
    }

    /**
     * POST /api/v1/auth/passkey/update-counter
     * Body: { credential_id, counter }
     */
    public function passkeyUpdateCounter()
    {
        $credentialId = $this->request->getJsonVar('credential_id');
        $counter      = $this->request->getJsonVar('counter');

        $user = $this->authModel->findByCredentialId($credentialId);
        if (!$user) {
            return $this->failNotFound('Credential not found');
        }

        $this->authModel->update($user['UserID'], [
            'WebAuthnCounter' => (int) $counter,
        ]);

        return $this->respond(['success' => true]);
    }

    /**
     * POST /api/v1/auth/change-password
     * Body: { username, current_password, new_password }
     */
    public function changePassword()
    {
        $username        = $this->request->getJsonVar('username');
        $currentPassword = $this->request->getJsonVar('current_password');
        $newPassword     = $this->request->getJsonVar('new_password');

        if (empty($username) || empty($currentPassword) || empty($newPassword)) {
            return $this->failValidationErrors(['error' => 'All fields are required']);
        }

        if (strlen($newPassword) < 12) {
            return $this->failValidationErrors(['error' => 'New password must be at least 12 characters']);
        }

        $user = $this->authModel->findByUsername($username);
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        if (!password_verify($currentPassword, $user['PasswordHash'])) {
            return $this->failUnauthorized('Current password is incorrect');
        }

        $this->authModel->update($user['UserID'], [
            'PasswordHash' => password_hash($newPassword, PASSWORD_BCRYPT),
        ]);

        return $this->respond(['success' => true]);
    }

    /**
     * POST /api/v1/auth/security-status
     * Body: { username }
     * Returns: { totp_enabled, has_passkey }
     */
    public function securityStatus()
    {
        $username = $this->request->getJsonVar('username');
        $user = $this->authModel->findByUsername($username);
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        return $this->respond([
            'totp_enabled' => (bool) ($user['TOTPEnabled'] ?? false),
            'has_passkey'  => !empty($user['WebAuthnCredentialID']),
        ]);
    }

    private function normalizeTotpSecret(?string $secret): ?string
    {
        if ($secret === null) {
            return null;
        }

        $candidates = [];
        $trimmed = trim($secret);

        if ($trimmed === '') {
            return null;
        }

        $candidates[] = $trimmed;

        if (str_starts_with($trimmed, 'otpauth://')) {
            $query = parse_url($trimmed, PHP_URL_QUERY);
            if (is_string($query)) {
                parse_str($query, $params);
                if (!empty($params['secret']) && is_string($params['secret'])) {
                    $candidates[] = $params['secret'];
                }
            }
        }

        $decoded = base64_decode($trimmed, true);
        if ($decoded !== false && is_string($decoded) && $decoded !== '') {
            $candidates[] = $decoded;
        }

        foreach ($candidates as $candidate) {
            $normalized = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $candidate) ?? '');
            if ($normalized !== '' && preg_match('/^[A-Z2-7]+$/', $normalized) === 1) {
                return $normalized;
            }
        }

        return null;
    }
}
