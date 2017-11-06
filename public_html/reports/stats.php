<?php

//$time_start =  microtime(true);

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/conversions.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/" . $cfg['language'] . ".php"); // load language file
$page->template = "../templates/" . $cfg['language'] . "/default.html"; // load template

include_once("../lib/user.class.php");
include_once("../lib/rebill_cycle.class.php");
include_once("../lib/order.class.php");
include_once("../lib/PayBackEnd.php");

include_once("../lib/antifraud.class.php");
include_once("../lib/antifraud.redflag.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if ($menuActiveIndex > 0) {
    $page->blocks['title'] = "Stats";
    $page->blocks['menu'] = get_menu($menuActiveIndex);
    $page->blocks['folder'] = $cfg['site']['folder'];
    $page->blocks['selectLanguage'] = $page->build_language_form();
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "reports/orders.php");
}


//echo "\n\n<!-- Mark 1: ".(microtime(true) - $time_start)."s -->\n\n";
//get min/max
$min = date("Y-m-1");
$max = date("Y-m-t");

$view_params = array(
    "period" => $min . ":" . $max,
    "from_date" => $min,
    "to_date" => $max,
); //the export params
//merge params
if (count($_POST)) {

    foreach ($_POST as $key => $value) {
        //is it a valid param ?
        if (array_key_exists($key, $view_params) && strlen($value)) {
            $view_params[$key] = $value;
        }
    }

    if ($view_params["period"] != "0:0") {
        $parts = explode(":", $view_params["period"]);
        $view_params["from_date"] = $parts[0];
        $view_params["to_date"] = $parts[1];
    }
}


//grab the stats for these dates
$stats = array();

//total

$total_data = single_query_assoc("SELECT SUM(hAmount) as s,count(hKey) as cnt FROM `mem_merchant_history` WHERE hDate BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59'");

//echo "\n\n<!-- Mark 3: ".(microtime(true) - $time_start)."s -->\n\n";

$stats["total"] = round($total_data["s"], 2);
$stats["count"] = $total_data["cnt"];

//subtotal rebills
$subtotal_rebills_data = single_query_assoc("SELECT SUM(hAmount) as s,count(hKey) as cnt FROM `mem_merchant_history` WHERE hDate BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59' AND is_rebill=1");
$stats["rebills"] = round($subtotal_rebills_data["s"], 2);
$stats["rebill_count"] = $subtotal_rebills_data["cnt"];

//echo "\n\n<!-- Mark 3: ".(microtime(true) - $time_start)."s -->\n\n";
//subtotal initials
$subtotal_initials_data = single_query_assoc("SELECT SUM(hAmount) as s FROM `mem_merchant_history` WHERE hDate BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59' AND is_rebill=0"); // AND `refunded_date` IS NULL");
$stats["initials"] = round($subtotal_initials_data["s"], 2);

//echo "\n\n<!-- Mark 4: ".(microtime(true) - $time_start)."s -->\n\n";
//remaining rebills until the end of the period
$rebill_obj = new rebill_cycle;

$stats['rebill_periods'] = array(60, 30);

foreach ($stats['rebill_periods'] as $rebill_period) {

    $stats['remaining_rebills_' . $rebill_period] = $rebill_obj->get_next_rebills($rebill_period, $view_params["to_date"] . " 23:59:59");

    //echo "\n\n<!-- Mark 5: ".(microtime(true) - $time_start)."s -->\n\n";
    //echo "<!-- \n". print_r($stats['remaining_rebills'],1)." \n -->";
    //split by months
    $stats['remaining_rebills_months_' . $rebill_period] = array();
    if (is_array($stats['remaining_rebills_' . $rebill_period])) {
        foreach ($stats['remaining_rebills_' . $rebill_period] as $remaining_rebill) {
            $month = date("F Y", strtotime($remaining_rebill['time']));



            if (!array_key_exists($month, $stats['remaining_rebills_months_' . $rebill_period])) {
                $stats['remaining_rebills_months_' . $rebill_period][$month] = 0;
            }

            $stats['remaining_rebills_months_' . $rebill_period][$month] ++;
        }
    }
}





//customers in interval
//$customers_data_60 = multi_query_assoc("SELECT u.UserID as uid,u.EmailAddress as email,r.amount,r.period FROM `mem_user` u LEFT JOIN mem_rebill_cycle r on u.UserID=r.user_id AND r.period=60 AND r.active=1 WHERE u.CreateTime BETWEEN '".$view_params["from_date"]."' AND '".$view_params["to_date"]." 23:59:59' ORDER BY u.UserID ASC");  
//echo "\n\n<!-- Mark 6: ".(microtime(true) - $time_start)."s -->\n\n";

$customers_data_all_intervals = multi_query_assoc("SELECT u.UserID as uid,u.EmailAddress as email,r.amount,r.period,r.active FROM `mem_user` u LEFT JOIN mem_rebill_cycle r on u.UserID=r.user_id WHERE u.CreateTime BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59' AND r.period IN (" . implode(',', $rebill_obj->get_possible_rebill_periods()) . ") ORDER BY u.UserID ASC");



//echo "\n\n<!-- Mark 7: ".(microtime(true) - $time_start)."s -->\n\n";



$customers_data_365 = multi_query_assoc("SELECT u.UserID as uid,u.EmailAddress as email,r.amount,r.period FROM `mem_user` u LEFT JOIN mem_rebill_cycle r on u.UserID=r.user_id WHERE u.CreateTime BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59' AND r.period=365 AND r.active=1 ORDER BY u.UserID ASC");

