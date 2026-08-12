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
        $this->set('IsDefault', 0)->update();
    }
}
