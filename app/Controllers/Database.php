<?php  //4 if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Some variables for each year
//$BiTSEvent = "BiTS China 2016";

//Set in Main // session_start(); // Remember things across pages

namespace App\Controllers;

use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;

// Make sure you are logged in to access these functions.
$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

} 

class Database extends BaseController {

 
	function __construct()
	{
		//X parent::__construct();

		/* Standard Libraries of codeigniter are required */
		//4 $this->load->database();
	//X	$db = \Config\Database::connect();
		
		//4 $this->load->helper('url');
		//4 $this->load->helper('string');
		helper('text');

		/* ------------------ */ 

		//X $this->load->library('grocery_CRUD');
		

 
	}
 
	public function index()
	{
			
		echo "<h1>TestConX Database - TestConX Office use only</h1>";
		echo "<h4>TestConX Confidential</h4>";
		echo "<OL>";
		echo "<LI>Manage <a href=" . site_url('/database/contacts') . ">Contacts</a></LI>";

//UPDATE LATER
		echo "<LI>Manage <a href=" . site_url('/database/companies') . ">Companies</a></LI>";
		
		echo "<LI>Manage <a href=" . site_url('/presentations') . ">Presentations Cruds</a></LI>";
	
		echo "<br><br>";
		echo "<ul>";
		//echo "<LI>Process <a href=" . site_url('/s2_match_db') . ">WordPress / s2member</a></LI>";
		echo "<LI>Tools for <a href=" . site_url('/mailinglist') . ">Mailing lists</a></LI>";		

		echo "<br><br>";
		
		echo "<LI><a href=" . site_url('/expo') . ">EXPO ENTRIES</a></LI>";
		echo "<LI><a href=" . site_url('/badge') . ">Badges</a></LI>";
		echo "</OL>";
	}

