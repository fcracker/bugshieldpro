<?php
include_once ("./lib/config.inc.php");
include_once ("./lib/database.inc.php");
include_once ("./lib/order.class.php");
include_once ("./lib/rebill_cycle.class.php");

$pap_cookie = supersession("PAPVisitorId") !== false ? supersession("PAPVisitorId") : 0;
$hasoffers_offer_id = supersession("hasoffers_offer_id") !== false ? base64_decode(supersession("hasoffers_offer_id")) : 0;
$hasoffers_aff_id = supersession("hasoffers_aff_id") !== false ? base64_decode(supersession("hasoffers_aff_id")) : 0;

global $cfg;
$con = connect_database();

if (!isset($_GET["offer"])) die();
if (isset($_GET["laundrykit"])) {
	if (!isset($_GET["pid"]) && $_GET["pid"] != 1 && $_GET["pid"] != 2 && $_GET["pid"] != 3) die();
}
if (isset($_GET["luxuriousmattress"])) {
	if (!isset($_GET["luxuriousmattress"])) die();
}

$offer = intval($_GET['offer']);
$valid_paths = array(
	"ap",
	"bp"
);
$path_index = isset($_GET["path"]) ? $_GET["path"] : "bp";

if (!in_array($path_index, $valid_paths)) {
	$path_index = "bp";
}

$qty = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
$t = new tracker;
$tracker = $t->get_data();
$next_url = "bug-shield-pro-luxurious-mattress.php";
$yearly_upsell = isset($cfg['use_yearly_upsell']) ? $cfg['use_yearly_upsell'] : FALSE;

if (isset($_GET["laundrykit"]) && $_GET["pid"] != NULL) {
	
	$upsell_price = $cfg['laundrykit_price_pick_'.$_GET["pid"]];
	$upsell_description = $cfg['laundrykit_text_pick_'.$_GET["pid"]];

} else if (isset($_GET["luxuriousmattress"])) {
	
	$upsell_price = $cfg['luxuriousmattress_price'];
	$upsell_description = $cfg['luxuriousmattress_text'];
	
} else if (isset($_GET["upselltype"]) && $_GET["upselltype"] == '1.1') {
	
	$upsell_price = $cfg['upsell_1_1_price'];
	$upsell_description = $cfg['upsell_1_1_description'];
	
} else if (isset($_GET["upselltype"]) && $_GET["upselltype"] == '50') {
	
	$upsell_price = $cfg['upsell_1_50_price'];
	$upsell_description = $cfg['upsell_1_50_description'];
	
} else {
	
	$upsell_price = $cfg['upsell_' . $offer . '_price'];
	$upsell_description = $cfg['upsell_' . $offer . '_description'];

}

$upsell_ok = false;
$upsell_description = strip_tags($upsell_description);

