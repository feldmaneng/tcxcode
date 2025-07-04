<?php  



// When updating for each year, don't forget to change the random numbers at the end of
// the company and contact function calls
namespace App\Controllers;
//resync grocery
use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;
use App\Libraries\PdfLibrary;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;


$session = session(); 
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

} 
class D2000 extends BaseController {

 
function __construct()
{
      
 
helper('text');
 
}
 
public function index()
{
	echo "<UL>";	
	echo "<li><a href=" . site_url('D2000/moveD2000') . ">D2000</a></li>";
	echo "</UL>";
	
}
	function moveD2000(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('D2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$GIVENNAME=$results[$n]["GivenName"];
			$LASTNAME=$results[$n]["FamilyName"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["D2000"];
			
		 $db = db_connect();
		 $builder = $db->table('attendancetest');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'GivenName'  => $GIVENNAME,
			'FamilyName'  => $LASTNAME,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}




 

    private function _getDbData($dbgroup = 'default') {
       // $db = (new ConfigDatabase())->default;
		$db = (new ConfigDatabase())->$dbgroup;
        return [
            'adapter' => [
                'driver' => 'Pdo_Mysql',
                'host'     => $db['hostname'],
                'database' => $db['database'],
                'username' => $db['username'],
                'password' => $db['password'],
                'charset' => 'utf8'
            ]
        ];
    }
   
}
 
