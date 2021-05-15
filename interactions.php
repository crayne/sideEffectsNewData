<?php
//For each medication, try to match against genericName in label_mapping table
//If found, get nuid
//If no map, select genericName, combination from label_mapping where medication = brandName
//	If not combination, get nuid using genericName.  If not found, look for nuid using first word of generic name
//   If Combination:  get components from combinations table
//	 For each component, getuid.  If not found, look for nuid using first word of component

//Save nuids in associative array like this:
//nuidArray[nuid] = originalMedication or
//nuidArray[nuid] = originalMedication,componentName

include_once "nuidNLMSearch.php";

Class Interaction {
	public $drug1;
	public $drug2;
}

$allConceptNames;
$nuidArray;
$interUrl;
$childParentArray;

//Make associative array:  $childParentArray[rxcuid] = drugname
function getParentChildMedsFromJson($thisMed, $thisRxcuid){
	error_log("In getParentChildMedsFromJson, thisMed = ".$thisMed);
	global $childParentArray;
	$componentRxcuidArray;

//https://rxnav.nlm.nih.gov/REST/rxcui/1372704/related.json?tty=PIN
	$thisUrl = "https://rxnav.nlm.nih.gov/REST/rxcui/".$thisRxcuid."/related.json?tty=PIN";
	error_log("In getParentChildMedsFromJson, url = ".$thisUrl);
	//Get JSON result
	$result = file_get_contents($thisUrl);
	$decodedResult = json_decode($result);
	$encodedConceptProperties = json_encode($decodedResult->relatedGroup->conceptGroup[0]->conceptProperties);
	//This contains the rxcuid
	$conceptPropertiesArray = $decodedResult->relatedGroup->conceptGroup[0]->conceptProperties;
	error_log("In getParentChildMedsFromJson, conceptPropertiesArray length = ".count($conceptPropertiesArray));

//TODO add elements of $componentRxcuidArray to $rxcuidArray
  for ($j=0; $j<count($conceptPropertiesArray); $j++){
		$key = $conceptPropertiesArray[$j]->name;
		$key = truncateToFirstBlank($key);
		$childParentArray[$key] = $thisMed;
		error_log("In getParentChildMedsFromJson, medication for key ".$key." is ".$thisMed);

	}
}

$hostName = $_SERVER["SERVER_NAME"];
$pos = strpos($hostName,"oryxtech");
if ($pos === false){
	$uname = "root";
	$pwd = "root";
        $dbname = "sideEffects";
}
else {
	$uname = "oryxtech_sideE";
	$pwd = "RW38>!mD?R";
	$dbname = "oryxtech_sideEffects";
}
error_log("In interactions.php, hostName".$hostName);
//partial drug name
$medNames = $_POST['medNames'];
error_log("medNames after POST = ".$medNames);
if (empty($medNames)){
	error_log("Before GET");
	$medNames = $_GET['medNames'];
	error_log("After GET, medNames = ".$medNames);
}
$medNames = urldecode($medNames);
$medications = explode(",",$medNames);

$con = mysqli_connect("localhost",$uname,$pwd,$dbname);
if (!$con)
{
  die('Could not connect: ' . mysqli_error());
}

$NS = new NUIDSearch;
error_log("In interactions.php, before brandNameSearch loop");
for ($i=0; $i<count($medications); $i++){
	$thisMed = $medications[$i];
	$nuid = $NS->getRxcuid($thisMed);
	$nuidArray[$nuid] = $thisMed;
	getParentChildMedsFromJson($thisMed, $nuid);
}
$nuidArrayKeys = array_keys($nuidArray);

//Now do the interactions
//See: https://rxnav.nlm.nih.gov/InteractionAPIs.html#uLink=Interaction_REST_findInteractionsFromList
//Example: https://rxnav.nlm.nih.gov/REST/interaction/list.json?rxcuis=207106+152923+656659

$interUrl = 	"http://rxnav.nlm.nih.gov/REST/interaction/list?&sources=DrugBank&rxcuis=";
$params = "";
for ($i=0; $i<count($nuidArrayKeys); $i++){
	$key = $nuidArrayKeys[$i];
	if ($i==0) $params .= $key;
	else $params .= "+".$key;
}
$params .= "&scope=3";
//urlencode of params does not work
$interUrl = $interUrl.$params;
error_log("In interactions.php, url to get actual interactions is: ".$interUrl);
$result = file_get_contents($interUrl);
error_log("In interactions.php, result of url search is: ".$result);
mysqli_close($con);

$dom = new DomDocument("1.0", "ISO-8859-1");
$dom->loadXML($result);

$items = $dom->getElementsByTagName('interactionPair');
if ($items->length == 0) {
	error_log("No interactions found");
	return;
}
else {
	$interactionArray = extractInteractionsFromXML($result, $medications);
	echo json_encode($interactionArray);
	return;
}

