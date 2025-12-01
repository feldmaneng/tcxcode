<!DOCTYPE html>
<html lang="en">

<head>
<title>Upload Form</title>
</head>
<body>


	<h3>Contact ID Lookup</h3>
	

	<?= form_open_multipart('contactsID/findID') ?>
		<input type="file" name="userfile" size="20">
		<br><br>
		<input type="submit" value="upload">

	</form>

</body>
</html>