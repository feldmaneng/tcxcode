<!DOCTYPE html>
<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<html>
<head>
<title>Badge Printing</title>
<script>
 function printTrigger(myFrame) {
            var getMyFrame = document.getElementById(myFrame);
            getMyFrame.focus();
            getMyFrame.contentWindow.print();
        }

</script>
</head>
<style>


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
  
  grid-column-start: 1;
  grid-column-end: 2;
  grid-row-start: 1;
  grid-row-end: 2;
}
.item2 {
  grid-column-start: 3;
  grid-column-end: 4;
  grid-row-start: 1;
  grid-row-end: 2;
}
.item3 {
  grid-column-start: 2;
  grid-column-end: 3;
  grid-row-start: 1;
  grid-row-end: 3;
}
.item4 {
  grid-column-start: 2;
  grid-column-end: 3;
  grid-row-start: 4;
  grid-row-end: 5;
  
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
  <div class="item1"><img width = "500px" style=" 
  background-color:black;
 
  border-width: 3px;
  border-color: black;" src="/images_new/TestConX-LOGO-White_1500.png"/></div>
  <div class="item2"><h4>Preview your badge here. <br>Need Assistance? Please ask the registration desk.</h4></div>
  <div class="item3" id="hide"><h4>Print below</h4>
  <iframe 
        src="/tmpqr/BadgeTest.pdf#toolbar=0" id="myFrame" 
            frameborder="0" style="border:0;" 
                width="275" height="400">
    </iframe>
  </div>  
 

  <div class="item4">
  <button type="button" class="button" onclick="Clear()" >Clear</button>
  </div>
  <div class="item5"></div>
  <div class="item6"></div> 
  <div class="item7"></div>
  <div class="item8"></div> 
  <div class="item9"></div> 
  <div class="item10"></div>
  <div class="item11"></div> 
  
</div>








<script>
function Clear(){
	
	location.replace("https://www.testconx.org/forms.php/PrintBadge/korea");
}

</script>

</body>
</html>