//echo "\n\n<!-- Mark 8: ".(microtime(true) - $time_start)."s -->\n\n";

$distinct_customers = array();
$distinct_customer_ids = array();

$distinct_customers_60 = array();
$distinct_customer_ids_60 = array();

$distinct_customers_30 = array();
$distinct_customer_ids_30 = array();

//count the rebills
$rebill_60_number = $rebill_30_number = 0;
$cancelled_60_number = $cancelled_30_number = 0;
foreach ($customers_data_all_intervals as $cd) {

    if ($cd["active"] == 1) {

        if ($cd['period'] == 60)
            $rebill_60_number++;

        if ($cd['period'] == 30)
            $rebill_30_number++;
    } else {

        if ($cd['period'] == 60)
            $cancelled_60_number++;

        if ($cd['period'] == 30)
            $cancelled_30_number++;
    }

    if (!in_array($cd["uid"], $distinct_customer_ids)) {

        $distinct_customers[] = $cd["email"];
        $distinct_customer_ids[] = $cd["uid"];

        if ($cd['period'] == 60) {
            $distinct_customers_60[] = $cd["email"];
            $distinct_customer_ids_60[] = $cd["uid"];
        }

        if ($cd['period'] == 30) {
            $distinct_customers_30[] = $cd["email"];
            $distinct_customer_ids_30[] = $cd["uid"];
        }
    }
}
$rebill_365_number = 0;
$cancelled_365_number = 0;
foreach ($customers_data_365 as $cd) {
    if (strlen($cd["amount"])) {
        $rebill_365_number++;
    } else {
        $cancelled_365_number++;
    }
}

$stats["customer_number"] = count($distinct_customers);

$stats["customer_number_60"] = count($distinct_customers_60);

$stats["customer_number_30"] = count($distinct_customers_30);

$stats["rebill_60_number"] = $rebill_60_number;

$stats["rebill_30_number"] = $rebill_30_number;

$stats["rebill_365_number"] = $rebill_365_number;

if ($stats["customer_number_60"] > 0) {
    $stats["cancelation_rate_60"] = min(round(($cancelled_60_number * 100) / $stats["customer_number_60"], 2), 100);
} else {
    $stats["cancelation_rate_60"] = 0;
}

if ($stats["customer_number_30"] > 0) {
    $stats["cancelation_rate_30"] = min(round(($cancelled_30_number * 100) / $stats["customer_number_30"], 2), 100);
} else {
    $stats["cancelation_rate_30"] = 0;
}

//average per customers
if ($stats["customer_number"] > 0) {
    $stats["average_per_customer"] = round($stats["initials"] / $stats["customer_number"], 2);
} else {
    $stats["average_per_customer"] = 0;
}


//$stats["customer_data"] = $customers_data;
//Count the transaction declines in the period, separated per type of transaction
//direct (initial), upsell or rebill
$decline_types = array("direct", "upsell", "rebill");

$all_transactions = multi_query_assoc('SELECT amount,status,type FROM transaction_log WHERE timestamp BETWEEN "' . $view_params["from_date"] . '" AND "' . $view_params["to_date"] . ' 23:59:59"');

$stats["declines_per_type"] = array();
foreach ($decline_types as $dt) {
    $stats["declines_per_type"][$dt] = array("count" => 0, "decline_count" => 0, "sum" => 0, "percentage" => 0);
}
$stats["count_all_transactions"] = count($all_transactions);

foreach ($all_transactions as $transaction) {
    if (array_key_exists($transaction['type'], $stats["declines_per_type"])) {
        $stats["declines_per_type"][$transaction['type']]["count"]+=1;
        if ($transaction['status'] == 0) {
            $stats["declines_per_type"][$transaction['type']]["decline_count"]+=1;
            $stats["declines_per_type"][$transaction['type']]["sum"]+=$transaction["amount"];
        }
    }
}
//compute percentages
foreach ($stats["declines_per_type"] as $dec_type => $dpt) {
    if ($dpt["decline_count"] > 0) {
        $stats["declines_per_type"][$dec_type]["percentage"] = round(($dpt["decline_count"] * 100) / $dpt["count"], 2);
    }
}

// get the 6 month upsell sales
$six_month_sql = "SELECT SUM(hAmount) AS total, COUNT(hKey) cnt FROM mem_merchant_history WHERE transaction_description='6 Month Supply' AND is_rebill=0 AND hDate BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59'";
$six_month_data = single_query_assoc($six_month_sql);
$stats["six_month_sales"] = $six_month_data['cnt'];
$stats["six_month_total"] = $six_month_data['total'];



//echo "\n\n<!-- Mark 9: ".(microtime(true) - $time_start)."s -->\n\n";
//get number of rebill periods between the start of the search period and the end of it
$start = strtotime($view_params["from_date"]);
$end = /* strtotime($view_params["to_date"]) */time();
$datediff = abs($end - $start);

