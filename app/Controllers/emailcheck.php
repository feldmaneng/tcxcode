<?php

namespace App\Controllers;

use CodeIgniter\Files\File;

class emailcheck extends BaseController
{
    protected $helpers = ['form'];

    public function index()
    {
        return view('upload_event', ['errors' => []]);
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
		
        if (! $this->validateData([], $validationRule)) {
            $data = ['errors' => $this->validator->getErrors()];

            return view('upload_event', $data);
        }
				$file = $this->request->getFile('userfile');
				//echo $file['full_path']."<br>";
				$originalName = $file->getClientName();
				$idName= rand(1000,10000);
				$newName = $idName."-".$originalName;
				
       // $img = $this->request->getFile('userfile');
		if (! $path = $file->store('/',$newName) ){
					  echo $path;
					  $error = $validation->getErrors();
					  return view('upload_error',$error);
					} 
       
		
			$list = array_map('str_getcsv',file(WRITEPATH.'uploads/'.$newName));
			
			$idrow = array_column($list,2);
			$numrows = count($idrow);
			
			for ($i = 1; $i < $numrows; $i++){
				$ID = $list[$i];
				
			$db = \Config\Database::connect();
					$builder = $db->table('contacts');
					$builder->select('*');
					$builder->where('ContactID',$ID[2]);
					
					$query = $builder->get();
					
					if ( $query->getNumRows() > 0 ) {
						$count = $query->getNumRows();
						$row = $query->getResultArray();
						 if($ID[0]=="email address has been hard bounced from this audience and can't be imported."){
							$row['EmailBounce']=1;
						 }
					/* if( requirement for unsubscribe is true){
						$row['Subscribe'] = 'No';
					} */
					//create a column in contacts for subscription set it to enum and make the choices Yes,No,and blank
					/* if( requirements for removing from contacts is true){
						$builder->where('ContactID',$ID[2]);
						$builder->delete();
					} */
						
						 
						 
						 
						 $builder->where('ContactID', $ID[2]); 
						 $builder->update($row);
					
							
						
					}		
					
					}
					
			}
//increment keyfirst to move to the first times one row down

		
        $data = ['errors' => 'The file has already been moved.'];

        //return view('upload_event', $data);
    }
}