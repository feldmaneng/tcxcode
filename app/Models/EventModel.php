<?php
namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table            = 'events';
    protected $primaryKey       = 'EventID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'Year', 'Name', 'FullName',
        'StartDate', 'EndDate',
        'City', 'Facility', 'FacilityAddress',
        'EventChair1ID', 'EventChair2ID', 'EventManagerID',
        'GeneralChairID',
        'IsClosed', 'ClosedAt',
        'GuestListEnabled', 'GolfEnabled',
        'GuestFormChinese', 'GuestFormKorean',
        'LogoID',
    ];

    /**
     * Lock rule:
     *   - IsClosed = 1 (forced closed), OR
     *   - IsClosed IS NULL AND EndDate IS NOT NULL AND today > EndDate + 7 days.
     * IsClosed = 0 = forced open (suppresses auto-lock).
     */
    public function isLocked(int $eventId): bool
    {
        $row = $this->select('IsClosed, EndDate')->find($eventId);
        if (!$row) return false;
        $isClosed = $row['IsClosed'];
        if ((int) $isClosed === 1) return true;
        if ($isClosed === null || $isClosed === '') {
            $end = $row['EndDate'] ?? null;
            if (!$end) return false;
            $endTs = strtotime((string) $end);
            if ($endTs === false) return false;
            return time() > ($endTs + 7 * 86400);
        }
        return false;
    }

    /** Returns all currently-locked event IDs (for scope checks). */
    public function lockedEventIds(): array
    {
        $cutoff = date('Y-m-d', time() - 7 * 86400);
        $rows = $this->builder()
            ->select('EventID')
            ->groupStart()
                ->where('IsClosed', 1)
                ->orGroupStart()
                    ->where('IsClosed', null)
                    ->where('EndDate IS NOT NULL', null, false)
                    ->where('EndDate <', $cutoff)
                ->groupEnd()
            ->groupEnd()
            ->get()->getResultArray();
        return array_map(fn($r) => (int) $r['EventID'], $rows);
    }

    /**
     * Event Manager for a given event year (events.EventManagerID).
     * Used to gate guest-list admin actions (token rotation, Related edits,
     * restoring soft-deleted guests, viewing removed rows).
     */
    public function managerUserIdForYear(int $year): ?int
    {
        if ($year <= 0) return null;
        $row = $this->select('EventManagerID')->where('Year', $year)->first();
        $id  = (int) ($row['EventManagerID'] ?? 0);
        return $id > 0 ? $id : null;
    }

    /** True when $userId is the event manager for $year. */
    public function isEventManagerForYear(int $userId, int $year): bool
    {
        $managerId = $this->managerUserIdForYear($year);
        return $managerId !== null && $managerId === $userId;
    }
}

