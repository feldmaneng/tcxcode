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
class Dtwomil extends BaseController {

 
function __construct()
{
      
 
helper('text');
 
}
 
public function index()
{
	echo "<UL>";	
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomil') . ">D2000</a></li>";
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomilone') . ">D2001</a></li>";
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomiltwo') . ">D2002</a></li>";
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomilthree') . ">D2003</a></li>";
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomilfour') . ">D2004</a></li>";
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomilfive') . ">D2005</a></li>";
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomilsix') . ">D2006</a></li>";
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomilseven') . ">D2007</a></li>";
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomileight') . ">D2008</a></li>";
	echo "<li><a href=" . site_url('Dtwomil/moveDtwomilnine') . ">D2009</a></li>";
	echo "</UL>";
	
}
	function moveDtwomil(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			$Year = 2000;
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}
	function moveDtwomilone(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			$Year = 2001;
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}
	function moveDtwomiltwo(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			$Year = 2002;
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}
	function moveDtwomilthree(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			$Year = 2003;
			
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}
	function moveDtwomilfour(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			$Year = 2004;
			
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}
	function moveDtwomilfive(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			 $Year = 2005;
			
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}
	function moveDtwomilsix(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			$Year = 2006;
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}
	function moveDtwomilseven(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			$Year = 2007;
			
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}
	function moveDtwomileight(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			$Year = 2008;
			
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
				];

		$builder->set($array);
		$builder->insert();
			
			
		 }			 
	}
	function moveDtwomilnine(){
		 $db = db_connect();
		 $builder = $db->table('contacts');
		 $builder -> where('d2000 !=', NULL);
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
		 
		 for($i=1 ; $i <= $people ; $i++){
			$n=$i-1;
			$CONTACTID=$results[$n]["ContactID"];
			$EMAIL=$results[$n]["Email"];
			$TYPE=$results[$n]["d2000"];
			$Year = 2009;
			
		 $db = db_connect();
		 $builder = $db->table('attendance');
		 $query = $builder->get();
		 
			 $array = [
			'ContactID'   => $CONTACTID,
			'Email'  => $EMAIL,
			'Type' => $TYPE,
			'Year' => $Year,
			'Event' => "Mesa",
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
 
