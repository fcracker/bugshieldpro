<?php

function save_to_icontact($data, $autosubmit = true)
{
	$target = "https://app.icontact.com/icp/signup.php";
	$needed = array(
		"fields_email",
		"fields_fname",
		"formid",
		"listid"
	);
	foreach($needed as $need) {
		if (!isset($data[$need])) {

			// get out, the data is not good

			return "";
		}
	}

	// make sure the special token is also set

	if (!isset($data["specialid:" . $data["listid"]])) {
		return "";
	}

	$f = '<form name="icontact_form" id="icontact_form" method="post" action="' . $target . '" target="icontact" style="position:absolute;" >';
	foreach($data as $k => $d) {
		$f.= '<input id="' . $k . '" type="hidden" name="' . $k . '" value="' . $d . '">';
	}

	$f.= '<input type="hidden" name="clientid" value="1249327">
<input type="hidden" name="reallistid" value="1">
<input type="hidden" name="doubleopt" value="0">
</form>';
	$f.= '<iframe style="width:0px;height:0px;border:0px;position:absolute;" name="icontact"></iframe>';
	if ($autosubmit) {

		// script to make it all work

		$f.= '<script>$(function(){$("#icontact_form").submit();});</script>';
	}

	return $f;
}

function get_first_last_name($full)
{
	$d = array();
	$firstname_lastname = explode(" ", $full);
	$d["firstname"] = $firstname_lastname[0];
	if (isset($firstname_lastname[1])) {
		$d["lastname"] = $firstname_lastname[1];
	}
	else {
		$d["lastname"] = ""; //unknown
	}

	return $d;
}

function remove_from_get_response($email)
{
	include_once ("get_response.php");

	$gr = new get_response;
	$gr->remove_by_email($email);
}

function remove_from_mailchimp_api($email)
{
	global $cfg;
	include_once ('mailchimp/MCAPI.class.php');

	$key = $cfg['mailchimp_api_key'];
	$api = new MCAPI($key);

	// grab to which list the user was subscribed

	$res = single_query_assoc("select webform_id from mem_signup_log where Email='" . $email . "'");
	if (count($res)) {
		if (strlen($res['webform_id'])) {
			$retval = $api->listUnsubscribe($res['webform_id'], $email, true, false, false);
		}
	}

	return $retval;
}

function remove_from_mailchimp_partial($email)
{
	global $cfg;
	include_once ('mailchimp/MCAPI.class.php');

	$key = $cfg['mailchimp_api_key'];
	$api = new MCAPI($key);

	// grab to which list the user was subscribed

	$res = single_query_assoc("select webform_id from mem_signup_log where Email='" . $email . "'");
	if (count($res)) {
		if (strlen($res['webform_id'])) {
			$retval = $api->listUnsubscribe($res['webform_id'], $email, true, false, false);
		}
	}

	return $retval;
}

