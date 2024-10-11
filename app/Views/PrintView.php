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
	width: 20%;
  background-color: white;
  color: black;
}
.grid-container {
  display: grid;
  gap: 20px;
  grid-template-columns: auto auto auto;
  background-color: black;
  padding: 0px;
}

.grid-container > div {
  background-color: black;
  text-align: center;
  padding: 20px 0;
  font-size: 30px;
}
.item1 {
  background-color: white;
  grid-column-start: 1;
  grid-column-end: 2;
  grid-row-start: 1;
  grid-row-end: 2;
}
.item2 {
  grid-column-start: 2;
  grid-column-end: 3;
  grid-row-start: 1;
  grid-row-end: 2;
}
.item3 {
  grid-column-start: 3;
  grid-column-end: 4;
  grid-row-start: 1;
  grid-row-end: 4;
}
.item4 {
  grid-column-start: 1;
  grid-column-end: 3;
  grid-row-start: 2;
  grid-row-end: 3;
}
 *{
	background-color:black;
} 
#hide {
  width: 100%;
  padding: 50px 0;
  text-align: center;
  background-color: black;
  margin-top: 20px;
}
</style>
<body>


<div class="grid-container">
  <div class="item1"><img width  = "500px" style=" 
  background-color:rgb(60, 60, 60);
  border-style: outset;
  border-width: 3px;
  border-color: black;" src="/images_new/TestConX-LOGO-Black_1500.png"/></div>
  <div class="item2"><h4>Scan your QR code <br>or enter your email</h4></div>
  <div class="item3" ></div>  
  <div class="item4">
  <?php
/* *{
	background-color:gray;
} */
//<a href="#" onclick="window.print(); return false;">Click me to Print</a>
echo form_open('PrintBadge/printgeneral','class="field"');
echo form_hidden('eventYearID', 'Korea2024');
//echo form_open('PrintBadge/print');
echo form_input('BadgeID', '1233');
echo form_submit('mysubmit', 'Submit','class="button"');
?>
</div>
  <div class="item5"></div>
  <div class="item6"></div>  
  
</div>









</body>
</html>