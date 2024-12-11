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

class EXPOpopulate extends BaseController
{
	public function __construct()
        {
                
                helper('form');
				helper('text');
				helper('url');
        }

    

	private function _getDbData() {
			$db = (new ConfigDatabase())->registration;
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
	private function _getGroceryCrudEnterprise($bootstrap = true, $jquery = true) {
        $db = $this->_getDbData();

        $config = (new ConfigGroceryCrud())->getDefaultConfig();

        $groceryCrud = new GroceryCrud($config, $db);
        return $groceryCrud;
    }    
	
	private function _example_output($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo $output->output;
            exit;
        }

        return view('testconx_template.php', (array)$output);
    }
	
   
      public function check(){
		
		return view('expo_upload',['error' => ' ']);	
		}
	 //fills in the expo table and makes secret keys for companies from previous year
	public function populate(){
		
		$year = $_POST["year"];
		$yearprevious = $year - 1;
		$event = $_POST["event"];
		$db = \Config\Database::connect('registration');
		$builder = $db->table('expodirectory');
		
		$builder->where('Year', $yearprevious);
		$builder->where('Event',$event);
		
		

		$query = $builder->get();
		$company = $query->getNumRows();
		
		$results = $query->getResultArray();
		
		for($i=0;$i<$company;$i++){
		$randombytes = random_bytes(4);
		$hexkey = bin2hex($randombytes);
		//$results[$i]['EntryID']="NULL";
		unset($results[$i]['EntryID']);
		$results[$i]['Year']= $year;
		$results[$i]['SecretKey'] = "25".$hexkey;	
		$builder->insert($results[$i]);
		}
		
		//$builder->insert($results);
		
		
	  }
	public function getsecretkey(){
		$table = new \CodeIgniter\View\Table();
		$year = 2025;
		$event = "Mesa";
		$db = \Config\Database::connect('registration');
		$builder = $db->table('expodirectory');
		//$builder->select('SecretKey','CompanyName');
		$builder->select('CompanyName');
		$builder->where('Year', $year);
		$builder->where('Event',$event);
		$query = $builder->get();
		echo $table->generate($query);
		
	}
}


?>