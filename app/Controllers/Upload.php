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
			
			$this->validate([
            'userfile' => [
                'uploaded[userfile]',
                'max_size[userfile,2048]',
                'mime_in[userfile,image/gif,image/jpg,image/png,image/jpeg,image/pdf,image/ai]',
                'ext_in[userfile,gif|jpg|png|jpeg|pdf|ai]',
                'max_dims[userfile,2048,2048]',
            ],
        ]);
				
				$file = $this->request->getFile('userfile');
				$originalName = $file->getClientName();
				//$file = $this->request->getFile('userfile')->store('/EXPOdirectory/logo_upload/');
				
				//$path=$file->store('/EXPOdirectory/logo_upload/');
				//die($path);
				
				//$entryIDname = session('entryIDname');
				   if (! $path = $file->store('/EXPOdirectory/logo_upload/',$originalName ) ){
					   $error =$validation->getErrors();
					  return view('upload_error',$error);
            //return view('upload_form', ['error' => 'upload failed']);
					}
					//$tempfile = $file->getTempName();
					//die($tempfile.' test1');
					//$path = $this->request->getFile('userfile')->store('/EXPOdirectory/logo_upload/');
					$data = ['upload_file_path' => $path];
					$upload_stat = 'New';
					$data_update = array(
						'Upload' => $upload_stat
					);
						$file_name = $file->getName();
						$session->set('upload_filename', $file_name);
						$session->set('upload_status', "New");
					//echo view('upload_success', $data);
						
						$secretkey = session('secretkey');
						//IMF not needed and loads wrong (i.e. the default database) $this->load->database();
						$db = db_connect('registration');
						$builder = $db->table('expodirectory');
						//$this->db = $this->load->database('RegistrationDataBase',TRUE);
						$builder->where('SecretKey', $secretkey);
						//IMF $this->db->update('test', $data_update);
						$builder->update($data_update);			

                        echo view('upload_success', $data);
				} 
				
				
						
							
        }

?>