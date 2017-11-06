<?php
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
$page->template = "../templates/".$cfg['language']."/default.html"; // load template

include_once("../lib/user.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['manageUsers'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/gateway_stats.php");
}

/*Computations*/
$data = array("merchants"=>array());

$today  = date("Y-m-d");

$from = $to = time();

$extra_sql = " AND %s>'".$today." 00:00:00' and %s<'".$today." 23:59:59'";


if(isset($_GET["from"]) && isset($_GET["to"])) {
  $from = strtotime($_GET["from"]);
  $to = strtotime($_GET["to"]);
  
  if($from > $to)
  {
    //exchange them
    $tmp = $from;
    $from = $to;
    $to = $tmp;
  }
  
  $extra_sql = " AND %s>'".date("Y-m-d",$from)." 00:00:00' AND %s<'".date("Y-m-d",$to)." 23:59:59'";
  
}


//get active gateways
$declines_sql = "SELECT m.BankName as merchant,COUNT(d.declined_id) as declined,d.country 
 FROM mem_merchant m
 LEFT JOIN declined_cards d ON m.BankID=d.bank_id
WHERE m.payment_period<2".sprintf($extra_sql,"d.when","d.when")." AND country IS NOT NULL 
GROUP BY m.BankName,d.country ORDER BY country ASC";

$approved_sql = "SELECT m.BankName as merchant,COUNT(h.hKey) as approved,u.country as country FROM mem_merchant m 
LEFT JOIN mem_merchant_history h ON m.BankID=h.BankID 
LEFT JOIN mem_user u ON h.user_email=u.EmailAddress
WHERE m.payment_period<2".sprintf($extra_sql,"h.hDate","h.hDate")." AND country IS NOT NULL AND h.transaction_id<>'offline-firstsale' 
GROUP BY m.BankName,u.country ORDER BY country ASC
";


$declines_res = mysql_query($declines_sql);

while($row = mysql_fetch_object($declines_res))  {
  $data["merchants"][$row->merchant][$row->country] = array("declined"=>$row->declined);
}

$approved_res = mysql_query($approved_sql);

while($row = mysql_fetch_object($approved_res))  {
  $data["merchants"][$row->merchant][$row->country]["approved"] = $row->approved;
}

//get the dates
$data["dates"] = array("start"=>$from,"stop"=>$to);



$page->blocks['content'] = list_stats($data);	

$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

close_database($con);

function list_stats($data) {

global $cfg;
  
  $t = "";
  
  $t.= "<script type='text/javascript' src='".$cfg['site']['folder']."js/jquery-1.4.2.min.js'></script>\n";
  $t.= "<script type='text/javascript' src='".$cfg['site']['folder']."js/admin_gateway_stats.js'></script>\n";

$t.= "<div class='listContent'>\n";

if(isset($data["messages"])) {
  $t.= "<p>\n";
    $t.="Messages:<br />\n";
    $t.="<strong>".$data["messages"]."</strong>";
  $t.= "</p>\n";
}

$t.= "<p>\n";
  $t.="Showing data between dates: ".date("Y-m-d",$data["dates"]["start"])." -> ".date("Y-m-d",$data["dates"]["stop"]).".\n";
  $t.= "[<a href='#' id='show_dates'>change</a>]\n";
$t.= "</p>\n";

$t.= "<p id='dates_picker_wrapper' style='display:none;'>\n";
  $t.="Select Dates: \n";
  $t.="<br /> <strong>From</strong>: \n"."<input type='text' size='12' readonly id='txt_from' name='txt_from' value='".date("Y-m-d",$data["dates"]["start"])."'>&nbsp;<a><img src='".$cfg['site']['folder']."images/calendar.gif' border='0' id='fromTrigger' align='absmiddle'></a>";
  $t.="&nbsp;&nbsp;<strong>To</strong>: \n"."<input type='text' size='12' readonly id='txt_to' name='txt_to' value='".date("Y-m-d",$data["dates"]["stop"])."'>&nbsp;<a><img src='".$cfg['site']['folder']."images/calendar.gif' border='0' id='toTrigger' align='absmiddle'></a>";
  $t.="<br /> <button id='conversion_dates_change'>Filter</button>\n";
$t.= "</p>\n";



foreach($data["merchants"] as $merchant=>$value) {

  $total_approved = 0;
  $total_declined = 0;
  $total_percentage = 100;

 
  
  
  $t.= "<p>";
    $t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $t .= "<tr>\n";
    $t .= "<td class=\"titleCell\">Merchant:".$merchant."</td>\n";
    $t.= "<td align='left'></td>\n";
    $t.= "<td align='right'></td>\n";
    $t.= "</tr>\n";
    $t.= "</table>\n";
    
    $t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
    
    $t.= "<tr class='captionRow'>\n";
    $t.= "<td><strong>Country Code</strong></td>";
    $t.= "<td><strong>Approved</strong></td>";
    $t.= "<td><strong>Denied</strong></td>";
    $t.= "<td><strong>Approved Percentage</strong></td>";
    $t.= "</tr>";
    
    $key = 0;
    
    foreach($value as $country=>$val) {
    
    $approved_value = (isset($val["approved"]) ? $val["approved"]:0);
    $declined_value = (isset($val["declined"]) ? $val["declined"]:0);
  
    $percentage = $declined_value==0 ? 100 : (ceil(($approved_value*100)/($declined_value+$approved_value)));
    
    $total_approved+=$approved_value;
    $total_declined+=$declined_value;    
    
    
    if($key % 2 == 0){
			$t.= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$t.= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
    
    $t.= "<td>".$country."</td>";
    $t.= "<td>".$approved_value."</td>";
    $t.= "<td>".$declined_value."</td>";
    $t.= "<td>".$percentage."%</td>";
    
    $t.="</tr>\n";
    
    $key++;
    
    }
    
    $total_percentage = $total_declined==0 ? 100 : (ceil(($total_approved*100)/($total_declined+$total_approved)));
    
    if($key % 2 == 0){
			$t.= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$t.= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
    
    $t.= "<td>Totals</td>";
    $t.= "<td>".$total_approved."</td>";
    $t.= "<td>".$total_declined."</td>";
    $t.= "<td>".$total_percentage."%</td>";
    
    $t.="</tr>\n";
    
    
    
    
  $t.= "</table>\n";
  
  
}




$t.= "</div>\n";;

return $t;
}