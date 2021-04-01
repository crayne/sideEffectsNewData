<?php

class NUIDSearch {

	public $isCombination;
	public $componentName;


//Replaces isMedicationGeneric
	public function getGenericNameFromNLM($medicationName) {
		$url = "http://rxnav.nlm.nih.gov/REST/drugs?name=";
		$url.= $medicationName;
		error_log("In getGenericNameFromNLM, url for rxnav api call is: ".$url);
		$result = file_get_contents($url);
		//error_log("in getGenericNameFromNLM, result of rxnav api call is: ".$result);
		$dom = new DomDocument("1.0", "ISO-8859-1");
		$dom->loadXML($result);
		//$nodes = $xpath->query('///rxnormdata/drugGroup/conceptGroup[2]/conceptProperties[1]/name');
		$items = $dom->getElementsByTagName('rxcui');
		/*
		$numItems = $items->length;
		$rxcui = $items->item($numItems-1)->nodeValue;
		$name = $this->getDisplayNameFromRXCUI($rxcui, $medicationName);
		return $name;
		*/
		$items = $dom->getElementsByTagName('name');
		//$nameLine = $items->item(1)->nodeValue;
		//Get first word
		//$nameLineArray = explode(" ",$nameLine);
		//$firstChar = substr($nameLineArray[0],0,1);
		$firstChar = "x";
		for ($i=1; $i<$items->length; $i++) {
			$nameLine = $items->item($i)->nodeValue;
			$firstChar = substr($nameLine,0,1);
			if (is_numeric($firstChar)) continue;
			$nameLineArray = explode(" ",$nameLine);
			return $nameLineArray[0];
		}
		//Name with non-numeric first character not found
		return "";
	}

//http://rxnav.nlm.nih.gov/REST/RxTerms/rxcui/198440/name

	public function getDisplayNameFromRXCUI($rxcui, $medicationName) {
		$url = "http://rxnav.nlm.nih.gov/REST/RxTerms/rxcui/";
		$url.= $rxcui."/name";
		$result = file_get_contents($url);
		$dom = new DomDocument("1.0", "ISO-8859-1");
		$dom->loadXML($result);
		$items = $dom->getElementsByTagName('displayName');
		/*
		If no displayName is found, use the medicationName.  This should work because
		if no displayName is found, the medication should be generic and the nui search will
		return an nui
		*/

		if ($items->item(0)->nodeValue == null) {
			$displayName = $medicationName;
		}
		else {
			$displayName = $items->item(0)->nodeValue;
		}
		return $displayName;

	}

	public function brandNameSearch($con,$medicationName){
		$query = "select distinct genericName, STITCHMarked from label_mapping where brandName like '".$medicationName."%'";
		$result = mysqli_query($con,$query);
		$row = mysqli_fetch_array($result);
		$STITCHMarked = $row['STITCHMarked'];
		if ($STITCHMarked == "combination"){
			$this->isCombination = 1;
		}
		else {
			$this->isCombination = 0;
		}
		$this->componentName = $row['genericName'];
	}

	public function getComponents($medicationName){
		$query = "select componentName from combinations where brandName like '".$medicationName."%'";

		$result = mysqli_query($con,$query);
		$componentArray;
		$i=0;

		while(($row = mysqli_fetch_array($result)) !== FALSE){
			$componentArray[$i++] = $row['componentName'];
		}
		return $componentArray;
	}
	//conceptNui>7digits
	public function getRxcuid($medName){
			//$NUIURL = "http://rxnav.nlm.nih.gov/REST/Ndfrt/search?conceptName=";

			$NUIURL = "http://rxnav.nlm.nih.gov/REST/rxcui?name=";

			error_log("In getRxcuid, url for gettin nui is: ".$NUIURL.$medName);
			$thisNuiUrl = $NUIURL.urlencode($medName);


			$result = file_get_contents($thisNuiUrl);
			error_log("In getRxcuid, result is: ".$result);
			$index = strpos($result, "<rxnormId>");

			if ($index && $index !== false){
			  $rxcuid = $this->getRxcuidFromXML($result);
			  error_log("In getRxcuid, medname, rxcuid = ".$medName.", ".$rxcuid);
			  return $rxcuid;
			}
			$medExplode = explode(" ",$medName);
			if (count($medExplode) == 1) return null;
			$medName = $medExplode[0];

			$thisNuiUrl = $NUIURL.urlEncode($medName);
			$result = file_get_contents($thisNuiUrl);
			$index = strpos($result, "<rxnormId>");

			if ($index !== FALSE){
			  $rxcuid = $this->getRxcuidFromXML($result);
			  error_log("In getRxcuid, medname, rxcuid = ".$medName.", ".$rxcuid);

			  return $nuid;
			}
			error_log("In getRxcuid, no rxcuid found for medname ".$medName);
			return false;


	}

	private function getRxcuidFromXML($xml){
		error_log("In getRxcuidFromXML");
		$dom = new DomDocument("1.0", "ISO-8859-1");
		$dom->loadXML($xml);
		$elements = $dom->getElementsByTagName('rxnormId');

		$rxcuidValue = $elements->item(0)->nodeValue;
		error_log("In getRxcuidFromXML, rxcuidValue = ".$rxcuidValue);

		return $rxcuidValue;

	}
}
?>
