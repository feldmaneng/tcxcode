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
        'Status', 'StatusChangedAt',
    ];

    public const STATUS_ACTIVE       = 'active';
    public const STATUS_NOT_SELECTED = 'not_selected';
    public const STATUS_WITHDRAWN    = 'withdrawn';

    public static function isHiddenStatus(?string $status): bool
    {
        return $status === self::STATUS_NOT_SELECTED || $status === self::STATUS_WITHDRAWN;
    }

    /** All presentation IDs with a hidden status (not_selected | withdrawn). */
    public function hiddenPresentationIds(): array
    {
        $rows = $this->builder()
            ->select('PresentationID')
            ->whereIn('Status', [self::STATUS_NOT_SELECTED, self::STATUS_WITHDRAWN])
            ->get()->getResultArray();
        return array_map(fn($r) => (int) $r['PresentationID'], $rows);
    }
}
