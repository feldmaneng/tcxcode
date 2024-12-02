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

class Authorpopulate extends BaseController
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
		
		return view('author_upload',['error' => ' ']);	
		}
	  public function databasecheck($email,$given,$family){
				$db      = \Config\Database::connect();
				$builder = $db->table('contacts');
				$builder->select('ContactID,Email,GivenName,FamilyName');
				$builder->where('Email', $email);
				
				

				$query = $builder->get();
				$people = $query->getNumRows();
				
				$results = $query->getResultArray();
				if( $people == 1){
				//$Email = $results[0]["Email"];
				$ContactID =  $results[0]["ContactID"];
				$GivenName = $results[0]["GivenName"];
				$FamilyName = $results[0]["FamilyName"];
				}
				else{
				//$Email = "Not Found";
				$ContactID =  "Not Found";
				$GivenName = $given;
				$FamilyName = $family;
				}
				return [$ContactID,$GivenName,$FamilyName];
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
			
			$title = array_search('Title', $csv[0]);
			$numberofauthors = array_search('Number of Authors', $csv[0]);
			$given1 = array_search('Given',$csv[0]);
			$given2 = array_search('GN2',$csv[0]);
			$given3 = array_search('GN3',$csv[0]);
			$given4 = array_search('GN4',$csv[0]);
			$given5 = array_search('GN5',$csv[0]);
			$family1 = array_search('Family',$csv[0]);
			$family2 = array_search('FN2',$csv[0]);
			$family3 = array_search('FN3',$csv[0]);
			$family4 = array_search('FN4',$csv[0]);
			$family5 = array_search('FN5',$csv[0]);
			$company1 = array_search('Company Name',$csv[0]);
			$company2 = array_search('Company2',$csv[0]);
			$company3 = array_search('Company3',$csv[0]);
			$company4 = array_search('Company4',$csv[0]);
			$company5 = array_search('Company5',$csv[0]);
			$email1 = array_search('Email1',$csv[0]);
			$email2 = array_search('Email2',$csv[0]);
			$email3 = array_search('Email3',$csv[0]);
			$email4 = array_search('Email4',$csv[0]);
			$email5 = array_search('Email5',$csv[0]);
			
			//added a dummy variable at the start of each array
			$given = array(0,$given1,$given2,$given3,$given4,$given5);
			$family = array(0,$family1,$family2,$family3,$family4,$family5);
			$company = array(0,$company1,$company2,$company3,$company4,$company5);
			$email = array(0,$email1,$email2,$email3,$email4,$email5);
			
			//$table = new \CodeIgniter\View\Table();
			//update for author? or remove
			//$table->setHeading(['Email from Csv','GivenName from Csv','FamilyName from Csv','ContactID','Email','GivenName','FamilyName','Match','MatchContactID','MatchGivenNameName','MatchFamilyName']);
			for($i=1;$i<$length;$i++){
				
			
				 $db      = \Config\Database::connect();
				$builder = $db->table('presentations');
				$builder->select('PresentationID,Title');
				$builder->where('Title', $csv[$i][$title]);
				
				

				$query = $builder->get();
				$people = $query->getNumRows();
				
				$results = $query->getResultArray();
				
				if( $people == 1){
				
				$PresentationID =  $results[0]["PresentationID"];
				
				}
				else{
				$PresentationID =  1000;
				}
					for($n=1;$n<=$csv[$i][$numberofauthors];$n++){
						[$ContactID,$GivenName,$FamilyName]	= $this->databasecheck($csv[$i][$email[$n]],$csv[$i][$given[$n]],$csv[$i][$family[$n]]);
						$Com = $csv[$i][$company[$n]];
						
						if($n==1){
							$Presenter = 1;
						}
						else{
							$Presenter = 0;
						}
						
						//use CodeIgniter\Database\RawSql;
						$db      = \Config\Database::connect();
						$builder = $db->table('authors_copy');
						$data = [
							'AuthorNumber'          => $n,
							'Presenter'       => $Presenter,
							'ContactID'        => $ContactID,
							'GivenName'        => $GivenName,
							'FamilyName'        => $FamilyName,
							'Company'        => $csv[$i][$company[$n]],
							'PresentationID' => $PresentationID,
						];

						$builder->insert($data);
						
						
					}
				
				
				
				
				
				
					
				
				//$table->addRow([$csv[$i][0],$csv[$i][1],$csv[$i][2], $ContactID, $Email, $GivenName, $FamilyName, $Match, $MatchContactID, $MatchGivenName, $MatchFamilyName]);
			
				
			}
			
			





			//echo $table->generate();



			
			
			
			
	  }

}


?>