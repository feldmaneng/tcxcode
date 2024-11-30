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
	
   
      public function check(){
		
		return view('contact_upload',['error' => ' ']);	
	  }
	  
	  public function do_upload(){
			$file = $this->request->getFile('userfile');
		  
			$csv = array_map('str_getcsv', file($file));
			$idrow = array_column($csv,0);
			$length = count($idrow);
			
			// $csv[0][0] is the value in the top right most corner $csv[1][0] is the value directly below the right most corner
			//echo $csv[0][0];
			//echo $csv[1][0];
			
			
			//Restating all inputs in the first columns, 
			// Query email address - if match compare name
			//       if names match, list contact ID - if they don't match, indicate different names found (and what they are)
			//   if no email match, query names - if only 1 result - list contact ID & email address found
			//   if multple results - flag that multiple matches found
			
			//$length = $x;
			$table = new \CodeIgniter\View\Table();
			$table->setHeading(['Email from Csv','GivenName from Csv','FamilyName from Csv','ContactID','Email','GivenName','FamilyName','Match','MatchContactID','MatchGivenNameName','MatchFamilyName']);
			for($i=1;$i<$length;$i++){
			
				$db      = \Config\Database::connect();
				$builder = $db->table('contacts');
				$builder->select('ContactID,Email,GivenName,FamilyName');
				$builder->where('Email', $csv[$i][0]);
				
				

				$query = $builder->get();
				$people = $query->getNumRows();
				
				$results = $query->getResultArray();
				if( $people == 1){
				$Email = $results[0]["Email"];
				$ContactID =  $results[0]["ContactID"];
				$GivenName = $results[0]["GivenName"];
				$FamilyName = $results[0]["FamilyName"];
				}
				else{
				$Email = "Not Found";
				$ContactID =  "Not Found";
				$GivenName = "Not Found";
				$FamilyName = "Not Found";
				}
				
				
				$db      = \Config\Database::connect();
				$builder = $db->table('contacts');
				$builder->select('ContactID,Email,GivenName,FamilyName');
				$builder->where('GivenName', $csv[$i][1]);
				$builder->where('FamilyName', $csv[$i][2]);
				
				

				$query = $builder->get();
				$people = $query->getNumRows();
				
				$results = $query->getResultArray();
				if( $people == 1){
				$Match = "Match Found";
				$MatchContactID =  $results[0]["ContactID"];
				$MatchGivenName = $results[0]["GivenName"];
				$MatchFamilyName = $results[0]["FamilyName"];
					if($MatchContactID == $ContactID)
					{
						$MatchContactID = 'Same';
						$MatchGivenName = 'Same';
						$MatchFamilyName = 'Same';
					}
				}
					else{
					$Match = "Not Found";
					$MatchContactID =  "Not Found";
					$MatchGivenName = "Not Found";
					$MatchFamilyName = "Not Found";
					}
					if ($people > 2)
					{
						$x=0;
						for($i=1;$i<=$people;$i++)
						{
							
							$Match = $i;
							$MatchContactID += $results[$x]["ContactID"];
							$MatchGivenName += $results[$x]["GivenName"];
							$MatchFamilyName += $results[$x]["FamilyName"];
							$x++;
						}
					}
				
					
				
				$table->addRow([$csv[$i][0],$csv[$i][1],$csv[$i][2], $ContactID, $Email, $GivenName, $FamilyName, $Match, $MatchContactID, $MatchGivenName, $MatchFamilyName]);
			
				
			}
			
			





			echo $table->generate();



			
			
			
			
	  }

}


?>