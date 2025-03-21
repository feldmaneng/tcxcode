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
	 
	
	function postguest()
	{
		$eventYear = "Mesa2025"; //$_POST["eventYearID"];
		
		//die ("Reaching function");
		
		/***
		Display the data keys and values for debugging purposes.
		***/
		//echo '<pre>', print_r($_POST, 1) , '</pre>';
		
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
		*/

		$fees = implode('; ',$_POST['fees']);

		// Required fields
		$tutorial = '0';
		$email = 'invalid_email';
		if (strlen($_POST['attendeesemail']) > 0) {
			$email = $_POST['attendeesemail'];
		}
				
		// Security check to make sure only certain forms are allowed
		if (!str_contains('250591362320146, 250236372630147, 250600864598161, 243396386676171', $_POST['formID'])) {
			die ("Not authorized");
		}
		
		if (($_POST['formID'] == "250591362320146") ||
			($_POST['formID'] == "250236372630147") ) {
			$type = "EXPO";
		} else {
			if (str_contains($fees,'Exhibitor'))  {
				$type = "Exhibitor"; 
			}			
			
			if (str_contains($fees,'Professional') ||
				str_contains($fees, 'Upgrade Exhibitor'))  {
				$type = "Professional"; 
			}


			if (str_contains($fees,'Tutorial'))  {
				$tutorial = '1'; 
			}		
		}
			
		// 250600864598161 Prof or Exhibitor
		// 243396386676171
		

		$data = [
			'GivenName' => $_POST['attendeesfull']['first'],
			'FamilyName' => $_POST['attendeesfull']['last'],
			'NameOnBadge' => $_POST['nameon16'],
			'Company' => $_POST['company'],
			'Email' => $email,
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
			'Fees' => $fees,
			'Control' => $_POST['control'],
			'SpecialNeeds' => $_POST['doyou'],
			'Type' => $type,
			'Tutorial' => $tutorial
		];
		

		
		//echo '<pre>', print_r($data, 1) , '</pre>';
		
		//die ("debug");
		
		
		// Connect to database and Guests table
		
		$db = \Config\Database::connect('registration');
		$builder = $db->table('guests');
			
			
			
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
			die ("Database Update Failed");
			
			
		} else {
			// success
			
		}

		if ($type === "EXPO") {
			return view('registration_complete_expo');
		} else {
			return view('registration_complete');
		}
	// return $this->_example_output($output);  

		
	


	} 

}
?>