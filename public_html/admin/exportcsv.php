<?php
header("Cache-Control:no-cache,must-revalidate");

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/mcrypt.class.php");
include_once("../lib/user.class.php");
include_once("../lib/protect.class.php");

$con = connect_database();
$user = new umUser();
$user->get_session();
if($user->userID != 0 && $user->get_user()){
}else{
	redirect($cfg['site']['folder']."login1.php");
}




$con = connect_database();
$Cipher = new Cipher();
$selectAry = array("Order Number", "User Name", "Email", "First Name", "Last Name", "Phone", "Country",
					"State", "City", "Address", "Postal Code", "Card Type", "Card Number", "Card Name", "CVV", "Shipped"
				);
$sql = "SELECT `UserID`,`Email`,`EmailAddress`,`firstname`,`lastname`,`phone`,`country`,`state`,`city`,`address`,`postalcode`,`cardtype`,
				`cardnumber`,`cardname`,`cvvcode`,IF(Shipped=1,'Yes','No') AS `Shipped`
		FROM ".$cfg['database']['prefix']."user WHERE 1";

if($_POST['fromCreateDate']!='') $sql .= " AND `CreateTime`>='".$_POST['fromCreateDate']." 0:0:0'";
if($_POST['toCreateDate']!='')	 $sql .= " AND `CreateTime`<='".$_POST['toCreateDate']." 23:59:59'";
$sql .= " ORDER BY UserID";

$list = multi_query_assoc($sql);
$file = $_POST['fromCreateDate']."~".$_POST['toCreateDate'];
if($file=="~") $file = "";
else $file = str_replace("-", "." , $file);
$file = "backup/order_list ".$file.".csv";
$fp = fopen($file, 'w');

@fputcsv($fp, $selectAry, ',');
foreach ($list as $line) {
	$line['cardnumber'] = $line['cardnumber']==""?"":$Cipher->decrypt($line['cardnumber']);
	$line['cardname'] = $line['cardname']==""?"":$Cipher->decrypt($line['cardname']);
	$line['cvvcode'] = $line['cvvcode']==""?"":$Cipher->decrypt($line['cvvcode']);
	@fputcsv($fp, $line, ',');
}

fclose($fp);
close_database($con);

if(isset($_POST['txt_encrypt']) && $_POST['txt_encrypt'] != ''){
	$recipient = 'ak.trifecta@gmail.com';
	putenv("GNUPGHOME=/home/kgi819/.gnupg");
	$planfile = '/home/kgi819/public_html/admin/'.$file;
	$outputfile = $planfile.".gpg";
	$msg = "gpg -e -r $recipient -o $outputfile $planfile";
	//$msg = "gpg --list-keys $recipient";
	$emsg = shell_exec($msg);
	// echo $msg."<br/>";
	// echo $emsg;
	// if(file_exists($outputfile)){
		// echo "Success";
	// }else{
		// echo "Faild";
	// }
	
	
	unlink($file);
	$file .= ".gpg";
}
header("location:".$cfg['site']['url'].$cfg['site']['folder']."admin/".$file);