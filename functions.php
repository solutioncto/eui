/* Copyright Eric Baller and other contributors; Licensed MIT */

<?php 
require_once("db.php");

function logPHP($msg,$fp=null) {
	
	if (!$fp) {
		$fp = fopen('php.log', 'a+');    
		}

	$msg = "\n -- opening log -- " . date("Y-m-d H:i:s") . " --";
	fprintf($fp,$msg);
	return $fp;
}

// insert details to ACCOUNTS table
function addLead($client, $lead) {

  try {
    $db = pdoConnect();
      
	$stmt = $db->prepare("INSERT accounts
			(account, client, zip, sqft, electric, fuel, gas, btu, provider, codename, ip,
				firstname, elecRate, fuelRate, finRate, finTerm, hers, estimated) 
			VALUES (:email, :client, :zip, :sqft, :electric, :fuel, :gas, :btu, :source, :codename, :ip, :firstname, 
				:elecRate, :fuelRate, :finRate, :finTerm, :hers, :estimated) ");
	$sqlVars[':client'] = $client;
	$sqlVars[':zip'] = isset($lead['zip']) ? $lead['zip'] : "";
	$sqlVars[':fuel'] = isset($lead['fuel']) ? $lead['fuel'] : "";
	$sqlVars[':source'] = isset($lead['source']) ? $lead['source'] : "";
	$sqlVars[':codename'] = isset($lead['codename']) ? $lead['codename'] : "";
	$sqlVars[':ip'] = isset($lead['ip']) ? $lead['ip'] : "";
	$sqlVars[':email'] = isset($lead['email']) ? $lead['email'] : "";		// into account
	$sqlVars[':firstname'] = isset($lead['first']) ? $lead['first'] : "";	// not firstname

	$sqlVars[':sqft'] = isset($lead['sqft']) ? $lead['sqft'] : 0;
	$sqlVars[':electric'] = isset($lead['electric']) ? $lead['electric'] : 0;
	$sqlVars[':gas'] = isset($lead['gas']) ? $lead['gas'] : 0;
	$sqlVars[':btu'] = isset($lead['btu']) ? $lead['btu'] : 0;
	$sqlVars[':elecRate'] = isset($lead['elecRate']) ? $lead['elecRate'] : 0.0;
	$sqlVars[':fuelRate'] = isset($lead['fuelRate']) ? $lead['fuelRate'] : 0.0;
	$sqlVars[':finRate'] = isset($lead['finRate']) ? $lead['finRate'] : 0.0;
	$sqlVars[':finTerm'] = isset($lead['finTerm']) ? $lead['finTerm'] : 0.0;
	
	$sqlVars[':hers'] = isset($lead['hers']) ? $lead['hers'] : -1;
	$sqlVars[':estimated'] = isset($lead['estimated']) ? $lead['estimated'] : true;
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
	}

	$lastId = $db->lastInsertId();
    $stmt = null;
	$result=$lastId;	// return insert id

  } catch (PDOException $e) {
      // addAlert("danger", "Oops, looks like our database encountered an error.(f01)");
      $result = 0;
      error_log($e->getMessage());
  } catch (ErrorException $e) {
      // addAlert("danger", "Oops, looks like our server goofed.  If you're an admin, please check the PHP error logs.(sl02)");
      $result = 0;
      error_log($e->getMessage());
  } catch (RuntimeException $e) {
      // addAlert("danger", "Oops, looks like our server goofed.  If you're an admin, please check the PHP error logs.(sl03)");
      $result = 0;
      error_log($e->getMessage());
  }

	return $result;
}

