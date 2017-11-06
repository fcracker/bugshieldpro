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
include_once("../lib/price.class.php");

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
	$page->blocks['title'] = "Manage Manual Price";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manage_price.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$priceObj = new umPrice();
	$errMsg = "";
	if(isset($_POST['action'])){
		switch($_POST['action']){
			case 'add':
				if ($_POST['ProductPrice'] == 0)
					$errMsg = "Insert ProductPrice";
				else 
					$priceObj->setPrice($_POST['ProductPrice'], $_POST['ProductPrice'] + $_POST['DistributionCost']);
				break;
			case 'save':
				if ($_POST['ProductPrice'] == 0)
					$errMsg = "Insert ProductPrice";
				else
					$priceObj->updatePrice($_POST['ProductionKey'],$_POST['ProductPrice'], $_POST['ProductPrice'] + $_POST['DistributionCost']);
				break;
			case 'delete':
				$priceObj->deletePrice($_POST['ProductionKey']);
				break;
		}
	}
	
	$page->blocks['content'] = show_priceList($errMsg);	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manage_price.php");
}

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

function show_priceList($errorMessage = ""){
	global $cfg;
	global $lang;
	global $priceObj;
	$html = "";
	// title
	$html .= "<script language=\"javascript\">\n";
	$html .= "function priceSubmit(action, key){\n";
	$html .= "	if (action == \"edit\"){\n";
	$html .= "		var priceCell = document.getElementById(\"priceBox\").rows[key*1 + 2].cells[1];\n";
	$html .= "		var costCell = document.getElementById(\"priceBox\").rows[key*1 + 2].cells[2];\n";
	$html .= "		var hrefCell = document.getElementById(\"priceBox\").rows[key*1 + 2].cells[4];\n";
	$html .= "		priceCell.innerHTML = \"<input type='text' value='\" + priceCell.innerHTML + \"' >\";\n";
	$html .= "		costCell.innerHTML = \"<input type='text' value='\" + costCell.innerHTML + \"' >\";\n";
	$html .= "		hrefCell.innerHTML = \"[ <a href='#' onclick=\\\"priceSubmit('save',\" + key + \")\\\">Save</a> ] [ <a href='#' onclick=\\\"priceSubmit('cancel',\" + key + \")\\\"> Cancel </a> ]\";\n";
	$html .= "	} else if (action ==\"save\"){\n";
	$html .= "		document.priceForm.action.value = action;\n";
	$html .= "		var productionKey = document.getElementById(\"priceBox\").rows[key*1 + 2].cells[0];\n";	
	$html .= "		var priceCell = document.getElementById(\"priceBox\").rows[key*1 + 2].cells[1].firstChild;\n";
	$html .= "		var costCell = document.getElementById(\"priceBox\").rows[key*1 + 2].cells[2].firstChild;\n";
	$html .= "		document.priceForm.ProductPrice.value = priceCell.value;\n";
	$html .= "		document.priceForm.DistributionCost.value = costCell.value;\n";
	$html .= "		document.priceForm.ProductionKey.value = productionKey.innerHTML;\n";	
	$html .= "		document.priceForm.submit();\n";		
	$html .= "	} else {\n";
	$html .= "		document.priceForm.action.value = action;\n";
	$html .= "		document.priceForm.ProductionKey.value = key;\n";
	$html .= "		document.priceForm.submit();\n";
	$html .= "	}\n";
	$html .= "}\n";
	$html .= "</script>\n";
	
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Manage Manual Price";
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
	$html .= "		<td width=\"7%\">Price ID</td>\n";
	$html .= "		<td width=\"27%\">Shopping Price</td>\n";	
	$html .= "		<td width=\"27%\">Distribution cost</td>\n";
	$html .= "		<td width=\"27%\">Total Price</td>\n";	
	$html .= "		<td width=\"12%\">Action</td>\n";	
	$html .= "	</tr>";
	$html .= "	<tr class=\"searchRow\">\n";
	$html .= "		<form  method=\"post\" name=\"priceForm\">\n";
	$html .= "			<td>&nbsp;<input type=\"hidden\" name=\"action\" value=\"add\"></td>\n";
	$html .= "			<td><input type=\"text\" name=\"ProductPrice\"></td>\n";	
	$html .= "			<td><input type=\"text\" name=\"DistributionCost\"></td>\n";
	$html .= "			<td>&nbsp;<input type=\"hidden\" name=\"ProductionKey\"></td>\n";
	$html .= "			<td><input type=\"button\" value=\"&nbsp;&nbsp;&nbsp;Add&nbsp;&nbsp;&nbsp;\" onclick=\"priceSubmit('add', '-1')\"></td>\n";	
	$html .= "		</form>";
	$html .= "	</tr>";
	$price = $priceObj->getPrice();
	for($i=0;$i<count($price);$i++){
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		$html .= "<td>".$price[$i]['Key']."</td>\n";
		$html .= "<td>".$price[$i]['Price']."</td>\n";
		$html .= "<td>".($price[$i]['ShoppingPrice']-$price[$i]['Price'])."</td>\n";
		$html .= "<td>".$price[$i]['ShoppingPrice']."</td>\n";
		$html .= "<td>[ <a href=\"#\" onclick=\"priceSubmit('edit', '{$i}')\">Edit</a> ] [ <a href=\"#\" onclick=\"priceSubmit('delete', '{$price[$i]['Key']}')\">Delete</a> ]</td>\n";	
		$html .= "</tr>";		
	}
	
	$html .= "</table>\n";	
	$html .= "</p>\n";
	$html .= "</div>\n";

	return $html;
}

close_database($con);
?>