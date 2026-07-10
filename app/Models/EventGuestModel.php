<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * Backed by the legacy `guests` table.
 * Mapping:
 *   InvitedByCompanyID  → companyguestlists.CompanyID (the guest list this row belongs to)
 *   Type                → 'EXPO' | 'PROFESSIONAL' (counts against InviteCount/EmployeeCount)
 *   BanquetCompanyID    → nonzero => banquet attendee for that company (counts against BanquetCount)
 *   OfficeNotes         → internal notes
 *   EventYear           → copied from companyguestlists.EventYear (or Year) on insert
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
        'GivenName', 'FamilyName', 'Company', 'OfficeNotes', 'ContactID', 'Type',
        'AddedBy', 'UpdatedBy',
    ];

    /**
     * Returns counts by category for a given companyguestlists row.
     * @return array{expo:int,professional:int,banquet:int}
     */
    public function countsForCompany(int $companyGuestListsId, ?int $excludeGuestId = null): array
    {
        $exclude = $excludeGuestId !== null ? ' AND GuestID != ' . (int) $excludeGuestId : '';
        $cid = (int) $companyGuestListsId;

        $expo = $this->db->query(
            "SELECT COUNT(*) AS c FROM guests WHERE InvitedByCompanyID = ? AND Type = 'EXPO'" . $exclude,
            [$cid]
        )->getRowArray();
        $prof = $this->db->query(
            "SELECT COUNT(*) AS c FROM guests WHERE InvitedByCompanyID = ? AND Type = 'PROFESSIONAL'" . $exclude,
            [$cid]
        )->getRowArray();
        $banq = $this->db->query(
            "SELECT COUNT(*) AS c FROM guests WHERE BanquetCompanyID = ?" . $exclude,
            [$cid]
        )->getRowArray();

        return [
            'expo'         => (int) ($expo['c'] ?? 0),
            'professional' => (int) ($prof['c'] ?? 0),
            'banquet'      => (int) ($banq['c'] ?? 0),
        ];
    }
}
