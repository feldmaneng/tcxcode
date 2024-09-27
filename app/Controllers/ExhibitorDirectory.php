<?php

	
// Installation notes:
//   Upload.php controller requires PHP fileinfo extension enabled.
//   Did so by turning on via cPanel for Rochen running PHP 7.0

// Future (non-urgent) enhancements to consider:
//   A user uploading a new file with the same name as their prior file overwrites the existing file.
//      perhaps a way to increment the file name?
//   Remove need for the upload button - upload upon choose file?

namespace App\Controllers;

// Probably need to recheck what is really necessary here...
use Config\Database as ConfigDatabase;

use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Files\File;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Controller;


class ExhibitorDirectory extends BaseController {

function __construct()
{
      
 
helper('text');
helper('form');
helper('html');

}
	

    public function form_show() {
    ini_set('error_log', '/home/testconx/log_public/php_errors.log');
	ini_set('error_reporting', 'E_ALL');
	
		$session = session(); 
		$model = model(DirectoryEntry::class);
		
	
		// Don't call without an empty key
		if ( !empty($_GET["key"]) ) {
			
			$secretKey = $_GET["key"];
			$session->set('secretKey', $_GET["key"]);
			
			error_log("Pulling up exhibitor directory with secret key:".$secretKey."\n",0);
			
			$db = db_connect('registration');
			$builder = $db->table('chinacompany');
			$builder->where('SecretKey', $secretKey);
			$query = $builder->get();
			$row = $query->getRow();
			//$keyreturn = session('secretKey');
			//$session->set('success', "");

			$data = array(
				'logo_dir' => "/EXPOdirectory/",
				//'Year' => "2024",
				//'Event' => "Mesa",
				'Entry' => $model->getEntry($secretKey),
				'PriorEntryImage' => "example.png", //Default image, updated below
				'StatusMessage' => session('statusMessage'),
				'PromptMessage' => "Please make any changes below and press either Approve, Save Draft, or Cancel button.",
			);
			
			if (!isset($data['Entry'])) {
				return view('link_invalid');
			}
			
			$status = $data['Entry']->Status;
			$session->set('entryID', $data['Entry']->EntryID); // used in upload file name

			if (!empty($data['Entry']->SampleEntry)) {
				$data['PriorEntryImage'] = $data['Entry']->SampleEntry;
			}

			if ($status != "Draft") {
				$data['PromptMessage'] = "Please contact the TestConX Office if you require any further changes.";
			}
			
			echo view('directory_old', $data);
			
			if ($status == "Draft") {
					echo view('upload_form');
			} else {
					echo "<br />";
                    echo "<br />";
			}
			
			echo view('directory_new_data', $data);
		
			if ($status == "Draft") {		
				echo view('directory_buttons', $data);
			}
			
			echo view('directory_footer');
			
			$session->set('statusMessage',"");
			
		} else {
			$this->index();
		}
    }
	
	//Posts the form fields and loads the view_form.php program
    public function data_submitted() { 
    	// Protect against CSRF - ref: https://codeigniter.com/user_guide/libraries/security.html
    	if (! $this->request->is('post')) {
 		   return $this->response->setStatusCode(405)->setBody('Method Not Allowed');
		}
    	
		$session = session(); 
		$secretKey = session('secretKey');
		
		$model = model(DirectoryEntry::class);
    	
		$request = \Config\Services::request();
		
		if (! $this->request->is('post')) {
 		   return $this->response->setStatusCode(405)->setBody('Method Not Allowed');
		}
		
		if($request->getPost('cancel')) {
			return redirect()->to(base_url());
		}					
						
		if ($request->getPost('approve') || $request->getPost('draft')) {

			if($request->getPost('approve')) {
				$status = 'Approved';
				$session->set('statusMessage', "Your data was successfully updated as Approved.");
			} else {
				$status = 'Draft';
				$session->set('statusMessage', "Your data was successfully updated as Draft");
			}
			
			$data_update = array(
				'CompanyName' => $request->getPost('comp_name'),
				'Line1' => $request->getPost('coord_name'),
				'Line2' => $request->getPost('comp_email'),
				'Line3' => $request->getPost('address1_change'),
				'Line4' => $request->getPost('address2_change'),
				'Line5' => $request->getPost('phone_change'),
				'Line6' => $request->getPost('website_change'),
				'Description' => $request->getPost('description_change'),
				'Updated' => date("Y-m-d H:i:s"),
				'Status' => $status,
				'Upload' => session('upload_status')
			); 
				
			$update_status = $model->updateEntry($secretKey, $data_update);
			if (!$update_status) {
				$session->set('statusMessage', "ERROR: Update failure.");
			}
		}
       
	   return redirect()->to('/directory?key='.session('secretKey'));
	
		
    }
    
    public function index()
	{
		echo "<h1>Please use the special link provided to you.</h1>";
		die('index');
	}
	
}
?>
