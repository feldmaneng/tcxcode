<?php defined('BASEPATH') OR exit('No direct script access allowed');


class Contacts {

	function __construct()
	 {
 
		/* Standard Libraries of codeigniter are required */
		$CI =& get_instance();

		$CI->load->helper('url');
		$CI->load->database();
		
			 
 
	 }
	
	
	// Find the Person Record with a given Email
	// Returns ConactID if one person is found
	// Returns FALSE if not found or empty email
	// *
	public function LookupPersonByEmail($email, $p_ReportNotFound = true) 
	{
		$CI =& get_instance();
		
		//$email = "15024354542@163.com";
		//echo "<p>Checking email " . $email . "*</p>";
		//dump_string ($email);
		//echo "Encoding: " . mb_detect_encoding($email) . "<br>"; //, 'UTF8');
		///$contactsDB = $CI->load->database('default',TRUE); // the TRUE paramater tells CI that you'd like to return the database object.
		
		if (strlen($email) > 0 ) {
			// Not sure why the query wasn't clearing. First query was okay and subsequent ones had a wrong where ContactID = clause
			// https://stackoverflow.com/questions/6246023/how-to-reset-codeigniter-active-record-for-consecutive-queries
			// https://www.codeigniter.com/userguide3/database/query_builder.html#resetting-query-builder 
			$CI->db->reset_query(); 
			
			$CI->db->select('*');
			$CI->db->from('contacts');
		
			$CI->db->where('Email',$email);
			
			$query_people = $CI->db->get();
			
			//echo "<p>" . $CI->db->last_query() . "</p>";
			
			if ( $query_people->num_rows() > 0 ) {
				// An email ID shouldn't be on more than one person, so we shouldn't have more than one row
				if ( $query_people->num_rows() > 1 ) {
					echo "Warning: Email query on ". $email . " yielded " .$query_people->num_rows() . "rows. (Multiple people.) <br>";
					foreach ($query_people->results as $row) {
						echo "	ContactID ". $row->ContactID . "<br>\n\n\n\n";
					}
					//die();
				}
				$row = $query_people->row_array();
				//echo "	  ContactID: " . $row['ContactID'] . "<br>";
				return $row['ContactID'];
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

	public function LookupPersonByName($given, $family, $p_ReportNotFound = true) 
	{
		$CI =& get_instance();
				
		if (strlen($given.$family) > 0 ) {
			$CI->db->select('*');
			$CI->db->from('contacts');
		
			$CI->db->where('GivenName', $given);
			$CI->db->where('FamilyName', $family);
			
			$query_people = $CI->db->get();
			//echo "<p>" . $this->db->FamilyName_query() . "</p>";
			if ( $query_people->num_rows() > 0 ) {
				// An email ID shouldn't be on more than one person, so we shouldn't have more than one row
				if ( $query_people->num_rows() > 1 ) {
					echo "Warning: Email query on ". $given . " " . $family . " yielded " .$query_people->num_rows() . "rows. (Multiple people.) <br>";
					foreach ($query_people->results as $row) {
						echo "	ContactID ". $row->ContactID . "<br>\n\n\n\n";
					}
					//die();
				}
				$row = $query_people->row_array();
				//echo "	  ContactID: " . $row['ContactID'] . "<br>";
				return $row['ContactID'];
			} else {				
				// didn't find the a person with that email address
				if ($p_ReportNotFound) {
					echo "Did not find: " . $given . ' ' . $family . "*<br>";
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}	

	public function expand_region($region)
	// Takes the Enumerated types and expands to text to match MailChimp
	{
	  $result = "";
	  switch ($region) {
		   case "worldwide" :  $result = "Worldwide (all information)"; break;
		   case "North America" :  $result = "North America only"; break;
		   case "China" :  $result = "China only"; break;
		   case "Asia" :  $result = "Asia (including China) only"; break;
		   case "Europe" :  $result = "Europe only"; break;
		   case "none" :  $result = "none - please do not send this information"; break;
	   }
	   return $result;
	}
}