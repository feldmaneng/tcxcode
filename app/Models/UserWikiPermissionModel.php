<?php
namespace App\Models;

use CodeIgniter\Model;

class UserWikiPermissionModel extends Model
{
    protected $DBGroup       = 'control';
    protected $table         = 'user_wiki_permissions';
    protected $primaryKey    = 'UserID'; // composite; CI4 needs a single PK
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['UserID', 'WikiID', 'Permission'];

    /** Returns 'read_comment'|'write_edit'|null */
    public function permissionFor(int $userId, int $wikiId): ?string
    {
        $row = $this->where(['UserID' => $userId, 'WikiID' => $wikiId])->first();
        return $row['Permission'] ?? null;
    }

    /** Returns array of [WikiID, Slug, Name, Permission] for the user. */
    public function wikisForUser(int $userId): array
    {
        return $this->db->table('user_wiki_permissions p')
            ->select('w.WikiID, w.Slug, w.Name, w.Description, p.Permission')
            ->join('wikis w', 'w.WikiID = p.WikiID')
            ->where('p.UserID', $userId)
            ->orderBy('w.Name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function set(int $userId, int $wikiId, ?string $permission): void
    {
        $this->where(['UserID' => $userId, 'WikiID' => $wikiId])->delete();
        if ($permission !== null && in_array($permission, ['read_comment', 'write_edit'], true)) {
            $this->insert([
                'UserID'     => $userId,
                'WikiID'     => $wikiId,
                'Permission' => $permission,
            ]);
        }
    }
}
