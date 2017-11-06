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
include_once("../lib/form.class.php");

$con = connect_database();
/*
 * create content blocks
 * page is built in this part
 */
$formID = 0;
if(isset($_GET['formID'])) $formID = $_GET['formID'];
if(isset($_POST['formID'])) $formID = $_POST['formID'];

$user = new umUser();
$user->get_session();

$menuActiveIndex = $_GET['activeID'];
if($menuActiveIndex>0){
	if($formID == 0){
		$page->blocks['title'] = $lang['title']['createForm'];
	}else{
		$page->blocks['title'] = $lang['title']['updateForm'];
	}
	$page->blocks['menu'] = get_menu(2);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
} else {
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/form_detail.php");
}

$allowGroups = $cfg['site']['adminGroupIDs'];
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(isset($_POST['formID'])){
		trim_post_value();
		$errorMessage = "";
		$errorMessage = validate_post_value();
		if($errorMessage == ""){
			$form = new umForm();
			$form->formID = $_POST['formID'];
			$form->defaultFormTitle = $_POST['defaultFormTitle'];
			for($i = 0; $i < count($cfg['languages']); $i++){
				$formTitle['langID'] = $_POST["formTitle".$i."LangID"];
				$formTitle['caption'] = $_POST["formTitle".$i."Caption"];
				$form->formTitles[] = $formTitle;
			}
			$form->formType = $_POST['formType'];
			$form->formSpecial = $_POST['formSpecial'];
			for($i = 0; $i < count($_POST['accessGroups']); $i++){
				$group = new umGroup();
				$group->groupID = $_POST['accessGroups'][$i];
				$form->accessGroups[] = $group;
			}
			for($i = 0; $i < count($_POST['assignToGroups']); $i++){
				$group = new umGroup();
				$group->groupID = $_POST['assignToGroups'][$i];
				$form->assignToGroups[] = $group;
			}
			
			for($i = 0; $i < count($_POST['removeFromGroups']); $i++){
				$group = new umGroup();
				$group->groupID = $_POST['removeFromGroups'][$i];
				$form->removeFromGroups[] = $group;
			}
			
			$form->redirTo = $_POST['redirTo'];
			
			if($form->formID == 0){
				// create new form
				if($form->create_form() && $form->formID != 0){
					init_post_value($form->formID);
					$page->blocks['content'] = build_form($lang['formTitle']['updateForm'], "", $lang['text']['createFormSuccessfully']);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['createForm'], "", $lang['text']['createFormFailed']);
				}
			}else{
				// update form
				if($form->update_form()){
					$page->blocks['content'] = build_form($lang['formTitle']['updateForm'], "", $lang['text']['updateFormSuccessfully']);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['updateForm'], "", $lang['text']['updateFormFailed']);
				}
			}
		}else{
			if($formID == 0){
				$page->blocks['content'] = build_form($lang['formTitle']['createForm'], $errorMessage);
			}else{
				$page->blocks['content'] = build_form($lang['formTitle']['updateForm'], $errorMessage);
			}
		}
	}else{
		if(init_post_value($formID)){
			if($formID == 0){
				$page->blocks['content'] = build_form($lang['formTitle']['createForm']);
			}else{
				$page->blocks['content'] = build_form($lang['formTitle']['updateForm']);
			}
		}else{
			$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
		}
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/form_detail.php?formID=".$formID);
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
	global $_formType;
	global $menuActiveIndex;
	// load all groups
	$result = new umResult();
	$tempGroup = new umGroup();
	$result = $tempGroup->search_groups(NULL);
	
	$html = "";
	$html .= "<script language=\"javascript\">\n";
	$html .= "function showDiv(){";
	$html .= "var ft = document.detailForm.formType;";
	$html .= "if(ft.value == '1'){";
	$html .= "document.getElementById('inputScript').style.display = 'block';";
	$html .= "}else{";
	$html .= "document.getElementById('inputScript').style.display = 'none';";
	$html .= "}";
	$html .= "}";
	$html .= "</script>";
	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> ";
		$html .= $resultMessage;
		$html .= "</div>";
	}
	$html .= "<div class=\"formDiv\">";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/form_detail.php?activeID=".$menuActiveIndex)."\" method=\"post\" onSubmit=\"return disablePage();\" class=\"formLayer\" name=\"detailForm\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['formID'];
	$html .= "</label>";
	$html .= "<span class=\"value\">";
	if($_POST['formID'] == 0){
		$html .= $lang['text']['notAssigned'];
	}else{
		$html .= $_POST['formID'];
	}
	$html .= "<input type=\"hidden\" name=\"formID\" value=\"".$_POST['formID']."\" size=\"15\">";
	$html .= "</span>";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['internalFormTitle'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"defaultFormTitle\" value=\"".htmlspecialchars($_POST['defaultFormTitle'])."\" size=\"50\">";
	$html .= "<br>";
	for($i = 0; $i < count($cfg['languages']); $i++){
		$formLangID = $cfg['languages'][$i]['id'];
		$formLangName = $cfg['languages'][$i]['display'];
		$formCaption = "";
		for($j = 0; $j < $_POST['formTitles']; $j++){
			if($formLangID == $_POST["formTitle".$j."LangID"]) $formCaption = $_POST["formTitle".$j."Caption"];
		}
		$html .= "<label>";
		$html .= sprintf($lang['field']['formTitle'],  "(".$formLangName.")");;
		$html .= "</label>";
		$html .= "<input type=\"text\" name=\"formTitle".$i."Caption\" value=\"".htmlspecialchars($formCaption)."\" size=\"50\">";
		$html .= "<input type=\"hidden\" name=\"formTitle".$i."LangID\" value=\"".htmlspecialchars($formLangID)."\">";
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['formType'];
	$html .= "</label>";
	if($_POST['formID'] == 0){
		$html .= "<select name=\"formType\" onChange=\"showDiv()\">";
		$html .= "<option value=\"-1\"></option>";
		for($i = 0; $i < count($_formType); $i++){
			$selected = "";
			if($i == $_POST['formType']) $selected = "selected";
			$html .= "<option value=\"".$i."\" ".$selected.">".$_formType[$i]."</option>";
		}
		$html .= "</select>";
	}else{
		$html .= "<span class=\"value\">";
		$html .= $_formType[$_POST['formType']];
		$html .= "<input type=\"hidden\" name=\"formType\" value=\"".$_POST['formType']."\">";
		$html .= "</span>";
	}
	$html .= "<br>";
	$displayMode = "none";
	if($_POST['formType'] == 1) $displayMode = "block";
	$html .= "<div id=\"inputScript\" style=\"display: ".$displayMode."\">";
	$html .= "<label>";
	$html .= $lang['field']['formSpecial'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"formSpecial\" value=\"".htmlspecialchars($_POST['formSpecial'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "</div>";
	$html .= "<label>";
	$html .= $lang['field']['accessGroups'];
	$html .= "</label>";
	$html .= "<select name=\"accessGroups[]\" multiple=\"multiple\" size=\"5\">";
	for($i = 0; $i < count($result->list); $i++){
		$selected = "";
		for($j = 0; $j < count($_POST['accessGroups']); $j++){
			if($_POST['accessGroups'][$j] == $result->list[$i]->groupID) $selected = "selected";
		}
		$groupStatus = "";
		if($result->list[$i]->status == 1){
			$groupStatus = $lang['text']['enabled'];
		}else{
			$groupStatus = $lang['text']['disabled'];
		}
		$html .= "<option value=\"".$result->list[$i]->groupID."\" ".$selected.">"."[".$groupStatus."] ".htmlspecialchars($result->list[$i]->groupTitle)."</option>";
	}
	$html .= "</select>";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['assignToGroups'];
	$html .= "</label>";
	$html .= "<select name=\"assignToGroups[]\" multiple=\"multiple\" size=\"5\">";
	for($i = 0; $i < count($result->list); $i++){
		$selected = "";
		for($j = 0; $j < count($_POST['assignToGroups']); $j++){
			if($_POST['assignToGroups'][$j] == $result->list[$i]->groupID) $selected = "selected";
		}
		$groupStatus = "";
		if($result->list[$i]->status == 1){
			$groupStatus = $lang['text']['enabled'];
		}else{
			$groupStatus = $lang['text']['disabled'];
		}
		$html .= "<option value=\"".$result->list[$i]->groupID."\" ".$selected.">"."[".$groupStatus."] ".htmlspecialchars($result->list[$i]->groupTitle)."</option>";
	}
	$html .= "</select>";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['removeFromGroups'];
	$html .= "</label>";
	$html .= "<select name=\"removeFromGroups[]\" multiple=\"multiple\" size=\"5\">";
	for($i = 0; $i < count($result->list); $i++){
		$selected = "";
		for($j = 0; $j < count($_POST['removeFromGroups']); $j++){
			if($_POST['removeFromGroups'][$j] == $result->list[$i]->groupID) $selected = "selected";
		}
		$groupStatus = "";
		if($result->list[$i]->status == 1){
			$groupStatus = $lang['text']['enabled'];
		}else{
			$groupStatus = $lang['text']['disabled'];
		}
		$html .= "<option value=\"".$result->list[$i]->groupID."\" ".$selected.">"."[".$groupStatus."] ".htmlspecialchars($result->list[$i]->groupTitle)."</option>";
	}
	$html .= "</select>";
	$html .= "<br>";
	$html .= "<label style=\"display:none;\" >";
	$html .= $lang['field']['redirTo'];
	$html .= "</label>";
	$html .= "<input type=\"text\" style=\"display:none;\" name=\"redirTo\" value=\"#".htmlspecialchars($_POST['redirTo'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "<br>";
	$html .= "<label></label>";
	if($_POST['formID'] == 0){
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['create']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}else{
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['submit']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}
	$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<input type=\"button\" value=\"Go Manage Forms\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"javascript:location.href='".sess_url($cfg['site']['folder']."admin/manage_forms.php")."'\" >";	
	$html .= "<input type=\"hidden\" name=\"formTitles\" value=\"".count($cfg['languages'])."\">";
	$html .= "<br>";
	$html .= "<br>";
	$html .= "</fieldset>";
	$html .= "</form>";
	$html .= "</div>";
	return $html;	
}

function init_post_value($formID){
	$return = true;
	$form = new umForm();
	$form->formID = $formID;
	if($form->formID != 0){
		// try to load form
		if(!$form->get_form()){
			$return = false;
		}
	}
	$_POST['formID'] = $form->formID;
	$_POST['defaultFormTitle'] = $form->defaultFormTitle;
	for($i = 0; $i < count($form->formTitles); $i++){
		$_POST["formTitle".$i."LangID"] = $form->formTitles[$i]['langID'];
		$_POST["formTitle".$i."Caption"] = $form->formTitles[$i]['caption'];
	}
	$_POST['formTitles'] = count($form->formTitles);
	$_POST['formType'] = $form->formType;
	$_POST['formSpecial'] = $form->formSpecial;
	$_POST['accessGroups'] = array();
	for($i = 0; $i < count($form->accessGroups); $i++){
		$_POST['accessGroups'][] = $form->accessGroups[$i]->groupID;
	}
	$_POST['assignToGroups'] = array();
	for($i = 0; $i < count($form->assignToGroups); $i++){
		$_POST['assignToGroups'][] = $form->assignToGroups[$i]->groupID;
	}
	$_POST['removeFromGroups'] = array();
	for($i = 0; $i < count($form->removeFromGroups); $i++){
		$_POST['removeFromGroups'][] = $form->removeFromGroups[$i]->groupID;
	}
	$_POST['redirTo'] = $form->redirTo;
	return $return;
}

function trim_post_value(){
	$_POST['defaultFormTitle'] = trim($_POST['defaultFormTitle']);
	for($i = 0; $i < $_POST['formTitles']; $i++){
		$_POST["formTitle".$i."Caption"] = trim($_POST["formTitle".$i."Caption"]);
	}
	$_POST['formSpecial'] = trim($_POST['formSpecial']);
	$_POST['redirTo'] = trim($_POST['redirTo']);
	if(!isset($_POST['accessGroups'])) $_POST['accessGroups'] = array();
	if(!isset($_POST['assignToGroups'])) $_POST['assignToGroups'] = array();
	if(!isset($_POST['removeFromGroups'])) $_POST['removeFromGroups'] = array();
}

function validate_post_value(){
	global $lang;
	global $cfg;
	
	$errorMessage = "";
	
	if(strlen($_POST['defaultFormTitle']) < FORM_TITLE_LENGTH_MIN || strlen($_POST['defaultFormTitle']) > FORM_TITLE_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['defaultFormTitleInvalidLength'], FORM_TITLE_LENGTH_MIN, FORM_TITLE_LENGTH_MAX)."</li>";
	}
	for($i = 0; $i < count($cfg['languages']); $i++){
		if(strlen($_POST["formTitle".$i."Caption"]) < FORM_TITLE_LENGTH_MIN || strlen($_POST["formTitle".$i."Caption"]) > FORM_TITLE_LENGTH_MAX){
			$errorMessage .= "<li>".sprintf($lang['error']['formTitleInvalidLength'], $cfg['languages'][$i]['display'], FORM_TITLE_LENGTH_MIN, FORM_TITLE_LENGTH_MAX)."</li>";
		}
	}
	if($_POST['formType'] == -1){
		$errorMessage .= "<li>".$lang['error']['formTypeNotSelected']."</li>";;
	}
	if(strlen($_POST['formSpecial']) > FORM_SEPCIAL_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['formSpecialInvalidLength'], FORM_SEPCIAL_LENGTH_MAX)."</li>";;
	}
	if(strlen($_POST['redirTo']) < FORM_REDIRECT_LENGTH_MIN || strlen($_POST['redirTo']) > FORM_REDIRECT_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['redirectURLInvalidLength'], FORM_REDIRECT_LENGTH_MIN, FORM_REDIRECT_LENGTH_MAX)."</li>";
	}
	
	return $errorMessage;
}
?>