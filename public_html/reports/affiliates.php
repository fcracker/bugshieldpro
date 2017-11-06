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
include_once("../lib/PayBackEnd.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if ($menuActiveIndex > 0 && $user->check_groups(array($cfg['group']['superAdmin']))) {
    $page->blocks['title'] = "Affiliates";
    $page->blocks['menu'] = get_menu($menuActiveIndex);
    $page->blocks['folder'] = $cfg['site']['folder'];
    $page->blocks['selectLanguage'] = $page->build_language_form();
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "reports/orders.php");
}


//get min/max
$min = date("Y-m-1");
$max = date("Y-m-t");

$view_params = array(
    "period" => $min . ":" . $max,
    "from_date" => $min,
    "to_date" => $max,


);
//the export params


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


//echo "\n\n<!-- Mark 13: ".(microtime(true) - $time_start)."s -->\n\n";
//get hasoffers trackers
$affiliates = multi_query_assoc("SELECT a.aff_id, t.count, t.total FROM `mem_affiliate` AS a LEFT JOIN (SELECT DISTINCT u.hasoffers_aff_id, COUNT(DISTINCT u.id) as count,sum(hAmount) as total FROM `mem_order` u LEFT JOIN mem_merchant_history h ON h.user_email=u.email WHERE `hasoffers_aff_id`>0 AND `date` BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59' GROUP BY `hasoffers_aff_id`) AS t ON a.aff_id = t.hasoffers_aff_id");
$page->blocks['content'] = markup($affiliates, $view_params);
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page


function markup($data, $params = array())
{
    $last_month = mktime(0,0,0,date("m")-1,1,date("Y"));
    $last_year = mktime(0,0,0,1,1,date("Y")-1);
    $periods = array(

        array("txt"=>"Custom","val"=>"0:0"),
        array("txt"=>"This Month","val"=>date("Y-m-1").":".date("Y-m-t")),
        array("txt"=>"Last Month","val"=>date("Y-m-1",$last_month).":".date("Y-m-t",$last_month)),
        array("txt"=>"This Year","val"=>date("Y-1-1").":".date("Y-12-31")),
        array("txt"=>"Last Year","val"=>date("Y-1-1",$last_year).":".date("Y-12-31",$last_year)),
    );

    for($j=2;$j<8;$j++) {
        $dt = mktime(0,0,0,date("m")-$j,1,date("Y"));
        $periods[] = array("txt"=>date("F Y",$dt),"val"=>date("Y-m-1",$dt).":".date("Y-m-t",$dt));
    }

    global $cfg;

    $t = "<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/jquery-1.4.2.min.js'></script>\n";
    $t .= '<script type="text/javascript" src="' . $cfg['site']['folder'] . 'js/jquery-ui.min-1.8.8.js"></script>
<link rel="stylesheet" type="text/css" href="' . $cfg['site']['folder'] . 'styles/jquery-ui-1.8.8.css"  />';

    $t .= "\n<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/reports/affiliates.js'></script>\n";

    $t .= "<div class='listContent'>\n";


    $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\">Affiliates</td>\n";
    $t .= "<td align='left'>Server Time:" . date("d-m-Y H:i:s") . "</td>\n";
    $t .= "<td align='right'>Total listed orders:" . count($data) . "</td>\n";
    $t .= "</tr>\n";
    $t .= "</table>\n";

    $t .= "<form action='' method='POST' id='order_form'>\n";

    $t .= "<div class='export_wrapper' style='padding-top:10px;'>\n";

    $t.= "<h3>Settings:</h3>";

    $t.= "Period: ";
    $t.= "<select name='period'>\n";
    foreach($periods as $period) {
        $t.= "<option value='".$period["val"]."'".($params['period']==$period["val"] ? " selected":"").">".$period["txt"]."</option>";
    }
    $t.= "</select>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";

    $t.= "From: ";
    $t.= "<input type='text' name='from_date' id='from_date' value='".$params["from_date"]."' /> <img src='".$cfg['site']['folder']."images/calendar.gif' border='0' id='from_date_trigger' align='absmiddle'>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";


    $t.= "To: ";
    $t.= "<input type='text' name='to_date' id='to_date' value='".$params["to_date"]."' /> <img src='".$cfg['site']['folder']."images/calendar.gif' border='0' id='to_date_trigger' align='absmiddle'>";

    $t.= "&nbsp;&nbsp;|&nbsp;&nbsp;";


    $t .= "<input type='submit' name='viewBtn' value='View' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='view_now();'>\n";


    $t.= "</div>\n";

    $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\"></td>\n";
    $t .= "</tr>\n";
    $t .= "</table>\n";


    $affiliate_total_number = 0;
    $affiliate_total_sales = 0;
    $affiliate_total_money = 0.0;

    $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";

    $t .= "<tr class='captionRow'>\n";
    $t .= "<td>Affiliate ID</td>";
    $t .= "<td>Sales</td>";
    $t .= "<td>Total</td>";
    $t .= "<td>Action</td>";
    $t .= "</tr>";
    foreach ($data as $affiliate) {

        $t .= "<tr>\n";
        $t .= "<td>" . $affiliate["aff_id"] . "</td>\n";
        $t .= "<td>" . number_format($affiliate["count"]) . "</td>\n";
        $t .= "<td>" . money_format('%i', $affiliate["total"]) . "</td>\n";
        $t.= "<td><a class='adjust' href='#' rel='".$affiliate["aff_id"]."'>Adjust</a>
         <div style='display:none;'>Orders count: <input type='number' id='adjust_".$affiliate["aff_id"]."' name='adjust_value_".$affiliate["aff_id"]."'  value='' />
         <a href='#' class='save' rel='".$affiliate["aff_id"]."'>Adjust</a> | <a href='#' class='adjust_cancel' rel='".$affiliate["aff_id"]."'>Cancel</a> | <span class='error".$affiliate["aff_id"]."' style='display:none;color:red;'>number must be positive</span></div>
         <a href='view_affiliate.php?aff_id=".$affiliate["aff_id"]."&from=".$params["from_date"]."&to=".$params["to_date"]."'>View</a></td>";
        $t .= "</tr>\n";

        $affiliate_total_number++;
        $affiliate_total_sales += $affiliate["count"];
        $affiliate_total_money += $affiliate["total"];

    }

//show the totals
    $t .= "<tr>\n";
    $t .= "<td>TOTALS for <strong>" . $affiliate_total_number . " affiliates</strong></td>\n";
    $t .= "<td><strong>" . $affiliate_total_sales . "</strong></td>\n";
    $t .= "<td><strong>" . money_format('%i', $affiliate_total_money) . "</strong></td>\n";
    $t .= "</tr>\n";
    $t .= "</table>\n";
    $t .= "</table>\n";
    $t .= "</form>\n";
    $t .= "<div id='dialog_fraud' title='Fraud Info'></div>";
    $t .= "</div>\n";

    return $t;
}
