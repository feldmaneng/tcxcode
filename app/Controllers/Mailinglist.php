<?php
namespace App\Controllers;

//setup php for working with Unicode data, needs to be update for codeigniter 4
/* mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8'); */
//ob_start('mb_output_handler');

//require '/Applications/MAMP/htdocs/Kint/Kint.class.php';
//require __DIR__ . '/vendor/autoload.php';
//require '/vendor/autoload.php';

//use DebugBar\StandardDebugBar;

//$debugbar = new StandardDebugBar();
//$debugbarRenderer = $debugbar->getJavascriptRenderer();

//$debugbar["messages"]->addMessage("hello world!");

// Global constants

define("EventYear", "China2024"); // For selecting records only for this year's event.
define("Event", "China");
define("Year", "2024");




use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;
use App\Libraries\Contacts;
// Make sure you are logged in to access these functions.
$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

} 

class Mailinglist extends BaseController {

 
	function __construct()
	{
		
		helper('text');

	
		

 
	}
	
	

	 
 
	  public function index()
	 {
	 	echo "<h2>Event Year: " . EventYear . "</h2>";
		echo "<p>Use Excel 2016 (PC) to save files as Unicode Text (.txt) to make sure Chinese characters are exported properly. Appears that you can use Mac too.</p>";
		echo "<p>With BB-Edit make sure the input file is UTF-16 Little Endian</p>";
		echo "<UL>";
		 // echo "<li><a href=" . site_url('mailinglist/read_tab_input') . ">Read Tab File to preview</a></li>";
		 // echo "<li><a href=" . site_url('mailinglist/read_tab_input_and_update') . ">Read Tab File to really add to database</a></li>";
		 // echo "<br>";
		 // echo "<li><a href=" . site_url('mailinglist/read_tab_input_for_attendance') . ">Update attendance list preview</a> -- don't forget to update constants first!</li>";
		 // echo "<li><a href=" . site_url('mailinglist/read_tab_input_for_attendance_update') . ">Update attendance list - update</a> -- don't forget to update constants first!</li>";
		 // echo "<br>";
		  echo "<li><a href=" . site_url('mailinglist/mark_inactive') . ">Mark Inactive</a></li>";
		  echo "<li><a href=" . site_url('mailinglist/write_mailchimp_with_Chinese') . ">Write CSV for MailChimp WITH Chinese Names</a></li>";
		echo "<li><a href=" . site_url('mailinglist/write_mailchimp_no_Chinese') . ">Write CSV for MailChimp without Chinese Names</a></li>";
		echo "<br>";
		echo "<li><a href=" . site_url('mailinglist/findGuests') . ">Match Guests to main contacts database</a></li>";
		echo "<li><a href=" . site_url('mailinglist/preview_Guest_to_Main') . ">Preview Main database update from Guest List</a></li>";
		echo "<li><a href=" . site_url('mailinglist/update_Guest_to_Main') . ">Update Main database from Guest List</a></li>";
		echo "<br>";
		echo "<li><a href=" . site_url('mailinglist/check_add_to_attendance') . ">Check addinging names to attendance database</a></li>";
		echo "<li><a href=" . site_url('mailinglist/add_to_attendance') . ">Add names to attendance database</a></li>";
		echo "<li><a href=" . site_url('mailinglist/testcontacts') . ">TestContacts</a></li>";

		//echo "<li><a href=" . site_url('mailinglist/China_MailChimp') . ">Generate MailChimp from China Guest List</a></li>";
		//echo "<li><a href=" . site_url('mailinglist/read_JotForm_tab_input') . ">Import from China JotForm</a></li>";
		//echo "<li><a href=" . site_url('mailinglist/read_MikeCRM_tab_input') . ">Import from China MikeCRM</a></li>";
		//echo "<li><a href=" . site_url('mailinglist/China_Name_Badges') . ">Generate China Name Badge list from China Guest List</a></li>";
		echo "<br>";
		//echo "<li><a href=" . site_url('mailinglist/swap_emails') . ">Swap out LinkedIn emails in a TDF</a></li>";
		//echo "<li><a href=" . site_url('mailinglist/wrangler_list') . ">Write wrangler list</a></li>";
		echo "<br>";
		//echo "<li><a href=" . site_url('mailinglist/list_expo_entries') . ">List Expo Directory</a></li>";
		  echo "</UL>";
		//echo "<br><br><br><p>Things to fix</p>";
		//echo "<ul>";
		
		  // die();
	 }
  

	function testcontacts () {
	$mine = new Contacts();
	$test = $mine->test();
	echo $test;
	}		

