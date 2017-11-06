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
include_once("../lib/protect.class.php");

$con = connect_database();
/*
 * create content blocks
 * page is built in this part
 */
$protectID = 0;
$protectType = 'F';
if(isset($_GET['protectID'])) $protectID = $_GET['protectID'];
if(isset($_POST['protectID'])) $protectID = $_POST['protectID'];
if(isset($_GET['protectType'])) $protectType = $_GET['protectType'];
if(isset($_POST['protectType'])) $protectType = $_POST['protectType'];

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = $_GET['activeID'];
if ($menuActiveIndex > 0){
	if($protectType == 'F'){
		$page->blocks['title'] = $lang['title']['protectFolder'];
	}else{
		$page->blocks['title'] = $lang['title']['protectLink'];
	}
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
} else {
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/protect_detail.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(isset($_POST['protectID'])){
		trim_post_value();
		$errorMessage = "";
		if($_POST['protectType'] == 'F'){
			/* protect folder */
			$errorMessage = validate_post_value_folder($protectID);
			if($errorMessage == ""){
				if($protectID == 0){
					/* new protected folder */
					$protect = new umProtect();
					$protect->protectType = 'F';
					$protect->protectURL = $_POST['protectURL'];
					$protect->redirURL = $_POST['redirURL'];
					$protect->memo = $_POST['memo'];
					for($i = 0; $i < count($_POST['groupID']); $i++){
						$group = new umGroup();
						$group->groupID = $_POST['groupID'][$i];
						$protect->allowGroups[] = $group;
					}
					if($protect->create_protect()){
						init_post_value($protect->protectID, $protect->protectType);
						$page->blocks['content'] = build_folder_form($lang['formTitle']['protectFolder'], "", $lang['text']['createProtectedFolderSuccessfully']);
					}else{
						$page->blocks['content'] = build_folder_form($lang['formTitle']['protectFolder'], "", $lang['text']['createProtectedFolderFailed']);
					}
				}else{
					/* update protected folder */
					$protect = new umProtect();
					$protect->protectID = $_POST['protectID'];
					$protect->protectType = 'F';
					$protect->protectURL = $_POST['protectURL'];
					$protect->redirURL = $_POST['redirURL'];
					$protect->memo = $_POST['memo'];
					for($i = 0; $i < count($_POST['groupID']); $i++){
						$group = new umGroup();
						$group->groupID = $_POST['groupID'][$i];
						$protect->allowGroups[] = $group;
					}
					if($protect->update_protect()){
						$page->blocks['content'] = build_folder_form($lang['formTitle']['protectFolder'], "", $lang['text']['updateProtectedFolderSuccessfully']);
					}else{
						$page->blocks['content'] = build_folder_form($lang['formTitle']['protectFolder'], "", $lang['text']['updateProtectedFolderFailed']);
					}					
				}
			}else{
				$page->blocks['content'] = build_folder_form($lang['formTitle']['protectFolder'], $errorMessage);
			}
		}else{
			/* protect url */
			$errorMessage = validate_post_value_url($protectID);
			if($errorMessage == ""){
				if($protectID == 0){
					/* new protected folder */
					$protect = new umProtect();
					$protect->protectType = 'U';
					$protect->protectURL = $_POST['protectURL'];
					$protect->redirURL = $_POST['redirURL'];
					$protect->memo = $_POST['memo'];
					for($i = 0; $i < count($_POST['groupID']); $i++){
						$group = new umGroup();
						$group->groupID = $_POST['groupID'][$i];
						$protect->allowGroups[] = $group;
					}
					if($protect->create_protect()){
						init_post_value($protect->protectID, $protect->protectType);
						$page->blocks['content'] = build_url_form($lang['formTitle']['protectLink'], "", $lang['text']['createProtectedURLSuccessfully']);
					}else{
						$page->blocks['content'] = build_url_form($lang['formTitle']['protectLink'], "", $lang['text']['createProtectedURLFailed']);
					}
				}else{
					/* update protected folder */
					$protect = new umProtect();
					$protect->protectID = $_POST['protectID'];
					$protect->protectType = 'U';
					$protect->protectURL = $_POST['protectURL'];
					$protect->redirURL = $_POST['redirURL'];
					$protect->memo = $_POST['memo'];
					for($i = 0; $i < count($_POST['groupID']); $i++){
						$group = new umGroup();
						$group->groupID = $_POST['groupID'][$i];
						$protect->allowGroups[] = $group;
					}
					if($protect->update_protect()){
						$page->blocks['content'] = build_url_form($lang['formTitle']['protectLink'], "", $lang['text']['updateProtectedURLSuccessfully']);
					}else{
						$page->blocks['content'] = build_url_form($lang['formTitle']['protectLink'], "", $lang['text']['updateProtectedURLFailed']);
					}					
				}
			}else{
				$page->blocks['content'] = build_url_form($lang['formTitle']['protectLink'], $errorMessage);
			}
		}
		
	}else{
		if(init_post_value($protectID, $protectType)){
			if($_POST['protectType'] == 'F'){
				$page->blocks['content'] = build_folder_form($lang['formTitle']['protectFolder']);
			}else{
				$page->blocks['content'] = build_url_form($lang['formTitle']['protectLink']);
			}
		}else{
			$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
		}
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/protect_detail.php?protectID=".$protectID);
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
function build_folder_form($formTitle, $errorMessage = "", $resultMessage = ""){
	global $lang;
	global $cfg;
	global $menuActiveIndex;
	// load all groups
	$result = new umResult();
	$tempGroup = new umGroup();
	$result = $tempGroup->search_groups(NULL);
	
	// load all redirect options
	$redirOptions = array();
	$option['url'] = $cfg['site']['folder']."login1.php";
	$option['display'] = $lang['text']['loginPage'];
	$redirOptions[] = $option;
	
	$html = "";
	$html .= "<script type=\"text/javascript\">";
	$html .= "var FP_RESULT = '';";
	$html .= "function openFolderPicker(){";
	$html .= "window.open('file-picker/file-picker.php?var=FP_RESULT&filter=8&multi=0', 'FilePicker', 'toolbar=no,menubar=no,width=400,height=300');";
	$html .= "}";
	$html .= "function setValue(){";
	$html .= "var obj;";
	$html .= "if (FP_RESULT){";
	$html .= "obj = eval('(' + FP_RESULT + ')');";
	$html .= "}";
	$html .= "document.protectForm.protectURL.value = obj.uri + '/' + obj.files;";
	$html .= "}";
	$html .= "function displayCustomURL(){";
	$html .= "if(document.protectForm.redirOption.value==''){";
	$html .= "document.getElementById('cDiv').style.display = 'block';";
	$html .= "}else{";
	$html .= "document.getElementById('cDiv').style.display = 'none';";
	$html .= "}";
	$html .= "}";
	$html .= "function submitForm(){";
	$html .= "if(document.protectForm.redirOption.value!=''){";
	$html .= "document.protectForm.redirURL.value = document.protectForm.redirOption.value;";
	$html .= "}";
	$html .= "if(document.getElementById('selectedGroups').value == ''){";
	$html .= "if(confirm('".$lang['text']['confirmNoAccess']."')){";
	$html .= "return disablePage();";
	$html .= "}else{";
	$html .= "return false;";
	$html .= "}";
	$html .= "}else{";
	$html .= "return disablePage();";
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
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/protect_detail.php?activeID=".$menuActiveIndex)."\" method=\"post\" onSubmit=\"return submitForm();\" class=\"formLayer\" name=\"protectForm\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['protectedFolder'];
	$html .= "</label>";
	if($_POST['protectID'] != 0){
		$html .= "<span class=\"value\">";
		$html .= htmlspecialchars($_POST['protectURL']);
		$html .= "<input type=\"hidden\" name=\"protectURL\" value=\"".htmlspecialchars($_POST['protectURL'])."\">";
		$html .= "</span>";
	}else{
		$html .= "<input type=\"text\" name=\"protectURL\" value=\"".htmlspecialchars($_POST['protectURL'])."\" size=\"50\">";
		$html .= "<input type=\"button\" name=\"openFolderPickerButton\" value=\"...\" class=\"gobtn\" onmouseover=\"this.className='gobtnhov'\" onmouseout=\"this.className='gobtn'\" onClick=\"openFolderPicker()\">";
	}
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['allowGroups'];
	$html .= "</label>";
	$html .= "<select name=\"groupID[]\" multiple=\"multiple\" size=\"5\" id=\"selectedGroups\">";
	for($i = 0; $i < count($result->list); $i++){
		$selected = "";
		for($j = 0; $j < count($_POST['groupID']); $j++){
			if($_POST['groupID'][$j] == $result->list[$i]->groupID) $selected = "selected";
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
	$html .= $lang['field']['redirTo'];
	$html .= "</label>";
	$selected = false;
	$html .= "<select name=\"redirOption\" onChange=\"displayCustomURL()\">";
	for($i = 0; $i < count($redirOptions); $i++){
		if($redirOptions[$i]['url'] == $_POST['redirOption']){
			$selected = true;
			$html .= "<option value=\"".$redirOptions[$i]['url']."\" selected>".$redirOptions[$i]['display']."</option>";
		}else{
			$html .= "<option value=\"".$redirOptions[$i]['url']."\">".$redirOptions[$i]['display']."</option>";
		}
	}
	if($selected){
		$html .= "<option value=\"\">".$lang['text']['customURL']."</option>";
	}else{
		$html .= "<option value=\"\" selected>".$lang['text']['customURL']."</option>";
	}
	$html .= "</select>";
	$html .= "<br>";
	$displayMode = "block";
	if($selected) $displayMode = "none";
	$html .= "<div id=\"cDiv\" style=\"display: ".$displayMode."\">";
	$html .= "<label>";
	$html .= $lang['field']['customURL'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"redirURL\" value=\"".htmlspecialchars($_POST['redirURL'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "</div>";
	$html .= "<label>";
	$html .= $lang['field']['memo'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"memo\" value=\"".htmlspecialchars($_POST['memo'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "<br>";
	$html .= "<label></label>";
	$html .= "<input type=\"hidden\" name=\"protectID\" value=\"".$_POST['protectID']."\">";
	$html .= "<input type=\"hidden\" name=\"protectType\" value=\"F\">";
	$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['submit']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= " ";
	$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<br>";
	$html .= "<br>";
	$html .= "</fieldset>";
	$html .= "</form>";
	$html .= "</div>";
	return $html;	
}

function build_url_form($formTitle, $errorMessage = "", $resultMessage = ""){
	global $lang;
	global $cfg;
	global $menuActiveIndex;
	// load all groups
	$result = new umResult();
	$tempGroup = new umGroup();
	$result = $tempGroup->search_groups(NULL);
	
	// load all redirect options
	$redirOptions = array();
	$option['url'] = $cfg['site']['folder']."login1.php";
	$option['display'] = $lang['text']['loginPage'];
	$redirOptions[] = $option;
	
	$html = "";
	$html .= "<script type=\"text/javascript\">";
	$html .= "function displayCustomURL(){";
	$html .= "if(document.protectForm.redirOption.value==''){";
	$html .= "document.getElementById('cDiv').style.display = 'block';";
	$html .= "}else{";
	$html .= "document.getElementById('cDiv').style.display = 'none';";
	$html .= "}";
	$html .= "}";
	$html .= "function submitForm(){";
	$html .= "if(document.protectForm.redirOption.value!=''){";
	$html .= "document.protectForm.redirURL.value = document.protectForm.redirOption.value;";
	$html .= "}";
	$html .= "if(document.getElementById('selectedGroups').value == ''){";
	$html .= "if(confirm('".$lang['text']['confirmNoAccess']."')){";
	$html .= "return disablePage();";
	$html .= "}else{";
	$html .= "return false;";
	$html .= "}";
	$html .= "}else{";
	$html .= "return disablePage();";
	$html .= "}";
	$html .= "}";
	$html .= "</script>";
	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> ";
		$html .= $resultMessage;
		$html .= "</div>";
	}
	$html .= "<div style=\"margin: 20px;\">";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/protect_detail.php?activeID=".$menuActiveIndex)."\" method=\"post\" onSubmit=\"return submitForm();\" class=\"formLayer\" name=\"protectForm\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['protectedURL'];
	$html .= "</label>";
	if($_POST['protectID'] != 0){
		$html .= "<span class=\"value\">";
		$html .= htmlspecialchars($_POST['protectURL']);
		$html .= "<input type=\"hidden\" name=\"protectURL\" value=\"".htmlspecialchars($_POST['protectURL'])."\">";
		$html .= "</span>";
	}else{
		$html .= "<input type=\"text\" name=\"protectURL\" value=\"".htmlspecialchars($_POST['protectURL'])."\" size=\"50\">";
	}
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['allowGroups'];
	$html .= "</label>";
	$html .= "<select name=\"groupID[]\" multiple=\"multiple\" size=\"5\" id=\"selectedGroups\">";
	for($i = 0; $i < count($result->list); $i++){
		$selected = "";
		for($j = 0; $j < count($_POST['groupID']); $j++){
			if($_POST['groupID'][$j] == $result->list[$i]->groupID) $selected = "selected";
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
	$html .= $lang['field']['redirTo'];
	$html .= "</label>";
	$selected = false;
	$html .= "<select name=\"redirOption\" onChange=\"displayCustomURL()\">";
	for($i = 0; $i < count($redirOptions); $i++){
		if($redirOptions[$i]['url'] == $_POST['redirOption']){
			$selected = true;
			$html .= "<option value=\"".$redirOptions[$i]['url']."\" selected>".$redirOptions[$i]['display']."</option>";
		}else{
			$html .= "<option value=\"".$redirOptions[$i]['url']."\">".$redirOptions[$i]['display']."</option>";
		}
	}
	if($selected){
		$html .= "<option value=\"\">".$lang['text']['customURL']."</option>";
	}else{
		$html .= "<option value=\"\" selected>".$lang['text']['customURL']."</option>";
	}
	$html .= "</select>";
	$html .= "<br>";
	$displayMode = "block";
	if($selected) $displayMode = "none";
	$html .= "<div id=\"cDiv\" style=\"display: ".$displayMode."\">";
	$html .= "<label>";
	$html .= $lang['field']['customURL'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"redirURL\" value=\"".htmlspecialchars($_POST['redirURL'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "</div>";
	$html .= "<label>";
	$html .= $lang['field']['memo'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"memo\" value=\"".htmlspecialchars($_POST['memo'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "<br>";
	$html .= "<label></label>";
	$html .= "<input type=\"hidden\" name=\"protectID\" value=\"".$_POST['protectID']."\">";
	$html .= "<input type=\"hidden\" name=\"protectType\" value=\"U\">";
	$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['submit']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= " ";
	$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<br>";
	$html .= "<br>";
	$html .= "</fieldset>";
	$html .= "</form>";
	$html .= "</div>";
	return $html;	
}

function init_post_value($protectID, $protectType){
	global $cfg;
	
	$return = true;
	$protect = new umProtect();
	$protect->protectID = $protectID;
	$protect->protectType = $protectType;
	if($protect->protectID != 0){
		// try to load protection
		if(!$protect->get_protect()) $return = false;
	}
	$_POST['protectID'] = $protect->protectID;
	$_POST['protectType'] = $protect->protectType;
	$_POST['protectURL'] = $protect->protectURL;
	$_POST['redirURL'] = $protect->redirURL;
	$_POST['memo'] = $protect->memo;
	$_POST['groupID'] = array();
	for($i = 0; $i < count($protect->allowGroups); $i++){
		$_POST['groupID'][] = $protect->allowGroups[$i]->groupID;
	}
	$_POST['redirOption'] = $protect->redirURL;
	if($protect->protectID == 0){
		$_POST['redirOption'] = $cfg['site']['folder']."login1.php"; // default redirect option is login page
	}
	return $return;
}

function trim_post_value(){
	$_POST['protectURL'] = trim($_POST['protectURL']);
	$_POST['redirURL'] = trim($_POST['redirURL']);
	$_POST['memo'] = trim($_POST['memo']);
	if(!isset($_POST['groupID'])) $_POST['groupID'] = array();
}

function validate_post_value_folder($protectID){
	global $lang;
	$errorMessage = "";
	
	if(strlen($_POST['protectURL']) < PROTECT_URL_LENGTH_MIN || strlen($_POST['protectURL']) > PROTECT_URL_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['protectedFolderInvalidLength'], PROTECT_URL_LENGTH_MIN, PROTECT_URL_LENGTH_MAX)."</li>";
	}
	if(substr($_POST['protectURL'], -1) == '/'){
		$errorMessage .= "<li>".$lang['error']['protectedFolderInvalidFormat']."</li>";
	}
	$existingProtect = new umProtect();
	$existingProtect->protectURL = $_POST['protectURL'];
	$existingProtect->get_protect();
	if($existingProtect->protectID != 0 && $protectID != $existingProtect->protectID){
		$errorMessage .= "<li>".$lang['error']['protectedFolderExists']."</li>";
	}
	if(strlen($_POST['redirURL']) < REDIRECT_URL_LENGTH_MIN || strlen($_POST['redirURL']) > REDIRECT_URL_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['redirectURLInvalidLength'], REDIRECT_URL_LENGTH_MIN, REDIRECT_URL_LENGTH_MAX)."</li>";
	}
	if(strlen($_POST['memo']) > MEMO_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['memoInvalidLength'], MEMO_LENGTH_MAX)."</li>";
	}
	
	return $errorMessage;
}

function validate_post_value_url($protectID){
	global $lang;
	$errorMessage = "";
	
	
	if(strlen($_POST['protectURL']) < PROTECT_URL_LENGTH_MIN || strlen($_POST['protectURL']) > PROTECT_URL_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['protectedFolderInvalidLength'], PROTECT_URL_LENGTH_MIN, PROTECT_URL_LENGTH_MAX)."</li>";;
	}
	if(substr($_POST['protectURL'], -1) != '$' || substr($_POST['protectURL'], 0, 1) != '^'){
		$errorMessage .= "<li>".$lang['error']['protectedURLInvalidFormat']."</li>";;
	}
	$existingProtect = new umProtect();
	$existingProtect->protectURL = $_POST['protectURL'];
	$existingProtect->get_protect();
	if($existingProtect->protectID != 0 && $protectID != $existingProtect->protectID){
		$errorMessage .= "<li>".$lang['error']['protectedFolderExists']."</li>";
	}
	if(strlen($_POST['redirURL']) < REDIRECT_URL_LENGTH_MIN || strlen($_POST['redirURL']) > REDIRECT_URL_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['redirectURLInvalidLength'], REDIRECT_URL_LENGTH_MIN, REDIRECT_URL_LENGTH_MAX)."</li>";;
	}
	if(strlen($_POST['memo']) > MEMO_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['memoInvalidLength'], MEMO_LENGTH_MAX)."</li>";;
	}
	
	return $errorMessage;
}

?>