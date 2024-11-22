<html>
<head>
<title>Upload Form</title>
</head>
<body>

<form action=" <?=base_url('ContactCheck/do_upload')?>" enctype="multipart/form-data" method="post">
	
	<label class="form-label" id="uploadFile">Select Files</label>
	<input type="file" name="uploadedFiles[]" multiple="multiple"/>
	<input type="submit" class="btn btn-primary" value="Upload" name="submit"/>

</form>

</body>
</html>
