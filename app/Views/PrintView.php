<!DOCTYPE html>
<html>
<head>
<title>Badge Printing</title>
</head>
<body>

<h1>Input your Badge ID</h1>
<?php
echo form_open('PrintBadge/print');
echo form_input('BadgeID', '1233');
echo form_submit('mysubmit', 'Submit');
?>
<a href="#" onclick="window.print(); return false;">Click me to Print</a>
<script>
window.print();

console.log('Test22');
</script>

</body>
</html>