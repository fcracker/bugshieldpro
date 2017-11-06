<?php
include_once ("./lib/config.inc.php");
include_once ("./lib/database.inc.php");
include_once ("./lib/form.class.php");
include_once ("./lib/country.class.php");
include_once ("./lib/usertemp.class.php");
include_once ("./lib/user.class.php");
include_once ("./lib/order.class.php");
include_once ("./lib/rebill_cycle.class.php");
require_once ('./lib/PayFrontEnd.php');
require_once ("./lib/email.inc.php");
require_once ("./lib/phpmailer/class.phpmailer.php");
require_once ("./lib/phpmailer/class.smtp.php");
include_once ("./lib/email.class.php");
include_once ("./lib/mobile_detect/Mobile_Detect.php");

// print_r($_POST);
// exit();

global $cfg;
$db = connect_database();
$t = new tracker;
$tracker = $t->get_data();
$valid_paths = array(
	"ap",
	"bp"
);
$path_index = isset($_GET["path"]) ? $_GET["path"] : "bp";

if (!in_array($path_index, $valid_paths)) {
	$path_index = "bp";
}

// did we receive the needed data ?

$data = array(); //received data

$needed = array(
	"first_name",
	"last_name",
	"email",
	"phone",
	"address",
	"country",
	"city",
	"state",
	"zip",
	"cardnumber",
	"expiration_month",
	"expiration_year",
	"cvv"
);
$different_shipping = false;

if (isset($_POST['billingIsSameAsShipping'])) {
	$different_shipping = $_POST['billingIsSameAsShipping'] == 'N';
	if ($different_shipping) {
		$needed = array_merge($needed, array(
			"billAddress",
			"billCity",
			"billState",
			"billZip"
		));
	}
}

$sanitize_fields = array(
	"cardnumber",
	"expiration_month",
	"expiration_year",
	"cvv",
	"quantity",
);
$data_received = true;
$error_fields = array();

// we might need the old qty, in case the users tries to re-charge

$tracker["quantity"] = $_POST['quantity'];

if ($_SESSION['productid'] != 5) {
	$tracker["quantity"] = $cfg['product_default_qty'] * $_POST['quantity'];
} else {
	$tracker["quantity"] = $_POST['quantity'];
}

$old_qty = isset($tracker["quantity"]) ? (int)$tracker["quantity"] : 0;

foreach($_POST as $key => $value) {	
	if (in_array($key, $needed)) {
		if (is_array($value)) {
			$value = $value[0];
		}

		if (in_array($key, $sanitize_fields)) {
			if (!is_numeric($value)) {

				// $value = intval($value);

				$tracker['error_message'] = "Field " . $key . " is not numeric!";
				$t->set_data($tracker);
				header("Location:" . $path_index . "/bug-shield-pro-step-3.php?id=".$_COOKIE['productid']);
				die();
			}
		}

		$tracker[$key] = htmlentities(strip_tags($value));
	}
}

foreach($needed as $need) {
	if (isset($tracker[$need]) && !empty($tracker[$need])) {
		$data[$need] = htmlentities(strip_tags($tracker[$need]));
	}
	else {
		$data_received = false;
		$error_fields[] = ucfirst($need);
	}
}

if (!$data_received) {
	$tracker['error_message'] = "The following fields were not filled in: " . implode(", ", $error_fields);
	$t->set_data($tracker);
	header("Location:" . $path_index . "/bug-shield-pro-step-3.php?id=".$_COOKIE['productid']);
	die();
}

$tracker['shipping_price'] = $cfg['shipping_price_' . $path_index];
$tracker['default_shipping_price'] = 0;

// build the shipping address

$shipping_data = array(
	"firstname" => $tracker[($different_shipping ? "first_name" : "first_name") ],
	"lastname" => $tracker[($different_shipping ? "last_name" : "last_name") ],
	"address" => $tracker[($different_shipping ? "billAddress" : "address") ],
	"country" => $tracker[($different_shipping ? "country" : "country") ],
	"state" => $tracker[($different_shipping ? "billState" : "state") ],
	"city" => $tracker[($different_shipping ? "billCity" : "city") ],
	"postalcode" => $tracker[($different_shipping ? "billZip" : "zip") ],
);

