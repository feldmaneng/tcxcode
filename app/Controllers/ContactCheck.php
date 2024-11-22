<?php 

namespace App\Controllers;

use CodeIgniter\Files\File;
use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;

$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

}

class ContactCheck extends BaseController
{
	public function __construct()
        {
                
                helper('form');
				helper('text');
				helper('url');
        }

    public function index()
    {
		echo "<a href=" . site_url('/PrintBadge/korea') . ">Korea</a> ";
		echo "<a href=" . site_url('/PrintBadge/china') . ">China</a> ";

    }
	
	 public function korea()
    {
        return view('PrintView', ['errors' => []]);
    }
	
	public function china()
    {
        return view('PrintViewChina', ['errors' => []]);
    }
	
	
	public function printpreview()
    {
        return view('PrintView2', ['errors' => []]);
    }
	
	public function printpreviewchina()
    {
        return view('PrintView2China', ['errors' => []]);
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
	
	private function _example_output($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo $output->output;
            exit;
        }

        return view('testconx_template.php', (array)$output);
    }
	
   
      function check(){
		
		return view('contact_upload', ['errors' => []]);	
	  }
	  
	  public function do_upload(){
		  $countFiles = count($_FILES['uploadedFiles']['name']);
			$target_file =basename($_FILES["fileToUpload"]["name"]);
			$csv = array_map('str_getcsv', file($target_file));
			
			$key = array_search('Email', array_column($csv, 0));
			
			
			$keyfirst = $key + 1;
			$keyfirst++;
			$x = 0;
			$emailc = array_search('Email',$csv[$keyfirst]);
			//increment keyfirst to move to the first times one row down
			while($csv[$keyfirst+$x][1] != NULL ){

				$x++;
				
				}
			$keylast = $keyfirst + $x;
			
			echo $csv[$emailc][0];
			echo $csv[$emailc][1];
			
			
			
			
			
	  }

}


?>