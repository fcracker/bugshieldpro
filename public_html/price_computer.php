<?php
include_once ("./lib/config.inc.php");
include_once ("./lib/database.inc.php");

global $cfg;
$con = connect_database();
$t = new tracker;
$tracker = $t->get_data();
$unit_price = $cfg['product_price'];
$shipping = $tracker['shipping_price'];
$path_addon = isset($tracker['path']) ? $tracker['path'] : "";

if (strlen($path_addon)) {
	if (array_key_exists('product_price_' . $path_addon, $cfg)) {
		$unit_price = $cfg['product_price_' . $path_addon];
	}
}

// check if we are in an exit

$exit = isset($_POST['exit']);

if ($exit || isset($tracker['exit_price'])) {
	if ($exit && is_numeric($_POST['exit']) && $_POST['exit'] > 0) {
		include_once ("./lib/exits.class.php");

		$custom_exit = new custom_exit($cfg);
		$exit_data = $custom_exit->get_exit_by_id($_POST['exit']);
		$unit_price = $exit_data['unit_price'];
		$tracker['exit_price'] = $unit_price;

		// update tracker

		$t->set_data($tracker);
	}
	else {

		// we are already in an exit status

		$unit_price = $tracker['exit_price'];
	}
}

if (isset($_POST['qty'])) {
	$qty = intval($_POST['qty']);
}
else {
	die();
}

if ($qty <= 0) die();

// check if we have an offer for this campaign

$email_campaign_offer = supersession("campaign_offer") !== false ? base64_decode(supersession("campaign_offer")) : 0;

if ($email_campaign_offer) {

	// get it

	$offer_res = single_query_assoc("select product_price from campaign_offers where id=" . intval($email_campaign_offer));
	if (count($offer_res)) {
		if (floatval($offer_res['product_price']) > 0) {
			$unit_price = floatval($offer_res['product_price']);
		}
	}
}

$return = array(
	"subtotal" => money_format("%.2i", $unit_price * $qty) ,
	"total" => money_format("%.2i", $unit_price * $qty + $shipping) ,
);

if ($exit) {
	$return['unit_price'] = money_format("%.2i", $unit_price);
}

echo json_encode($return);
