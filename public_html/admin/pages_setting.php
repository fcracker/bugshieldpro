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

include_once("../lib/pagemanager.class.php");

include_once("template_func.inc.php");



$con = connect_database();

//$sql = "ALTER TABLE `mem_pages` ADD `is_menu_show` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'y'";
//@mysql_query($sql);

/*

 * create content blocks

 * page is built in this part

 */



if(!isset($_POST['pageSize'])) $_POST['pageSize'] = 10; // set default page size

if(!isset($_POST['orderBy'])) $_POST['orderBy'] = "PageName ASC"; // set default order

// allow input groupID with get method

//if(!isset($_POST['groupID']) && isset($_GET['groupID'])) $_POST['groupID'] = $_GET['groupID'];



$user = new umUser();

$user->get_session();



$allowGroups = $cfg['site']['adminGroupIDs'];

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);

if($menuActiveIndex>0){

	$page->blocks['title'] = "Manage Custom Page";

	$page->blocks['menu'] = get_menu($menuActiveIndex);

	$page->blocks['folder'] = $cfg['site']['folder'];

	$page->blocks['selectLanguage'] = $page->build_language_form();

}else{

	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/pages_setting.php");

}



if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){

	$pm = new PageManager();

	$pageid = isset($_POST["pageid"]) ? $_POST["pageid"] : false;



	$operation = isset($_POST["operation"]) ? $_POST["operation"] : "";

//	echo $operation;

	$resultMessage = "";

	$menu = new umMenu();

	if ($operation == "create" || $operation == "edit") {

		if($pageid) {

			$pm->pageid = $pageid;

			$data = $pm->getPageContent();

			if($data['is_menu_show']=='y') $data['is_menu_show'] = "checked";
			else $data['is_menu_show'] = "";
			
			$menuID = $menu->get_menuKey("page.php?pageID=".$pageid);

			if($menuID) $userGroup = $menu->get_GroupIDs($menuID);

			else $userGroup = array();

		} else {

			$data = array ("PageName" => "", "PageTitle" => "", "PageContent"=> "", "is_menu_show"=>"checked");

			$userGroup = array();

		}

		

		$page->blocks['content'] = edit_content($data);

	} else {

		//ADDED BY VLAD
		if($operation == "rollback") {			
			$results = $pm->getPageHistory($pageid);			
			$page->blocks['content'] = rollback($results);
				
		} else {

		if ($operation == "delete") {

			if(isset($_POST["selectedID"]))

				$pm->pageid = $_POST["selectedID"];

			else

				$pm->pageid = $pageid;

			

			$pm->deletePage();

			

			$menuID = $menu->get_menuKey("page.php?pageID=".$pm->pageid);

			if($menuID!==false){

				$menu->delete_menu($menuID);

				$menu->delete_groupMapping($menuID);

			}

		}				
		else if ($operation == "save") {

			$menuID = 0;


			if($pageid) {

				$pm->pageid = $pageid;

				$pm->updatePage($_POST);

				if(isset($_POST['chk_menu_show'])){
					$menuID = $menu->get_menuKey("page.php?pageID=".$pageid);
					if($menuID===false) $menuID = $menu->insert_Menu($_POST['PageName'], "page.php?pageID=".$pageid);
					else $menu->update_Menu($menuID, $_POST['PageName']);
				}else{
					if($menuID!==false) $menu->delete_menu($menuID);
					$menuID = 0;
				}

			} else {

				$pm->createPage($_POST);

				$pm->pageid = mysql_insert_id();
				
				if(isset($_POST['chk_menu_show']))
					$menuID = $menu->insert_Menu($_POST['PageName'], "page.php?pageID=".$pm->pageid);

			}

			if(isset($_POST['SelectGroup']) && $menuID>0) $userGroup = $_POST['SelectGroup'];
			else $userGroup = array();
			
			$menu->set_pageMenu($menuID, $userGroup);

		}

		

		$searchResult = $pm->searchPages($_POST);



		$page->blocks['content'] = list_users($searchResult);	
		}

	}

}else{

	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/pages_setting.php");

}



/*

 * construct and print page

 */

$page->construct_page(); // construct html page

