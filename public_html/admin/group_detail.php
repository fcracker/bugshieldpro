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
$groupID = 0;
if(isset($_GET['groupID'])) $groupID = $_GET['groupID'];
if(isset($_POST['groupID'])) $groupID = $_POST['groupID'];

$user = new umUser();
$user->get_session();

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	if($groupID == 0){
		$page->blocks['title'] = $lang['title']['createGroup'];
	}else{
		$page->blocks['title'] = $lang['title']['updateGroup'];
	}
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
} else {
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/group_detail.php");	
}

$allowGroups = $cfg['site']['adminGroupIDs'];
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(isset($_POST['groupID'])){
		trim_post_value();
		$errorMessage = "";
		$errorMessage = validate_post_value($groupID);
		if($errorMessage == ""){
			$group = new umGroup();
			$group->groupID = $_POST['groupID'];
			$group->groupTitle = $_POST['groupTitle'];
			$group->defaultGroup = $_POST['defaultGroup'];
			$group->status = $_POST['status'];
			$group->memo = $_POST['memo'];
			$group->price = $_POST['price'];
			$group->upkeep = $_POST['upkeep'];
			if($group->groupID == 0){
				// create new group
				if($group->create_group() && $group->groupID != 0){
					init_post_value($group->groupID);
					$page->blocks['content'] = build_form($lang['formTitle']['updateGroup'], "", $lang['text']['createGroupSuccessfully']);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['createGroup'], "", $lang['text']['createGroupFailed']);
				}
			}else{
				// update group
				if($group->update_group()){
					$page->blocks['content'] = build_form($lang['formTitle']['updateGroup'], "", $lang['text']['updateGroupSuccessfully']);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['updateGroup'], "", $lang['text']['updateGroupFailed']);
				}
			}
		}else{
			if($groupID == 0){
				$page->blocks['content'] = build_form($lang['formTitle']['createGroup'], $errorMessage);
			}else{
				$page->blocks['content'] = build_form($lang['formTitle']['updateGroup'], $errorMessage);
			}
		}
	}else{
		if(init_post_value($groupID)){
			if($groupID == 0){
				$page->blocks['content'] = build_form($lang['formTitle']['createGroup']);
			}else{
				$page->blocks['content'] = build_form($lang['formTitle']['updateGroup']);
			}
		}else{
			$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
		}
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/group_detail.php?groupID=".$groupID);
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

/*
* show message
*/
function show_message($messageText){
	$html = "";
	$html .= "<div style=\"margin: 20px; height: 300px\">";
	$html .= $messageText;
	$html .= "</div>";
	return $html;
}

/*
* build form of this page
*/
function build_form($formTitle, $errorMessage = "", $resultMessage = ""){
	global $lang;
	global $cfg;
	$html = "";
	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> ";
		$html .= $resultMessage;
		$html .= "</div>";
	}
	$html .= "<div class=\"formDiv\">";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/group_detail.php")."\" method=\"post\" onSubmit=\"return disablePage();\" class=\"formLayer\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['groupID'];
	$html .= "</label>";
	$html .= "<span class=\"value\">";
	if($_POST['groupID'] == 0){
		$html .= $lang['text']['notAssigned'];
	}else{
		$html .= $_POST['groupID'];
	}
	$html .= "<input type=\"hidden\" name=\"groupID\" value=\"".$_POST['groupID']."\" size=\"15\">";
	$html .= "</span>";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['groupTitle'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"groupTitle\" value=\"".htmlspecialchars($_POST['groupTitle'])."\" size=\"30\">";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['defaultGroup'];
	$html .= "</label>";
	if($_POST['defaultGroup'] == 1){
		$html .= "<input type=\"radio\" name=\"defaultGroup\" value=\"1\" class=\"radio\" checked> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"defaultGroup\" value=\"0\" class=\"radio\"> ".$lang['text']['no'];
	}else{
		$html .= "<input type=\"radio\" name=\"defaultGroup\" value=\"1\" class=\"radio\"> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"defaultGroup\" value=\"0\" class=\"radio\" checked> ".$lang['text']['no'];
	}
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['status'];
	$html .= "</label>";
	if($_POST['status'] == 1){
		$html .= "<input type=\"radio\" name=\"status\" value=\"1\" class=\"radio\" checked> ".$lang['text']['enabled'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"status\" value=\"0\" class=\"radio\"> ".$lang['text']['disabled'];
	}else{
		$html .= "<input type=\"radio\" name=\"status\" value=\"1\" class=\"radio\"> ".$lang['text']['enabled'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"status\" value=\"0\" class=\"radio\" checked> ".$lang['text']['disabled'];
	}
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['memo'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"memo\" value=\"".htmlspecialchars($_POST['memo'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "<label>Price : </label>";
	$html .= "<input type=\"text\" name=\"price\" value=\"".htmlspecialchars($_POST['price'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "<label>Upkeep : </label>";
	$html .= "<input type=\"text\" name=\"upkeep\" value=\"".htmlspecialchars($_POST['upkeep'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "<label></label>";
	if($_POST['groupID'] == 0){
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['create']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
		$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}else{
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['submit']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
		$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
		$html .= "<input type=\"button\" value=\"Go Manage Groups\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"javascript:location.href='".sess_url($cfg['site']['folder']."admin/manage_groups.php'")."\" >";
	}

	$html .= "<br>";
	$html .= "<br>";
	$html .= "</fieldset>";
	$html .= "</form>";
	$html .= "</div>";
	return $html;	
}

function init_post_value($groupID){
	$return = true;
	$group = new umGroup();
	$group->groupID = $groupID;
	if($group->groupID != 0){
		// try to load groups
		if(!$group->get_group()){
			$return = false;
		}
	}
	$_POST['groupID'] = $group->groupID;
	$_POST['groupTitle'] = $group->groupTitle;
	$_POST['defaultGroup'] = $group->defaultGroup;
	$_POST['status'] = $group->status;
	$_POST['memo'] = $group->memo;
	$_POST['price'] = $group->price;
	$_POST['upkeep'] = $group->upkeep;
	return $return;
}

function trim_post_value(){
	$_POST['groupTitle'] = trim($_POST['groupTitle']);
	$_POST['memo'] = trim($_POST['memo']);
}

function validate_post_value($groupID){
	global $lang;
	$errorMessage = "";
	
	if(strlen($_POST['groupTitle']) < GROUP_TITLE_LENGTH_MIN || strlen($_POST['groupTitle']) > GROUP_TITLE_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['groupTitleInvalidLength'], GROUP_TITLE_LENGTH_MIN, GROUP_TITLE_LENGTH_MAX)."</li>";;
	}
	$existingGroup = new umGroup();
	$existingGroup->groupTitle = $_POST['groupTitle'];
	$existingGroup->get_group();
	if($existingGroup->groupID != 0 && $groupID != $existingGroup->groupID){
		$errorMessage .= "<li>".$lang['error']['groupExisits']."</li>";
	}
	if(strlen($_POST['memo']) > MEMO_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['memoInvalidLength'], MEMO_LENGTH_MAX)."</li>";;
	}
	
	return $errorMessage;
}
?>