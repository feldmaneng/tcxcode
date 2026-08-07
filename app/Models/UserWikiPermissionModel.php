<?php
namespace App\Models;

use CodeIgniter\Model;

class UserWikiPermissionModel extends Model
{
    protected $DBGroup       = 'wiki';
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

    /**
     * Returns array of [WikiID, Slug, Name, Permission] for the user.
     * Closed wikis are hidden unless $includeClosed is true (admins).
     */
    public function wikisForUser(int $userId, bool $includeClosed = false): array
    {
        $b = $this->db->table('user_wiki_permissions p')
            ->select('w.WikiID, w.Slug, w.Name, w.Description, w.ClosedAt, p.Permission')
            ->join('wikis w', 'w.WikiID = p.WikiID')
            ->where('p.UserID', $userId);
        if (!$includeClosed) $b->where('w.ClosedAt IS NULL', null, false);
        return $b->orderBy('w.Name', 'ASC')->get()->getResultArray();
    }

    public function setPermission(int $userId, int $wikiId, ?string $permission): void
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
