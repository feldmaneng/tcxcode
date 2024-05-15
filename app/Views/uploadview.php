<html>
<head>
<title>Upload Form</title>
</head>
<body>

<?php echo form_open_multipart('Testupload/do_upload');?>

<input type="file" name="fileToUpload" id="fileToUpload" />

<input type="submit" value="Upload Zoom Csv" name="submit"/>

</form>

</body>
</html>
