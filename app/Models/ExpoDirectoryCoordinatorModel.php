<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * Coordinators assigned to an expodirectory entry (max 4, one primary).
 *
 * Lives in the `registration` DB group (bitswork_registration) next to
 * expodirectory. `ContactID` points at contacts in the default (conference)
 * database, so it is a plain int — no cross-database foreign key.
 */
class ExpoDirectoryCoordinatorModel extends Model
{
    protected $DBGroup          = 'registration';
    protected $table            = 'expodirectory_coordinators';
    protected $primaryKey       = 'ID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = ['EntryID', 'ContactID', 'IsPrimary', 'SortOrder'];

    public const MAX_COORDINATORS = 4;

    /** @return array<int,array> coordinator rows for an entry, primary first */
    public function forEntry(int $entryId): array
    {
        return $this->builder()
            ->where('EntryID', $entryId)
            ->orderBy('IsPrimary', 'DESC')
            ->orderBy('SortOrder', 'ASC')
            ->orderBy('ID', 'ASC')
            ->get()->getResultArray();
    }

    /** @param int[] $entryIds @return array<int,array<int,array>> keyed by EntryID */
    public function forEntries(array $entryIds): array
    {
        if (!$entryIds) return [];
        $rows = $this->builder()
            ->whereIn('EntryID', $entryIds)
            ->orderBy('IsPrimary', 'DESC')
            ->orderBy('SortOrder', 'ASC')
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['EntryID']][] = $r;
        }
        return $out;
    }

    /** @return int[] expodirectory entry ids this contact coordinates */
    public function entryIdsForContact(int $contactId): array
    {
        if ($contactId <= 0) return [];
        $rows = $this->builder()
            ->select('EntryID')
            ->where('ContactID', $contactId)
            ->get()->getResultArray();
        return array_map(fn($r) => (int) $r['EntryID'], $rows);
    }

    public function isCoordinator(int $contactId, int $entryId): bool
    {
        if ($contactId <= 0) return false;
        return $this->where('ContactID', $contactId)->where('EntryID', $entryId)->countAllResults() > 0;
    }
}
