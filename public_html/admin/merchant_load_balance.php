<?php
/*
 * init web page
 */

//session_start();
//$_SESSION['result'] = array();
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
include_once("../lib/PayBackEnd.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];

$page->blocks['title'] = $lang['title']['manageUsers'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();

/*
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['manageUsers'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/merchant_load_balance.php");
}*/

$merchant = new PayBackEnd();

$message = "";

if($user->userID != 0 && $user->get_user()/* && $user->check_groups($allowGroups)*/){
	
	if(isset($_POST['save_percentages'])){
  
		//get the percentages
    if(is_array($_POST['load_balance'])) {
      
      foreach($_POST['load_balance'] as $mid=>$percent) {
        
        
        $merchant->update_percent_for_merchant($mid,$percent);
        
      }
      
      $message = "UPDATED PERCENTAGES!";
    }
	}
  
  if(isset($_POST['save_amex_percentages'])){
  
		//get the percentages
    if(is_array($_POST['amex_load_balance'])) {
      
      foreach($_POST['amex_load_balance'] as $mid=>$percent) {
        
        
        $merchant->update_amex_percent_for_merchant($mid,$percent);
        
      }
      
      $message = "UPDATED AMEX PERCENTAGES!";
    }
	}
	
  
  
	$merchantResult = $merchant->get_merchant(1);
  

  
	$page->blocks['content'] = list_load_balance($merchantResult,$message);	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/merchant_load_balance.php");
}
/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page


close_database($con);

/*
* ============================================== page complete here ==============================================
* The following functions construct content for this page
*/

function list_load_balance($merchantResult,$message=""){
	global $cfg;
	global $lang;
	global $menuActiveIndex;
	$html = '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.8.0.min.js"></script>
  <script type="text/javascript" src="'.$cfg['site']['folder'].'js/colresizable.js"></script>
  <script type="text/javascript" src="'.$cfg['site']['folder'].'js/admin_load_balance.js"></script>';
	
	// title
  
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">Manage Load Balance</td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= "</td>\n";
  $html .= "</tr>\n";
  
  if($message) {
  $html .= "<tr>\n";
	$html .= "<td class=\"titleCell\"></td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= $message."</td>\n";
  $html .= "</tr>\n";
  }
  
	
	$html .= "</table>\n";
	
	$html  .="<form action='' method='post'>\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\" id='load_balance_table'>\n";
	$html .= "<tr>\n";
  
  $backend = new PayBackEnd();
  
  //$html= "<pre>".print_r($merchantResult,1)."</pre>";
  
  
  //I had this beautiful hex sum thing that would generate colors ad infinitum, but it was just too bland (the distribution I mean)
  $possible_colors = array(
  
    "Red",
    "Blue",
    "Yellow",
    "Green",
    "DeepPink",
    "ForestGreen",
    "Gold",
    "LightCoral"
  
  );
  
  
  $merchant_data = array(); 
  
	// list data
	$count = count($merchantResult);
  
	for($i=0; $i < $count; $i++){
  
    $new_color = isset($possible_colors[$i]) ? $possible_colors[$i] : $possible_colors[rand(0,count($possible_colors)-1)];
    
  
    $html .= "<td rel='".$merchantResult[$i]['BankID']."' height='30' bgcolor='".($new_color)."' width='".$merchantResult[$i]['persent']."%'>\n";
    $html .= "<input type='hidden' name='load_balance[".$merchantResult[$i]['BankID']."]' value='".$merchantResult[$i]['persent']."' />"; 
    //save the color
    $merchant_data[$merchantResult[$i]['BankName']] = array(
      "color"=>$new_color,
      "percentage"=>$merchantResult[$i]['persent'],
      "can_amex"=>$merchantResult[$i]['can_process_amex']>0,      
      "amex_percentage"=>$merchantResult[$i]['amex_probability'],
      "id"=>$merchantResult[$i]['BankID']
      );    
    
		
	
   $html .= "</td>\n";

	
		
	}
	$html .= "</tr>\n";
	$html .= "</table>\n";
  
  $html.= "<br />\n"; 
  
  $html.= "<input class='largerbtn' type='submit' name='save_percentages' value='Save General percentages' />\n";
  $html.= "</form>";
  
  $html.= "<br /><br />\n"; 
  
   
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">Manage American Express Load Balance</td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= "</td>\n";
  $html .= "</tr>\n";
	$html .= "</table>\n";
  
  $html  .="<form action='' method='post'>\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\" id='amex_load_balance_table'>\n";
	$html .= "<tr>\n";
  
  for($i=0; $i < $count; $i++){    
  
  if($merchantResult[$i]['can_process_amex']>0) {
  
      $color =  $merchant_data[$merchantResult[$i]['BankName']]['color'];
      $html .= "<td rel='".$merchantResult[$i]['BankID']."' height='30' bgcolor='".($color)."' width='".$merchantResult[$i]['amex_probability']."%'>\n";
      
      $html .= "<input type='hidden' name='amex_load_balance[".$merchantResult[$i]['BankID']."]' value='".$merchantResult[$i]['amex_probability']."' />"; 
      
     $html .= "</td>\n";
   }

	
		
	}
	$html .= "</tr>\n";
  
  $html .= "</table>\n";
  $html.= "<br />\n"; 
  
  $html.= "<input class='largerbtn' type='submit' name='save_amex_percentages' value='Save American Express percentages' />\n";
  $html.= "</form>";
  
 
  
    $html.= "<br /><br /><table cellpadding=5 cellspacing=5>";
    $html.= "<tr>\n";
    $html.= "</tr>\n";
      $html.= "<td align='center' colspan='2'>Bank Name</td>\n";
      $html.= "<td align='center'>General</td>\n";
      $html.= "<td align='center'>Amex</td>\n";
    $html .= "<tr>\n";
      $html .= "<td bgcolor='#F0FFF0' height='1' colspan=3></td>\n";
    $html .= "</tr>\n";
  
  foreach($merchant_data as $merchant_name=>$merchant_data) {
  
    $html .= "<tr>\n";
      $html .= "<td bgcolor='".$merchant_data['color']."' width='50'>&nbsp;</td>\n";
      $html .= "<td>".$merchant_name."</td>\n";
      $html .= "<td><span id='mpi".$merchant_data['id']."'>".$merchant_data['percentage']."</span>%</td>\n";
      $html .= "<td><span id='amx".$merchant_data['id']."'>".($merchant_data['can_amex'] ? $merchant_data['amex_percentage'] : "N/A")."</span>".($merchant_data['can_amex'] ? "%" : "")."</td>\n";
    $html .= "</tr>\n";
    
    $html .= "<tr>\n";
      $html .= "<td bgcolor='#F0FFF0' height='1' colspan=3></td>\n";
    $html .= "</tr>\n";
    
     
    
  }
  
  $html .= "<tr>\n";
      $html .= "<td bgcolor='#F0FFF0'' colspan=3>(If you want a merchant to be zero %, disable it in the manage merchants menu)</td>\n";
    $html .= "</tr>\n";
  
  
  $html .= "</table>\n";
  $html.= "<br />\n"; 
  
   $html.="<br /><a href='manage_merchant.php' class='btn'>Manage Merchant Data</a>\n";
  
	$html .= "</div>\n";
	return $html;
}
?>