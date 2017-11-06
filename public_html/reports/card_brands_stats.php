<?php

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/conversions.php");
include_once("../lib/user.class.php");
include_once("../lib/helper_functions.php");

include_once("../lib/order.class.php");
include_once("../lib/PayBackEnd.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

//get min/max
$min = date("Y-m-1");
$max = date("Y-m-t");

$view_params = array(
	"period"  =>  $min.":".$max,
	"from_date"	=> $min,
	"to_date"	=> $max,


);

$payback = new PayBackEnd;
$merchants = $payback->get_merchant();

$merc = array();

//$merchants = array_slice($merchants,0,1);

foreach($merchants as $merchant) {

    echo "grabbing stats for ".$merchant["BankName"];
    
    $sql = "SELECT h.hKey,h.hAmount as amnt,u.cardnumber FROM mem_merchant_history h LEFT JOIN mem_user u ON h.user_email=u.Email WHERE h.BankID='".$merchant["BankID"]."' AND h.hDate BETWEEN '".$view_params["from_date"]."' AND '".$view_params["to_date"]." 23:59:59'";
    
    $transactions = multi_query_assoc($sql);
      
    echo "<br /> ". $sql."<br />";
    
    $total_per_brands = array();
    
    $card_brands = array();
    

    foreach($transactions as $transaction) {
      
      $brand = check_cc($user->Cipher->decrypt($transaction["cardnumber"]));
      if(!array_key_exists($brand,$card_brands)) {
        $card_brands[$brand]=1;
      } else {
        $card_brands[$brand]++;
      }
      
      if(!array_key_exists($brand,$total_per_brands)) {
        $total_per_brands[$brand]=$transaction["amnt"];
      } else {
        $total_per_brands[$brand]+=$transaction["amnt"];
      }
      
      
    }
    
    $mercs[]=array(
      "merchant"=>$merchant["BankName"],
      "brands"=>$card_brands,
      "brands_totals"=>$total_per_brands,
    );
    
      
 
 
 }
 
  echo "<br /> <pre>".print_r($mercs,1)." </pre> <br />";
     