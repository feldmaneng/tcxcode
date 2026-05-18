<?php
namespace App\Models;

use CodeIgniter\Model;

class PresentationModel extends Model
{
    protected $table            = 'presentations';
    protected $primaryKey       = 'PresentationID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'Event', 'Year', 'Session', 'SessionID', 'PresentationNumber',
        'Title', 'TitleChinese', 'TitleKorean',
        'Wrangler', 'Topic', 'Award', 'URL', 'BaseFileName',
        'PDFLockCode', 'VideoID', 'AbstractNumber',
        'EarlyBird', 'AuthorDiscountCode', 'WranglerID',
        'AbstractEnglish', 'AbstractChinese', 'AbstractKorean',
        'BioEnglish', 'BioChinese', 'BioKorean',
    ];
}
