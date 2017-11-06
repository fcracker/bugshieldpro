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
if(/*$menuActiveIndex>0*/true){
	$page->blocks['title'] = "Tracking Setup";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/tracking_setup.php");
}

if(isset($_GET["from"]) && isset($_GET["to"])) {
  $from = ($_GET["from"]);
  $to = ($_GET["to"]);
  
  if($from > $to)
  {
    //exchange them
    $tmp = $from;
    $from = $to;
    $to = $tmp;
  } 
 
} else {
	//have some defaults, like ..today
	$to=$from=date("Y-m-d");
}

$msg = "";

if(isset($_POST["save"])) {

$name = $_POST["name"];
$data = $_POST["data"];
$percent = intval($_POST["percent"]);

//limits
if($percent<0) $percent=0;
if($percent>100) $percent=100;

if(isset($_POST["id"])) $sql = "update "; else $sql = "insert into ";

$sql.= "mem_tracking_pixels set tracking_name='".mysql_real_escape_string($name)."',tracking_value='".mysql_real_escape_string($data)."',tracking_percent=".$percent;

if(isset($_POST["id"])) $sql.=" where trackingid=".intval($_POST['id']);

if(mysql_query($sql)) {
$msg = "Tracking item ".(isset($_POST["id"]) ? "updated!":"inserted into the database!");
}

}

if(isset($_GET["delete"])) {
	$id = intval($_GET["delete"]);
	if(mysql_query("delete from mem_tracking_pixels where trackingid=".$id." limit 1")) {
		$msg = "Tracking item removed!";
	}
}

//get the items
$sql = "select * from mem_tracking_pixels order by trackingid desc";
$result = mysql_query($sql);

$items = array();

while($r = mysql_fetch_object($result)) {
	$items[]=$r;
}






$page->blocks['content'] = tracking($items,$msg,$from,$to);	

$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

close_database($con);


