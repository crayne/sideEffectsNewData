<?php
include_once "header.php";

/*
 * Check discrepancies between this and symptom search by medication
 */

$symptom = $_POST['symptom'];
if (empty($symptom)){
	error_log("Before GET, symptom = ".$symptom);
	$symptom = $_GET['symptom'];
	error_log("After GET, symptom = ".$symptom);
}



$medication = $_POST['medication'];
if (empty($medication)){
	error_log("Before GET");
	$medication = $_GET['medication'];
	error_log("After GET, medication = ".$medication);
}


$returnPrefix = null;
if (!empty($origMedication)) $returnPrefix = $origMedication." - ";

error_log("In searchSideEffectVerbal, line 34");




$con = mysqli_connect("localhost",$uname,$pwd,$dbname);
if (!$con)
  {
  die('Could not connect: ' . mysqli_error());
  }

$query = "select distinct drug_name from sideEffects where drug_name = '".$medication."' and one_side_effect = '".$symptom."'";

error_log("In searchSideEffect.php, query = ".$query);


$result = mysqli_query($con,$query);

$numRows = mysqli_num_rows($result);


error_log("In searchSideEffect.php, numRows (should be 1 or 0) = ".$numRows);
if ($numRows == 0){
	mysqli_close($con);
	exit;
}


for($i=0; $i<$numRows; $i++){
	//if ($i > 0) echo ",";
	$row = mysqli_fetch_array($result);
	$item = $row['drug_name'];
	error_log("In searchSideEffect.php, med name item is @".$item."@");
	echo $item;
}

mysqli_close($con);


?>
