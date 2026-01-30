<?php
namespace App\Controllers;

use CodeIgniter\Files\File;
use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;






$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

}

class contactsID extends BaseController {
	
	public function __construct()
        {
                
                helper('form');
				helper('text');
				helper('url');
        }
	
	public function lookup(){
		
		return view('contactID_upload',['error' => ' ']);	
	  }
	function findID(){
			
			$file = $this->request->getFile('userfile');
			$emailerror=0;
	//$list = array_map('str_getcsv', file('listofdeaduserstest.csv'));
	//if (($handle = fopen("round3checkr2.csv", "r")) !== FALSE) {
		//while(($list = fgetcsv($handle, 1000, ",")) !==FALSE){
			
		//$list = array_map('str_getcsv', file('round3checkr2.csv'));
		$list = array_map('str_getcsv', file($file));
		
		$idrow = array_column($list,0);
		$numrows = count($idrow);
		for ($i = 0; $i < $numrows; $i++){
			$email = $list[$i]; 
		$db = \Config\Database::connect();
				$builder = $db->table('contacts');
				$builder->select('*');
				$builder->where('Email',$email);
				
				$query = $builder->get();
				
				if ( $query->getNumRows() > 0 ) {
					
				$row = $query->getResultArray(); 
				echo $email[0].", ".$row[0]['ContactID'] .", ".$row[0]['FirstName'].", ".$row[0]['LastName'] . "<br>\n";
					//print_r($row);		
				}		
				else{
					echo $email[0].", Not found <br>\n";
				}
				
		}
	//increment keyfirst to move to the first times one row down

		//}
	}
}

?>