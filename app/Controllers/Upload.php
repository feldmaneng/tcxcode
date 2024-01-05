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
			//die('test9');
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
				$idName= session('entryIDname');
				//echo $idname;
				$newName = $idName."-".$originalName;
				//echo $newName;
				
				   if (! $path = $file->store('/EXPOdirectory/logo_upload/',$newName ) ){
					  echo $path;
					  $error =$validation->getErrors();
					  return view('upload_error',$error);
            //return view('upload_form', ['error' => 'upload failed']);
					}

					//$tempfile = $file->getTempName();
					//die($tempfile.' test1');
					//$path = $this->request->getFile('userfile')->store('/EXPOdirectory/logo_upload/');
					
					$data = ['upload_file_path' => $path];
					
				/// Missing file rename code here
					
					$upload_stat = 'New';
					$data_update = [
							'Upload' => $upload_stat
						];
					/* $data_update = array(
						'Upload' => $upload_stat
					); */
						//$file_name = $file->getName();
						$session->set('upload_filename', $originalName);
						$session->set('upload_status', "New");
					//echo view('upload_success', $data);
						
						$secretkey = session('secretKey');
						//IMF not needed and loads wrong (i.e. the default database) $this->load->database();
						$db = db_connect('registration');
						$builder = $db->table('expodirectory');
						 
						
						$data = [
							'Upload' => $upload_stat 
						];
						echo $secretkey."test1";
						$builder->where('SecretKey', $secretkey);
						$builder->update($data);
						//$this->db = $this->load->database('RegistrationDataBase',TRUE);
						//$builder->where('SecretKey', $secretkey);
						//IMF $this->db->update('test', $data_update);
						//$builder->set('Upload',$upload_stat);
						//$builder->insert();
						

                        echo view('upload_success', $data);
				} 
				
				
						
							
        }

?>