<?php
/*

UserFrosting Version: 0.1
By Alex Weissman
Copyright (c) 2014

Based on the UserCake user management system, v2.0.2.
Copyright (c) 2009-2012

UserFrosting, like UserCake, is 100% free and open-source.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the 'Software'), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

$log = fopen('oauth.log', 'a+');    
GLOBAL $log;
GLOBAL $environment;
GLOBAL $db_connection;

function addAlert($level,$msg) {
	global $log;
	$msg = "\n" . date("Ymd-H:i:s") . " " . $msg;
	if ($log === false) {
   		// echo $msg;
	} else {
		fwrite($log, $msg);
	}
}

//Database Information
if (file_exists(__DIR__."/../local/db.php")) {
	require_once(__DIR__."/../local/db.php");
	$environment="development";
} else {
	// error_reporting(E_ERROR);
	// error_reporting(E_ALL);  // messes with ajax JSON
	$db_host = "localhost"; //Host address (most likely localhost)
	$db_name = "TODO"; //Name of Database
	$db_user = "TODO"; //Name of database user
	$db_pass = "TODO"; //Password for database user
	$environment="production";
}
$db_table_prefix = "uc_";

function pdoConnect(){
	global $db_connection, $db_host, $db_name, $db_user, $db_pass;
	if(isset($db_connection)) {
		return $db_connection;
		}
	$host = "mysql:host=$db_host;dbname=$db_name";
	addAlert("info",$host);
	$opt = array(
	  PDO::ATTR_EMULATE_PREPARES, false,
	  PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,
	  PDO::MYSQL_ATTR_FOUND_ROWS, true
	);
	try {  
	  $db = new PDO($host, $db_user, $db_pass,$opt);
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
