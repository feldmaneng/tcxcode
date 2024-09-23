<!DOCTYPE html>
<html lang="en">

<head>
<title>Upload Form</title>
</head>
<body>
<h3> This is my test view</h3>
<h3> Here we will test variables below</h3>
<h1><?= esc($var1) ?></h1>
<h1><?= esc($var2) ?></h1>
<button onclick="viewfunction()">Click to view</button>
<button onclick="displayDate()">The time is?</button>
 <?php foreach ($var3 as $item): ?>

        <li><?= esc($item) ?></li>

    <?php endforeach ?>
	<script>
	function viewfunction(){
		
		const element = document.createElement('div');
		element.innerHTML = "<p>Hi Bree you have a nice smile.♥</p>";
		document.body.appendChild(element);
		
	}
	
		function displayDate() {
	  document.getElementById("demo").innerHTML = Date();
	}
	
	</script>
	<p id="demo"></p>
</body>
</html>