function generate_checkout_icontact_form($tracker)
{

	// BedroomGuardian-One Form

	include_once 'mobile_detect/Mobile_Detect.php';

	// include_once("get_response.php");

	$detect = new Mobile_Detect;
	$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'desktop');

	// save to our database as well

	$ip = $_SERVER['REMOTE_ADDR'];
	$unique_id = str_replace(".", "", $ip) . "-" . rand(1000, 9999) . "-" . md5(date("dmYHis") . $tracker["fullname"]);
	$referer = session('referer');
	if ($referer === false) {
		$referer = "";
	}

	$special_state_visitor = supersession('_sys');
	$data = array(
		"FullName" => $tracker["fullname"],
		"Country" => $tracker["country"],
		"State" => $tracker["state"],
		"City" => $tracker["city"],
		"Address" => $tracker["address"],
		"PostalCode" => $tracker["postalcode"],
		"Telephone" => $tracker["phone"],
		"AccessDate" => date("Y-m-d H:i:s") ,
		"Email" => $tracker["email"],
		"ip" => $_SERVER['REMOTE_ADDR'],
		"referer" => $referer,
		"device_type" => $deviceType,
		"unique_id" => $unique_id,
	);
	$sql = "insert into mem_signup_log set ";
	foreach($data as $k => $v) {
		$sql.= ($k != "FullName" ? "," : "") . $k . "='" . $v . "'";
	}

	@mysql_query($sql);
	$names = get_first_last_name($tracker["fullname"]);
	$data = $tracker;
	$data["unique_id"] = $unique_id;
	save_to_mailchimp_api($data);
	if ($special_state_visitor != false) {
		include_once ("email.class.php");

		$visitor_data = multi_query_assoc("SELECT * FROM special_state_log WHERE unique_id='" . mysql_real_escape_string($special_state_visitor) . "' order by visit_time desc");
		$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$special_state_text = "A visitor from one of the special states visited a checkout page(" . $actual_link . ") \n <br /> \n";
		$special_state_text.= "We have this data on the him/her: \n <br /> \n ";
		foreach($data as $k => $v) {
			$special_state_text.= $k . " = " . $v . "\n <br /> \n";
		}

		$special_state_text.= "\n\n <br /><br /> \n\n";
		if ($visitor_data) {
			$listed_data = array_slice($visitor_data, 0, 10);
			$special_state_text.= "Also, he/she visited before, and here is the data from those previous visits:\n <br /> \n";
			foreach($visitor_data as $k => $v) {
				foreach($v as $kk => $vv) {
					$special_state_text.= $kk . " = " . $vv . "\n <br /> \n";
				}

				$special_state_text.= "\n\n <br />-------------------<br /> \n\n";
			}
		}

		$from = 'special_state@bedroomguardian.com';
		$fromName = 'Special State Bedroom Guardian';
		$mailObj = new CI_Email();
		$mailObj->set_mailtype("html");
		$mailObj->from($from, $fromName);
		$mailObj->setMailTo(array(
			"vlad.2hex.toma@gmail.com"
		));
		$mailObj->setMailTitle("A visitor from the special states visited a live page");
		$mailObj->setMailContent($special_state_text);
		$mailObj->sendToMail();
	}
}

function save_to_mailchimp_api($data)
{
	global $cfg;

	// check if we have it already saved someplace

	$check_res = single_query_assoc("select ID from mem_signup_log WHERE Email='" . $data["email"] . "' and webform_id IS NOT NULL");
	if (count($check_res)) {
		return false; //bail, no reason to re-add this person
	}

	include_once ('mailchimp/MCAPI.class.php');

	$key = $cfg['mailchimp_api_key'];
	$api = new MCAPI($key);
	$names = get_first_last_name($data["fullname"]);
	$merge_vars = array(
		"FNAME" => $names["firstname"],
		"LNAME" => $names["lastname"],
		"ADDRESS" => $data["address"],
		"COUNTRY" => $data["country"],
		"STATE" => $data["state"],
		"UNIQUEID" => $data["unique_id"],
	);
	$the_campaign_id = null;
	$splits = array();

	// need to find the list to which we subscribe

	$active_campaigns = multi_query_assoc("select * from email_campaigns where active=1");
	if (count($active_campaigns)) {
		foreach($active_campaigns as $key => $campaign) {
			if ($key == 0) {
				$the_campaign_id = $campaign["webform_id"];
			} //get just the first found as a default
			if ($campaign["split"]) {
				$splits[] = $campaign["webform_id"];
			}
		}

		if (count($splits) >= 2) {
			$split_index = rand(0, 1);
			$the_campaign_id = $splits[$split_index];
		}
	}

	if ($the_campaign_id != null) {
		$retval = $api->listSubscribe($the_campaign_id, $data["email"], $merge_vars, 'html', false);

		// save the list id under which we saved

		$sql = "update mem_signup_log set webform_id='" . $the_campaign_id . "' where Email='" . $data["email"] . "'";
		mysql_query($sql);
	}
}

function save_to_mailchimp_buyers($order_data)
{
	global $cfg;
	include_once ('mailchimp/MCAPI.class.php');

	$key = $cfg['mailchimp_api_key'];
	$api = new MCAPI($key);
	$merge_vars = array(
		"FNAME" => $order_data["firstname"],
		"LNAME" => $order_data["lastname"],
		"ADDRESS" => $order_data["address"],
		"COUNTRY" => $order_data["country"],
		"STATE" => $order_data["state"],
		"USERID" => $order_data["user_id"],
	);
	$the_campaign_id = $cfg['mailchimp_buyers_list'];
	if ($the_campaign_id != null) {
		$retval = $api->listSubscribe($the_campaign_id, $order_data["email"], $merge_vars, 'html', false);
	}
}

