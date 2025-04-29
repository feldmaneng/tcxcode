<!DOCTYPE html>
<html lang="en">
<head>
    <title>Upload Form</title>
</head>
<body>



<?= form_open_multipart('uploadevent/uploadevent') ?>
    <input type="file" name="userfile" size="20">
    <br><br>
    <input type="submit" value="upload">
</form>

</body>
</html>