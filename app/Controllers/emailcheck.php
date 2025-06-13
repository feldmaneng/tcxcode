<?php

namespace App\Controllers;

use CodeIgniter\Files\File;

class emailcheck extends BaseController
{
    protected $helpers = ['form'];

    public function index()
    {
        return view('emailcheck', ['errors' => []]);
    }

    public function emailcheck()
    {
		$session = session();
        $validationRule = [
            'userfile' => [
                'label' => 'Text File',
                'rules' => [
                    'uploaded[userfile]',
                      ],
            ],
        ];
		
        /* if (! $this->validateData([], $validationRule)) {
            $data = ['errors' => $this->validator->getErrors()];

            return view('email_error', $data);
        } */
				$file = $this->request->getFile('userfile');
				
				$originalName = $file->getClientName();
				$idName= rand(1000,10000);
				$newName = $idName."-".$originalName;
				
       
		if (! $path = $file->store('/',$newName) ){
					  echo $path;
					  $error = $validation->getErrors();
					  return view('email_error',$error);
					} 
       
		
			$list = array_map('str_getcsv',file(WRITEPATH.'uploads/'.$newName));
			
			$idrow = array_column($list,2);
			$numrows = count($idrow);
			
			
			
			
			for ($i = 1; $i < $numrows; $i++){
				$ID = $list[$i];
				echo $ID[1].";";
				/*
				echo $ID[1].";";
				echo $ID[0].";"; */
					$db2 = \Config\Database::connect();
					$builder2 = $db2->table('attendance');
					$builder2->select('ContactID');
					$builder2->where('ContactID',$ID[1]);
					$query2 = $builder2->get();
					
					$count2 = $query2->getNumRows();
				
					$db = \Config\Database::connect();
					//$builder = $db->table('contacts');
					$builder = $db->table('contactstestemail3');
					$builder->select('*');
					$builder->where('ContactID',$ID[1]);
					
					$query = $builder->get();
										
					$count = $query->getNumRows();
					
					if ( $count > 0) {
						//this checks if the email has been bounced or unsubed and then updates the appropriate database fields
						//$ID[0] is the error field from mailchimp
						//$ID[1] is the ContactID field
						 if($ID[0]=="email address has been hard bounced from this audience and can't be imported."){
							$rowb['EmailBounce']=1;
							$rowb['Notes'] = "Bounced - ";
							//$rowb['Notes'].="Bounced - ".date("Y-m-d H:i:s");
							if($count2 == 0){
								$rowb['Active']=0;
							}
							
							$builder->where('ContactID', $ID[1]); 
							$builder->update($rowb);
							echo "bounce;";
						 }
						 if($ID[0]=="email address has been unsubscribed from this audience and can't be re-imported."){
							$rowc['Exhibitinfo']='none';
							$rowc['Techinfo']='none';
							//$rowb['Notes'].="Unsubscribed - ".date("Y-m-d H:i:s");
							if($count2 == 0){
								$rowc['Active']=0;
							}
							$builder->where('ContactID', $ID[1]); 
							$builder->update($rowc);
							echo "unsub;";
						 }
				//echo "<br>";
						
						 
						 
						 
						
					
							
						
					}		
					echo "<br>";
					}
				//return view('emailcheck_success');	
			}
//increment keyfirst to move to the first times one row down

		
        //$data = ['errors' => 'The file has already been moved.'];

        //return view('upload_event', $data);
    }
