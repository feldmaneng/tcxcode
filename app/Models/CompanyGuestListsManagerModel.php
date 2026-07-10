<?php
namespace App\Models;

use CodeIgniter\Model;

class CompanyGuestListsManagerModel extends Model
{
    protected $DBGroup          = 'registration';
    protected $table            = 'companyguestlists_managers';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = ['CompanyGuestListsID', 'UserID', 'AddedBy'];

    /** @return int[] user ids managing this companyguestlists row */
    public function userIdsForCompany(int $companyGuestListsId): array
    {
        $rows = $this->builder()
            ->select('UserID')
            ->where('CompanyGuestListsID', $companyGuestListsId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        return array_map(fn($r) => (int) $r['UserID'], $rows);
    }

    /** @return int[] companyguestlists ids this user can manage */
    public function companyIdsForUser(int $userId): array
    {
        $rows = $this->builder()
            ->select('CompanyGuestListsID')
            ->where('UserID', $userId)
            ->get()->getResultArray();
        return array_map(fn($r) => (int) $r['CompanyGuestListsID'], $rows);
    }

    public function userManages(int $userId, int $companyGuestListsId): bool
    {
        return $this->where('UserID', $userId)
            ->where('CompanyGuestListsID', $companyGuestListsId)
            ->countAllResults() > 0;
    }
}
