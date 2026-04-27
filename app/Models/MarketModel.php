<?php
namespace App\Models;

use CodeIgniter\Model;

class MarketModel extends Model
{
    protected $table            = 'markets';
    protected $primaryKey       = 'MarketID';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'ParentID', 'Name', 'Slug', 'Path', 'Depth', 'Active', 'Sort',
        'Added', 'Stamp',
    ];
}
