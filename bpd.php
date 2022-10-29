/* Copyright Eric Baller and other contributors; Licensed MIT */

<?php 
require_once 'db-settings.php';

function getLead($codename) {
    $db = pdoConnect();
      
	$stmt = $db->prepare("SELECT * FROM accounts where codename = :codename AND zip is not NULL order by ts DESC limit 1");
	$sqlVars[':codename'] = $codename;
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
		$results=false;
	} else if (!($results = $stmt->fetch(PDO::FETCH_ASSOC))){
        addAlert("danger", "Invalid account id specified");
         $results = array("errors" => 1, "successes" => 0);
	}

	return($results);
}

function getEmail($lead) {
    $db = pdoConnect();
      
	$stmt = $db->prepare("SELECT * FROM accounts where codename = :codename order by ts DESC limit 1");
	$sqlVars[':codename'] = $lead['codename'];
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
		return false;
	} else if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))){
        addAlert("danger", "Invalid account id specified");
		return false;
	}

	return($result['account']);
	

}

function getEuiArray($lead) {

	$msg="getEuiArray: ";
	$result=array();
	if (isset($lead['eui']) AND is_numeric($lead['eui'])) {
		$result['eui'] = $lead['eui'];
		$msg .= " Found {$result['eui']} as EUI";
		} else if( isset($lead['sqft']) && $lead['sqft']>1 ) {

		$result['fuel']=$lead['btu']/1000/$lead['sqft'];	// 1000BTU/sqft
		$msg .= " {$lead['fuel']} EUI = {$result['fuel']}.";

		$kwh = isset($lead['electric']) ? $lead['electric'] : 0;
		$result['elec']=$kwh * 3412 / 1000 / $lead['sqft'];
		$msg .= " Electric EUI = {$result['elec']}.";

		$result['eui']=$result['elec'] + $result['fuel'];
		$msg .= " Calculated {$result['eui']} as EUI";

		} else {

		$msg = "Unable to calculate EUI";
		$result=0;

		}

    addAlert("info", $msg);
	return($result);
}

function getState($lead) {

	if( !isset($lead['zip']) ) {
		return false;
		}

    $db = pdoConnect();
      
	$stmt = $db->prepare("SELECT city_state FROM zipcodes where city_zip = :zip");
	$sqlVars[':zip'] = $lead['zip'];
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
		return false;
	} else if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))){
        addAlert("danger", "Invalid account id specified");
		return false;
	}

	return($result['city_state']);
	
}

function getLeadZone($lead) {

	if( !isset($lead['zip']) ) {
		return false;
		}

    $db = pdoConnect();
      
	$stmt = $db->prepare("SELECT zone FROM zones where zip = :zip");
	$sqlVars[':zip'] = $lead['zip'];
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
		return false;
	} else if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))){
        addAlert("danger", "Invalid account id specified");
		return false;
	}

	return($result['zone']);
	
}

// get climate zone for a zip code (currently ignores DOE moisture regime)
function getZipZone($zip) {

	$result=array();
    $db = pdoConnect();
      
	$stmt = $db->prepare("SELECT zone, moisture FROM zones where zip = :zip");
	$sqlVars[':zip'] = $zip;
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
		return 0;
	} else if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))){
        // addAlert("danger", "Invalid zip specified");
		$errors[] = "Invalid ZIP";
		return 0;
	}

	$answer = isset($result['zone']) ? $result['zone'] : 0;
	return($answer);
}

function getDoeZone($lead) {

	if( !isset($lead['zip']) ) {
		return false;
		}

    $db = pdoConnect();
      
	$stmt = $db->prepare("select doe from zones_doe d LEFT JOIN zones z ON (d.zone=z.zone AND d.moisture=z.moisture) where zip = :zip");
	$sqlVars[':zip'] = $lead['zip'];
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
		return false;
	} else if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))){
        addAlert("danger", "Invalid account id specified");
		return false;
	}

	return($result['doe']);
	
}

// get city, state for a ZIP
function getCity($zip) {

	$result=array();
    $db = pdoConnect();
      
	$stmt = $db->prepare("SELECT city_name, city_state FROM zipcodes where city_zip = :zip");
	$sqlVars[':zip'] = $zip;
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
		return false;
	} else if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))){
        // addAlert("danger", "Invalid zip specified");
		$errors[] = "Invalid ZIP";
		return false;
	}

	$answer = $result['city_name'] . ", " . $result['city_state'];
	return($answer);
}

