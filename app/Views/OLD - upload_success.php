<html>
<head>
<title>Upload Form</title>
</head>
<body>

<?php //$this->load->library('session');
$session = session(); 

$upload_file = $session->get('upload_filename');

echo "<h3>Your file, $upload_file, was successfully uploaded!</h3>";

//echo "<a href=\"javascript:history.go(-1)\">Return to TestConX EXPO Workshop Guide</a>";

?>
<p><?=
//$keys = session('secretKey');
 anchor('directory?key='.session('secretKey'), 'Return to TestConX EXPO Directory Entry') ?></p>
</body>
</html>