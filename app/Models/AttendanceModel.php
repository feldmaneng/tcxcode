<?php
namespace App\Models;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table            = 'attendance';
    protected $primaryKey       = 'AttendanceID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'ContactID', 'Email', 'Year', 'Event', 'Type', 'Payment',
        'Show', 'InviteCompanyID', 'EventRegID', 'Tutorial',
    ];
}
