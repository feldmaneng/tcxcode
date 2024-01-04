<html>
<head>
<title>Upload Error</title>
</head>
<body>

<?php

//$this->load->library('session');
$session = session(); 
$data = array('upload_data' => $this->upload->data());
$a=$this->upload->data();  
$error_name = $a['file_name'];	

echo "<h2>Error uploading your file - $error_name</h2>";
echo "<h2>Error: " . $error . "</h2>";
echo "<h2>Please make sure your file size is not larger than 1 megabyte.</h2>";
echo "<h2>Please contact TestConX if you are not able to upload your file.</h2>";

echo "<a href=\"javascript:history.go(-1)\">Return to TestConX EXPO Workshop Guide</a>";

?>

</body>
</html>