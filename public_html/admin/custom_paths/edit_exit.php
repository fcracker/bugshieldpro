<?php
include_once("../../lib/config.inc.php");
include_once("../../lib/database.inc.php");
include_once("../../lib/page.class.php");
include_once("../../lib/menu.class.php");
include_once("../../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../../languages/".$cfg['language'].".php"); // load language file
$page->template = "../../templates/".$cfg['language']."/default.html"; // load template

include_once("../../lib/user.class.php");
include_once("../../lib/exits.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];


	$page->blocks['title'] = "Custom Paths";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
/*
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "Email Campaigns";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/email_campaigns.php");
}
*/


$custom_exit = new custom_exit($cfg);

$exit_id = isset($_GET['exit_id']) ? intval($_GET['exit_id']) : -1;

$exit = array();
$go_to_list = false;

if($exit_id > 0) {

	if(isset($_POST['popup_text']) && isset($_POST['unit_price'])) {
		if(strlen($_POST['popup_text']) && strlen($_POST['unit_price'])) {
			$custom_exit->edit_exit($exit_id,$_POST);
			$go_to_list = true;
		}
	}

	$exit = $custom_exit->get_exit_by_id($exit_id);
} else {
	if(isset($_POST['popup_text']) && isset($_POST['unit_price'])) {
		if(strlen($_POST['popup_text']) && strlen($_POST['unit_price'])) {
			$custom_exit->add_exit($_POST);
			$go_to_list = true;
		}
	}
}

if($go_to_list) {
	header("Location:exits.php?updated=1");
	exit;
}




$page->blocks['content'] = exit_edit($exit);	

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

function exit_edit($exit) {
	
	global $cfg;
	$new = !isset($exit['exit_id']);
	$html = "";
	
	$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.8.0.min.js"></script>';
	$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/fckeditor/fckeditor.js"></script>';
	

	$html .= "<div class=\"listContent\">\n";
	
		$html .= "<div class=\"formDiv\">";
			$html .= "<form name=\"mainform\" action=\"edit_exit.php?exit_id=".($new ? "-1":$exit['exit_id'])."&menuIndex=".$menuActiveIndex."\" method=\"post\" class=\"formLayer\">";
			$html .= "<fieldset>";
			$html .= "<legend>".(!$new ? "Edit":"Create")." Exit</legend>";
				$html .= "<label>Popup Text</label>";
				$html .= "<textarea name='popup_text' cols='100' rows='15'>".($new ? "":$exit['popup_text'])."</textarea>\n";
				$html .= "<br />";
				
				$html .= "<label>Unit Price</label>";
				$html .= "<input type=\"text\" name=\"unit_price\" value=\"".($new ? "":$exit['unit_price'])."\" size=\"50\">";
				$html .= "<br />";
				
				$html .= "<label>Page Discount Text</label>";
				$html .= "<textarea name='discount_page_text' cols='100' rows='15' class='editor_area'>".($new ? "":$exit['discount_page_text'])."</textarea>\n";
				$html .= "<br />";
				
				$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"Save\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
				$html .= "<br />";
				$html .= "<br />";
				
			$html .= "</fieldset>";
			$html .= "</form>";
		$html .= "</div>\n";
		
		
	
	$html .= "</div>\n";
	
	$html.= '<script>FCKeditor.BasePath = "/js/fckeditor/";FCKeditor.Height = "300";FCKeditor.MinHeight = "300";FCKeditor.ReplaceAllTextareas("editor_area");</script>';
	
	
	return $html;
}