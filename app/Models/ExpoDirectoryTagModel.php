<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * Tag assignments for exhibitor entries (expodirectory_tags).
 *
 * Composite primary key (EntryID, TagID) — CodeIgniter models want a single
 * key, so writes go through the query builder.
 */
class ExpoDirectoryTagModel extends Model
{
    protected $DBGroup       = 'registration';
    protected $table         = 'expodirectory_tags';
    protected $primaryKey    = 'EntryID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['EntryID', 'TagID', 'AddedBy'];

    /** @param int[] $entryIds @return array<int,int[]> tag ids keyed by EntryID */
    public function tagIdsForEntries(array $entryIds): array
    {
        $entryIds = array_values(array_filter(array_map('intval', $entryIds)));
        if (!$entryIds) return [];
        $rows = $this->builder()->select('EntryID, TagID')->whereIn('EntryID', $entryIds)->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) $out[(int) $r['EntryID']][] = (int) $r['TagID'];
        return $out;
    }

    /** Replace the full tag set for an entry. @param int[] $tagIds */
    public function setForEntry(int $entryId, array $tagIds, ?int $userId): void
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        $this->builder()->where('EntryID', $entryId)->delete();
        foreach ($tagIds as $tagId) {
            try {
                $this->builder()->insert(['EntryID' => $entryId, 'TagID' => $tagId, 'AddedBy' => $userId]);
            } catch (\Throwable $e) {
                log_message('error', '[expo] tag assign failed: ' . $e->getMessage());
            }
        }
    }
}
