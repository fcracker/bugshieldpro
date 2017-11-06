<?php
/*
/* 4 * * * /usr/bin/php /home/kgi819/public_html/cron_monthly_fee.php &> /dev/null
*/

// die("-disabled");

header("cache-control:no-cache;must-revalidate");
$cron_path = dirname(__FILE__);
define('RUNNING_REBILL', 1);
require_once ($cron_path . "/lib/config.inc.php");
require_once ($cron_path . "/lib/database.inc.php");
require_once ($cron_path . "/lib/user.class.php");
require_once ($cron_path . "/lib/usertemp.class.php");
require_once ($cron_path . "/lib/email.inc.php");
include_once ($cron_path . "/lib/rebill_cycle.class.php");
include_once ($cron_path . "/lib/order.class.php");
include_once ($cron_path . "/lib/papapi.class.php");
require_once ($cron_path . "/lib/phpmailer/class.phpmailer.php");
require_once ($cron_path . "/lib/phpmailer/class.smtp.php");
require_once ($cron_path . "/paypal/dobundlepayment.php");
include_once $cron_path . '/lib/PayBackEnd.php';
require_once ($cron_path . "/lib/curl_nmi_charger.php");

global $cfg;

// connect

$db = connect_database();
$rebill_cycle = new rebill_cycle;

// default

$rebill_period = 30;
$possible_periods = $rebill_cycle->get_possible_rebill_periods();

// added extra bit to favour 30 day rebills

for ($j = 0; $j < count($possible_periods); $j++) {
	if ($possible_periods[$j] == 30) {
		if (rand(0, 4) > 0) {
			$rebill_period = 30;
			break;
		}
	}
	else {
		if (rand(0, 100) >= 80) {
			$rebill_period = $possible_periods[$j];
			break;
		}
	}
}

// end extra bit - uncomment the next 2 lines for equal opportunity
// $rebill_period_index = rand(0,count($possible_periods)-1);
// $rebill_period = $possible_periods[$rebill_period_index];
// echo "\n ... checking for ".$rebill_period." days ...\n";
// overwrite
// $rebill_period = 60;

$user = new umUser();
$users = array();
$rebill_cycle = new rebill_cycle;
$r = $rebill_cycle->get_next_rebill($rebill_period);
/*
echo "<pre>".print_r($r,1)."</pre>";
die();
*/

// do a sanity check

/*
$rebill_period_in_seconds = $rebill_period*24*60*60;
$normal_rebill_time = (strtotime($r['last_payment']) + $rebill_period_in_seconds);

if(intval(date("m",$normal_rebill_time))==8  AND (intval(date("d",$normal_rebill_time))<=26)) {

// we are not very sure about this

mail("vlad.2hex.toma@gmail.com","REBILL-UNSURE","We are not sure about rebilling but stilol doing it!".$r['user_id']);

// die();

}

*/

