<?php
namespace App\Models;

use CodeIgniter\Model;

class WikiShareModel extends Model
{
    protected $DBGroup       = 'wiki';
    protected $table         = 'wiki_page_shares';
    protected $primaryKey    = 'ShareID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'PageID', 'Token', 'IncludeChildren', 'ExpiresAt', 'RevokedAt', 'CreatedBy',
    ];

    /** True once the wiki_page_shares table has been migrated in. */
    public function supported(): bool
    {
        static $has = null;
        if ($has === null) $has = $this->db->tableExists($this->table);
        return $has;
    }

    /**
     * Find an active share row by token.
     * Returns null if not found, revoked, or expired.
     */
    public function findActiveByToken(string $token): ?array
    {
        if (!$this->supported()) return null;
        $row = $this->where('Token', $token)
            ->where('RevokedAt', null)
            ->groupStart()
                ->where('ExpiresAt', null)
                ->orWhere('ExpiresAt >', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->first();
        return $row ?: null;
    }

    /**
     * Find an active share row whose root page is `pageId` OR is an ancestor of `pageId`
     * with IncludeChildren = 1. Walks ParentID upward, capped at 32 levels.
     *
     * Returns the share row + the resolved root PageID if any covers this page.
     */
    public function findCoveringShare(int $pageId): ?array
    {
        if (!$this->supported()) return null;
        $db = db_connect('wiki');
        $current = $pageId;
        for ($depth = 0; $depth < 32; $depth++) {
            $share = $this->where('PageID', $current)
                ->where('RevokedAt', null)
                ->groupStart()
                    ->where('ExpiresAt', null)
                    ->orWhere('ExpiresAt >', date('Y-m-d H:i:s'))
                ->groupEnd()
                ->first();

            if ($share) {
                // Direct hit on the current page is always valid.
                // For ancestors, IncludeChildren must be 1.
                if ($current === $pageId || (int) $share['IncludeChildren'] === 1) {
                    return $share;
                }
            }

            $row = $db->table('wiki_pages')
                ->select('ParentID')
                ->where('PageID', $current)
                ->get()->getRowArray();
            if (!$row || $row['ParentID'] === null) return null;
            $current = (int) $row['ParentID'];
        }
        return null;
    }

    /** @return string 22-char URL-safe base64 token */
    public static function generateToken(): string
    {
        $bytes = random_bytes(16);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
