<?php 


namespace App\Controllers;

use Config\Database as ConfigDatabase;

$session = session();
/* Dont need to login 

if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

}
*/
 
class Jotformpost extends BaseController {

  
	function __construct()
	{
		//parent::__construct();
		helper('text');
		
	}
 
	public function index()
	{
		
		die("Shouldn't be here");
	
	}
	 
// Is this CRUD item?
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
 
	
	function postguest()
	{
		$eventYear = "Mesa2025"; //$_POST["eventYearID"];
		
		//die ("Reaching function");
		
		/***
		Display the data keys and values for debugging purposes.
		***/
		echo '<pre>', print_r($_POST, 1) , '</pre>';
		
		//die ("debug");
		
		/***
		Test the data if it's a valid submission by checking the submission ID.
		***/
		if (!isset($_POST['submission_id'])) {
			die("Invalid submission data!");
		}
		$sid = $_POST['submission_id'];

		/***
		## Data to Save
		
		Prepare the data to prevent possible SQL injection vulnerabilities to the database.
		
		NOTE: Add the POST data to save in your database.
		To view the submission as POST data, see https://www.jotform.com/help/51-how-to-post-submission-data-to-thank-you-page/
		***/
		/*$name = $mysqli->real_escape_string(implode(" ", $_POST['name']));
		$email = $mysqli->real_escape_string($_POST['email']);
		$message = $mysqli->real_escape_string($_POST['message']);
		*/
		/*
		$given = $_POST['attendeesfull']['first'];
		$family = $_POST['attendeesfull']['last'];
		$company = $_POST['company'];
		$email = $_POST['attendeesemail'];
		*/
		
		// Need to always fill Email address
		
		if ($_POST['formID'] == "250591362320146") {
		$data = [
			'GivenName' => $_POST['attendeesfull']['first'],
			'FamilyName' => $_POST['attendeesfull']['last'],
			'NameOnBadge' => $_POST['nameon16'],
			'Company' => $_POST['company'],
			'Email' => $_POST['attendeesemail']];
			
			/* ,
			'Title' => $_POST['jobtitle'],
			'Address1' => $_POST['address13']['addr_line1'],
			'Address2' => $_POST['address13']['addr_line2'],
			'City' => $_POST['address13']['city'],
			'State' => $_POST['address13']['state'],
			'Country' => $_POST['address13']['country'],
			'PCode' => $_POST['address13']['postal'],
			'Phone' => $_POST['workphone27'],
			'Mobile'=> $_POST['mobile28'],
			
			'SubmissionId' => $sid,
			'EventYear' => $eventYear,
			'ToPrint' => 'Yes',
			'Fees' => $_POST['fees'],
			'Control' => $_POST['control'],
			'SpecialNeeds' => $_POST['doyou']
		];
		*/ 
		
		$data['Type'] = "EXPO";
		}
		
		
		echo '<pre>', print_r($data, 1) , '</pre>';
		
		//die ("debug");
		
		
		// Connect to database and Guests table
		
		$db = \Config\Database::connect('registration');
		$builder = $db->table('guests_test');
			
			
			
		/***
		Prepare the test to check if the submission already exists in your database.
		***/
		//$sid = $mysqli->real_escape_string($_POST['submission_id']);
		//$result = $mysqli->query("SELECT * FROM $db_table WHERE submission_id = '$sid'");
		
		// Specific fields needed
		//$builder->select('NameOnBadge,GivenName,ChineseName,CN_Company,Company,Email,EventYear,FamilyName,ContactID,InvitedByCompanyID,Control,HardCopy,Tutorial,Type,Message,Dinner,PrintTime');
			
		$builder->where('SubmissionId', $sid);
		$query = $builder->get();
		
			//$people = $query->getNumRows();
			//$results = $query->getResultArray();
			
		
		/***
		## Queries to Run
		
		Perform the test and then UPDATE or INSERT the record
		depending if the submission is already in the database or not.
		
		NOTE:
		Edit the queries below according to your form and database table structure.
		For more information, see:
		- https://www.freecodecamp.org/news/the-sql-update-statement-explained/#how-do-you-use-an-update-statement
		- https://www.freecodecamp.org/news/sql-insert-and-insert-into-statements-with-example-syntax/#how-to-use-insert-into-in-sql
		***/
		

		
		// Logic to figure out if EXPO Only, Exhibitor, or Professional Reg type
		
		if ($query->getNumRows() > 0) {
			// May not want to enable updating...
			
			/* UPDATE query */
			/*
			$result = $mysqli->query("UPDATE $db_table 
				SET name = '$name',
					email = '$email', 
					message = '$message' 
				
				WHERE submission_id = '$sid'
			");
			*/
			die ("Updating not supported at this time.");
			
		}
		else {
			/* INSERT query */
			$success = $builder->insert($data);
			
		}
		
		if ($success !== TRUE) {
			echo "Database Update Failed";
			
		} else {
			// success
			
		}


	return $this->_example_output($output);  

		
	


	} 

}
?>