<?php

// Installation notes:
//   Upload.php controller requires PHP fileinfo extension enabled.
//   Did so by turning on via cPanel for Rochen running PHP 7.0

// Future (non-urgent) enhancements to consider:
//   A user uploading a new file with the same name as their prior file overwrites the existing file.
//      perhaps a way to increment the file name?
//   Remove need for the upload button - upload upon choose file?

namespace App\Controllers;
//resync grocery
use Config\Database as ConfigDatabase;
//use Config\GroceryCrud as ConfigGroceryCrud;
//use GroceryCrud\Core\GroceryCrud;
//use App\Libraries\PdfLibrary;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Files\File;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Controller;
// Some variables for each year



class ExhibitorDirectory extends BaseController {

function __construct()
{
      
 
helper('text');
helper('form');
helper('html');

}
	

    public function form_show() {
		$session = session(); 
		$model = model(DirectoryEntry::class);
		
		// Don't call without an empty key
		if ( !empty($_GET["key"]) ) {
			
			$secretKey = $_GET["key"];
			$session->set('secretKey', $_GET["key"]);
			
			//$keyreturn = session('secretKey');
			
			$session->set('success', "");

			$data = array(
				'logo_dir' => "/EXPOdirectory/",
				'Year' => "2025",
				'Event' => "Mesa",
				'Entry' => $model->getEntry($secretKey),
				'PriorEntryImage' => "example.png", //Default image, updated below
				'StatusMessage' => "",
				'PromptMessage' => "Please make any changes below and press either Approve, Save Draft, or Cancel button.",
			);
			
			$status = $data['Entry']->Status;

			if (!empty($data['Entry']->SampleEntry)) {
				$data['PriorEntryImage'] = $data['Entry']->SampleEntry;
			}
			
			//dd($status);
			
			/* echo view('view_form');
			
			$session->set('updated', "unset");
			//$updated = $this->session->userdata('updated');
			*/
								/* if(session('success') == 'saved') {
						echo '<h1>Your information was successfully updated.<h1>';
						//echo heading('Your information was successfully updated.', 1, 'style="color:#52D017"');
					}
					*/
					


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
				echo view('directory_buttons');
			}
			
			echo view('directory_footer');
			
		} else {
			$this->index();
		}
    }
	
	//Posts the form fields and loads the view_form.php program
    public function data_submitted() {
		$session = session(); 
		$secretKey = session('secretKey');
		//dd($secretKey);
		
		$model = model(DirectoryEntry::class);
    	
		$request = \Config\Services::request();
		
		/*
		$data = array(
            'company_name' =>$request->getPost('comp_name'),
            'coordinator_name' => $request->getPost('coord_name'),
            'email_address' => $request->getPost('comp_email'),
			'address1_change' =>$request->getPost('address1_change'),
			'address2_change' => $request->getPost('address2_change'),
			'phone_change' => $request->getPost('phone_change'),
			'website_change' => $request->getPost('website_change'),
			'description_change' => $request->getPost('description_change')
		);
		*/
		
		if($request->getPost('cancel')) {
			header("Location: https://www.testconx.org/");
		}
						
		//dd($request);
						
						
		if ($request->getPost('approve') || $request->getPost('draft')) {
							//echo "<h1>About to do the Post for approve and draft commands</h1>";
							//print_r($request);
							//die();
							//$saved = 'saved';
							//$session->set('success', $saved);
							
							
			if($request->getPost('approve')) {
				$status = 'Approved';
				//$session->set('updated', "approve");
				//echo "<h3>Your data was successfully updated as Approved.</h3>";
			} else {
				$status = 'Draft';
				//$session->set('updated', "draft");
				//echo "<h3>Your data was successfully updated as Draft.</h3>";
			}
		
			$upload_status = $session->set('upload_status');
			
			/*$data_update = array(
				'CompanyName' => $company_name,
				'Line1' => $coordinator_name,
				'Line2' => $email_address,
				'Line3' => $address1_change,
				'Line4' => $address2_change,
				'Line5' => $phone_change,
				'Line6' => $website_change,
				'Description' => $description_change,
				'Updated' => date("Y-m-d H:i:s"),
				'Status' => $status,
				'Upload' => $upload_status
			); */
			
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
				'Upload' => $upload_status
			); 
				
			$update_status = $model->updateEntry($secretKey, $data_update);
			// Do something with status?
		}
			
			
			/*
							$builder->where('SecretKey', $demo_key);
							//$this->db->where('SecretKey', $demo_key);
							//$this->db->update('test', $data_update);
							//dd($data_update);
							$builder->update($data_update);
								
								//return redirect()->back();	
								
								return redirect()->to('/directory?key='.$demo_key);	
		
		
       echo view('view_form', $data);*/
       
	   return redirect()->to('/dir2?key='.session('secretKey'));
	
		
    }
    
    public function index()
	{
	echo "<h1>Please use the special link provided to you.</h1>";
	die('index');
	}
	
}
?>
