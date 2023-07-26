<?php  
defined('BASEPATH') OR exit('No direct script access allowed');  

//Reference https://www.javatpoint.com/codeigniter-database-login-form
  
class Main extends CI_Controller {  
  
    public function index()  
    {  
        $this->login();  
    }  
  
    public function login()  
    {  
	    if ($this->session->userdata('currently_logged_in')) 
	    {
	    	//$this->data(); // Replace with call to main menu
	    	redirect('Database');
	    } else {
     	   $this->load->view('login_view');  
     	}
    }  
  
/*    public function signin()  
    {  
        $this->load->view('signin');  
    }  
*/
    public function data()  
    {  
        if ($this->session->userdata('currently_logged_in'))   
        {  
            $this->load->view('data');  
        } else {  
            redirect('Main/invalid');  
        }  
    }  
  
    public function invalid()  
    {  
        $this->load->view('invalid');  
    }  
  
  
    // Was in models/login_model.php but couldn't find it in current configuration
    private function log_in_correctly() {  
    
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
    //
    
    public function login_action()  
    {  
        $this->load->helper('security');  
        $this->load->library('form_validation');  
  
        $this->form_validation->set_rules('username', 'Username:', 'required|trim|xss_clean|callback_validation');  
        $this->form_validation->set_rules('password', 'Password:', 'required|trim');  
  
        if ($this->form_validation->run())   
        {  
            $data = array(  
                'username' => $this->input->post('username'),  
                'currently_logged_in' => 1  
                );    
            $this->session->set_userdata($data);  
            //redirect('Main/data');  
            redirect('Database');
        }   
        else {  
            $this->load->view('login_view');  
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
    	/* $data = array(  
            'username' => '',  
            'currently_logged_in' => 0  
                );    
        $this->session->set_userdata($data); 
        */
        
                  
        $this->session->sess_destroy();  

        //$this->load->view('data');  
             
        redirect('Main');
       
    }  
  
}  
?>  