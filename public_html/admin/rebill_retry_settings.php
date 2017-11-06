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
include_once("../languages/" . $cfg['language'] . ".php"); // load language file
$page->template = "../templates/" . $cfg['language'] . "/default.html"; // load template

include_once("../lib/user.class.php");
include_once("../lib/rebill_cycle.class.php");

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
  } */

$r = new rebill_cycle();

$message = "";

if ($user->userID != 0 && $user->get_user()/* && $user->check_groups($allowGroups) */) {

    if (isset($_POST['save_rebill_retry_settings'])) {

        if (isset($_POST['rebill_retry_active']) && $_POST['rebill_retry_active']=='1') {

            $rebill_retry_months = intval($_POST['rebill_retry_months']);
            
            $r->set_rebill_settings('rebill_retry_active', 1);
            $r->set_rebill_settings('rebill_retry_months', $rebill_retry_months);

            
        } else {
            $r->set_rebill_settings('rebill_retry_active', 0);
        }
        
        $message = "Updated rebill retry settings!";
    }



    $settings = $r->get_rebill_settings();



    $page->blocks['content'] = list_rebill_retry_settings($settings, $message);
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "admin/rebill_retry_settings.php");
}
/*
 * construct and print page
 */
$page->construct_page();  // construct html page
$page->output_page();   // output page


close_database($con);

/*
 * ============================================== page complete here ==============================================
 * The following functions construct content for this page
 */

function list_rebill_retry_settings($settings, $message = "") {
    global $cfg;
    global $lang;
    global $menuActiveIndex;
    $html = '<script type="text/javascript" src="' . $cfg['site']['folder'] . 'js/jquery-1.8.0.min.js"></script>';

    // title

    $html .= "<div class=\"listContent\">\n";
    $html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $html .= "<tr>\n";
    $html .= "<td class=\"titleCell\">Manage Rebill Retry settings</td>\n";
    $html .= "<td align=\"right\">\n";
    $html .= "</td>\n";
    $html .= "</tr>\n";

    if ($message) {
        $html .= "<tr>\n";
        $html .= "<td class=\"titleCell\"></td>\n";
        $html .= "<td align=\"right\">\n";
        $html .= $message . "</td>\n";
        $html .= "</tr>\n";
    }


    $html .= "</table>\n";

    $html .="<form action='' method='post'>\n";
    
    $html .= "<label for='rebill_retry_active'>Rebill Retry active ?</label>";
    $html .= "<input type='hidden' name='rebill_retry_active' value='0' />";
    $html .= "<input type=\"checkbox\" id=\"rebill_retry_active\" name=\"rebill_retry_active\" value=\"1\" ".($settings['rebill_retry_active']==1 ? 'checked':'').">";
    
	$html .= "<br />";
    $html .= "<br />";
	$html .= "<br />";
	
	
    $html .= "<label>Rebill For how many months ?</label>";
    $html .= "<input type=\"number\" name=\"rebill_retry_months\" value=\"".intval($settings['rebill_retry_months'])."\" size='10'>";
    $html .= "<br />";
    
    $html .= "<input type='submit' name='save_rebill_retry_settings' value='Save' />";
            
    $html.= "</form>";

    $html.= "<br /><br />\n";


    
    $html.= "<br />\n";

    $html.="<br /><a href='manage_merchant.php' class='btn'>Manage Merchant Data</a>\n";

    $html .= "</div>\n";
    return $html;
}

?>