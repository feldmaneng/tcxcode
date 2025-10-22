<?php

namespace App\Controllers;

use CodeIgniter\Files\File;
$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

} 
class test extends BaseController
{
    protected $helpers = ['form'];

    

    public function testarray()
    {
    
    
    /*
			$db = \Config\Database::connect();
			$builder = $db->table('attendance');
			//$builder->distinct('ContactID');
			$builder->select('ContactID');
			$query = $builder->get();
			$row = $query->getRowArray();
			$array = $row['ContactID'];
			echo $array;

			
		$array = array("snapple","tomato","pear");
		
		
		if(in_array("apple",$array)){
			echo "there is an apple;";
							}
							
		if(!in_array("apple",$array)){
			echo "no apple;";
			}
							
	*/
	
	echo $_SERVER['CI_ENVIRONMENT'];
	
	log_message('debug', "Debug message to be written");
	}
}

?>