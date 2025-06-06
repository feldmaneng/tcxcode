<?php 
namespace App\Controllers;
//resync grocery
use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;
use App\Libraries\PdfLibrary;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Files\File;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Controller;


// Make sure you are logged in to access these functions.
$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

} 

// Some variables for each year

$session = session(); 
$year=2025;
class Presentations extends BaseController {

 
function __construct()
{
      
 
helper('text');
 
}


 
	public function index()
	{
		echo "<h1>TestConX Presentations - TestConX Office use only</h1>";
		echo "<h4>TestConX Confidential</h4>";
		echo "<OL>";
		echo "<LI>EXPO entries for <a href=" . site_url('/presentations/general') . ">General</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/presentations/mesa') . ">Mesa</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/presentations/china') . ">China</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/presentations/korea') . ">Korea</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/presentations/authors') . ">Authors</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/presentations/attendance') . ">Attendance</a></LI>";
		echo "</OL>";
	}
	
	private function _getGroceryCrudEnterprise($dbgroup = 'default', $bootstrap = true, $jquery = true) {
        $db = $this->_getDbData($dbgroup);

        $config = (new ConfigGroceryCrud())->getDefaultConfig();

        $groceryCrud = new GroceryCrud($config, $db);
        return $groceryCrud;
    }    
	
	/* private function _getDbData($dbgroup = 'default') {
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
    } */
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
	
	
	private function _example_output($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo $output->output;
            exit;
        }

        return view('testconx_template.php', (array)$output);
    }



public function mesa()
		{
	$year=2025;
    
    $year2=$year+1;
	
	$crud = $this->_getGroceryCrudEnterprise();
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());
	$crud->setTable('presentations');
	$crud->setSubject('Presentation', 'Presentations');
	

		
   $crud->where(['presentations.Event = ?' => 'Mesa','presentations.Year >= ?' => $year]);
					
					$crud->columns (['PresentationID','Year','Event','Session','PresentationNumber','Title']);
	
					
$crud->setUniqueId('mesa_2025');

		$output = $crud->render();

	return $this->_example_output($output);         
}

public function general()
		{
	
	
	$crud = $this->_getGroceryCrudEnterprise();
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());
	$crud->setTable('presentations');
	$crud->setSubject('Presentation', 'Presentations');
	

		
 
					
	$crud->columns (['PresentationID','Year','Event','Session','PresentationNumber','Title','VideoID']);
	
					
	$crud->setUniqueId('general_2025');

		$output = $crud->render();

	return $this->_example_output($output);         
}

public function china()
		{
	$year=2025;
    
    $year2=$year+1;
	
	$crud = $this->_getGroceryCrudEnterprise();
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());
	$crud->setTable('presentations');
	$crud->setSubject('Presentation', 'Presentations');
	

		
   $crud->where(['presentations.Event = ?' => 'China','presentations.Year >= ?'=> $year]);
$crud->columns (['PresentationID','Year','Event','Session','PresentationNumber','Title']);
			
		
$crud->setUniqueId('china_2025');
 
		$output = $crud->render();

	return $this->_example_output($output);         
}

public function korea()
		{
	$year=2025;
    
    $year2=$year+1;
	
	$crud = $this->_getGroceryCrudEnterprise();
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());
	$crud->setTable('presentations');
	$crud->setSubject('Presentation', 'Presentations');
	

		
   $crud->where(['presentations.Event = ?' => 'Korea','presentations.Year >= ?'=> $year]);
					
	$crud->columns (['PresentationID','Year','Event','Session','PresentationNumber','Title']);
		//$crud->fields (['PresentationID','Year','Event','Session','PresentationNumber','Title','TitleChinese','TitleKorea','Wrangler','Topic','Award','URL','BaseFileName','PDFLockCode','VideoID','AbstractNumber','EarlyBird','AuthorDiscountCode','WranglerID','AbstractEnglish','AbstractChinese','AbstractKorean','BioEnglish','BioChinese','BioKorean']);
	$crud->setUniqueId('korea_2025');
	

		$output = $crud->render();

	return $this->_example_output($output);         
}
public function authors()
		{
	
	
	$crud = $this->_getGroceryCrudEnterprise();
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());
	$crud->setTable('authors');
	$crud->setSubject('author', 'authors');
	
$crud->setUniqueId('authors_2025');
		
   //$crud->where(['presentations.Event = ?' => 'China','presentations.Year >= ?'=> $year]);
					

 
		$output = $crud->render();

	return $this->_example_output($output);         
}
public function attendance()
		{
	
	
	$crud = $this->_getGroceryCrudEnterprise();
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());
	$crud->setTable('attendance');
	$crud->setSubject('antendee', 'attendees');
	
$crud->setUniqueId('attendance_2025');
		
   //$crud->where(['presentations.Event = ?' => 'China','presentations.Year >= ?'=> $year]);
					

 
		$output = $crud->render();

	return $this->_example_output($output);         
}
}
?>