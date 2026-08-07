<?php
namespace App\Models;

use CodeIgniter\Model;

class WikiModel extends Model
{
    protected $DBGroup       = 'wiki';
    protected $table         = 'wikis';
    protected $primaryKey    = 'WikiID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['Slug', 'Name', 'Description', 'CreatedBy', 'ClosedAt'];

    /** True when the wiki exists and is closed. */
    public function isClosed(int $wikiId): bool
    {
        $row = $this->where('WikiID', $wikiId)->first();
        return $row !== null && !empty($row['ClosedAt']);
    }
}
