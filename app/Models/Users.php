<?php  
  
namespace App\Models;

use CodeIgniter\Model;

class Users extends Model
{
 
	// Good hashing tool https://onlinephp.io/password-hash
	
	
    public function getCheckUserPassword($userName, $password) {  
    
    	$control_db = db_connect('control');
  		$builder = $control_db->table('users');
  		
  		$builder->where('UserName', $userName);  
  		
        //$this->control_db->where('PasswordHash', $this->input->post('password'));  
        //$query = $this->control_db->get('users');  
        $query = $builder->get();
          
        if ($query->getNumRows() == 1) {
        	$row = $query->getRow();
          	if (password_verify($password, $row->PasswordHash)) {  
            	return true;  
        	} 
        }
        return false;  
    }  
  
      
}  
?>  