foreach ($stats['rebill_periods'] as $rebill_period) {


    $rebill_period_in_seconds = $rebill_period * 24 * 60 * 60; //days * hours_in_day*minutes_in_hour*seconds_in_minute
    $periods = ceil($datediff / $rebill_period_in_seconds);
    $stats["periods_" . $rebill_period] = $periods;

    if ($periods > 36) {
        $stats["periods_" . $rebill_period] = "36 (capped, actual number is " . $periods . ")";
        $periods = 36;
    }

    //get canceled rebills in cycles, from these users
    if ($periods > 0) {

        $canceled_rebills = array();

        for ($i = 0; $i < $periods; $i++) {



            $start_time = $start + ($i * $rebill_period_in_seconds);
            $end_time = $start_time + $rebill_period_in_seconds;

            $rebill_canceled_cycle = multi_query_assoc("SELECT u.UserID as uid,u.EmailAddress as email,r.amount,r.period,r.active FROM `mem_user` u LEFT JOIN mem_rebill_cycle r on u.UserID=r.user_id AND r.period=" . $rebill_period . " AND r.active=0 WHERE u.CreateTime BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59'  AND r.cancel_date BETWEEN '" . date("Y-m-d 00:00:00", $start_time) . "' AND '" . date("Y-m-d 00:00:00", $end_time) . "'");


            $amount_charged_in_cycle = single_query_assoc('SELECT SUM(hAmount) as amnt, COUNT(hKey) as cnt from `mem_merchant_history` mh LEFT JOIN `mem_user` u ON mh.user_email=u.EmailAddress LEFT JOIN mem_rebill_cycle r on u.UserID=r.user_id  WHERE mh.is_rebill=1 AND r.period=' . $rebill_period . ' AND u.CreateTime BETWEEN "' . $view_params["from_date"] . '" AND "' . $view_params["to_date"] . ' 23:59:59" AND mh.hDate BETWEEN "' . date("Y-m-d 00:00:00", $start_time) . '" AND "' . date("Y-m-d 00:00:00", $end_time) . '"');

            //echo "<!-- \n"."SELECT u.UserID as uid,u.EmailAddress as email,r.amount,r.period,r.active FROM `mem_user` u LEFT JOIN mem_rebill_cycle r on u.UserID=r.user_id AND r.period=".$rebill_period." AND r.active=0 WHERE u.CreateTime BETWEEN '".$view_params["from_date"]."' AND '".$view_params["to_date"]." 23:59:59'  AND r.cancel_date BETWEEN '".date("Y-m-d 00:00:00",$start_time)."' AND '".date("Y-m-d 00:00:00",$end_time)."'"." \n-->\n";
            //gather unique cancelled user rebills
            $unique_cancelled_rebills = array();
            foreach ($rebill_canceled_cycle as $rcc) {
                if (!in_array($rcc["uid"], $unique_cancelled_rebills)) {
                    $unique_cancelled_rebills[] = $rcc["uid"];
                }
            }


            $canceled_rebills[] = array(
                "start" => $start_time,
                "end" => $end_time,
                "count" => count($rebill_canceled_cycle),
                "unique_count" => count($unique_cancelled_rebills),
                "percentage" => $stats["customer_number_" . $rebill_period] > 0 ? round((count($rebill_canceled_cycle) * 100) / $stats["customer_number_" . $rebill_period], 2) : 0,
                "amount" => round($amount_charged_in_cycle["amnt"], 2),
                "charged_count" => $amount_charged_in_cycle["cnt"]
            );
        }
        $stats["canceled_rebills_" . $rebill_period] = $canceled_rebills;
    }
}

//echo "\n\n<!-- Mark 10: ".(microtime(true) - $time_start)."s -->\n\n";
//grab some bank stats
$payback = new PayBackEnd;
$merchants = array_merge($payback->get_merchant("1", true), $payback->get_merchant("0", true));
$stats["merchants"] = array();
foreach ($merchants as $merchant) {
    //get transaction count, transaction sum
    $bank_transaction = single_query_assoc(
            "SELECT IFNULL(SUM(hAmount),0) AS amount,COUNT(hKey) as transaction_number  
			FROM mem_merchant_history 
			WHERE BankID='" . $merchant["BankID"] . "' 
      AND hDate BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59'
      "
    );

    $bank_transaction_only_rebills = single_query_assoc(
            "SELECT IFNULL(SUM(hAmount),0) AS amount,COUNT(hKey) as transaction_number  
			FROM mem_merchant_history 
			WHERE BankID='" . $merchant["BankID"] . "' 
      AND hDate BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59' AND is_rebill=1
      "
    );




    //echo "\n\n<!-- Mark 11: ".(microtime(true) - $time_start)."s -->\n\n";

    $sql = "SELECT h.hKey,h.hAmount as amnt,u.cardnumber,h.is_rebill,h.hDate,h.user_email FROM mem_merchant_history h LEFT JOIN mem_user u ON h.user_email=u.Email WHERE h.BankID='" . $merchant["BankID"] . "' AND h.hDate BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59'";

    $transactions = multi_query_assoc($sql);

//echo "\n\n<!-- Mark 12: ".(microtime(true) - $time_start)."s -->\n\n";    

    $total_per_brands = array();

    $card_brands = array();

    $initials = array();
    $initials_amount = 0.0;

    $upsells = array();


    foreach ($transactions as $transaction) {

        $brand = check_cc($user->Cipher->decrypt($transaction["cardnumber"]));
        if (!array_key_exists($brand, $card_brands)) {
            $card_brands[$brand] = 1;
        } else {
            $card_brands[$brand] ++;
        }

        if (!array_key_exists($brand, $total_per_brands)) {
            $total_per_brands[$brand] = $transaction["amnt"];
        } else {
            $total_per_brands[$brand]+=$transaction["amnt"];
        }


        $transaction_hash = $transaction['user_email'];

        if (!in_array($transaction_hash, $initials) && $transaction["is_rebill"] == 0) {
            //this looks like it is an initial
            $initials[] = $transaction_hash;
            $initials_amount+=$transaction["amnt"];
        } else {
            if ($transaction["is_rebill"] == 0)
                $upsells[] = $transaction;
        }
    }

    //echo "<!-- \n M: ".$merchant["BankID"]."".print_r($upsells,1)." -->\n";


    if ($bank_transaction["transaction_number"] > 0) {

        //echo "<!-- \n M: ".$merchant["BankName"]." ".$bank_transaction["amount"]." ".$initials_amount." ".$bank_transaction_only_rebills["amount"]."-->\n";

        $_upsell_amount = round($bank_transaction["amount"] - $initials_amount - $bank_transaction_only_rebills["amount"], 2);

        if ($_upsell_amount <= 0) {
            $_upsell_amount = "0.00";
        }

        $stats["merchants"][] = array(
            "name" => $merchant["BankName"],
            "amount" => round($bank_transaction["amount"], 2),
            "transaction_number" => $bank_transaction["transaction_number"],
            "rebill_amount" => $bank_transaction_only_rebills["amount"],
            "rebill_transaction_number" => $bank_transaction_only_rebills["transaction_number"],
            "initials" => count($initials),
            "initial_amount" => round($initials_amount, 2),
            "upsells" => $bank_transaction["transaction_number"] - count($initials) - $bank_transaction_only_rebills["transaction_number"],
            "upsell_amount" => $_upsell_amount,
            "brands" => $card_brands,
            "brands_totals" => $total_per_brands,
        );
    }
}


