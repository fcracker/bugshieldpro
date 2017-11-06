<?php
//this is to be called via the cron job
$libfolder = dirname(__FILE__);
$libfolder = dirname($libfolder);
$libfolder = $libfolder . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR;
require_once($libfolder  . 'config.inc.php');
require_once($libfolder  . 'database.inc.php');
require_once($libfolder  . 'PayFrontEnd.php');

function do_bundle_payment($data,$amount=1,$is_monthly=false,$force_merchant=0) {
global $cfg;
$con = connect_database();
$mypaypal = new PayFrontEnd();
$param = array(
    "paymentType"   	=>  "Sale",
    "firstName"     	=>  $data['firstname'],
    "lastName"      	=>  $data['lastname'],
    "creditCardType" 	=> check_cc($data['cardnumber']),
    "creditCardNumber"	=> $data['cardnumber'],
    "expDateYear"  		=>   $data['exp_year'],
    "expDateMonth"  	=>  $data['exp_month'],
    "cvv2Number"    	=>  $data['cvvcode'],
    "address1"      	=>  $data['address'],
    "address2"      	=>  "",
    "city"          	=>  $data['city'],
    "state"         	=>  $data['state'],
    "zip"           	=>  $data['postalcode'],
    "country"       	=>  $data['country'],
    "phone"       		=>  $data['phone'],
    "amount"        	=>  $amount
);

if(isset($data["ip"])) {
  $param["ip"] = $data["ip"];
}

if(isset($data["transaction_description"])) {
  $param["transaction_description"] = $data["transaction_description"];
}

if(isset($data["is_rebill"])) {
  $param["is_rebill"] = $data["is_rebill"];
}

if(isset($data["is_yearly_rebill"])) {
  $param["is_yearly_rebill"] = $data["is_yearly_rebill"];
}



$res_array = $mypaypal->directPay($param, $data["email"],$is_monthly,$force_merchant);
return $res_array;



}

?>