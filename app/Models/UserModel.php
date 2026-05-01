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
    ];

    public function searchPaginated(string $q, int $page, int $perPage): array
    {
        $b = $this->builder()
            ->select('UserID, UserName, GivenName, FamilyName, Email, Active, TOTPEnabled, MustChangePassword, PasswordChangedAt');
        if ($q !== '') {
            $b->groupStart()
                ->like('UserName', $q)
                ->orLike('GivenName', $q)
                ->orLike('FamilyName', $q)
                ->orLike('Email', $q)
              ->groupEnd();
        }
        $total = (clone $b)->countAllResults(false);
        $rows = $b->orderBy('UserName', 'ASC')
            ->limit($perPage, max(0, ($page - 1) * $perPage))
            ->get()->getResultArray();
        return ['total' => $total, 'data' => $rows];
    }
}
