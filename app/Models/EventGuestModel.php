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
        'BouncedAt', 'BounceReason', 'ComplainedAt', 'EmailSuppressed',
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

    /**
     * Lossless cleanup applied before an email is stored.
     * Trims, collapses whitespace, unwraps "Name <addr@host>" and lowercases.
     * Deliberately does NOT strip +tags or dots: those are real, deliverable
     * addresses and rewriting them can misdirect mail.
     */
    public static function normalizeEmail($raw): string
    {
        $s = trim((string) $raw);
        if ($s === '') return '';
        if (preg_match('/<([^<>\s]+@[^<>\s]+)>/', $s, $m)) $s = $m[1];
        $s = preg_replace('/\s+/', '', $s) ?? $s;
        return strtolower(trim($s));
    }

    /** Rows for one event scope (EventYear), falling back to one company list. */
    private function eventScopeBuilder(string $eventYear, ?int $fallbackCompanyId)
    {
        $q = $this->builder();
        if ($eventYear !== '') $q->where('EventYear', $eventYear);
        elseif ($fallbackCompanyId) $q->where('InvitedByCompanyID', $fallbackCompanyId);
        return $q;
    }

    /**
     * A person is unique per event by email. Returns the live row already using
     * this email anywhere in the event, or null.
     */
    public function liveByEmailInEvent(string $eventYear, string $email, ?int $excludeGuestId = null, ?int $fallbackCompanyId = null): ?array
    {
        $email = self::normalizeEmail($email);
        if ($email === '') return null;
        $q = $this->eventScopeBuilder($eventYear, $fallbackCompanyId)
            ->where('DeletedAt', null)
            ->where('LOWER(TRIM(Email))', $email);
        if ($excludeGuestId !== null) $q->where('GuestID !=', $excludeGuestId);
        return $q->orderBy('GuestID', 'DESC')->limit(1)->get()->getRowArray() ?: null;
    }

    /**
     * Most recent soft-deleted row for this email in the event, so a re-add can
     * restore the person's history instead of creating a second row.
     */
    public function deletedByEmailInEvent(string $eventYear, string $email, ?int $fallbackCompanyId = null): ?array
    {
        $email = self::normalizeEmail($email);
        if ($email === '') return null;
        return $this->eventScopeBuilder($eventYear, $fallbackCompanyId)
            ->where('DeletedAt IS NOT NULL', null, false)
            ->where('LOWER(TRIM(Email))', $email)
            ->orderBy('GuestID', 'DESC')
            ->limit(1)->get()->getRowArray() ?: null;
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
