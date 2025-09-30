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
$guestCount = $_SESSION["GuestCount"];
$invitedStaffCount = $_SESSION["StaffCount"];
$staffLimit = $_SESSION["StaffLimit"];


$totalInviteLimit = $staffLimit + $guestLimit; //Stop allowing the addition of people at this number

$message = '';
if ($invitedStaffCount >= $staffLimit) {
	$message = "<b>You have reached the limit of invites for staff and others related to your company. Do not invite more related people without contacting the TestConX Office.</b>";
}

$var = "Hello World!";
$html = <<<EOT
<div>
    
		
		
		
		
		<br>
 		<h1> $event - Guest List for $company </h1>
		
   		
   		<p>TestConX EXPO Coordinator: $staffName </p>
   		<p>You are entitled to invite $guestLimit guests to receive complimentary <b>Full Conference</b> registration for customers and other guests.</p>
   		   The limit for staff, employees, and other people related to your company is $staffLimit.<br>
   		<p>This form will stop accepting registrations at a total of $totalInviteLimit registrations.</p>
		<p>The TestConX Office will regularly review registrations. At last review you have used $invitedStaffCount staff and $guestCount guest registrations.<br>
			$message</p>
		
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