<?php 

$servername = x
$username = x
$password = x
$database = x

$con = @new mysqli($servername, $username, $password, $database);
	 
if (mysqli_connect_errno()) {
	printf("Unable to connect to database: $s", mysqli_connect_error());
	exit();
}

$p_username = mysqli_real_escape_string($con, $_GET['username']);
$p_password = mysqli_real_escape_string($con, $_GET['password']);


$sql="INSERT INTO shatapp_users (username, password)
VALUES ('$p_username','$p_password')";

if (!mysqli_query($con,$sql)) {
	die('Error: ' . mysqli_error($con));
} else {
	
	echo("Bruger " . $p_username . " oprettet!<br/>");
}


mysqli_close($con);


?>