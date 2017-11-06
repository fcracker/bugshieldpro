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

include_once("../lib/order.class.php");
include_once("../lib/PayBackEnd.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "User Feed";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."reports/orders.php");
}

$data = array();


$data['delay'] = isset($_POST['delay']) ? intval($_POST['delay']) : 20;//minutes of delay

$data['limit'] = isset($_POST['limit']) ? intval($_POST['limit']) : 3;//users not older than X days

$data['device'] = (isset($_POST['device']) && is_array($_POST['device']) && count($_POST['device'])) ? implode(',',$_POST['device']) : "phone,desktop,tablet";//what type of device(s) to fetch

$devices = explode(",",$data["device"]);
array_walk($devices,create_function('&$val', '$val = "\'$val\'";'));

$sql = "SELECT * from mem_signup_log WHERE DATE_ADD(AccessDate,INTERVAL ".$data['delay']." MINUTE) <='".date("Y-m-d H:i:s")."' AND AccessDate > '".date("Y-m-d H:i:s",strtotime("-".$data['limit']." days",time()))."' AND device_type IN (".implode(',',$devices).") GROUP BY Email ORDER BY AccessDate DESC";

//echo $sql;

$data['visitors'] = multi_query_assoc($sql);


$page->blocks['content'] = markup($data);	

$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

close_database($con);

function markup($data) {

global $cfg;

$selected_devices = explode(",",$data['device']);

$t.= "<div class='listContent'>\n";

$t.= "<form action='' method='POST' id='user_feed_form'>\n";

$t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
$t .= "<tr>\n";
$t .= "<td class=\"titleCell\">User feed</td>\n";
$t.= "<td align='left'>Server Time:".date("d-m-Y H:i:s")."</td>\n";
$t.= "<td align='right'>listing ".count($data["visitors"])." visitors</td>\n";
$t.= "</tr>\n";

$t .= "<td class=\"titleCell\"></td>\n";

$t.= "<td align='left'>\n";

  $t.= "Show visitors older than <select name='delay'>\n";
    $delay_vals = array(5,10,15,20,30,45,60,120,180);
    foreach($delay_vals as $dv) {
      $t.= "<option value='".$dv."'".($dv==$data["delay"] ? " selected":"").">".(($dv%60==0) ? ($dv/60)." hour(s)":$dv." minutes")."</option>\n";
    }
  $t.= "</select>";
  
  $t.= "&nbsp;&nbsp;\n";
  
  
  $t.= "But not older than <select name='limit'>\n";
    $limit_vals = array(1,2,3,4,5,7,10,14,21,30);
    foreach($limit_vals as $lv) {
      $t.= "<option value='".$lv."'".($lv==$data["limit"] ? " selected":"").">".$lv." days</option>\n";
    }
  $t.= "</select>";
  
  $t.= "&nbsp;&nbsp;\n";
  
   $t.= "And used device: \n";
    $devices = array("desktop","phone","tablet");
    foreach($devices as $device) {
      $t.= "&nbsp;<input type='checkbox' id='uf_".$device."'".(in_array($device,$selected_devices) ? " checked":"")." name='device[]' value='".$device."' />&nbsp;<label for='uf_".$device."'>".$device."</label>&nbsp;";
    }     
  
  $t.= "</td>\n";
$t.= "<td align='right'><input type='submit' name='filter_user_feed' value='Filter' /></td>\n";
$t.= "</tr>\n";

$t.= "</table>\n";


$t.= "</form>\n";


$t.= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
$t .= "<tr>\n";
$t .= "<td class=\"titleCell\"></td>\n";
$t.= "</tr>\n";
$t.= "</table>\n";

if(count($data['visitors'])) {



$t .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";

  $t.= "<tr class='captionRow'>\n";
  $t.= "<td>Access Date</td>";
  $t.= "<td>Full Name</td>";
  $t.= "<td>Country</td>";
  $t.= "<td>State</td>";
  $t.= "<td>City</td>";
  $t.= "<td>Address</td>";
  $t.= "<td>ZIP</td>";
  $t.= "<td>Phone</td>";
  $t.= "<td>Email</td>";
  $t.= "<td>Device</td>";
  $t.= "</tr>";
  

foreach($data['visitors'] as $key=>$d) {


      if($key % 2 == 0){
            $t.= "<tr class=\"dataRow1 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow1 drow'\">\n";
          }else{
            $t.= "<tr class=\"dataRow2 drow\" onmouseover=\"this.className='heightDataRow drow'\" onmouseout=\"this.className='dataRow2 drow'\">\n";
          }	
    
  
  $t.= "<td>".$d['AccessDate']."</td>";
  $t.= "<td>".$d['FullName']."</td>";
  $t.= "<td>".$d['Country']."</td>";
  $t.= "<td>".$d['State']."</td>";
  $t.= "<td>".$d['City']."</td>";
  $t.= "<td>".$d['Address']."</td>";
  $t.= "<td>".$d['PostalCode']."</td>";
  $t.= "<td>".$d['Telephone']."</td>";
  $t.= "<td>".$d['Email']."</td>";
  $t.= "<td>".$d['device_type']."</td>";
   
    
$t.="</tr>\n";

}





$t.= "</table>\n";
}


$t.= "</div>\n";

return $t;
  
}

