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

include_once("../lib/order.class.php");

include_once("../lib/antifraud.class.php");
include_once("../lib/antifraud.redflag.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();


$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if ($menuActiveIndex > 0) {
    $page->blocks['title'] = "Anti Fraud Service";
    $page->blocks['menu'] = get_menu($menuActiveIndex);
    $page->blocks['folder'] = $cfg['site']['folder'];
    $page->blocks['selectLanguage'] = $page->build_language_form();
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "reports/anti-fraud.php");
}


$order = new order;
$antifraud = new antifraud;

$orderno_search = 0;

//process
if (isset($_GET['action'])) {
    switch ($_GET['action']) {

        case 'check':
            $order_id = intval($_GET['o']);
            $antifraud->check_order($order_id);
            break;
        case 'recheck':
            $order_id = intval($_GET['o']);
            $antifraud->check_order($order_id, true);
            break;
        case 'search':
            $order_id = intval($_GET['o']);
            $orderno_search = $order_id;

            $affiliate_id = intval($_GET['affiliate']);
            $affiliate_search = $affiliate_id;
            break;
    }
}


//get min/max
$min_max = $order->get_min_max_dates();
$min = $min_max["min"];
$max = $min_max["max"];
$per_page = 20;
$p = 0; //page

$view_params = array(
    "status" => "yellow;red",
    "from_date" => $min,
    "to_date" => $max,
    "rebill" => "",
    "per_page" => $per_page,
    "page" => $p,
    "order_search" => $orderno_search,
    "affiliate" => $affiliate_search,
    "remaining_queries" => $antifraud->get_remaining_queries(),
); //the export params
//merge params
if (count($_REQUEST)) {

    foreach ($_REQUEST as $key => $value) {
        //is it a valid param ?
        if (array_key_exists($key, $view_params) && strlen($value)) {
            $view_params[$key] = $value;
        }
    }
}



if ($orderno_search <= 0 && $affiliate_search <= 0) {
    $data = $antifraud->get_orders($view_params["from_date"], $view_params["to_date"], $view_params["page"], $view_params["per_page"], $view_params["status"], $view_params["rebill"]);

    $count = $antifraud->get_total_orders($view_params["from_date"], $view_params["to_date"], $view_params["status"], $view_params["rebill"]);
} else {
    //we have an order search
    if ($orderno_search) {
        $data = $antifraud->get_single_order($orderno_search);
        $count = count($data);
    } else if ($affiliate_search) {
        $data = $antifraud->get_orders_by_affiliate($affiliate_search, $view_params["page"], $view_params["per_page"]);
        $count = $antifraud->get_order_count_by_affiliate($affiliate_search);
    }
}

//echo "<pre>".print_r($data,1)."</pre>";

$redflagRules = new antifraud_reflag();
$redflagemails = $redflagRules->getRulesByCategory('email');

$page->blocks['content'] = markup($data, $view_params, $count, $redflagemails);

$page->construct_page();  // construct html page
$page->output_page();   // output page

close_database($con);

function display($data, $context) {

    $class = "green";
    $value = $data;

    $func = "check_" . $context;
    if (function_exists($func)) {
        list($class, $value) = $func($data);
    }

    return array("class" => $class, "value" => $value);
}

function check_avs($data) {

    switch ($data) {

        case "N":$class = "red";
            break;
        case "G":$class = "yellow";
            break;
        default:$class = "green";
            break;
    }

    return array($class, "(" . $data . ")");
}

function check_cvv($data) {

    switch ($data) {

        case "N":$class = "red";
            break;
        default:$class = "green";
            break;
    }

    return array($class, "(" . $data . ")");
}

function check_generic_no_yes($data) {
    switch ($data) {

        case "Yes":$class = "red";
            break;
        case "NA":$class = "grey";
            break;
        default:$class = "green";
            break;
    }

    return array($class, $data);
}

function check_generic_yes_no($data) {
    switch ($data) {

        case "No":$class = "red";
            break;
        case "NA":$class = "grey";
            break;
        default:$class = "green";
            break;
    }

    return array($class, $data);
}

function check_correlation_external($data) {
    //just in km now
    //hard limit to 50/100
    $num = intval($data);

    if ($num <= 50) {
        $class = "green";
    } elseif ($num > 50 && $num <= 100) {
        $class = "yellow";
    } else {
        $class = "red";
    }

    return array($class, $data . " KM");
}