function tracking($items,$msg,$from,$to) {

global $cfg;

$ps = array();
$ng = array();
$keys = array();


//fetch the analytics

$positives_result = mysql_query("SELECT COUNT(h.status) as k,t.tracking_name FROM `mem_tracking_pixels` t join mem_tracking_hit h on t.trackingid=h.trackingid where h.hit_time>'".$from." 00:00:00' and h.hit_time<'".$to." 23:59:59'  and h.status=1 group by tracking_name");

while($positives_row = mysql_fetch_object($positives_result)) {

$ps[$positives_row->tracking_name] = $positives_row->k;

if(!in_array($positives_row->tracking_name,$keys)) $keys[]=$positives_row->tracking_name;

}

$negatives_result = mysql_query("SELECT COUNT(h.status) as k,t.tracking_name FROM `mem_tracking_pixels` t join mem_tracking_hit h on t.trackingid=h.trackingid where h.hit_time>'".$from." 00:00:00' and h.hit_time<'".$to." 23:59:59'  and h.status=0 group by tracking_name");

while($negatives_row = mysql_fetch_object($negatives_result)) {

$ng[$negatives_row->tracking_name] = $negatives_row->k;

if(!in_array($negatives_row->tracking_name,$keys)) $keys[]=$negatives_row->tracking_name;

}


	$t = "";

$t.= "<div class='listContent'>\n";

$t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
$t .= "<tr>\n";
$t .= "<td class=\"titleCell\">Tracking Setup</td>\n";
$t.= "<td align='left'>Server Time:".date("d-m-Y H:i:s")."</td>\n";
$t.= "<td align='right'></td>\n";
$t.= "</tr>\n";
$t.= "</table>\n";


 $t.= "<div style='padding:20px 10px 10px 30px;'>";
 
 $t.= "<script type='text/javascript' src='".$cfg['site']['folder']."js/jquery-1.4.2.min.js'></script>\n";
  $t.= "<script type='text/javascript' src='".$cfg['site']['folder']."js/tracking_pixels.js'></script>\n";
  
  if(strlen($msg)) {
  $t.= "<p>\n";
    $t.="Messages:<br />\n";
    $t.="<strong>".$msg."</strong>";
  $t.= "</p>\n";
}




$t.= "<p>\n";
  $t.="Showing data between dates: ".$from." -> ".$to.".\n";
  $t.= "[<a href='#' id='show_dates'>change</a>]\n";
$t.= "</p>\n";




$t.= "<p id='tracking_picker_wrapper' style='display:none;float:left;clear:both;'>\n";
  $t.="Select Dates: \n";
  $t.="<br /> <strong>From</strong>: \n"."<input type='text' size='12' readonly id='txt_from' name='txt_from' value='".$from."'>&nbsp;<a><img src='".$cfg['site']['folder']."images/calendar.gif' border='0' id='fromTrigger' align='absmiddle'></a>";
  $t.="&nbsp;&nbsp;<strong>To</strong>: \n"."<input type='text' size='12' readonly id='txt_to' name='txt_to' value='".$to."'>&nbsp;<a><img src='".$cfg['site']['folder']."images/calendar.gif' border='0' id='toTrigger' align='absmiddle'></a>";
  $t.="<br /> <button id='tracking_dates_change'>Filter</button>\n";
$t.= "</p>\n";

$t.= "<div style='padding:10px;border:1px solid #CCC;float:left;clear:both;'>";
	foreach($keys as $pixel) {
	
		$poz = isset($ps[$pixel]) ? intval($ps[$pixel]):0;
		$neg = isset($ng[$pixel]) ? intval($ng[$pixel]):0;
		$tot = $poz+$neg;
		
		if($tot>0)
		$percentage = ($poz*100)/$tot;
		else
		$percentage = 0;
		
	
		$t.="<p style='float:left;padding:10px;margin-right:20px;'>";
			$t.= "<strong>".$pixel."</strong><br />";
			$t.= "Hits: ".$poz."<br />";
			$t.= "Misses: ".$neg."<br />";
			$t.= "Total: ".$tot."<br />";
			$t.= "Real Rate: ".round($percentage,2)."%<br />";
		$t.="</p>";
	}
$t.= "<div style='clear:both;'></div></div>";

foreach($items as $item) {
	$t.="<div style='padding:10px;border:1px solid #333;float:left;width:500px;clear:both;margin:5px 0;''>";
		$t.= "<strong>".$item->tracking_name."</strong>";
        $t.= " (".$item->tracking_percent."%) ";
		$t.= " <a href='#' onclick='tracking_edit(\"t".$item->trackingid."\")'>[view]</a> ";
		$t.= " <a href='tracking_setup.php?delete=".$item->trackingid."' onclick='return confirm(\"Are you sure?\")'>[delete]</a>";
		
		$t.= "<div id='t".$item->trackingid."' style='display:none;'>";
			$t.= "<form action='' method='POST'>";
			$t.= "<p>";
				$t.= "Name:<br /> "."<input type='text' name='name' value='".$item->tracking_name."' style='width:300px;padding:5px;font-size:14px;' />";				
			$t.= "</p>";
			$t.= "<p>";
				$t.= "Value:<br /> "."<textarea name='data' cols='90' rows='7'>".htmlentities($item->tracking_value)."</textarea>";
			$t.= "</p>";
			$t.= "<p>";
				$t.= "Percent(0-100):<br /> "."<input type='text' name='percent' value='".$item->tracking_percent."' style='width:300px;padding:5px;font-size:14px;' />%";				
			$t.= "</p>";
			$t.= "<p>";
				$t.= "<input type='submit' name='save' value='Update' style='padding:5px;font-size:16px;'/>";	
				$t.= "<input type='hidden' name='id' value='".$item->trackingid."' />";
			$t.= "</p>";
		$t.= "</form>";
		$t.= "</div>";
		
	$t.="</div>";
}


//add form

$t.="<div style='padding:10px;border:1px solid #333;float:left;clear:both;margin:5px 0;'>";
		$t.= "<h3>Add a new one</h3>";
		$t.= "<form action='' method='POST'>";
			$t.= "<p>";
				$t.= "Name:<br /> "."<input type='text' name='name' value='' style='width:300px;padding:5px;font-size:14px;' />";				
			$t.= "</p>";
			$t.= "<p>";
				$t.= "Value:<br /> "."<textarea name='data' cols='90' rows='7'></textarea>";
			$t.= "</p>";
			$t.= "<p>";
				$t.= "Percent(0-100):<br /> "."<input type='text' name='percent' value='' style='width:300px;padding:5px;font-size:14px;' />%";				
			$t.= "</p>";
			$t.= "<p>";
				$t.= "<input type='submit' name='save' value='Insert' style='padding:5px;font-size:16px;'/>";				
			$t.= "</p>";
		$t.= "</form>";
$t.="</div>";






return $t."</div></div>";

}