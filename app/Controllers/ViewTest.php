<?php

namespace App\Controllers;
use Config\Database as ConfigDatabase;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;
class ViewTest extends BaseController {
	public function __construct()
	{
		
		helper('form');
		helper('text');
		helper('url');
	
	}
	public function index()
	{
			$data['var1']   = 'this is a variable';
			return view('viewtest1',$data);
	}
	
	
}
?>
