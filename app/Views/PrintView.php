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
body{
	color:orange;
	
}
.button {
  background-color: black;
  border: 5px;
  border-color:white;
  border-style: solid;
  color: white;
  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
  position:relative;
  top:100px;
  right:130px;
 
 
  
}
.field {
	
  background-color: black;
  border: 1px;
  border-color:white;
  border-style: solid;
  color: white;
  padding: 1px 1px;
  text-align: center;
  text-decoration: none;
 
  font-size: 16px;
  margin: 1px 1px;
  cursor: pointer;
  
  
  
 
 
}
input[type=text]:focus {
  border: 3px solid #555;
}
input[type=text] {
	width: 10%;
  background-color: white;
  color: black;
}
 *{
	background-color:gray;
} 

</style>
<body>

<h1>Input your Badge ID</h1>

<?php
/* *{
	background-color:gray;
} */
//<a href="#" onclick="window.print(); return false;">Click me to Print</a>
echo form_open('PrintBadge/print','class="field"');
//echo form_open('PrintBadge/print');
echo form_input('BadgeID', '1233');
echo form_submit('mysubmit', 'Submit','class="button"');
?>




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