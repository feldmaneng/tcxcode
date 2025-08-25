<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="utf-8" />
 <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body style="font-family: Arial;">
<!-- Beginning header -->
 <div>
 	<a href='<?php echo site_url('example/customers')?>'>Customers</a>
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

