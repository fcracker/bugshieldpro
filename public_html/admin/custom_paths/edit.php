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
include_once("../../lib/custom_paths.class.php");
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


$custom_path = new custom_path($cfg);

$path_id = isset($_GET['path_id']) ? intval($_GET['path_id']) : -1;

$path = array();
$go_to_list = false;

if($path_id > 0) {

	if(isset($_POST['path_name']) && isset($_POST['path_folder'])) {
		if(strlen($_POST['path_name']) && strlen($_POST['path_folder'])) {
			$custom_path->edit_path($path_id,$_POST);
			$go_to_list = true;
		}
	}

	$path = $custom_path->get_path_by_id($path_id);
} else {
	if(isset($_POST['path_name']) && isset($_POST['path_folder'])) {
		if(strlen($_POST['path_name']) && strlen($_POST['path_folder'])) {
			$custom_path->add_path($_POST);
			$go_to_list = true;
		}
	}
}

if($go_to_list) {
	header("Location:paths.php?updated=1");
	exit;
}




$page->blocks['content'] = path_edit($path);	

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

function path_edit($path) {
	
	global $cfg;
	
	$exit_class = new custom_exit($cfg);
	$custom_path = new custom_path($cfg);
	
	$exits = $exit_class->get_exits();
	
	$exits_for_path = array();
	
	if(isset($path['custom_path_id'])) {
		$exits_for_path = $custom_path->get_exits_for_path($path['custom_path_id']);
	}
	
	$new = !isset($path['custom_path_id']);
	$html = "";
	
	$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.8.0.min.js"></script>';
	$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/email_campaigns.js"></script>';

	$html .= "<div class=\"listContent\">\n";
	
		$html .= "<div class=\"formDiv\">";
			$html .= "<form name=\"mainform\" action=\"edit.php?path_id=".($new ? "-1":$path['custom_path_id'])."&menuIndex=".$menuActiveIndex."\" method=\"post\" class=\"formLayer\">";
			$html .= "<fieldset>";
			$html .= "<legend>".(!$new ? "Edit":"Create")." Path</legend>";
				$html .= "<label>Path Name</label>";
				$html .= "<input type=\"text\" name=\"path_name\" value=\"".($new ? "":$path['path_name'])."\" size=\"50\">";
				$html .= "<br />";
				
				$html .= "<label>Path Folder</label>";
				$html .= "<input type=\"text\" name=\"path_folder\" value=\"".($new ? "":$path['path_folder'])."\" size=\"50\">";
				$html .= "<br />";
				
				$html .= "<label>Attached Exits</label>";
				
				foreach($exits as $exit) {
					$html .= "<br />";
					$html .= "<input type=\"checkbox\" name=\"path_exits[]\" value=\"".($exit['exit_id'])."\" id='exit_".$exit['exit_id']."' ".(in_array($exit['exit_id'],$exits_for_path) ? "checked":"").">";
					$html .= "&nbsp;&nbsp;";
					$html .= "<label for='exit_".$exit['exit_id']."'>[".substr($exit['popup_text'],0,20)."...]</label>";
					$html .= "<br />";
				}
				
				$html .= "<br />";
				
				
				$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"Save\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
				$html .= "<br />";
				$html .= "<br />";
				
			$html .= "</fieldset>";
			$html .= "</form>";
		$html .= "</div>\n";
		
		
	
	$html .= "</div>\n";
	
	
	return $html;
}