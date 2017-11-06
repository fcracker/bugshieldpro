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
include_once ("../lib/unscribe.class.php");

$con = connect_database();

/*
 * create content blocks
 * page is built in this part
 */

if(!isset($_POST['protectType']) && isset($_GET['protectType'])) $_POST['protectType'] = $_GET['protectType'];

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "Unsubscribe List";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/unscribe.php");
}
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$lists = new umUnsubscribe();
	$errMsg = "";
    if (isset ($_POST['active'])){
        $flag = $_POST['subscribe'];
    } else {
		$flag = "export";
	}
	$list = $lists->getData($flag);
	$page->blocks['content'] = show_unSubScribeList($errMsg);
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/unscribe.php");
}

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

function show_unSubScribeList($errorMessage = ""){
	global $cfg;
	global $lang;
	global $list;
	global $flag;
	$html = "";
	// title
    $html .= "<script type=\"text/javascript\" src=\"".$cfg['site']['folder']."js/jquery/jquery.js\"></script>";
	$html .= "<script language=\"javascript\">\n";
   	$html .= " function changeActivate(){
				var frm = document.unscribeForm;
				frm.action = '".sess_url("unscribe.php")."';
				document.unscribeForm.submit();
			}
			function export(){
				var frm = document.unscribeForm;
				frm.action='".sess_url("exportscribe.php")."';
				frm.submit();
			}
	";
	$html .= "</script>\n";

	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Unsubscribe List";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	// page navigation
	$html .= "<p align='center'>\n";
	$html .= "<table id=\"paypalBox\" width=\"80%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\" align=\"center\">\n";
	$html .= "<tr>
				<form method=\"post\" name=\"unscribeForm\">
					<input type='radio' name='subscribe' id='order' onclick=changeActivate() value='order' ".($flag == "order" ? "checked" : "")." /><label for='order'>Order</label>
					<input type='radio' name='subscribe' id='expert' onclick=changeActivate() value='export' ".($flag == "export" ? "checked" : "")." /><label for='expert'>Export</label>
					<input type='hidden' name='active' value='active' />
					<input type='button' value='export' onclick=export() />
				<form>
			</tr>";
	$html .= "	<tr class=\"captionRow\">\n";
	$html .= "		<td width=\"10%\">No</td>\n";
	$html .= "		<td width=\"30%\">Email Address</td>\n";
	$html .= "		<td width=\"30%\">Request Date</td>\n";
	$html .= "		<td width=\"30%\">Export Date</td>\n";
	$html .= "	</tr>";
	$html .= "	<tr class=\"searchRow\">\n";
	$html .= "	</tr>";
	for($i=0;$i<count($list);$i++){
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		$html .= "<td>$i</td>\n";
		$html .= "<td>".$list[$i]['email']."</td>\n";
		$html .= "<td>".$list[$i]['requestdate']."</td>\n";
		$html .= "<td>".$list[$i]['exportdate']."</td>\n";
		$html .= "</tr>";
	}
	$html .= "</table>\n";
	$html .= "</p>\n";
	$html .= "</div>\n";

	return $html;
}
close_database($con);