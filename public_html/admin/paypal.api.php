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
include_once("../lib/paypal.api.class.php");

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
	$page->blocks['title'] = "Paypal API";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/paypal_api.php");
}
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$paypalObject = new umPaypal();
	$errMsg = "";
    if (isset ($_POST['ajax'])){
        $paypalObject->setActive($_POST['id']);
        return;
    }
	if(isset($_POST['action'])){
		switch($_POST['action']){
			case 'add':
				if (strlen($_POST['username']) == 0)
					$errMsg = "Insert Insert UserName";
				else
					$paypalObject->setData($_POST);
				break;
			case 'save':
				if (strlen($_POST['username']) == 0)
					$errMsg = "Insert User Name";
				else
					$paypalObject->updateData($_POST);
				break;
			case 'delete':
				$paypalObject->deleteData($_POST);
				break;
		}
	}

	$page->blocks['content'] = show_priceList($errMsg);
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/paypal_api.php");
}

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

function show_priceList($errorMessage = ""){
	global $cfg;
	global $lang;
	global $paypalObject;
	$html = "";
	// title
    $html .= "<script type=\"text/javascript\" src=\"".$cfg['site']['folder']."js/jquery/jquery.js\"></script>";
	$html .= "<script language=\"javascript\">\n";
	$html .= "function paypalSubmit(action, key){\n";
	$html .= "	if (action == \"edit\"){\n";
	$html .= "		var usernameCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[1];\n";
	$html .= "		var userpasswordCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[2];\n";
	$html .= "		var signatureCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[3];\n";
	$html .= "		var endpointCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[4];\n";
	$html .= "		var hrefCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[6];\n";
	$html .= "		usernameCell.innerHTML = \"<input type='text' value='\" + usernameCell.innerHTML + \"' >\";\n";
	$html .= "		userpasswordCell.innerHTML = \"<input type='text' value='\" + userpasswordCell.innerHTML + \"' >\";\n";
	$html .= "		signatureCell.innerHTML = \"<input type='text' value='\" + signatureCell.innerHTML + \"' >\";\n";
	$html .= "		endpointCell.innerHTML = \"<input type='text' value='\" + endpointCell.innerHTML + \"' >\";\n;";
	$html .= "		hrefCell.innerHTML = \"[ <a href='#' onclick=\\\"paypalSubmit('save',\" + key + \")\\\">Save</a> ] [ <a href='#' onclick=\\\"paypalSubmit('cancel',\" + key + \")\\\"> Cancel </a> ]\";\n";
	$html .= "	} else if (action ==\"save\"){\n";
	$html .= "		document.paypalForm.action.value = action;\n";
	$html .= "		var idCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[0];\n";
	$html .= "		var usernameCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[1].firstChild;\n";
	$html .= "		var userpasswordCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[2].firstChild;\n";
	$html .= "		var signatureCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[3].firstChild;\n";
	$html .= "		var endpointCell = document.getElementById(\"paypalBox\").rows[key*1 + 2].cells[4].firstChild;\n";
	$html .= "		document.paypalForm.id.value = idCell.innerHTML;\n";
	$html .= "		document.paypalForm.username.value = usernameCell.value;\n";
	$html .= "		document.paypalForm.userpassword.value = userpasswordCell.value;\n";
	$html .= "		document.paypalForm.signature.value = signatureCell.value;\n";
	$html .= "		document.paypalForm.endpoint.value = endpointCell.value;\n";
	$html .= "		document.paypalForm.submit();\n";
	$html .= "	} else {\n";
	$html .= "		document.paypalForm.action.value = action;\n";
	$html .= "		document.paypalForm.id.value = key;\n";
	$html .= "		document.paypalForm.submit();\n";
	$html .= "	}\n";
	$html .= "}\n";
   	$html .= " function changeActivate(id){
					$.post('paypal.api.php', {
    				ajax: 'send data',
				    id: id
					}, function() {});}";
	$html .= "</script>\n";

	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Paypal API";
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
	$html .= "	<tr class=\"captionRow\">\n";
	$html .= "		<td width=\"5%\">ID</td>\n";
	$html .= "		<td width=\"15%\">API Username</td>\n";
	$html .= "		<td width=\"15%\">API Userpassword</td>\n";
	$html .= "		<td width=\"20%\">API Signature</td>\n";
	$html .= "		<td width=\"27%\">End Point</td>\n";
    $html .= "      <td width=\"5%\">Active</td>";
    $html .= "      <td width=\"13%\">Action</td>";
	$html .= "	</tr>";
	$html .= "	<tr class=\"searchRow\">\n";
	$html .= "		<form action=\"".sess_url($cfg['site']['folder']."admin/paypal.api.php")."\" method=\"post\" name=\"paypalForm\">\n";
	$html .= "			<td>&nbsp;<input type=\"hidden\" name=\"id\"></td>\n";
	$html .= "			<td><input style=\"width:95%\" type=\"text\" name=\"username\"></td>\n";
	$html .= "			<td><input style=\"width:95%\" type=\"text\" name=\"userpassword\"></td>\n";
    $html .= "			<td><input style=\"width:95%\" type=\"text\" name=\"signature\"></td>\n";
    $html .= "			<td><input style=\"width:95%\" type=\"text\" name=\"endpoint\"></td>\n";
	$html .= "			<td>&nbsp;<input type=\"hidden\" name=\"action\" value=\"add\"></td>\n";
	$html .= "			<td><input type=\"submit\" value=\"Add\"></td>\n";
	$html .= "		</form>";
	$html .= "	</tr>";
	$paypal = $paypalObject->getData();
	for($i=0;$i<count($paypal);$i++){
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		$html .= "<td>".$paypal[$i]['ID']."</td>\n";
		$html .= "<td>".$paypal[$i]['username']."</td>\n";
		$html .= "<td>".$paypal[$i]['password']."</td>\n";
		$html .= "<td>".$paypal[$i]['signature']."</td>\n";
		$html .= "<td>".$paypal[$i]['endpoint']."</td>\n";
        $html .= "<td><input type=\"radio\" name=\"activate\"".($paypal[$i]['activate'] == 1 ? "checked" : "")." onclick=\"changeActivate('".$paypal[$i]['ID']."')\"></td>";
		$html .= "<td>[ <a href=\"#\" onclick=\"paypalSubmit('edit', '{$i}')\">Edit</a> ] [ <a href=\"#\" onclick=\"paypalSubmit('delete', '{$paypal[$i]['ID']}')\">Delete</a> ]</td>\n";
		$html .= "</tr>";
	}
	$html .= "</table>\n";
	$html .= "</p>\n";
	$html .= "</div>\n";

	return $html;
}
close_database($con);
?>