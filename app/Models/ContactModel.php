<?php
namespace App\Models;
use CodeIgniter\Model;

/**
 * Contacts model.
 * The `contacts` table uses PascalCase columns. The API uses snake_case.
 * Translation happens in the controller via FIELD_MAP.
 *
 * Excluded from API: d2000..d2009, secret/internal fields stay accessible
 * server-side but the API surface omits the d-fields per product decision.
 */
class ContactModel extends Model
{
    protected $table          = 'contacts';
    protected $primaryKey     = 'ContactID';
    protected $returnType     = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps  = false; // table uses Stamp/Added with DB defaults
    protected $allowedFields  = [
        'DBuser','Email','GivenName','FamilyName','Nickname','AddressAs','Active',
        'NativeLanguage','NativeName','Expo_mailing','Tech_mailing','China_mailing',
        'CorrespondenceType','Email_only','Record_type','Abbr','Title',
        'CompanyID','Company','CN_Company',
        'Address1','Address2','City','State','Country','PCode','CN_Address',
        'Website','Phone','Fax','Mobile','Source','Origin','Solicitation',
        'LinkedInEmail','LinkedInURL','LinkedInGroup','WordPressID','Notes',
        'EUCountry','TechInfo','ExhibitInfo','Language',
        'EmailPermission','PostalPermission','AppPermission','EmailBounce','WeChatID',
    ];
}
