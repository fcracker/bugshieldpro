<?php
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/conversions.php");
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
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/monthly_calendar.php");
}


$data = $user->get_monthly_fee_calendar();;




$page->blocks['content'] = list_calendar($data);	

$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

close_database($con);

function list_calendar($data) {

$today = date("d-m-Y");

global $cfg;

$t= "<script type='text/javascript' src='".$cfg['site']['folder']."js/jquery-1.4.2.min.js'></script>\n";
$t.= "<script type='text/javascript' src='".$cfg['site']['folder']."js/monthly_calendar.js'></script>\n";

$t.= "<div class='listContent'>\n";



$current_date = "";

if(count($data)) {

$t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
$t .= "<tr>\n";
$t .= "<td class=\"titleCell\">Monthly fee calendar</td>\n";
$t.= "<td align='left'>Server Time:".date("d-m-Y H:i:s")."</td>\n";
$t.= "<td align='right'>Total Users that are billed monthly:".count($data)."</td>\n";
$t.= "</tr>\n";
$t.= "</table>\n";

$t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";

foreach($data as $key=>$d) {

$this_date = date("d-m-Y",strtotime($d["d"]));

if($this_date!=$current_date) {

  $t.= "<tr class='captionRow'>\n";
  $t.= "<td  colspan='6' id='date".md5($this_date)."' class='date_trigger'><strong>".date("d-m-Y",strtotime($d["d"]))."</strong></td>";
  $t.= "</tr>";
  $current_date = $this_date;
}

$extra_style = $current_date!=$today ? " style='display:none;'":"";

if($key % 2 == 0){
			$t.= "<tr".$extra_style." class=\"dataRow1 date".md5($this_date)." drow\" onmouseover=\"this.className='heightDataRow date".md5($this_date)." drow'\" onmouseout=\"this.className='dataRow1 date".md5($this_date)." drow'\">\n";
		}else{
			$t.= "<tr".$extra_style." class=\"dataRow2 date".md5($this_date)." drow\" onmouseover=\"this.className='heightDataRow date".md5($this_date)." drow'\" onmouseout=\"this.className='dataRow2 date".md5($this_date)." drow'\">\n";
		}	
    
    $t.= "<td>".date("d-m-Y H:i:s",strtotime($d["d"]))."</td>";
    $t.= "<td>".$d["n"]."</td>";
    $t.= "<td>".$d["e"]."</td>";
    $t.= "<td>".$d["fee"]."</td>";
    $t.= "<td>".$d["user_ip"]."</td>";
    $t.= "<td>".$d["country"]."/".$d["state"]."</td>";
    
$t.="</tr>\n";


}


$t.= "</table>\n";


}


$t.= "</div>\n";;

return $t;
}