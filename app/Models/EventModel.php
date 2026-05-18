<?php
namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table            = 'events';
    protected $primaryKey       = 'EventID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'Year', 'Name', 'FullName',
        'StartDate', 'EndDate',
        'City', 'Facility', 'FacilityAddress',
        'EventChair1ID', 'EventChair2ID', 'EventManagerID',
    ];
}
