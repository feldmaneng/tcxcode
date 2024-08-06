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
$year=2024;
class Expo extends BaseController {

 
function __construct()
{
      
 
helper('text');
 
}


 
	public function index()
	{
		echo "<h1>TestConX EXPO - TestConX Office use only</h1>";
		echo "<h4>TestConX Confidential</h4>";
		echo "<OL>";
		echo "<LI>EXPO entries for <a href=" . site_url('/expo/list_expo_entries_suzhou') . ">Suzhou</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/expo/list_expo_entries_shenzhen') . ">Shenzhen</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/expo/list_expo_entries_shanghai') . ">Shanghai</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/expo/list_expo_entries_china') . ">China</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/expo/list_expo_entries_mesa') . ">Mesa</a></LI>";
		echo "<LI>EXPO entries for <a href=" . site_url('/expo/list_expo_entries_korea') . ">Korea</a></LI>";
		echo "</OL>";
	}
	
	private function _getGroceryCrudEnterprise($dbgroup = 'default', $bootstrap = true, $jquery = true) {
        $db = $this->_getDbData($dbgroup);

        $config = (new ConfigGroceryCrud())->getDefaultConfig();

        $groceryCrud = new GroceryCrud($config, $db);
        return $groceryCrud;
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
	
	private function _example_output($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo $output->output;
            exit;
        }

        return view('testconx_template.php', (array)$output);
    }
	
	
		public function contact1337()
		{
	$year=2024;
    //include 'singleprint.php';
    //Global $year;
    $year2=$year+1;
	/* $this->db = $this->load->database('RegistrationDataBase', TRUE);
	$this->grocery_crud->set_theme('bootstrap');
	$this->grocery_crud->set_subject('Expo');
	$this->grocery_crud->set_table('expodirectory'); */
	$crud = $this->_getGroceryCrudEnterprise('registration');
	$crud->setTable('expodirectory');
	$crud->setSubject('EXPO', 'EXPOS');
	
/* 
    	$this->grocery_crud->where('Event', 'Mesa'); 
    	$this->grocery_crud->where('Year', $year);
    	$this->grocery_crud->or_where('Year', $year2); */
		//NOTE replace 2024 with year variable
   	$crud->where(['expodirectory.Event' => 'Mesa',
					'expodirectory.Year' => '2024']);
					
/* 	$crud->where(['expodirectory.Event' => 'Mesa',
					'expodirectory.Year' => $year]); */
   //$crud->where(['expodirectory.Event' => 'Mesa']);
	
	//$crud->columns (['Year','Event','Status','CompanyName','DirectoryName']);
	//$crud->fields (['Year','Event','Status','CompanyName','DirectoryName','CompanyID','Line1','Line2','Line3','Line4','Line5','Line6','Description','Updated']);

	
	
	
	
	//
	//$this->grocery_crud->add_action('Print China2020', '', site_url('/china/singleprintchina/'),'ui-icon-image'); 
	//$crud->setActionButton('Duplicate ('.$year2.')', 'ui-icon-image', site_url('/expo/duplicate/'));
	//$this->grocery_crud->set_language("english-chinese");
	/* $crud->setActionButton('Avatar', 'fa fa-user', function ($row) {
    return '#/avatar/' . $row->Event;
});	 */
//NOTE commented out the below action to test pieces seperately
/* $crud->setActionButton('Duplicate ('.$year2.')', 'fa fa-user', function ($row) {
    return site_url('/Expo/duplicate/');
});	 */
		$output = $crud->render();

	return $this->_example_output($output);         
}




function validatefunction($key){
	
		$db = db_connect('registration');
		$builder = $db->table('expodirectory');	
		$builder->where('SecretKey', $key);


		$sql = 'SELECT * FROM expodirectory Where SecretKey = ? LIMIT 1;';
		$query =$db->query($sql, [$key]);
		$row = $query->getRow();
		$querycount = $query->getNumRows();
			if ($builder->countAllResults(false) == 1) {
				return false;
			}
			else{
				return true;
			}


}


