<?php 
/*
 * init web page
 */
header("Cache-Control:no-cache,must-revalidate");
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
include_once("../lib/protect.class.php");
include_once("../lib/group_price.class.php");
$con = connect_database();
/*
 * create content blocks
 * page is built in this part
 */

if(!isset($_POST['pageSize'])) $_POST['pageSize'] = 10; // set default page size
if(!isset($_POST['protectType']) && isset($_GET['protectType'])) $_POST['protectType'] = $_GET['protectType'];

$user = new umUser();
$user->get_session();
$allowGroups = $cfg['site']['adminGroupIDs'];

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "Manage Group Price";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
} else {
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/mem_group_price.php");	
}
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$groupObj = new umGroupPrice();
	if (isset($_POST['action'])){
		$groupObj->updatePrice($_POST);
	}
	$page->blocks['content'] = show_list();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/mem_group_price.php");
}

/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

function show_list($errorMessage=""){
	global $cfg;
	global $lang;
	global $groupObj;
	
	$html = "";
	
	$html .= "<script language=\"javascript\">\n";
	$html .= "	function saveListForm(){\n";
	$html .= "		document.listForm.action.value=\"save\";\n";
	$html .= "		document.listForm.submit();\n";
	$html .= "	}\n";
	$html .= "</script>\n";
	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "	<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "		<tr>\n";
	$html .= "			<td class=\"titleCell\">\n";
	$html .= "				Manage Group Price";
	$html .= "			</td>\n";
	$html .= "			<td align=\"right\">\n";
	$html .= "				<input type=\"button\" value=\"Save\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onClick=\"saveListForm();\">\n";
	$html .= "			</td>\n";
	$html .= "		</tr>\n";
	$html .= "	</table>\n";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	// page navigation
	$html .= "<p align='center'>\n";
	
	$html .= "<table width=\"60%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\" align=\"center\">\n";
	$html .= "		<tr class=\"captionRow\">\n";
	$html .= "			<td width=\"10%\">No</td>\n";
	$html .= "			<td width=\"30%\">Group ID</td>\n";
	$html .= "			<td width=\"30%\">Price</td>\n";
	$html .= "			<td width=\"30%\">Upkeep</td>\n";	
	$html .= "		</tr>";	
	$html .= "	<form action=\"".sess_url($cfg['site']['folder']."admin/mem_group_price.php")."\" method=\"post\" name=\"listForm\">\n";
		$groupPrice = $groupObj->getPrice();
		for($i = 0; $i < count($groupPrice); $i++){
			if($i % 2 == 0){
				$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
			}else{
				$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
			}
			
			$html .= "<td align=\"left\">".($i+1)."</td>\n";
			$html .= "<td>".$groupPrice[$i]['ID']."</td>\n";
			$html .= "<td><input type=\"text\" size=\"40\" name=\"Price-".$groupPrice[$i]['ID']."\" value=\"".$groupPrice[$i]['Price']."\"></td>\n";
			$html .= "<td><input type=\"text\" size=\"40\" name=\"Upkeep-".$groupPrice[$i]['ID']."\" value=\"".$groupPrice[$i]['Upkeep']."\"></td>\n";
		}
	$html .= "		<input type=\"hidden\" name=\"action\">";
	$html .= "	</form>\n";
	$html .= "</table>\n";
	$html .= "</p>\n";
	$html .= "</div>\n";

	return $html;
}
close_database($con);
?>