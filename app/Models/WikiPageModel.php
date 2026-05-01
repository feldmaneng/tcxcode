<?php
namespace App\Models;

use CodeIgniter\Model;

class WikiPageModel extends Model
{
    protected $DBGroup       = 'control';
    protected $table         = 'wiki_pages';
    protected $primaryKey    = 'PageID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'WikiID', 'ParentID', 'Slug', 'Title', 'SortOrder',
        'CurrentRevisionID', 'CreatedBy', 'DeletedAt',
    ];

    /** Tree of pages for a wiki, excluding soft-deleted. */
    public function treeForWiki(int $wikiId): array
    {
        return $this->where('WikiID', $wikiId)
            ->where('DeletedAt', null)
            ->orderBy('ParentID', 'ASC')
            ->orderBy('SortOrder', 'ASC')
            ->orderBy('Title', 'ASC')
            ->findAll();
    }
}
