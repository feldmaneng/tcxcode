<?php
namespace App\Models;

use CodeIgniter\Model;

class CompanyModel extends Model
{
    protected $table            = 'company';
    protected $primaryKey       = 'CompanyID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'Name', 'ParentID', 'IsParent', 'Active', 'CN_Name', 'URL',
        'Stock_Market', 'Ticker_Symbol', 'Research_link', 'Notes',
        'Added', 'Stamp',
    ];
}
