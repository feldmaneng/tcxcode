<?php

namespace App\Models;

use CodeIgniter\Model;

class DirectoryEntry extends Model
{
	
	public function getEntry($secretKey)
	{
		$db = db_connect('registration');
		$builder = $db->table('expodirectory');
					
		$builder->where('SecretKey', $secretKey);
//		$sql = 'SELECT * FROM expodirectory Where SecretKey = ? LIMIT 1;';
//		$query =$db->query($sql, [$secretKey]);
					
		// We should check to make sure we actually returned a single row, if not die
		// However, we are only getting one row and we did use a LIMIT 1
		$query = $builder->get();
	
		if ($query->getNumRows() !== 1) {
			return NULL;
		} else {
			return $query->getRow();
		} 	
	}				
			
	public function updateEntry($secretKey, $data_update)
	{
		$db = db_connect('registration');
		$builder = $db->table('expodirectory');
					
		$builder->where('SecretKey', $secretKey);
					
		return $builder->update($data_update);

	}			
}