<?php
//This gets medications
error_log("In autoComplete.php");
$hostName = $_SERVER["SERVER_NAME"];
error_log("hostName = ".$hostName);
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
error_log("In autoComplete.php, hostName".$hostName);
//partial drug name
$searchValue = $_POST['searchValue'];
error_log("search value after POST = ".$searchValue);
if (empty($searchValue)){
	error_log("Before GET");
	$searchValue = $_GET['searchValue'];
	error_log("After GET = ".$searchValue);
}
error_log("searchvalue before urldecode = ".$searchValue);
$searchValue = urldecode($searchValue);
error_log("searchvalue after urldecode = ".$searchValue);


$con = mysqli_connect("localhost",$uname,$pwd,$dbname);
if (!$con)
{
  die('Could not connect: ' . mysql_error());
}


$query = "select distinct drug_name from sideEffects where drug_name like '".$searchValue."%'";

$result = mysqli_query($con, $query);
//error_log("In autoComplete.php, query result = ".$result);
$i = 0;
while(($row = mysqli_fetch_array($result)) !== FALSE){
	$item = $row['drug_name'];
	if ($item == "") break;
	$i += 1;
	if ($i < 10){
		error_log("item = ".$item);
	}
	echo $item.",";
}
echo "\n";


mysqli_close($con);

?>
