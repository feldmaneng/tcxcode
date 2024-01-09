<?php  
//Reference https://www.javatpoint.com/codeigniter-database-login-form
//Changed to CI4 8-Jan-2024 IMF

namespace App\Controllers;
  
class Main extends BaseController { 


	function __construct() {
	
		//helper('text');
		helper('form');
		//helper('html');

	}

  
    public function index()  
    {  
 		$session = session();
 		//dd($session->tcx_userdata['currently_logged_in']);
 		
	    if (isset($session->tcx_userdata['currently_logged_in']) &&
	    	($session->tcx_userdata['currently_logged_in'])) {
	    	//$this->data(); // Replace with call to main menu
	    	die("Logged in");
	    	
	    	redirect('database');
	    } else {
     	   echo view('login');  
     	}
    }  
  
/*
    public function data()  
    {  
        if ($this->session->userdata('currently_logged_in'))   
        {  
            $this->load->view('data');  
        } else {  
            redirect('Main/invalid');  
        }  
    }  
*/  
    public function invalid()  
    {  
        echo view('invalid');  
    }  
  
  
    // Was in models/login_model.php but couldn't find it in current configuration
// REPLACED WITH MODEL
/*    private function log_in_correctly() {  
    	
    	$this->control_db = $this->load->database('ControlDataBase', TRUE);
  
        $this->control_db->where('UserName', $this->input->post('username'));  
        //$this->control_db->where('PasswordHash', $this->input->post('password'));  
        $query = $this->control_db->get('users');  
          
        if ($query->num_rows() == 1) {
        	$row = $query->row();
          	if (password_verify($this->input->post('password'), $row->PasswordHash))   
        	{  
            	return true;  
        	} 
        }
        return false;  
    } 
*/  
    //
    
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
  
        if (TRUE) { //($this->validate([]))   
        	$username = $request->getPost('username');
        	$loggedIn = $model->getCheckUserPassword ($username, $request->getPost('password'));
            $data = array(  
                'username' => $username, 
                'currently_logged_in' => $loggedIn,
                );    
            $session->set('tcx_userdata',$data);  
            //redirect('Main/data');  
            // Maybe check password?

            if ($loggedIn) {
            	die ("Logged in from login_action");
            	redirect('Database');
            } else {
            	return view('login');
            }
        }   
        else {  
            echo view('login', ['validation' => $this->validator,]);  
        }  
    }  
  
  /*
    public function signin_validation()  
    {  
        $this->load->library('form_validation');  
  
        $this->form_validation->set_rules('username', 'Username', 'trim|xss_clean|is_unique[signup.username]');  
  
        $this->form_validation->set_rules('password', 'Password', 'required|trim');  
  
        $this->form_validation->set_rules('cpassword', 'Confirm Password', 'required|trim|matches[password]');  
  
        $this->form_validation->set_message('is_unique', 'username already exists');  
  
    if ($this->form_validation->run())  
        {  
            echo "Welcome, you are logged in.";  
         }   
            else {  
              
            $this->load->view('signin');  
        }  
    }  
  */
  
    public function validation()  
    {  
        /* Turned off to work around model location
        $this->load->model('login_model');  
  
        if ($this->login_model->log_in_correctly())  
		*/
		        
        if ($this->log_in_correctly())
        {  
  
            return true;  
        } else {  
            $this->form_validation->set_message('validation', 'Incorrect username/password.');  
            return false;  
        }  
    }  
  
    public function logout()  
    {  

        $session->destroy();  
             
        redirect('Main');
       
    }  
  
}  
?>  