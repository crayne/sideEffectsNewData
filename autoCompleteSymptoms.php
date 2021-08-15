<?php
$hostName = $_SERVER["SERVER_NAME"];
$pos = strpos($hostName,"oryxtech");
if ($pos === false){
	$uname = "root";
	$pwd = "root";
        $dbname = "sideEffectsNewSource";
}
else {
	$uname = "oryxtech_sideE";
	$pwd = "RW38>!mD?R";
	$dbname = "oryxtech_sideEffects";
}
//partial drug name
$searchValue = $_POST['searchValue'];
if (empty($searchValue)){
	error_log("Before GET");
	$searchValue = $_GET['searchValue'];
	error_log("After GET");
}
$con = mysqli_connect("localhost",$uname,$pwd,$dbname);
if (!$con)
{
  die('Could not connect: ' . mysql_error());
}

//Changed because "restlessness" was not foundd
// $query = "select distinct UMLSConceptName from adverse_effects where UMLSConceptName like '".$searchValue."%'";
$query = "select distinct one_side_effect from sideEffects where one_side_effect like '%".$searchValue."%'";

error_log("In autoCompleteSymptoms.php, query = ".$query);


$result = mysqli_query($con,$query);

$numRows = mysqli_num_rows($result);

error_log("In autoCompleteSymptoms.php, numRows = ".$numRows);



while(($row = mysqli_fetch_array($result)) !== FALSE){
	//$item = $row['UMLSConceptName'];
	$item = $row['one_side_effect'];
	if ($item == "") break;
	echo $item."+";
}
echo "\n";

mysqli_close($con);

?>
