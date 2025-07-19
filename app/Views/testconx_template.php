<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="utf-8" />
 <meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php foreach($js_files as $file): ?>
    <script src="<?php echo $file; ?>"></script>
<?php endforeach; ?>
<?php
if (!empty($css_files)) {
foreach($css_files as $file) { ?>
 <link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
<?php
}
}
?>
</head>
<body style="font-family: Arial;">

<!-- Beginning header -->
 <div>
 	<!-- <a href='<?php echo site_url('example/customers')?>'>Customers</a> -->

 		<h1>TestConX Office Use Only</h1>
 		
        <a href='<?php echo site_url()?>'>Menu</a> 

		|  Hello <?php	
		
				/*if (isset($_SERVER['REMOTE_USER'])) {
 					echo " ".$_SERVER['REMOTE_USER']. " -- ";
 					
 					// Logout code does not appear to be working in Chrome
 					if (strpos(base_url(), 'dev.testconx.org') > 0 ) {
 						echo '<a href="https://logout:logout@dev.testconx.org">logout</a>';
 					} else {
	 					echo '<a href="https://logout:logout@testconx.org">logout</a>';
	 				}
 				} else {
	 				echo "local_user"; 	 
	 			} 
	 			*/
	 			
	 				$session = session();
		
					if (isset($session->tcx_userdata) && isset($session->tcx_userdata['username'])) {
						echo $session->tcx_userdata['username'] . " | ";
						// Add logout code here
						echo '<a href="../logout">logout</a>';
						
					} else {
						echo "local_user"; 	
					}
	 		?>
		<?php
 		//echo "<pre>";

		//die(print_r(app.baseURL, TRUE));
		?>
		
    </div>


<!-- End of header-->


 <!-- Beginning of main content -->
 <div style='height:20px;'></div> 
 <div style='padding: 10px;'>
     <?php
     if (!empty($output)) {
         echo $output;
     }
     ?>
 </div>
 <!-- End of main content -->
 
<!-- Beginning footer -->

<!-- End of Footer -->
<?php
if (!empty($js_files)) {
    foreach($js_files as $file) { ?>
        <script src="<?php echo $file; ?>"></script>
    <?php }
}
?>
</body>
</html>