function save_to_mailchimp_partial($order_data)
{
	global $cfg;
	include_once ('mailchimp/MCAPI.class.php');

	$key = $cfg['mailchimp_api_key'];
	$api = new MCAPI($key);
	$merge_vars = array(
		"FNAME" => $order_data["first_name"],
		"LNAME" => $order_data["last_name"],
		"ADDRESS" => $order_data["address"],
		"COUNTRY" => $order_data["country"],
		"STATE" => $order_data["state"],
		"USERID" => 1,
	);
	$the_campaign_id = $cfg['mailchimp_partial_list'];
	if ($the_campaign_id != null) {
		$retval = $api->listSubscribe($the_campaign_id, $order_data["email"], $merge_vars, 'html', false);
	}
}

function save_to_getresponse($data)
{
	$target = "https://app.getresponse.com/add_contact_webform.html";
	$needed = array(
		"name",
		"email"
	);
	foreach($needed as $need) {
		if (!isset($data[$need])) {

			// get out, the data is not good

			return "";
		}
	}

	$f = '<form name="getresponse_form" id="getresponse_form" method="post" action="' . $target . '" target="getresponse" style="position:absolute;" >';
	foreach($data as $k => $d) {
		$f.= '<input id="' . $k . '" type="hidden" name="' . $k . '" value="' . $d . '">';
	}

	if (!isset($data["webform_id"])) {
		$f.= '<input type="hidden" name="webform_id" value="439102">';
	}

	$f.= '</form>';
	$f.= '<iframe style="width:0px;height:0px;border:0px;position:absolute;" name="getresponse"></iframe>';
	if (true) {

		// script to make it all work

		$f.= '<script>$(function(){$("#getresponse_form").submit();});</script>';
	}

	return $f;
}

function generate_upsell_icontact_form($tracker)
{

	// BedroomGuardian-Two Form

	$names = get_first_last_name($tracker["fullname"]);
	return save_to_icontact(array(
		"formid" => 154,
		"listid" => 3570,
		"specialid:3570" => "CHBP",
		"fields_email" => $tracker["email"],
		"fields_fname" => $names['firstname'],
		"fields_lname" => $names['lastname'],
		"fields_phone" => $tracker["phone"],
		"fields_address1" => $tracker["address"],
		"fields_city" => $tracker["city"],
		"fields_state" => $tracker["state"],
		"fields_zip" => $tracker["postalcode"],
	));
}

function generate_social_icontact_form()
{

	// BedroomGuardian-Three Form
	// this is going to be filled in with dummy data for starters,
	// JS will fill in the gaps

	return save_to_icontact(array(
		"formid" => 155,
		"listid" => 3571,
		"specialid:3571" => "4PC9",
		"fields_email" => "noemail",
		"fields_fname" => "nofname",
		"fields_lname" => "nolname",
	) , false);
}

function save_to_aweber($list_name, $name, $email)
{
	$data = array();
	$data["meta_web_form_id"] = "446484013";
	$data["meta_split_id"] = "";
	$data["listname"] = $list_name;
	$data["redirect"] = "";
	$data["meta_adtracking"] = "dsf4vfs";
	$data["meta_message"] = "1";
	$data["meta_required"] = "name,email";
	$data["name"] = $name;
	$data["email"] = $email;
	$target = "https://www.aweber.com/scripts/addlead.pl";
	$f = '<form name="hweber_form" id="hweber_form" method="post" action="' . $target . '" target="hweber" >
	<input type="hidden" name="meta_web_form_id" value="' . $data["meta_web_form_id"] . '" />
	<input type="hidden" name="meta_split_id" value="" />
	<input type="hidden" name="listname" value="' . $data["listname"] . '" />
	<input type="hidden" name="redirect" value="" />
	<input type="hidden" name="meta_redirect_onlist" value="" />
	<input type="hidden" name="meta_adtracking" value="' . $data["meta_adtracking"] . '" />
	<input type="hidden" name="meta_message" value="1" />
	<input type="hidden" name="meta_required" value="name,email" />
	<input type="hidden" name="meta_forward_vars" value="1" />
	<input type="hidden" name="meta_tooltip" value="" />
	<input type="hidden" name="name" value="' . $name . '" />
	<input type="hidden" name="email" value="' . $email . '"  />
	</form>';
	$f.= '<iframe style="width:0px;height:0px;border:0px;" name="hweber" />';

	// script to make it all work

	$f.= '<script>$(function(){$("#hweber_form").submit();});</script>';
	return $f;
}

