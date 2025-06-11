<?php

namespace App\Controllers;

use CodeIgniter\Files\File;

class test extends BaseController
{
    protected $helpers = ['form'];

    

    public function testarray()
    {
			$db = \Config\Database::connect();
			$builder = $db->table('attendance');
			$builder->distinct('ContactID');
			$query = $builder->get();
			$row = $query->getRowArray();
			$array = $row['ContactID'];
			
			foreach ( $array as $alsorow){
			echo $alsorow;
			}
			
		$array = array("snapple","tomato","pear");
		
		
		if(in_array("apple",$array)){
			echo "there is an apple;";
							}
							
		if(!in_array("apple",$array)){
			echo "no apple;";
							}
							
	}
}

?>