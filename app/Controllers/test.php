<?php

namespace App\Controllers;

use CodeIgniter\Files\File;

class test extends BaseController
{
    protected $helpers = ['form'];

    

    public function testarray()
    {
		$array = array("snapple","tomato","pear");
		
		
		if(in_array("apple",$array,true)){
			echo "there is an apple;";
							}
							
		if(!in_array("apple",$array,true)){
			echo "no apple;";
							}
							
	}
}

?>