$page->output_page(); // output page



close_database($con);


//ADDED BY VLAD
function rollback($results=array()){

global $cfg;
$html = "";

$html .= '<script type="text/javascript">';
$html .= '

function getContent(id) {
e = document.getElementById("hidden_preview_"+id);
return e.innerHTML;
}

function getPageName(id) {
e = document.getElementById("hidden_pagename_"+id);
return e.innerHTML;
}

function getPageTitle(id) {
e = document.getElementById("hidden_pagetitle_"+id);
return e.innerHTML;
}

function getMenuShow(id) {
e = document.getElementById("hidden_menushow_"+id);
if(e.innerHTML=="y")
 return true;

return false; 
}

function getSelectedGroups(id){
//get the groups
e = document.getElementById("hidden_groups_"+id);

var list = e.innerHTML.split(";");

var txt = "";

for(i=0;i<list.length;i++){
txt+="<input name=\'SelectGroup[]\' type=\'hidden\' value=\'"+list[i]+"\' />";
}

return txt;
}



function previewPage(id) {

					document.preview_form.content.value = getContent(id);
					document.preview_form.submit();

				}
				
			function dorollback(id) {
					
					if(confirm("Are you sure?")) {
					setOperation("save");
					
					document.getElementById("PageContent").value = getContent(id);
					
					document.getElementById("PageName").value = getPageName(id);
					
					document.getElementById("PageTitle").value = getPageTitle(id);
					
					document.getElementById("chk_menu_show").checked = getMenuShow(id);
					
					//set the groups also
					document.getElementById("group_holder").innerHTML = getSelectedGroups(id);
					
					//document.forms[0].pageid.value=pageid;
					
					document.forms[0].submit();
					}
				}
				
				function cancelRollback() {

					setOperation("");

					document.forms[0].submit();

				}
				
				

				

				function setOperation(op) {

					document.forms[0].operation.value = op;

				}				
				
				';
$html .= '</script>';				

$html .= "<div class='formDiv'>\n";
$html .= "<form method=\"post\" name=\"pageForm\">\n";
	if(count($results)){	
	$html.= "<h3>Saved Pages</h3>";
	$html.= "<ul>\n";
		foreach($results as $res){
			$html.= "<li>\n";
				$html.= "Version saved on ".$res->saved_on." (name: ".$res->pageName.") <a href='javascript: dorollback(".$res->id.")'>[rollback to this version]</a>&nbsp;&nbsp;<a href='javascript: previewPage(".$res->id.")'>[preview]</a>\n";
				$html.= "<div style='display:none;' id='hidden_preview_".$res->id."'>".$res->pageContent."</div>\n";
				$html.= "<div style='display:none;' id='hidden_pagename_".$res->id."'>".$res->pageName."</div>\n";
				$html.= "<div style='display:none;' id='hidden_pagetitle_".$res->id."'>".$res->pageTitle."</div>\n";
				$html.= "<div style='display:none;' id='hidden_menushow_".$res->id."'>".$res->is_menu_show."</div>\n";
				$html.= "<div style='display:none;' id='hidden_groups_".$res->id."'>".$res->groups."</div>\n";
			$html.= "</li>\n";
		}
	$html.= "</ul>\n";
	
	$html .= "<input type=\"button\" value=\"Cancel\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"cancelRollback();\"/>&nbsp;&nbsp;&nbsp;";
	
	} else {
	$html.= "<h3>This page has no previously saved versions!</h3>";
	}
	
	$html.="<div>\n";
		$html.="<strong>Note:</strong> Versions get saved every time a page is saved, in the limit of ".$cfg['pages']['rollback_limit']." versions.<br />\n";
		$html.="That limit can be modified in the config file. Rolling back from a previous version can be undone by rolling back again to the latest version";
	$html.="</div>\n";
	
	$forbidden_keys = array(
	'operation',
	'SelectGroup',
	'chk_menu_show',
	'PageContent',
	'PageTitle',
	'PageName',
	'chk_menu_show',
	);
	
	foreach ($_POST as $key=>$value) {

		if(!in_array($key,$forbidden_keys))

			$html .= '<input name="' . $key . '" value="' . $value . '" type="hidden" />'; 

	}	

	$html .= '<input type="hidden" name="operation" value=""/>';
	$html .= '<input type="hidden" name="PageContent" id="PageContent" value=""/>';
	$html .= '<input type="hidden" name="PageTitle" id="PageTitle" value=""/>';
	$html .= '<input type="hidden" name="PageName" id="PageName" value=""/>';
	$html .= '<div style="display:none;"><input type="checkbox" name="chk_menu_show" id="chk_menu_show" value=""/></div>';
	$html .= '<div style="display:none;" id="group_holder"></div>';

$html .= "</form><form style='display:none;' method='post' action='".sess_url("template_preview.php")."' target='_blank' name='preview_form'><textarea name='content'></textarea></form></div>";

$html.= "</div>\n";

return $html;

}


