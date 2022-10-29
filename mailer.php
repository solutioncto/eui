/* Copyright Eric Baller and other contributors; Licensed MIT */

<?php
require_once('lib/swift/lib/swift_required.php');
require_once("db.php");

function myMailer($lead){

	if ( ! $lead ) return;

	// async processing
	$cmd="nohup /usr/bin/php report.php?lead={$lead} >/dev/null 2>/dev/null &";
	exec($cmd);
      
	$thankURL="placeholder.html";

	return($thankURL);
}

?>