foreach($shipping_data as $sdKey => $sd) {
	$tracker["billing_" . $sdKey] = $sd;
}

$tracker["quantity"] = $old_qty;
$idesc = strip_tags($cfg['product_text_pick_'.$_SESSION['productid']]);

$t->set_data($tracker);
$user_error = true;

// what price are we selling the product at

//$offered_price = $path_index == 'ap' ? 0 : $cfg['unit_price'];
$offered_price = $cfg['unit_price'];

$subid = supersession("bg_subid") !== false ? base64_decode(supersession("bg_subid")) : 0;

// hasoffer data

$hasoffers_offer_id = supersession("hasoffers_offer_id") !== false ? base64_decode(supersession("hasoffers_offer_id")) : 0;
$hasoffers_aff_id = supersession("hasoffers_aff_id") !== false ? base64_decode(supersession("hasoffers_aff_id")) : 0;

// this one is useful at rebills

$pap_cookie = supersession("PAPVisitorId") !== false ? supersession("PAPVisitorId") : 0;
$email_campaign_id = 0;
$email_campaign_offer = 0;

// email campaign data

if (supersession("email_campaign") !== false) {

	// grab the email campaign ID, based on unique_id of this user

	$sql_ec = "select ec.id as email_campaign_id from email_campaigns ec LEFT JOIN mem_signup_log ml on BINARY ec.webform_id= BINARY ml.webform_id WHERE ml.unique_id='" . (base64_decode(supersession("email_campaign"))) . "'";
	$res_ec = single_query_assoc($sql_ec);
	if (count($res_ec)) {
		if (strlen($res_ec['email_campaign_id'])) {
			$email_campaign_id = intval($res_ec['email_campaign_id']);

			// do we have an offer ?

			$email_campaign_offer = supersession("campaign_offer") !== false ? base64_decode(supersession("campaign_offer")) : 0;
			if ($email_campaign_offer) {

				// get it

				$offer_res = single_query_assoc("select product_price from campaign_offers where id=" . intval($email_campaign_offer));
				if (count($offer_res)) {
					if (floatval($offer_res['product_price']) > 0) {
						$offered_price = floatval($offer_res['product_price']);
					}
				}
			}
		}
	}
}

$rebills = array();
$tempuser = new umUserTemp();

// check we do not have the user already in the database

$tempuser->email = $tracker["email"];
$ur = new umUser();

if ($tempuser->isExist() || $ur->isExistEmail($tracker["email"])) {

	// $tracker['error_message'] = "This email is already registered in our system. Please use another!";
	// $t->set_data($tracker);
	// header("Location:landingpage_p".$path_index.".php#orderform");die();
	// ok, so this user already exists, it could mean that we had an issue with the first payment lagging out
	// check the last login date against now, and if it is less than 24 hours, just forward the user to the upsell pages

	$old_user_info = $ur->get_user_info_by_email($tracker["email"]);
	if (strtotime($old_user_info["CreateTime"]) > (time() - (10 * 60))) {

		// inform the user he already ordered
		$_SESSION['alreadyordered'] = 'You have already placed your order. You may try again later';
		header("Location:" . $path_index . "/bug-shield-pro-step-3.php?id=".$_COOKIE['productid']);
		die();
	}
}

$user_data = $tracker;

// process the data a bit

$user_data["firstname"] = $user_data['first_name'];
$user_data["lastname"] = $user_data['last_name'];
$user_data["postalcode"] = $user_data['zip'];
$user_data["cvvcode"] = $user_data['cvv'];
$user_data["cardname"] = $user_data["first_name"] . " " . $user_data['last_name'];
$user_data["tempid"] = rand(0, 10000) . md5(date("dmyHis"));
$user_data["alt"] = "100"; //bogus
$user_data["Shipped"] = "0";
$user_data["lifetime"] = "2050-10-1";
$user_data["shipped_from"] = "2050-10-1";
$user_data["shipped_to"] = "2050-10-1";
$user_data["notes"] = "";
$user_data["cardtype"] = check_cc(str_replace(" ", "", str_replace("-", "", $user_data["cardnumber"])));
$user_data["expiration"] = $user_data["expiration_year"] . "-" . $user_data["expiration_month"] . "-" . "1";