//Search for interactions pairs??
function extractInteractionsFromXML($result, $medications){
	global $allConceptNames;
	global $childParentArray;
	error_log("result of interaction query is: " + $result);
	$xml = simplexml_load_string($result);
	//Get all interactionPair nodes
	//$query="//interactionTriple/groupConcepts/concept/conceptKind[.='DRUG_INTERACTION_KIND']";
	$query=".//interactionPair";
	error_log("Getting interactionPairNodes");
	$interactionPairNodes = $xml->xpath($query);
	$numberOfInteractions = count($interactionPairNodes);
	error_log("Number of interactions = ".$numberOfInteractions);
	error_log("First interactionPair node: ".$interactionPairNodes[0]);

	//Get all brand names
	$query=".//minConcept/name";
	$allConceptNames = $xml->xpath($query);
	for ($i=0; $i<count($allConceptNames); $i++) {
		$allBrandNames[$i] = (string)$allConceptNames[$i];
		error_log("allBrandNames[".$i."] is ".$allBrandNames[$i]);
	}

	//Get all generic names
	$query=".//minConceptItem/name";
	$allConceptItemNames = $xml->xpath($query);
	for ($i=0; $i<count($allConceptItemNames); $i++) {
		$allConceptNames[$i] = (string)$allConceptItemNames[$i];
		error_log("allConceptNames[".$i."] is ".$allConceptNames[$i]);
	}
	//Get all medication rxcuids
	$query=".//minConceptItem/rxcui";
	$allConceptItemRxcuids = $xml->xpath($query);
	for ($i=0; $i<count($allConceptItemRxcuids); $i++) {
		$allConceptRxCuids[$i] = (string)$allConceptItemRxcuids[$i];
	}

  //Get severity array
	$query="//interactionPair/severity";
	$severities = $xml->xpath($query);
	error_log("severities length = ".count($severities));
  foreach($severities as $severity) {
    	error_log("In extractInteractionsFromXML, severity is: ".$severity);
    }
    //Get description array
  $query="//interactionPair/description";
  $descriptions = $xml->xpath($query);
  $interactionArray = array();
  //Make array of interactions
  $j=0;
  $i = 0;
  //Is number of interactions correct?  There appear to be 2 interactions, with severity only in the second
  $validInteractionCounter = 0;
  do {
  	$interaction = new Interaction();
  	$k = 2*$i;
  	$interaction->drug1 = $allConceptNames[$k];
		$interaction->nui1 = $allConceptRxCuids[$k];
   	$interaction->drug2 = $allConceptNames[$k+1];
 		$interaction->nui2 = $allConceptRxCuids[$k+1];
  	$interaction->interactionNui = $allConceptRxCuids[$j+4];
  	$interaction->severity = $severities[$i];
  	$interaction->descriptionText = $descriptions[$i];
		$drug1 = truncateToFirstBlank($interaction->drug1);
  	$interaction->originalDrugName1 = $allBrandNames[$k];
		//We should be able to get the original drug name here!!
		//if ($interaction->originalDrugName1 == null) $interaction->originalDrugName1 = $interaction->drug1;
		$drug2 = truncateToFirstBlank($interaction->drug2);
  	$interaction->originalDrugName2 = $allBrandNames[$k+1];
		//if ($interaction->originalDrugName2 == null) $interaction->originalDrugName2 = $interaction->drug2;
  	$interactionArray[$validInteractionCounter] = $interaction;
  	error_log("Value of validInteractionCounter in interaction loop is ".$validInteractionCounter);
  	$validInteractionCounter++;
  	$i++;
  	$j+=5;
  } while ($i<$numberOfInteractions);

  error_log("validInteractionCounter = ".$validInteractionCounter);
  for ($i=0; $i<$validInteractionCounter; $i++) {
  	error_log("");
  	error_log("interactionArray[$i]->drug1 = ".$interactionArray[$i]->drug1);
  	error_log("interactionArray[$i]->drug2 = ".$interactionArray[$i]->drug2);
  	error_log("interactionArray[$i]->nui1 = ".$interactionArray[$i]->nui1);
  	error_log("interactionArray[$i]->nui2 = ".$interactionArray[$i]->nui2);
  	error_log("interactionArray[$i]->interactionNui = ".$interactionArray[$i]->interactionNui);
  	error_log("interactionArray[$i]->severity = ".$interactionArray[$i]->severity);
  	error_log("interactionArray[$i]->descriptionText = ".$interactionArray[$i]->descriptionText);
  	error_log("interactionArray[$i]->originalDrugName1 = ".$interactionArray[$i]->originalDrugName1);
  	error_log("interactionArray[$i]->originalDrugName2 = ".$interactionArray[$i]->originalDrugName2);
	}
  return $interactionArray;
}

function truncateToFirstBlank($input){
	$output = $input;
	$firstBlank = strpos($input, " ", 0);
	if ($firstBlank != false) {
		$output = substr($input, 0, $firstBlank);
	}
	return $output;
}
?>
