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



class Form extends BaseController {

function __construct()
{
      
 
helper('text');
helper('form');
helper('html');

}
	

	
	//Sets the session secretkey equal to the key passed in on the URL and loads the view_form.php program
    public function form_show() {
		$session = session(); 
		
		// Don't call without an empty key
		if ( !empty($_GET["key"]) ) {
			
			$session->set('secretKey', $_GET["key"]);
			
			$keyreturn = session('secretKey');
			//not in original code
			//$session->set('success', "saved");
			$session->set('success', "");
			//$test = $_SESSION["secretKey"];
				//echo $_SESSION["secretkey"];
				//die($test);
			//$session->set($array)('secretkey', $_GET["key"]);
			//$this->load->view('view_form',$newdata);
			echo view('view_form');
			
			//view('testform');
			//die('after');
			// ask ira where updated comes from
			$session->set('updated', "unset");
			//$updated = $this->session->userdata('updated');
		} else {
			$this->index();
		}
    }
	
	//Posts the form fields and loads the view_form.php program
    public function data_submitted() {
		$request = \Config\Services::request();
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
		
       echo view('view_form', $data);
	   return redirect()->to('/directory?key='.session('secretKey'));
    }
    
    public function index()
	{
	echo "<h1>Please use the special link provided to you.</h1>";
	die('index');
	}
	
}
?>
