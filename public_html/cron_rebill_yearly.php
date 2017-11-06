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

global $cfg;

// connect

$db = connect_database();
$rebill_period = 365;
$user = new umUser();
$users = array();
$rebill_cycle = new rebill_cycle;
$r = $rebill_cycle->get_next_rebill($rebill_period);

// print_r($users);

if ($r !== false) {

	// log this thing

	@$handle = fopen(LOG_DIR . "rebill/" . date("Y-m-d-") . "cron_log_" . $rebill_period . ".txt", "a+");
	@fwrite($handle, date("Y-m-d H:i:s") . "--Monthly Fee Processing start" . "\r\n");
	@fwrite($handle, "-Found user -> ID: " . $r["user_id"] . "\r\n");
	$users[] = $user->get_user_info_by_id($r["user_id"]);
	foreach($users as $key => $data) {

		// process date

		$data["exp_year"] = $data["expiration_year"];
		$data["exp_month"] = $data["expiration_month"];

		// make sure to inlcude ip

		$data["ip"] = $data["user_ip"];
		$data["is_yearly_rebill"] = 1;

		// description

		$data["transaction_description"] = $data["description"];
		$res = do_bundle_payment($data, $r["amount"]);
		if ($res['ACK'] == "Success") {
			fwrite($handle, "-User " . $data["email"] . " OK!\r\n");

			// need to create a new order
			// add an order

			$order = new order;
			$order_data = array(
				"user_id" => $r['user_id'],
				"qty" => $r["qty"],
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
			*
			*/
		}
		else {

			// add a note of this failure

			$note_text = "* Failed to charge the " . $rebill_period . " rebill -  $" . $r["amount"] . " at " . date("Y-m-d H:i") . ", result was: <<" . print_r($res, 1) . ">> *";

			// check if the used merchant has a soft decline rule, and if we actually had a soft decline

			$soft_decline = false;
			if (isset($r['L_LONGMESSAGE0'])) {
				if (strtoupper($r['L_LONGMESSAGE0']) == 'AVS REJECTED') {
					$soft_decline = true;
				}
			}

			$remove_rebill = true;
			$reason_to_not_remove = "";

			// should we act on this soft decline

			if ($soft_decline) {

				// get used merchant

				$backEndObj = new PayBackEnd();
				$last_bank = $backEndObj->get_last_used_bank($data["email"]);
				if ($last_bank !== null) {

					// check if we have soft decline enabled

					if ($last_bank->soft_decline_rebill == 0) {
						$remove_rebill = false;
						$reason_to_not_remove = "Soft Declines DO NOT remove rebills on merchant << " . $last_bank->BankName . " >>";
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
				@fwrite($handle, "-User " . $data["email"] . " FAILED REBILL - BUT SOFT DECLINE, NOT REMOVING!  \r\n");
				$note_text.= $reason_to_not_remove;
			}

			$new_notes = $data["notes"] . $note_text;
			$user->userID = ($r["user_id"]);
			$user->notes = $notes;
			$user->update_notes_user();
		}
	}

	@fwrite($handle, date("Y-m-d H:i:s") . "--FINISHED--\r\n\r\n");
	@fclose($handle);
}