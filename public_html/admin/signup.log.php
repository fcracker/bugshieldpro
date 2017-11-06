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
include_once("../lib/signup.class.php");

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
	$page->blocks['title'] = "Signup page Log";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/signup.log.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$userObj = new umSignup();
	if(isset($_POST['action'])){
		if ($_POST['action'] != 'search'){
			$userObj->deleteUser($_POST['action']);
			init_SearchValue();
		}
	}else{
		init_SearchValue();
	}
	$page->blocks['content'] = show_userList();
	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/signup.log.php");
}

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page
function init_SearchValue(){
	$_POST['firstName'] = "";
	$_POST['lastName'] = "";
	$_POST['telePhone'] = "";
	$_POST['emailAddress'] = "";
	$_POST['accessDate'] = "";
}
function show_userList($errorMessage = ""){
	global $cfg;
	global $lang;
	global $userObj;
	global $condition;
	$html = "";
	// title
	$html .= "<script language=\"javascript\">\n";
	$html .= "function usersSubmit(action){\n";
	$html .= "	document.priceForm.action.value = action;\n";
	$html .= "	document.priceForm.submit();\n";		
	$html .= "}\n";
	$html .= "</script>\n";
	 
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Signup page Log";
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
	
	$html .= "<table id=\"priceBox\" width=\"80%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\" align=\"center\">\n";
	$html .= "	<tr class=\"captionRow\">\n";
	$html .= "		<td width=\"25%\">eMail Address</td>\n";
	$html .= "		<td width=\"15%\">First Name</td>\n";
	$html .= "		<td width=\"15%\">Last Name</td>\n";	
	$html .= "		<td width=\"15%\">Telephone</td>\n";
	$html .= "		<td width=\"15%\">AccessDate</td>\n";
	$html .= "		<td width=\"15%\">Action</td>\n";	
	$html .= "	</tr>";
	$html .= "	<tr class=\"searchRow\">\n";
	$html .= "		<form action=\"".sess_url($cfg['site']['folder']."admin/signup.log.php")."\" method=\"post\" name=\"priceForm\">\n";
	$html .= "			<td><input style=\"width:95%\" type=\"text\" name=\"emailAddress\" value='".$_POST['emailAddress']."'></td>\n";
	$html .= "			<td><input style=\"width:95%\" type=\"text\" name=\"firstName\" value='".$_POST['firstName']."'></td>\n";
	$html .= "			<td><input style=\"width:95%\" type=\"text\" name=\"lastName\" value='".$_POST['lastName']."'></td>\n";
	$html .= "			<td><input style=\"width:95%\" type=\"text\" name=\"telePhone\" value='".$_POST['telePhone']."'></td>\n";
	$html .= "			<td>\n";
	$html .= "				<input type=\"text\" style=\"width:80%\" name=\"accessDate\" value='".$_POST['accessDate']."' id=\"fcDate\">\n";
	$html .= "				<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"fcDateTrigger\" align=\"absmiddle\"></a>";
	$html .= "			</td>\n";
	$html .= "			<td>";
	$html .= "				<input type=\"button\" value=\"&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;\" onclick=\"usersSubmit('search')\">";
	$html .= "				<input type=\"hidden\" name=\"action\">";
	$html .= "			</td>\n";
	$html .= "		</form>";
	$html .= "	</tr>";
	
	$html .= "<script type=\"text/javascript\">";
	$html .= "	Calendar.setup(";
	$html .= "		{";
	$html .= "			inputField: \"fcDate\",";
	$html .= "			ifFormat: \"%Y-%m-%d\",";
	$html .= "			showsTime: false,";
	$html .= "			button: \"fcDateTrigger\"";
	$html .= "		}";
	$html .= "	);";
	$html .= "</script>";
	
	$users = $userObj->getUsers($_POST);
	for($i=0;$i<count($users);$i++){
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		$html .= "		<td>".$users[$i]['Email']."</td>\n";
		$html .= "		<td>".$users[$i]['FirstName']."</td>\n";
		$html .= "		<td>".$users[$i]['LastName']."</td>\n";
		$html .= "		<td>".$users[$i]['Telephone']."</td>\n";
		$html .= "		<td>".$users[$i]['AccessDate']."</td>\n";
		$html .= "		<td>[ <a href=\"#\" onclick=\"usersSubmit('{$users[$i]['UserID']}')\">Delete</a> ]</td>\n";	
		$html .= "	</tr>";		
	}
	
	$html .= "</table>\n";	
	$html .= "</p>\n";
	$html .= "</div>\n";

	return $html;
}

close_database($con);
?>