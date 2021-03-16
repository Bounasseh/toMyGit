<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
</head>
<body>
	<form action="#" method="POST" enctype="multipart/form-data">
		<label for="file"></label>
		<input id="file" name="file" type="file">
		<br><br>
		<input name="upload" type="submit" value="update">
		<br><br>
	</form>
</body>
</html>

<?php
	require 'vendor/autoload.php';

	//$client = new MongoDB\Client("mongodb://localhost:27010");
	$client = new MongoDB\Client;
	$mydb = $client->selectDatabase('mydb');
	$myTable = $mydb->selectCollection('myTable');

	if(isset($_POST["upload"]))
	{
		echo "*****<br />";

		if(move_uploaded_file($_FILES['file']['tmp_name'], 'upload/'.$_FILES['file']['name']))
		{
			$fileName = $_FILES['file']['name'];

			echo "file '".$fileName."' has been uploaded !<br>";

			$result = $myTable->insertOne(
				[
					"_id" => 5,
					"fileName" => $fileName
				]
			);

			if($result->getInsertedCount() > 0)
			{
				echo "file '".$fileName."' has been inserted !<br />";
			}
			else
			{
				echo "file has NOT been inserted !<br />";
			}
		}
		else
		{
			echo "file has NOT been uploaded !<br />";
		}

		echo "*****<br />";
	}

	$results = $myTable->find();
	foreach($results as $key => $value)
	{
		echo $value['fileName'].'<br />';
		
		?> <!-- img src="upload/windows.png" alt="windows.png"> <br / --> <?php

		echo '<img src="upload/'.$value['fileName'].'" alt="'.$value['fileName'].'"> <br />';
	}
?>