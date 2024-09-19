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

 <?php foreach ($var3 as $item): ?>

        <li><?= esc($item) ?></li>

    <?php endforeach ?>
</body>
</html>