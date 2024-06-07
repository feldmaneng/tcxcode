<?php
namespace App\Controllers;

use Config\Database as ConfigDatabase;





$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

}

class contactsID extends BaseController {
function findID(){
		
		
		$emailerror=0;
//$list = array_map('str_getcsv', file('listofdeaduserstest.csv'));
if (($handle = fopen("koreareg.csv", "r")) !== FALSE) {
	//while(($list = fgetcsv($handle, 1000, ",")) !==FALSE){
		
	$list = array_map('str_getcsv', file('koreareg.csv'));
	
	$idrow = array_column($list,0);
	$numrows = count($idrow);
	for ($i = 0; $i <= $numrows; $i++){
		$email =$list[$i]; 
	$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			$builder->where('Email',$email);
			
			$query = $builder->get();
			$row = $query->getResultArray(); 
			echo $email.",".$row['ContactID'] . "<br>\n";
						
					
			
			
	}
//increment keyfirst to move to the first times one row down

	}
}
}

?>