function check_cc($cc)
{
	$cards = array(
		"visa" => "(4\d{12}(?:\d{3})?)",
		"amex" => "(3[47]\d{13})",
		"jcb" => "(35[2-8][89]\d\d\d{10})",
		"maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
		"solo" => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
		"mastercard" => "(5[1-5]\d{14})",
		"switch" => "(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)",
		"discover" => "(6(?:011\d{12}|5\d{14}|4[4-9]\d{13}|22(?:1(?:2[6-9]|[3-9]\d)|[2-8]\d{2}|9(?:[01]\d|2[0-5]))\d{10}))",
	);
	$names = array(
		"Visa",
		"American Express",
		"JCB",
		"Maestro",
		"Solo",
		"Mastercard",
		"Switch",
		"Discover"
	);
	$matches = array();
	$pattern = "#^(?:" . implode("|", $cards) . ")$#";
	$result = preg_match($pattern, str_replace(" ", "", $cc) , $matches);
	return ($result > 0) ? $names[sizeof($matches) - 2] : "Visa";
}

function log_transaction($data)
{
	if (!isset($data["email"]) || !isset($data["amount"])) {
		return false;
	}

	$sql = "insert into transaction_log set " . "email='" . $data["email"] . "'," . "amount='" . $data["amount"] . "'," . "timestamp=NOW()," . "raw='" . (isset($data["raw"]) ? $data["raw"] : "") . "'," . "txid='" . (isset($data["txid"]) ? $data["txid"] : "") . "'," . "fname='" . (isset($data["fname"]) ? $data["fname"] : "") . "'," . "lname='" . (isset($data["lname"]) ? $data["lname"] : "") . "'," . "addr='" . (isset($data["addr"]) ? $data["addr"] : "") . "'," . "city='" . (isset($data["city"]) ? $data["city"] : "") . "'," . "state='" . (isset($data["state"]) ? $data["state"] : "") . "'," . "zip='" . (isset($data["zip"]) ? $data["zip"] : "") . "'," . "country='" . (isset($data["country"]) ? $data["country"] : "") . "'," . "phone='" . (isset($data["phone"]) ? $data["phone"] : "") . "'," . "type='" . (isset($data["type"]) ? $data["type"] : "") . "'," . "status=" . (isset($data["status"]) ? intval($data["status"]) : 1) . "";
	mysql_query($sql);
}

function log_transaction_timestamped($data)
{
	if (!isset($data["email"]) || !isset($data["amount"]) || !isset($data["timestamp"])) {
		return false;
	}

	$sql = "insert into transaction_log set " . "email='" . $data["email"] . "'," . "amount='" . $data["amount"] . "'," . "timestamp='" . $data["timestamp"] . "'," . "raw='" . (isset($data["raw"]) ? $data["raw"] : "") . "'," . "txid='" . (isset($data["txid"]) ? $data["txid"] : "") . "'," . "fname='" . (isset($data["fname"]) ? $data["fname"] : "") . "'," . "lname='" . (isset($data["lname"]) ? $data["lname"] : "") . "'," . "addr='" . (isset($data["addr"]) ? $data["addr"] : "") . "'," . "city='" . (isset($data["city"]) ? $data["city"] : "") . "'," . "state='" . (isset($data["state"]) ? $data["state"] : "") . "'," . "zip='" . (isset($data["zip"]) ? $data["zip"] : "") . "'," . "country='" . (isset($data["country"]) ? $data["country"] : "") . "'," . "phone='" . (isset($data["phone"]) ? $data["phone"] : "") . "'," . "status=" . (isset($data["status"]) ? intval($data["status"]) : 1) . "";
	mysql_query($sql);
}

