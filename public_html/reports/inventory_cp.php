<?php
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/menu.block.php"); // load menu function
include_once("../lib/user.class.php");
include_once("../lib/rebill_cycle.class.php");
include_once("../lib/order.class.php");
include_once("../lib/PayBackEnd.php");
include_once("../lib/inventory.class.php");

$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
$page->template = "../templates/".$cfg['language']."/default.html"; // load template

global $cfg;

$con = connect_database();

$db = get_pdo_db(
		$cfg['database']['user'],
		$cfg['database']['password'],
    $cfg['database']['dbName']
);

$inventory = new Inventory($db,$cfg);

//grab todays inventory
$today_inventory = $inventory->getInventoryDay();

//merge params
if(count($_POST)) {
	
	if(isset($_POST['inventory'])) {
    $new_inventory = $_POST['inventory'];
    
    if(is_numeric($new_inventory)) {
    
      $inventory->updateInventoryDay((int)$new_inventory);
      
      $today_inventory = (int)$new_inventory;
      
    }
    
  }
  
}
  
$user = new umUser();
$user->get_session();


$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "Inventory Control Panel";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."reports/inventory_cp.php");
}

$data  = array();

//define the period dates
$last_month = mktime(0,0,0,date("m")-1,1,date("Y"));
$last_year = mktime(0,0,0,1,1,date("Y")-1);
$week_to_date = mktime(0,0,0,date("m"),date("d")-7,date("Y"));
$month_to_date = mktime(0,0,0,date("m"),/*date("d")-30*/1,date("Y"));
$year_to_date = mktime(0,0,0,date("m"),date("d")-365,date("Y"));


$last_2_weeks = mktime(0,0,0,date("m"),date("d")-14,date("Y"));

$periods = array(
  
  //array("txt"=>"Custom","val"=>"0:0"),
  array("txt"=>"Week To Date","val"=>date("Y-m-d",$week_to_date).":".date("Y-m-d")),
  array("txt"=>"Month To Date","val"=>date("Y-m-d",$month_to_date).":".date("Y-m-d")),
  array("txt"=>"Last Month (".date("F Y",$last_month).")","val"=>date("Y-m-1",$last_month).":".date("Y-m-t",$last_month)),  
);

for($j=2;$j<7;$j++) {
$dt = mktime(0,0,0,date("m")-$j,1,date("Y"));
$periods[] = array("txt"=>$j." Months Ago (".date("F Y",$dt).")","val"=>date("Y-m-1",$dt).":".date("Y-m-t",$dt));
}

$periods[] = array("txt"=>"Year To Date","val"=>date("Y-m-d",$year_to_date).":".date("Y-m-d"));
$periods[] = array("txt"=>"Last Year","val"=>date("Y-1-1",$last_year).":".date("Y-12-31",$last_year));




$order = new order;

//fetch units sold in the various periods
foreach($periods as &$period) {
  $parts = explode(":",$period['val']);
  $period['total'] = $order->get_total_qtys_date_restricted($parts[0],$parts[1]);  
}
//print_r($periods);
$data['periods'] = $periods;

$data['today_inventory'] = $today_inventory;

$data['history_start'] = date("Y-m-d",$last_2_weeks);
$data['history_end'] = date("Y-m-d");
$data['history'] = $inventory->getInventoryBetween($data['history_start'],$data['history_end']);


$page->blocks['content'] = markup($data,$view_params);	
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page




function markup($data,$params=array()) {

global $cfg;

$t= "<script type='text/javascript' src='".$cfg['site']['folder']."js/jquery-1.4.2.min.js'></script>\n";
$t.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-ui.min-1.8.8.js"></script>
<link rel="stylesheet" type="text/css" href="'.$cfg['site']['folder'].'styles/jquery-ui-1.8.8.css"  />';
$t.= "<script type='text/javascript' src='".$cfg['site']['folder']."js/reports/inventory_cp.js'></script>\n";

$t.= "<div class='listContent'>\n";



$current_date = "";

$t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
$t .= "<tr>\n";
$t .= "<td class=\"titleCell\">Inventory Control Panel</td>\n";
$t.= "<td align='left'>Server Time:".date("d-m-Y H:i:s")."</td>\n";
$t.= "<td align='right'></td>\n";
$t.= "</tr>\n";
$t.= "</table>\n";

$t.= "<form action='' method='POST' id='inventory_cp_form' onsubmit='return update_inventory();'>\n";


/*
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
*/
//start listing the data

$t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";

  $t.= "<tr class='captionRow'>\n";
  $t.= "<td colspan='2'>Units Shipped</td>";
  $t.= "<td>Current Inventory</td>";
  $t.= "</tr>";
  
 
    foreach($data['periods'] as $key=>$period) {
    
      if($key % 2 == 0){
          $t.= "<tr class=\"dataRow1 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow1 drow'\">\n";
        }else{
          $t.= "<tr class=\"dataRow2 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow2 drow'\">\n";
        }
        
      $t.="<td>";
        $t.=$period['txt'];
      $t.="</td>";
      
      $t.="<td>";
        $t.=$period['total'];
      $t.="</td>";
      
      if($key==0) {
        $t.= "<td rowspan='".count($data['periods'])."' style='text-align:center'>";
          $t.= "<input placeholder='Input the inventory' name='inventory' value='".$data['today_inventory']."' style='padding:5px;font-size:18px;'> &nbsp;&nbsp;";
          $t .= "<input type='submit' name='viewBtn' value='Update Inventory' class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"'>\n";
        $t.= "</td>";
      }
      
       $t.="</tr>\n";
      
    }
    
 
  
  $t.= "</table>\n";
$t .= "</form>\n";

$t.="<div style='padding-top:50px;'>";
  $t.="<h3>Last 2 Weeks Inventory History(".$data['history_start']." - ".$data['history_end'].")</h3>";
  $t.= "<table width=\"300\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
    $t.= "<tr class='captionRow'>\n";
  $t.= "<td>Date</td>";
  $t.= "<td>Value</td>";
  $t.= "</tr>";
  foreach($data['history'] as $key=>$h) {
  
     if($key % 2 == 0){
          $t.= "<tr class=\"dataRow1 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow1 drow'\">\n";
        }else{
          $t.= "<tr class=\"dataRow2 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow2 drow'\">\n";
        }
        
      $t.="<td>";
        $t.=$h['inventory_day'];
      $t.="</td>";
      
      $t.="<td>";
        $t.=$h['inventory_value'];
      $t.="</td>";  
      

       $t.="</tr>\n"; 
        
  }
  $t.= "</table>\n";
$t.="</div>";

 $t.="<div id='dialog_confirm' title='Are you sure ?'>";
  $t.= "<h2>Are you sure you want to update the Inventory?</h2>\n";
  $t.= "<button class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='inventory_update();'>YES!</button>\n";
  $t.= "&nbsp;&nbsp;\n";
  $t.= "<button class='btn' onmouseover='this.className=\"btnhov\"' onmouseout='this.className=\"btn\"' onclick='inventory_update_cancel();'>NOT REALLY</button>\n";
 $t.= "</div>\n";
 
 $t.= "</div>\n";
 
 return $t; 
}