	// Find the Person Record with a given LinkedInEmail
	// Returns ConactID if one person is found
	// Returns FALSE if not found or empty email
	// *
	private function LookupPersonByLinkedInEmail($email) 
	{
		//$email = "15024354542@163.com";
		//echo "<p>Checking email " . $email . "*</p>";
		//dump_string ($email);
		//echo "Encoding: " . mb_detect_encoding($email) . "<br>"; //, 'UTF8');
		
		if (strlen($email) > 0 ) {
			
			$db = \Config\Database::conect();
			$builder = $db->table('contacts');
			$builder->where('LinkedInEmail',$email);
		
		
			
			$query_people = $builder->get();
			//echo "<p>" . $this->db->FamilyName_query() . "</p>";
			if ( $query_people->getNumRows() > 0 ) {
				// An email ID shouldn't be on more than one person, so we shouldn't have more than one row
				if ( $query_people->getNumRows() > 1 ) {
					echo "Warning: LinkedInEmail query on ". $email . " yielded " .$query_people->num_rows() . "rows. (Multiple people.) <br>";
					foreach ($query_people->getResult() as $row) {
						echo "	ContactID ". $row->ContactID . "<br>\n\n\n\n";
					}
					//die();
				}
				$row = $query_people->getRowArrary();
				//echo "	  ContactID: " . $row['ContactID'] . "<br>";
				return $row['ContactID'];
			} else {				
				// didn't find the a person with that email address
				echo "Did not find: " . $email . "* in LinkedInEmail<br>";
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}	
		
	// Returns the name of the company given the CompanyID
	private function FindCompanyByID($companyID, $ChinaDB)
	{
		if ( $companyID > 0 ) {
			
			$db = \Config\Database::conect();
			$builder = $db->table('chinacompany');
			$builder->where('CompanyID',$companyID);
			
			/* $ChinaDB->select('*');
			$ChinaDB->table('chinacompany');
		
			$ChinaDB->where('CompanyID',$companyID); */
			
			$query = $builder->get();
			$row = $query->getRowArray();
			return $row['Company'];
		}
	}	
	
	function write_archive ($primary_key) {
		//There may be a data structure with the original record somewhere, but haven't found it.
		//So simply retrieve it prior to writing the updated record
			$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->where('ContactID', $primary_key);
			$old_query = $builder->get();
		
		// We've also assumed only one record got returned since primary_key is unique
		// Better to check, etc.
		 

	
		
		//If we have more than 1 row, for some reason, only the GivenName row will be written to the archive
		$old_row = $old_query->getRowArray(); //result_array() row_array();
	
		//Save the ContactID under OriginalContactID since the ForeignKey relationship will set 
		//the ContactID to NULL when the record is deleted from Contacts	
		$new_row = array (
				'ContactID'	=> $old_row['ContactID'],
				'OriginalContactID' => $old_row['ContactID']
		);
		
		
		// Copy the old record field by field to a new array however skip the
		// ContactID field since it is RecordID in the Archive table and is set above
		foreach($old_row as $key => $value) {
			//if ($key != "ContactID") { //Need to skip this field since called RecordID in archive table
				$new_row[$key] = $old_row[$key];
			//}
		}
		
		// Guard against old database entrys where Stamp is empty
		if (empty($new_row['Stamp'])) {
			$new_row['Stamp'] = date('Y-m-d H:i:s ');
		}
		$db->table('contacts_archive')->insert($new_row); 	
		 
		return;
	}

	private function update_record(&$record, $input, $field, $input_field) {
		$verbose = "";
		if ( empty($record[$field]) && !empty($input[$input_field])) {
			$record[$field] = $input[$input_field];
			//d($record[$field], $input[$input_field]);
			$verbose = $field . "\t";
		}
		return $verbose;
	}
	
	function read_tab_input($p_write = false)
	// This reads a tab delimited file from Google on Mac OS
	// set up for UTF-16 LE
	
	// set $p_write to true to do an actual write, otherwise it just previews
	
	 {
		//$debugbar["messages"]->addMessage("Just entered read_tab_input");
		  //echo $debugbarRenderer->render();
		
		  set_time_limit(120);
		  // Set for UTF-16 Little-Endian
		  //ask ira about this library 
		  $this->load->library('tabreaderselect2');
	 
			 $filePath = './names_to_add.txt';
			 $field_list = array (//'Show', 
			 		'GivenName', 'FamilyName', // 'CN_GivenName', 'CN_FamilyName',
					'Nickname', 'Email', 'Company', //'CN_Company',
					'Job_Title', 'Street', 'Line2',
					'City', 'State', 'Country', 'Postal_Code', //'CN_Address',
					'Work', 'Mobile', 
					'Attendance', 'Payment',
					'InviteCompanyID','Tutorial','ContactID'); 
				
				//$data['csvData'] = $this->csvreader->parse_file($filePath);
			$data['csvData'] = $this->tabreaderselect2->parse_file($filePath, TRUE, $field_list);
		    d($data['csvData']);
			
			$records_processed = 0;
				foreach($data['csvData'] as $field) { 
				//d($field);
				$records_processed++;
				$verbose= "Processing\t" . $field['GivenName'] ."\t" . $field['FamilyName'] ."\t" .
						$field['Email'] . "\t";
				
				$contactID = $this->LookupPersonByEmail($field['Email']);
				if (!empty($field['ContactID'])) {
					if ($contactID == $field['ContactID']) {
						$verbose .= "Contact ID match\t";
					} else {
						$verbose .= "mismatch on Contact ID\t";
					}
				} else {
					$verbose .= "\t";
				}
				
				if (! $contactID ) {
					$verbose .= "New Person\t";
					// Add new record
					$SQLdata = array (
						'GivenName' => $field['GivenName'],
						'FamilyName' => $field['FamilyName'],
						'Email' => $field['Email'],
						'Origin' => "BiTS 2018"	 //Change for each list import
					);
					
					if ($p_write) {
						$db->table('contacts')->insert($SQLdata); 
						
					}
					
					
					$contactID = $this->LookupPersonByEmail($field['Email']);
					
				} else {
					$verbose .= "Found \t";
					// We already have the record selected based upon the LookupPersonByEmail
					if ($p_write) {
						$this->write_archive($contactID); // Save a copy first
					}
				}

					$db = \Config\Database::conect();
					$builder = $db->table('contacts');
					$builder->select('*');
					$builder->where('ContactID', $contactID);
					
					$query = $builder->get();
					$row = $query->getRowArray();
					
				
					$verbose .= $row['ContactID'] ."\t";
					
					// Better form would be to also check if the input field is non-empty before updating too...
										
					/*if (empty($row['CN_GivenName']) && !empty($field[) { $row['CN_GivenName'] = $field['CN_GivenName']; $verbose .= 'CN_GivenName '; }
					if (empty($row['CN_FamilyName']))  { $row['CN_FamilyName']	= $field['CN_FamilyName']; $verbose .= 'CN_FamilyName '; }
					if (empty($row['Nickname']))		{ $row['Nickname']	= $field['Nickname']; $verbose .= 'Nickname '; }
					if (empty($row['Company']))	 { $row['Company']		= $field['Company'];	 $verbose .= 'Company ';}
					if (empty($row['Title']))		 { $row['Title']		= $field['Job_Title']; $verbose .= 'Title ';}
					if (empty($row['Address1']))	 { 
							$row['Address1']	= $field['Street']; 
							$row['Address2']	= $field['Line2'];
							$verbose .= 'Address '; }
					if (empty($row['City']))		 { $row['City']		= $field['City']; $verbose .= 'City ';}
					if (empty($row['State']))		 { $row['State']		= $field['State']; $verbose .= 'State ';}
					if (empty($row['Country']))	 { $row['Country']		= $field['Country']; $verbose .= 'Country ';}
					if (empty($row['Pcode']))		 { $row['Pcode']		= $field['Postal_Code']; $verbose .= 'PCode ';}
					if (empty($row['Phone']))		 { $row['Phone']		= $field['Work']; $verbose .= 'Work ';}
					if (empty($row['CN_Company']))	 { $row['CN_Company']	= $field['CN_Company']; $verbose .= 'CN_Company ';}
					*/
					
					$verbose .= $this->update_record($row, $field, 'CN_GivenName', 'CN_GivenName');
					$verbose .= $this->update_record($row, $field, 'CN_FamilyName', 'CN_FamilyName');
					
					if ($field['Nickname'] != $field['GivenName']) {
						$verbose .= $this->update_record($row, $field, 'Nickname', 'Nickname');
					}
					
					$verbose .= $this->update_record($row, $field, 'Company', 'Company');
					$verbose .= $this->update_record($row, $field, 'Title', 'Job_Title');
					if (empty($row['Address1'])) {
						$verbose .= $this->update_record($row, $field, 'Address1', 'Street');
						$verbose .= $this->update_record($row, $field, 'Address2', 'Line2');
					}			
					$verbose .= $this->update_record($row, $field, 'City', 'City');
					$verbose .= $this->update_record($row, $field, 'State', 'State');
					$verbose .= $this->update_record($row, $field, 'Country', 'Country');
					$verbose .= $this->update_record($row, $field, 'City', 'City');
					$verbose .= $this->update_record($row, $field, 'PCode', 'Postal_Code');
					$verbose .= $this->update_record($row, $field, 'CN_Address', 'CN_Address');
					$verbose .= $this->update_record($row, $field, 'Phone', 'Work');
					$verbose .= $this->update_record($row, $field, 'Mobile', 'Mobile');
					$verbose .= $this->update_record($row, $field, 'CN_Company', 'CN_Company');
					//$verbose .= $this->update_record($row, $field, 'CN_Address', 'CN_Address');

										
					if ($field['Attendance'] == "EXPO Staff") {
						$row['Expo_mailing'] = "1"; // Switched to 1 = TRUE -1"; //Access uses -1 for TRUE
					}
					
					$row['DBuser'] = "script";
					$row['Active'] = "1"; //Switched 1 = True now "-1"; //Access uses -1 for TRUE
					$row['Tech_mailing'] = "1"; //"-1";
					$row['China_mailing'] = "1";
					$row['Stamp'] = date('Y-m-d H:i:s ');
					

					if ($p_write) {
						//d($row);
						$builder->where('ContactID', $contactID); 
						$builder->update($row);
						$verbose .= "record updated ";
					}
			
				 
				echo "<p>".$verbose."</p>";
			}
			
			
			
			return;
			
	}
	
	function read_tab_input_and_update ()
	{
		$update = TRUE;
		return $this->read_tab_input( $update );
	}
	
	function read_tab_input_for_attendance ($p_write = false)
	
	
	 {
		  define("YEAR", '2023');
		  define("EVENT", 'Mesa');
		  
		  set_time_limit(120);
		  // Set for UTF-16 Little-Endian
		  //ask ira
		  $this->load->library('tabreaderselect2');
	 
			 $filePath = './names_to_add.txt';
			 
			 /* For China
			 
			 $field_list = array ('Show', 'GivenName', 'FamilyName', 'CN_GivenName', 'CN_FamilyName',
			 		'Email', 'InviteCompanyID', 'RegID',
			 		'Attendance', 'Payment');
					//'Nickname', ' 'Company', 'Job_Title', 'Street', 'Line2',
					//'City', 'State', 'Country', 'Work', 'Mobile', 
			*/
			
			/* For Mesa */
			$field_list = array ('GivenName', 'FamilyName', //'CN_GivenName', 'CN_FamilyName',
			 		'Email', 'Tutorial', // 'InviteCompanyID', 'RegID',
			 		// 'Attendance', 'Payment');
				'No Show/Absent','Control', 'Registration Paid');
				
				//$data['csvData'] = $this->csvreader->parse_file($filePath);
			$data['csvData'] = $this->tabreaderselect2->parse_file($filePath, TRUE, $field_list);
			
			$records_processed = 0;
				foreach($data['csvData'] as $field) { 
				$records_processed++;
				$verbose= "Processing\t" . $field['GivenName'] ."\t" . $field['FamilyName'] ."\t" .
						$field['Email'] . "\t";
				
				$contactID = $this->LookupPersonByEmail($field['Email']);
				
				if (! $contactID ) {
					$verbose .= "Not Found - nothing changed\t";
					
				} else {
					$verbose .= "Found - adding attendance\t";

					// We found the person, check they aren't already in the attendnace record
					$db = \Config\Database::conect();
					$builder = $db->table('attendance');
					$builder->select('*');
					
					
					$where_criteria = array (
						'Email' => $field['Email'],
						'Year' => YEAR,
						'Event' => EVENT
					);					
		
					$builder->where($where_criteria);
					$query = $builder->get();
					if ( $query->getNumRows() > 0 ) {
						echo "Attendance record already exists for ". $field['Email'] . " no update made<br>";
					} else {
						// Okay, now create their attendance record

						/* For Mesa - comment out for China */
						$field['Attendance'] = 'Professional';
						$field['RegID'] = $field['Control'];
						$field['Payment']= $field['Registration Paid'];
						if ( empty($field['No Show/Absent'])) {
							$field['Show'] = 'No';
						} else {
							$field['Show'] = '';
						}				
						$field['InviteCompanyID'] = '';
						
						/* End of Mesa hacks */
						
						$show = '1'; // default everyone as a show
						if ($field['Show'] == "No") {
							$show = '0';
						}
						
						$tutorial = NULL;
						if ($field['Tutorial'] == "1") {
							$tutorial = '1';
						}
						

						$attendance_row = array(
							'ContactID' => $contactID,
							'Email'		=> $field['Email'],
							'Year'		=> YEAR,
							'Event'	=> EVENT,
							'Type'		=> $field['Attendance'],
							'Payment'	=> $field['Payment'],
							'Show'		=> $show,
							'InviteCompanyID' => $field['InviteCompanyID'],
							'EventRegID' => $field['RegID'],
							'Tutorial' => $tutorial
						  );
						if ($p_write) {
							$builder->insert($attendance_row);	
						}	  
					}						

				}
				 
				echo "<p>".$verbose."</p>";
			}
			
			//print_r ($data['csvData']);
			
			return;
			
	}

	function read_tab_input_for_attendance_update()
	{
		//askira
		return $this->read_tab_input_for_attendance ( TRUE );
	}
	
	function mark_inactive()
	// This reads a tab delimited file from Google on Mac OS
	
	 {
		  
		  set_time_limit(120);
		  //ask ira
		  $this->load->library('tabreaderselect2');
	 
			 $filePath = './inactive_names.txt';
			 $field_list = array ('Email','ContactID');
				
			$data['csvData'] = $this->tabreaderselect2->parse_file($filePath, TRUE, $field_list);
			
			$records_processed = 0;
				foreach($data['csvData'] as $field) { 
				$records_processed++;
				$verbose= "Processing " . $field['Email'] . "\t";
				
				$contactID = $this->LookupPersonByEmail($field['Email']);
				
				if ( $contactID == $field['ContactID']) {
				
					$verbose .= "Found\t";
					// We already have the record selected based upon the LookupPersonByEmail
					$this->write_archive($contactID); // Save a copy GivenName
					$db = \Config\Database::connect();
					$builder = $db->table('contacts');
					$builder->select('*');
					$builder->where('ContactID', $contactID);
					$query = $builder->get();
					$row = $query->getRowArray();
					
					
						
					$row['Notes'] = $row['Notes'] . " - Hard Bouncing, removed";	
					$row['DBuser'] = "script";
					$row['Active'] = "0"; //Access uses -1 for TRUE
					$row['Stamp'] = date('Y-m-d H:i:s ');
					
					$builder->where('ContactID', $contactID); 
					$builder->update($row);
					$verbose .= " - record updated ";
				} else {
					
					$verbose .= "Mismatch on ContactID - not updated";
				}
				 
				echo "<p>".$verbose."</p>";
			}
			
			
			return;
			
	}	
	
	private function expand_region($region)
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
	
	
	private function write_mailchimp($includeChinese)
	// Writes out CSV data for MailChimp, without the Chinese Fields
	{

 		
		$subscribers = 0;
		
		//echo "Number of rows in Contacts " . $this->db->count_all_results('Contacts') . "</p>";
			$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			

		
		
		// Query updated with switch of Active to TINYINT (0,1)
		$where_criteria = array (
			'Active' => "1",
			'Email is NOT NULL' => NULL,
			'EmailBounce' =>"0"
		);					
		$builder->where($where_criteria);
		

		
		$query = $builder->get();
		$num = $query->getNumRows();
		
		
		echo "# BiTS Database Export<br>";
		echo "# BiTS Workshop Confidential<br>";
		echo "# Date: " . date("Y-m-d h:i:sa") . "<br>";
		echo "# Number of rows: ". $num. "<br>";
		//echo "# Number of rows with EmailBounce:". $bounce."<br>";
		//echo "<p>Query: ". print_r($query) . "</p>";
		//echo "<p>FamilyName Query ". $this->db->FamilyName_query() . "</p>";
		//echo "<p>Error: ". print_r($this->db->error()) . "</p>";
		
		echo "ContactID,Email,Action,Given Name,Family Name,Nickname (optional),Technical Program Information,Exhibiting & Sponsoring Information,Preferred Language,EUCountry";
		if ($includeChinese) {
			echo ",Chinese Name (optional)"; 
		}
		echo "<br>";
		
		foreach ($query->getResultArray() as $row)
		{	
			
			echo $row['ContactID'] . ',' . $row['Email'] . ',';
			// Fixed Action flag
			echo "bits_db_exported". date("Y-m-d") .",";
			echo '"'. $row['GivenName'] . '","' . $row['FamilyName'] . '","' . $row['Nickname'] . '",';
			echo '"'.$this->expand_region($row['TechInfo']) . '",';
			echo '"'.$this->expand_region($row['ExhibitInfo']) . '",' . $row['Language'] . ',' . $row['EUCountry'];
			/* 
			if ( $row['Tech_mailing'] == 0) {
				echo "0,";
			} else {
				echo "1,";
			} 
			if ( $row['Expo_mailing'] == 0) {
				echo "0,";
			} else {
				echo "1,";
			}
			if ( $row['China_mailing'] == 0) {
				echo "0,";
			} else {
				echo "1,";
			}
			*/
			
			if ($includeChinese) {
				echo ',"'. $row['ChineseName'] . '"'; 
			}
			echo "<br>";
		
			$subscribers++;
			
		} //end for each
		
		//echo "<p>Total subscribers: ". $subscribers ."</p>";
		
		return;
		
	}
	
	function write_mailchimp_with_Chinese()
	{
		$chinese = TRUE;
		$this->write_mailchimp(TRUE);
	}
	
	function write_mailchimp_no_Chinese()
	{
		$chinese = FALSE;
		$this->write_mailchimp(FALSE);
	}

	function wrangler_list()
	// Writes out CSV data for Wrangler Reference
	{
			$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			
 		
		$subscribers = 0;
		
		//echo "Number of rows in Contacts " . $this->db->count_all_results('Contacts') . "</p>";
		

		
		
		// Query updated with switch of Active to TINYINT (0,1)
		$where_criteria = array (
			'Active' => "1",
			'Company <> "" ' => NULL,
			'FamilyName <> "" ' => NULL
		); 					
		
		$builder->where($where_criteria);

	
		$builder->orderby('Company ASC, FamilyName ASC, GivenName ASC');
		$query = $builder->get();
		$num = $query->getNumRows();
		
		echo "# BiTS Database Export<br>";
		echo "# BiTS Workshop Confidential<br>";
		echo "# Date: " . date("Y-m-d h:i:sa") . "<br>";
		echo "# Number of rows: ". $num. "<br>";
		//echo "<p>Query: ". print_r($query) . "</p>";
		//echo "<p>FamilyName Query ". $this->db->FamilyName_query() . "</p>";
		//echo "<p>Error: ". print_r($this->db->error()) . "</p>";
		
		echo "Company Name,GivenName Name,FamilyName Name,Nickname,Title";
		echo "<br>";
		
		foreach ($query->getResultArray() as $row)
		{	
			echo '"'. $row['Company'] . '",';
			echo '"'. $row['GivenName'] . '","' . $row['FamilyName'] . '","' . $row['Nickname'] . '",';
			echo '"'. $row['Title'] . '"';
			/* if ( $row['Active'] == 0) {
				echo "in-active";
			} else {
				echo "active";
			} */

			echo "<br>";
		
			$subscribers++;
			
		} //end for each
		
		//echo "<p>Total subscribers: ". $subscribers ."</p>";
		
		return;
		
	}
	
	
	function findGuests()
	// Reads the Guest database and checks that everyone has a MasterContactID
	// main BiTS database. IF not, it tries to find the person
	
	{
			$db = \Config\Database::connect('RegistrationDataBase');
			$builder = $db->table('guests');
			$builder->select('*');
		
		
		$where_criteria = array (
			'EventYear' => EventYear,
			//'Type !=' => "Professional",
			'MasterContactID is NULL' => NULL,
			'Email is NOT NULL' => NULL
		);					
		
		$builder->where($where_criteria);

		$builder->table('guests');
		$query = $builder->get();
		
		foreach ($query->getResultArray as $field) {
			$verbose= ""; //"Processing " . $field['Email'] . "\t";
			
			$contactID = $this->contacts->LookupPersonByEmail($field['Email'], FALSE);
			if ( $contactID ) {
				$verbose .= "Looked up email\t" . $field['Email'];
			}		
			
			if (! $contactID ) {
				$verbose .= "Looking up by name:\t".$field['GivenName']." ".$field['FamilyName'];
				$contactID = $this->contacts->LookupPersonByName($field['GivenName'],$field['FamilyName'], FALSE);
			}
			
			if ($contactID > 0 ) {
				$verbose .= "\tContactID:\t". $contactID;
			}
			echo $verbose ."<br>";
		}
	
	}
	
	
	function transfer_Guest_to_Main( $update = FALSE )
	// Reads the Guest database and checks that everyone in it are included in the
	// main BiTS database
	
	{
		$mine = new Contacts();
		
		
		
			$db = \Config\Database::connect('registration');
			$builder = $db->table('guests');
			$builder->select('*');
		
		$where_criteria = array (
			'EventYear' => EventYear,
			//'Type !=' => "Professional",
			//'MasterContactID is NOT NULL' => NULL,
			'Email is NOT NULL' => NULL
		);					
		
		$builder->where($where_criteria);

		//$builder->table('guests');
		$query = $builder->get();
		
		foreach ($query->getResultArray() as $field) {
			$verbose= "Processing " . $field['Email'] . "\t";
			
			$contactID = $mine->LookupPersonByEmail($field['Email'], FALSE);
			if (( $field['MasterContactID'] > 0) && ($contactID !== $field['MasterContactID']) ){
				if ($contactID <= 0) {
					$verbose .= "*** Not found by email - MasterContactID " . $field['MasterContactID'] . " ";	
				} else {
					$verbose .= "*** MISMATCH between MasterContactID " . $field['MasterContactID'] . " and found by email " . $contactID ." ";
				}
				// Use the MasterID to override
				$contactID = $field['MasterContactID'];
			} 
					
			$existing_person = FALSE;
			$updated = FALSE;
			//$verbose .= " {". $contactID . "} ";
			if ($contactID < 1  ) {
				if (empty($field['Email'])) {
					$verbose .= "**** Skipped since empty email address\t";
				} else {
					$verbose .= "New Person\t";
					// Add new record
					$SQLdata = array (
						'GivenName' => $field['GivenName'],
						'FamilyName' => $field['FamilyName'],
						'Email' => $field['Email'],
						'Origin' => EventYear
					);
					if ( $update ) {
						$builder->insert($SQLdata);
						//$query = $this->db->get();
						//$row = $SQLdata; //$query->row_array();
			
						$contactID = $mine->LookupPersonByEmail($field['Email']);
					}
				}
			} else {
				$verbose .= "using\t";
				
				$existing_person = TRUE;
				// We already have the record selected based upon the LookupPersonByEmail
				// Do only if there is an update... $this->write_archive($contactID); // Save a copy GivenName

			}

			$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			$builder->where('ContactID', $contactID);
			
			
		
			$query = $builder->get();
			$row = $query->getRowArray();
		
	if(! empty($row['ContactID'])){
		$verbose .= $row['ContactID'];}
		else{
		$verbose .= ' new ';
		}
	
			
						
			// if (empty($row['CN_GivenName'])) { 
// 			//if (! empty($field['CN_GivenNameName'])) { 
// 				$row['CN_GivenName'] = $field['CN_GivenName'];
// 				$updated = TRUE;
// 			}
// 			if (empty($row['CN_FamilyName']))  {
// 			//if (! empty($field['CN_FamilyNameName']))	{
// 				$row['CN_FamilyName']  = $field['CN_FamilyName'];
// 				$updated = TRUE;
// 			}

			if (empty($row['Nickname']))		{
				$row['Nickname']	= $field['NameOnBadge'];
				$updated = TRUE;
			}
			if (empty($row['Company']))	 { 
				$row['Company']		= $field['Company'];
				$updated = TRUE;
			}
			if (empty($row['Title']))		 { 
				$row['Title']		= $field['Title'];
				$updated = TRUE;
			}
			if (empty($row['Address1']))	 { 
				$row['Address1']	= $field['Address1']; 
				$row['Address2']	= $field['Address2'];
				$updated = TRUE;
			}
			if (empty($row['City']))		 {
				$row['City']		= $field['City'];
				$updated = TRUE;
			}
			if (empty($row['State']))		 {
				$row['State']		= $field['State'];
				$updated = TRUE;
			}
			if (empty($row['Country']))	 {
				$row['Country']		= $field['Country'];
				$updated = TRUE;
			}
			if (empty($row['Pcode']))		 {
				$row['Pcode']		= $field['PCode'];
				$updated = TRUE;
			}
			if (empty($row['Phone']))		 {
				$row['Phone']		= $field['Phone'];
				$updated = TRUE;
			}
			if (empty($row['Mobile']))		 { 
				$row['Mobile']			= $field['Mobile'];
				$updated = TRUE;
			}

			if ( $existing_person and $updated and $update) {
				// Save a record to the Archive
				$this->write_archive($contactID); // Save a copy GivenName
			}
			
			$row['DBuser'] = "script";
			$row['Active'] = "1"; 
			$row['Tech_mailing'] = "1";
			// $row['China_mailing'] = "1";
			// Add to right mailing lists here
			//
			//
			$row['Stamp'] = date('Y-m-d H:i:s ');
		
			$builder->where('ContactID', $contactID); 
			if ($update) {
				$builder->update($row);
				$verbose .= " - record updated ";
			}
			
			echo $verbose . "<br>";
		}
	}
	
	function preview_Guest_to_Main()
	{
		$this->transfer_Guest_to_Main ();
	}

	function update_Guest_to_Main()
	{
		$this->transfer_Guest_to_Main ( TRUE );
	}	
	
	function China_MailChimp()
	// Reads the BiTS China database and generates control information for MailChimp
	
	{	
	//askira
		$db = \Config\Database::connect('ChinaDataBase');
		$builder = $db->table('guests');
		$query = $builder->get();
		
		/* $where_criteria = array (
			'Active' => "-1",
			'Email is NOT NULL' => NULL
		);					
		*/
		//$ChinaDB->where($where_criteria);
		echo "Email,ChinaControl,ChinaCompany,RegCode<br>";
		
		
		
		foreach ($query->getResultArray() as $field) {
			$company = $this->FindCompanyByID($field['InvitedByCompanyID'], $ChinaDB);
			
			$invited="Invited";
			if (empty($company)) {
				$invited = "EXPO_Staff";
			}
			if ($field['InvitedByCompanyID'] == 21) { // mask BiTS Workshop for committee & authors
				$invited="Paid";
				$company="";
			}
			$regcode= $field['ContactID'] + 1000;
			
			echo $field['Email'] . ',' . $invited . ',"' . $company . '",'. $regcode . '<br>';
		}
			
	}		

	function China_Name_Badges()
	// Reads the BiTS China database and generates a CSV for the name badges
	{
		$db = \Config\Database::connect('ChinaDataBase');
		$builder = $db->table('guests');
		$query = $builder->get();
		
		
		/* $where_criteria = array (
			'Active' => "-1",
			'Email is NOT NULL' => NULL
		);					
		*/
		//$ChinaDB->where($where_criteria);
		echo "ChineseName,Given FAMILY,Company,nnnnaaaa<br>";
		
		
		$query = $builder->get();
		
		foreach ($query->getResultArray() as $field) {
		
			$chineseName = $field['CN_FamilyNameName'] . $field['CN_GivenNameName'];
			$company = $field['Company'];
			if (empty($company)) {
				$company = $field['CN_Company'];
			}
			$name = $field['NameOnBadge'];
			if (empty($name)) {
				$name = $field['GivenName'];
			}
			$name .= ' '.strtoupper($field['FamilyName']);
			$code = 1000 + $field['ContactID'];
			$aaaa = substr(strtolower($field['Email']),0,4);
			
			echo $chineseName . ',' . $name . ',"' . $company . '",' . $code . $aaaa . '<br>';
			//echo $chineseName . "\t" . $name . "\t\"" . $company . "\"\t" . $code . $aaaa . '<br>';
		}
			
	}		

	function read_JotForm_tab_input()
	// This reads a tab delimited file from Google on Mac OS
	
	 {
		  $test_run = TRUE;
		  
		  set_time_limit(120);
		  // Set for UTF-16 Little-Endian
		  $this->load->library('tabreaderselect2');
	 
			 $filePath = './names_to_add.txt';
			 $field_list = array ('GivenName/Given Name', 'FamilyName/Family Name', 
					'Attendee\'s Full Name (Mandarin) (GivenName/Given Name)', 
					'Attendee\'s Full Name (Mandarin) (FamilyName/Family Name)',
					'GivenName Name on Badge (English)',
					'Attendee\'s Email', 'Company / Organization', 'Job Title', 
					'Street Address', 'Street Address Line 2 and/or Mailstop',
					'City', 'State / Province', 'Country', 'Postal / Zip Code','Work Phone', 'Mobile');

				
				//$data['csvData'] = $this->csvreader->parse_file($filePath);
			$data['csvData'] = $this->tabreaderselect2->parse_file($filePath, TRUE, $field_list);
			
			$records_processed = 0;
				foreach($data['csvData'] as $field) { 
				$records_processed++;
				$verbose= "Processing\t" . $field['GivenName/Given Name'] ."\t" . $field['FamilyName/Family Name'] ."\t" .
						$field['Attendee\'s Email'] . "\t";
				
				$contactID = $this->LookupPersonByEmail($field['Attendee\'s Email']);
				
				$existing_person = FALSE;
				$updated = FALSE;
				if ( $contactID > 0) {
					$verbose .= "Found\t";
					// We already have the record selected based upon the LookupPersonByEmail
					//$this->write_archive($contactID); // Save a copy GivenName
					$existing_person = TRUE;
				} else {
					$verbose .= "New Person\t";
					// Add new record
					$SQLdata = array (
						'GivenName' => $field['GivenName/Given Name'],
						'FamilyName' => $field['FamilyName/Family Name'],
						'Email' => $field['Attendee\'s Email'],
						'Origin' => "BiTS China 2016 - JotForm"  //Change for each list import
					);
					if (! $test_run) {
						$builder->insert($SQLdata);
					}
					//$query = $this->db->get();
					//$row = $SQLdata; //$query->row_array();
					
					$contactID = $this->LookupPersonByEmail($field['Attendee\'s Email']);
					
				}

			$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			$builder->where('ContactID', $contactID);
				
				$query = $builder->get();
				$row = $query->getRowArray();
				
			
				$verbose .= $row['ContactID'];
								
				if (empty($row['CN_GivenName'])) { 
					$row['CN_GivenName'] = $field['Attendee\'s Full Name (Mandarin) (GivenName/Given Name)'];
					$updated = TRUE;
				}
				if (empty($row['CN_FamilyName']))  {
					$row['CN_FamilyName']  = $field['Attendee\'s Full Name (Mandarin) (FamilyName/Family Name)'];
					$updated = TRUE;
				}

				if (empty($row['Nickname']))		{
					$row['Nickname']	= $field['GivenName Name on Badge (English)'];
					$updated = TRUE;
				}
				if (empty($row['Company']))	 { 
					$row['Company']		= $field['Company / Organization'];
					$updated = TRUE;
				}
				if (empty($row['Title']))		 { 
					$row['Title']		= $field['Job Title'];
					$updated = TRUE;
				}
				if (empty($row['Address1']))	 { 
					$row['Address1']	= $field['Street Address']; 
					$row['Address2']	= $field['Street Address Line 2 and/or Mailstop'];
					$updated = TRUE;
				}
				if (empty($row['City']))		 {
					$row['City']		= $field['City'];
					$updated = TRUE;
				}
				if (empty($row['State']))		 {
					$row['State']		= $field['State / Province'];
					$updated = TRUE;
				}
				if (empty($row['Country']))	 {
					$row['Country']		= $field['Country'];
					$updated = TRUE;
				}
				if (empty($row['Pcode']))		 {
					$row['Pcode']		= $field['Postal / Zip Code'];
					$updated = TRUE;
				}
				if (empty($row['Phone']))		 {
					$row['Phone']		= $field['Work Phone'];
					$updated = TRUE;
				}
				if (empty($row['Cell']))		 { 
					$row['Cell']			= $field['Mobile'];
					$updated = TRUE;
				}

				if ( $existing_person and $updated ) {
					// Save a record to the Archive
					$this->write_archive($contactID); // Save a copy GivenName
					$verbose .= " - record updated ";
				}
				
				/* if ($field['Attendance'] == "EXPO Staff") {
					$row['Expo_mailing'] = "-1"; //Access uses -1 for TRUE
				} */
				
				$row['DBuser'] = "script";
				$row['Active'] = "-1"; //Access uses -1 for TRUE
				$row['Tech_mailing'] = "-1";
				$row['China_mailing'] = "1";
				$row['Stamp'] = date('Y-m-d H:i:s ');
				
				if (! $test_run) {
					$builder->where('ContactID', $contactID); 
					$builder->update($row);
				}
			
				 
				echo "<p>".$verbose."</p>";
			}
			
			//print_r ($data['csvData']);
			
			return;
			
	}
	
	// Next time re-write this and do a mapping from the CSV/Tab file to an intermediate array
	// so only need to change the field names once (at the time of reading...)
	
	function read_MikeCRM_tab_input()
	// This reads a tab delimited file from Google on Mac OS
	
	// Should update to ony update if there is new data (non-empty) field from JotForm side too.
	 {
		$test_run = FALSE;
		  
		  set_time_limit(120);
		  // Set for UTF-16 Little-Endian
		  //ask ira
		  $this->load->library('tabreaderselect2');
	 
			 $filePath = './names_to_add.txt';
			 $field_list = array ('Email','GivenName', 'FamilyName', 
					'CN_GivenNameName','CN_FamilyNameName', 
					'NameOnBadge',
					'Title', 'Company',	
					'Address1', 'Address2',
					'City', 'State', 'Country', 'PCode','Phone', 'Mobile');

				
				//$data['csvData'] = $this->csvreader->parse_file($filePath);
			$data['csvData'] = $this->tabreaderselect2->parse_file($filePath, TRUE, $field_list);
			
			$records_processed = 0;
				foreach($data['csvData'] as $field) { 
				$records_processed++;
				$verbose= "Processing\t" . $field['GivenName'] ."\t" . $field['FamilyName'] ."\t" .
						$field['Email'] . "\t";
				
				$contactID = $this->LookupPersonByEmail($field['Email']);
				
				$existing_person = FALSE;
				$updated = FALSE;
				if ( $contactID > 0) {
					$verbose .= "Found\t";
					// We already have the record selected based upon the LookupPersonByEmail

					$existing_person = TRUE;
				} else {
					$verbose .= "New Person\t";
					// Add new record
					$SQLdata = array (
						'GivenName' => $field['GivenName'],
						'FamilyName' => $field['FamilyName'],
						'Email' => $field['Email'],
						'Origin' => "BiTS China 2016 - MikeCRM"  //Change for each list import
					);
					if (! $test_run) {
						$db = \Config\Database::connect();
						$builder = $db->table('contacts');
						$builder->select('*');
						$builder->insert($SQLdata);
					}
					//$query = $this->db->get();
					//$row = $SQLdata; //$query->row_array();
					
					$contactID = $this->LookupPersonByEmail($field['Email']);
					
				}

				$db = \Config\Database::connect();
				$builder = $db->table('contacts');
				$builder->select('*');
				$builder->where('ContactID', $contactID);
				
				$query = $builder->get();
				$row = $query->getRowArray();
				
			
				$verbose .= $row['ContactID'];
								
				if (empty($row['CN_GivenName'])) { 
					$row['CN_GivenName'] = $field['CN_GivenNameName'];
					$updated = TRUE;
				}
				if (empty($row['CN_FamilyName']))  {
					$row['CN_FamilyName']  = $field['CN_FamilyNameName'];
					$updated = TRUE;
				}

				if (empty($row['Nickname']))		{
					$row['Nickname']	= $field['NameOnBadge'];
					$updated = TRUE;
				}
				if (empty($row['Company']))	 { 
					$row['Company']		= $field['Company'];
					$updated = TRUE;
				}
				if (empty($row['Title']))		 { 
					$row['Title']		= $field['Title'];
					$updated = TRUE;
				}
				if (empty($row['Address1']))	 { 
					$row['Address1']	= $field['Address1']; 
					$row['Address2']	= $field['Address2'];
					$updated = TRUE;
				}
				if (empty($row['City']))		 {
					$row['City']		= $field['City'];
					$updated = TRUE;
				}
				if (empty($row['State']))		 {
					$row['State']		= $field['State'];
					$updated = TRUE;
				}
				if (empty($row['Country']))	 {
					$row['Country']		= $field['Country'];
					$updated = TRUE;
				}
				if (empty($row['Pcode']))		 {
					$row['Pcode']		= $field['PCode'];
					$updated = TRUE;
				}
				if (empty($row['Phone']))		 {
					$row['Phone']		= $field['Phone'];
					$updated = TRUE;
				}
				if (empty($row['Cell']))		 { 
					$row['Cell']			= $field['Mobile'];
					$updated = TRUE;
				}

				if ( $existing_person and $updated ) {
					// Save a record to the Archive
					if (! $test_run) {
						$this->write_archive($contactID); // Save a copy GivenName
					}
					$verbose .= " - record updated ";
				}
				
				/* if ($field['Attendance'] == "EXPO Staff") {
					$row['Expo_mailing'] = "-1"; //Access uses -1 for TRUE
				} */
				
				$row['DBuser'] = "script";
				$row['Active'] = "-1"; //Access uses -1 for TRUE
				$row['Tech_mailing'] = "-1";
				$row['China_mailing'] = "1";
				$row['Stamp'] = date('Y-m-d H:i:s ');
				
				if (! $test_run) {
					$builder->where('ContactID', $contactID); 
					$builder->update($row);
				}
			
				 
				echo "<p>".$verbose."</p>";
			}
			
			//print_r ($data['csvData']);
			
			return;
			
	}
	
	function swap_emails()
	// This reads a tab delimited file from Google on Mac OS
	// set up for UTF-16 LE
	
	// It looks up the email address and if it is the person's LinkedIn email address
	// it switches it in the output their primary email address
	// Writes the output as a CSV 
	{
		  
		  set_time_limit(120);
		  // Set for UTF-16 Little-Endian
		  //ask ira
		  $this->load->library('tabreaderselect2');
	 
			 $filePath = './emails_to_swap.txt';
			 $field_list = array ('Username', 'Password', 'Email', 'ChinaControl');
				
			 $data['csvData'] = $this->tabreaderselect2->parse_file($filePath, TRUE, $field_list);
		  d($data['csvData']);
			
			echo "Username,Password,Email,ChinaControl<br>";
			
			$records_processed = 0;
			foreach($data['csvData'] as $field) { 
				$records_processed++;
				//$verbose= "Processing\t" . $field['GivenName'] ."\t" . $field['FamilyName'] ."\t" .
				//		$field['Email'] . "\t";
				
				$contactID = $this->LookupPersonByEmail($field['Email'], false);
				
				echo $field['Username'] . "," . $field['Password'] . ",";
				
				if ($contactID > 0) {
					echo $field['Email'];
				} else {
					// We didn't find them using their primary email addressType
					$contactID = $this->LookupPersonByLinkedInEmail($field['Email']);
					if ($contactID > 0) {
						$db = \Config\Database::connect();
						$builder = $db->table('contacts');
						$builder->select('*');
						$builder->where('ContactID', $contactID);
						
						
						$query = $builder->get();
						$row = $query->getRowArray();

						echo $row['Email'];
					} else {
						echo "<br>*** No match found for " . $field['Email'] . "<br>";
					}
				}
				
				echo "," . $field['ChinaControl'] . "<br>";
			}
			
			return;
		}	
	
	function lookup_expo_entry( $CompanyID, $Year, $Event)
	{
		$db = \Config\Database::connect('registration');
		
		$builder = $db -> table('expodirectory');
		
		
		$where_criteria = array (
			'CompanyID' => $CompanyID,
			'Year' => $Year,
			'Event' => $Event
		);					
		$builder->where($where_criteria);

		
		// should probably check that there is only 1 row
		$query = $builder->get();
		if ($query->getNumRows() > 0) {
			if ($query->getNumRows() > 1 ) {
				echo "Warning: more than row returned for CompanyID ". $CompanyID . " Year " . $Year . " Event " . $Event . "<br>";
			}
			$row = $query->getRowArray();
			return $row;
					
		} else {
			return FALSE;
		}
	}
		
	function list_expo_entries() 
	// Reads the BiTS EXPO registration database and dumps the entries
	{
		$Year = 2018;
		$Event = "Suzhou";
		$PriorEvent = "Shanghai";
		
		$db = \Config\Database::connect('registration');
		$builder = $db->table('expodirectory');
		$where_criteria = array (
			'Year' => $Year,
			'Event' => $Event
		);					
		
		$builder->where($where_criteria);
		$builder->orderby('CompanyName','ASC');
		
		
		$query = $builder->get();
		
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
			$old = $this->lookup_expo_entry( $field['CompanyID'], ($Year-1), $PriorEvent);
			$old_id = $old['EntryID'];
			//echo "Old record = " . $old['EntryID'] . "<br>";
			
			for ($i = 1; $i <= 6 ; $i++) {
				if ( ($old_id > 0) && ( $field['Line'.$i] == $old['Line'.$i])) {
					echo "  ";
				} else {
				    echo "* ";
				}
				echo $field['Line'.$i] . "<br>";
			}
			
			echo "<br>";
			if ( ($old_id > 0) && ( $field['Description'] == $old['Description'] )) {
				echo "(no change)<br>";
			} else {
				echo "New or updated description:<br>";
			}
			echo $field['Description'] . "<br>";
			echo "<br>";
			
		}
			
	}	
	
	
	// In preview mode until $write is set TRUE
	private function add_to_attendance_database ($write = FALSE) {
	$mine = new Contacts();

		$start_time = microtime(TRUE);
		//$this->diag_log("mailinglist: starting function add_to_attendance_database");
		$db = \Config\Database::connect('registration');
		$builder = $db->table('guests');
		$builder->where('EventYear', EventYear);
		
		$query = $builder->get();

				
	//askira
		//$this->db = $this->load->database('default', TRUE);
		
		echo "<h4>Event: " . EventYear . "</h4>";
		echo "<p>Found  " . $query->getNumRows() . " number of rows</p>";
		
		echo "Record\tEmail\tContactID\tPayment<br>";
		foreach ($query->getResult() as $row) {
			$status = $row->ContactID . "\t" . $row->Email . "\t";
			
			if ($row->MasterContactID > 0) {
				$contactID = $row->MasterContactID;
			} else {
				// Use email to look them up
				$contactID = $mine->LookupPersonByEmail($row->Email, FALSE);
			}
			
			if ( !$contactID ) {
				$status .= "Error finding person - skipped";
			} else {
				
				// Update payment related fields depending on which event
				if (Event == "Mesa") {
					$payment = 0;
					$start = strpos($row->Fees,"Amount:");
					if ( $start > 0) {
						$payment = substr($row->Fees, $start+8, strpos($row->Fees," USD")-$start-8);
					}
					
					if ( $row->Complimentary == "1") {
						$payment = "Complimentary";
					}
								
					$type = $row->Type;		
					$invitedBy = "";
					$regID = $row->Control;
									
				} else {
					// Hard coded for China
					// Was this done better for 2018?
					
					$payment = "Vendor Invite";
					if (in_array($row->InvitedByCompanyID, array(73, 74, 80, 81))) {
						$payment = "Paid";
					}
					if (in_array($row->InvitedByCompanyID, array(71, 72))) {
						$payment = "TestConX Guest";
					}				
					if (in_array($row->InvitedByCompanyID, array(82, 83))) {
						$payment = "EXPO Coordinator";
					}					
					if (in_array($row->InvitedByCompanyID, array(85, 87))) {
						$payment = "Unregistered";
					}
					
					$type = "Full Conference";
					$invitedBy = $row->InvitedByCompanyID;
					$regID = $row->ContactID;
				}
				
				$status .= $contactID . "\t" . $payment . "\t";
				
				$show = '1';
				if ($row->NoShow == 'Yes') {
					$show = '0';
				}
								
				// Add new record
				$SQLdata = array (
					'ContactID' => $contactID,
					'Email' => $row->Email,	
					'Year' => Year,
					'Event' => Event,
					'Type' => $type, 
					'Payment' => $payment,
					'Show' => $show,
					'InviteCompanyID' => $invitedBy,
					'EventRegID' => $regID,
					'Tutorial' => $row->Tutorial
				);	

			
				if ($write) {
					$db = \Config\Database::connect();
					$builder = $db->table('attendance');
					$builder->insert($SQLdata);
					$status .= "\tAdded new attendance record\t";
				} else {
					$status .= "\tNot in write mode";
				}	
						
			}


			echo $status . "<br>"; 
	 	
		 	//set_time_limit(30); // Reset to keep from timing out on long runs
		}

		
		//$message = "Finished adding to BiTS DB at WordPress ID ". $wp_ID;
		//$this->diag_log("s2_match_dp: " . $message);
		//echo "<p>" . $message . "</p>";
		
		$elapsed_time = microtime(TRUE) - $start_time;
		$message = "Total execution time " . $elapsed_time;
		//$this->diag_log("post_china: " . $message);
		echo "<p>" . $message . "</p>";	
		
	}
	
	function check_add_to_attendance() {
		$this->add_to_attendance_database();
	}
	
	function add_to_attendance() {
		$this->add_to_attendance_database(TRUE);
	}	

}
/* End of file main.php */

?>
