<?php
namespace App\Models;

use CodeIgniter\Model;

class SessionModel extends Model
{
    protected $table            = 'sessions';
    protected $primaryKey       = 'SessionID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'EventID', 'SessionNumber', 'SessionName',
        'Coordinator1ID', 'Coordinator2ID',
        'StartTime', 'EndTime', 'Room',
    ];
}