function log_special_page($data)
{
	$sql = "insert into special_state_log set ";
	foreach($data as $k => $v) {
		$sql.= $k . "='" . mysql_real_escape_string($v) . "',";
	}

	$sql.= "visit_time = NOW()";
	mysql_query($sql);
}

function can_preselect_merchant()
{
	$sql = "select COUNT(BankID) as knt from mem_merchant WHERE use_monthly>0";
	$res = mysql_query($sql);
	$row = mysql_fetch_object($res);
	return ($row->knt > 0);
}

function get_preselected_merchant()
{
	$pre_select = supersession('bgcookie8');
	if ($pre_select !== false) {
		$sql = "SELECT * FROM `mem_merchant` WHERE md5(CONCAT(`BankID`,`gatewayType`,`gatewayID`))='" . mysql_real_escape_string($pre_select) . "'";
		$res = mysql_query($sql);
		if ($res && mysql_num_rows($res)) {
			$row = mysql_fetch_object($res);
			return $row;
		}
		else {
		}
	}

	return false;
}

function get_specific_merchant_data($mid)
{
	$sql = "SELECT * FROM `mem_merchant` WHERE `BankID`='" . $mid . "'";
	$res = mysql_query($sql);
	if ($res && mysql_num_rows($res)) {
		$row = mysql_fetch_object($res);
		return $row;
	}

	return null;
}

function get_merchant_initial_forward($mid)
{
	$sql = "SELECT initial_forward FROM `mem_merchant` WHERE `BankID`=" . $mid;
	$res = mysql_query($sql);
	if ($res && mysql_num_rows($res)) {
		$row = mysql_fetch_object($res);
		return $row->initial_forward;
	}

	return 0;
}

function do_upsell_charge($tracker, $amount, $description, $merchant_tracker, $p = NULL)
{
	global $cfg;
	require_once (dirname(__FILE__) . '/PayFrontEnd.php');

	include_once (dirname(__FILE__) . "/usertemp.class.php");

	include_once (dirname(__FILE__) . "/user.class.php");

	$user_data = array();
	$ut = new umUserTemp();
	$ur = new umUser();
	if ($cfg['bundle_sales']) {
		$ut->email = $tracker['email'];
		$user_data = $ut->getUser();
	}
	else {
		$ur->email = $tracker['email'];
		$user_data = $ur->get_user_info_by_email($ur->email);
	}

	$param = array(
		"paymentType" => 'Sale',
		"firstName" => $user_data["firstname"],
		"lastName" => $user_data["lastname"],
		"cardname" => $user_data["cardname"],
		"creditCardType" => check_cc(str_replace(" ", "", str_replace("-", "", $user_data["cardnumber"]))) ,
		"creditCardNumber" => str_replace(" ", "", str_replace("-", "", $user_data["cardnumber"])) ,
		"expDateYear" => $user_data["expiration_year"],
		"expDateMonth" => $user_data["expiration_month"],
		"cvv2Number" => $user_data['cvvcode'],
		"address1" => $user_data['address'],
		"address2" => "",
		"city" => $user_data['city'],
		"state" => $user_data['state'],
		"zip" => $user_data['postalcode'],
		"country" => $user_data['country'],
		"phone" => $user_data['phone'],
		"email" => $user_data['email'],
		"amount" => $amount
	);
	$param["transaction_description"] = $description;
	$processor = new PayFrontEnd();
	$pre_selected_merchant = get_preselected_merchant();
	$upsell_charges = $pre_selected_merchant->upsell_charges;
	$merchant_id = $pre_selected_merchant->BankID;
	if (strlen($upsell_charges)) {
		$upsell_merchants = unserialize($upsell_charges);
		if (is_array($upsell_merchants) && isset($upsell_merchants[$merchant_tracker])) {
			$merchant_id = (int)$upsell_merchants[$merchant_tracker];
		}
	}

	$payment_response = $processor->directPay($param, $tracker['email'], false, $merchant_id, true);
	
	//add validation for add offer
	if ($p != NULL) {
		if ($payment_response['ACK'] != 'Success') {
			$_SESSION['ACK_ERROR'] =  $payment_response['L_LONGMESSAGE0'];
			return false;
		} else {
			return ($payment_response["ACK"] == "Success");
		}
	}
	
	return ($payment_response["ACK"] == "Success");
}