function saveLead($client, $lead) {

  try {
    $db = pdoConnect();
      
	$stmt = $db->prepare("INSERT accounts (client, zip, sqft, electric, fuel, gas, btu, provider) 
			VALUES (:client, :zip, :sqft, :electric, :fuel, :gas, :btu, :source) ");
	$sqlVars[':client'] = $client;
	$sqlVars[':zip'] = $lead['zip'];
	$sqlVars[':sqft'] = $lead['sqft'];
	$sqlVars[':electric'] = $lead['electric'];
	$sqlVars[':fuel'] = $lead['fuel'];
	$sqlVars[':gas'] = $lead['gas'];
	$sqlVars[':btu'] = $lead['btu'];
	$sqlVars[':source'] = $lead['source'];
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
	}

	$lastId = $db->lastInsertId();
    $stmt = null;
	$result=$lastId;	// return insert id

  } catch (PDOException $e) {
      // addAlert("danger", "Oops, looks like our database encountered an error.(f02)");
      $result = 0;
      error_log($e->getMessage());
  } catch (ErrorException $e) {
      // addAlert("danger", "Oops, our server might have goofed.  If you're an admin, please check the PHP error logs.(sl02)");
      $result = 0;
      error_log($e->getMessage());
  } catch (RuntimeException $e) {
      // addAlert("danger", "Oops, our server might have goofed.  If you're an admin, please check the PHP error logs.(sl03)");
      $result = 0;
      error_log($e->getMessage());
  }

	return $result;
}

function updateLead($id, $lead) {

  try {
    $db = pdoConnect();
      
	$stmt = $db->prepare("UPDATE accounts set account = :email, sqft = :sqft,
			electric = :electric, gas = :gas, btu = :btu
			where id = :id ");
	$sqlVars[':id'] = $id;
	$sqlVars[':email'] = $lead['email'];
	$sqlVars[':sqft'] = $lead['sqft'];
	$sqlVars[':electric'] = $lead['electric'];
	$sqlVars[':gas'] = $lead['gas'];
	$sqlVars[':btu'] = $lead['btu'];
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
	}

    $stmt = null;
	$result=$id;	// return insert id

  } catch (PDOException $e) {
      // addAlert("danger", "Oops, looks like our database encountered an error.(f03)");
      $result = 0;
      error_log($e->getMessage());
  } catch (ErrorException $e) {
      // addAlert("danger", "Oops, our server might have goofed.  If you're an admin, please check the PHP error logs.(sl02)");
      $result = 0;
      error_log($e->getMessage());
  } catch (RuntimeException $e) {
      // addAlert("danger", "Oops, our server might have goofed.  If you're an admin, please check the PHP error logs.(sl03)");
      $result = 0;
      error_log($e->getMessage());
  }

	return $result;
}

//Generate an activation key and optionally test for uniqueness
function generateKey($length = 5, $table = null)
{
	$alphabet="abcdefghijkmnopqrstuvwxyz23456789"; // removed l01, length=33
	if (is_null($table)) {
		$gen="";
		for($i=0;$i<$length;$i++) {
			$gen .= $alphabet[mt_rand(0,32)];
			}
		} else {
		do {
			$gen="";
			for($i=0;$i<$length;$i++) {
				$gen .= $alphabet[mt_rand(0,32)];
				}
			} while(existsKey($gen,$table));	// if exists already, create a new one
		}
	return $gen;
}

// check key for uniqueness. Return false if not found (often a good thing)
function existsKey($key,$table)
{
    $db = pdoConnect();

	if (strlen($key)<1) {
		// $warnings[] = "Empty key";
		return false;	
		}

	// $warnings[] = "Validating {$key} against {$table}.";
	try {
		switch ($table) {
			case 'accounts':
				$stmt = $db->prepare("SELECT id FROM accounts where codename = ?");
				break;
			}
		$stmt->execute(array($key));
		$num_returns = $stmt->rowCount();
	} catch(PDOException $ex) {
		$errors[] = $ex->getMessage();
		return false;
	}

	if ($num_returns > 0)
	{
		return $key;
	}
	else
	{
		return false;	
	}
}

// get census region for a zip code
function getRegion($zip) {

	$result=array();
    $db = pdoConnect();
      
	$stmt = $db->prepare("SELECT region FROM zipcodes JOIN regions USING (city_state) where city_zip = :zip");
	$sqlVars[':zip'] = $zip;
	
	if (!$stmt->execute($sqlVars)){
		$errors[] = lang("SQL_ERROR");
		return false;
	} else if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))){
        // addAlert("danger", "Invalid zip specified");
		$errors[] = "Invalid ZIP";
		return false;
	}

	$answer = isset($result['region']) ? $result['region'] : false;
	return($answer);
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
?>
