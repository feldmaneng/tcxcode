<?php

namespace App\Controllers;
use Config\Database as ConfigDatabase;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;
class Generalform extends BaseController {
	public function __construct()
	{
		
		helper('form');
		helper('text');
		helper('url');
	
	}
	public function index()
	{
			
			return view('generalform');
	}
	
	public function certs()
	{
			echo $_POST["year"];
			echo $_POST["event"];
			
	}
	
	
}
?>