if(!function_exists("money_format")) {
    function money_format($format, $number) 
        { 
            $regex  = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?'. 
                      '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/'; 
            if (setlocale(LC_MONETARY, 0) == 'C') { 
                setlocale(LC_MONETARY, ''); 
            } 
            $locale = localeconv(); 
            
            preg_match_all($regex, $format, $matches, PREG_SET_ORDER); 
            foreach ($matches as $fmatch) { 
                $value = floatval($number); 
                $flags = array( 
                    'fillchar'  => preg_match('/\=(.)/', $fmatch[1], $match) ? 
                                   $match[1] : ' ', 
                    'nogroup'   => preg_match('/\^/', $fmatch[1]) > 0, 
                    'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ? 
                                   $match[0] : '+', 
                    'nosimbol'  => preg_match('/\!/', $fmatch[1]) > 0, 
                    'isleft'    => preg_match('/\-/', $fmatch[1]) > 0 
                ); 
                
                $flags['nosimbol'] = true;
                
                $width      = trim($fmatch[2]) ? (int)$fmatch[2] : 0; 
                $left       = trim($fmatch[3]) ? (int)$fmatch[3] : 0; 
                $right      = trim($fmatch[4]) ? (int)$fmatch[4] : $locale['int_frac_digits']; 
                $conversion = $fmatch[5]; 

                $positive = true; 
                if ($value < 0) { 
                    $positive = false; 
                    $value  *= -1; 
                } 
                $letter = $positive ? 'p' : 'n'; 

                $prefix = $suffix = $cprefix = $csuffix = $signal = ''; 

                $signal = $positive ? $locale['positive_sign'] : $locale['negative_sign']; 
                switch (true) { 
                    case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+': 
                        $prefix = $signal; 
                        break; 
                    case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+': 
                        $suffix = $signal; 
                        break; 
                    case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+': 
                        $cprefix = $signal; 
                        break; 
                    case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+': 
                        $csuffix = $signal; 
                        break; 
                    case $flags['usesignal'] == '(': 
                    case $locale["{$letter}_sign_posn"] == 0: 
                        $prefix = '('; 
                        $suffix = ')'; 
                        break; 
                } 
                if (!$flags['nosimbol']) { 
                    $currency = $cprefix . 
                                ($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) . 
                                $csuffix; 
                } else { 
                    $currency = ''; 
                } 
                
                if(empty($locale['mon_decimal_point'])) {
                    $locale['mon_decimal_point'] = $locale['decimal_point'];
                }
                
                $space  = $locale["{$letter}_sep_by_space"] ? ' ' : ''; 

                $value = number_format($value, $right, $locale['mon_decimal_point'], 
                         $flags['nogroup'] ? '' : $locale['mon_thousands_sep']); 
                $value = explode($locale['mon_decimal_point'], $value); 

                $n = strlen($prefix) + strlen($currency) + strlen($value[0]); 
                if ($left > 0 && $left > $n) { 
                    $value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0]; 
                } 
                $value = implode($locale['mon_decimal_point'], $value); 
                if ($locale["{$letter}_cs_precedes"]) { 
                    $value = $prefix . $currency . $space . $value . $suffix; 
                } else { 
                    $value = $prefix . $value . $space . $currency . $suffix; 
                } 
                if ($width > 0) { 
                    $value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ? 
                             STR_PAD_RIGHT : STR_PAD_LEFT); 
                } 

                $format = str_replace($fmatch[0], $value, $format); 
            } 
            return $format; 
        } 

}

function mask_cc_number($number) {
    return substr($number, 0, 4) . str_repeat("X", strlen($number) - 8) . substr($number, -4);
}

function disclosure($offered_price, $rebill_period, $rebill_price="19.90") {
    
    if(strpos($offered_price, "\$")) {
        $offered_price = str_replace("\$", "", $offered_price);
    }
    
    if(strpos($rebill_price, "\$")) {
        $rebill_price = str_replace("\$", "", $rebill_price);
    }
    
    $offered_price = sprintf("%.2f", $offered_price);
    $rebill_price = sprintf("%.2f", $rebill_price);
	
	if(defined('BASE_PATH')) {
		return disclosure_base_path($offered_price, $rebill_period, $rebill_price);
	}
    
    if(defined('SPECIAL_STATE')) {
        return disclosure_special_state($offered_price, $rebill_period, $rebill_price);
    } 
    return disclosure_default($offered_price, $rebill_period, $rebill_price);
}

