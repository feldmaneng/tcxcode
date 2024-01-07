<!DOCTYPE html>
<html lang="en">

<head>
<title>Upload Form</title>
</head>
<body>
<?= validation_list_errors() ?>

	<h3>Upload new logo file if logo shown above needs updating.</h3>
	<h3>Please contact the TestConX Office if you are not able to upload your logo file.</h3>

	<?= form_open_multipart('upload/do_upload') ?>
		<input type="file" name="userfile" size="20">
		<br><br>
		<input type="submit" value="upload">

	</form>

</body>
</html>

