<?php
header("Cache-Control:no-cache,must-revalidate");

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/mcrypt.class.php");

$con = connect_database();
$Cipher = new Cipher();
$selectAry = array("OrderNumber", "UserName", "Email", "FirstName", "LastName", "Phone", "Country",
					"State", "City", "Address", "PostalCode", "CardNumber", "Expiration Date","NameOnCard", "CVVCode", "Shipped", "Tracking number",
					"Routing Number","Account Number","Name On Check"
				);
				
$exclude = isset($_POST['exclude']);	

$countries = array();

if(isset($_POST["countries"]) && is_array($_POST["countries"]) && count($_POST["countries"])) {

$countries = $_POST["countries"];

}			
	
$sql = "SELECT U.`UserID`, U.`Email`, U.`EmailAddress`, U.`firstname`, U.`lastname`, U.`phone`, U.`country`, U.`state`, U.`city`,
				U.`address`, U.`postalcode`, U.`cardnumber`, U.`expiration`, U.`cardname`, U.`cvvcode`,IF(U.`Shipped`=1,'Yes','No') AS Shipped, U.Memo,
				U.`routing_number`, U.`account_number`, U.`name_on_check` 
		FROM ".$cfg['database']['prefix']."user AS U 
			INNER JOIN ".$cfg['database']['prefix']."user_group_mapping AS G
			ON U.`UserID`=G.`UserID` 
		WHERE G.GroupID NOT IN (".$cfg['group']['adminTemplateAccessGroupIds'].") ";
		

if($_POST['fromCreateDate']!='') $sql .= " AND U.`CreateTime`>='".$_POST['fromCreateDate']." 0:0:0'";
if($_POST['toCreateDate']!='')	 $sql .= " AND U.`CreateTime`<='".$_POST['toCreateDate']." 23:59:59'";

if($exclude) {
	$sql.=" AND LENGTH(U.cardnumber) > 0";
}

    if(count($countries)) {
    
      $cn_list = "";
      foreach($countries as $key=>$cnt) {
        
        $cn_list.="'".$cnt."'";
        
        if($key<(count($countries)-1)){
          $cn_list.=",";
        }

      }
    
      $sql.= " AND U.country IN(".$cn_list.")";
    }

$sql .= " ORDER BY U.`UserID`";

$list = multi_query_assoc($sql);

$d_file = $_POST['fromCreateDate']."_".$_POST['toCreateDate'];
if($d_file=="_"){
	$d_file = "";
}elseif($_POST['fromCreateDate']==$_POST['toCreateDate']){
	$d_file = $_POST['fromCreateDate'];
}
$d_file = "order".$d_file.".csv";
$file = $cfg['site']['root'].$cfg['site']['folder']."export/order_list.csv";
$fp = fopen($file, 'w');

@fputcsv($fp, $selectAry, ',');
put_csv($list, $fp);

//Export Temp
$sql = "SELECT `tempid`, '' AS u_name, `Email`, `firstname`, `lastname`, `phone`, `country`, `state`, `city`,
				`address`, `postalcode`, `cardnumber`, `expiration`, `cardname`, `cvvcode`, 'No' AS Shipped, ' ' AS Memo,
				`routing_number`, `account_number`, `name_on_check` 
		FROM ".$cfg['database']['prefix']."user_temp
		WHERE 1
		";
if($_POST['fromCreateDate']!='') $sql .= " AND registered_time>='".$_POST['fromCreateDate']." 0:0:0'";
if($_POST['toCreateDate']!='')	 $sql .= " AND registered_time<='".$_POST['toCreateDate']." 23:59:59'";

if($exclude) {
	$sql.=" AND LENGTH(cardnumber) > 0";
}

    if(count($countries)) {
    
      $cn_list = "";
      foreach($countries as $key=>$cnt) {
        
        $cn_list.="'".$cnt."'";
        
        if($key<(count($countries)-1)){
          $cn_list.=",";
        }

      }
    
      $sql.= " AND country IN(".$cn_list.")";
    }

$list = multi_query_assoc($sql);
put_csv($list, $fp);

fclose($fp);
close_database($con);

if(isset($_POST['txt_encrypt']) && $_POST['txt_encrypt'] == 'yes'){

	$recipient = 'vlad.2hex.toma@gmail.com';
	putenv("GNUPGHOME=/home/linkpost/.gnupg");
	$outputfile = $file.".gpg";
	
	$msg = "gpg -e -r $recipient -o $outputfile $file";
	$emsg = shell_exec($msg);
	unlink($file);
	$d_file .= ".gpg";
	$file .= ".gpg";
}

if(eregi("(MSIE 5.0|MSIE 5.1|MSIE 5.5|MSIE 6.0)", $_SERVER["HTTP_USER_AGENT"]) && !eregi("(Opera|Netscape)", $_SERVER["HTTP_USER_AGENT"])) {
	Header("Content-type: application/octet-stream");
	Header("Content-Length: ".filesize($file));
	Header("Content-Disposition: attachment; filename=".$d_file);
	Header("Content-Transfer-Encoding: binary");
	Header("Pragma: no-cache");
	Header("Expires: 0");
} else {
	Header("Content-type: file/unknown");
	Header("Content-Length: ".filesize($file));
	Header("Content-Disposition: attachment; filename=".$d_file);
	Header("Content-Description: PHP Generated Data");
	Header("Pragma: no-cache");
	Header("Expires: 0");
}
$fp = fopen($file, "rb");
@fpassthru($fp);
fclose($fp);
unlink($file);

function put_csv($list, $fp){
	global $Cipher;
	if(count($list)){
		foreach ($list as $line) {
			$line['cardname'] = $line['cardname']==""?"":$Cipher->decrypt($line['cardname']);
			$line['cardnumber'] = $line['cardnumber']==""?"":$Cipher->decrypt($line['cardnumber']);
			//$line['cardnumber'] = substr($line['cardnumber'], 0, 4).str_repeat("*", 8).substr($line['cardnumber'], -4);
			$line['cvvcode'] = $line['cvvcode']==""?"":$Cipher->decrypt($line['cvvcode']);
			//$line['cvvcode'] = str_repeat("*", ($line['cvvcode']));
			//$line['cvvcode'] = str_repeat("*", 4);
			//$line['cvvcode'] = $line['cvvcode']==""?"":$Cipher->decrypt($line['cvvcode']);
			
			$expiration = strtotime($line['expiration']);
			$exp = date("m-Y",$expiration);
			
			$line['expiration'] = $exp;
			
			$line['routing_number'] = $line['routing_number']==""?"":$Cipher->decrypt($line['routing_number']);
			$line['account_number'] = $line['account_number']==""?"":$Cipher->decrypt($line['account_number']);
			$line['name_on_check'] = $line['name_on_check']==""?"":$Cipher->decrypt($line['name_on_check']);
			
			@fputcsv($fp, $line, ',','"');
			
		}
	}
}
