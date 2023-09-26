<?php 
namespace App\Controllers;

use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;
use App\Libraries\PdfLibrary;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;
class Examples extends BaseController {
function __construct()
{
      
 
helper('text');
 
}

	public function _example_output($output = null)
	{
		return view('example.php',(array)$output);
	}	

	public function offices()
	{
		$output = $this->render();

		return $this->_example_output($output);
	}

	public function index()
	{
		$this->_example_output((object)array('output' => '' , 'js_files' => array() , 'css_files' => array()));
	}

	public function offices_management()
	{
		try{
			$crud = new grocery_CRUD();

			//$crud->set_theme('datatables');
			$crud->setTable('offices');
			$crud->setSubject('Office,Offices');
			$crud->requiredFields(['city']);
			$crud->columns(['city','country','phone','addressLine1','postalCode']);

			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}

	public function employees_management()
	{
			$crud = new grocery_CRUD();

			//$crud->set_theme('datatables');
			$crud->setTable('employees');
			$crud->setRelation('officeCode','offices','city');
			$crud->displayAs('officeCode','Office City');
			$crud->setSubject('Employee','Employees');

			$crud->requiredFields(['lastName']);
/* The method is taking 3 parameters:
ask ira
The first parameter is the field name in the database. Have in mind that only the name of the file (without the full path) is stored
The second parameter is the field that the file will actually be stored. If the path is the same as the public one then the second parameter will be the same with the 3rd
The public path that the file can be accessed by the end-user that will need to be the full path
Example
$crud->setFieldUpload('image', 'uploader/customer-image', '/assets/images/customers'); */
			$crud->set_field_upload('file_url','assets/uploads/files');

			$output = $crud->render();

			$this->_example_output($output);
	}

	public function customers_management()
	{
			$crud = new grocery_CRUD();

			$crud->setTable('customers');
			$crud->columns(['customerName','contactLastName','phone','city','country','salesRepEmployeeNumber','creditLimit']);
			$crud->displayAs('salesRepEmployeeNumber','from Employeer')
				 ->displayAs('customerName','Name')
				 ->displayAs('contactLastName','Last Name');
			$crud->setSubject('Customer','Customers');
			$crud->setRelation('salesRepEmployeeNumber','employees','lastName');

			$output = $crud->render();

			return $this->_example_output($output);
	}

	public function orders_management()
	{
			$crud = new grocery_CRUD();

			$crud->setRelation('customerNumber','customers','{contactLastName} {contactFirstName}');
			$crud->displayAs('customerNumber','Customer');
			$crud->setTable('orders');
			$crud->setSubject('Order','Orders');
			//ask ira
			$crud->unset_add();
			$crud->unset_delete();

			$output = $crud->render();

			return $this->_example_output($output);
	}

	public function products_management()
	{
			$crud = new grocery_CRUD();

			$crud->setTable('products');
			$crud->setSubject('Product');
			//ask ira
			$crud->unset_columns('productDescription');
			$crud->callback_column('buyPrice',array($this,'valueToEuro'));

			$output = $crud->render();

			$this->_example_output($output);
	}

	public function valueToEuro($value, $row)
	{
		return $value.' &euro;';
	}

	public function film_management()
	{
		$crud = new grocery_CRUD();

		$crud->setTable('film');
		//ask ira
		$crud->set_relation_n_n('actors', 'film_actor', 'actor', 'film_id', 'actor_id', 'fullname','priority');
		$crud->set_relation_n_n('category', 'film_category', 'category', 'film_id', 'category_id', 'name');
		$crud->unset_columns('special_features','description','actors');

		$crud->fields(['title', 'description', 'actors' ,  'category' ,'release_year', 'rental_duration', 'rental_rate', 'length', 'replacement_cost', 'rating', 'special_features']);

		$output = $crud->render();

		return $this->_example_output($output);
	}

	public function film_management_twitter_bootstrap()
	{
		try{
			$crud = new grocery_CRUD();

			//$crud->set_theme('twitter-bootstrap');
			$crud->setTable('film');
			//$crud->set_relation_n_n('actors', 'film_actor', 'actor', 'film_id', 'actor_id', 'fullname','priority');
			//$crud->set_relation_n_n('category', 'film_category', 'category', 'film_id', 'category_id', 'name');
			//$crud->unset_columns('special_features','description','actors');

			$crud->fields(['title', 'description', 'actors' ,  'category' ,'release_year', 'rental_duration', 'rental_rate', 'length', 'replacement_cost', 'rating', 'special_features']);

			$output = $crud->render();
			return $this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}

	function multigrids()
	{
		//ask ira
		$this->config->load('grocery_crud');
		$this->config->set_item('grocery_crud_dialog_forms',true);
		$this->config->set_item('grocery_crud_default_per_page',10);

		$output1 = $this->offices_management2();

		$output2 = $this->employees_management2();

		$output3 = $this->customers_management2();

		$js_files = $output1->js_files + $output2->js_files + $output3->js_files;
		$css_files = $output1->css_files + $output2->css_files + $output3->css_files;
		$output = "<h1>List 1</h1>".$output1->output."<h1>List 2</h1>".$output2->output."<h1>List 3</h1>".$output3->output;

		$this->_example_output((object)array(
				'js_files' => $js_files,
				'css_files' => $css_files,
				'output'	=> $output
		));
	}

	public function offices_management2()
	{
		$crud = new grocery_CRUD();
		$crud->setTable('offices');
		//$crud->setSubject('Office');

		$crud->set_crud_url_path(site_url(strtolower(__CLASS__."/".__FUNCTION__)),site_url(strtolower(__CLASS__."/multigrids")));

		$output = $crud->render();

		if($crud->getState() != 'list') {
			return $this->_example_output($output);
		} else {
			return $output;
		}
	}

	public function employees_management2()
	{
		$crud = new grocery_CRUD();

		//$crud->set_theme('datatables');
		$crud->setTable('employees');
		$crud->setRelation('officeCode','offices','city');
		$crud->displayAs('officeCode','Office City');
		//$crud->set_subject('Employee');

		$crud->required_fields('lastName');

		$crud->set_field_upload('file_url','assets/uploads/files');

		$crud->set_crud_url_path(site_url(strtolower(__CLASS__."/".__FUNCTION__)),site_url(strtolower(__CLASS__."/multigrids")));

		$output = $crud->render();

		if($crud->getState() != 'list') {
			return $this->_example_output($output);
		} else {
			return $output;
		}
	}

	public function customers_management2()
	{
		$crud = new grocery_CRUD();

		$crud->set_table('customers');
		$crud->columns('customerName','contactLastName','phone','city','country','salesRepEmployeeNumber','creditLimit');
		$crud->display_as('salesRepEmployeeNumber','from Employeer')
			 ->display_as('customerName','Name')
			 ->display_as('contactLastName','Last Name');
		$crud->set_subject('Customer');
		$crud->set_relation('salesRepEmployeeNumber','employees','lastName');

		$crud->set_crud_url_path(site_url(strtolower(__CLASS__."/".__FUNCTION__)),site_url(strtolower(__CLASS__."/multigrids")));

		$output = $crud->render();

		if($crud->getState() != 'list') {
			$this->_example_output($output);
		} else {
			return $output;
		}
	}

}