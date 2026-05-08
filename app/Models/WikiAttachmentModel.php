<?php
namespace App\Models;

use CodeIgniter\Model;

class WikiAttachmentModel extends Model
{
    protected $DBGroup       = 'wiki';
    protected $table         = 'wiki_attachments';
    protected $primaryKey    = 'AttachmentID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'WikiID', 'PageID', 'StorageBucket', 'StorageKey',
        'OriginalName', 'MimeType', 'SizeBytes', 'Width', 'Height', 'UploadedBy',
    ];
}