function disclosure_default($offered_price, $rebill_period, $rebill_price) 
{
	return 
	"Bedroom Guardian - Bed Bug Prevention Program. 
	You are ordering the Bedroom Guardian Bed Bug Protection 1 Step Device. 
	Your initial order of Bedroom Guardian costs just \$$offered_price per unit. 
	Starting $rebill_period days from your initial order date, you'll receive a new Bedroom Guardian refill every $rebill_period days 
	at the guaranteed low price of just \$$rebill_price multiplied by the number of units ordered today, 
	which will conveniently be charged to the card you provide today. There is no minimum unit order to buy. 
	To customize this program or future shipments and charges, call customer service during regular business hours which are 9 am to 5 pm Pacific Standard Time. 
	Every Bedroom Guardian order comes with our $rebill_period-day Money Back Guarantee.";
}

function disclosure_special_state($offered_price, $rebill_period, $rebill_price) 
{
	return 
	"Bedroom Guardian - Bed Bug Prevention Program. 
	When you order today, you'll get our rush delivery and save 50%! 
	STARTING $rebill_period DAYS FROM YOUR ORDER DATE, YOU'LL RECEIVE A NEW $rebill_period-DAY 
	SUPPLY OF BEDROOM GUARDIAN EVERY $rebill_period DAYS AT THE GUARANTEED LOW PRICE 
	OF JUST \$$rebill_price MULTIPLIED BY THE NUMBER OF UNITS ORDERED TODAY, 
	which will conveniently be charged to the card you provide today 
	unless you call to cancel. If you use a debit card, this recurring payment 
	will be automatically deducted from the card's associated bank account.
	There is no commitment and no minimum to buy.
	<br />
	<br />
	To customize this program or future shipments and charges, call customer service during regular business hours which are 9 am to 5 pm Pacific Standard Time. 
	Every Bedroom Guardian order comes with our $rebill_period-day Money Back Guarantee.
	<br />
	<br />
	You may call <strong>1-877-738-4912</strong> within $rebill_period days to obtain a refund.";
}

function disclosure_base_path($offered_price, $rebill_period, $rebill_price) 
{
	return
	"Bedroom Guardian - Bed Bug Prevention Program. 
	When you order today, you'll get our rush delivery and save 50%! 
	YOUR INITIAL ORDER WILL COST A DISCOUNTED RATE OF \$$offered_price. STARTING $rebill_period DAYS FROM YOUR ORDER DATE, YOU'LL RECEIVE A NEW $rebill_period-DAY SUPPLY OF BEDROOM GUARDIAN EVERY $rebill_period DAYS AT THE GUARANTEED LOW PRICE OF JUST \$$rebill_price MULTIPLIED BY THE NUMBER OF UNITS ORDERED TODAY, which will conveniently be charged to the card you provide today unless you call to cancel. If you use a debit card, this recurring payment will be automatically deducted from the card's associated bank account. There is no commitment and no minimum to buy. 
	<br />
	<br />
	To customize this program or future shipments and charges, call customer service during regular business hours which are 9 am to 5 pm Pacific Standard Time. Every Bedroom Guardian order comes with our $rebill_period-day Money Back Guarantee. 
	<br />
	<br />
	You may call 1-877-738-4912 within $rebill_period days to obtain a refund.";
}

function product_subtext($main = false)
{
	if (defined('SPECIAL_STATE')) {
		return product_subtext_special_state();
	}

	return $main ? product_subtext_default() : product_subtext_not_main();
}

function product_subtext_default()
{
	return "Bed Bug Protection 1<br />Step Device";
}

function product_subtext_special_state()
{
	return "<span style='font-size:13px;'>(Includes Bed Bug Detector + Food Grade Elimination Powder)</span>";
}

function product_subtext_not_main()
{
	return "(1 Device Per Bed)";
}

function _state_redirect()
{
	if (defined('SPECIAL_STATE') && !defined('BASE_PATH')) {
		header("Location:index_p1.php");
		exit;
	}
}



