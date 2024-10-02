<!DOCTYPE html>
<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<html>
<head>
<title>Badge Printing</title>
</head>
<style>
.topright{
	position:absolute;
	top:8px;
	right:16px;
}
</style>
<body>

<h1>Input your Badge ID</h1>

<?php

echo form_open('PrintBadge/print');
echo form_input('BadgeID', '1233');
echo form_submit('mysubmit', 'Submit');
?>
<a href="#" onclick="window.print(); return false;">Click me to Print</a>



<object class="topright" 
type="application/pdf"
data="/tmpqr/BadgeTest.pdf#toolbar=0"
width="350"
height="500"
>
</object> 


<script>
window.print();

console.log('Test22');
</script>

</body>
</html>