function apiCall($api, $params) {
	global $errors;
	$limit=array();
	$limit['BPD']=1800;
	$limit['BPx']=100;
/**
CREATE TABLE `api_calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api` char(3) NOT NULL,
  `monthcode` char(6) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `params` varchar(50) DEFAULT NULL,
**/


  try {
    $db = pdoConnect();
      
	$month=date("Ym");
	$stmt = $db->prepare("INSERT api_calls (api, monthcode, params) 
			VALUES (:api,:month, :params) ");
	$sqlVars[':api'] = $api;
	$sqlVars[':month'] = $month;
	$sqlVars[':params'] = $params;
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
	}

	$stmt = $db->prepare("SELECT count(*) FROM api_calls where api=:api and monthcode=:month");
	unset($sqlVars[':params']);
	$result = $stmt->execute($sqlVars);
	if (!$result){
		$errors[] = lang("SQL_ERROR");
		$result=false;
	} else {
		$num = (int)$stmt->fetchColumn(); 
		$result = isset($limit[$api]) ? ($num>$limit[$api] ? false : true) : false;
	}

	if ($result) {
		$msg = "Returned {$num} TRUE";
		} else {
		$msg = "Returned {$num} FALSE";
		}
    addAlert("info", $msg);

  } catch (PDOException $e) {
      addAlert("danger", "Oops, looks like our database encountered an error.(bpd01)");
      $result = 0;
      // error_log($e->getMessage());
  } catch (ErrorException $e) {
      addAlert("danger", "Oops, looks like our server might have goofed.  If you're an admin, please check the PHP error logs.(bpd02)");
      $result = 0;
      // error_log($e->getMessage());
  } catch (RuntimeException $e) {
      addAlert("danger", "Oops, looks like our server might have goofed.  If you're an admin, please check the PHP error logs.(bpd03)");
      $result = 0;
      // error_log($e->getMessage());
  }

	return $result;
}

function bpdStub($state,$sqft,$log) {
	fprintf($log,"\n----- bpdStub in bpd.php ----");
	$param = "{$state}-{$sqft}";
	if (!apiCall('BPx',$param)) return false;	// exceeded API calls

	$json_data = file_get_contents('bpd.txt');
	$result=json_decode($json_data, true);
	return($result);
}

function bpd($state,$sqft,$log) {
require_once 'include.php';
require_once 'oauth.php';

try {
	fprintf($log,"\n----- bpd.php ----");
	$param = "{$state}-{$sqft}";
	if (!apiCall('BPD',$param)) return false;	// exceeded API calls

	fprintf($log,"\n-- REQUEST --\n");
	fwrite($log, print_r($_REQUEST, TRUE));

	// initialization
time_elapsed($log);

	$scatterplot=array();
	$totals=array();
	$metadata=array();

	$ch = curl_init();

	doe_setup($ch,$site,$username,$key,$log);
	fprintf($log,"\n-- setup complete  -\n");

	time_elapsed($log);
	fwrite($log, "doe_breakdown");
	$result = doe_breakdown($state,$sqft,$ch,$site,$username,$key,$log);
/* include for debug ***/
	fprintf($log,"\n-- doe_breakdown result --\n");
	$scatterplot=$result["scatterplot"];
	$totals=$result["totals"];
	$metadata=$result["metadata"];
	fprintf($log,"\n-- totals --\n");
	fwrite($log, print_r($totals, TRUE));
	$statement="No data found?";
	$found = (int)$totals["number_of_matching_buildings"];
	$total = (int)$totals["number_of_buildings_in_bpd"];
	if( $found >= 1000 ) {
		$statement="Using 1000 random entries of the ".$totals['number_of_matching_buildings']." matching buildings found in the database of ".$totals['number_of_buildings_in_bpd'].".";
		} else {
		$statement="Using the {$found} matching buildings from the database of {$total}.";
		}

	$q=array();
    $q[]=$totals["percentile_25"][0];
    $q[]=$totals["percentile_50"][0];
    $q[]=$totals["percentile_75"][0];
    $q[]=$totals["percentile_100"][0];
	fprintf($log,"\n-- analysis --\n");
	fwrite($log, $statement);
	fwrite($log, print_r($q, TRUE));
	fprintf($log,"\n-- metadata --\n");
	fwrite($log, print_r($metadata, TRUE));
	fprintf($log,"\n-- scatterplot --\n");
	fwrite($log, print_r($scatterplot, TRUE));
/*** end debug */

} catch( Exception $e) {
  addAlert("danger", "DOE BPD connection error.");
  fwrite($log,$e->getMessage());
}

/*
	$response = "[";
	for($i=0;$i<$found;$i++) {
		if ($i > 10) break;
		$x=$scatterplot[$i][0];
		$y=$scatterplot[$i][1]+$scatterplot[$i][2];
		$response.= "[{$x},{$y}]";
		}
	$response.= "]";
*/

	return($result);
}