function check_correlation_local($data) {
    return array("grey", $data);
}

function check_bin_country_match($data) {

    $parts = explode(";", $data);
    $class = "red";
    if ($parts[0] == $parts[2]) {
        $class = "green";
    }

    return array($class, implode("<br />", $parts));
}

function check_bin_prepaid_match($data) {
    return check_generic_no_yes($data);
}

function check_ip_is_proxy($data) {
    return check_generic_no_yes($data);
}

function check_is_ip_high_risk($data) {
    return check_generic_no_yes($data);
}

function check_is_email_high_risk($data) {
    return check_generic_no_yes($data);
}

function check_is_address_high_risk($data) {
    return check_generic_no_yes($data);
}

function check_risk_score($data) {
    return array("grey", $data);
}

function check_bin_phone_match($data) {
    return check_generic_yes_no($data);
}

function check_bin_name_match($data) {
    return check_generic_yes_no($data);
}

function markup($data, $params = array(), $count = 0, $redflagemails = array()) {



    global $cfg;

    $t = "<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/jquery-1.4.2.min.js'></script>\n";
    $t.= "<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/reports/antifraud.js'></script>\n";

    $t.= "<div class='listContent'>\n";



    $current_date = "";

    $t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t.= " <tr>\n";
    $t.= "     <td class=\"titleCell\">Fraud Detection System - Orders</td>\n";
    $t.= "      <td align='left'>Server Time:" . date("d-m-Y H:i:s") . "</td>\n";
    $t.= "      <td align='right'>Per Page:" . $params["per_page"] . " / Total: " . $count . "&nbsp;&nbsp;&nbsp;<input onclick=\"location.href='redflag_setting.php'\" type=\"button\" value=\"Red Flag Setting\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\"></td>\n";
    $t.= "  </tr>\n";
    $t.= "</table>\n";

    $t.= "<form action='' method='POST' id='order_form'>\n";



    $t .= "<div class='export_wrapper' style='padding-top:10px;'>\n";

    $t.= "<h3>Settings:</h3>";

    $t.= "Status: ";
    $t.= "<select name='status'>\n";
    $t.= "<option value='yellow;red'" . ($params['status'] == "yellow;red" ? " selected" : "") . ">Show only red and yellow flag orders</option>";
    $t.= "<option value='all'" . ($params['status'] == "all" ? " selected" : "") . ">Show all orders</option>";
    $t.= "</select>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";

    $t.= "From: ";
    $t.= "<input type='text' name='from_date' id='from_date' value='" . $params["from_date"] . "' /> <img src='" . $cfg['site']['folder'] . "images/calendar.gif' border='0' id='from_date_trigger' align='absmiddle'>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";


    $t.= "To: ";
    $t.= "<input type='text' name='to_date' id='to_date' value='" . $params["to_date"] . "' /> <img src='" . $cfg['site']['folder'] . "images/calendar.gif' border='0' id='to_date_trigger' align='absmiddle'>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp; Rebills: ";
    $t.= "<select name='rebill'>\n";
    $t.= "<option value=''></option>";
    $t.= "<option value='rebill'" . ($params['rebill'] == "rebill" ? " selected" : "") . ">Show only rebills</option>";
    $t.= "<option value='inital'" . ($params['rebill'] == "inital" ? " selected" : "") . ">Show only initial order</option>";
    $t.= "</select>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";


    $t .= "<input type='submit' name='viewBtn' value='View' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='view_now();'>\n";

    $t .= "<input  style=\"float:right\" type='button' name='recheckall' value='Recheck All' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='recheck_all(event);'>\n";

    $t.= "<br />Search by Order#: ";
    $t.= "<input type='text' name='orderno' placeholder='#...' id='orderno_search' value='" . ($params['order_search'] ? $params['order_search'] : '') . "' />";
    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";
    $t .= "<button class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='return search_by_orderno();'>Search</button>\n";
    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";
    $t .= "<a href='#' style='display:" . (($params['order_search'] ? 'inline' : 'none')) . "' onclick='reset_panel();'>[reset]</a>\n";

    $t.= "<br />Search by Affiliate#: ";
    $t.= "<input type='text' name='affiliateno' placeholder='#...' id='affiliateno_search' value='" . ($params['affiliate'] ? $params['affiliate'] : '') . "' />";
    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";
    $t .= "<button class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='return search_by_affiliate();'>Search</button>\n";
    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";
    $t .= "<a href='#' style='display:" . (($params['affiliate'] ? 'inline' : 'none')) . "' onclick='reset_panel();'>[reset]</a>\n";


    $t.= "</div>\n";

    if (intval($count) > 0) {

        $pages = ceil($count / $params["per_page"]);

        $pages_to_show = array();





        $t.="<div class='paginationatifraud'>\n";

        $t.= "<span>Remaining queries: " . $params['remaining_queries'] . " &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;</span>";

        $t.= "<span>Pages:</span>";
        if ($params["page"] > 0) {
            $ps = $params;
            $ps["page"] = $params["page"] - 1;
            $t.= "<a href='anti-fraud.php?" . http_build_query($ps) . "'>&laquo; previous</a>";
        }

        if ($pages <= 20) {
            for ($j = 0; $j < $pages; $j++) {
                $ps = $params;
                $ps["page"] = $j;
                $t.= "<a href='anti-fraud.php?" . http_build_query($ps) . "'" . ($j == $params["page"] ? " class='active'" : "") . ">" . ($j + 1) . "</a>";
            }
        } else {
            $t.= "<span>" . ($params["page"] + 1) . "/" . $pages . "</span>";
        }

        if ($params["page"] < ($pages - 1)) {
            $ps = $params;
            $ps["page"] = $params["page"] + 1;
            $t.= "<a href='anti-fraud.php?" . http_build_query($ps) . "'>next &raquo;</a>";
        }



        $t.="</div>";
    }


    $t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\"></td>\n";
    $t.= "</tr>\n";
    $t.= "</table>\n";


    if (count($data)) {



        $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";

        $t.= "<tr class='captionRow'>\n";
        //$t.= "<td><input type='checkbox' id='sel_all' /></td>";
        $t.= "<td>Order#</td>";
        $t.= "<td>Affiliate</td>";
        $t.= "<td>AVS Response</td>";
        $t.= "<td>CVV (M/N)</td>";
        $t.= "<td>In House IP & physical location correlate?</td>";
        $t.= "<td>Maxmind IP & physical location correlate?</td>";
        $t.= "<td>Order form & BIN country match?</td>";
        $t.= "<td>BIN prepaid card match?</td>";
        $t.= "<td>IP a proxy?</td>";
        $t.= "<td>Maxmind High risk?</td>";
        $t.= "<td>Maxmind High risk email?</td>";
        $t.= "<td>Maxmind High risk shipping address?</td>";
        $t.= "<td>Maxmind Risk Score</td>";
        $t.= "<td>BIN Phone Match</td>";
        $t.= "<td>BIN Name Match</td>";
        $t.= "<td>Action</td>";
        $t.= "</tr>";


        foreach ($data as $key => $d) {


            if ($key % 2 == 0) {
                $t.= "<tr class=\"dataRow1 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow1 drow'\">\n";
            } else {
                $t.= "<tr class=\"dataRow2 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow2 drow'\">\n";
            }

            // $t.= "<td rowspan='2'><input class='orderid' type='checkbox' name='orderids[]' value='".$d->orderID."' checked='checked' /></td>";
            //put a header
            $emailClass = "";
            if (in_array($d->email, $redflagemails)) {
                $emailClass = "red-email";
            }
            $t.= "<td colspan='16' style='text-align:center;'>" . $d->date . "  /  " . $d->firstname . " " . $d->lastname . " - <span class=\"{$emailClass}\">" . $d->email . "</span> - " . $d->user_ip . "</td></tr>";

            if (!strlen($d->order_id)) {
                //fraud check not yet done

                if ($key % 2 == 0) {
                    $t.= "<tr class=\"dataRow1 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow1 drow'\">\n";
                } else {
                    $t.= "<tr class=\"dataRow2 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow2 drow'\">\n";
                }

                $ps = $params;
                $ps["action"] = "check";
                $ps["o"] = $d->orderID;
                $t.= "<td colspan='16' style='text-align:center;'>Fraud Check was not yet performed on this order.<a href='anti-fraud.php?" . http_build_query($ps) . "' class='quick check'>Check now</a></td></tr>";
            } else {

                if ($key % 2 == 0) {
                    $t.= "<tr class=\"dataRow1 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow1 drow'\">\n";
                } else {
                    $t.= "<tr class=\"dataRow2 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow2 drow'\">\n";
                }

                $t.= "<td class=\"" . ($d->fraudulent_flag == 1 ? "yellow" : "") . "\">" . $d->user_id . "</td>";

                $antifraud = new antifraud;

                $affClassName = $antifraud->check_bin_affiliate($d->affID, $d->total);

                if ($d->affID && $affClassName != "red") {
                    $affClassName = $antifraud->check_bin_affiliate_rules($d->affID);
                }


                $t.= "<td class=\"{$affClassName}\"><span>" . ($d->affID > 0 ? $d->affID : "N/A") . "<span></td>";


                //avs response
                $avs_response = display($d->avs_response, "avs");
                $t.= "<td class='" . $avs_response['class'] . "'>" . $avs_response['value'] . "</td>";

                //cvv response
                $cvv_response = display($d->cvv_response, "cvv");
                $t.= "<td class='" . $cvv_response['class'] . "'>" . $cvv_response['value'] . "</td>";

                //correlation local 
                $correlation_local = display($d->ip_location_correlation_local, "correlation_local");
                $t.= "<td class='" . $correlation_local['class'] . "'><span id='correlationlocal" . $d->orderID . "'>" . $correlation_local['value'] . "</span>";
                if ($d->country == "CA" /* || $d->country == "US" */) {
                    $t.= "<br />" .
                            "<a href='#' rel='" . $d->orderID . "' zip='" . $d->zip . "' class='localcorrelation'>[recompute]</a>";
                }
                $t.="</td>";

                //correlation external 
                $correlation_external = display($d->ip_location_correlation_external, "correlation_external");
                $t.= "<td class='" . $correlation_external['class'] . "'>" . $correlation_external['value'] . "</td>";



                $bin_country_match = display($d->bin_country_match, "bin_country_match");
                $t.= "<td class='" . $bin_country_match['class'] . "'>" . $bin_country_match['value'] . "</td>";


                $bin_prepaid_match = display($d->bin_prepaid_match, "bin_prepaid_match");
                $t.= "<td class='" . $bin_prepaid_match['class'] . "'>" . $bin_prepaid_match['value'] . "</td>";


                $ip_is_proxy = display($d->ip_is_proxy, "ip_is_proxy");
                $t.= "<td class='" . $ip_is_proxy['class'] . "'>" . $ip_is_proxy['value'] . "</td>";


                $is_ip_high_risk = display($d->is_ip_high_risk, "is_ip_high_risk");
                $t.= "<td class='" . $is_ip_high_risk['class'] . "'>" . $is_ip_high_risk['value'] . "</td>";

                $is_email_high_risk = display($d->is_email_high_risk, "is_email_high_risk");
                $t.= "<td class='" . $is_email_high_risk['class'] . "'>" . $is_email_high_risk['value'] . "</td>";


                $is_address_high_risk = display($d->is_address_high_risk, "is_address_high_risk");
                $t.= "<td class='" . $is_address_high_risk['class'] . "'>" . $is_address_high_risk['value'] . "</td>";


                $risk_score = display($d->risk_score, "risk_score");
                $t.= "<td class='" . $risk_score['class'] . "'>" . $risk_score['value'] . "</td>";


                $bin_phone_match = display($d->bin_phone_match, "bin_phone_match");
                $t.= "<td class='" . $bin_phone_match['class'] . "'>" . $bin_phone_match['value'] . "</td>";


                $bin_name_match = display($d->bin_name_match, "bin_name_match");
                $t.= "<td class='" . $bin_name_match['class'] . "'>" . $bin_name_match['value'] . "</td>";

                $ps = $params;
                $ps["action"] = "recheck";
                $ps["o"] = $d->orderID;


                $t.= "<td><a href='anti-fraud.php?" . http_build_query($ps) . "'>ReCheck</a></td>";
            }



            $t.="</tr>\n";

            $t.= "<tr><td colspan='16' style='background-color:#333;height:5px;padding:0;font-size:1px;'>&nbsp;</td></tr>";
        }


        $t.= "</table>\n";
        $t .= "</form>\n";
    }


    $t.= "</div>\n";

    return $t;
}
