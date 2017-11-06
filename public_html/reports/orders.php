<?php

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

require_once("../lib/antifraud.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if ($menuActiveIndex > 0) {
    $page->blocks['title'] = $lang['title']['manageUsers'];
    $page->blocks['menu'] = get_menu($menuActiveIndex);
    $page->blocks['folder'] = $cfg['site']['folder'];
    $page->blocks['selectLanguage'] = $page->build_language_form();
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "reports/orders.php");
}


$order = new order;

//get min/max
$min_max = $order->get_min_max_dates();
$min = $min_max["min"];
$max = $min_max["max"];

$view_params = array(
    "status" => "not shipped",
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
}




$data = $order->get_orders_by_status_date_restricted($view_params["status"], $view_params["from_date"], $view_params["to_date"], 'user_id', 'asc');

//$p_data = "<pre>".print_r($data,1)."</pre>";



$page->blocks['content'] = markup($data, $view_params);

$page->construct_page();  // construct html page
$page->output_page();   // output page

close_database($con);

function markup($data, $params = array()) {



    global $cfg;

    $t = "<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/jquery-1.4.2.min.js'></script>\n";
    $t.= '<script type="text/javascript" src="' . $cfg['site']['folder'] . 'js/jquery-ui.min-1.8.8.js"></script>
<link rel="stylesheet" type="text/css" href="' . $cfg['site']['folder'] . 'styles/jquery-ui-1.8.8.css"  />';

    $t.= "\n<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/reports/orders.js'></script>\n";

    $t.= "<div class='listContent'>\n";



    $current_date = "";

    $t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\">Orders</td>\n";
    $t.= "<td align='left'>Server Time:" . date("d-m-Y H:i:s") . "</td>\n";
    $t.= "<td align='right'>Total listed orders:" . count($data) . "</td>\n";
    $t.= "</tr>\n";
    $t.= "</table>\n";

    $t.= "<form action='' method='POST' id='order_form'>\n";



    $t .= "<div class='export_wrapper' style='padding-top:10px;'>\n";

    $t.= "<h3>Settings:</h3>";

    $t.= "Status: ";
    $t.= "<select name='status'>\n";
    $t.= "<option value='not shipped'" . ($params['status'] == "not shipped" ? " selected" : "") . ">Outstanding Orders</option>";
    $t.= "<option value='shipped'" . ($params['status'] == "shipped" ? " selected" : "") . ">Shipped Orders</option>";
    $t.= "</select>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";

    $t.= "From: ";
    $t.= "<input type='text' name='from_date' id='from_date' value='" . $params["from_date"] . "' /> <img src='" . $cfg['site']['folder'] . "images/calendar.gif' border='0' id='from_date_trigger' align='absmiddle'>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";


    $t.= "To: ";
    $t.= "<input type='text' name='to_date' id='to_date' value='" . $params["to_date"] . "' /> <img src='" . $cfg['site']['folder'] . "images/calendar.gif' border='0' id='to_date_trigger' align='absmiddle'>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";


    $t .= "<input type='submit' name='viewBtn' value='View' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='view_now();'>\n";

    $t .= "<input type='submit' name='exportBtn' value='Export' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='export_now();'>\n";

    $t .= "<input type='submit' name='exportBtnv2' value='Export V2' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='export_nowv2();'>\n";

    $t .= "<input type='submit' name='exportStaging' value='Export Staging' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='export_now_staging();'>\n";

    $t .= "<input type='submit' name='exportibs' value='Export IBS' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='export_now_ibs();'>\n";


    $t.= "</div>\n";


    $t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\"></td>\n";
    $t.= "</tr>\n";
    $t.= "</table>\n";


    if (count($data)) {



        $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";

        $t.= "<tr class='captionRow'>\n";
        $t.= "<td><input type='checkbox' id='sel_all' /></td>";
        $t.= "<td>SubID</td>";
        $t.= "<td>Rebills</td>";
        $t.= "<td>QTY</td>";
        $t.= "<td>Description</td>";
        $t.= "<td>Fraud Info</td>";
        $t.= "<td>Order #</td>";
        $t.= "<td>Date</td>";
        $t.= "<td>First name</td>";
        $t.= "<td>Last name</td>";
        $t.= "<td>Address</td>";
        $t.= "<td>Country</td>";
        $t.= "<td>City</td>";
        $t.= "<td>State</td>";
        $t.= "<td>Zip</td>";
        $t.= "<td>Tracking #</td>";
        $t.= "<td>Action</td>";
        $t.= "</tr>";


        foreach ($data as $key => $d) {

            $tracking_number = strlen($d->tracking_number) ? $d->tracking_number : "click to assign";
            $tracking_number_value = strlen($d->tracking_number) ? $d->tracking_number : "";

            $user_rebills = rebill_cycle::user_rebills($d->user_id);

            $rebill_color = "black";
            $rebill_string = "NONE";
            if (count($user_rebills)) {
                $rebill_string = "";
                if (count($user_rebills) > 1) {
                    $rebill_color = "red";
                }

                foreach ($user_rebills as $kk => $ur) {

                    if (!in_array($ur->period, array(60, 365))) {
                        $rebill_color = "red";
                    }
                    $rebill_string .= ($kk > 0 ? ' <br /> ' : '') . '$' . $ur->amount . ' / ' . ($ur->period == 365 ? 'year' : $ur->period . ' days');
                }
            }

            if ($key % 2 == 0) {
                $t.= "<tr class=\"dataRow1 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow1 drow'\">\n";
            } else {
                $t.= "<tr class=\"dataRow2 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow2 drow'\">\n";
            }

            $t.= "<td><input class='orderid' type='checkbox' name='orderids[]' value='" . $d->user_id . "' checked='checked' /></td>";
            $t.= "<td>" . $d->subid . "</td>";
            $t.= "<td>" . "<span style='color:" . $rebill_color . "'><strong>" . $rebill_string . "</strong></span>" . "</td>";
            $t.= "<td>" . $d->qty . "</td>";
            $t.= "<td>" . $d->description . "</td>";
            $t.= "<td><a class='fraud_info' rel='" . $d->id . "' data-uid='" . $d->user_id . "' href='#'>Fraud info</a></td>";
            $t.= "<td>" . $d->user_id . "</td>";
            $t.= "<td>" . date("Y-m-d", strtotime($d->date)) . "</td>";
            $t.= "<td>" . $d->firstname . "</td>";
            $t.= "<td>" . $d->lastname . "</td>";
            $t.= "<td>" . $d->address . "</td>";
            $t.= "<td>" . $d->country . "</td>";
            $t.= "<td>" . $d->city . "</td>";
            $t.= "<td>" . $d->state . "</td>";
            $t.= "<td>" . $d->zip . "</td>";

            $t.= "<td><a href='#' class='tracking_no'>" . $tracking_number . "</a> <div style='display:none;'><input type='text' id='tracking_no_" . $d->id . "' name='tracking_no_" . $d->id . "'  value='" . $tracking_number_value . "' /> <a href='#' class='tracking_no_save' rel='" . $d->id . "'>Save</a> | <a href='#' class='tracking_no_cancel' rel='" . $d->id . "'>Cancel</a></div></td>";

            if ($params["status"] == "not shipped") {
                $t.= "<td><a class='ship_order' href='#' rel='" . $d->id . "'>Ship</a> <div style='display:none;'>Date: <input type='text' id='ship_" . $d->id . "' name='ship_date_" . $d->id . "'  value='' /> <a href='#' class='ship_save' rel='" . $d->id . "'>Ship</a> | <a href='#' class='ship_cancel' rel='" . $d->id . "'>Cancel</a></div></td>";
            } else {

                $t.= "<td><a class='unship_order' href='#' rel='" . $d->id . "'>Shipped on " . $d->shipment_date . ".click to undo</a> </td>";
            }

            $t.="</tr>\n";
        }


        $t.= "</table>\n";
        $t .= "</form>\n";

//import area
        $t.= "<div id='import_wrapper'>\n";
        $t.= "<form id='import_form' name='import_form' target='import_iframe' action='importv2.php' enctype='multipart/form-data' method='POST'>\n";
        $t.= "<h3>Import a CSV file to update existing orders</h3>\n";
        $t.= "<input type='file' id='import_file' name='import_file' value='' size='40' />\n";
        $t .= "<input type='submit' name='importBtn' value='Start Import' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"'>\n";
        $t .= "</form>\n";
        $t .= "<iframe name='import_iframe' id='import_iframe' frameborder='0' height='1'>--No output yet--</iframe>\n";
        $t.= "</div>";
    }

    $t.="<div id='dialog_fraud' title='Fraud Info'></div>";


    $t.= "</div>\n";

    return $t;
}
