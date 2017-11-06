<?php
include_once("../lib/security.inc.php");
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/user.class.php");
require_once("../lib/PayFrontEnd.php");
$con = connect_database();
$user = new umUser();
if (isset($_POST['user_id']) && $_POST['user_id'] > 0) {
    $user->userID = ($_POST['user_id']);
} else {
    die("ILLEGAL ACCESS!");
}
$data = $user->get_user_info($user->userID);
if ($data == FALSE) die("ILLEGAL ACCESS");
$price = $_POST["charge_amount"];
$mypaypal = new PayFrontEnd();
$param = array(
    "paymentType"   =>  "Sale",
    "firstName"     =>  $data['firstname'],
    "lastName"      =>  $data['lastname'],
    "creditCardType" => $data['cardtype'],
    "creditCardNumber"=>$data['cardnumber'],
    "expDateYear"   =>  $data['expiration_year'],
    "expDateMonth"  =>  $data['expiration_month'],
    "cvv2Number"    =>  $data['cvvcode'],
    "address1"      =>  $data['address'],
    "address2"      =>  "",
    "city"          =>  $data['city'],
    "state"         =>  $data['state'],
    "zip"           =>  $data['postalcode'],
    "country"       =>  $data['country'],
    "phone"         =>  $data['phone'],
    "amount"        =>  $price
);
//die($data['firstname']." ".$data['lastname'].":".$data['cardnumber'].":".$data["expiration_year"].":".$data["expiration_month"].":".$data["cvvcode"]);
$res_ary = $mypaypal->directPay($param, $data["email"]);
if($res_ary["ACK"] == "Success" || $res_ary["ACK"] == "SuccessWithWarning"){
    echo "OK";
}else{
    echo $res_ary['L_LONGMESSAGE0'];
}
?>
