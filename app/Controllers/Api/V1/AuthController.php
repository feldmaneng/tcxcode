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
        $this->authModel = new AuthModel();
    }

    /**
     * POST /api/v1/auth/login
     * Body: { username, password } or { username, skip_password: true }
     * Returns: { user: { id, username, given_name, totp_enabled } }
     */
    public function login()
    {
    
    	log_message('debug', 'Login attempt starting');
    	
        $username = $this->request->getJsonVar('username');
        $password = $this->request->getJsonVar('password');
        $skipPassword = $this->request->getJsonVar('skip_password');

        if (empty($username)) {
            return $this->failValidationErrors(['username' => 'Username is required']);
        }

        $user = $this->authModel->findByUsername($username);
        
        log_message('debug', 'Login attempt: user=' . $username . ', found=' . ($user ? 'yes' : 'no'));
		if ($user) {
			log_message('debug', 'Hash starts with: ' . substr($user['PasswordHash'], 0, 7));
			log_message('debug', 'password_verify result: ' . (password_verify($password, $user['PasswordHash']) ? 'true' : 'false'));
		}

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

        // Use a TOTP library (e.g. RobThree/TwoFactorAuth) to verify
        $tfa = new \RobThree\Auth\TwoFactorAuth('ContactsApp');
        $valid = $tfa->verifyCode($user['TOTPSecret'], $code, 1);

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

        // Encrypt the secret before storing (recommended)
        $encrypter = \Config\Services::encrypter();
        $encrypted = base64_encode($encrypter->encrypt($secret));

        $this->authModel->update($user['UserID'], [
            'TOTPSecret'  => $encrypted,
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
}
