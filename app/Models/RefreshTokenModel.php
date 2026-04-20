<?php
namespace App\Models;
use CodeIgniter\Model;

class RefreshTokenModel extends Model
{
    protected $DBGroup = 'control';
    protected $table = 'refresh_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id','token_hash','expires_at','revoked_at','created_at'];
}