if (isset($_GET["laundrykit"]) && $_GET["pid"] != NULL) {
	
	$next_url = $path_index . "/" . "bug-shield-pro-luxurious-mattress.php";
	if (!isset($tracker["has_laundrykit"])) {
		if (do_upsell_charge($tracker, $upsell_price, $upsell_description, $upsell_description, true)) {
			$tracker["has_laundrykit"] = 1;
			$tracker["laundrykit_id"] = $_GET["pid"];
			$tracker["laundrykit_qty"] = $qty;
			$upsell_qty = $upsell_qty;
			$upsell_description = $upsell_description;
			$upsell_ok = true;
		} else {
			$next_url = $_SERVER['HTTP_REFERER'];
		}
	}
	
	
} else if (isset($_GET["luxuriousmattress"])) {
	
	if (!isset($tracker["has_upsell_1"])) $next_url = $path_index . "/" . "bug-shield-pro-upsell-1-50-off.php";
	else $next_url = $path_index . "/" . "bug-shield-pro-upsell-1-1.php";
	if (!isset($tracker["has_luxuriousmattress"])) {
		if (do_upsell_charge($tracker, $upsell_price, $upsell_description, $upsell_description, true)) {
			$tracker["has_luxuriousmattress"] = 1;
			$tracker["luxuriousmattress_qty"] = $qty;
			$upsell_qty = $upsell_qty;
			$upsell_description = $upsell_description;
			$upsell_ok = true;
		} else {
			$next_url = $_SERVER['HTTP_REFERER'];
		}
	}

} else {
	
	switch (intval($_GET["offer"])) {
	default:
	break;

	case 1:
		$next_url = $path_index . "/" . "bug-shield-pro-laundry-kit.php";
		if (!isset($tracker["has_upsell_1"])) {
			if (do_upsell_charge($tracker, $upsell_price, $upsell_description, $upsell_description, true)) {
				$tracker["has_upsell_1"] = 1;
				$tracker["upsell_1_qty"] = $qty;
				$upsell_qty = $upsell_qty;
				$upsell_description = $upsell_description;
				$upsell_ok = true;
			}
		} else {
			$next_url = $_SERVER['HTTP_REFERER'];
		}
	
		break;
		
	case 11:
		$next_url = $path_index . "/" . "bug-shield-pro-upsell-2.php";		
		if (!isset($tracker["has_upsell_1_1"])) {

			if (do_upsell_charge($tracker, $upsell_price, $upsell_description, $upsell_description, true)) {
				$tracker["has_upsell_1_1"] = 1;
				$tracker["upsell_1_1_qty"] = $qty;
				$upsell_qty = $upsell_qty;
				$upsell_description = $upsell_description;
				$upsell_ok = true;
			} else {
				$next_url = $_SERVER['HTTP_REFERER'];
			}

			//UPSELL REBILL
			$rebill_cycle = new rebill_cycle;
			$rbl = array(
				"amount" => $cfg['upsell_1_1_price'],
				"description" => "Rebill - " .$upsell_description,
				"period" => 30,
				"last_payment" => date("Y-m-d H:i:s") ,
				"user_id" => $tracker["user_id"],
				"PAPVisitorId" => $pap_cookie,
				"qty" => 1,
				"hasoffers_offer_id" => $hasoffers_offer_id,
				"hasoffers_aff_id" => $hasoffers_aff_id
			);
			$rebill_cycle->create($rbl);
		}
	
		break;	
		
	case 15:
		$next_url = $path_index . "/" . "bug-shield-pro-upsell-1-1.php";
		if (!isset($tracker["has_upsell_1_50"])) {
			if (do_upsell_charge($tracker, $upsell_price, $upsell_description, $upsell_description, true)) {
				$tracker["has_upsell_1_50"] = 1;
				$tracker["upsell_1_50_qty"] = $qty;
				$upsell_qty = $upsell_qty;
				$upsell_description = $upsell_description;
				$upsell_ok = true;
			} else {
				$next_url = $_SERVER['HTTP_REFERER'];
			}
		}
	
		break;
	
	case 2:
		$next_url = $path_index . "/" . "bug-shield-pro-upsell-3.php";
		if (!isset($tracker["has_upsell_2"])) {
			
			if (do_upsell_charge($tracker, $upsell_price, $upsell_description, $upsell_description, true)) {
				$tracker["has_upsell_2"] = 1;
				$tracker["upsell_2_qty"] = $qty;
				$upsell_qty = $upsell_qty;
				$upsell_description = $upsell_description;
				$upsell_ok = true;
			} else {
				$next_url = $_SERVER['HTTP_REFERER'];
			}
			
			//UPSELL REBILL
			$rebill_cycle = new rebill_cycle;
			$rbl = array(
				"amount" => $cfg['upsell_2_price'],
				"description" => "Rebill - " .$upsell_description,
				"period" => 30,
				"last_payment" => date("Y-m-d H:i:s") ,
				"user_id" => $tracker["user_id"],
				"PAPVisitorId" => $pap_cookie,
				"qty" => 1,
				"hasoffers_offer_id" => $hasoffers_offer_id,
				"hasoffers_aff_id" => $hasoffers_aff_id
			);
			$rebill_cycle->create($rbl);
		}
	
		break;
		
	case 3:
		$next_url = $path_index . "/" . "bug-shield-pro-upsell-4.php";
		if (!isset($tracker["has_upsell_3"])) {

			if (do_upsell_charge($tracker, $upsell_price, $upsell_description, $upsell_description, true)) {
				$tracker["has_upsell_3"] = 1;
				$tracker["upsell_3_qty"] = $qty;
				$upsell_qty = $upsell_qty;
				$upsell_description = $upsell_description;
				$upsell_ok = true;
			} else {
				$next_url = $_SERVER['HTTP_REFERER'];
			}
			
			//UPSELL REBILL
			$rebill_cycle = new rebill_cycle;
			$rbl = array(
				"amount" => $cfg['upsell_3_price'],
				"description" => "Rebill - " .$upsell_description,
				"period" => 30,
				"last_payment" => date("Y-m-d H:i:s") ,
				"user_id" => $tracker["user_id"],
				"PAPVisitorId" => $pap_cookie,
				"qty" => 1,
				"hasoffers_offer_id" => $hasoffers_offer_id,
				"hasoffers_aff_id" => $hasoffers_aff_id
			);
			$rebill_cycle->create($rbl);
		}
	
		break;
		
	case 4:
		$next_url = $path_index . "/" . "confirmation.php";
		if (!isset($tracker["has_upsell_4"])) {
			if (do_upsell_charge($tracker, $upsell_price, $upsell_description, $upsell_description, true)) {
				$tracker["has_upsell_4"] = 1;
				$tracker["upsell_4_qty"] = $qty;
				$upsell_qty = $upsell_qty;
				$upsell_description = $upsell_description;
				$upsell_ok = true;
			} else {
				$next_url = $_SERVER['HTTP_REFERER'];
			}
		}
	
		break;
		
	}
	
}

if ($upsell_ok) {

	// add up

	if (!isset($tracker["upsell_price"])) {
		$tracker["upsell_price"] = $upsell_price;
	}
	else {
		$tracker["upsell_price"]+= $upsell_price;
	}

	$tracker["quantity"]+= $upsell_qty;

	// update the order hgere, in case the user leaves the process sooner

	if (isset($tracker['order_id'])) {
		$order = new order;
		$this_order = array_shift($order->get_specific_orders(array(
			intval($tracker['order_id'])
		)));
		$new_description = $this_order->description . (strlen($upsell_description) ? " + " . $upsell_description : "");
		$new_qty = (int)$this_order->qty + $qty;

		// run the update

		$order->update_by_id(intval($tracker['order_id']) , array(
			"qty" => $new_qty,
			"description" => $new_description,
		));
	}

	// $tracker["shipping_price"]+= $upsell_shipping;

	$t->set_data($tracker);
}

header("Location:" . $next_url);
die();