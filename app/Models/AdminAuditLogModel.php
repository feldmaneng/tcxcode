<?php
namespace App\Models;

use CodeIgniter\Model;

class AdminAuditLogModel extends Model
{
    protected $DBGroup       = 'control';
    protected $table         = 'admin_audit_log';
    protected $primaryKey    = 'AuditID';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'ActorUserID', 'Action', 'TargetType', 'TargetID', 'Details', 'IpAddress',
    ];

    public function log(?int $actorId, string $action, ?string $targetType = null, ?string $targetId = null, ?array $details = null, ?string $ip = null): void
    {
        $this->insert([
            'ActorUserID' => $actorId,
            'Action'      => $action,
            'TargetType'  => $targetType,
            'TargetID'    => $targetId,
            'Details'     => $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'IpAddress'   => $ip,
        ]);
    }
}
