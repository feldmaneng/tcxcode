<!DOCTYPE html>
<html lang="en">

<head>
<title>Upload Form</title>
</head>
<body>
<?= validation_list_errors() ?>

	<h3>Contact ID Checking</h3>
	

	<?= form_open_multipart('Authorpopulate/do_upload') ?>
		<input type="file" name="userfile" size="20">
		<br><br>
		<input type="submit" value="upload">

	</form>

</body>
</html>

