<?php  

namespace App\Controllers;

use Config\Database as ConfigDatabase;





$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

}

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

// Highest s2member ID
define("MAXUSER", "10000");

//require '/../bitscode/Kint/Kint.class.php';
//include (substr($_SERVER["DOCUMENT_ROOT"], 0, stripos($_SERVER["DOCUMENT_ROOT"],"public_html")) ."bitscode/Kint/Kint.class.php");
//require 'kint.phar';
//include (substr($_SERVER["DOCUMENT_ROOT"], 0, stripos($_SERVER["DOCUMENT_ROOT"],"public_html")) ."bitscode/Kint/build/kint.phar");
		
class Smember extends BaseController {

 
	function __construct()
	{
		helper('text');
		
/* IMF - Load our API keys */
		global $BiTS_api_keys;
		//echo substr($_SERVER["DOCUMENT_ROOT"], 0, stripos($_SERVER["DOCUMENT_ROOT"],"tcxcode")) ."/home/testconx/secure/api_keys.php";
	echo $_SERVER["DOCUMENT_ROOT"] . "/../secure/api_keys.php";
			//include (substr($_SERVER["DOCUMENT_ROOT"], 0, stripos($_SERVER["DOCUMENT_ROOT"],"tcxcode")) ."/home/testconx/secure/api_keys.php");
		include ($_SERVER["DOCUMENT_ROOT"] . "/../secure/api_keys.php");
		


	}
 
	public function index()
	{
		//$this->load->view('multidemoupload_zoomform', array('error' => ' ' ));
		echo "<h1>s2member Helper Tools</h1>";
		echo "<OL>";
		echo "<LI>Test <a href=" . site_url('/smember/show_user') . ">one ID</a></LI>";
		echo "<LI>Test <a href=" . site_url('/smember/find_user_list') . ">User List from file to delete users and bots from s2</a></LI>";
		echo "<br>";
		echo "<LI>Cross check WordPress/s2 members to BiTS DB - ignores s2members with BiTS ID already<br>";
		echo "<a href=" . site_url('/smember/crosscheck_users1') . ">1-499</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users500') . ">500-999</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users1000') . ">1000-1499</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users1500') . ">1500-1999</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users2000') . ">2000-2499</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users2500') . ">2500-2999</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users3000') . ">3000-3499</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users3500') . ">3500-3999</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users4000') . ">4000-4500</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users4500') . ">4500-5000</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users5000') . ">5000-5500</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users5500') . ">5500-6000</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users6000') . ">XXXX-6500</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users6500') . ">XXXX-7000</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users7000') . ">XXXX-7500</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users7500') . ">XXXX-8000</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users8000') . ">XXXX-8500</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users8500') . ">XXXX-9000</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users9000') . ">XXXX-9500</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users9500') . ">XXXX-10000</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users10000') . ">10000-Max</a> ";
		echo "<a href=" . site_url('/smember/crosscheck_users_all') . ">all</a> ";
		echo "</LI>";
		echo "<LI>Redo/recheck cross check WordPress/s2 members to BiTS DB - forces full recheck even s2members with BiTS ID<br>";
		echo "<a href=" . site_url('/smember/recheck_crosscheck1') . ">1-499</a> ";
		echo "<a href=" . site_url('/smember/recheck_crosscheck500') . ">500-999</a> ";
		echo "<a href=" . site_url('/smember/recheck_crosscheck1000') . ">1000-1499</a> ";
		echo "<a href=" . site_url('/smember/recheck_crosscheck1500') . ">1500-1999</a> ";
		echo "<a href=" . site_url('/smember/recheck_crosscheck2000') . ">2000-2499</a> ";
		echo "<a href=" . site_url('/smember/recheck_crosscheck_all') . ">all</a> ";
		echo "</LI>";
		
		echo "<p>Note: all may not work due to server time out limit(s)</p>";
		echo "<LI>Reset alll China users (s2member Level 1) to Level 0- guests<br>";
		echo "<a href=" . site_url('/smember/reset_china_1') . ">1-100</a> ";
		echo "<a href=" . site_url('/smember/reset_china_all') . ">all - 1-".MAXUSER."</a><br>";
		//echo "<a href=" . site_url('/s2_match_db/reset_china_500') . ">500-999</a> ";
		//echo "<a href=" . site_url('/s2_match_db/reset_china_1000') . ">1000-1499</a> ";
		//echo "<a href=" . site_url('/s2_match_db/reset_china_1500') . ">1500-1999</a> ";
		echo "Note this may take about 20 minutes without any output until end...";
		echo "</li>";
		
		echo "<li><a href=" . site_url('/smember/reset_mesa_all') . ">Reset Mesa users to either Level 0 or 1</a></li>";

		echo "<li><a href=" . site_url('/smember/set_china_users') . ">Set China event users</a></li>";
		echo "<li><a href=" . site_url('/smember/set_korea_users') . ">Set Korea event users</a></li>";
		
		echo "<li><a href=" . site_url('/smember/set_mesa_users') . ">Set Mesa event users</a></li>";
		
		echo "<li><a href=" . site_url('/smember/preview_add_to_database') . ">Preview of Add missing s2 users to BiTS database</a></li>";
		echo "<li><a href=" . site_url('/smember/add_to_database') . ">Add missing s2 users to BiTS database</a></li>";
		echo "<li><a href=" . site_url('/smember/write_mailchimp_with_Chinese') . ">Write CSV for MailChimp WITH s2 usernames, s2 emails, & Chinese Names</a></li>";

		
		//return view('multidemoupload_zoomform');
		// Was for a one time need - may be useful for fixing mistakes in future
		// echo "<li><a href=" . site_url('/s2_match_db/clean_ccaps') . ">Clean s2member_ccaps</a></li>";
	}

