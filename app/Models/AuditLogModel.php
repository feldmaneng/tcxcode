<?php
namespace App\Models;
use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $DBGroup = 'control';
    protected $table = 'api_audit_log';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['client_id','user_id','method','path','payload_hash','ip','status','created_at'];
}
