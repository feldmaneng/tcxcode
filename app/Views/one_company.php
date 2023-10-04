<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
 
<?php
if (!empty($css_files)) {
foreach($css_files as $file) { ?>
 <link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
<?php
}
}
?>
 
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
<?php 
$session = session();

$secretKey= $_SESSION["SecretKey"];
$event = $_SESSION["Event"];
$company = $_SESSION["Company"];
$staffName = $_SESSION["StaffName"];
$guestLimit = $_SESSION["GuestLimit"];
$output2 = $_SESSION["Output"];



$var = "Hello World!";
$html = <<<EOT
<div>
    
		
		
		
		
		<br>
 		<h1> $event; </h1>
   		<h2> 	 $company</h2>
   		<p>TestConX EXPO Staff: $staffName <br>
   		(The person in charge of your booth. They will receive a Full Conference registration.)</p>
   		<p>You are entitled to invite $guestLimit guests to receive complimentary <b>Full Conference</b> registration.
   			For customers, staff, and other guests.<br>
   			</p>
    </div>
</div>
EOT;
echo $html;

//print_r($output->output);
//echo $output;
//echo $output2;
 
     if (!empty($output)) {
         echo $output;
     }
     
 ?>
 <?php
if (!empty($js_files)) {
    foreach($js_files as $file) { ?>
        <script src="<?php echo $file; ?>"></script>
    <?php }
}
?>
 		
 		
<!-- End of header-->
    <div style='height:20px;'></div>  
   
<!-- Beginning footer -->
<div><!--Footer--></div>
<!-- End of Footer -->
</body>
</html>