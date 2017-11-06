<?php

if(!isset($_GET["custom_pass"])) die("error 1");

if($_GET["custom_pass"]!="3ffvsdbbrt4SSFG") die("error 2");

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/PayFrontEnd.php");

$f = new PayFrontEnd;

$sql = "SELECT u.UserID AS orderno, u.EmailAddress, u.country, u.CreateTime, h.hAmount, h.transaction_id
FROM  `mem_user` u
LEFT JOIN mem_merchant_history h ON u.EmailAddress = h.user_email
WHERE u.user_ip =  '180.194.247.246'
ORDER BY UserID DESC";

$res = mysql_query($sql);

while($row = mysql_fetch_object($res)) {
  
}