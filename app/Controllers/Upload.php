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
    ini_set('error_log', '/home/testconx/log_public/php_errors.log');
	ini_set('error_reporting', 'E_ALL');
	
        	$model = model(DirectoryEntry::class);
			$session = session();

			$file = $this->request->getFile('userfile');
			if(! $this->validate(
			[
            'userfile' => 
				
                'uploaded[userfile]|max_size[userfile,2048]|mime_in[userfile,image/gif,image/jpg,image/png,image/jpeg,image/pdf,image/ai]|ext_in[userfile,gif,jpg,png,jpeg,pdf,ai]|max_dims[userfile,2048,2048]'
				
			])
			){					  
					  return view('upload_error',[
						'error' => $this->validator->getErrors(),
						]);
			}
			
			// Check that the file was actually uploaded
			if (! $file->isValid()) {
  				  throw new \RuntimeException($file->getErrorString() . '(' . $file->getError() . ')');
			}
					
				$originalName = $file->getClientName();
				$idName= session('entryID');
				$newName = $idName."-".$originalName;
			error_log("File size: ". $file->getSize() ."\n",0);
			error_log("Temp name: ". $file->getTempName() ."\n",0);
				
				// changed from store() to move()
				  if (! $path = $file->store('/EXPO_logo_upload/',$newName ) ){
					  echo $path;
					  $error =$validation->getErrors();
					  return view('upload_error',$error);
					} 
				/*	
				if ($file->hasMoved()) {
					error_log("File Has moved \n",0); 
				} else {
					error_log("File Has not moved: ". $file->hasMoved()."\n",0);
				} */
				
				error_log("Stored uploaded file to ".$path."\n",0);
				// Check that the file was actually uploaded
				if (! $file->isValid()) {
  					error_log($file->getErrorString() . '(' . $file->getError() . ")\n",0);
				} else {
					error_log("File passed validity check.\n",0);
				} 
				error_log("File size: ". $file->getSize() ."\n",0);

				
					$data = ['upload_file_path' => $path];
					
				/// Missing file rename code here
					
					$upload_stat = 'New';
					$data_update = [
							'Upload' => $upload_stat,
						];
		
					$secretKey = session('secretKey');
					$db  = \Config\Database::connect('registration');
						
					$builder = $db->table('expodirectory');
								
					$builder->where('SecretKey', $secretKey);
								
					$update_status = $builder->replace($data_update);
					//model
					//$update_status = $model->updateEntry($secretKey, $data_update);
					if (!$update_status) {
						$session->set('statusMessage', "ERROR: Update failure.");
					} else {
						$session->set('statusMessage', "Your file, $originalName, was successfully uploaded!");
					}
	
       			
       		 //later return to a variable set in session()
	  		 return redirect()->to('/directory?key='.session('secretKey'));
				 

					
				} 				
						
							
        }

?>