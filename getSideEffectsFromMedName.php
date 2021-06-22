<?php
include_once "header.php";



$medication = $_POST['medName'];
if (empty($medication)){
	error_log("Before GET");
	$medication = $_GET['medName'];
	error_log("After GET, medication = ".$medication);
}


$returnPrefix = null;
if (!empty($origMedication)) $returnPrefix = $origMedication." - ";

error_log("In getSideEffectsFromMedName, line 34");




$con = mysqli_connect("localhost",$uname,$pwd,$dbname);
if (!$con)
  {
  die('Could not connect: ' . mysqli_error());
  }

$query = "select one_side_effect from sideEffects where drug_name = '".$medication."'";

error_log("In getSideEffectsFromMedName.php, query = ".$query);


$result = mysqli_query($con,$query);

$numRows = mysqli_num_rows($result);


for($i=0; $i<$numRows; $i++){
	//if ($i > 0) echo ",";
	$row = mysqli_fetch_array($result);
	$item = $row['one_side_effect'];
	error_log("In getSideEffectsFromMedName.php, side effect item is @".$item."@");
	echo $item;
	if ($i != $numRows - 1) echo ",";
}

mysqli_close($con);


?>
