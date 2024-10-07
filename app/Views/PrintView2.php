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
	width: 10%;
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
  <div class="item1"><img src="/EXPOdirectory/logos/Bece.png"/></div>
  <div class="item2"><h4>Scan your QR code <br>or enter your email</h4></div>
  <div class="item3" id="hide">
  <iframe 
        src="/tmpqr/BadgeTest.pdf" id="myFrame" 
            frameborder="0" style="border:0;" 
                width="350" height="500">
    </iframe>
  </div>  
  <div class="item4">
  <?php
  /*  <object  
type="application/pdf"
data="/tmpqr/BadgeTest.pdf#toolbar=0"
width="350"
height="500"
>
</object> */
/* *{
	background-color:gray;
} */
//<a href="#" onclick="window.print(); return false;">Click me to Print</a>
echo form_open('PrintBadge/print','class="field"');
//echo form_open('PrintBadge/print');
echo form_input('BadgeID', '1233');
echo form_submit('mysubmit', 'Submit','class="button" onclick="myFunction()"');
?>
</div>
  <div class="item5">
  <button type="button" class="button" onclick="Clear()" >Clear</button>
  </div>
  <div class="item6"> <input type="button" id="bt" class="button"  onclick="print()" value="Print PDF" /></div>  
  
</div>








<script>
function Clear(){
	location.replace("https://www.testconx.org/forms.php/print";
}
function myFunction() {
  var x = document.getElementById("myDIV");
  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}

let print = () => {
        let objFra = document.getElementById('myFrame');
        objFra.contentWindow.focus();
        objFra.contentWindow.print();
}
window.print();

console.log('Test22');
</script>

</body>
</html>