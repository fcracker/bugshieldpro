<?php
header("Cache-Control:no-cache,must-revalidate");
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");

$con = connect_database();
$sql = "SELECT email FROM ".$cfg['database']['prefix']."unsubscribe Where exportdate='0000-00-00'";
$list = multi_query_assoc($sql);
if (count($list) == 0){
	redirect("unscribe.php");
}
$file = "file".date("Ymd").".csv";
$fp = fopen($file, 'w');

foreach ($list as $line) {
	@fputcsv($fp, $line, ',');
}
fclose($fp);
$date = date("Y-m-d");
$sql = "Update ".$cfg['database']['prefix']."unsubscribe Set exportdate='$date' Where exportdate='0000-00-00'";
mysql_query($sql);
close_database($con);
