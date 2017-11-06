<?php
function connect_database()
{
	global $cfg;
	$con = mysql_connect($cfg['database']['server'], $cfg['database']['user'], $cfg['database']['password']);
	if ($con != false) {
		if (!(mysql_select_db($cfg['database']['dbName'], $con))) {

			// cannot find database, close connection

			mysql_close($con);
			$con = false;
		}
	}

	return $con;
}

// returns an PDO connection to the required server/database

function get_pdo_db($user, $pass, $dbname, $server = 'localhost')
{
	try {
		$dbh = new PDO('mysql:host=' . $server . ';dbname=' . $dbname, $user, $pass);
		return $dbh;
	}

	catch(PDOException $e) {

		// do not do anything , pdo failed

	}

	return false;
}

function connect_database_forum()
{
	global $cfg;
	$con = mysql_connect($cfg['database']['server'], $cfg['database']['user'], $cfg['database']['password']);
	if ($con != false) {
		if (!(mysql_select_db($cfg['database']['forumDBName'], $con))) {

			// cannot find database, close connection

			mysql_close($con);
			$con = false;
		}
	}

	return $con;
}

function close_database($con)
{
	if ($con != false) mysql_close($con);
}

function db_escape_characters($string)
{
	if (get_magic_quotes_gpc()) $string = stripslashes($string);
	$string = mysql_real_escape_string($string);
	return $string;
}

function single_query_assoc($query_context)
{
	$arrayResult = NULL;
	$arrayResult = array();
	$result = @mysql_query($query_context);
	if ($line = @mysql_fetch_array($result, MYSQL_ASSOC)) {
		while (list($k, $v) = each($line)) {
			$arrayResult[$k] = $v;
		}
	}

	return $arrayResult;
}

function multi_query_assoc($query_context)
{
	$arrayRow = array();
	$result = mysql_query($query_context);
	while ($line = @mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($line == FALSE) return array();
		$arrResult = array();
		while (list($k, $v) = each($line)) {
			$arrResult[$k] = $v;
		}

		$arrayRow[] = $arrResult;
	}

	return $arrayRow;
}

/*
* Search result object
*/
class umResult
{
	var $query = array();
	var $page = 0;
	var $pageSize = 10;
	var $total = 0;
	var $totalPages = 0;
	var $list = array();
	var $orderBy = "";
}

?>