// unset uneeded things in the user data

unset($user_data["upsell_price"]);
unset($user_data["shipping_price"]);
unset($user_data["quantity"]);
unset($user_data["upsell_step"]);
unset($user_data["path"]);
unset($user_data["zip"]);
unset($user_data["cvv"]);
unset($user_data["first_name"]);
unset($user_data["last_name"]);
unset($user_data["default_shipping_price"]);
unset($user_data["expiration_month"]);
unset($user_data["expiration_year"]);
unset($user_data["tempuser_id"]);
unset($user_data["user_id"]);
unset($user_data["order_id"]);
unset($user_data["original_qty"]);

if ($cfg['bundle_sales']) {

	// place this user as temp, no payment yet

	$temp_id = $tempuser->setUser($user_data);
	$user_error = false;
}
else {

	// we need to create the user directly / make payment
	// add up the amount

	$quantity = $tracker["quantity"];
	$tracker["original_qty"] = $quantity;
	
	$product_price = $offered_price * $quantity;
	$shipping_price = $tracker["shipping_price"];
	$total = $product_price + $shipping_price;

	// overide total amount base on selection
	$total = ($_POST['quantity'] * $offered_price) + $shipping_price;
	$tracker["productquantitypackage"] = $_POST['quantity'];

	// compute the rebill
	// check if we have a pre-selected merchant, and use its rebill price

	$pre_selected_merchant = get_preselected_merchant();
	$rebill_price = $cfg['product_price_rebill'];
	$rebill_period = $cfg['rebill_period'];
	if ($pre_selected_merchant !== false) {
		if ($pre_selected_merchant->use_monthly > 0) {
			$rebill_price = $pre_selected_merchant->monthly_price;
			$rebill_period = $pre_selected_merchant->rebill_period;
		}
	}
	
	// overide price declared at config
	$rebill_period = 30;
	if ($_SESSION['productid'] != 5) {
		$rebill_quantity = $cfg['product_default_qty'] * $_POST['quantity'];
	} else {
		$rebill_quantity = $quantity;
	}

	$rebill_total = ($rebill_quantity * $rebill_price) + $shipping_price;
	
	// get the correct date

	$full_date = explode("-", $user_data['expiration']);
	$year = $full_date[0];
	$month = $full_date[1];
	$param = array(
		"paymentType" => 'Sale',
		"firstName" => $user_data["billing_firstname"],
		"lastName" => $user_data["billing_lastname"],
		"cardname" => $user_data["cardname"],
		"creditCardType" => check_cc(str_replace(" ", "", str_replace("-", "", $user_data["cardnumber"]))) ,
		"creditCardNumber" => str_replace(" ", "", str_replace("-", "", $user_data["cardnumber"])) ,
		"expDateYear" => $year,
		"expDateMonth" => $month,
		"cvv2Number" => $user_data['cvvcode'],
		"address1" => $user_data['billing_address'],
		"address2" => "",
		"city" => $user_data['billing_city'],
		"state" => $user_data['billing_state'],
		"zip" => $user_data['billing_postalcode'],
		"country" => $user_data['billing_country'],
		"phone" => $user_data['phone'],
		"email" => $user_data['email'],
		"amount" => $total
	);
	
	$param["transaction_description"] = $_POST['quantity'] . " x (".$idesc.")" . " " . ($shipping_price > 0 ? " + shipping(" . $shipping_price . ")" : "");
	/*
	while($ur->isExistEmail($user_data['email'])) {

	// redirect("index.php?existing=1");
	// exit();

	$user_data['email'] = "new_".$user_data['email'];
	}*/
	if (!isset($cfg['enable_payment']) || (isset($cfg['enable_payment']) && $cfg['enable_payment'])) {

		// attempt payment

		$processor = new PayFrontEnd();
		if ($pre_selected_merchant !== false) {
			$payment_response = $processor->directPay($param, $user_data['email'], false, $pre_selected_merchant->BankID);
		}
		else {
			$payment_response = $processor->directPay($param, $user_data['email']);
		}
	}
	else {
		$payment_response["ACK"] = "Success";
	}

	// check initial fail forwards

	if ($payment_response["ACK"] != "Success") {
		if ($pre_selected_merchant !== false) {
			$initial_forward = $pre_selected_merchant->initial_forward;
		}
		else {

			// get the merchant forward

			$initial_forward = get_merchant_initial_forward($payment_response['bank_id']);
		}

		$times_forwarded = 0;

		// jump through forwards until we either succceed or don't have any forwards left
		// make sure wqe do this only a resoanable time, to avoid looping

		while ($initial_forward > 0) {
			/*
			-- THIS IS NOT NEEDED, BECAUSE THE USER ALREADY AGREED TO A PRICE --

			// grab the rebill data, so we set the correct rebill price

			$new_merchant_data = get_specific_merchant_data($initial_forward);
			if($new_merchant_data->use_monthly>0) {
			$rebill_price = $new_merchant_data->monthly_price;
			$rebill_period = $new_merchant_data->rebill_period;
			}

			*/
			$payment_response = $processor->directPay($param, $user_data['email'], false, $initial_forward);
			if ($payment_response["ACK"] != "Success") {
				$initial_forward = get_merchant_initial_forward($payment_response['bank_id']);
			}
			else {
				$initial_forward = 0;
			}

			$times_forwarded++;
			if ($times_forwarded > 5) {
				break;
			}
		}
	}

	// print_r($payment_response);die(" -- end --");

	if ($payment_response["ACK"] == "Success") {

		// we payed directly!
		// oh goodie
		// create a user

		$days = $user_data["days"];
		$alt = $user_data["alt"];
		$monthly_fee = $user_data["monthly_fee"];
		$errors = array();
		$username = $user_data['email'];
		$password = "";
		$data = $user_data;
		$data["emailAddress"] = $username;
		$data["email"] = $username;
		$data["password"] = md5($password);
		$tempid = 0;
		$months = 120;
		$data["recurring_fee"] = 0;
		$data["recurring_fee_period"] = $rebill_period;
		$data["last_payment"] = date("Y-m-d");
		$data["subid"] = $subid;
		$data["hasoffers_offer_id"] = $hasoffers_offer_id;
		$data["hasoffers_aff_id"] = $hasoffers_aff_id;
		$grp = $cfg['group']['gold'];

		// device type

		$deviceType = "desktop";
		$detect = new Mobile_Detect;
		$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'desktop');
		$data["device_type"] = $deviceType;
		$data["email_campaign"] = $email_campaign_id;
		$data["campaign_offer"] = $email_campaign_offer;
		$registered_time = $data['registered_time'];

		// unset($data['lifetime']);

		unset($data['id']);
		unset($data["tempid"]);
		unset($data['registered_time']);
		unset($data['monthly_fee']);
		unset($data['order_id']);
		unset($data['quantity']);

		// unset($data['email']);

		$data_copy = $data;
		foreach($data_copy as $k => $v) {
			if (strpos($k, "billing_") === 0) {
				$oKey = str_replace("billing_", "", $k);
				//$data[$oKey] = $v;
				unset($data[$k]);
			}
		}

		unset($data["address1"]);
		unset($data["address2"]);
		if ($different_shipping) {
			unset($data["billAddress"]);
			unset($data["billCity"]);
			unset($data["billState"]);
			unset($data["billZip"]);
		}

		// grab any email campaign id,offer ids

		$user = new umUser();
		if (!$ur->isExistEmail($user_data['email'])) {
			$user_id = $user->create_user($data, $grp, $tempid);
		}
		else {

			// update the user

			$user->email = $data["email"];
			$user->get_user();
			$user_id = $user->userID;
			@$user->update_address($data, $user_id);
		}

		if ($user_id > 0) {
			$user_error = false;

			// emailUser($data['emailAddress'], $email_data,$tracker);

			$user->set_user_lifetime(120);
			
			// define item description
			
			$item_description = NULL;
			if (isset($tracker["quantity"])) {
				$item_description = $item_description . ' ' . $_POST['quantity'] . " x (" . $idesc . ')';
			}

			// add an order

			$order = new order;
			$order_data = array(
				"user_id" => $user_id,
				"qty" => $quantity,
				"total" => $total,
				"firstname" => $param["firstName"],
				"lastname" => $param["lastName"],
				"address" => $param['address1'],
				"city" => $param['city'],
				"state" => $param['state'],
				"zip" => $param['zip'],
				"country" => $param['country'],
				"phone" => $param['phone'],
				"email" => $param['email'],
				"description" => $item_description,
				"status" => "not shipped",
				"subid" => $subid,
				"hasoffers_offer_id" => $hasoffers_offer_id,
				"hasoffers_aff_id" => $hasoffers_aff_id,
				"PAPVisitorId" => $pap_cookie,
				"raw_response" => serialize($payment_response) ,
			);
			$tracker["order_id"] = $order->create($order_data);
			$rebill_cycle = new rebill_cycle;
			if ($total > 0) {
				$rbl = array(
					"amount" => $rebill_total,
					"description" => "Rebill - " .$item_description,
					"period" => $rebill_period,
					"last_payment" => date("Y-m-d H:i:s") ,
					"user_id" => $user_id,
					"PAPVisitorId" => $pap_cookie,
					"qty" => $rebill_quantity,
					"hasoffers_offer_id" => $hasoffers_offer_id,
					"hasoffers_aff_id" => $hasoffers_aff_id
				);
				$rebill_cycle->create($rbl);
			}

			// try and remove the user from the email campaign list

			@remove_from_mailchimp_api($param['email']);
			@save_to_mailchimp_buyers($order_data);

			// remove from signup log

			@mysql_query("delete from mem_signup_log where Email='" . $param['email'] . "'");
		}
		else {

			// echo("Unable to register user. Please contact support.");
			// log this error to the dev

			$mailObj = new CI_Email();
			$mailObj->set_mailtype("html");
			$mailObj->from("bedroomguardian@gmail.com", "BG- Error");
			$mailObj->setMailTo("vlad.2hex.toma@gmail.com");
			$mailObj->setMailTitle("Error in payment!");
			$mailObj->setMailContent("We could not find the user. Here is some data: " . print_r($data, 1));
			$mailObj->sendToMail();
		}
	}
	else
	if ($payment_response["ACK"] == "Pending") {

		// we need to go through the 3ds process
		// we should obtain some data

		$is3ds = true;
		$form = $payment_response["form"];
		$rand = $payment_response["rand"];
		$hash_value = $form . "___" . $rand . "___" . $tracker['email'];
		$hash = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key) , $hash_value, MCRYPT_MODE_CBC, md5(md5($key))));
	}
	else {

		// we seem to have an error, display error page

		header("Location:transaction_error.php?email=" . $tracker['email']);
		die();
	}
}

// ok, we have a temp user ?

if (!$user_error) {
	if ($cfg['bundle_sales']) {

		// save the ID in the session, for easier grabbing

		$tracker["tempuser_id"] = intval($temp_id);
	}
	else {
		$tracker["user_id"] = intval($user_id);
	}

	$t->set_data($tracker);

	// go onto upsell

	header("Location:" . $path_index . "/bug-shield-pro-upsell-1.php");
	die();
}
else {
	$tracker['error_message'] = "There was a problem processing the payment. Please try again.";
	$t->set_data($tracker);
	header("Location:" . $path_index . "/checkout");
	die();
}