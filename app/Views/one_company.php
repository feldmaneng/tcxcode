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

        <!-- <a href='<?php echo site_url('main2/company')?>'>Company List</a> | 
        <a href='<?php echo site_url('examples/employees_management')?>'>Employees</a> |
        <a href='<?php echo site_url('examples/customers_management')?>'>Customers</a> |
        <a href='<?php echo site_url('examples/orders_management')?>'>Orders</a> |
        <a href='<?php echo site_url('examples/products_management')?>'>Products</a> | 
        <a href='<?php echo site_url('examples/film_management')?>'>Films</a>
 		-->
 		
 		Secret Key:  <? echo $_SESSION["SecretKey"] ?><br>
 		<h1><? echo $_SESSION["Event"] ?></h1>
   		<h2><? echo $_SESSION["Company"] ?></h2>
   		<p>TestConX EXPO Staff 展商员工: <? echo $_SESSION["StaffName"] ?><br>
   		(The person in charge of your booth. They will receive a Full Conference registration.<br>
   		该人员为负责贵展位的直接联系人，将获得全场会议通行证。)</p>
   		<p>You are entitled to invite <? echo $_SESSION["GuestLimit"] ?> guests to receive complimentary <b>Full Conference</b> registration.
   			For customers, staff, and other guests.<br>
   			贵司有权邀请<? echo $_SESSION["GuestLimit"] ?>张额外全场会议通行证，供贵司员工、客户或相关客人入场。</p>
    </div>
<!-- End of header-->
    <div style='height:20px;'></div>  
    <div>
<?php echo $output; ?>
 
    </div>
<!-- Beginning footer -->
<div><!--Footer--></div>
<!-- End of Footer -->
</body>
</html>