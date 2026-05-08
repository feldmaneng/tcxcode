<?php
namespace App\Models;

use CodeIgniter\Model;

class WikiModel extends Model
{
    protected $DBGroup       = 'wiki';
    protected $table         = 'wikis';
    protected $primaryKey    = 'WikiID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['Slug', 'Name', 'Description', 'CreatedBy'];
}
