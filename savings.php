/* Copyright Eric Baller and other contributors; Licensed MIT */

<?php
require_once("functions.php");
require_once("mailer.php");

$log = logPHP('savings.php');

	header("Content-Type: application/json", true);

	$step = filter_var($_POST["step"],FILTER_SANITIZE_STRING);
	$respArray = array();
	$lead=array();
// $headers = apache_request_headers();
// fprintf($log,"\nheaders=%s\n",json_encode($headers));
// fprintf($log,"\nSERVER=%s\n",json_encode($_SERVER));
fprintf($log,"\nPOST=%s\n",json_encode($_POST));
	foreach($_POST as $key => $value) {
		switch ($key) {
			case 'sqft':
			case 'gas':
			case 'electric':
			case 'btu':
				$lead[$key] = (int)$value;
				$respArray[$key] = $lead[$key];
				break;
			default:
				$lead[$key]=filter_var($value,FILTER_SANITIZE_STRING);
				$respArray[$key] = $lead[$key];
			}
		}
    if(!isset($lead["source"]) ) {
		$lead['source'] = "empty";
		}

fprintf($log,"\nlead[]=%s\n",json_encode($lead));
	switch ($step) {

	case '1':	// initial page

		$lead['codename'] = generateKey(5, 'accounts');
		$respArray['codename'] = $lead['codename'];
		$respArray['id'] = addLead('tbrdg', $lead);
		if(isset($lead["zip"])) {
			$respArray['zone'] = getZipZone($lead['zip']);
			}
		break;

	case '1a':	// recalculate existing inputs, do not increment step
		if(isset($lead["codename"])) {
			$respArray['codename'] = $lead['codename'];
			$respArray["id"] = addLead('tbrdg', $lead);
			}
		if(isset($lead["zip"])) {
			$respArray['zone'] = getZipZone($lead['zip']);
			}
		break;

	case '0':	// final email submit button
		if(isset($lead["codename"])) {
			$respArray['codename'] = $lead['codename'];
			if ( ! addLead('tbrdg', $lead) ) {
				echo "save_failed";
				fprintf($log,"\nSAVE FAILED\n");
 				return; 
				}
			}

		if(isset($lead["email"])) {
			$codename = $lead['codename'];
			$cmd="nohup /usr/bin/php reportBkg.php {$codename} 2>&1";
fprintf($log,"\ncmd=%s\n",$cmd);
			$out=array();
			exec($cmd,$out);
fwrite($log, print_r($out, TRUE));
			$thankURL="placeholder.html";
			$respArray["thanks"]=$thankURL;
			}
fprintf($log,"\nCASE1 respArray=%s\n",json_encode($respArray));

		break;

	case '-1':	// page impression
		$lead=array();
		$lead['zip']=0;
		$lead['sqft']=0;
		$lead['electric']=0;
		$lead['fuel']=0;
		$lead['btu']=0;
		$lead['gas']=0;
		$lead['source']=0;
		if ( ! saveLead('tbrdg', $lead) ) {
			echo "save_failed";
			fprintf($log,"\nSAVE FAILED\n");
 			return; 
			}
		break;

	default:
fprintf($log,"\nDEFAULT lead=%s\n",json_encode($lead));
		if(isset($lead['codename'])) {
			$respArray['codename'] = $lead['codename'];
			$respArray['zone'] = getZipZone($lead['zip']);
			$respArray['id'] = addLead('tbrdg', $lead);
// source?
// store all available fields in respArray for storage in html form
			}
fprintf($log,"\nCASE234 respArray=%s\n",json_encode($respArray));
		break;

	}

	echo json_encode($respArray);
	return;

?>
