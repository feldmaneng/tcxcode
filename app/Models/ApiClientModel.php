<?php
namespace App\Models;
use CodeIgniter\Model;

class ApiClientModel extends Model
{
    protected $DBGroup = 'control';
    protected $table = 'api_clients';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name','api_key','secret_encrypted','active','created_at','rotated_at'];
}
