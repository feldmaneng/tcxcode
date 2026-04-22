<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AuthModel — queries the control.users table for authentication.
 *
 * Actual column names:
 *   UserID (PK), UserName, PasswordHash, GivenName, FamilyName, Updated,
 *   TOTPSecret, TOTPEnabled, WebAuthnCredentialID, WebAuthnPublicKey,
 *   WebAuthnCounter, WebAuthnTransports
 */
class AuthModel extends Model
{
    protected $DBGroup    = 'control'; // adjust to your DB group
    protected $table      = 'users';
    protected $primaryKey = 'UserID';

    protected $allowedFields = [
        'PasswordHash',
        'TOTPSecret',
        'TOTPEnabled',
        'WebAuthnCredentialID',
        'WebAuthnPublicKey',
        'WebAuthnCounter',
        'WebAuthnTransports',
    ];

    /**
     * Find a user by username.
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('UserName', $username)->first();
    }

    /**
     * Find a user by WebAuthn credential ID.
     */
    public function findByCredentialId(string $credentialId): ?array
    {
        return $this->where('WebAuthnCredentialID', $credentialId)->first();
    }
}
