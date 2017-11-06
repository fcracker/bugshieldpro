<?php

include_once("../../lib/config.inc.php");
include_once("../../lib/database.inc.php");
include_once("../../lib/page.class.php");
include_once("../../lib/menu.class.php");
include_once("../../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../../languages/" . $cfg['language'] . ".php"); // load language file
$page->template = "../../templates/" . $cfg['language'] . "/default.html"; // load template
include_once("../../lib/user.class.php");
include_once("../../lib/affiliate.class.php");

global $cfg;
$con = connect_database();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);

if ($menuActiveIndex > 0) {
    $page->blocks['title'] = "Affiliates stats";
    $page->blocks['menu'] = get_menu($menuActiveIndex);
    $page->blocks['folder'] = $cfg['site']['folder'];
    $page->blocks['selectLanguage'] = $page->build_language_form();
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "admin/affiliate/stats.php");
}


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

$affiliate_id = isset($_GET['aff_id']) ? intval($_GET['aff_id']) : 0;
if ($affiliate_id < 0) {
    $affiliate_id = 0;
}

$affiliate_class = new affiliate($cfg);



$stats_data = $affiliate_class->get_affiliate_stats($affiliate_id, $view_params["from_date"], $view_params["to_date"]);


$page->blocks['content'] = list_stats_page($stats_data, $affiliate_id, $view_params);

/*
 * construct and print page
 */
$page->construct_page();  // construct html page
$page->output_page();   // output page

function list_stats_page($stats_data, $affiliate_id, $params = array()) {


    global $cfg;
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


// get bank list
    $bank_list = array();

    foreach ($stats_data as $k => $v) {
        foreach ($v as $k1 => $v1) {
            foreach (array_keys($v1) as $bank) {
                if (!in_array($bank, $bank_list) && (strpos($bank, "_sum") === FALSE) && (strpos($bank, "_refund") === FALSE)) {
                    $bank_list[] = $bank;
                }
            }
        }
    }
    $t = "<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/jquery-1.4.2.min.js'></script>\n";
    $t.= "<script type='text/javascript' src='" . $cfg['site']['folder'] . "js/reports/stats.js'></script>\n";

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

    $t.= "</form>\n";

    foreach ($stats_data as $label => $affiliates) {

        $has_refunds = $label != 'Refunds';

        $big_totals = array();
        $big_refunds = array();

        $t .= "<h2>" . $label . "</h2>";

        $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
        $t .= "<tr class=\"captionRow\">\n";
        $t .= "<td width=\"3%\">Affiliate ID</td>";
        foreach ($bank_list as $bank) {
            $t .= "<td colspan=2>" . $bank . "</td>";
            $big_totals[$bank] = 0;
            $big_refunds[$bank] = 0;
        }
        $t .= "<td colspan=2>Total</td>";

        $t .= "</tr>\n";

        // subs
        if ($has_refunds) {
            $t .= "<tr class=\"captionRow\">\n";

            $t .= "<td>&nbsp;</td>";

            foreach ($bank_list as $bank) {
                $t .= "<td>Sales</td>";
                $t .= "<td>Refunds</td>";
            }

            $t .= "<td>Sales</td>";
            $t .= "<td>Refunds</td>";


            $t .= "</tr>\n";
        }

        $i = 0;

        $big_totals['total'] = 0;
        $big_refunds['total'] = 0;

        foreach ($affiliates as $aff_id => $banks) {

            if ($i % 2 == 0) {
                $t .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
            } else {
                $t .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
            }

            $i++;

            $total = 0;
            $refund_total = 0;

            $t .= "<td>" . $aff_id . "</td>";

            foreach ($bank_list as $bank) {

                $tt = array_key_exists($bank, $banks) ? $banks[$bank] : 0;
                $t .= "<td colspan=" . ($has_refunds ? 1 : 2) . ">";

                if ($tt) {
                    $t .= $tt . " / $" . $stats_data[$label][$aff_id][$bank . "_sum"];
                } else {
                    $t .= "N/A";
                }
                $t .= "</td>";

                if ($has_refunds) {

                    $rt = array_key_exists($bank . "_refund_count", $banks) ? $banks[$bank . "_refund_count"] : 0;

                    $t .= "<td>";

                    if ($rt) {
                        $t .= $rt . " / $" . $stats_data[$label][$aff_id][$bank . "_refund_sum"];
                        // compute percentage
                        $percentage = 0.0;
                        if ($tt && $rt) {
                            $percentage = ($rt * 100) / $tt;

                            $t .= sprintf(' (%.2f', $percentage);
                            $t .= '%)';
                        }
                    } else {
                        $t .= "N/A";
                    }
                    $t .= "</td>";
                }

                $total+=$tt;
                $refund_total+=$rt;
                $big_refunds[$bank] += $rt;

                $big_totals[$bank] += $tt;
            }

            $t .= "<td>" . $total . "</td>";
            if ($has_refunds) {
                $t .= "<td>" . $refund_total;
                // compute percentage
                $percentage = 0.0;
                if ($refund_total) {
                    $percentage = ($refund_total * 100) / $total;

                    $t .= sprintf(' (%.2f', $percentage);
                    $t .= '%)';
                }
                $t .= "</td>";
            }

            $t .= "</tr>\n";

            $big_totals['total']+=$total;
            $big_refunds['total']+= $refund_total;
        }

        if ($i % 2 == 0) {
            $t .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
        } else {
            $t .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
        }

        $t .= "<td>TOTALS</td>";

        foreach ($bank_list as $bank) {
            
            $bbt = array_key_exists($bank, $big_totals) ? $big_totals[$bank] : 0;
            
            $t .= "<td colspan=" . ($has_refunds ? 1 : 2) . ">" . $bbt . "</td>";

            if ($has_refunds) {
                $rrt = array_key_exists($bank, $big_refunds) ? $big_refunds[$bank] : 0;
                $t .= "<td>" . $rrt;
                
                // compute percentage
                $percentage = 0.0;
                if ($bbt && $rrt) {
                    $percentage = ($rrt * 100) / $bbt;

                    $t .= sprintf(' (%.2f', $percentage);
                    $t .= '%)';
                }
            $t .= "</td>"; 
                
            }
        }

        $t .= "<td>" . $big_totals['total'] . "</td>";

        if ($has_refunds) {

            $t .= "<td>" . $big_refunds['total'];
            
            // compute percentage
                $percentage = 0.0;
                if ($big_refunds['total']) {
                    $percentage = ($big_refunds['total'] * 100) / $big_totals['total'];

                    $t .= sprintf(' (%.2f', $percentage);
                    $t .= '%)';
                }
            $t .= "</td>";    
                
        }


        $t .= "</table>\n";
    }



    $t .= "</div>\n";

    return $t;
}
