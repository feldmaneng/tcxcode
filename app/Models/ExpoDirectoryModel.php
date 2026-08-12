<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * Exhibitor directory entries — one row per company per event.
 *
 * Lives in the 'registration' DB group alongside companyguestlists / guests.
 * `contacts` (default group) is the source of truth for the coordinator name
 * and email; the legacy Contact* columns are kept in sync for old CI4 pages.
 */
class ExpoDirectoryModel extends Model
{
    protected $DBGroup          = 'registration';
    protected $table            = 'expodirectory';
    protected $primaryKey       = 'EntryID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'SecretKey', 'Year', 'Event', 'EventID', 'Status',
        'CompanyID', 'CompanyName', 'SampleEntry',
        'Line1', 'Line2', 'Line3', 'Line4', 'Line5', 'Line6',
        'Description', 'Upload', 'EXPOApplication', 'RegistrationDate',
        'BoothNumber', 'BoothType', 'StaffQuantity', 'StaffRegCode', 'AttendeeCode',
        'ContactID', 'ContactGivenName', 'ContactFamilyName', 'ContactEmail', 'CCEmail',
        'Notes', 'URL', 'LogoFile', 'DirectoryName',
    ];

    public const STATUSES    = ['Draft', 'Approved', 'Final', 'Canceled'];
    public const BOOTH_TYPES = ['8', '10', '2x8', '2x10', '8+10'];

    /** Fields copied forward when cloning a prior-year entry. */
    public const COPY_FIELDS = [
        'CompanyID', 'CompanyName', 'DirectoryName', 'SampleEntry',
        'Line1', 'Line2', 'Line3', 'Line4', 'Line5', 'Line6',
        'Description', 'URL', 'LogoFile',
        'ContactID', 'ContactGivenName', 'ContactFamilyName', 'ContactEmail', 'CCEmail',
    ];
}
