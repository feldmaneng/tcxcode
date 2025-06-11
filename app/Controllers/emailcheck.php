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
			
			$db = \Config\Database::connect();
			$builder = $db->table('attendance');
			$builder->distinct('ContactID');
			$query = $builder->get();
			$row = $query->getRowArray();
			$AttendanceContactID = $row['ContactID'];
			
			
			for ($i = 1; $i < $numrows; $i++){
				$ID = $list[$i];
				echo $ID[2].";";
				/*
				echo $ID[1].";";
				echo $ID[0].";"; */
					$db = \Config\Database::connect();
					$builder = $db->table('contactstestemail2');
					$builder->select('*');
					$builder->where('ContactID',$ID[2]);
					
					$query = $builder->get();
										
					$count = $query->getNumRows();
					
					if ( $count > 0) {
						//this checks if the email has been bounced or unsubed and then updates the appropriate database fields
						//$ID[0] is the error field from mailchimp
						//$ID[2] is the ContactID field
						 if($ID[0]=="email address has been hard bounced from this audience and can't be imported."){
							$rowb['EmailBounce']=1;
							if(!in_array($ID[2],$AttendanceContactID,true)){
								$rowb['Active']=0;
							}
							
							$builder->where('ContactID', $ID[2]); 
							$builder->update($rowb);
							echo "bounce;";
						 }
						 if($ID[0]=="email address has been unsubscribed from this audience and can't be re-imported."){
							$rowc['Expo_mailing']=0;
							$rowc['Tech_mailing']=0;
							if(!in_array($ID[2],$AttendanceContactID,true)){
								$rowc['Active']=0;
							}
							$builder->where('ContactID', $ID[2]); 
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