//takes in a list spits out a list with emails, pass array of ids
	function find_user_list(){
		
		
		$emailerror=0;
		$deleteusers = "1_31delete.csv";
//$list = array_map('str_getcsv', file('listofdeaduserstest.csv'));
if (($handle = fopen($deleteusers, "r")) !== FALSE) {
	//while(($list = fgetcsv($handle, 1000, ",")) !==FALSE){
		
	$list = array_map('str_getcsv', file($deleteusers));
	
	$idrow = array_column($list,0);
	$numrows = count($idrow); 
//increment keyfirst to move to the first times one row down

	
	
	
	
		
		for ($c=0; $c < $numrows; $c++) {

	
				
    $result = $this->s2_get_user_by_id($list[$c][0]);
	
		if ($result && empty($result['error'])) {
			echo "<pre>";
			echo $result['data']['ID'].",".$result['data']['user_email'];
				if($result['data']['user_email']!=$list[$c][1]){
				
				echo ',<strong>email mismatch </strong>';
				
				$emailerror++;
				} else{
					$result = $this->s2_delete_user_by_id($list[$c][0]);
					// Add error checking here for the delete operation
					if ($result && empty($result['error']) && !empty($result['ID'])) {
						echo ',Success Deleted user ID: '.$result['ID'];
					}
					if(!empty($result['error'])) {
						echo ',Delete Failed : '.$result['error'];
					}
				}					// Print full array.
			echo "</pre>";
			 

		} elseif (!empty($result['error'])) {
			echo "<pre>";
			echo 'API error reads: '.$result['error'];
			echo "</pre>";
		}
		
		}
		
			
		
		
		
		
	//}
	echo "<pre>";
	echo "Number of mismatched emails: ".$emailerror;
	echo "</pre>";
}
	
	
	
		
	}
	
	
	
	private function diag_log ($message) {
		$message = "[" . date("Y-M-D H:i:s e") . "] " . basename(__file__, '.php') . ": " . $message . "\n";
		error_log ($message, 3, substr($_SERVER["DOCUMENT_ROOT"], 0, stripos($_SERVER["DOCUMENT_ROOT"],"public_html")) ."log_public/diagnostic_log");	
	}
	
	private function echo_diag_log ($message) {
		$this->diag_log ($message);
		echo "<p>" . $message . "</p>\n";
	}
	
	private function s2_api($operation, $data)
	{
		global $BiTS_api_keys;
		
		if (! empty($operation)) {
			$op["op"] = $operation;
			$op["data"] = $data;
			$op["api_key"] = $BiTS_api_keys['s2_api'];  // Loaded via the api_keys.php from secure
			// See: `s2Member → API / Scripting → Remote Operations API → API Key`
			
			$post_data = stream_context_create(array('http' => array('method' => 'POST', 'header' => 'Content-type: application/x-www-form-urlencoded', 'content' => 's2member_pro_remote_op='.urlencode(json_encode($op)))));
			$result    = json_decode(trim(file_get_contents('https://www.testconx.org/premium/?s2member_pro_remote_op=1', false, $post_data)), true);

		
		} else {
			$result['error'] = "Empty operation";
		}
		return $result;
		
	}
	
	// A User ID to query the database for.
    private function s2_get_user_by_id($id)
	{
		$data = array(
			"user_id" => $id
		);
		
		
		return $this->s2_api("get_user", $data);
	}
	   
	private function s2_delete_user_by_id($id)
	{
		$data = array(
			"user_id" => $id
		);
		return $this->s2_api("delete_user", $data);
	}

	// Lookup a person by username
    private function s2_get_user_by_username($username)
	{
		$data = array(
			"user_login" => $username
		);
		return $this->s2_api("get_user", $data);
	}

	// Lookup a person by email
    private function s2_get_user_by_email($email)
	{
		$data = array(
			"user_email" => $email
		);
		return $this->s2_api("get_user", $data);
	}
	
	// Returns s2 user ID if username exists
	// otherwise returns FALSE
    private function s2_check_username($username)
	{
		$status = $this->s2_get_user_by_username($username);
		if (! empty ($status['error'])) {
			return FALSE;
		}
		
		return $status['ID'];
	}	
	
	// Returns s2 user ID if email is in use
	// otherwise returns FALSE
    private function s2_check_email($email)
	{
		$status = $this->s2_get_user_by_email($email);
		if (! empty ($status['error'])) {
			return FALSE;
		}
		
		return $status['ID'];
	}	
		
	
	// Work to find an avaiable user name and return it
	// if no combination works returns FALSE
	private function s2_generate_username($given, $family, $ID) {
		// Protect against sloppy data entry
		$given = trim($given);
		$family = trim($family);
		// Deal with multipart names
		$given = str_replace(" ","_", $given);
		$family = str_replace(" ","_", $family);
		$username = $given . "-" . $family;
		if ($this->s2_check_username($username) ) {
			// Don't use spaces in usernames - creates parsing problems
			//$username = $given . " " . $family;
			//if ($this->s2_check_username($username) ) {
				$username = $given . "-" . $family . $ID;
				if ($this->s2_check_username($username) ) {
					return FALSE;
				}
			//}
		}
		return $username;
	}
	
	// Update the s2 user all fields
	private function s2_update_user ($data)
	{
		
		$result = $this->s2_api("modify_user", $data);
		if ($result && empty($result['error']) && !empty($result['ID'])) {
			return "all user fields updated";
		} elseif (!empty($result['error'])) {
			return 'API error reads: '.$result['error'];
		}

	}

	// Update the s2 user level
	private function s2_update_user_level ($id, $user_level)
	{
		$data = array(
			"user_id" => $id,
			's2member_level' => $user_level
		);
		$result = $this->s2_api("modify_user", $data);
		if ($result && empty($result['error']) && !empty($result['ID'])) {
			return TRUE;
		} elseif (!empty($result['error'])) {
			return 'API error reads: '.$result['error'];
		}
	
	}
	

	// Update the s2 user custom_fields
	private function s2_update_user_custom_fields ($id, $custom_fields)
	{
		$data = array(
			"user_id" => $id,
			"custom_fields" => $custom_fields
		);
		$result = $this->s2_api("modify_user", $data);
		if ($result && empty($result['error']) && !empty($result['ID'])) {
			return "Custom fields updated";
		} elseif (!empty($result['error'])) {
			return 'API error reads: '.$result['error'];
		}
	
	}
	
	// Create a new user - assuming that $username and $email has already been checked to not be in use
	private function s2_create_user($username, $email, $BiTS_ID, $password, $given, $family) {
		$data = array(
			'user_login' => $username,
			'user_email' => $email,
			'user_pass' => $password,
			'first_name' => $given,
			'last_name' => $family,
			'custom_fields' => array('bits_id' => $BiTS_ID,
									 'bits_regular_email' => $email),
			'modify_if_login_exists' => '0', // do NOT modify existing
			'notification' => '0' // do NOT send notification
		);
		//print_r($data);
		$result = $this->s2_api("create_user", $data);
		if ($result && empty($result['error']) && !empty($result['ID'])) {
			return $result['ID'];
		} elseif (!empty($result['error'])) {
			$this->echo_diag_log ("Add user failed - API error reads: ".$result['error']);
			return FALSE;
		}
			
	}
	
	
	// source https://stackoverflow.com/questions/1837432/how-to-generate-random-password-with-php
	private function generatePassword($length = 8) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$count = mb_strlen($chars);

		for ($i = 0, $result = ''; $i < $length; $i++) {
			$index = rand(0, $count - 1);
			$result .= mb_substr($chars, $index, 1);
		}
		return $result;
	}
	
	// Remove prior hacks to s2_member_ccaps of using just a "t"
	// And change s2015 for Shanghai to c2015
	// take the ccaps as an array and returns a comma deliminated string
	private function clean_s2_member_ccaps ($cap_array) {
		if (!empty($cap_array) ) {
			$this->diag_log("ccap before:\t" . print_r($cap_array, true));
			//$cap_array = str_getcsv($ccaps);
		
			foreach ($cap_array as $key => $value ) {
				if ($value === "t") {
					unset($cap_array[$key]);
				}
				if ($value === "s2015") {
					$cap_array[$key] = "c2015";
				}
				/* if ($value === "c2017") {
					unset($cap_array[$key]);
				} */
			}
		
			$output = "";
			foreach ($cap_array as $value ) {
				if (!empty($output)) {
					$output .= ",";
				}
				$output .= $value;
			}

			$this->diag_log("ccap after:\t" . $output);
		
			return $output;
			
		} else {
			return;
		}
	}


	// Find the Person Record with a given Email
	// Returns ConactID if one person is found
	// Returns FALSE if not found or empty email
	// *
	private function LookupPersonByEmail($email, $p_ReportNotFound = true) 
	{
		//$email = "15024354542@163.com";
		//echo "<p>Checking email " . $email . "*</p>";
		//dump_string ($email);
		//echo "Encoding: " . mb_detect_encoding($email) . "<br>"; //, 'UTF8');
		
		if (strlen($email) > 0 ) {
			$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			$builder->where('Email',$email);
			
			$query_people = $builder->get();
			//echo "<p>" . $this->db->last_query() . "</p>";
			if ( $query_people->getNumRows() > 0 ) {
				// An email ID shouldn't be on more than one person, so we shouldn't have more than one row
				if ( $query_people->getNumRows() > 1 ) {
					echo "Warning: Email query on ". $email . " yielded " .$query_people->getNumRows() . "rows. (Multiple people.) <br>";
					foreach ($query_people->getResult() as $row) {
						echo "	ContactID ". $row->ContactID . "<br>\n\n\n\n";
					}
					//die();
				}
				$row = $query_people->getResultArray();
				//echo "	  ContactID: " . $row['ContactID'] . "<br>";
				return $row[0]['ContactID'];
			} else {				
				// didn't find the a person with that email address
				if ($p_ReportNotFound) {
					echo "Did not find: " . $email . "*<br>";
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}	

	// Find the Person Record with a given LinkedInEmail
	// Returns ConactID if one person is found
	// Returns FALSE if not found or empty email
	// *
	private function LookupPersonByLinkedInEmail($email, $p_ReportNotFound = true) 
	{
		//$email = "15024354542@163.com";
		//echo "<p>Checking email " . $email . "*</p>";
		//dump_string ($email);
		//echo "Encoding: " . mb_detect_encoding($email) . "<br>"; //, 'UTF8');
		
		if (strlen($email) > 0 ) {
			
			$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			$builder->where('LinkedInEmail',$email);
			
			
			
			$query_people = $builder->get();
			//echo "<p>" . $this->db->last_query() . "</p>";
			if ( $query_people->getNumRows() > 0 ) {
				// An email ID shouldn't be on more than one person, so we shouldn't have more than one row
				if ( $query_people->getNumRows() > 1 ) {
					echo "Warning: LinkedInEmail query on ". $email . " yielded " .$query_people->getNumRows() . "rows. (Multiple people.) <br>";
					foreach ($query_people->getResult() as $row) {
						echo "	ContactID ". $row->{'ContactID'} . "<br>\n\n\n\n";
					}
					//die();
				}
				$row = $query_people->getResultArray();
				//echo "	  ContactID: " . $row['ContactID'] . "<br>";
				return $row[0]['ContactID'];
			} else {				
				// didn't find the a person with that email address
				if ($p_ReportNotFound) {
					echo "Did not find: " . $email . "* in LinkedInEmail<br>";
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}	
	

	// Find the Person Record by Given & Family name
	// Returns ConactID if one person is found
	// $use_nickname uses Nickname field instead of GivenName field
	// Returns FALSE if not found or empty email
	// *

	private function LookupPersonByName($given, $family, $use_nickname = FALSE) //$p_ReportNotFound = true) 
	{
		//$contactID = FALSE;
		
		if ((strlen($family) > 0 ) && (strlen($given) > 0)) {
		
			$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			
			
			
		
			if (! $use_nickname) {
				$builder->where('GivenName',$given);	
			} else {
			$builder->where('Nickname',$given);	
					
			}		
			$builder->where('FamilyName',$family);	
			
			$query_people = $builder->get();
			
			
			if ( $query_people->getNumRows() > 0 ) {
				
				if ( $query_people->getNumRows() > 1 ) {
					echo "Warning: Query on ". $given . " ". $family . " yielded " .$query_people->getNumRows() . "rows. (Multiple people.) <br>";
					foreach ($query_people->getResult() as $row) {
						echo "	ContactID ". $row->ContactID . "<br>\n\n\n\n";
					}
					//die();
				}
				$row = $query_people->getResultArray();
				//echo "	  ContactID: " . $row['ContactID'] . "<br>";
				//return $row->ContactID;
				//return print_r($row);
				return $row[0]['ContactID'];
			} 
			
				/* else {				
				// didn't find a person who matched
				if ($p_ReportNotFound) {
					echo "Did not find: " . $given . " ". $family . "*<br>";
				}
				return FALSE;
			} */
			
		} 
		
		return FALSE; //$contactID;
	}	
	
	// Given a ContactID, lookup the WordPressID in the BiTS Database
	// ContactID is set to unique, so only 1 row is available
	private function LookupWordPressID($ID)
	{
		$db = \Config\Database::connect();
		$builder = $db->table('contacts');
		$builder->where('ContactID', $ID);
		$query_people = $builder->get();
		$row = $query_people->getResultArray();
		
		
		
		//return $row->WordPressID;
		return $row[0]['WordPressID'];
	}

	private function WriteWordPressID($ID, $wp_ID)
	{
		//$row = array();
		
	/* 	$db = \Config\Database::connect();
		$builder = $db->table('contacts');
		$builder->select('WordPressID');
		
		$builder->where('ContactID =', (int)$ID);
		$query = $builder->get();
		//should be a row not array
		$row = $query->getResultArray();
		
		
		
		$row['WordPressID'] = $wp_ID;
		$builder->where('ContactID =', (int)$ID);
		//$this->db->where('ContactID', $ID);	
		return $builder->update($row); */
		$data = [
			'WordPressID' => $wp_ID
    
			];
		$db = \Config\Database::connect();
		$builder = $db->table('contacts');
		$builder->where('ContactID',$ID);
		$builder->update($data);
		
	}

	// Parse the s2member user record until we find the person in the BiTS Database
	private function find_BiTS_ID($s2user_record, $use_name = FALSE) {
		// Try email first
		//echo "<p>Checking for email " . $s2user_record['data']['user_email'] . "</p>";
		
		$BiTS_ID = $this->LookupPersonByEmail($s2user_record['data']['user_email'],FALSE);
		
		if ( (! $BiTS_ID ) && (! empty ($s2user_record['s2member_custom_fields']['bits_regular_email'] ) )) {
			// next checks their bits_regular, if it exists
			$BiTS_ID = $this->LookupPersonByEmail($s2user_record['s2member_custom_fields']['bits_regular_email'],FALSE);
		}
		
		// No luck? Try matching against their LinkedIn email address in the BiTS Database
		if (! $BiTS_ID ) {
			$BiTS_ID = $this->LookupPersonByLinkedInEmail($s2user_record['data']['user_email'],FALSE);

		}	
		
		/* Next logical check would be via First & Last */
		if ((! $BiTS_ID ) && ( $use_name )) {
			//d($s2user_record);
			// It appears that first_name and last_name are no longer in the s2 record as 0f 3/19/19
			// they may have never been there. So we should parse them ourselves from the display_name
			//if ( (strlen($s2user_record['data']['first_name']) > 0) &&
			//	 (strlen($s2user_record['data']['last_name']) > 0) ) {
				// Some of the user names are hypenhated given-family
				$display_name = $s2user_record['data']['display_name'];		
				$display_name = str_replace("-"," ",$display_name);				
				$given = substr($display_name,0,strpos($display_name," "));
				$family = substr($display_name,strpos($display_name," ")+1);
				
			if ((strlen($given) > 0) && (strlen($family) > 0)) {
				//Debug
				//echo "Looking for Given: " . $s2user_record['data']['first_name'] . " Family: " . $s2user_record['data']['last_name'] . "*<br>";
				//$BiTS_ID = $this->LookupPersonByName($s2user_record['data']['first_name'], $s2user_record['data']['last_name']);
				echo "Looking for Given: " . $given . " Family: " . $family . "*<br>";
				$BiTS_ID = $this->LookupPersonByName($given, $family);
				
				// If no luck try Nickname & Family name
				if (! $BiTS_ID) {
					//echo "Trying nickname<br>";
					//$BiTS_ID = $this->LookupPersonByName($s2user_record['data']['first_name'], $s2user_record['data']['last_name'], TRUE);
					$BiTS_ID = $this->LookupPersonByName($given, $family, TRUE);
				}
			} else {
				echo "Empty Given or Family Name<br>";
			}
		} 
		
		/* Check by parsing display name */
		if ((! $BiTS_ID ) && ( $use_name )) {
			if (strlen($s2user_record['data']['display_name']) > 0) {
				// Some of the user names are hypenhated given-family
				// However, it does not appear that the display names are hypendated...
				$display_name = $s2user_record['data']['display_name'];		
				$display_name = str_replace("-"," ",$display_name);				
				$given = substr($display_name,0,strpos($display_name," "));
				$family = substr($display_name,strpos($display_name," ")+1);
				//echo "Looking for Given: " . $given . " Family: " . $family . "*<br>";
				$BiTS_ID = $this->LookupPersonByName($given, $family);
				
				// If no luck try Nickname & Family name
				if (! $BiTS_ID) {
					//echo "Trying nickname<br>";
					$BiTS_ID = $this->LookupPersonByName($given, $family, TRUE);
				}
			} else {
				echo "Empty Display Name<br>";
			}
		} 
		
		
		return $BiTS_ID;
	}
	
	function show_user(){
		$result = $this->s2_get_user_by_id("8");
	
		if ($result && empty($result['error'])) {
			echo "<pre>";
			print_r($result);  // Print full array.
			echo "</pre>";
			
			echo "<h2>BiTS ID: " . $this->find_BiTS_ID($result) . "</h2>";

		} elseif (!empty($result['error'])) {
			echo 'API error reads: '.$result['error'];
		}
	}
	
	// ignores s2members with BiTS ID already unles $recheck is TRUE
	private function crosscheck($start, $end, $recheck = FALSE){
	
		//$recheck = TRUE; // Cross check all WordPress records
		$start_ID = $start;
		$empty_output = FALSE;
		$start_time = microtime(TRUE);
		$this->diag_log("s2_match_dp: starting function crosscheck");
		echo "<p>Starting cross check at WordPress ID " . $start_ID . "</p>";
		echo "WP ID\tUser Name\tBiTS DB ID\tMessage 1\tMessage 2<br>";
		for ($wp_ID=$start_ID; $wp_ID <= $end; $wp_ID++) { //1650 max on 10/28/17
			$status = "";
			$BiTS_ID = FALSE;
			
			if ($wp_ID % 25 == 0) {
				$this->diag_log("s2_match_dp: Crosschecking user " . $wp_ID );
			}
			
			$s2_user = $this->s2_get_user_by_id($wp_ID);
			//echo $wp_ID . "<br>";
			
			if ($s2_user && empty($s2_user['error'])) {
				// If not recheck'ing skip records that have a BiTS_ID set already
				if ( $recheck || empty($s2_user['s2member_custom_fields']['bits_id']) ||
					 ($s2_user['s2member_custom_fields']['bits_id'] == 0) ) 
					 {
					 
							
					//echo "<pre>";
					//print_r($result);  // Print full array.
					//echo "</pre>"; 
					$found_by_name = FALSE;
					$BiTS_ID = $this->find_BiTS_ID($s2_user);
					if (! $BiTS_ID ) {
						// Try by name too this time - less accurate
						$BiTS_ID = $this->find_BiTS_ID($s2_user, TRUE);
						if ( $BiTS_ID) {
							$found_by_name = TRUE; 
							//$BiTS_ID .= "\tFound by name";
						} else {
							$status .= "\tNot Found\t" . $s2_user['data']['user_email'];
							if (!empty($s2_user['data']['user_url'])) {
								$status .= "\t" . $s2_user['data']['user_url'];
							}
						}
					}
				
					// If found, then do our updates
					if ( $BiTS_ID ) {
						// Check to see if there is a WP ID in the BiTS DB - if so, does it match
						$BiTS_DB_WordPress_ID = $this->LookupWordPressID($BiTS_ID);
						if (( $BiTS_DB_WordPress_ID != 0 ) && ($BiTS_DB_WordPress_ID != $wp_ID)){
							$status .= "\tMismatch on stored WordPress ID: " . $BiTS_DB_WordPress_ID;
						} else {
							if ($BiTS_DB_WordPress_ID == $wp_ID) {
								//if (! $recheck) {
									$status .= "\tBiTS DB already had same WordPress ID";
								//}
							} else {
								// Empty or blank - record it
								$this->WriteWordPressID($BiTS_ID, $wp_ID);
							}
						}
						// Check if their BiTS_ID is set already
						if ( empty($s2_user['s2member_custom_fields']['bits_id']) || 
							($s2_user['s2member_custom_fields']['bits_id'] == 0)	) {
								$custom_fields = array("bits_id" => $BiTS_ID);
								if ( !$found_by_name ) {
									// Set just the BiTS ID
									$result = $this->s2_update_user_custom_fields($wp_ID, $custom_fields);
								} else {
									$new_notes = "[BiTS DB cross-check found by name]";
									if (!empty($s2_user['s2member_notes'])) {
										$new_notes .= " " . $s2_user['s2member_notes'];
									} 
									$s2_data = array(
										"user_id" => $wp_ID,
										"s2member_notes" => $new_notes,
										"custom_fields" => $custom_fields
									);
									$status .= $this->s2_update_user($s2_data);
								}
						} else {
							// Check if the same?
							if (intval($s2_user['s2member_custom_fields']['bits_id']) == $BiTS_ID ) {
								//$status .= "\tBiTS ID matches";
							} else {
								$status .= "\tNO match on BiTS ID - s2 has: " . $s2_user['s2member_custom_fields']['bits_id'];
							}
						}
						
				}
				
					if ($empty_output) {
						echo "<br>";
						$empty_output = FALSE;
					}
					echo $wp_ID . "; \t". $s2_user['data']['user_login'] . "; \t" . $BiTS_ID . "; \t" . $status . "<br>";
				// It appears that PHP has a problem with a bunch of if's that don't result in anything being output
				// Without regular echo get 200 HTTP Okay error and no other output. 
				} else {
					$empty_output = TRUE;
					echo ".";
					//echo "<p>False or has BiTS_ID</p>";
				} 
				
			} elseif (!empty($s2_user['error'])) {
				if (substr($s2_user['error'],0, 27 ) == "Failed to locate this User.") {
					//Not a big issue since there are gaps in the WP User ID sequence
					//echo "<p>Not Found - WP ID = " . $wp_ID . "</p>";
				} else {
					if (!empty($s2_user['data']['user_login'])) {
						echo $wp_ID . "; \t". $s2_user['data']['user_login'] . "; \tNot Found;<br>";
					}
					echo $wp_ID . "; \t". 'API error reads: '.$s2_user['error'] . "<br>";
				}
			}
		 	
		 	
		 	set_time_limit(30); // Reset to keep from timing out on long runs
		}
		
		$message = "Finished cross check at WordPress ID ". $wp_ID;
		$this->diag_log("s2_match_dp: " . $message);
		echo "<p>" . $message . "</p>";
		
		$elapsed_time = microtime(TRUE) - $start_time;
		$message = "Total execution time" . $elapsed_time;
		$this->diag_log("s2_match_dp: " . $message);
		echo "<p>" . $message . "</p>";	
		
	}
	
	function crosscheck_users1()
	{
		$this->crosscheck(1,499,FALSE);
	}

	function crosscheck_users500()
	{
		$this->crosscheck(500,999,FALSE);
	}
	
	function crosscheck_users1000()
	{
		$this->crosscheck(1000,1499,FALSE);
	}
		
	function crosscheck_users1500()
	{
		$this->crosscheck(1500,1999,FALSE);
	}
	
	function crosscheck_users2000()
	{
		$this->crosscheck(2000,2499,FALSE);
	}
	
	function crosscheck_users2500()
	{
		$this->crosscheck(2500,2999,FALSE);
	}
	
	function crosscheck_users3000()
	{
		$this->crosscheck(3000,3499,FALSE);
	}
	
	function crosscheck_users3500()
	{
		$this->crosscheck(3500,3999,FALSE);
	}
		function crosscheck_users4000()
	{
		$this->crosscheck(4000,4499,FALSE);
	}
		function crosscheck_users4500()
	{
		$this->crosscheck(4500,4999,FALSE);
	}
		function crosscheck_users5000()
	{
		$this->crosscheck(5000,5499,FALSE);
	}
		function crosscheck_users5500()
	{
		$this->crosscheck(5500,5999,FALSE);
	}
		function crosscheck_users6000()
	{
		$this->crosscheck(6000,6499,FALSE);
	}
		function crosscheck_users6500()
	{
		$this->crosscheck(6500,6999,FALSE);
	}
		function crosscheck_users7000()
	{
		$this->crosscheck(7000,7499,FALSE);
	}
		function crosscheck_users7500()
	{
		$this->crosscheck(7500,7999,FALSE);
	}
		function crosscheck_users8000()
	{
		$this->crosscheck(8000,8499,FALSE);
	}
		function crosscheck_users8500()
	{
		$this->crosscheck(8500,8999,FALSE);
	}
	
	function crosscheck_users9000()
	{
		$this->crosscheck(9000,9499,FALSE);
	}
	
	function crosscheck_users9500()
	{
		$this->crosscheck(9500,9999,FALSE);
	}
	
	function crosscheck_users10000()
	{
		$this->crosscheck(10000,MAXUSER,FALSE);
	}
	
	
	// function crosscheck_users2000()
// 	{
// 		$this->crosscheck(2000,MAXUSER,FALSE);
// 	}
	
	
	
	function crosscheck_users_all()
	{
		$this->crosscheck(1,MAXUSER,FALSE);
	}
	
	function recheck_crosscheck1() 
	{
		$this->crosscheck(1,499,TRUE);
	}
	
	function recheck_crosscheck500() 
	{
		$this->crosscheck(500,999,TRUE);
	}

	function recheck_crosscheck1000() 
	{
		$this->crosscheck(1000,1499,TRUE);
	}

	function recheck_crosscheck1500() 
	{
		$this->crosscheck(1500,1999,TRUE);
	}

	function recheck_crosscheck2000() 
	{
		$this->crosscheck(2000,MAXUSER,TRUE);
	}
	
	function recheck_crosscheck_all() 
	{
		$this->crosscheck(1,MAXUSER,TRUE);
	}
	
	// Adds s2members who do have a BiTS ID to the BiTS Database
	// In preview mode until $write is set TRUE
	private function add_to_bits_database ($start, $end, $write = FALSE) {
	
		$start_ID = $start;
		$empty_output = FALSE;
		$start_time = microtime(TRUE);
		$this->diag_log("s2_match_db: starting function add_to_bits_database");
		echo "<p>Starting at WordPress ID " . $start_ID . "</p>";
		echo "WP ID\tUser Name\tBiTS DB ID\tMessage 1\tMessage 2<br>";
		for ($wp_ID=$start_ID; $wp_ID <= $end; $wp_ID++) { 
			$status = "";
			$BiTS_ID = FALSE;
			
			if ($wp_ID % 25 == 0) {
				$this->diag_log("s2_match_dp: Adding to BiTS DB user " . $wp_ID );
			}
			
			$s2_user = $this->s2_get_user_by_id($wp_ID);
			//echo $wp_ID . "<br>";
			
			if ($s2_user && empty($s2_user['error'])) {
				if (empty($s2_user['s2member_custom_fields']['bits_id']) ||
					 ($s2_user['s2member_custom_fields']['bits_id'] == 0) )
					 {
					 				
					//echo "<pre>";
					//print_r($result);  // Print full array.
					//echo "</pre>"; 
					$found_by_name = FALSE;
					$BiTS_ID = $this->find_BiTS_ID($s2_user);
					if (! $BiTS_ID ) {
						// Try by name too this time - less accurate
						$BiTS_ID = $this->find_BiTS_ID($s2_user, TRUE);
						if ( $BiTS_ID) {
							$found_by_name = TRUE; 
							//$BiTS_ID .= "\tFound by name";
						} 
					}
				
					// If they are really not found, then add to BiTS Database
					if ( ! $BiTS_ID ) {
						if ( ! empty($s2_user['data']['user_email'])) {
							// Okay we know the email address wasn't found otherwise we would have matched above
							// Add new record
							
							// It appears that first_name and last_name are no longer in the s2 record as of 3/19/19
							// they may have never been there. So we should parse them ourselves from the display_name

								$display_name = $s2_user['data']['display_name'];		
								$display_name = str_replace("-"," ",$display_name);				
								$given = substr($display_name,0,strpos($display_name," "));
								$family = substr($display_name,strpos($display_name," ")+1);
							
										
							$SQLdata = [
								'GivenName' => $given, //$s2user_record['data']['first_name'],
								'FamilyName' => $family, //$s2user_record['data']['last_name'],
								'Email' => $s2_user['data']['user_email'],
								'Origin' => "Cross match to WordPress Users",
								'WordPressID' => $wp_ID,
								'Active' => 1
							];
							//$status .= "\t" . $s2user_record['data']['first_name'] . "\t" . $s2user_record['data']['last_name'] .
							//	"\t" . $s2user_record['data']['user_email'];
							$status .= "\t" . $given . "\t" . $family .	"\t" . $s2_user['data']['user_email'];
								$db = \Config\Database::connect();
								$builder = $db->table('contacts');
								
							if ($write) {
								$builder->insert($SQLdata);
								// Read back their database ID
								$BiTS_ID = $this->LookupPersonByEmail($s2_user['data']['user_email'],FALSE);
								if (! $BiTS_ID ) {
									$status .= "\tWrite of BiTS DB record failed - no BiTS ID found on check";
								} else {
									$status .= "\tAdded new record - BiTS ID = \t" . $BiTS_ID;
								}
							} else {
								$status .= "\tNot in write mode";
							}				
									
							// Now add their BiTS database ID to their WP record
							if ( ($BiTS_ID > 0) ) {
								$custom_fields = array("bits_id" => $BiTS_ID);
								$result = $this->s2_update_user_custom_fields($wp_ID, $custom_fields);
							}
										
						} else {
							$status .= "\t" . "Empty email address - not added";
						}
					} else {
						$status .= "\t" . "BiTS ID found - re-run cross check";
					}
						
				
			
					if ($empty_output) {
						echo "<br>";
						$empty_output = FALSE;
					}
					echo $wp_ID . "\t". $s2_user['data']['user_login'] . "\t" . $BiTS_ID . "\t" . $status . "<br>";
				// It appears that PHP has a problem with a bunch of if's that don't result in anything being output
				// Without regular echo get 200 HTTP Okay error and no other output. 
				} else {
					$empty_output = TRUE;
					echo ".";
					//echo "<p>False or has BiTS_ID</p>";
				} 
				
			} elseif (!empty($s2_user['error'])) {
				if (substr($s2_user['error'],0, 27 ) == "Failed to locate this User.") {
					//Not a big issue since there are gaps in the WP User ID sequence
					//echo "<p>Not Found - WP ID = " . $wp_ID . "</p>";
				} else {
					if (!empty($s2_user['data']['user_login'])) {
						echo $wp_ID . "\t". $s2_user['data']['user_login'] . "\tNot Found<br>";
					}
					echo $wp_ID . "\t". 'API error reads: '.$s2_user['error'] . "<br>";
				}
			}
		 	
		 	
		 	set_time_limit(30); // Reset to keep from timing out on long runs
		}
		
		$message = "Finished adding to BiTS DB at WordPress ID ". $wp_ID;
		$this->diag_log("s2_match_dp: " . $message);
		echo "<p>" . $message . "</p>";
		
		$elapsed_time = microtime(TRUE) - $start_time;
		$message = "Total execution time" . $elapsed_time;
		$this->diag_log("s2_match_dp: " . $message);
		echo "<p>" . $message . "</p>";	
		
	}
	
	function preview_add_to_database () {
		$this->add_to_bits_database(1, MAXUSER, FALSE);
	}
	
	function add_to_database () {
		$this->add_to_bits_database(1, MAXUSER, TRUE);
	}
	
	// Move all s2members who are Level 1 back to Level 0
	// To be run right before or just immediately after the China event
	private function reset_china_users($start, $end) {
		//xdebug_break();
		
		$this->diag_log("starting reset_china_users");
		echo "<p>Starting resetting China users from Level 1 to Level 0 " . "</p>";
		echo "<p>Starting at " . $start . "</p>";
		echo "WP ID\tUser Name\tBiTS DB ID\tMessage 1\tMessage 2<br>";
		
		$max_time = 0;
		$min_time = 100000;
		$total_time = 0;
		$loop_start = microtime(TRUE);
		
		for ($wp_ID=$start; $wp_ID <= $end; $wp_ID++) { //1650 max on 10/28/17
			$status = "";
			$BiTS_ID = FALSE;
			
			$start_time = microtime(TRUE);
			
			$s2_user = $this->s2_get_user_by_id($wp_ID);
			

			//$this->diag_log("s2 processing: " . $wp_ID );
			
			if ($s2_user && empty($s2_user['error'])) {
				//$this->diag_log("User: ". $wp_ID . " Level: " . $s2_user['level']);
				if ($s2_user['level'] == 1) {
					$status = $this->s2_update_user_level($wp_ID, 0);
					if ($status) {
						echo "Reset user:\t". $wp_ID . "\t from Level:\t" . $s2_user['level'] . "\tto Level 0 <br>";
					} else {
						echo "Failed to reset user:\t" . $wp_ID . " - ". $status . "<br>";
					}
				} /*else {
					echo ".";
				} 
			} else {
				echo "+"; */
			} 
			
			$elapsed = (microtime(TRUE) - $start_time);

			
			$max_time = max($max_time, $elapsed);
			$min_time = min($min_time, $elapsed);
			$total_time += $elapsed;
			
			if ($wp_ID % 25 == 0) {
				$this->diag_log("s2 processing: " . $wp_ID );
				$this->diag_log("Elapsed time " . $elapsed . " seconds");
				$this->diag_log("Max time " . $max_time . " seconds");
				$this->diag_log("Average time: " . ($total_time/($wp_ID-$start+1)) );
			}
			
			set_time_limit(30); // Reset to keep from timing out on long runs
			//$set_tl = (microtime(TRUE) - $start_time - $elapsed);
			//$this->diag_log("Time to set time limit: " . $set_tl);
		}
		$this->diag_log("Finished at " . $end );
		$this->diag_log("Maximum time " . $max_time . " seconds");
		$this->diag_log("Total time: " . $total_time );
		$this->diag_log("Average time: " . ($total_time/($end-$start+1)) );
				
		$total_execution_time = (microtime(TRUE) - $loop_start);

		$this->diag_log("Total execution time " . $total_execution_time );
		
		
		$output ="<p>Finished at " . $end . "</p>";
		$output .="<p>Maximum time: " . $max_time . "</p>";
		$output .= "<p>Minimum time: " . $min_time . "</p>";
		$output .= "<p>Total time: " . $total_time . "</p>";
		$output .= "<p>Average time: " . ($total_time/($end-$start+1)) . "<p>";
		
		$output .= "<p>Total execution time ". $total_execution_time . "</p>";
		echo $output;

		//$this->load->view('s2member');
		
	}
	
	function reset_china_1() {
		$this->reset_china_users(1, 499);
	}
	function reset_china_500() {
		$this->reset_china_users(500, 999);
	}
	function reset_china_1000() {
		$this->reset_china_users(1000, 1499);
	}
	function reset_china_1500() {
		$this->reset_china_users(1500, 1999);
	}
	function reset_china_2000() {
		$this->reset_china_users(2000, 2499);
	}
	function reset_china_all() {
		$this->reset_china_users(1, MAXUSER); 
	}
	
	// $s2_level is the new level for users who are Type = Full Conference in the attendance database
	// if the user is a higher level already their level will not be changed.
	private function set_event_users($year, $event, $ccap, $tutorial, $s2_level) {
		//xdebug_break();
		echo "<p>Either look at diagnostic_log in /logs_public or view page source to get Tab Delimited data</p>\n";
				
		$this->diag_log("starting set_event_users");
		$message = "Starting setting ". $event . " " . $year . " Full Conference attendees to Level" . $s2_level;
		$this->echo_diag_log($message);

		//$message = "Starting at " . $start;
		//$this->echo_diag_log($message);
		
		$db = \Config\Database::connect();
		$builder = $db->table('contacts');
		$builder->select('AttendanceID, attendance.ContactID, WordPressID, contacts.Email,attendance.Email, Type, Tutorial, GivenName, FamilyName');
		$builder->from('attendance');
		$builder->where('attendance.ContactID = contacts.ContactID');
		$builder->where('Year = ' . $year);
		$builder->where('Event = "' . $event . '"');
		

		
		//$this->db->where('WordPressID IS NOT NULL'); 
		
		//$this->db->where("WordPressID < 100");  // For debug
		
		//$this->db->where("AttendanceID < 650");
		
		
		$query = $builder->get();
		
		$message = $db->getLastQuery();

		$this->echo_diag_log($message);
		$message = "Rows = " . $query->getNumRows();
		$this->echo_diag_log($message);
		//xdebug_break();	
		//d($query->result_array());

		
		echo "\tType\tBiTS DB ID\tWP ID\tWordPress Username\tPassword\tEmail\tLinkedInEmail\ts2 Level\tMessage 1\tMessage 2<br>\n";
		foreach ($query->getResultArray() as $row) {
			$status = "";
			$wp_ID = $row['WordPressID'];
			$username = "";
			$password = "";
			$linkedInEmail = "";
			$new_level = 0;
			$new_ccaps = ''; // Not sure why this wasn't here before
			
			if (($row['Type'] == "Full Conference") || ($row['Type'] == "Professional") || ($row['Tutorial'] == 1)) {
				if (empty($wp_ID)) {
					//xdebug_break();	
					// This person doesn't have a WordPress account, so need to add it first
					// Generate username and check for uniqueness
						if ((strlen($row['GivenName']) >0) && (strlen($row['FamilyName']) >0)) {
							$username = $this->s2_generate_username($row['GivenName'], $row['FamilyName'], $row['ContactID']);
							if (! $username ) {
								$status .= "\tUsername generation failed";
							} else {
								//changed from $row['Email']
								// Double check that email address isn't in use already
								if (! $this->s2_check_email($row['contacts.Email'])) {
									// Okay now create the user
									$password = $this->generatePassword();
									$wp_ID = $this->s2_create_user($username, $row['Email'], $row['ContactID'], $password, $row['GivenName'], $row['FamilyName']);
									if ($wp_ID) {
												
									$data = [
										'WordpressID' => $wp_ID,
								
									];
									$db2 = \Config\Database::connect();
									$builder2 = $db2->table('contacts');
									$builder2->where('ContactID', $row['ContactID']);
									$builder2->update($data);
										// Best to stuff it in the row table? Seems okay.
										//doesn't work $row['WordPressID'] = $wp_ID;
										$status .= "\tAdding user as WP ID\t" . $wp_ID ."\tusername:\t" . $username . "\tpass:\t" . $password;
										//$this->echo_diag_log($status);
										// And for neatness record their WP ID back into the BiTS Database	
										$this->WriteWordPressID($row['ContactID'], $wp_ID);
								
									} else {
										$status .= "\tFailed to create user";
									}
								} else {						
									$status .= "\tEmail " . $row['Email'] . " already in use - add failed";
								}
							}
						} else {
							$status .="\tGiven or Family name is empty - username generation skipped";
						
						}
				
				}
						//$wp_id = 8912;
				// Look up s2member record, verify BiTS DB ID matches
				$s2_data = $this->s2_get_user_by_id($wp_ID);
				if ((empty($s2_data['error'])) && ($s2_data['s2member_custom_fields']['bits_id'] == $row['ContactID'])) {
					$username = $s2_data['data']['user_login'];
					// Assume if URL is LinkedIn then the email address is their primary LinkedIN email address
					// this is the convention set by OneAllSocial
					if (strpos($s2_data['data']['user_url'], "linkedin.com") !== FALSE) {			
						$linkedInEmail = $s2_data['data']['user_email'];
					}
					// Set user level
					if (($row['Type'] == "Full Conference") || ($row['Type'] == "Professional")) { 
						$new_level = $s2_level;
						$new_ccaps = $ccap;
					}
					if ($s2_data['level'] > $s2_level) {
						$new_level = $s2_data['level'];
						$status .="\tstaying at original level ";					
					}

					// Check Tutorial and add cc_cap if needed
					if ($row['Tutorial'] == 1 ) {
						if (!empty($new_ccaps)) {
							$new_ccaps .=",";
						}
						$new_ccaps .= $tutorial;
						$status .= "\tAdding tutorial";
					}
					
					// temporary fix for accounts prematurely created in error
					/*
					if (($wp_ID > 2482) && ($wp_ID < 2552)) {
						$password = $this->generatePassword();
						$data = array(
						"user_id" => $wp_ID,
						'user_pass' => $password,
						's2member_ccaps' => $new_ccaps,
						's2member_level' => $new_level
						);
						$status .= "\tPassword\t" . $password;
						
					} else { */
					
					$data = array(
						"user_id" => $wp_ID,
						's2member_ccaps' => $new_ccaps,
						's2member_level' => $new_level
						);
					//}
					
					$result = $this->s2_api("modify_user", $data);
				
					if ($result && empty($result['error']) && !empty($result['ID'])) {
						$status .= "\ts2member user data updated";
					} elseif (!empty($result['error'])) {
						$status .= '\tAPI error reads: '.$result['error'];
					}
				
				} else {
					if (empty($s2_data['error'])) {
						$status = "\tMis-match on BiTS ID";
					} else {
						$status = "\tAPI error reads: " . $s2_data['error'];
					}
				}
			
			}
			//find emails and compare here IRA
			if (strcasecmp($row['contacts.Email'], $row['attendance.Email']) != 0) {
				$status.= $row['contacts.Email'].' is different from attendance email '.$row['attendance.Email'];
				}
			
			if (empty($username)) { $username = " "; }
			if (empty($password)) { $password =" "; }
			if (empty($linkedInEmail)) { $linkedInEmail = " ";}
			
			$message = $row['Type'] . "\t" . $row['ContactID'] . "\t" . $wp_ID . "\t" . $username . "\t" . $password . "\t" . $row['Email'] ."\t";
			$message .= $linkedInEmail . "\t" . $new_level . $status;
			
			echo $message . "<br>\n";
			$this->diag_log($message);
			
			set_time_limit(30); // Reset to keep from timing out on long runs
		}



		
		$message = "Finished setting user levels."; // at ID " . ($wp_ID-1);
		$this->echo_diag_log($message);

		

	}

	function set_china_users() {
		//$this->set_event_users("2018", "Suzhou", "c2018", "tc2018", 1);
		// Do twice since we had two China events in 2018
		$this->set_event_users("2024", "China", "c2024", "", 1);
	}
	
	function set_korea_users() {
		//$this->set_event_users("2018", "Suzhou", "c2018", "tc2018", 1);
		// Do twice since we had two China events in 2018
		$this->set_event_users("2024", "Korea", "k2024", "", 1);
	}
	
	
	function set_mesa_users() {
		$this->set_event_users("2025", "Mesa", "2025", "t2025", 4);
	}
	
	// Not fully written / debugged - wait unitl March 2018 to run...
	private function reset_mesa_users($start, $end) {
		//echo "<h2>Not tested yet!</h2>";
		//die();
		
		//xdebug_break();
		$koreaYear = "k2024";
		$chinaYear = "c2024";
		$this->diag_log("starting reset_mesa_users");
		echo "<p>Starting resetting Mesa users from Level 4 to Level 0 " . "</p>";
		echo "<p>If they were at China in the last year - i.e ". $chinaYear. " is set in ccaps , they will be set to Level 1</p>";
		echo "<p>If they were at Korea in the last year - i.e ". $koreaYear. " is set in ccaps , they will be set to Level 1</p>";
		echo "<p>Starting at " . $start . "</p>";
		echo "WP ID\tUser Name\tTestConX DB ID\tMessage 1\tMessage 2<br>";
				
		for ($wp_ID=$start; $wp_ID <= $end; $wp_ID++) { 
			$status = "";
			$BiTS_ID = FALSE;
			
			$s2_user = $this->s2_get_user_by_id($wp_ID);
			
			if ($s2_user && empty($s2_user['error'])) {
				//$this->diag_log("User: ". $wp_ID . " Level: " . $s2_user['level']);
				if ($s2_user['level'] == 4) {
					$new_level = 0;
					if (in_array($chinaYear, $s2_user['ccaps'])) {
						$new_level = 1;
					}
					if (in_array($koreaYear, $s2_user['ccaps'])) {
						$new_level = 1;
					}
					$status = $this->s2_update_user_level($wp_ID, $new_level);
					if ($status) {
						echo "Reset user:\t". $wp_ID . "\t from Level:\t" . $s2_user['level'] . "\tto Level " . $new_level . " <br>";
					} else {
						echo "Failed to reset user:\t" . $wp_ID . " - ". $status . "<br>";
					}
				} 
			} 

			set_time_limit(30); // Reset to keep from timing out on long runs
		}

		$message = "Finished resetting Mesa user level at ID " . ($wp_ID-1);
		$this->diag_log($message);
		echo "<p>" . $message . "</p>";
		

	}
	
	function reset_mesa_all() {
		$this->reset_mesa_users(1, MAXUSER); // Fix back
	}


	private function write_mailchimp($includeChinese)
	// Writes out CSV data for MailChimp, without the Chinese Fields
	// And adds s2member/WP username & email address
	
	{
		$db = \Config\Database::connect();
		$builder = $db->table('contacts');
		
		
 		//ask ira $this->load->database('default',TRUE);
 		
		$subscribers = 0;
		
		//echo "Number of rows in Contacts " . $this->db->count_all_results('Contacts') . "</p>";
		
		
		// Query updated with switch of Active to TINYINT (0,1)
		$where_criteria = [
			'Active' => "1",
			'Email is NOT NULL' => NULL,
			'EmailBounce' =>"0"
		];					
		
		$builder->where($where_criteria);

		$query = $builder->get();
		$num = $query->getNumRows();
		
		
		echo "# TestConX Database Export<br>";
		echo "# TestConX Workshop Confidential<br>";
		echo "# Date: " . date("Y-m-d h:i:sa") . "<br>";
		echo "# Number of rows: ". $num. "<br>";
		
		echo "ContactID,Email,Action,Given Name,Family Name,Nickname (optional),Username,s2Email,Technical Program Information,Exhibiting & Sponsoring Information,Preferred Language,EUCountry";
		if ($includeChinese) {
			echo ",Chinese Name (optional)"; 
		}
		echo "<br>";
		
		foreach ($query->getResultArray() as $row)
		{	
			$s2Username = '';
			$s2Email = '';
			if ($row['WordPressID'] > 0) {
				$s2_user = $this->s2_get_user_by_id($row['WordPressID']);
				if ($s2_user && empty($s2_user['error'])) {
					$s2Username = $s2_user['data']['user_login'];
					$s2Email = $s2_user['data']['user_email'];
				}
			}
			echo $row['ContactID'] . ',' . $row['Email'] . ',';
			// Fixed Action flag
			echo "bits_db_exported". date("Y-m-d") .",";
			echo '"'. $row['GivenName'] . '","' . $row['FamilyName'] . '","' . $row['Nickname'] . '",';
			echo '"'. $s2Username . '","' . $s2Email . '",';			
			
			echo '"'.$this->contacts->expand_region($row['TechInfo']) . '",';
			echo '"'.$this->contacts->expand_region($row['ExhibitInfo']) . '",' . $row['Language'] . ',' . $row['EUCountry'];

			
			if ($includeChinese) {
				echo ',"'. $row['ChineseName'] . '"'; 
			}
			echo "<br>";
		
			$subscribers++;
			
		} 
		return;
		
	}
	
	function write_mailchimp_with_Chinese()
	{
		$chinese = TRUE;
		$this->write_mailchimp(TRUE);
	}
	


	// One time code to clean up the ccaps
	function clean_ccaps() {
		$start = 1499;
		$end = 2000; //1650;
		
		//xdebug_break();
		
		$this->diag_log("starting clean_ccaps");
		echo "<p>Starting to clean s2_member_ccaps " . "</p>";
		echo "<p>Starting at " . $start . "</p>";
		echo "WP ID\tUser Name\tBiTS DB ID\tMessage 1\tMessage 2<br>";
				
		for ($wp_ID=$start; $wp_ID <= $end; $wp_ID++) { 
			$status = "";
			$BiTS_ID = FALSE;
			
			$s2_user = $this->s2_get_user_by_id($wp_ID);
			

			//$this->diag_log("s2 processing: " . $wp_ID );
			if ($s2_user && empty($s2_user['error'])) {
				//$this->diag_log("User: ". $wp_ID . " Level: " . $s2_user['level']);
				if (!empty($s2_user['ccaps'])) {
					$message = "cleaning ccaps for:\t" . $wp_ID;
					$this->diag_log($message);
					echo $message . "<br>";
					$new_ccaps = $this->clean_s2_member_ccaps($s2_user['ccaps']);
					
					$data = array(
						"user_id" => $wp_ID,
						's2member_ccaps' => "-all," . $new_ccaps
					);
					$result = $this->s2_api("modify_user", $data);
					if (!empty($result['error'])) {
						$message = 'API error reads: '.print_r($result['error'],true);
						$this->diag_log($message);
						echo $message . "<br>";
					}
					
				}			

					/* $status = $this->s2_update_user_level($wp_ID, 0);
					if ($status) {
						echo "Reset user:\t". $wp_ID . "\t from Level:\t" . $s2_user['level'] . "\tto Level 0 <br>";
					} else {
						echo "Failed to reset user:\t" . $wp_ID . " - ". $status . "<br>";
					} */
					
			} 
		}				


		
		$this->diag_log("Finished cleaning ccaps at ID " . ($wp_ID-1));
		echo "<p>Finished at " . $end . "</p>";
		

		//$this->load->view('s2member');
		
	}
	
	
}

?>

