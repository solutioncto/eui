/* Copyright Eric Baller and other contributors; Licensed MIT */

<?php 

function time_elapsed($log)
{
    static $last = null;

    $now = microtime(true);

    if ($last != null) {
		fprintf($log,"\n--- %f ---\n",($now - $last));
    }

    $last = $now;
}


function doe_setup($ch,$site,$username,$key,$log=NULL) {

		// debug
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		if(isset($log)) {
			curl_setopt($ch, CURLOPT_STDERR, $log);
			}

		$code = $username . ":" .  $key;
		$auth = "ApiKey " . $code;

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		'Content-Type: application/json',
    		'Connection: keep-alive',
			'Authorization: ' . $auth)
		);

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	// trust the SSL cert
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

fprintf($log,"\n- Site: ");
fwrite($log, $site);
	// open a connection
	$url = $site;

fprintf($log,"\n- URL: ");
fwrite($log, $url);
fflush($log);

	curl_setopt($ch, CURLOPT_URL, $url);

	$json = curl_exec($ch);

fprintf($log,"\n- json: --\n");
fwrite($log, print_r($json, TRUE));

	$result = json_decode($json, true);

fprintf($log,"\n- result: --\n");
fwrite($log, print_r($result, TRUE));
fprintf($log,"\n- OUT doe_setup --\n");
fflush($log);

	return($result);
}

function doe_count($ch,$site,$username,$key,$log) {

	$path='/api/v2/analyze/count';

	// request a token
	$url = $site . $path;

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$code = $username . ":" .  $key;
	$auth = "ApiKey " . $code;

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
   		'Content-Type: application/json',
   		'Connection: keep-alive',
		'Authorization: ' . $auth)
	);

	$postData = array(
		"recalculate" => "true",
		"filters" => array(
			"state" => array("NY"),
			"building_class" => array("Residential")
			)
	);
	fwrite($log, print_r($postData, TRUE));
	$json=json_encode($postData);
	fwrite($log, print_r($json, TRUE));

	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

	$json = curl_exec($ch);

	$result = json_decode($json, true);

	return($result);
}

function doe_data($state,$sqft,$ch,$site,$username,$key,$log) {
	$path='/api/v2/analyze/scatterplot';
	$url = $site . $path;

	// constraints on data
	$min=0;
	$max=$sqft*1.5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$code = $username . ":" .  $key;
	$auth = "ApiKey " . $code;

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
   		'Content-Type: application/json',
   		'Connection: keep-alive',
   		'Accept: */*',
   		'Accept-Encoding: gzip, deflate',
		'Authorization: ' . $auth)
	);

	$postData = array(
		"filters" => array(
			"floor_area" => array(
				"min" => $min,
				"max" => $max),
			"state" => array($state),
			"building_class" => array("Residential")
			),
		"additional_fields" => array(),
		"x-axis" => "floor_area",
		"y-axis" => "site_eui",
		"limit" => 1000
	);
	fwrite($log, print_r($postData, TRUE));
	$json=json_encode($postData);
	fwrite($log, print_r($json, TRUE));

	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

	$json = curl_exec($ch);
	$result = json_decode($json, true);
	return($result);
}

function doe_breakdown($state,$sqft,$ch,$site,$username,$key,$log) {
	$path='/api/v2/analyze/scatterplot';
	$url = $site . $path;

	// constraints on data
	$min=0;
	$max=$sqft*1.5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$code = $username . ":" .  $key;
	$auth = "ApiKey " . $code;

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
   		'Content-Type: application/json',
   		'Connection: keep-alive',
   		'Accept: */*',
   		'Accept-Encoding: gzip, deflate',
		'Authorization: ' . $auth)
	);

	$postData = array(
		"filters" => array(
			"floor_area" => array(
				"min" => $min,
				"max" => $max),
			"state" => array($state),
			"building_class" => array("Residential")
			),
		"additional_fields" => array("fuel_eui"),
		// "additional_fields" => array("climate"),
		"x-axis" => "floor_area",
		"y-axis" => "electric_eui",
		"limit" => 1000
	);
	fwrite($log, print_r($postData, TRUE));
	$json=json_encode($postData);
	fwrite($log, print_r($json, TRUE));

	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

	$json = curl_exec($ch);
	$result = json_decode($json, true);
	return($result);
}

function doe_by_zone($zone,$sqft,$ch,$site,$username,$key,$log) {
	$path='/api/v2/analyze/scatterplot';
	$url = $site . $path;

	// constraints on data
	$min=0;
	$max=$sqft*1.5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$code = $username . ":" .  $key;
	$auth = "ApiKey " . $code;

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
   		'Content-Type: application/json',
   		'Connection: keep-alive',
   		'Accept: */*',
   		'Accept-Encoding: gzip, deflate',
		'Authorization: ' . $auth)
	);

	$postData = array(
		"filters" => array(
			"floor_area" => array(
				"min" => $min,
				"max" => $max),
			"climate" => array($zone),
			"building_class" => array("Residential")
			),
		"additional_fields" => array("fuel_eui"),
		"x-axis" => "floor_area",
		"y-axis" => "electric_eui",
		"limit" => 1000
	);
	fwrite($log, print_r($postData, TRUE));
	$json=json_encode($postData);
	fwrite($log, print_r($json, TRUE));

	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

	$json = curl_exec($ch);
	$result = json_decode($json, true);
	return($result);
}

function doe_electric($state,$sqft,$ch,$site,$username,$key,$log) {

	$path='/api/v2/analyze/scatterplot';
	$url = $site . $path;

	// constraints on data
	$min=0;
	$max=$sqft*1.5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$code = $username . ":" .  $key;
	$auth = "ApiKey " . $code;

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
   		'Content-Type: application/json',
   		'Connection: keep-alive',
   		'Accept: */*',
   		'Accept-Encoding: gzip, deflate',
		'Authorization: ' . $auth)
	);

	$postData = array(
		"filters" => array(
			"floor_area" => array(
				"min" => $min,
				"max" => $max),
			"fuel_eui" => array(
				"min" => "0",
				"max" => "10"),
			"state" => array($state),
			"building_class" => array("Residential")
			),
		"additional_fields" => array(),
		"x-axis" => "floor_area",
		"y-axis" => "site_eui",
		"limit" => 1000
	);
	fwrite($log, print_r($postData, TRUE));
	$json=json_encode($postData);
	fwrite($log, print_r($json, TRUE));

	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

	$json = curl_exec($ch);

	$result = json_decode($json, true);

	return($result);
}

function doe_numerical($ch,$site,$log) {

	$path='/api/v2/introspection/fields?field_type=numerical';

	// request a token
	$url = $site . $path;

	curl_setopt($ch, CURLOPT_URL, $url);

	$json = curl_exec($ch);

	$result = json_decode($json, true);

	return($result);
}

function doe_categorical($ch,$site,$log) {

	$path='/api/v2/introspection/fields?field_type=categorical';

	// request a token
	$url = $site . $path;

	curl_setopt($ch, CURLOPT_URL, $url);

	$json = curl_exec($ch);

	$result = json_decode($json, true);

	return($result);
}

?>
