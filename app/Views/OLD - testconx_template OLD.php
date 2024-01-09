<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
 
<?php 
foreach($css_files as $file): ?>
    <link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
 
<?php endforeach; ?>
<?php foreach($js_files as $file): ?>
 
    <script src="<?php echo $file; ?>"></script>
<?php endforeach; ?>
 
<style type='text/css'>
body
{
    font-family: Arial;
    font-size: 14px;
}
a {
    color: blue;
    text-decoration: none;
    font-size: 14px;
}
a:hover
{
    text-decoration: underline;
}
</style>
</head>
<body>
<!-- Beginning header -->
    <div>

 		<h1>TestConX Office Use Only</h1>
 		
        <a href='<?php echo site_url()?>'>Menu</a> 
        <!-- <a href='<?php echo site_url('main/contact700169')?>'>Guests</a> -->
        <!-- |
        <a href='<?php echo site_url('examples/customers_management')?>'>Customers</a> |
        <a href='<?php echo site_url('examples/orders_management')?>'>Orders</a> |
        <a href='<?php echo site_url('examples/products_management')?>'>Products</a> | 
        <a href='<?php echo site_url('examples/film_management')?>'>Films</a>
		-->
		|  Hello <?php	if (isset($_SERVER['REMOTE_USER'])) {
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
	 		?>
		<?php
 		//echo "<pre>";

		//die(print_r(app.baseURL, TRUE));
		?>
		
    </div>
<!-- End of header-->
    <div style='height:20px;'></div>  
    <div>
<?php echo $output; ?>
 
    </div>
<!-- Beginning footer -->
<div>Footer</div>
<!-- End of Footer -->
</body>
</html>