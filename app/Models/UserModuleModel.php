<?php
namespace App\Models;

use CodeIgniter\Model;

class UserModuleModel extends Model
{
    protected $DBGroup       = 'control';
    protected $table         = 'user_modules';
    protected $primaryKey    = 'UserID'; // composite (UserID, ModuleID); CI4 needs a single PK declared
    protected $returnType    = 'array';
    protected $allowedFields = ['UserID', 'ModuleID'];

    /** @return string[] module codes the user has */
    public function codesForUser(int $userId): array
    {
        $rows = $this->db->table('user_modules um')
            ->select('m.Code')
            ->join('modules m', 'm.ModuleID = um.ModuleID')
            ->where('um.UserID', $userId)
            ->orderBy('m.SortOrder', 'ASC')
            ->get()
            ->getResultArray();
        return array_column($rows, 'Code');
    }

    public function userHasModule(int $userId, string $code): bool
    {
        $row = $this->db->table('user_modules um')
            ->select('1', false)
            ->join('modules m', 'm.ModuleID = um.ModuleID')
            ->where('um.UserID', $userId)
            ->where('m.Code', $code)
            ->limit(1)
            ->get()
            ->getRowArray();
        return $row !== null;
    }

    public function setUserModules(int $userId, array $codes): void
    {
        $this->db->transStart();
        $this->db->table('user_modules')->where('UserID', $userId)->delete();
        if (!empty($codes)) {
            $modules = $this->db->table('modules')
                ->select('ModuleID, Code')
                ->whereIn('Code', $codes)
                ->get()
                ->getResultArray();
            $rows = [];
            foreach ($modules as $m) {
                $rows[] = ['UserID' => $userId, 'ModuleID' => (int) $m['ModuleID']];
            }
            if ($rows) {
                $this->db->table('user_modules')->insertBatch($rows);
            }
        }
        $this->db->transComplete();
    }
}
