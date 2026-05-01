<?php
namespace App\Models;

use CodeIgniter\Model;

class ModuleModel extends Model
{
    protected $DBGroup       = 'control';
    protected $table         = 'modules';
    protected $primaryKey    = 'ModuleID';
    protected $returnType    = 'array';
    protected $allowedFields = ['Code', 'Name', 'Description', 'SortOrder'];
}
