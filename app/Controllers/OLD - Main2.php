<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Some variables for each year
$BiTSEvent = "BiTS China 2016";

session_start(); // Remember things across pages

class Main extends CI_Controller {

 
function __construct()
{
        parent::__construct();
 
/* Standard Libraries of codeigniter are required */
$this->load->database();
$this->load->helper('url');
$this->load->helper('string');

/* ------------------ */ 
 
$this->load->library('grocery_CRUD');
 
}
 
public function index()
{
echo "<h1>Please use the special link provided to you.</h1>";//Just an example to ensure that we get into the function
die();
}

} 
/* End of file Main.php */
/* Location: ./application/controllers/Main.php */