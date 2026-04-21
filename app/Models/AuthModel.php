<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthModel extends Model
{
    protected $DBGroup    = 'control';
    protected $table      = 'users';
    protected $primaryKey = 'UserID';

    protected $allowedFields = [
        'TOTPSecret',
        'TOTPEnabled',
        'WebAuthnCredentialID',
        'WebAuthnPublicKey',
        'WebAuthnCounter',
        'WebAuthnTransports',
    ];

    public function findByUsername(string $username): ?array
    {
        return $this->where('UserName', $username)->first();
    }

    public function findByCredentialId(string $credentialId): ?array
    {
        return $this->where('WebAuthnCredentialID', $credentialId)->first();
    }
}
