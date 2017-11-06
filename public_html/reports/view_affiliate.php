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

$adjustQuery = "(adjust_aff_id = '' OR adjust_aff_id IS NULL)";

$con = connect_database();

$user = new umUser();
$user->get_session();

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if ($user->check_groups(array($cfg['group']['superAdmin']))) {
    $page->blocks['title'] = "Affiliate Orders";
    $page->blocks['menu'] = get_menu($menuActiveIndex);
    $page->blocks['folder'] = $cfg['site']['folder'];
    $page->blocks['selectLanguage'] = $page->build_language_form();
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "reports/orders.php");
}


//get min/max
$min = date("Y-m-1");
$max = date("Y-m-t");


//the export params
if(isset($_GET["from"]) ){
    $min = date($_GET["from"]);
    $max = date($_GET["to"]);
    $view_params = array(
        "period" => $min . ":" . $max,
        "from_date" => $min,
        "to_date" => $max,
    );
}
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

$affiliateType = isset($_POST['affiliate-type']) ? $_POST['affiliate-type'] : 1;
$view_params["type"] = $affiliateType;
switch ($affiliateType) {
    case 0:
        $adjustQuery = "(adjust_aff_id = '' OR adjust_aff_id IS NULL)";
        break;
    case 1:
        $adjustQuery = "1";
        break;
    case 2:
        $adjustQuery = "adjust_aff_id > ''";
        break;
    default:
        $adjustQuery = "(adjust_aff_id = '' OR adjust_aff_id IS NULL)";
        break;
}

$orders = multi_query_assoc("SELECT * FROM `mem_order` WHERE `hasoffers_aff_id` = '" . $_GET['aff_id'] . "' AND " . $adjustQuery . " AND `date` BETWEEN '" . $view_params["from_date"] . "' AND '" . $view_params["to_date"] . " 23:59:59'");
$page->blocks['content'] = markup($orders, $view_params);
$page->construct_page(); // construct html page
$page->output_page(); // output page


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

//    $t .= "\n<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/reports/orders.js'></script>\n";

    $t .= "<div class='listContent'>\n";

    $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\">Affiliate Orders</td>\n";
    $t .= "<td align='left'>Server Time:" . date("d-m-Y H:i:s") . "</td>\n";
    $t .= "<td align='right'>Total listed orders:" . count($data) . "</td>\n";
    $t .= "</tr>\n";
    $t .= "</table>\n";

    $t .= "<form action='' method='POST' id='order_form'>\n";


    $t .= "<div class='export_wrapper' style='padding-top:10px;'>\n";

    $t .= "<h3>Settings:</h3>";
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

    $t.= "<br/>";
    $t .= "Affiliates type: ";
    $t .= "<select name='affiliate-type'>\n";
    $t .= "<option value='0'" . ($params['type'] == "0" ? " selected" : "") . ">Orders with affiliate IDs</option>";
    $t .= "<option value='1'" . ($params['type'] == "1" ? " selected" : "") . ">Orders with affiliate IDs and orders with adjusted affiliate IDs</option>";
    $t .= "<option value='2'" . ($params['type'] == "2" ? " selected" : "") . ">Orders with adjusted affiliate Ids only</option>";
    $t .= "</select>";

    $t .= "&nbsp;&nbsp;|&nbsp;&nbsp;";


    $t .= "<input id='viewSub' type='submit' name='viewBtn' value='View' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"'>\n";

    $t .= "</div>\n";


    $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\"></td>\n";
    $t .= "</tr>\n";
    $t .= "</table>\n";

    if (count($data)) {


        $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";

        $t .= "<tr class='captionRow'>\n";
        $t .= "<td>SubID</td>";
        $t .= "<td>QTY</td>";
        $t .= "<td>Description</td>";
        $t .= "<td>Order #</td>";
        $t .= "<td>Date</td>";
        $t .= "<td>First name</td>";
        $t .= "<td>Last name</td>";
        $t .= "<td>Address</td>";
        $t .= "<td>Country</td>";
        $t .= "<td>City</td>";
        $t .= "<td>State</td>";
        $t .= "<td>Zip</td>";
        $t .= "<td>Undo</td>";
        $t .= "</tr>";


        foreach ($data as $key => $d) {
            $t .= "<td>" . $d['subid'] . "</td>";
            $t .= "<td>" . $d['qty'] . "</td>";
            $t .= "<td>" . $d['description'] . "</td>";
            $t .= "<td>" . $d['user_id'] . "</td>";
            $t .= "<td>" . date("Y-m-d", strtotime($d['date'])) . "</td>";
            $t .= "<td>" . $d['firstname'] . "</td>";
            $t .= "<td>" . $d['lastname'] . "</td>";
            $t .= "<td>" . $d['address'] . "</td>";
            $t .= "<td>" . $d['country'] . "</td>";
            $t .= "<td>" . $d['city'] . "</td>";
            $t .= "<td>" . $d['state'] . "</td>";
            $t .= "<td>" . $d['zip'] . "</td>";
            $t .= "<td>" . ($d['adjust_aff_id'] ? '<a class="undo-adjust" href="#" onclick="Undo(\''.$d['id'].'\')">Undo</a>' : '') . "</td>";
            $t .= "</tr>\n";


        }


        $t .= "</table>\n";
        $t .= "</form>\n";
    }

    $t .= "<div id='dialog_fraud' title='Fraud Info'></div>";


    $t .= "</div>\n";

    $t .= "<script>";
    $t .= '
            function Undo(OrerID){
                var conf = confirm("Confirm undo this order?");
                if (conf == true) {
                    $.post(
                        "ajax_handler.php",
                        {
                            action: "adjust_affiliates_undo",
                            aff_id: OrerID
                        },
                        function (data) {
                            if (data.status == "OK") {
                                $("#viewSub").click();
                            } else {
                                alert(data.html);
                            }
                        },
                        "json"
                    );
                }
            }
    ';
    $t .= "Calendar.setup({inputField: 'from_date',ifFormat: '%Y-%m-%d',showsTime: false,button: 'from_date_trigger'});";
    $t .= "Calendar.setup({inputField: 'to_date',ifFormat: '%Y-%m-%d',showsTime: false,button: 'to_date_trigger'});";
    $t .= "</script>";
    return $t;
}

?>