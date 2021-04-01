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
?>
