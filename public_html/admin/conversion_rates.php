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
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/conversion_rates.php");
}

/*Computations*/
$data = array();

$c = new conversions();

if(isset($_GET["flush_cache"])) {
  if($c->flush_cache()) {
    $data["messages"] = "Cache Flushed!";
  }
}

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
  
  $c->set_dates(array(
    "start"=>$from,
    "stop"=>$to,   
  ));
}

//get the dates
$data["dates"] = $c->get_dates();

$data["total_bronze"] = $c->get_total_by_membership("bronze");
$data["total_gold"] = $c->get_total_by_membership("gold");

foreach($c->get_indexes() as $index) {
  $data["indexes"][$index]["bronze"] = $c->get_total_by_index_membership($index,"bronze");
  $data["indexes"][$index]["gold"] = $c->get_total_by_index_membership($index,"gold");
  $data["indexes"][$index]["total"] = $data["indexes"][$index]["bronze"] + $data["indexes"][$index]["gold"];
}

$page->blocks['content'] = list_conversions($data);	

$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

close_database($con);

function list_conversions($data) {

global $cfg;
$total = $data["total_bronze"] + $data["total_gold"];
if($total!=0)
  $total_percentage = sprintf("%0.2f",($data["total_gold"]*100/$total));
else
  $total_percentage = 0;
  
  $t = "";
  
  $t.= "<script type='text/javascript' src='".$cfg['site']['folder']."js/jquery-1.4.2.min.js'></script>\n";
  $t.= "<script type='text/javascript' src='".$cfg['site']['folder']."js/admin_conversion.js'></script>\n";

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
  $t.= "[<a href='conversion_rates.php?flush_cache=true' id='flush_cache'>flush cache</a>]\n";
$t.= "</p>\n";

$t.= "<p id='dates_picker_wrapper' style='display:none;'>\n";
  $t.="Select Dates: \n";
  $t.="<br /> <strong>From</strong>: \n"."<input type='text' size='12' readonly id='txt_from' name='txt_from' value='".date("Y-m-d",$data["dates"]["start"])."'>&nbsp;<a><img src='".$cfg['site']['folder']."images/calendar.gif' border='0' id='fromTrigger' align='absmiddle'></a>";
  $t.="&nbsp;&nbsp;<strong>To</strong>: \n"."<input type='text' size='12' readonly id='txt_to' name='txt_to' value='".date("Y-m-d",$data["dates"]["stop"])."'>&nbsp;<a><img src='".$cfg['site']['folder']."images/calendar.gif' border='0' id='toTrigger' align='absmiddle'></a>";
  $t.="<br /> <button id='conversion_dates_change'>Filter</button>\n";
$t.= "</p>\n";

$t.= "<p>\nOut of a total of ".$total." users, ".$data["total_gold"]." chose the gold membership (".$total_percentage." %)\n</p>\n";

$t.= "<h2>Per entry point:</h2>\n";

if(isset($data["indexes"])) {
foreach($data["indexes"] as $k=>$v){

  $conversion_rate = "N/A";
  
  if($v["total"]!=0)
  $conversion_rate = sprintf("%0.2f",($v["gold"]*100/$v["total"]));
else
  $conversion_rate = 0;
  

  $t.="<p>\n";
    $t.="<strong>".$k."</strong>: ".$v["total"]." total, ".$v["gold"]." gold, ".$conversion_rate." %\n";
  $t.="</p>\n";
  
  
}
} else {
  $t.="<p>\n No recorded data yet \n </p>\n";
}

$t.= "</div>\n";;

return $t;
}