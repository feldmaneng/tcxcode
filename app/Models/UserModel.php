<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * UserModel — admin CRUD on the control.users table.
 * (AuthModel is kept separate for auth-only operations.)
 */
class UserModel extends Model
{
    protected $DBGroup       = 'control';
    protected $table         = 'users';
    protected $primaryKey    = 'UserID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'UserName', 'GivenName', 'FamilyName', 'Email',
        'PasswordHash', 'Active', 'MustChangePassword', 'PasswordChangedAt',
        'TOTPSecret', 'TOTPEnabled',
        'WebAuthnCredentialID', 'WebAuthnPublicKey', 'WebAuthnCounter', 'WebAuthnTransports',
        'auth_provider', 'wp_user_id', 'ProvisionedFromContactID', 'ContactID',
    ];

    public function searchPaginated(string $q, int $page, int $perPage): array
    {
        $b = $this->builder()
            ->select('UserID, UserName, GivenName, FamilyName, Email, Active, TOTPEnabled, MustChangePassword, PasswordChangedAt, auth_provider, wp_user_id, ProvisionedFromContactID, ContactID');
        if ($q !== '') {
            $b->groupStart()
                ->like('UserName', $q)
                ->orLike('GivenName', $q)
                ->orLike('FamilyName', $q)
                ->orLike('Email', $q);
            if (ctype_digit($q)) {
                $b->orWhere('UserID', (int) $q);
            }
            $b->groupEnd();
        }

        $total = (clone $b)->countAllResults(false);
        $rows = $b->orderBy('UserName', 'ASC')
            ->limit($perPage, max(0, ($page - 1) * $perPage))
            ->get()->getResultArray();
        return ['total' => $total, 'data' => $rows];
    }
}
