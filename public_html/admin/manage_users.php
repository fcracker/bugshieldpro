<?php
/*
 * init web page
 */

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/PayBackEnd.php");
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

if(!isset($_POST['pageSize'])) $_POST['pageSize'] = 10; // set default page size
if(!isset($_POST['orderBy'])) $_POST['orderBy'] = "UserID DESC"; // set default order
// allow input groupID with get method
if(!isset($_POST['groupID']) && isset($_GET['groupID'])) $_POST['groupID'] = $_GET['groupID'];

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);

if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['manageUsers'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manage_users.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$tempUser = new umUser();
	$resultMessage = "";
	if(isset($_POST['operation'])){
		if($_POST['operation'] == 'disable'){
			if($tempUser->change_users_status(0, $_POST['selectedID'])){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
		if($_POST['operation'] == 'enable'){
			if($tempUser->change_users_status(1, $_POST['selectedID'])){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
		if($_POST['operation'] == 'unverify'){
			if($tempUser->change_users_verification(0, $_POST['selectedID'])){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
		if($_POST['operation'] == 'verify'){
			if($tempUser->change_users_verification(1, $_POST['selectedID'])){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
		if($_POST['operation'] == 'assign'){
			if($tempUser->assign_users_group($_POST['actionGroupID'], $_POST['selectedID'], true)){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
		if($_POST['operation'] == 'remove'){
			if($tempUser->assign_users_group($_POST['actionGroupID'], $_POST['selectedID'], false)){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
	}
	$searchResult = $tempUser->search_users($_POST);
	$page->blocks['content'] = list_users($searchResult, $resultMessage);	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manage_users.php");
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

function list_users($searchResult, $resultMessage = ""){
	global $cfg;
	global $lang;
	global $menuActiveIndex;
	// load all groups
	$result = new umResult();
	$tempGroup = new umGroup();
	$result = $tempGroup->search_groups(NULL);
  
  $pay = new PayBackEnd();
	
	$html = "";
	// javascript
  $html .= "<script src='../js/jquery-1.4.2.min.js'></script>\n";
	$html .= "<script language=\"javascript\">\n";  
  
  $html .= "function return_country(iso,el){\n";
  $html .= "jQuery.get('../ajax_iso_countries.php?code='+iso,function(data){jQuery('#country'+el).text(data);});return false;";
  $html .= "}\n";
  
  
  
  $html .= "function remove_monthly(user,el){\n";
  $html .= "if(!confirm('Are you sure you want to remove the monthly fee for this user ?')) return false;\n";
  $html .= "jQuery.get('../ajax_remove_monthly.php?user='+user,function(data){if(data.res=='1') {jQuery(el).remove();} alert(data.message);},'json');return false;";
  $html .= "}\n";
  
  
	$html .= "function nextPage(page){\n";
	$html .= "document.pageForm.page.value = page + 1;\n";
	$html .= "document.pageForm.submit();\n";
	$html .= "disablePage();\n";
	$html .= "}\n";
	$html .= "function prevPage(page){\n";
	$html .= "document.pageForm.page.value = page - 1;\n";
	$html .= "document.pageForm.submit();\n";
	$html .= "disablePage();\n";
	$html .= "}\n";
	$html .= "function changePageSize(){\n";
	$html .= "var pageSize = document.pageForm.pageSize.value;\n";
	$html .= "document.pageForm.reset();\n";
	$html .= "document.pageForm.pageSize.value = pageSize;\n";	
	$html .= "document.pageForm.submit();\n";
	$html .= "disablePage();\n";
	$html .= "}\n";
	$html .= "function sort(orderBy){\n";
	$html .= "document.searchForm.reset();\n";
	$html .= "document.searchForm.orderBy.value = orderBy;\n";
	$html .= "document.searchForm.submit();\n";
	$html .= "disablePage();\n";
	$html .= "}\n";
	$html .= "function searchRecords(){\n";
	$html .= "document.searchForm.page.value = 1;\n";
	$html .= "document.searchForm.submit();\n";
	$html .= "disablePage();\n";
	$html .= "}\n";
	$html .= "function submitOperation(){\n";
	$html .= "var confirmed = false;\n";
	$html .= "if(countSelected() > 0){\n";
	$html .= "if(document.actionForm.operation.value != ''){\n";
	$html .= "if(document.actionForm.operation.value == 'disable'){\n";
	$html .= "if(confirm('".$lang['text']['confirmDisableUsers']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(document.actionForm.operation.value == 'enable'){\n";
	$html .= "if(confirm('".$lang['text']['confirmEnableUsers']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(document.actionForm.operation.value == 'unverify'){\n";
	$html .= "if(confirm('".$lang['text']['confirmUnverifyUsers']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(document.actionForm.operation.value == 'verify'){\n";
	$html .= "if(confirm('".$lang['text']['confirmVerifyUsers']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(document.actionForm.operation.value == 'assign'){\n";
	$html .= "if(confirm('".$lang['text']['confirmAssignUsers']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(document.actionForm.operation.value == 'remove'){\n";
	$html .= "if(confirm('".$lang['text']['confirmRemoveUsers']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(confirmed){\n";
	$html .= "document.listForm.operation.value = document.actionForm.operation.value;\n";
	$html .= "if(document.actionForm.operation.value == 'remove' || document.actionForm.operation.value == 'assign'){\n";
	$html .= "document.listForm.actionGroupID.value = document.actionForm.actionGroupID.value;\n";
	$html .= "}\n";	
	$html .= "document.listForm.submit();\n";
	$html .= "disablePage();\n";
	$html .= "}\n";
	$html .= "}else{\n";
	$html .= "alert('".$lang['text']['mustChooseAction']."');\n";	
	$html .= "}\n";
	$html .= "}else{\n";
	$html .= "alert('".$lang['text']['mustSelectRecord']."');\n";
	$html .= "}\n";
	$html .= "}\n";
	$html .= "function selectAll(){\n";
	$html .= "for(var i = 0; i < document.listForm.length; i++){\n";
	$html .= "if(document.listForm.elements[i].type == 'checkbox') document.listForm.elements[i].checked = true;\n";
	$html .= "}\n";
	$html .= "}\n";
	$html .= "function selectNone(){\n";
	$html .= "for(var i = 0; i < document.listForm.length; i++){\n";
	$html .= "if(document.listForm.elements[i].type == 'checkbox') document.listForm.elements[i].checked = false;\n";
	$html .= "}\n";
	$html .= "}\n";
	$html .= "function countSelected(){\n";
	$html .= "var selectedNum = 0;\n";
	$html .= "for(var i = 0; i < document.listForm.length; i++){\n";
	$html .= "if(document.listForm.elements[i].type == 'checkbox'){\n";
	$html .= "if(document.listForm.elements[i].checked == true) selectedNum++;\n";
	$html .= "}\n";
	$html .= "}\n";
	$html .= "return selectedNum;\n";
	$html .= "}\n";
	$html .= "function changeOperation(){\n";
	$html .= "if(document.actionForm.operation.value == 'assign' || document.actionForm.operation.value == 'remove'){\n";
	$html .= "document.getElementById(\"GroupDiv\").style.display = \"\";\n";
	$html .= "}else{\n";
	$html .= "document.getElementById(\"GroupDiv\").style.display = \"none\";\n";
	$html .= "}\n";
	$html .= "}\n";
	$html .= "</script>\n";

	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">\n";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> \n";
		$html .= $resultMessage;
		$html .= "</div>\n";
	}
	
	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= $lang['menu']['manageUsers'];
	$html .= "</td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= "<input type=\"button\" value=\"".$lang['buttonCaption']['createUser']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onClick=\"location.href='".sess_url("user_detail.php")."'\">\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	
	// page navigation
	$html .= "<p>\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\">\n";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/manage_users.php")."\" method=\"post\" name=\"pageForm\" onSubmit=\"return disablePage();\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"pageNav\">\n";
	
	$pageBlock = "\n";
	if($searchResult->page == 1){
		$pageBlock .= "<img src=\"".$cfg['site']['folder']."images/pager_arrow_left_off.gif\" align=\"absmiddle\" border=\"0\"> \n";
	}else{
		$pageBlock .= "<a href=\"#\" onClick=\"prevPage(".$searchResult->page.")\"><img src=\"".$cfg['site']['folder']."images/pager_arrow_left.gif\" align=\"absmiddle\" border=\"0\"></a> \n";
	}
	$pageBlock .= "<input type=\"text\" name=\"page\" value=\"".$searchResult->page."\" size=\"3\"> \n";
	if($searchResult->page == $searchResult->totalPages){
		$pageBlock .= "<img src=\"".$cfg['site']['folder']."images/pager_arrow_right_off.gif\" align=\"absmiddle\" border=\"0\"> \n";
	}else{
		$pageBlock .= "<a href=\"#\" onClick=\"nextPage(".$searchResult->page.")\"><img src=\"".$cfg['site']['folder']."images/pager_arrow_right.gif\" align=\"absmiddle\" border=\"0\"></a> \n";
	}
	
	$pageSizeBlock = "\n";
	$pageSizeBlock .= "<select name=\"pageSize\" onChange=\"changePageSize()\">\n";
	$optionSize = array(10, 20, 50, 100);
	for($i = 0; $i < count($optionSize); $i++){
		if($optionSize[$i] == $searchResult->pageSize){
			$pageSizeBlock .= "<option value=\"".$optionSize[$i]."\" selected>".$optionSize[$i]."</option>\n";
		}else{
			$pageSizeBlock .= "<option value=\"".$optionSize[$i]."\">".$optionSize[$i]."</option>\n";
		}
	}
	$pageSizeBlock .= "</select>\n";
	
	$html .= sprintf($lang['text']['pageNavigation'], $pageBlock, $searchResult->totalPages, $pageSizeBlock, $searchResult->total);
	foreach($searchResult->query as $key => $value){
		if($key != 'page' && $key != 'pageSize' && $key != 'orderBy' && $key != 'selectedID' && $key != 'operation' && $key != 'actionGroupID'){
			$html .= "<input type=\"hidden\" name=\"".htmlspecialchars($key)."\" value=\"".htmlspecialchars($value)."\">\n";
		}
	}
	$html .= "<input type=\"hidden\" name=\"orderBy\" value=\"".$searchResult->orderBy."\">\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</form>\n";
	$html .= "</table>\n";
	
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
	// actions
	$html .= "<form name=\"actionForm\">\n";
	$html .= "<tr class=\"actionsRow\">\n";
	$html .= "<td colspan=\"5\"align=\"left\">\n";
	$html .= "&nbsp;&nbsp;\n";
	$html .= $lang['field']['select']." <a href=\"#\" onClick=\"selectAll()\">".$lang['text']['all']."</a>, <a href=\"#\" onClick=\"selectNone()\">".$lang['text']['none']."</a>\n";
	$html .= "</td>\n";
	$html .= "<td colspan=\"7\" align=\"right\">\n";
	$html .= $lang['field']['actions']." \n";
	$html .= "<select name=\"operation\" onChange=\"changeOperation()\">\n";
	$html .= "<option value=\"\"></option>\n";
	$html .= "<option value=\"enable\">".$lang['text']['enable']."</option>\n";
	$html .= "<option value=\"disable\">".$lang['text']['disable']."</option>\n";
	$html .= "<option value=\"verify\">".$lang['text']['verifyUsers']."</option>\n";
	$html .= "<option value=\"unverify\">".$lang['text']['unverifyUsers']."</option>\n";
	$html .= "<option value=\"assign\">".$lang['text']['assignToGroup']."</option>\n";
	$html .= "<option value=\"remove\">".$lang['text']['removeFromGroup']."</option>\n";
	$html .= "</select> \n";
	$html .= "<span id=\"GroupDiv\" style=\"display: none;\">";
	$html .= "<select name=\"actionGroupID\">";
	for($i = 0; $i < count($result->list); $i++){
		$groupStatus = "";
		if($result->list[$i]->status == 1){
			$groupStatus = $lang['text']['enabled'];
		}else{
			$groupStatus = $lang['text']['disabled'];
		}
		$html .= "<option value=\"".$result->list[$i]->groupID."\">"."[".$groupStatus."] ".htmlspecialchars($result->list[$i]->groupTitle)."</option>";
	}
	$html .= "</select>\n";
	$html .= "</span>";
	$html .= "<input type=\"button\" value=\"".$lang['buttonCaption']['submit']."\" class=\"gobtn\" onmouseover=\"this.className='gobtnhov'\" onmouseout=\"this.className='gobtn'\" onClick=\"submitOperation()\">\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</form>\n";

	// caption and sort
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/manage_users.php")."\" method=\"post\" name=\"searchForm\">\n";
	
	$html .= "<tr class=\"captionRow\">\n";
	$html .= "<td width=\"3%\">&nbsp;</td>\n";
	$html .= "<td width=\"8%\">\n";
	$html .= $lang['text']['userID']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.UserID ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.UserID DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	$html .= "<td width=\"9%\">\n";
	$html .= "User Name \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.Email ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.Email DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	
	$html .= "<td width=\"17%\">\n";
	$html .= "Email \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.EmailAddress ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.EmailAddress DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	
	$html .= "<td width=\"6%\">\n";
	$html .= $lang['text']['verified']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.EmailVerified ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.EmailVerified DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	$html .= "<td width=\"13%\">\n";
	$html .= $lang['text']['createTime']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.CreateTime ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.CreateTime DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	$html .= "<td width=\"12%\">\n";
	$html .= "Expiration Date \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.expiration ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.expiration DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	$html .= "<td width=\"13%\">\n";
	$html .= $lang['text']['lastLoginTime']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.LastLoginTime ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.LastLoginTime DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
  
	$html .= "<td width=\"3%\">\n";
	$html .= $lang['text']['logins']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.LoginCount ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.LoginCount DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
  
	$html .= "<td width=\"4%\">\n";
	$html .= $lang['text']['status']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.Status ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('u.Status DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
  
  $html .= "<td width=\"5%\">\n";
	$html .= "Bank \n";
	$html .= "</td>\n";
  
    $html .= "<td width=\"2%\">\n";
	$html .= "Country \n";
	$html .= "</td>\n";
  
	$html .= "<td width=\"5%\" class=\"last\">".$lang['text']['action']."</td>\n";
	$html .= "</tr>\n";
	
	
	//search condictions
	$html .= "<tr class=\"searchRow\">\n";
	$html .= "<td>&nbsp;</td>\n";
	$html .= "<td>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['from']."</div><input type=\"text\" size=\"8\" name=\"fromID\" value=\"".htmlspecialchars($searchResult->query['fromID'])."\">\n";
	$html .= "<br>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['to']."</div><input type=\"text\" size=\"8\" name=\"toID\" value=\"".htmlspecialchars($searchResult->query['toID'])."\">\n";
	$html .= "</td>\n";
	$html .= "<td valign=\"top\">";
	$html .= "<input type=\"text\" name=\"keywords\" style=\"width: 95%\" value=\"".htmlspecialchars($searchResult->query['keywords'])."\"><br>";
	$html .= "<select name=\"groupID\">";
	$html .= "<option value=\"-\"></option>";
	for($i = 0; $i < count($result->list); $i++){
		$selected = "";
		if($searchResult->query['groupID'] == $result->list[$i]->groupID) $selected = "selected";
		$groupStatus = "";
		if($result->list[$i]->status == 1){
			$groupStatus = $lang['text']['enabled'];
		}else{
			$groupStatus = $lang['text']['disabled'];
		}
		$html .= "<option value=\"".$result->list[$i]->groupID."\" ".$selected.">"."[".$groupStatus."] ".htmlspecialchars($result->list[$i]->groupTitle)."</option>";
	}
	$html .= "</select>";
	$html .= "</td>\n";
	
//	$html .= "<input type=\"text\" name=\"keywords\" style=\"width: 95%\" value=\"".htmlspecialchars($searchResult->query['keywords'])."\"><br>";
	$html .= "<td>
				<input type=\"text\" name=\"emailAddress\" style=\"width: 95%\" value=\"".htmlspecialchars($searchResult->query['emailAddress'])."\">
			</td>";
	
	$html .= "<td valign=\"top\"><select name=\"emailVerified\">\n";
	if($searchResult->query['emailVerified'] == "-"){
		$html .= "<option value=\"-\" selected></option>\n";
	}else{
		$html .= "<option value=\"-\"></option>\n";
	}
	if($searchResult->query['emailVerified'] == "1"){
		$html .= "<option value=\"1\" selected>".$lang['text']['yes']."</option>\n";
	}else{
		$html .= "<option value=\"1\">".$lang['text']['yes']."</option>\n";
	}
	if($searchResult->query['emailVerified'] == "0"){
		$html .= "<option value=\"0\" selected>".$lang['text']['no']."</option>\n";
	}else{
		$html .= "<option value=\"0\">".$lang['text']['no']."</option>\n";
	}
	$html .= "</select></td>\n";
	
	$html .= "<td>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['from']."</div>";
	$html .= "<input type=\"text\" size=\"15\" name=\"fromCreateDate\" value=\"".htmlspecialchars($searchResult->query['fromCreateDate'])."\" readonly id=\"fcDate\">\n";
	$html .= "<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"fcDateTrigger\" align=\"absmiddle\"></a>";
	$html .= "<br>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['to']."</div>";
	$html .= "<input type=\"text\" size=\"15\" name=\"toCreateDate\" value=\"".htmlspecialchars($searchResult->query['toCreateDate'])."\" readonly id=\"tcDate\">\n";
	$html .= "<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"tcDateTrigger\" align=\"absmiddle\"></a>";
	$html .= "</td>\n";
	
	$html .= "<td>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['from']."</div>";
	$html .= "<input type=\"text\" size=\"15\" name=\"fromExpirationDate\" value=\"".htmlspecialchars($searchResult->query['fromExpirationDate'])."\" readonly id=\"feDate\">\n";
	$html .= "<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"feDateTrigger\" align=\"absmiddle\"></a>";
	$html .= "<br>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['to']."</div>";
	$html .= "<input type=\"text\" size=\"15\" name=\"toExpirationDate\" value=\"".htmlspecialchars($searchResult->query['toExpirationDate'])."\" readonly id=\"teDate\">\n";
	$html .= "<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"teDateTrigger\" align=\"absmiddle\"></a>";
	$html .= "</td>\n";
	
	$html .= "<td>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['from']."</div>";
	$html .= "<input type=\"text\" size=\"15\" name=\"fromLastLoginDate\" value=\"".htmlspecialchars($searchResult->query['fromLastLoginDate'])."\" readonly id=\"flDate\">\n";
	$html .= "<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"flDateTrigger\" align=\"absmiddle\"></a>";
	$html .= "<br>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['to']."</div>";
	$html .= "<input type=\"text\" size=\"15\" name=\"toLastLoginDate\" value=\"".htmlspecialchars($searchResult->query['toLastLoginDate'])."\" readonly id=\"tlDate\">\n";
	$html .= "<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"tlDateTrigger\" align=\"absmiddle\"></a>";
	$html .= "</td>\n";
	
	$html .= "<td>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['from']."</div><input type=\"text\" size=\"5\" name=\"fromLoginCount\" value=\"".htmlspecialchars($searchResult->query['fromLoginCount'])."\">\n";
	$html .= "<br>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['to']."</div><input type=\"text\" size=\"5\" name=\"toLoginCount\" value=\"".htmlspecialchars($searchResult->query['toLoginCount'])."\">\n";
	$html .= "</td>\n";
	
	$html .= "<td valign=\"top\"><select name=\"status\">\n";
	if($searchResult->query['status'] == "-"){
		$html .= "<option value=\"-\" selected></option>\n";
	}else{
		$html .= "<option value=\"-\"></option>\n";
	}
	if($searchResult->query['status'] == "1"){
		$html .= "<option value=\"1\" selected>".$lang['text']['enabled']."</option>\n";
	}else{
		$html .= "<option value=\"1\">".$lang['text']['enabled']."</option>\n";
	}
	if($searchResult->query['status'] == "0"){
		$html .= "<option value=\"0\" selected>".$lang['text']['disabled']."</option>\n";
	}else{
		$html .= "<option value=\"0\">".$lang['text']['disabled']."</option>\n";
	}
	$html .= "</select></td>\n";
  
  $html .= "<td>&nbsp;</td>\n";
  $html .= "<td>&nbsp;</td>\n";
  
	$html .= "<td class=\"last\" valign=\"top\">\n";
	$html .= "[ <a href=\"#\" onClick=\"searchRecords();\">".$lang['buttonCaption']['search']."</a> ]\n";
	$html .= "<input type=\"hidden\" name=\"orderBy\" value=\"".$searchResult->orderBy."\">\n";
	$html .= "<input type=\"hidden\" name=\"page\" value=\"".$searchResult->page."\">\n";
	$html .= "<input type=\"hidden\" name=\"pageSize\" value=\"".$searchResult->pageSize."\">\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</form>\n";

	// list data
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/manage_users.php")."\" method=\"post\" name=\"listForm\">\n";
	for($i = 0; $i < count($searchResult->list); $i++){
		$objUser = new umUser();
		$objUser = $searchResult->list[$i];
    $bank_used_row = $pay->get_used_bank($objUser->EmailAddress);
    $bank_used = (count($bank_used_row)) ? $bank_used_row["BankName"]:"N/A";
    
    $bank_used = str_replace("Remote","Rem.",str_replace(".com","",$bank_used));
    
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		$html .= "<td align=\"center\"><input type=\"checkbox\" name=\"selectedID[]\" value=\"".$objUser->userID."\"></td>\n";
		$html .= "<td>".$objUser->userID."</td>\n";
		$html .= "<td>".htmlspecialchars($objUser->email)."</td>\n";
		$html .= "<td>".$objUser->EmailAddress."</td>\n";
		if($objUser->emailVerified == 1){
			$html .= "<td>".$lang['text']['yes']."</td>\n";
		}else{
			$html .= "<td>".$lang['text']['no']."</td>\n";
		}
		$html .= "<td>".date($lang['timeFormat'], strtotime($objUser->createTime))."</td>\n";
		
		$html .= "<td>".($objUser->expirationDate=="0000-00-00"?"-":date($lang['timeFormat'], strtotime($objUser->expirationDate)))."</td>\n";

		if($objUser->loginCount > 0){
			$html .= "<td>".date($lang['timeFormat'], strtotime($objUser->lastLoginTime))."</td>\n";
		}else{
			$html .= "<td>&nbsp;</td>\n";
		}
		$html .= "<td>".$objUser->loginCount."</td>\n";
		if($objUser->status == 1){
			$html .= "<td>".$lang['text']['enabled']."</td>\n";
		}else{
			$html .= "<td>".$lang['text']['disabled']."</td>\n";
		}
    
    $html .= "<td>".$bank_used."</td>\n";
    
    //country
    $html .= "<td><a href='#' id='country".$objUser->userID."' title='Click to see full name' onclick=' return return_country(\"".$objUser->country."\",".$objUser->userID.")'>".$objUser->country."</a></td>\n";
    
		$html .= "<td class=\"last\">";
		$html .= "[ <a href=\"".sess_url("user_detail.php?userID=".$objUser->userID."&activeID=".$menuActiveIndex)."\">".$lang['text']['edit']."</a> ]";
    
    if($objUser->has_monthly_fee) {
    $html .= " <br /> [ <a onclick='return remove_monthly(".$objUser->userID.",this)' href=\"#\">Remove Monthly</a> ]";
    }
    
		if($i == 0){
			foreach($searchResult->query as $key => $value){
				if($key != 'page' && $key != 'pageSize' && $key != 'orderBy' && $key != 'selectedID' && $key != 'operation' && $key != 'actionGroupID'){
					$html .= "<input type=\"hidden\" name=\"".htmlspecialchars($key)."\" value=\"".htmlspecialchars($value)."\">\n";
				}
			}
			$html .= "<input type=\"hidden\" name=\"orderBy\" value=\"".$searchResult->orderBy."\">\n";
			$html .= "<input type=\"hidden\" name=\"page\" value=\"".$searchResult->page."\">\n";
			$html .= "<input type=\"hidden\" name=\"pageSize\" value=\"".$searchResult->pageSize."\">\n";
			$html .= "<input type=\"hidden\" name=\"operation\" value=\"\">\n";
			$html .= "<input type=\"hidden\" name=\"actionGroupID\" value=\"\">\n";
		}
		$html .= "</td>\n";
		$html .= "</tr>\n";
	}
	$html .= "</form>\n";
	$html .= "</table>\n";
	$html .= "<script type=\"text/javascript\">";
	
	$html .= "Calendar.setup(";
	$html .= "{";
	$html .= "inputField: \"fcDate\",";
	$html .= "ifFormat: \"%Y-%m-%d\",";
	$html .= "showsTime: false,";
	$html .= "button: \"fcDateTrigger\"";
	$html .= "}";
	$html .= ");";	
	$html .= "Calendar.setup(";
	$html .= "{";
	$html .= "inputField: \"tcDate\",";
	$html .= "ifFormat: \"%Y-%m-%d\",";
	$html .= "showsTime: false,";
	$html .= "button: \"tcDateTrigger\"";
	$html .= "}";
	$html .= ");";
	
	
	$html .= "Calendar.setup(";
	$html .= "{";
	$html .= "inputField: \"feDate\",";
	$html .= "ifFormat: \"%Y-%m-%d\",";
	$html .= "showsTime: false,";
	$html .= "button: \"feDateTrigger\"";
	$html .= "}";
	$html .= ");";	
	$html .= "Calendar.setup(";
	$html .= "{";
	$html .= "inputField: \"teDate\",";
	$html .= "ifFormat: \"%Y-%m-%d\",";
	$html .= "showsTime: false,";
	$html .= "button: \"teDateTrigger\"";
	$html .= "}";
	$html .= ");";
	
	$html .= "Calendar.setup(";
	$html .= "{";
	$html .= "inputField: \"flDate\",";
	$html .= "ifFormat: \"%Y-%m-%d\",";
	$html .= "showsTime: false,";
	$html .= "button: \"flDateTrigger\"";
	$html .= "}";
	$html .= ");";
	$html .= "Calendar.setup(";
	$html .= "{";
	$html .= "inputField: \"tlDate\",";
	$html .= "ifFormat: \"%Y-%m-%d\",";
	$html .= "showsTime: false,";
	$html .= "button: \"tlDateTrigger\"";
	$html .= "}";
	$html .= ");";
	$html .= "</script>";
	$html .= "</p>\n";
	$html .= "</div>\n";
	return $html;
}
?>