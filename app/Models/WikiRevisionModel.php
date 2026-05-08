<?php
namespace App\Models;

use CodeIgniter\Model;

class WikiRevisionModel extends Model
{
    protected $DBGroup       = 'wiki';
    protected $table         = 'wiki_revisions';
    protected $primaryKey    = 'RevisionID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'PageID', 'Title', 'BodyMarkdown', 'BodyHtml',
        'EditedBy', 'EditSummary',
    ];
}