function edit_content($data) {

	global $cfg;

	global $userGroup;

	$groupObj = new umGroup();

	$groupAll = new umResult();

	$groupAll = $groupObj->search_groups(array());



	$html = '';

	$html .= '<script src="' . $cfg['site']['folder'] . 'js/fckeditor/fckeditor.js" type="text/javascript"></script>';

	$html .= '<script type="text/javascript">

				var sBasePath = "'. $cfg['site']['folder'] . 'js/fckeditor/";

				window.onload = function(){document.pageForm.btn_save.disabled = false;}

				

				function savePage() {

					if(document.forms[0].PageName == "") {

						alert ( "Please enter page name");

						return;

					}

					document.getElementById("PageContent").value = getContent();

					

					setOperation("save");

					document.forms[0].submit();

				}

				

				function setOperation(op){

					document.forms[0].operation.value = op;

				}

				function cancelEdit() {

					setOperation("");

					document.pageForm.btn_save.disabled = true;

					document.forms[0].PageContent.disabled = true;

					document.forms[0].PageName.disabled = true;

					document.forms[0].PageTitle.disabled = true;

					document.forms[0].submit();

				}
				
				function rollbackpage() {
					
					setOperation("rollback");					
					
					document.forms[0].submit();
				}

				

				function getContent() {

					var oEditor = FCKeditorAPI.GetInstance("PageContent") ;

					var content = oEditor.GetXHTML( true ) ;

					

					return content;

				}

				

				function previewPage() {

					document.preview_form.content.value = getContent();

					document.preview_form.submit();

				}

				

			 </script>';

	$html .= "<div class=\"formDiv\">";

	$html .= "<form method=\"post\" name=\"pageForm\">\n";

	$html .= "<table width=\"95%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";

	$html .= "<tr>\n";

	$html .= "<td class=\"titleCell\">\n";

	$html .= "Edit Content";

	$html .= "</td>\n";

	$html .= "<td align=\"right\">\n";

	$html .= "<input type=\"button\" name=\"btn_save\" value=\"Save\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"savePage();\" disabled />&nbsp;&nbsp;&nbsp;";

	$html .= "<input type=\"button\" value=\"Cancel\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"cancelEdit();\"/>&nbsp;&nbsp;&nbsp;";

	$html .= "<input type=\"button\" value=\"Preview\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"previewPage();\"/>&nbsp;&nbsp;&nbsp;";
	
	$html .= "<input type=\"button\" value=\"Rollback\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"rollbackpage();\"/>&nbsp;&nbsp;&nbsp;";

	$html .= "</td>\n";

	$html .= "</tr>\n";

	$html .= "</table>\n";

	

	// page navigation



	$html .= "<table width=\"95%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";

	// caption and sort

	$html .= "<tr class=\"captionRow\">\n";

	$html .= "<td width=\"10%\">Page Name</td>";
	
	$html .= "<td><input type=\"text\" name=\"PageName\" value=\"".($data["PageName"])."\" size=\"175\"/> &nbsp;<input type='checkbox' name='chk_menu_show' value='yes' id='chk_menu_show' ".$data['is_menu_show']." ><label for='chk_menu_show'>Show on Menu</label></td>";

	$html .= "</tr>";

	

	$html .= "<tr class=\"captionRow\">";

	$html .= "<td width=\"10%\">Page Title</td><td><input type=\"text\" name=\"PageTitle\" value=\"".($data["PageTitle"])."\" size=\"200\"/>";

	$html .= "</tr>";

	

	$html .= "<tr class=\"captionRow\">";

	$html .= "<td colspan=\"2\">";

//	print "<pre>";

	//echo $groupAll->list[1]->groupID;

//	echo $groupAll->list[1]->groupTitle;

	//print_r($groupAll->list);	

	

	for($i=0;$i<count($groupAll->list);$i++){

		$html .= "<input id=\"chk_".$groupAll->list[$i]->groupTitle."\" type=\"checkbox\" name=\"SelectGroup[]\" value=\"".$groupAll->list[$i]->groupID."\" ".(in_array($groupAll->list[$i]->groupID, $userGroup)?"checked ":"")."/><label for=\"chk_".$groupAll->list[$i]->groupTitle."\">Use ".$groupAll->list[$i]->groupTitle." Group</label>&nbsp;&nbsp;";

	}

	$html .= "</td>";

	$html .= "</tr>";

	

	$html .= "<tr class=\"captionRow\">";

	$html .= "<td colspan=\"2\">Content</td></tr>";

	$html .= "<td colspan=\"2\">";

	$html .= '<script type="text/javascript">

				var fckFooter = new FCKeditor( "PageContent" ) ;

				fckFooter.Config["FullPage"] = true ;

				fckFooter.Value = "<html><head><title></title><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\"></head><body>' . escape_linefeed($data["PageContent"]) . '</body></html>";

				fckFooter.BasePath	= sBasePath ;

				fckFooter.Height	= 400 ;

				fckFooter.Width = "100%";

				fckFooter.Config["EnterMode"] = "br";

				fckFooter.Create();				

			  </script>';

	$html .= "</td></tr>";

	$html .= "</table>";

	foreach ($_POST as $key=>$value) {

		if($key != 'operation' && $key!='SelectGroup' && $key!='chk_menu_show')

			$html .= '<input name="' . $key . '" value="' . $value . '" type="hidden" />'; 

	}

	

	$html .= '<input type="hidden" name="operation" value=""/>';

	$html .= "</form><form style='display:none;' method='post' action='".sess_url("template_preview.php")."' target='_blank' name='preview_form'><textarea name='content'></textarea></form></div>";

	return $html;

}





