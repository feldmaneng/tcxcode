<?php  


namespace App\Controllers;

use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;

class shorttest extends BaseController {

 
	function __construct()
	{
		
		helper('text');

	
 
	}
 
	
function customers()
{
    $crud = $this->_getGroceryCrudEnterprise();

    $crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());

    $crud->setTable('contacts');
    $crud->setSubject('User', 'Users');

    $output = $crud->render();

    return $this->_example_output($output);
}
}