function bpdZone($zone,$sqft,$log) {
require_once 'include.php';
require_once 'oauth.php';

try {
	fprintf($log,"\n----- bpd.php bpdZone ----");
	$param = "{$zone}-{$sqft}";
	if (!apiCall('BPD',$param)) return false;	// exceeded API calls

	fprintf($log,"\n-- REQUEST --\n");
	fwrite($log, print_r($_REQUEST, TRUE));

	// initialization
time_elapsed($log);

	$scatterplot=array();
	$totals=array();
	$metadata=array();

	$ch = curl_init();

	doe_setup($ch,$site,$username,$key,$log);
	fprintf($log,"\n-- setup complete  -\n");

	time_elapsed($log);
	fwrite($log, "doe_by_zone");
	$result = doe_by_zone($zone,$sqft,$ch,$site,$username,$key,$log);
/* include for debug ***/
	fprintf($log,"\n-- doe_by_zone result --\n");
	$scatterplot=$result["scatterplot"];
	$totals=$result["totals"];
	$metadata=$result["metadata"];
	fprintf($log,"\n-- totals --\n");
	fwrite($log, print_r($totals, TRUE));
	$statement="No data found?";
	$found = (int)$totals["number_of_matching_buildings"];
	$total = (int)$totals["number_of_buildings_in_bpd"];
	if( $found >= 1000 ) {
		$statement="Using 1000 random entries of the ".$totals['number_of_matching_buildings']." matching buildings found in the database of ".$totals['number_of_buildings_in_bpd'].".";
		} else {
		$statement="Using the {$found} matching buildings from the database of {$total}.";
		}

/***
	$q=array();
	$q['sqft']=array();
	$q['elec']=array();
	$q['fuel']=array();
    $q['sqft'][0]=$totals["percentile_0"][0];
    $q['elec'][0]=$totals["percentile_0"][1];
    $q['fuel'][0]=$totals["percentile_0"][2];
    $q['sqft'][25]=$totals["percentile_25"][0];
    $q['elec'][25]=$totals["percentile_25"][1];
    $q['fuel'][25]=$totals["percentile_25"][2];
    $q['sqft'][50]=$totals["percentile_50"][0];
    $q['elec'][50]=$totals["percentile_50"][1];
    $q['fuel'][50]=$totals["percentile_50"][2];
    $q['sqft'][75]=$totals["percentile_75"][0];
    $q['elec'][75]=$totals["percentile_75"][1];
    $q['fuel'][75]=$totals["percentile_75"][2];
    $q['sqft'][100]=$totals["percentile_100"][0];
    $q['elec'][100]=$totals["percentile_100"][1];
    $q['fuel'][100]=$totals["percentile_100"][2];
	fprintf($log,"\n-- analysis --\n");
	fwrite($log, $statement);
	fwrite($log, print_r($q, TRUE));
***/
	fprintf($log,"\n-- metadata --\n");
	fwrite($log, print_r($metadata, TRUE));
	fprintf($log,"\n-- scatterplot --\n");
	fwrite($log, print_r($scatterplot, TRUE));
/*** end debug */

} catch( Exception $e) {
  addAlert("danger", "DOE BPD connection error.");
  fwrite($log,$e->getMessage());
}

/*
	$response = "[";
	for($i=0;$i<$found;$i++) {
		if ($i > 10) break;
		$x=$scatterplot[$i][0];
		$y=$scatterplot[$i][1]+$scatterplot[$i][2];
		$response.= "[{$x},{$y}]";
		}
	$response.= "]";
*/

	return($result);
}
?>
