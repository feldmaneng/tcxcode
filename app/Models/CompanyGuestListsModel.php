<?php
namespace App\Models;

use CodeIgniter\Model;

class CompanyGuestListsModel extends Model
{
    protected $DBGroup          = 'registration';
    protected $table            = 'companyguestlists';
    protected $primaryKey       = 'CompanyID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'EventYear', 'Year', 'Name', 'SecretKey', 'Company',
        'InviteCount', 'EmployeeCount', 'BanquetCount', 'StaffID',
        'FullConfToken', 'ExhibitorToken', 'CcPrimaryOnRegistration',
        'EventID',
    ];


    public static function newToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /** Finds a guest list by either public registration token. */
    public function findByToken(string $token, string $kind): ?array
    {
        $col = $kind === 'exhibitor' ? 'ExhibitorToken' : 'FullConfToken';
        $token = trim($token);
        if ($token === '') return null;
        $row = $this->where($col, $token)->first();
        return $row ?: null;
    }
}