function build_preview() {

	global $cfg;

	$con = connect_database();

	$sql = "SELECT * FROM " . $cfg['database']['prefix'] . "template LIMIT 1";

	$result = mysql_query($sql);

	

	if(mysql_num_rows($result))

		$data = mysql_fetch_assoc($result);

	

	mysql_free_result($result);

	

	$html = '';

	$html .= '<style type="text/css">

				#preview { width: 100%; height: 100%; position: absolute; left: 0; top: 0; display: none; z-index: 100; }

				#previewNav { background-image: url(../images/nav_bg.png); background-color: orange; text-align: right; padding: 10px; }

				#previewHeader { float: left; width: 100%; }

				#previewWrapper { margin: 0 auto; background-color: #' . $data["WrapperBgcolor"] .'}

				#previewMenu { float: left; clear: both; width: 100%; overflow: hidden; background-color: #' . $data["Menu_BgColor"] .'}

				#previewContent { height: auto; float: left; clear: both; width: 100%; }

				#previewFooter { height: 50px; clear: both; width: 100%: overflow: hidden; }

				#previewBody { float: left; width: 100%; background-color: #fff; height: 100%; background-color: #' . $data["Body_Bgcolor"] .'; background-image:url(../templates/images/' . $data["Body_BgImg"] . ');}

				#previewMenu ul { float: right; list-style: none; width: 100%; padding: 0; margin: 0; background-color: #' . $data["Menu_BgColor"] .'}

				#previewMenu li { float: left; }

				#previewMenu li a {

					padding: 8px 10px;

					display: block;

					background-color:#' . $data["Mitem_Bgcolor"] .';

					color: #' . $data["Mitem_Color"] . ';

					text-decoration: ' . ($data["Mitem_Tunderline"] == 'y' ? "underline" : "none"). ';

					font-weight: ' . ($data["Mitem_Tbold"] == 'y' ? "bold" : "normal"). ';

				}

				#previewMenu li a:hover {

					text-decoration: none;

					background-color:#' . $data["Mitem_O_Bgcolor"] .';

					color: #' . $data["Mitem_O_Color"] . ';

					text-decoration: ' . ($data["Mitem_O_Tunderline"] == 'y' ? "underline" : "none"). ';

					font-weight: ' . ($data["Mitem_O_Tbold"] == 'y' ? "bold" : "normal"). ';

				}

			  </style>';

	

	$html .= '<div id="preview">';

	$html .= 	'<div id="previewNav"><a href="#none" onclick="this.parentNode.parentNode.style.display=\'none\';">Close Preview</a></div>';

	$html .=	'<div id="previewBody">';

	$html .=		'<div id="previewWrapper">';

	$html .= 			'<div id="previewHeader">[Header]</div>';

	$html .= 			'<div id="previewMenu"><ul><li><a href="#none">Menu Item 1</a></li><li><a href="#none">Menu Item 2</a></li><li><a href="#none">Menu Item 3</a></li></ul></div>';

	$html .= 			'<div id="previewContent"><h1>Welcome to Live Preview!</h1></div>';

	$html .= 			'<div id="previewFooter">[Footer]</div>';

	$html .=			'<div class="hiddenclear"></div>';

	$html .= 		'</div>';

	$html .=		'<div class="hiddenclear"></div>';

	$html .=	'</div>';

	$html .= '</div>';

	

	return $html;

}



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

	$html .= "document.pageForm.reset();\n";

	$html .= "document.pageForm.orderBy.value = orderBy;\n";

	$html .= "document.pageForm.submit();\n";

	$html .= "disablePage();\n";

	$html .= "}\n";

	$html .= "function selectAll(){\n";

	$html .= "for(var i = 0; i < document.pageForm.length; i++){\n";

	$html .= "if(document.pageForm.elements[i].type == 'checkbox') document.pageForm.elements[i].checked = true;\n";

	$html .= "}\n";

	$html .= "}\n";

	$html .= "function selectNone(){\n";

	$html .= "for(var i = 0; i < document.pageForm.length; i++){\n";

	$html .= "if(document.pageForm.elements[i].type == 'checkbox') document.pageForm.elements[i].checked = false;\n";

	$html .= "}\n";

	$html .= "}\n";

	$html .= "function countSelected(){\n";

	$html .= "var selectedNum = 0;\n";

	$html .= "for(var i = 0; i < document.pageForm.length; i++){\n";

	$html .= "if(document.pageForm.elements[i].type == 'checkbox'){\n";

	$html .= "if(document.pageForm.elements[i].checked == true) selectedNum++;\n";

	$html .= "}\n";

	$html .= "}\n";

	$html .= "return selectedNum;\n";

	$html .= "}\n";

	$html .= "</script>\n";

	$html .= '<script type="text/javascript">

				function createpage() {

					setOperation("create");

					document.forms[0].submit();

				}

				

				function deletepage(pageid) {

					if (!pageid  && !countSelected()) {

						alert("Please select pages to delete.");

						return;

					}

					setOperation("delete");

					document.forms[0].pageid.value = pageid;

					document.forms[0].submit();

				}

				

				function editpage(pageid) {

					setOperation("edit");

					document.forms[0].pageid.value=pageid;

					document.forms[0].submit();

				}
				
				function rollbackpage(pageid) {
					
					setOperation("rollback");
					
					document.forms[0].pageid.value=pageid;
					
					document.forms[0].submit();
				}

				

				function setOperation(op) {

					document.forms[0].operation.value = op;

				}

			  </script>';



	if($resultMessage != ""){

		$html .= "<div class=\"resultDiv\">\n";

		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> \n";

		$html .= $resultMessage;

		$html .= "</div>\n";

	}

	

	// title

	$html .= "<div class=\"listContent\">\n";

	$html .= "<form method=\"post\" name=\"pageForm\">\n";

	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";

	$html .= "<tr>\n";

	$html .= "<td class=\"titleCell\">\n";

	$html .= "Manage Custom Page";

	$html .= "</td>\n";

	$html .= "<td align=\"right\">\n";

	$html .= "<input type=\"button\" value=\"New\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"createpage();\"/>&nbsp;&nbsp;&nbsp;";

	$html .= "<input type=\"button\" value=\"Delete\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onClick=\"deletepage();\">\n";

	$html .= "</td>\n";

	$html .= "</tr>\n";

	$html .= "</table>\n";

	

	// page navigation

	$html .= "<p>\n";

	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\">\n";

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

		if($key != 'page' && $key != 'pageSize' && $key != 'orderBy' && $key != 'selectedID' && $key != 'PageName' && $key != 'PageTitle' && $key != 'PageContent' && $key != 'operation' && $key != 'pageid' && $key != 'SelectGroup'){

			$html .= "<input type=\"hidden\" name=\"".htmlspecialchars($key)."\" value=\"".htmlspecialchars($value)."\">\n";

		}

	}

	$html .= "<input type=\"hidden\" name=\"orderBy\" value=\"".$searchResult->orderBy."\">\n";

	$html .= "</td>\n";

	$html .= "</tr>\n";

	$html .= "</table>\n";

	

	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";

	// actions

	$html .= "<tr class=\"actionsRow\">\n";

	$html .= "<td colspan=\"3\"align=\"left\">\n";

	$html .= "&nbsp;&nbsp;\n";

	$html .= $lang['field']['select']." <a href=\"#\" onClick=\"selectAll()\">".$lang['text']['all']."</a>, <a href=\"#\" onClick=\"selectNone()\">".$lang['text']['none']."</a>\n";

	$html .= "</td>\n";

	$html .= "<td colspan=\"6\" align=\"right\"></td>\n";

	$html .= "</tr>\n";



	// caption and sort

	

	$html .= "<tr class=\"captionRow\">\n";

	$html .= "<td width=\"3%\">&nbsp;</td>\n";

	$html .= "<td width=\"5%\">Page ID</td>";
	
	$html .= "<td width=\"5%\">Show on Menu</td>";

	$html .= "<td width=\"30%\">\n";

	$html .= "Page Name\n";

	$html .= "<a href=\"#\" onClick=\"sort('PageName ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";

	$html .= "<a href=\"#\" onClick=\"sort('PageName DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";

	$html .= "</td>\n";

	$html .= "<td width=\"48%\">\n";

	$html .= "Page Title\n";

	$html .= "<a href=\"#\" onClick=\"sort('PageTitle ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";

	$html .= "<a href=\"#\" onClick=\"sort('PageTitle DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";

	$html .= "</td>\n";

	$html .= "<td width=\"auto\">\n";

	$html .= "Actions";

	$html .= "</td>\n";

	

	for($i = 0; $i < count($searchResult->list); $i++){

		$item = $searchResult->list[$i];

		if($i % 2 == 0){

			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";

		}else{

			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";

		}

		

		$html .= "<td align=\"center\"><input type=\"checkbox\" name=\"selectedID[]\" value=\"" . $item->PageID . "\"></td>\n";

		$html .= "<td>".$item->PageID."</td>\n";
		
		$html .= "<td>".($item->is_menu_show=='y'?"Yes":"No")."</td>\n";

		$html .= "<td><a href=\"javascript: editpage('" . $item->PageID . "')\">".$item->PageName."</a></td>\n";

		$html .= "<td>".$item->PageTitle."</td>\n";

		$html .= "<td><a href=\"javascript: editpage('" . $item->PageID . "')\">[Edit]</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"javascript: deletepage('" . $item->PageID . "')\">[Delete]</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"javascript: rollbackpage('".$item->PageID."')\">[Rollback]</a></td>\n";	

		$html .= "</tr>\n";

	}

	$html .= "</table>\n";

	$html .= '<input name="operation" type="hidden" id= "operation" value=""/>';

	$html .= "<input name=\"pageid\" id=\"pageid\" type=\"hidden\" value=\"\"/>";

	$html .= "</form>\n";

	$html .= "</p>\n";

	$html .= "</div>\n";

	

	return $html;

}

?>