//echo "\n\n<!-- Mark 13: ".(microtime(true) - $time_start)."s -->\n\n";
//get hasoffers trackers
$affiliates = multi_query_assoc("
        SELECT 
            DISTINCT u.hasoffers_aff_id, 
            COUNT(DISTINCT u.id) as count,
            sum(hAmount) as total
        FROM `mem_order` u 
        LEFT JOIN mem_merchant_history h ON h.user_email=u.email 
        WHERE `hasoffers_aff_id`>0 AND `date` 
        BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59' GROUP BY `hasoffers_aff_id`");

//echo "\n\n<!-- Mark 14: ".(microtime(true) - $time_start)."s -->\n\n";

$stats["affiliates"] = $affiliates;


//refunds
/*
  //these variant gather refunds only for transactions that happened in the selected period
  $refunds_in_period = single_query_assoc("SELECT COUNT(h.hKey) as k,SUM(r.refund_amount) as total FROM `mem_merchant_history` h LEFT JOIN mem_refund_history r ON r.refund_date=h.refunded_date WHERE `hDate` BETWEEN '".$view_params["from_date"]."' AND '".$view_params["to_date"]." 23:59:59' and is_rebill=0 AND `refunded_date` IS NOT NULL");

  $refunds_in_period_rebills = single_query_assoc("SELECT COUNT(h.hKey) as k,SUM(r.refund_amount) as total FROM `mem_merchant_history` h LEFT JOIN mem_refund_history r ON r.refund_date=h.refunded_date WHERE `hDate` BETWEEN '".$view_params["from_date"]."' AND '".$view_params["to_date"]." 23:59:59' and is_rebill=1 AND `refunded_date` IS NOT NULL");
 */


//this variant gathers refunds that happened in the selected period, no matter of the original transaction time
$refunds_in_period = single_query_assoc("SELECT COUNT(h.hKey) as k,SUM(r.refund_amount) as total FROM `mem_merchant_history` h LEFT JOIN mem_refund_history r ON r.trans_id=h.transaction_id WHERE `refunded_date` BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59' and is_rebill=0 AND `refunded_date` IS NOT NULL");

//echo "\n\n<!-- Mark 15: ".(microtime(true) - $time_start)."s -->\n\n";

$refunds_in_period_rebills = single_query_assoc("SELECT COUNT(h.hKey) as k,SUM(r.refund_amount) as total FROM `mem_merchant_history` h LEFT JOIN mem_refund_history r ON r.trans_id=h.transaction_id WHERE `refunded_date` BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59' and is_rebill=1 AND `refunded_date` IS NOT NULL");

//echo "\n\n<!-- Mark 16: ".(microtime(true) - $time_start)."s -->\n\n";
//echo "<!-- SELECT COUNT(h.hKey) as k,SUM(r.refund_amount) as total FROM `mem_merchant_history` h LEFT JOIN mem_refund_history r ON r.refund_date=h.refunded_date WHERE `refunded_date` BETWEEN '".$view_params["from_date"]."' AND '".$view_params["to_date"]." 23:59:59' and is_rebill=0 AND `refunded_date` IS NOT NULL -->";
//SELECT h.hKey as k,r.refund_amount as amount FROM `mem_merchant_history` h LEFT JOIN mem_refund_history r ON r.refund_date=h.refunded_date WHERE `hDate` BETWEEN '2013-05-1' AND '2013-05-31 23:59:59' and `refunded_date` IS NOT NULL
// $refunds = single_query_assoc("SELECT SUM(refund_amount) as total,COUNT(`ref_history_id`) as k FROM `mem_refund_history` WHERE `refund_date` BETWEEN '".$view_params["from_date"]."' AND '".$view_params["to_date"]." 23:59:59'");
//echo "<pre>".print_r($refunds_total,1)."</pre>";
/*
  $stats["amount_refunded"] = round($refunds["total"],2);
  $stats["refund_count"] = $refunds["k"];

  //percentage of money refunded, out of the total
  $stats["refund_percentage"] = round(($refunds["total"]*100)/$stats["total"],2);
  $stats["net_total"] = round($stats["total"]-$refunds["total"],2);
 */
$stats["amount_refunded"] = $refunds_in_period["total"];
$stats["refund_count"] = $refunds_in_period["k"];

$stats["amount_refunded_rebills"] = $refunds_in_period_rebills["total"];
$stats["refund_count_rebills"] = $refunds_in_period_rebills["k"];

//percentage of money refunded, out of the total
if ($stats["total"] > 0) {
    $stats["refund_percentage"] = round((($refunds_in_period["total"] + $refunds_in_period_rebills["total"]) * 100) / $stats["total"], 2);
} else {
    $stats["refund_percentage"] = 0;
}

$stats["net_total"] = round($stats["total"] - $refunds_in_period["total"] - $refunds_in_period_rebills["total"], 2);

$stats["net_rebills"] = round($stats["rebills"] - $refunds_in_period_rebills["total"], 2);
$stats["net_initials"] = round($stats["initials"] - $refunds_in_period["total"], 2);









//$data = $order->get_orders_by_status_date_restricted($view_params["status"],$view_params["from_date"],$view_params["to_date"]);
//$p_data = "<pre>".print_r($data,1)."</pre>";

$data = array();



$page->blocks['content'] = markup($stats, $view_params);

//echo "\n\n<!-- Mark 17: ".(microtime(true) - $time_start)."s -->\n\n";

$page->construct_page();  // construct html page
$page->output_page();   // output page

close_database($con);

function markup($data, $params = array()) {

    //define the period dates
    $last_month = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
    $last_year = mktime(0, 0, 0, 1, 1, date("Y") - 1);
    $periods = array(
        array("txt" => "Custom", "val" => "0:0"),
        array("txt" => "This Month", "val" => date("Y-m-1") . ":" . date("Y-m-t")),
        array("txt" => "Last Month", "val" => date("Y-m-1", $last_month) . ":" . date("Y-m-t", $last_month)),
        array("txt" => "This Year", "val" => date("Y-1-1") . ":" . date("Y-12-31")),
        array("txt" => "Last Year", "val" => date("Y-1-1", $last_year) . ":" . date("Y-12-31", $last_year)),
    );

    for ($j = 2; $j < 8; $j++) {
        $dt = mktime(0, 0, 0, date("m") - $j, 1, date("Y"));
        $periods[] = array("txt" => date("F Y", $dt), "val" => date("Y-m-1", $dt) . ":" . date("Y-m-t", $dt));
    }

    $antifraud = new antifraud;

    global $cfg;

    $t = "<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/jquery-1.4.2.min.js'></script>\n";
    $t.= "<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/reports/stats.js?4'></script>\n";

    $t.= "<div class='listContent'>\n";



    $current_date = "";

    $t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\">Stats</td>\n";
    $t.= "<td align='left'>Server Time:" . date("d-m-Y H:i:s") . "</td>\n";
    $t.= "<td align='right'></td>\n";
    $t.= "</tr>\n";
    $t.= "</table>\n";

    $t.= "<form action='' method='POST' id='stats_form'>\n";



    $t .= "<div class='export_wrapper' style='padding-top:10px;'>\n";

    $t.= "<h3>Settings:</h3>";

    $t.= "Period: ";
    $t.= "<select name='period'>\n";
    foreach ($periods as $period) {
        $t.= "<option value='" . $period["val"] . "'" . ($params['period'] == $period["val"] ? " selected" : "") . ">" . $period["txt"] . "</option>";
    }
    $t.= "</select>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";

    $t.= "From: ";
    $t.= "<input type='text' name='from_date' id='from_date' value='" . $params["from_date"] . "' /> <img src='" . $cfg['site']['folder'] . "images/calendar.gif' border='0' id='from_date_trigger' align='absmiddle'>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";


    $t.= "To: ";
    $t.= "<input type='text' name='to_date' id='to_date' value='" . $params["to_date"] . "' /> <img src='" . $cfg['site']['folder'] . "images/calendar.gif' border='0' id='to_date_trigger' align='absmiddle'>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";


    $t .= "<input type='submit' name='viewBtn' value='View' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='view_now();'>\n";


    $t.= "</div>\n";


    $t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\"></td>\n";
    $t.= "</tr>\n";
    $t.= "</table>\n";
    setlocale(LC_MONETARY, 'en_US');
    $t.= "<h2>For the selected period:</h2>\n";
    $t.= "<div class='stats_right_toggler'><button onclick='$(this).parent().next(\".stats_box_right\").slideToggle();return false;'>Toggle Merchant Data</button></div>\n";
    $t.= "<div class='stats_box_right'>\n";
    $t.= "<span class='stats_label_merchant'>Merchant Data</span>\n";

    $t.= "<table cellspacing=1 class='merchant_stats_data'>\n";
//merchant table header
    $t.= "<tr>\n";
    $t.= "<th>Merchant Acct</th>\n";
    $t.= "<th>Total</th>\n";
    $t.= "<th>Transactions</th>\n";
    $t.= "<th>Inital Transactions</th>\n";
    $t.= "<th>Upsell Transactions</th>\n";
    $t.= "<th>Rebill Transactions</th>\n";
    $t.= "<th>Visa</th>\n";
    $t.= "<th>Mastercard</th>\n";
    $t.= "<th>Amex</th>\n";
    $t.= "<th>Discover</th>\n";
    $t.= "</tr>\n";
    $amount_total = $amount_rebill_total = $amount_initial_total = $amount_upsell_total = $amount_visa = $amount_mastercard = $amount_amex = $amount_jcb = $transaction_total = $transaction_rebill_total = $transaction_initial_total = $transaction_upsell_total = $transaction_visa = $transaction_mastercard = $transaction_amex = $transaction_discover = 0;

    foreach ($data["merchants"] as $merchant) {


        $t.= "<tr>\n";
        $t.= "<td>" . $merchant["name"] . "</td>\n";
        $t.= "<td>" . money_format('%i', $merchant["amount"]) . "</td>\n";
        $t.= "<td>" . $merchant["transaction_number"] . "</td>\n";

        $t.= "<td>" . $merchant["initials"] . " / " . money_format('%i', $merchant["initial_amount"]) . "</td>\n";

        $t.= "<td>" . $merchant["upsells"] . " / " . money_format('%i', $merchant["upsell_amount"]) . "</td>\n";

        $t.= "<td>" . $merchant["rebill_transaction_number"] . " / " . money_format('%i', $merchant["rebill_amount"]) . "</td>\n";
        $t.= "<td>" . (isset($merchant["brands"]["Visa"]) ? $merchant["brands"]["Visa"] . " / " . money_format('%i', $merchant["brands_totals"]["Visa"]) : "0") . "</td>\n";
        $t.= "<td>" . (isset($merchant["brands"]["Mastercard"]) ? $merchant["brands"]["Mastercard"] . " / " . money_format('%i', $merchant["brands_totals"]["Mastercard"]) : "0") . "</td>\n";
        $t.= "<td>" . (isset($merchant["brands"]["American Express"]) ? $merchant["brands"]["American Express"] . " / " . money_format('%i', $merchant["brands_totals"]["American Express"]) : "0") . "</td>\n";
        $t.= "<td>" . (isset($merchant["brands"]["Discover"]) ? $merchant["brands"]["Discover"] . " / " . money_format('%i', $merchant["brands_totals"]["Discover"]) : "0") . "</td>\n";
        $t.= "</tr>\n";


        $amount_total+=$merchant["amount"];
        $amount_rebill_total+=$merchant["rebill_amount"];

        $amount_initial_total+=$merchant["initial_amount"];
        $amount_upsell_total+=$merchant["upsell_amount"];

        $amount_visa+= (isset($merchant["brands_totals"]["Visa"]) ? $merchant["brands_totals"]["Visa"] : 0.0);
        $amount_mastercard+= (isset($merchant["brands_totals"]["Mastercard"]) ? $merchant["brands_totals"]["Mastercard"] : 0.0);
        $amount_amex+= (isset($merchant["brands_totals"]["American Express"]) ? $merchant["brands_totals"]["American Express"] : 0.0);
        $amount_discover+= (isset($merchant["brands_totals"]["Discover"]) ? $merchant["brands_totals"]["Discover"] : 0.0);


        $transaction_total+=$merchant["transaction_number"];
        $transaction_rebill_total+=$merchant["rebill_transaction_number"];

        $transaction_initial_total+=$merchant["initials"];
        $transaction_upsell_total+=$merchant["upsells"];

        $transaction_visa+=(isset($merchant["brands"]["Visa"]) ? $merchant["brands"]["Visa"] : 0);
        $transaction_mastercard+=(isset($merchant["brands"]["Mastercard"]) ? $merchant["brands"]["Mastercard"] : 0);
        $transaction_amex+=(isset($merchant["brands"]["American Express"]) ? $merchant["brands"]["American Express"] : 0);
        $transaction_discover+=(isset($merchant["brands"]["Discover"]) ? $merchant["brands"]["Discover"] : 0);
    }

    $t.= "<tr>\n";
    $t.= "<td>TOTALS</td>\n";
    $t.= "<td>" . money_format('%i', $amount_total) . "</td>\n";
    $t.= "<td>" . $transaction_total . "</td>\n";

    $t.= "<td>" . $transaction_initial_total . " / " . money_format('%i', $amount_initial_total) . "</td>\n";
    $t.= "<td>" . $transaction_upsell_total . " / " . money_format('%i', $amount_upsell_total) . "</td>\n";

    $t.= "<td>" . $transaction_rebill_total . " / " . money_format('%i', $amount_rebill_total) . "</td>\n";



    $t.= "<td>" . $transaction_visa . " / " . money_format('%i', $amount_visa) . "</td>\n";
    $t.= "<td>" . $transaction_mastercard . " / " . money_format('%i', $amount_mastercard) . "</td>\n";
    $t.= "<td>" . $transaction_amex . " / " . money_format('%i', $amount_amex) . "</td>\n";
    $t.= "<td>" . $transaction_discover . " / " . money_format('%i', $amount_discover) . "</td>\n";
    $t.= "</tr>\n";

    $t.= "</table>\n";

    /*
      $t.= "<div>\n";
      $t.= "<strong>Total</strong>:\n";
      $t.= "<span>".money_format('%i',$amount_total)." from ".$transaction_total." transactions</span>\n";
      $t.= "</div>\n";
     */



//Affiliate data
    if (isset($data["affiliates"]) && is_array($data["affiliates"]) && count($data["affiliates"])) {

        $affiliate_total_number = 0;
        $affiliate_total_sales = 0;
        $affiliate_total_money = 0.0;




        $t.= "<span class='stats_label_merchant'>Affiliate Data</span>\n";

        $t.= "<table cellspacing=1 class='merchant_stats_data'>\n";
//merchant table header
        $t.= "<tr>\n";
        $t.= "<th>Affiliate ID</th>\n";
        $t.= "<th>Sales</th>\n";
        $t.= "<th>Total</th>\n";
        $t.= "<th>Note</th>\n";
        $t.= "<th>Action</th>\n";
        $t.= "<th></th>\n";
        $t.= "</tr>\n";
        foreach ($data["affiliates"] as $affiliate) {

            /**
             * Check Red Flag High Light
             */
            $affData = single_query_assoc("SELECT * FROM `mem_affiliate` WHERE `aff_id` = {$affiliate["hasoffers_aff_id"]}");
            $affClassName = $antifraud->check_bin_affiliate_rules($affiliate["hasoffers_aff_id"]);

            /**
             * Check Yello Flag High Light
             */
            $yellowData = single_query_assoc("SELECT sum(fraudulent_flag) as total_fraudulent, sum(fraudulent_investigated) as total_investigated  FROM `mem_user` WHERE `hasoffers_aff_id` = {$affiliate["hasoffers_aff_id"]}");

            //echo "\n\n<!-- Mark 14: ".(microtime(true) - $time_start)."s -->\n\n";



            $t.= "<tr class=\"" . ($yellowData["total_fraudulent"] > $yellowData["total_investigated"] ? "yellow-flag" : "") . " " . (($affClassName == "red" && $affData['ignore_highlight'] != 1) ? "red-flag" : "") . "\"  data-highlight=\"{$affClassName}\">\n";
            $t.= "<td>" . $affiliate["hasoffers_aff_id"] . "</td>\n";
            $t.= "<td>" . $affiliate["count"] . "</td>\n";
            $t.= "<td>" . money_format('%i', $affiliate["total"]) . "</td>\n";
            //$affData['note'] = "asdklfjalskdjflkasjdf kasjio aidjf jasdkf jlkasdf";
            $t.= "<td>
                    <div class=\"notes\"><span id='notes_{$affiliate["hasoffers_aff_id"]}'>{$affData['note']}</span><a href='#' class='edit_notes' rel='{$affiliate["hasoffers_aff_id"]}'>[edit]</a> 
                    <span style='display:none;'><a href='#' class='save_notes' rel='{$affiliate["hasoffers_aff_id"]}'>[save]</a> | <a href='#' class='cancel_notes' rel='{$affiliate["hasoffers_aff_id"]}'>[cancel]
                    </a><br/>
                    </span
                    </div>
                  </td>\n";

            if ($yellowData["total_fraudulent"] > $yellowData["total_investigated"]) {
                $t.= "<td><a class='make_investigated' rel='" . $affiliate["hasoffers_aff_id"] . "' from_date='" . $params["from_date"] . "' to_date='" . $params["to_date"] . "'  href='#'>unFlag Yellow</a></td>\n";
            } else {
                $t.= "<td></td>\n";
            }
            $checked = $affData['ignore_highlight'] == 1 ? "checked=\"checked\"" : "";
            $t.= "<td><input type=\"checkbox\" class=\"highlight_check\" rel=\"{$affiliate["hasoffers_aff_id"]}\" id=\"ignore_{$affiliate["hasoffers_aff_id"]}\" {$checked}/><label for=\"ignore_{$affiliate["hasoffers_aff_id"]}\">Ignore highlight</label></td>\n";

            $t.= "</tr>\n";

            $affiliate_total_number++;
            $affiliate_total_sales+= $affiliate["count"];
            $affiliate_total_money+= $affiliate["total"];
        }

//show the totals
        $t.= "<tr>\n";
        $t.= "<td>TOTALS for <strong>" . $affiliate_total_number . " affiliates</strong></td>\n";
        $t.= "<td><strong>" . $affiliate_total_sales . "</strong></td>\n";
        $t.= "<td><strong>" . money_format('%i', $affiliate_total_money) . "</strong></td>\n";
        $t.= "<td></td>\n";
        $t.= "<td></td>\n";
        $t.= "<td></td>\n";
        $t.= "</tr>\n";

        $t.= "</table>\n";
    }
//End affiliate data

    $t.= "</div>\n";


    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Gross revenue</span>\n";
    $t.= "<span class='stats_value'>" . money_format('%i', $data["total"]) . "</span>\n";
    $t.= "</div>\n";


    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Gross Rebill Amount Processed</span>\n";
    $t.= "<span class='stats_value'>" . money_format('%i', $data["rebills"]) . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Gross Initial Sales</span>\n";
    $t.= "<span class='stats_value'>" . money_format('%i', $data["initials"]) . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>NET Rebill Amount Processed(Gross Rebill Amount Processed - Amount Refunded for rebills)</span>\n";
    $t.= "<span class='stats_value'>" . money_format('%i', $data["net_rebills"]) . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>NET Initial Sales(Gross Initial Sales - Amount Refunded)</span>\n";
    $t.= "<span class='stats_value'>" . money_format('%i', $data["net_initials"]) . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Average per customer</span>\n";
    $t.= "<span class='stats_value'>" . money_format('%i', $data["average_per_customer"]) . "</span>\n";
    $t.= "</div>\n";





    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Registered Customers</span>\n";
    $t.= "<span class='stats_value'>" . $data["customer_number"] . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>2 Month Rebills</span>\n";
    $t.= "<span class='stats_value'>" . $data["rebill_60_number"] . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>2 Month Cancellation Rate</span>\n";
    $t.= "<span class='stats_value'>" . $data["cancelation_rate_60"] . "%</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>1 Month Rebills</span>\n";
    $t.= "<span class='stats_value'>" . $data["rebill_30_number"] . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>1 Month Cancellation Rate</span>\n";
    $t.= "<span class='stats_value'>" . $data["cancelation_rate_30"] . "%</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Yearly Rebills</span>\n";
    $t.= "<span class='stats_value'>" . $data["rebill_365_number"] . "</span>\n";
    $t.= "</div>\n";


    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Amount Refunded</span>\n";
    $t.= "<span class='stats_value'>" . money_format('%i', $data["amount_refunded"]) . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Number of refunds</span>\n";
    $t.= "<span class='stats_value'>" . $data["refund_count"] . "</span>\n";
    $t.= "</div>\n";


    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Amount Refunded for rebills</span>\n";
    $t.= "<span class='stats_value'>" . money_format('%i', $data["amount_refunded_rebills"]) . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Number of refunds for rebills</span>\n";
    $t.= "<span class='stats_value'>" . $data["refund_count_rebills"] . "</span>\n";
    $t.= "</div>\n";


    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Refund percentage</span>\n";
    $t.= "<span class='stats_value'>" . $data["refund_percentage"] . "%</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Net Total (Total - Refunds - Rebill Refunds)</span>\n";
    $t.= "<span class='stats_value'>" . money_format('%i', $data["net_total"]) . "</span>\n";
    $t.= "</div>\n";

    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Number of 6 month upsell</span>\n";
    $t.= "<span class='stats_value'>" . $data["six_month_sales"] . " (USD " . ($data["six_month_total"] > 0 ? $data["six_month_total"] : 0) . ")</span>\n";
    $t.= "</div>\n";


    $t.= "<div class='stats_box'>\n";
    $t.= "<span class='stats_label'>Number of transactions of all types (Includes declines)</span>\n";
    $t.= "<span class='stats_value'>" . $data["count_all_transactions"] . "</span>\n";
    $t.= "</div>\n";
//declines
    $decline_types = array("Initial Sales" => "direct", "Upsells" => "upsell", "Rebills" => "rebill");

    foreach ($decline_types as $label => $decline_type) {

        $t.= "<div class='stats_box'>\n";
        $t.= "<span class='stats_label'>Number of " . $label . " transactions</span>\n";
        $t.= "<span class='stats_value'>" . $data['declines_per_type'][$decline_type]['count'] . "</span>\n";
        $t.= "</div>\n";

        $t.= "<div class='stats_box'>\n";
        $t.= "<span class='stats_label'>Declines for " . $label . "</span>\n";
        $t.= "<span class='stats_value'>" . $data['declines_per_type'][$decline_type]['percentage'] . "% (" . $data['declines_per_type'][$decline_type]['decline_count'] . " / " . money_format('%i', $data['declines_per_type'][$decline_type]['sum']) . ")</span>\n";
        $t.= "</div>\n";
    }


    foreach ($data['rebill_periods'] as $rebill_period) {

        $t.= "<div class='stats_box'>\n";
        $t.= "<span class='stats_label'>Remaining rebills (" . $rebill_period . " days)</span>\n";
        $t.= "<span class='stats_value'>" . count($data['remaining_rebills_' . $rebill_period]) . (count($data['remaining_rebills_' . $rebill_period]) > 0 ? " (detailed below) " : "") . "</span>\n";
        $t.= "</div>\n";

//remaining rebills per months
        foreach ($data['remaining_rebills_months_' . $rebill_period] as $month => $count) {
            $t.= "<div class='stats_box'>\n";
            $t.= "<span class='stats_label'>Remaining rebills (" . $rebill_period . " days) " . $month . "</span>\n";
            $t.= "<span class='stats_value'>" . $count . "</span>\n";
            $t.= "</div>\n";
        }
    }

//$t.= "<!-- ".print_r($data['remaining_rebills'],1)." -->\n";








    foreach ($data['rebill_periods'] as $rebill_period) {

        $t.= "<div class='stats_box'>\n";
        $t.= "<span class='stats_label'>Number of rebill cycles (" . $rebill_period . " days) between then and now</span>\n";
        $t.= "<span class='stats_value'>" . $data["periods_" . $rebill_period] . "</span>\n";
        $t.= "</div>\n";

        if ($data["periods_" . $rebill_period] > 0) {
            foreach ($data["canceled_rebills_" . $rebill_period] as $index => $cycle) {

                if ($index > -1) {

                    $t.= "<div class='stats_box'>\n";

                    $t.= "<span class='stats_label'>Cycle " . ($index + 1) . " (" . date("Y/m/d", $cycle["start"]) . "-" . date("Y/m/d", $cycle["end"]) . ")</span>\n";

                    //$t.= "<br />\n";

                    $t.= "<span class='stats_label'>Amount charged</span>\n";
                    $t.= "<span class='stats_value'>" . money_format('%i', $cycle["amount"]) . " (" . $cycle["charged_count"] . " charges)</span>\n";

                    $t.= "<span class='stats_label'>Canceled rebills</span>\n";
                    $t.= "<span class='stats_value'>" . $cycle["percentage"] . "% (" . $cycle["count"] . " cancellations/" . $cycle["unique_count"] . " users)</span>\n";
                    $t.= "</div>\n";
                }
            }
        }
    }

//$t.= "<pre>".print_r($data,1)."</pre>";


    $t.= "</div>\n";

    return $t;
}
