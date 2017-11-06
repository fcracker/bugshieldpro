<?php
header("Cache-Control:no-cache,must-revalidate");

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/mcrypt.class.php");

define ("DETAIL_2", '9780672319648');

$con = connect_database();


$file = "order_list.csv";
$fp = fopen($file, 'w');

if(isset($_POST['shipped'])) $shipped = $_POST['shipped']; else $shipped=array();
if(isset($_POST['unshipped'])) $unshipped = $_POST['unshipped']; else $unshipped=array();
$carriedcode = $_POST['carriedcode'];

$sql = "SELECT * FROM ".$cfg['database']['prefix']."user WHERE UserID IN(".implode(",", array_merge($shipped, $unshipped)).")";
$rows = multi_query_assoc($sql);
foreach($rows as $row){
	$orderID = $row['UserID'];
	$orderDate = preg_split("/\D/", $row['CreateTime']);
	$orderDate = $orderDate[1]."/".$orderDate[2]."/".$orderDate[0];
	$data = array(
				"H",										// 1
				$orderID,									// 2
				$cfg['pssc']['publishid'],					// 3
				$carriedcode[$orderID],						// 4
				"SHDOME",									// 5
				$orderDate,									// 6
				str_replace('"', '""', $row['firstname']." ".$row['lastname']),		// 7
				str_replace('"', '""', $row['address']),							// 8
				"",											// 9
				"",											// 10
				"",											// 11
				str_replace('"', '""', $row['city']),		// 12
				str_replace('"', '""', $row['state']),		// 13
				$row['postalcode'],							// 14
				str_replace('"', '""', $row['country']),	// 15
				"",											// 16
				"",											// 17
				""											// 18
			);
	fwrite($fp, getCsvLineString($data));
	$data = array(
				"D",										// 1
				'9780672319648',							// 2 
				'1',										// 3 
				$orderID									// 4 
			);
	fwrite($fp, getCsvLineString($data));
}
fclose($fp);
close_database($con);

if(eregi("(MSIE 5.0|MSIE 5.1|MSIE 5.5|MSIE 6.0)", $_SERVER["HTTP_USER_AGENT"]) && !eregi("(Opera|Netscape)", $_SERVER["HTTP_USER_AGENT"])) {
	header("Content-type: application/octet-stream");
	header("Content-Length: ".filesize($file));
	header("Content-Disposition: attachment; filename=".$file);
	header("Content-Transfer-Encoding: binary");
	header("Pragma: no-cache");
	header("Expires: 0");
} else {
	header("Content-type: file/unknown");
	header("Content-Length: ".filesize($file));
	header("Content-Disposition: attachment; filename=".$file);
	header("Content-Description: PHP3 Generated Data");
	header("Pragma: no-cache");
	header("Expires: 0");
}
$fp = fopen($file, "rb");
@fpassthru($fp);
fclose($fp);
unlink($file);

function getCsvLineString($data){
	$str = '"'.implode('","', $data).'"';
	$str .= "\r\n";
	return $str;
}