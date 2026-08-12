<?php
namespace App\Models;

use CodeIgniter\Model;

class LogoModel extends Model
{
    protected $table = 'logos';
    protected $primaryKey = 'LogoID';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'Name',
        'Url',
        'StorageKey',
        'MimeType',
        'Width',
        'Height',
        'IsDefault',
        'IsActive',
    ];

    /** Clear any existing default flag. */
    public function clearDefault(): void
    {
        // Use the query builder directly: Model::update() without an ID
        // requires a WHERE clause or CI4 throws a DatabaseException.
        $this->builder()
            ->where('IsDefault', 1)
            ->update(['IsDefault' => 0]);
    }
}
