<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * Global exhibitor tags — sponsorship levels and advertising packages.
 *
 * Lives in the `registration` DB group (bitswork_registration) next to
 * expodirectory. The list is global: the same tags are reused for every event.
 */
class ExpoTagModel extends Model
{
    protected $DBGroup          = 'registration';
    protected $table            = 'expotags';
    protected $primaryKey       = 'TagID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = ['Name', 'Category', 'Sort', 'Active'];

    /** Categories the UI groups by; other values are allowed and shown as-is. */
    public const CATEGORIES = ['sponsorship', 'advertising'];

    /** @return array<int,array> all tags, active first then category/sort/name */
    public function allSorted(bool $activeOnly = false): array
    {
        $b = $this->builder();
        if ($activeOnly) $b->where('Active', 1);
        return $b->orderBy('Category', 'ASC')->orderBy('Sort', 'ASC')->orderBy('Name', 'ASC')
            ->get()->getResultArray();
    }

    public function findByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') return null;
        $row = $this->builder()
            ->where('LOWER(Name) = ' . $this->db->escape(mb_strtolower($name)), null, false)
            ->get()->getRowArray();
        return $row ?: null;
    }
}
