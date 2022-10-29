<?php

// abbreviated database Information
if (file_exists("../local/db.php")) {
	require_once("../local/db.php");
} else {
	$db_host = "localhost"; //Host address (most likely localhost)
	$db_name = "TODO"; //Name of Database
	$db_user = "TODO"; //Name of database user
	$db_pass = "TODO"; //Password for database user
	$environment="production";
}

function pdoConnect(){
	global $db_host, $db_name, $db_user, $db_pass;
	$opt = array(
	  PDO::ATTR_EMULATE_PREPARES, false,
	  PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,
	  PDO::MYSQL_ATTR_FOUND_ROWS, true
	);
	try {  
	  $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass,$opt);
	  // $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	  // $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	  // $db->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS, true);
	  return $db;
	} catch(PDOException $e) {  
		return $e->getMessage();  
	}  
}

GLOBAL $errors;
GLOBAL $successes;

$errors = array();
$successes = array();

?>
