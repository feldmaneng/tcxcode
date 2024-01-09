<?php  
//Reference https://www.javatpoint.com/codeigniter-database-login-form
//Changed to CI4 8-Jan-2024 IMF

namespace App\Controllers;
  
class Main extends BaseController { 


	function __construct() {
	
		//helper('text');
		helper('form','url');
		//helper('html');

	}

  
    public function index()  
    {  
 		$session = session();
 		
		if ( $session->tcx_logged_in ) {
	    	return redirect()->to('database');
	    	
	    } else {
     	   echo view('login');  
     	}
    }  
  

    public function invalid()  
    {  
        echo view('invalid');  
    }  
  
    
    public function login_action() {  
        // Protect against CSRF - ref: https://codeigniter.com/user_guide/libraries/security.html
    	if (! $this->request->is('post')) {
 		   return $this->response->setStatusCode(405)->setBody('Method Not Allowed');
		}
    	
    	$model = model(Users::class);
    	
    	$request = \Config\Services::request();
    	
    	if (! $this->request->is('post')) {
 		   return $this->response->setStatusCode(405)->setBody('Method Not Allowed');
		}

    	$session = session();
  
  		$isValid = $this->validate([
        	'username' => 'required|trim|alpha_numeric|min_length[3]',  
       		'password' => 'required|trim|alpha_numeric_punct|min_length[8]',
       	]);
  
        if ($isValid) { 
        	$username = $request->getPost('username');
        	$loggedIn = $model->getCheckUserPassword ($username, $request->getPost('password'));
            $data = array(  
                'username' => $username,
                );    
            $session->set('tcx_userdata',$data);  
            $session->set('tcx_logged_in',$loggedIn);

            if ($loggedIn) {
            	return redirect()->to('database');
            } else {
            	return view('login');
            }
        }   
        else {  
            echo view('login', ['validation' => $this->validator,]);  
        }  
    }  
  
  
    public function logout()  
    {  
		$session = session();
		
        $session->destroy();  
             
        return redirect()->to('/');
       
    }  
  
}  
?>  