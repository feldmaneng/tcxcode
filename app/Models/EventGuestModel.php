<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * Backed by the legacy `guests` table.
 * Mapping:
 *   InvitedByCompanyID  → companyguestlists.CompanyID (the guest list this row belongs to)
 *   Type                → 'Professional' (Invite / Full Conference-EXPO) | 'Exhibitor' (Exhibitor Staff)
 *   BanquetCompanyID    → nonzero => banquet attendee for that company (counts against BanquetCount)
 *   OfficeNotes         → internal notes
 *   EventYear           → copied from companyguestlists.EventYear (or Year) on insert
 *   DeletedAt           → soft delete marker; all normal reads exclude these rows
 *   AddedBy/UpdatedBy   → 0 means the row was written by the public registration form
 */
class EventGuestModel extends Model
{
    protected $DBGroup          = 'registration';
    protected $table            = 'guests';
    protected $primaryKey       = 'GuestID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'EventYear', 'Email', 'InvitedByCompanyID', 'BanquetCompanyID',
        'GivenName', 'FamilyName', 'NativeName', 'Company', 'CN_Company',
        'Title', 'Mobile', 'WeChatID', 'KakaoID', 'Related', 'SignupType',
        'OfficeNotes', 'ContactID', 'Type',
        'AddedBy', 'UpdatedBy', 'AddedIP', 'UpdatedIP',
        'DeletedAt', 'DeletedBy', 'DeletedIP',
    ];

    /** Guest type constants (stored verbatim in `guests`.`Type`). */
    public const TYPE_PROFESSIONAL = 'Professional';
    public const TYPE_EXHIBITOR    = 'Exhibitor';

    /**
     * Normalizes any stored/legacy value to one of the two supported types.
     * Legacy 'EXPO' rows fall back to Professional.
     */
    public static function normalizeType($value): string
    {
        return strcasecmp(trim((string) $value), self::TYPE_EXHIBITOR) === 0
            ? self::TYPE_EXHIBITOR
            : self::TYPE_PROFESSIONAL;
    }

    /** Base builder that excludes soft-deleted rows. */
    public function live()
    {
        return $this->where('DeletedAt', null);
    }

    /**
     * Returns counts by category for a given companyguestlists row.
     * Soft-deleted rows are excluded.
     * @return array{professional:int,exhibitor:int,banquet:int}
     */
    public function countsForCompany(int $companyGuestListsId, ?int $excludeGuestId = null): array
    {
        $rows = $this->where('InvitedByCompanyID', $companyGuestListsId)
            ->where('DeletedAt', null)
            ->findAll();

        $counts = ['professional' => 0, 'exhibitor' => 0, 'banquet' => 0];
        foreach ($rows as $row) {
            if ($excludeGuestId !== null && (int) $row['GuestID'] === $excludeGuestId) continue;
            if (self::normalizeType($row['Type'] ?? '') === self::TYPE_EXHIBITOR) $counts['exhibitor']++;
            else $counts['professional']++;
            if (!empty($row['BanquetCompanyID'])) $counts['banquet']++;
        }
        return $counts;
    }
}