    function contacts()
	{

		$crud = $this->_getGroceryCrudEnterprise();

        $crud->setCsrfTokenName(csrf_token());
        $crud->setCsrfTokenValue(csrf_hash());

        $crud->setTable('contacts');
        $crud->setSubject('Contact', 'Contacts');
		
		$crud->columns (['ContactID','GivenName','FamilyName','ChineseName','Company','Email','Active']);


		$crud->uniqueFields(['Email']);
		
		// Try restricting fields...
		$crud->fields (['ContactID','DBuser','Email',
			'Abbr','GivenName','FamilyName','Nickname',
			'ChineseName',
			'Active', 'TechInfo', 'ExhibitInfo',
			'CorrespondenceType','Email_only', 'Record_type',
			'Title','Company', 'ParentCompanyID','CN_Company', 'LinkedInEmail',
			'LinkedInURL','LinkedInGroup','WordPressID',
			'Website',
			'Address1', 'Address2', 'City', 'State', 'PCode', 'Country', 
			'CN_Address','Phone', 'Mobile',
			'Language','EUCountry',
			'Source','Origin','Notes','EmailBounce',
			'Expo_mailing', 'Tech_mailing', 'China_mailing',
			'EmailPermission', 'PostalPermission', 'AppPermission']		);
		//For some reason DBuser does not work after Notes in the fields list 
		// Not using 'Solicitation'
		

	
		$crud->setRelation('ParentCompanyID','company','{Name} (ID:{CompanyID} IsParent:{IsParent})',"(IsParent = '1' OR ParentID IS NULL)");	
		//GC V3 does not support ORDER BY Name ASC]);

	
		
		//4 $crud->fieldType('ContactID','readonly');
		$crud->readOnlyFields(['ContactID','DBuser','Expo_mailing','Tech_mailing','China_mailing',
			'EmailPermission','PostalPermission','AppPermission']);
		
		// Don't do this: crud->fieldType('DBuser','readonly');
		// If you mark DBuser as readonly the callback before update can't write the user name
		// $crud->fieldType('DBuser','readonly');
		// so if you don't want it to appear, it should be a hidden field
		// And if you don't do all the fields, if you leave DBUser out of the field list, the
		// callback can't write it either unless you set it as invisible
	
//		$crud->requiredFields(['Active']);
//		$crud->fieldType('Active','boolean'); //'enum',['No','Yes']);
		$crud->fieldType('Active','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('Expo_mailing','dropdown',['0' => 'No', '1'=>'Yes']);
		//$crud->fieldType('Tech_mailing','dropdown',['0' => 'No', '1'=>'Yes']);
		//$crud->fieldType('China_mailing','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('EmailBounce','dropdown',['0' => 'No', '1'=>'Yes']);

		$crud->fieldType('Email_only','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('Solicitation','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('TechInfo','enum',['worldwide','North America','China','Asia','Europe','none']);					
		$crud->fieldType('ExhibitInfo','enum',['worldwide','North America','China','Asia','Europe','none']);			

		$crud->fieldType('Language','enum',['English','Chinese']);
		$crud->fieldType('EUCountry','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('LinkedInGroup','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('Source', 'invisible'); // no longer using this field - combined with Origin
		$crud->displayAs('LinkedInGroup','In LinkedIn Group?');
		
		$crud->callbackBeforeUpdate(function ($stateParameters) {
			$this->write_archive($stateParameters->primaryKeyValue);
			
			$stateParameters->data['DBUser'] = $this->determine_user();
			return $stateParameters;
		});

		$crud->callbackBeforeInsert(function ($stateParameters) {
			$stateParameters->data['DBUser'] = $this->determine_user();
			return $stateParameters;
		});
		

		$crud->callbackBeforeDelete(function ($stateParameters) {
			$this->write_archive($stateParameters->primaryKeyValue, TRUE);
			return $stateParameters;
		});
		
		$output = $crud->render();
      	return $this->_example_output($output);
	}  



   function companies()
   {

		
		// New way
		$crud = $this->_getGroceryCrudEnterprise();

        $crud->setCsrfTokenName(csrf_token());
        $crud->setCsrfTokenValue(csrf_hash());

        $crud->setTable('company');
        $crud->setSubject('Company', 'Companies');
        
        	
		
		$crud->columns (['CompanyID','Name','CN_Name','ParentID', 'IsParent']);
		
		
		
		$crud->fields (['CompanyID','Name','CN_Name','ParentID','IsParent',
			'URL', 'Stock_Market','Ticker_Symbol',
			'Research','BiTS_DB','BiTS_Attend','BiTS_Expo','BiTS_Sponsor',
			'BiTS_Outreach', 'BiTS_China', 'FEC_Client', 'Acquired',
			'Summary', 'Notes',
			'Street1','Street2','City','State','Postal','Country', 'CN_Address',
			'PhoneCountryCode','Phone',
			'Market1', 'Market2', 'Market3', 'Market4', 'Market5', 'Market6', 
			'Market7', 'Market8', 'MarketTestConX',
			'Added', 'Updated']);
			
		// Grocery Crud does not allow a set_relation back into the same table...
		//$crud->setRelation('ParentID','company','Parent Name'); //'{Name}'); // {IsParent}',null,'Name ASC');
		$crud->setRelation('Market1','markets','{Market} - {ID}');
		$crud->setRelation('Market2','markets','{Market} - {ID}');
		$crud->setRelation('Market3','markets','{Market} - {ID}');
		$crud->setRelation('Market4','markets','{Market} - {ID}');
		$crud->setRelation('Market5','markets','{Market} - {ID}');
		$crud->setRelation('Market6','markets','{Market} - {ID}');
		$crud->setRelation('Market7','markets','{Market} - {ID}');
		$crud->setRelation('Market8','markets','{Market} - {ID}');
		$crud->setRelation('MarketTestConX','markets','{Market} - {ID}');
		
		//$crud->fieldType('CompanyID','readonly');
	//	$crud->fieldType('Added','readonly');
		//$crud->fieldType('Updated','readonly');
		$crud->readOnlyFields(['CompanyID','Added','Updated']);
		
		$crud->fieldType('IsParent','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('Research','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('BiTS_DB','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('BiTS_Attend','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('BiTS_Expo','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('BiTS_Sponsor','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('BiTS_Outreach','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('BiTS_China','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('FEC_Client','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('TSensors','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('Rosenberger-OSI','dropdown',['0' => 'No', '1'=>'Yes']);
		$crud->fieldType('Acquired','dropdown',['0' => 'No', '1'=>'Yes']);

		$crud->displayAs('CN_Name','Chinese Name');
		$crud->displayAs('CN_Address','Chinese Address');
		
		$output = $crud->render();
      	return $this->_example_output($output);

	}  


	
	// Determine who the current user is 
	function determine_user() {

		$session = session();
		
		if (isset($session->tcx_userdata) && isset($session->tcx_userdata['username'])) {
			$user = $session->tcx_userdata['username'];
		} else {
	 		$user =  "local_user"; 	
	 	}
	 	
	 	return $user;
	 }
	
	function write_archive ($primary_key, $delete = FALSE) {
		//There may be a data structure with the original record somewhere, but haven't found it.
		//So simply retrieve it prior to writing the updated record
		$db = \Config\Database::connect();
		$builder = $db->table('contacts');
		$builder->select('*');

		$builder->where('ContactID', $primary_key);
		$old_query = $builder->get();

		// We've also assumed only one record got returend since primary_key is unique
		// Better to check, etc.
		if ($old_query->getNumRows() !== 1) {
			die("ERROR: More than one row found for ContactID = " . $primary_key . " in contacts table");
		} 
		
		//If we have more than 1 row, for some reason, only the first row will be written to the archive
		$new_row = $old_query->getRowArray(); 
		
		//Save the ContactID since the ForeignKey relationship will set the ContactID to NULL when the 
		//record is deleted from Contacts
		$new_row['OriginalContactID'] = $new_row['ContactID'];
		
		// Modify the notes field to indicate who deleted this record
		if ( $delete ) {
			$new_row['Notes'] = "DELETED " . date("Y-m-d h:i:sa") . " by ". $this->determine_user(). " ". $new_row['Notes'];
		}
	
		$db->table('contacts_archive')->insert($new_row); 
	
		return;
	}
	
/*	
	// Record the user into the updated or inserted record
	function record_user($post_array) {
 		$post_array['DBuser'] = $this->determine_user();
	 
	 	return $post_array;
	}
	 
	// Saves a copy of the original contact record in the Contacts_Archive table
	// And updates the DBUser in the record before it is updated
	function archive_contact_record_update($post_array, $primary_key) {
 		
		$this->write_archive ($primary_key);
 		
 		$post_array = $this->record_user($post_array);
 		
		return $post_array;
	}
	
	function contact_record_insert($post_array) { 		
		$post_array = $this->record_user($post_array);
 		
		return $post_array;
	}
	
	
	function archive_contact_record_delete($primary_key) {
 		
		$this->write_archive ($primary_key, TRUE);
 		
		return $post_array;
	}
	
*/
	/* function _example_output($output = null)
    {
        view('bits_template.php',$output);    
    }  
    */

    private function _example_output($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo $output->output;
            exit;
        }

        return view('testconx_template.php', (array)$output);
    }

    private function _getDbData() {
        $db = (new ConfigDatabase())->default;
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
    
} 

