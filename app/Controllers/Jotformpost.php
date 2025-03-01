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
	
		//die ("Reaching function");
		
		/***
		Display the data keys and values for debugging purposes.
		***/
		echo '<pre>', print_r($_POST, 1) , '</pre>';
		
		
		
		/***
		Test the data if it's a valid submission by checking the submission ID.
		***/
		if (!isset($_POST['submission_id'])) {
			die("Invalid submission data!");
		}


		/***
		## Data to Save
		
		Prepare the data to prevent possible SQL injection vulnerabilities to the database.
		
		NOTE: Add the POST data to save in your database.
		To view the submission as POST data, see https://www.jotform.com/help/51-how-to-post-submission-data-to-thank-you-page/
		***/
		$name = $mysqli->real_escape_string(implode(" ", $_POST['name']));
		$email = $mysqli->real_escape_string($_POST['email']);
		$message = $mysqli->real_escape_string($_POST['message']);


		// Connect to database and Guests table
		
		$db = \Config\Database::connect('registration');
		$builder = $db->table('guests_test');
			
			
			
		/***
		Prepare the test to check if the submission already exists in your database.
		***/
		$sid = $mysqli->real_escape_string($_POST['submission_id']);
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
		
		$eventYear = "Mesa2025"; //$_POST["eventYearID"];
		
		// Logic to figure out if EXPO Only, Exhibitor, or Professional Reg type
		
		if ($query->num_rows > 0) {
			// May not want to enable updating...
			
			/* UPDATE query */
			$result = $mysqli->query("UPDATE $db_table 
				SET name = '$name',
					email = '$email', 
					message = '$message' 
				
				WHERE submission_id = '$sid'
			");
		}
		else {
			/* INSERT query */
			$result = $mysqli->query("INSERT IGNORE INTO $db_table (
				submission_id, 
				name, 
				email, 
				message
			) VALUES (
				'$sid', 
				'$name', 
				'$email',
				'$message')
			");
		}
		
		
		
		/***
		Display the outcome.
		***/
		if ($result === true) {
			echo "Success!";
		}
		else {
			echo "SQL error:" . $mysqli->error;
		}
		


	return $this->_example_output($output);  

		
	


	} 

}
?>