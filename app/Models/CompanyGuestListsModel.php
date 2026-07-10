<?php
namespace App\Models;

use CodeIgniter\Model;

class CompanyGuestListsModel extends Model
{
    protected $table            = 'companyguestlists';
    protected $primaryKey       = 'CompanyID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'EventYear', 'Year', 'Name', 'SecretKey', 'Company',
        'InviteCount', 'EmployeeCount', 'BanquetCount', 'StaffID',
    ];
}
