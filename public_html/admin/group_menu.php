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

$menuActiveIndex = $_GET['activeID'];
if($menuActiveIndex>0){
	$page->blocks['title'] = "Manage Group Menu";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
} else {
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/group_menu.php");	
}
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(isset($_GET['groupID'])){
		$menu = new umMenu();
		$menu->groupID = $_GET['groupID'];
		if(isset($_POST['operater'])){
			if(isset($_POST['selectedMenu'])) $menu->menus = $_POST['selectedMenu'];
			else $menu->menus = array();
			$menu->set_group_menus();
		}
		$menu->get_group_menus();
		
		$group = new umGroup();
		$group->groupID = $_GET['groupID'];
		$group->get_group();
		
		$page->blocks['content'] = show_list($group->groupTitle);
		$group = null;
	}else{
		$page->blocks['content'] = show_message("Unselected Group");
	}	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/group_menu.php");
}

/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

function show_list($groupTitle){
	global $cfg;
	global $lang;
	global $menu;
	global $menuActiveIndex;
	$html = "";
	
	$html .= "<script language=\"javascript\">\n";
	$html .= "function saveListForm(){\n";
	$html .= "document.listForm.operater.value = 'save';\n";
	$html .= "document.listForm.submit();\n";
	$html .= "}\n";
	
	$html .= "function selectAll(){\n";
	$html .= "for(var i = 0; i < document.listForm.length; i++){\n";
	$html .= "if(document.listForm.elements[i].type == 'checkbox') document.listForm.elements[i].checked = document.listForm.allMenu.checked;\n";
	$html .= "}\n";
	$html .= "}\n";
	
	$html .= "function selectMenu(chkObj){\n";
	$html .= "if(chkObj.getAttribute('f2')==\"0\"){\n";
	$html .= "for(var i = 0; i < document.listForm.length; i++){\n";
	$html .= "if(document.listForm.elements[i].type == 'checkbox' && document.listForm.elements[i].getAttribute('f1')==chkObj.getAttribute('f1'))\n";
	$html .= "document.listForm.elements[i].checked = chkObj.checked;\n";
	$html .= "}\n";
	$html .= "}else{\n";
	$html .= "var chkFlag = false;\n";
	$html .= "for(var i = 0; i < document.listForm.length; i++){\n";
	$html .= "if(document.listForm.elements[i].type == 'checkbox' && document.listForm.elements[i].getAttribute('f1')==chkObj.getAttribute('f1')){\n";
	$html .= "if(document.listForm.elements[i].getAttribute('f2')==0){\n";
	$html .= "chkObj = document.listForm.elements[i];\n";
	$html .= "continue;\n";
	$html .= "}else{\n";
	$html .= "if(chkFlag==false && document.listForm.elements[i].checked) chkFlag = true;\n";
	$html .= "}\n";
	$html .= "}\n";  
	$html .= "}\n";   
	$html .= "chkObj.checked = chkFlag;\n";
	$html .= "}\n";
	$html .= "}\n";
	$html .= "</script>\n";
	
	
	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Manage Group Menus [$groupTitle Group]";
	$html .= "</td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= "<input type=\"button\" value=\"Save\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onClick=\"saveListForm();\">\n";
	$html .= "<input type=\"button\" value=\"Go Manage Group\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"javascript:location.href='".sess_url($cfg['site']['folder']."admin/manage_groups.php")."'\" >";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
//	if($errorMessage != ""){
//		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
//	}else{
//		$html .= "<br>";
//	}
	// page navigation
	$html .= "<p align='center'>\n";
	
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/group_menu.php?groupID=".$_GET['groupID']."&activeID=".$menuActiveIndex)."\" method=\"post\" name=\"listForm\">\n";
	$html .= "<tr class=\"captionRow\">\n";
	$html .= "<td width=\"5%\"><input type=\"checkbox\" name=\"allMenu\" onclick=\"selectAll()\" checked=\"true\" ></td>\n";
	$html .= "<td width=\"35%\">Menu Name</td>\n";
	$html .= "<td width=\"60%\">Link URL</td>\n";	
	$html .= "</tr>";
	
	$mainMenu = $menu->get_main_menus();
	for($i = 0; $i < count($mainMenu); $i++){
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		
		if(is_MenuinGroup($mainMenu[$i]["key"])) $checkd = " checked=\"true\"";
		else $checkd = "";
		
		if($mainMenu[$i]["f2"] == 0) $prefix = "|_";
		else  $prefix = "|_|_";
		$html .= "<td align=\"left\">".$prefix."<input type=\"checkbox\" name=\"selectedMenu[]\" value=\"".$mainMenu[$i]["key"]."\" f1=\"".$mainMenu[$i]["f1"]."\" f2=\"".$mainMenu[$i]["f2"]."\"".$checkd." onclick=\"selectMenu(this)\"></td>\n";
		$html .= "<td>".$mainMenu[$i]["name"]."</td>\n";
		if(trim($mainMenu[$i]["url"])=="")
			$html .= "<td>&nbsp;</td>\n";
		else
			$html .= "<td>".$mainMenu[$i]["url"]."</td>\n";
		$html .= "</tr>\n";
	}
	
	
	
	$html .="<input type=\"hidden\" name=\"operater\" value=\"\">\n";
	$html .= "</form>\n";
	$html .= "</table>\n";
	
	
	$html .= "</p>\n";
	$html .= "</div>\n";
	
	$html .= "<script language=\"javascript\">\n";
	$html .= "for(var i = 0; i < document.listForm.length; i++){\n";
	$html .= "if(document.listForm.elements[i].type == 'checkbox'){";
	$html .= "if(!document.listForm.elements[i].checked){\n";
	$html .= "document.listForm.allMenu.checked =false;break;";
	$html .= "}\n";
	$html .= "}\n";
	$html .= "}\n";
	$html .= "</script>\n";
	return $html;
}


function show_message($resultMessage){
	global $cfg;
	$html = "<div class=\"resultDiv\">\n";
	$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> \n";
	$html .= $resultMessage;
	$html .= "</div>\n";
	return $html;
}

function is_MenuinGroup($key){
	global $menu;
	$return = false;
	for($i=0; $i<count($menu->menus); $i++){
		if($key == $menu->menus[$i]["key"]){
			$return = true;
			break;
		}
	}
	
	return $return;
}

close_database($con);
?>