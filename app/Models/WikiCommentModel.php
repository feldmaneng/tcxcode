<?php
namespace App\Models;

use CodeIgniter\Model;

class WikiCommentModel extends Model
{
    protected $DBGroup       = 'wiki';
    protected $table         = 'wiki_comments';
    protected $primaryKey    = 'CommentID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'PageID', 'ParentCommentID', 'BodyMarkdown', 'AuthorUserID', 'DeletedAt',
    ];
}
