<?php
/*
 * init web page
 */
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
/*
 * create content blocks
 * page is built in this part
 */

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['pluginList'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/plug-ins.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$page->blocks['content'] = display_plugins(get_plugins());	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/plug-ins.php");
}

/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

close_database($con);

/*
* ============================================== page complete here ==============================================
* The following functions construct content for this page
*/

function get_plugins(){
	global $cfg;
	$list = array();
	
	if ($handle = opendir($cfg['site']['root'].$cfg['site']['folder']."plug-ins")){
		while (false !== ($file = readdir($handle))) {
			if($file != '.' && $file != '..'){
				if(is_dir($cfg['site']['root'].$cfg['site']['folder']."plug-ins/".$file)) $list[] = $file;
			}
		}
	}
	sort($list);
	return $list;
}

function display_plugins($list){
	global $cfg;
	global $lang;
	
	$html = "";
	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= $lang['title']['pluginList'];
	$html .= "</td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= "<input type=\"button\" value=\"".$lang['buttonCaption']['downloadPlugins']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onClick=\"window.open('http://www.phpmembers.com/plug-ins.html', '_blank')\">\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";	

	$html .= "<p>";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
	$html .= "<tr class=\"actionsRow\">\n";
	$html .= "<td width=\"90%\">\n";
	$html .= "&nbsp;\n";
	$html .= "</td>\n";
	$html .= "<td width=\"10%\">\n";
	$html .= "&nbsp;\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	for($i = 0; $i < count($list); $i++){
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		$html .= "<td>".$list[$i]."</td>\n";
		$html .= "<td class=\"last\">";
		if(file_exists($cfg['site']['root'].$cfg['site']['folder']."plug-ins/".$list[$i]."/install.php")){
			$html .= "[ <a href=\"".$cfg['site']['folder']."plug-ins/".$list[$i]."/install.php\">".$lang['text']['install']."</a> ]";
		}else{
			$html .= "[ <a href=\"".$cfg['site']['folder']."plug-ins/".$list[$i]."\">".$lang['text']['configure']."</a> ]";
		}
		$html .= "</td>\n";
		$html .= "</tr>\n";
	}
	$html .= "</table>\n";
	$html .= "</p>";
	$html .= "</div>\n";
	
	return $html;
}
?>