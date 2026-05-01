<?php
namespace App\Models;

use CodeIgniter\Model;

class AuthorModel extends Model
{
    protected $table            = 'authors';
    protected $primaryKey       = 'AuthorID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'AuthorNumber', 'Presenter', 'ContactID', 'GivenName', 'FamilyName',
        'Company', 'CompanyID', 'PresentationID',
    ];
}
