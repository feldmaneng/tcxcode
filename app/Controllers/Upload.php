<?php
//session_start();
namespace App\Controllers;
//resync grocery
use Config\Database as ConfigDatabase;

//use App\Libraries\PdfLibrary;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;
class Upload extends BaseController {

        public function __construct()
        {
                
                helper('form');
				helper('text');
				helper('url');
        }

        public function index()
        {
			
                return view('upload_form', ['error' => ' ']);
        }

        public function do_upload()
        {
        	$model = model(DirectoryEntry::class);
			$session = session();

			$file = $this->request->getFile('userfile');
			if(! $this->validate(
			[
            'userfile' => 
				
                'uploaded[userfile]|max_size[userfile,2048]|mime_in[userfile,image/gif,image/jpg,image/png,image/jpeg,image/pdf,image/ai]|ext_in[userfile,gif,jpg,png,jpeg,pdf,ai]|max_dims[userfile,2048,2048]'
				
			])
			){
					
					 // $error = $this->validator->getErrors();
					  
					  return view('upload_error',[
						'error' => $this->validator->getErrors(),
						]);
			}
					
				$originalName = $file->getClientName();
				$idName= session('entryID');
				$newName = $idName."-".$originalName;

				
				   if (! $path = $file->store('/EXPOdirectory/logo_upload/',$newName ) ){
					  echo $path;
					  $error =$validation->getErrors();
					  return view('upload_error',$error);
					}

					$data = ['upload_file_path' => $path];
					
				/// Missing file rename code here
					
					$upload_stat = 'New';
					$data_update = [
							'Upload' => $upload_stat
						];

					//$session->set('upload_filename', $originalName);
					//$session->set('upload_status', "New");
					
					$secretKey = session('secretKey');

					$update_status = $model->updateEntry($secretKey, $data_update);
					if (!$update_status) {
						$session->set('statusMessage', "ERROR: Update failure.");
					} else {
						$session->set('statusMessage', "Your file, $originalName, was successfully uploaded!");
					}
		
       
       				//echo view('upload_success', $data);
       			
       		 //later return to a variable set in session()
	  		 return redirect()->to('/dir2?key='.session('secretKey'));
				 

					
				} 				
						
							
        }

?>