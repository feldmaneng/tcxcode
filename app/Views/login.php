<!DOCTYPE html>  
<html lang="en">  
<head>  
    <meta charset="utf-8">  
    <title>Login Page</title>  
</head>  
<body>  
    <h1>TestConX Login</h1>
    <strong>Internal Use Only</strong>  

	<div id="form_input">
    <?php  
	
		echo form_open('main'); //login_action');  
  
		//echo validation_errors();  
  
		echo "<p>Username: ";  
		echo form_input('username'); //, $this->input->post('username'));  
		echo "</p>";  
  
		echo "<p>Password: ";  
		echo form_password('password');  
		echo "</p>";  
  
		echo "</p>";  
		echo form_submit('login_submit', 'Login');  
		echo "</p>";  
  
		echo form_close();
    ?>		  
  	</div>
  
  
  <!-- <a href='<?php echo base_url()."tools/secure.php/Main/signin"; ?>'>Sign In</a> -->    
</body>  
</html>  
