<?php

namespace App\Controllers;

use CodeIgniter\Files\File;

class uploadevent extends BaseController
{
    protected $helpers = ['form'];

    public function index()
    {
        return view('upload_event', ['errors' => []]);
    }

    public function uploadevent()
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
       /*  if (! $img->hasMoved()) {
            $filepath = WRITEPATH . 'uploads/' . $img->store();

            $data = ['uploaded_fileinfo' => new File($filepath)];

            //return view('uploadevent_success', $data);
        } */
		
			
		//echo $file['full_path']."<br>";
		
			$list = array_map('str_getcsv',file(WRITEPATH.'uploads/'.$newName));
			
			$idrow = array_column($list,0);
			$numrows = count($idrow);
			for ($i = 0; $i < $numrows; $i++){
				$ID = $list[$i];
			$db = \Config\Database::connect();
					$builder = $db->table('attendance');
					$builder->select('*');
					$builder->where('ContactID',$ID);
					
					$query = $builder->get();
					
					if ( $query->getNumRows() > 0 ) {
						$count = $query->getNumRows();
						
					$row = $query->getResultArray();
					$textoutput =  $ID[0];
							
							for  ($x = 1; $x < $count; $x++){
								$y = $x-1;
								$textoutput .= ", ".$row[$y]['Year'].", ".$row[$y]['Event'];
								
							}
							$textoutput .="<br>\n";
							echo $textoutput;
					}		
					else{
						echo $ID[0].", Not found <br>\n";
					}
					
			}
//increment keyfirst to move to the first times one row down

		
        $data = ['errors' => 'The file has already been moved.'];

        return view('upload_event', $data);
    }
}