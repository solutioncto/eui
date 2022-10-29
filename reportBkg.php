<?php
require_once("db-settings.php");
require_once("reportGen.php");

if (isset($argv[1])) {
	reportGen($argv[1]);
	reportSend($argv[1]);
	}
?>
