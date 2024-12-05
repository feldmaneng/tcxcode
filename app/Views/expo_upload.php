<!DOCTYPE html>
<html lang="en">

<head>
<title>Upload Form</title>
</head>
<body>
<?= validation_list_errors() ?>

	<h3>EXPO Populate</h3>
	
	

	
	
		<?= form_open('EXPOpopulate/populate') ?>
		<label for="year"> Current Event Year:</label>
		<input type="number" id="year" name="year" min ="2000" max ="3000">
		<label for="event"> Event(Mesa,Korea,China):</label>
		<input type="text" id="event" name="event">
		<input type="submit">

	</form>

</body>
</html>

