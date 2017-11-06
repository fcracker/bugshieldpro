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

if(!isset($_POST['pageSize'])) $_POST['pageSize'] = 10; // set default page size

$user = new umUser();
$user->get_session();
$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['manageGroups'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manage_groups.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$tempGroup = new umGroup();
	$resultMessage = "";
	if(isset($_POST['operation'])){
		if($_POST['operation'] == 'disable'){
			if($tempGroup->change_groups_status(0, $_POST['selectedID'])){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
		if($_POST['operation'] == 'enable'){
			if($tempGroup->change_groups_status(1, $_POST['selectedID'])){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
		if($_POST['operation'] == 'disableUsers'){
			if($tempGroup->change_users_status(0, $_POST['selectedID'])){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
		if($_POST['operation'] == 'enableUsers'){
			if($tempGroup->change_users_status(1, $_POST['selectedID'])){
				$resultMessage = $lang['text']['performActionSuccessfully'];
			}else{
				$resultMessage = $lang['text']['performActionFailed'];
			}
		}
	}
	$searchResult = $tempGroup->search_groups($_POST);
	if (!$user->check_groups(array($cfg['group']['superAdmin']))){
		if (isset($searchResult->list)){
			foreach($searchResult->list as $index => $group){
				if ($group->groupID == $cfg['group']['superAdmin']){
					unset($searchResult->list[$index]);
				}
			}
		}
	}
	$page->blocks['content'] = list_groups($searchResult, $resultMessage);	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manage_groups.php");
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

function list_groups($searchResult, $resultMessage = ""){
	global $cfg;
	global $lang;
	global $menuActiveIndex;
	$html = "";
	// javascript
	$html .= "<script language=\"javascript\">\n";
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
	$html .= "if(confirm('".$lang['text']['confirmDisableGroups']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(document.actionForm.operation.value == 'enable'){\n";
	$html .= "if(confirm('".$lang['text']['confirmEnableGroups']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(document.actionForm.operation.value == 'disableUsers'){\n";
	$html .= "if(confirm('".$lang['text']['confirmDisableGroupUsers']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(document.actionForm.operation.value == 'enableUsers'){\n";
	$html .= "if(confirm('".$lang['text']['confirmEnableGroupUsers']."')) confirmed = true;\n";
	$html .= "}\n";
	$html .= "if(confirmed){\n";
	$html .= "document.listForm.operation.value = document.actionForm.operation.value;\n";
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
	$html .= $lang['menu']['manageGroups'];
	$html .= "</td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= "<input type=\"button\" value=\"".$lang['buttonCaption']['createGroup']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onClick=\"location.href='".sess_url("group_detail.php")."'\">\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	
	// page navigation
	$html .= "<p>\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\">\n";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/manage_groups.php")."\" method=\"post\" name=\"pageForm\" onSubmit=\"return disablePage();\">\n";
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
		if($key != 'page' && $key != 'pageSize' && $key != 'orderBy' && $key != 'selectedID' && $key != 'operation'){
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
	$html .= "<td colspan=\"3\"align=\"left\">\n";
	$html .= "&nbsp;&nbsp;\n";
	$html .= $lang['field']['select']." <a href=\"#\" onClick=\"selectAll()\">".$lang['text']['all']."</a>, <a href=\"#\" onClick=\"selectNone()\">".$lang['text']['none']."</a>\n";
	$html .= "</td>\n";
	$html .= "<td colspan=\"3\" align=\"right\">\n";
	$html .= $lang['field']['actions']." \n";
	$html .= "<select name=\"operation\">\n";
	$html .= "<option value=\"\"></option>\n";
	$html .= "<option value=\"enable\">".$lang['text']['enable']."</option>\n";
	$html .= "<option value=\"disable\">".$lang['text']['disable']."</option>\n";
	$html .= "<option value=\"enableUsers\">".$lang['text']['enableUsers']."</option>\n";
	$html .= "<option value=\"disableUsers\">".$lang['text']['disableUsers']."</option>\n";
	$html .= "</select> \n";
	$html .= "<input type=\"button\" value=\"".$lang['buttonCaption']['submit']."\" class=\"gobtn\" onmouseover=\"this.className='gobtnhov'\" onmouseout=\"this.className='gobtn'\" onClick=\"submitOperation()\">\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</form>\n";

	// caption and sort
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/manage_groups.php")."\" method=\"post\" name=\"searchForm\">\n";
	$html .= "<tr class=\"captionRow\">\n";
	$html .= "<td width=\"5%\">&nbsp;</td>\n";
	$html .= "<td width=\"10%\">\n";
	$html .= $lang['text']['groupID']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('GroupID ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('GroupID DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	$html .= "<td width=\"50%\">\n";
	$html .= $lang['text']['groupTitle']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('GroupTitle ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('GroupTitle DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	$html .= "<td width=\"7%\">\n";
	$html .= $lang['text']['default']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('DefaultGroup ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('DefaultGroup DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	$html .= "<td width=\"8%\">\n";
	$html .= $lang['text']['status']." \n";
	$html .= "<a href=\"#\" onClick=\"sort('Status ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
	$html .= "<a href=\"#\" onClick=\"sort('Status DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
	$html .= "</td>\n";
	$html .= "<td width=\"20%\" class=\"last\">".$lang['text']['action']."</td>\n";
	$html .= "</tr>\n";
	//search condictions
	$html .= "<tr class=\"searchRow\">\n";
	$html .= "<td>&nbsp;</td>\n";
	$html .= "<td>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['from']."</div><input type=\"text\" size=\"8\" name=\"fromID\" value=\"".htmlspecialchars($searchResult->query['fromID'])."\">\n";
	$html .= "<br>\n";
	$html .= "<div style=\"width: 45px; float: left;\">".$lang['field']['to']."</div><input type=\"text\" size=\"8\" name=\"toID\" value=\"".htmlspecialchars($searchResult->query['toID'])."\">\n";
	$html .= "</td>\n";
	$html .= "<td valign=\"top\"><input type=\"text\" name=\"keywords\" style=\"width: 95%\" value=\"".htmlspecialchars($searchResult->query['keywords'])."\"></td>\n";
	$html .= "<td valign=\"top\"><select name=\"defaultGroup\">\n";
	if($searchResult->query['defaultGroup'] == "-"){
		$html .= "<option value=\"-\" selected></option>\n";
	}else{
		$html .= "<option value=\"-\"></option>\n";
	}
	if($searchResult->query['defaultGroup'] == "1"){
		$html .= "<option value=\"1\" selected>".$lang['text']['yes']."</option>\n";
	}else{
		$html .= "<option value=\"1\">".$lang['text']['yes']."</option>\n";
	}
	if($searchResult->query['defaultGroup'] == "0"){
		$html .= "<option value=\"0\" selected>".$lang['text']['no']."</option>\n";
	}else{
		$html .= "<option value=\"0\">".$lang['text']['no']."</option>\n";
	}
	$html .= "</select></td>\n";
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
	$html .= "<td class=\"last\" valign=\"top\">\n";
	$html .= "[ <a href=\"#\" onClick=\"searchRecords();\">".$lang['buttonCaption']['search']."</a> ]\n";
	$html .= "<input type=\"hidden\" name=\"orderBy\" value=\"".$searchResult->orderBy."\">\n";
	$html .= "<input type=\"hidden\" name=\"page\" value=\"".$searchResult->page."\">\n";
	$html .= "<input type=\"hidden\" name=\"pageSize\" value=\"".$searchResult->pageSize."\">\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</form>\n";
	// list data
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/manage_groups.php")."\" method=\"post\" name=\"listForm\">\n";
	for($i = 0; $i < count($searchResult->list); $i++){
		$group = new umGroup();
		$group = $searchResult->list[$i];
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		$html .= "<td align=\"center\"><input type=\"checkbox\" name=\"selectedID[]\" value=\"".$group->groupID."\"></td>\n";
		$html .= "<td>".$group->groupID."</td>\n";
		$html .= "<td>".htmlspecialchars($group->groupTitle)."</td>\n";
		if($group->defaultGroup == 1){
			$html .= "<td>".$lang['text']['yes']."</td>\n";
		}else{
			$html .= "<td>".$lang['text']['no']."</td>\n";
		}
		if($group->status == 1){
			$html .= "<td>".$lang['text']['enabled']."</td>\n";
		}else{
			$html .= "<td>".$lang['text']['disabled']."</td>\n";
		}
		$html .= "<td class=\"last\">";
		$html .= "[ <a href=\"".sess_url("group_detail.php?groupID=".$group->groupID."&activeID=".$menuActiveIndex)."\">".$lang['text']['edit']."</a> ]";
		$html .= " ";
		$html .= "[ <a href=\"".sess_url("manage_users.php?groupID=".$group->groupID)."\">".$lang['text']['viewUsers']."</a> ]";
		$html .= " ";
		$html .= "[ <a href=\"".sess_url("group_menu.php?groupID=".$group->groupID."&activeID=".$menuActiveIndex)."\">Edit Menu</a> ]";
		if($i == 0){
			foreach($searchResult->query as $key => $value){
				if($key != 'page' && $key != 'pageSize' && $key != 'orderBy' && $key != 'selectedID' && $key != 'operation'){
					$html .= "<input type=\"hidden\" name=\"".htmlspecialchars($key)."\" value=\"".htmlspecialchars($value)."\">\n";
				}
			}
			$html .= "<input type=\"hidden\" name=\"orderBy\" value=\"".$searchResult->orderBy."\">\n";
			$html .= "<input type=\"hidden\" name=\"page\" value=\"".$searchResult->page."\">\n";
			$html .= "<input type=\"hidden\" name=\"pageSize\" value=\"".$searchResult->pageSize."\">\n";
			$html .= "<input type=\"hidden\" name=\"operation\" value=\"\">\n";
		}
		$html .= "</td>\n";
		$html .= "</tr>\n";
	}
	$html .= "</form>\n";
	$html .= "</table>\n";
	$html .= "</p>\n";
	$html .= "</div>\n";
	return $html;
}
?>