if ($r !== false) {

	// log this thing

	@$handle = fopen(LOG_DIR . "rebill/" . date("Y-m-d-") . "cron_log_" . $rebill_period . ".txt", "a+");
	@fwrite($handle, date("Y-m-d H:i:s") . "--Monthly Fee Processing start" . "\r\n");
	fwrite($handle, "-Found user -> ID: " . $r["user_id"] . "\r\n");
	$users[] = $user->get_user_info_by_id($r["user_id"]);
	if (($users[0]['original_cardnumber'] == 'ZbvFQgVun4GAHX')) {

		// special handling

		$our_user = $users[0];

		// check it wasn't disabled before

		$sql_test_r = "select active from mem_rebill_cycle_old where id=" . $r["id"];
		$res_test_r = mysql_query($sql_test_r);
		$go_on = true;
		if (mysql_num_rows($res_test_r)) {
			$row_test_r = mysql_fetch_object($res_test_r);
			if ($row_test_r->active == 0) {
				@fwrite($handle, "\r\n ------ USER WAS FOUND IN-ACTIVE IN OLD REBILL CYCLE-- ");
				mail("vlad.2hex.toma@gmail.com", "SPECIAL REBILL FAIL OLD", "We failed to charge " . $r['user_id'] . " / " . $our_user['email'] . " with " . $r['amount']);
				mysql_query("update mem_rebill_cycle set active=0 where id=" . $r['id'] . " limit 1");
				$go_on = false;
			}
		}

		if ($go_on) {

			// we need the last transaction ID

			$sql_tid = "select transaction_id,BankID from mem_merchant_history where user_email='" . $our_user['email'] . "' order by hDate ASC LIMIT 1";
			$res_tid = mysql_query($sql_tid);
			if (mysql_num_rows($res_tid)) {
				$rowtid = mysql_fetch_object($res_tid);
				if ($rowtid->BankID != 31) { //no Paypal
					$the_amount = money_format('%!.2i', $r['amount']);

					// execute

					$result = do_nmi_charge($rowtid->transaction_id, $the_amount);
					@fwrite($handle, "\r\n ------ Charge was SPECIAL -- result was : " . ($result ? 'POSITIVE' : 'NEGATIVE'));
					if ($result) {
						$backEndObj = new PayBackEnd();

						// add it to history

						$param = array(
							"bankid" => $rowtid->BankID,
							"transactionid" => "AAAAAA",
							"userid" => $our_user['email'],
							"methodtype" => "direct",
							"amount" => $r['amount'],
							"processor_id" => "bedroomguardian",
							"previous_sale" => "",
							"raw_response" => "",
							"transaction_description" => "automated NMI curl charge",
							"is_rebill" => 1,
						);
						$backEndObj->setHistory($param);

						// need to create a new order
						// add an order

						$order = new order;

						// fetch the last order, so we can replaicate the qty

						$last_order_array = $order->get_specific_orders_by_user(array(
							$r['user_id']
						));
						$last_order = $last_order_array[0];
						$order_data = array(
							"user_id" => $r['user_id'],
							"qty" => max(1, $r["qty"]) ,
							"total" => $r['amount'],
							"firstname" => $our_user["firstname"],
							"lastname" => $our_user["lastname"],
							"address" => $our_user['address'],
							"city" => $our_user['city'],
							"state" => $our_user['state'],
							"zip" => $our_user['postalcode'],
							"country" => $our_user['country'],
							"phone" => $our_user['phone'],
							"email" => $our_user['email'],
							"description" => $r['description'],
							"status" => "not shipped",
							"subid" => $our_user["subid"],
							"PAPVisitorId" => $r["PAPVisitorId"],
							"raw_response" => "",
						);
						$oid = $order->create($order_data);
						$rebill_cycle->update($r["id"], array(
							"last_payment" => date("Y-m-d H:i:s")
						));
						mail("vlad.2hex.toma@gmail.com", "SPECIAL REBILL", "We charged " . $r['user_id'] . " / " . $our_user['email'] . " with " . $r['amount']);
					}
					else {
						$rebill_cycle->remove_for_user($r["user_id"]);
						mail("vlad.2hex.toma@gmail.com", "SPECIAL REBILL FAIL", "We failed to charge " . $r['user_id'] . " / " . $our_user['email'] . " with " . $r['amount']);
					}

					// nullify the users, there is no need to continue processing

					$users = array();
				}
			}
		}
	}

	foreach($users as $key => $data) {

		// process date

		$data["exp_year"] = $data["expiration_year"];
		$data["exp_month"] = $data["expiration_month"];

		// make sure to include ip

		$data["ip"] = $data["user_ip"];
		$data["is_rebill"] = 1;

		// description

		$data["transaction_description"] = $data["description"];
		if ($data["transaction_description"] == NULL) $data["transaction_description"] = $r["description"];
		$res = do_bundle_payment($data, $r["amount"], false, $r["forced_merchant"]);
		if ($res['ACK'] == "Success") {
			fwrite($handle, "-User " . $data["email"] . " OK!\r\n");

			// need to create a new order
			// add an order

			$order = new order;

			// fetch the last order, so we can replaicate the qty
			// $last_order_array = $order->get_specific_orders_by_user(array($r['user_id']));
			// $last_order = $last_order_array[0];

			$order_data = array(
				"user_id" => $r['user_id'],
				"qty" => max(1, $r["qty"]) ,
				"total" => $r['amount'],
				"firstname" => $data["firstname"],
				"lastname" => $data["lastname"],
				"address" => $data['address'],
				"city" => $data['city'],
				"state" => $data['state'],
				"zip" => $data['postalcode'],
				"country" => $data['country'],
				"phone" => $data['phone'],
				"email" => $data['email'],
				"description" => $r['description'],
				"status" => "not shipped",
				"subid" => $data["subid"],
				"PAPVisitorId" => $r["PAPVisitorId"],
				"raw_response" => serialize($res) ,
			);
			$oid = $order->create($order_data);
			$rebill_cycle->update($r["id"], array(
				"last_payment" => date("Y-m-d H:i:s")
			));

			// echo $cfg['site']['url'].'/aff/scripts/sale.php';
			// track with PAP

			/*
			$saleTracker = new Pap_Api_SaleTracker($cfg['site']['url'].'/aff/scripts/sale.php');
			$saleTracker->setAccountId('default1');
			$saleTracker->setVisitorId($r["PAPVisitorId"]);
			$sale1 = $saleTracker->createSale();
			$sale1->setTotalCost($r['amount']);
			$sale1->setOrderID($oid);
			$sale1->setProductID('BedroomGuardian-'.$rebill_period.' rebill');
			$saleTracker->register();
			*/

			// mail("vlad.2hex.toma@gmail.com","REBILL-POSTACTION","We rebilled ".$r['user_id']." with ".$r['amount']);

		}
		else {

			// add a note of this failure

			$note_text = "* Failed to charge the " . $rebill_period . " rebill -  $" . $r["amount"] . " at " . date("Y-m-d H:i") . ", result was: <~" . print_r($res, 1) . "~> *";

			// check if the used merchant has a soft decline rule, and if we actually had a soft decline

			$soft_decline = false;
			$rebill_forward_later = false;
			if (isset($r['L_LONGMESSAGE0'])) {
				if (stripos($r['L_LONGMESSAGE0'], 'AVS REJECTED') !== FALSE) {
					$soft_decline = true;
				}
			}
			else
			if (isset($r['responsetext'])) {
				if (stripos($r['responsetext'], 'AVS REJECTED') !== FALSE) {
					$soft_decline = true;
				}
			}

			// get used merchant

			$backEndObj = new PayBackEnd();
			if ($r["forced_merchant"]) {
				$last_bank = $backEndObj->get_merchant_by_id($r["forced_merchant"]);
			}
			else {
				$last_bank = $backEndObj->get_last_used_bank($data["email"]);
			}

			// check if the merchant we used has a forward rebill

			if ($last_bank->rebill_fail_action > 0) {
				$new_merchant = $last_bank->rebill_fail_action > 1;
				if ($new_merchant) {
					$new_merchant_id = $last_bank->rebill_fail_merchant;
					$backEndObj->bankID = $new_merchant_id;
					$new_merchant_data = $backEndObj->get_merchant();
				}

				$time_to_add = $last_bank->rebill_fail_try_after;
				$soft_decline = true;
				$rebill_forward_later = true;
			}

			$remove_rebill = true;
			$reason_to_not_remove = "";

			// should we act on this soft decline

			if ($soft_decline) {
				if ($last_bank !== null) {

					// check if we have soft decline enabled

					if ($last_bank->soft_decline_rebill == 0 && !$rebill_forward_later) {
						$remove_rebill = false;
						$reason_to_not_remove = "Soft Declines DO NOT remove rebills on merchant << " . $last_bank->BankName . " >>";
					}

					if ($rebill_forward_later) {
						$remove_rebill = false;
						if ($new_merchant) {
							$reason_to_not_remove = "Rebill Forwarded to Merchant << " . $new_merchant_data['BankName'] . " >> after " . $time_to_add . " HRS";
						}
						else {
							$reason_to_not_remove = "Rebill Postponed for Merchant << " . $last_bank->BankName . " >> after " . $time_to_add . " HRS";
						}
					}
				}
			}

			if ($remove_rebill) {
				$rebill_retry_settings = $rebill_cycle->get_rebill_settings();

				// check if we have global retry enabled

				if ($rebill_retry_settings['rebill_retry_active'] == 1) {

					// check if the rebill  is already at some point in a retry cycle

					if ($r['retry_index'] < intval($rebill_retry_settings['rebill_retry_months'])) {

						// we have NOT tried enough for this, do it again

						$rebill_cycle->update_retry_index($r['id'], $r['retry_index'] + 1);
						$one_month_in_hours = 24 * 30;
						$rebill_cycle->postpone($r['id'], $one_month_in_hours);
						$remove_rebill = false;
						$reason_to_not_remove = 'Retrying next month[try ' . ($r["retry_index"] + 1) . '/' . $rebill_retry_settings['rebill_retry_months'] . ']';
					}
				}
			}

			if ($remove_rebill) {

				// we failed miserably

				@fwrite($handle, "-User " . $data["email"] . " FAILED FOR GOOD! ...removing the rebill \r\n");

				// @mail("vlad.2hex.toma@gmail.com","Monthly Fee Failed","User: ".$data["email"]);

				$rebill_cycle->remove_for_user($r["user_id"]);
			}
			else {
				@fwrite($handle, "-User " . $data["email"] . " FAILED REBILL - BUT SOFT DECLINE/FORWARD/RETRY, NOT REMOVING!  \r\n");
				$note_text.= $reason_to_not_remove;
				if ($rebill_forward_later) {

					// do we need to update the merchant ?

					if ($new_merchant) {
						$rebill_cycle->update_forced_merchant($r["id"], $new_merchant_id);
					}

					// postpone the rebill

					$rebill_cycle->postpone($r["id"], $time_to_add);
				}
			}

			$new_notes = $data["notes"] . $note_text;
			$user->userID = ($r["user_id"]);
			$user->notes = $new_notes;
			$user->update_notes_user();
		}
	}

	@fwrite($handle, date("Y-m-d H:i:s") . "--FINISHED--\r\n\r\n");
	@fclose($handle);
}