function duplicate() {//gets the primary key of the row you're duplicating
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    $link = "https"; 
else
    $link = "http"; 
  
// Here append the common URL characters. 
$link .= "://"; 
  
// Append the host(domain name, ip) to the URL. 
$link .= $_SERVER['HTTP_HOST']; 
  
// Append the requested resource location to the URL 
$link .= $_SERVER['REQUEST_URI']; 
      
// Print the link
//need to ask ira 
echo $link;
//$id = ltrim($link, "https://www.testconx.org/tools/secure.php/expo/duplicate/");
$id = ltrim($link, "http://testconx/tools/menu.php/Expo/duplicate");
echo  $id;

$db = db_connect('registration');

$Status = 'Draft';
$year=2023;
//Global $year;
$year2 = $year+1;
$Permittedcharacters='123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
$NewSecret=substr(str_shuffle($Permittedcharacters),0,10);


while($this->validatefunction([$NewSecret]) == true){
$NewSecret=substr(str_shuffle($Permittedcharacters),0,10);
}
        //Query to the database to get the whole row
        
		//$query = $this->db->get_where('expodirectory', array('EntryID' => $id));
		$db = db_connect('registration');
		$builder = $db->table('expodirectory');
		$builder->where('EntryID' , $id);
		$query = $builder->get();
		$querycount = $query->getNumRows();
		$row = $query->getRow();
        if ($querycount > 0) {
            $res = $query->result();
            $row = $res[0];
        }
        
        //puts the data on an array
        $data = array(
            //'EntryID' => $row->ID_OBJETIVO_OPERATIVO,
            'SecretKey' => $NewSecret,
            'Year' => $year2,
            'Event' => $row->Event,
            'Status' => $Status,
            'CompanyID' => $row->CompanyID,
            'CompanyName' => $row->CompanyName,
            'SampleEntry' => $row->SampleEntry,
            'Line1' => $row->Line1,
            'Line2' => $row->Line2,
            'Line3' => $row->Line3,
            'Line4' => $row->Line4,
            'Line5' => $row->Line5,
            'Line6' => $row->Line6,
            'Description' => $row->Description,
            'Updated' => $row->Updated,// set to current time date
            'Upload' => 'Null',//set to null
            'EXPOApplication' => $row->EXPOApplication,
            'RegistrationDate' => $row->RegistrationDate,
            'BoothNumber' => $row->BoothNumber,
            'BoothType' => $row->BoothType,
            'StaffQuantity' => $row->StaffQuantity,
            'StaffRegCode' => $row->StaffRegCode,
            'AttendeeCode' => $row->AttendeeCode,
            'ContactID' => $row->ContactID,
            'ContactGivenName' => $row->ContactGivenName,
            'ContactFamilyName' => $row->ContactFamilyName,
            'ContactEmail' => $row->ContactEmail,
            'Notes' => $row->Notes,
            'URL' => $row->URL,
            'LogoFile' => $row->LogoFile,
            'DirectoryName' => $row->DirectoryName,
        );
        
        //insert the data as a new record
        $builder->insert($data);
     
        //redirects to the page you were
       // header('location:' . site_url('/objetivos/act_obj/' . $row->ID_OBJETIVO_OPERATIVO));
        exit;
    }
	function lookup_expo_entry( $CompanyID, $Year, $Event)
	{
		
		
		$db = db_connect('registration');
		$builder = $db->table('expodirectory');	
		$builder->where('CompanyID', $CompanyID);
		$builder->where('Year', $Year);
		$builder->where('Event', $Event);


		$sql = 'SELECT * FROM expodirectory Where CompanyID = ? AND Year = ? AND Event = ?;';
		$query =$db->query($sql, [$CompanyID,$Year,$Event]);
		
		$querycount = $query->getNumRows();
		
		// should probably check that there is only 1 row
		
		if ($querycount > 0) {
			if ($querycount > 1 ) {
				echo "Warning: more than row returned for CompanyID ". $CompanyID . " Year " . $Year . " Event " . $Event . "<br>";
			}
			$row = $query->getRow();
			return $row;
					
		} else {
			return FALSE;
		}
	}
		
	
	function list_expo_entries($Event, $PriorEvent = "Mesa", $Year = 2024, $PriorYear = 2023) 
	// Reads the BiTS EXPO registration database and dumps the entries
	{
		//$Year = 2022;
		//$Event = "Suzhou";
		//$PriorEvent = "Mesa";
	
		
		
		$db = db_connect('registration');
		$builder = $db->table('expodirectory');	
		
		$builder->where('Year', $Year);
		$builder->where('Event', $Event);


		$sql = 'SELECT * FROM expodirectory Where Year = ? AND Event = ? ORDER BY CompanyName ASC;';
		$query =$db->query($sql, [$Year,$Event]);
		$row = $query->getRow();
		$querycount = $query->getNumRows();
		
		
		
		echo "<h1>TestConX " . $Year . " " . $Event . " Exhibitor Directory Data</h1>";
		echo date("Y-m-d h:i:sa") . "<br>";
		echo "<br>";
		
		foreach ($query->getResultArray() as $field) {
			echo "<h2>" . $field['CompanyName'] . "</h2>";
			echo "Status: " .$field['Status'] . "<br>";
			if ( $field['Upload'] == "New" ) {
				echo "New logo file uploaded<br>";
			}
			echo "<br>";
			//ask ira
			$old = $this->lookup_expo_entry( $field['CompanyID'], ($PriorYear), $PriorEvent);
			$oldarr = (array)$old;
			//var_dump($old);
			//die($old);
			if ( $old != FALSE ) {
				$old_id = $oldarr['EntryID'];
			} else {
				$old_id = 0;
			}
			//echo "Old record = " . $old['EntryID'] . "<br>";
			
			for ($i = 1; $i <= 6 ; $i++) {
				if ( ($old_id > 0) && ( $field['Line'.$i] == $oldarr['Line'.$i])) {
					echo "  ";
				} else {
				    echo "* ";
				}
				echo $field['Line'.$i] . "<br>";
			}
			
			echo "<br>";
			if ( ($old_id > 0) && ( $field['Description'] == $oldarr['Description'] )) {
				echo "(no change)<br>";
			} else {
				echo "New or updated description:<br>";
			}
			echo $field['Description'] . "<br>";
			echo "<br>";
			
		}
			
	}	
	
	function list_expo_entries_suzhou()
	{
		$this->list_expo_entries("Suzhou");
	}
	
	function list_expo_entries_shenzhen()
	{
		$this->list_expo_entries("Shenzhen");
	}
	function list_expo_entries_shanghai()
	{
		$this->list_expo_entries("Shanghai", "Suzhou");
	}
	
	function list_expo_entries_china()
	{
		$this->list_expo_entries("China", "Suzhou");
	}
	
	function list_expo_entries_mesa()
	{
		$this->list_expo_entries("Mesa", "Mesa");
	}
	function list_expo_entries_korea()
	{
		$this->list_expo_entries("Korea", "Mesa");
	}
}