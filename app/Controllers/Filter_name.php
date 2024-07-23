<?php

namespace App\Controllers;

use Config\Database as ConfigDatabase;

$session = session();

if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotfound();
	die ("Login failure");
}

ini_set('display_errors', 'On');

error_reporting(E_ALL | E_STRICT);

class Filter_name extends BaseController {
	public function index()
	{
	echo "<h1>Filter function for name checking<a href=" . site_url('/Filter_name/filter') ."> click here</a>";	
		
		
		
	}
		function filter(){
			//the file should not have column names
			$file = "filter.csv";
			if(($handle = fopen($file,"r")) !==FALSE)	{
				
				$list = array_map('str_getcsv', file($file));
				
				$idrow = array_column($list,0);
				$emailrow = array_column($list,1);
				$namerow =array_column($list,2);
				$numrows = count($idrow);
				$capitalcount = 0; 
					for($c=0; $c < $numrows; $c++) {
						
						$len = strlen($list[$c][2]);
						$array = str_split($list[$c][2]);
						for($x=0; $x <= $len; $x++){
							$unicode = mb_ord($array[$x],"UTF-8");
								if($unicode >= 65){
									$capitalcount++;
								}
							
						}
						echo $list[$c][0].";".$list[$c][1].";".$list[$c][2].";".$capitalcount."<br>";						
						
					}
						
						
			}
	
	
		}
	





}