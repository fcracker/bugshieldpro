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
$fieldID = 0;
if(isset($_GET['fieldID'])) $fieldID = $_GET['fieldID'];
if(isset($_POST['fieldID'])) $fieldID = $_POST['fieldID'];

$optionID = 0;
if(isset($_GET['optionID'])) $optionID = $_GET['optionID'];
if(isset($_POST['optionID'])) $optionID = $_POST['optionID'];

if($optionID == 0){
	$page->blocks['title'] = $lang['title']['createOption'];
}else{
	$page->blocks['title'] = $lang['title']['updateOption'];
}
$page->blocks['menu'] = get_menu(2);
$page->blocks['folder'] = $cfg['site']['folder'];
$page->blocks['selectLanguage'] = $page->build_language_form();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if($fieldID != 0){
		if(isset($_POST['optionID'])){
			trim_post_value();
			$errorMessage = "";
			$errorMessage = validate_post_value();
			if($errorMessage == ""){
				$option = new umFieldOption();
				$option->optionID = $_POST['optionID'];
				$option->fieldID = $_POST['fieldID'];
				$option->defaultCaption = $_POST['defaultCaption'];
				for($i = 0; $i < count($cfg['languages']); $i++){
					$optionCaption['langID'] = $_POST["optionCaption".$i."LangID"];
					$optionCaption['caption'] = $_POST["optionCaption".$i."Caption"];
					$option->captions[] = $optionCaption;
				}
				$option->sort = $_POST['sort'];
				$option->status = $_POST['status'];
			
				if($option->optionID == 0){
					// create new option
					if($option->create_option() && $option->optionID != 0){
						//init_post_value($option->optionID, $fieldID);
						//$page->blocks['content'] = build_form($lang['formTitle']['updateOption'], "", $lang['text']['createOptionSuccessfully']);
						redirect($cfg['site']['folder']."admin/manage_options.php?fieldID=".$fieldID."&orderBy=OptionID%20DESC");
					}else{
						$page->blocks['content'] = build_form($lang['formTitle']['createOption'], "", $lang['text']['createOptionFailed']);
					}
				}else{
					// update option
					if($option->update_option()){
						$page->blocks['content'] = build_form($lang['formTitle']['updateOption'], "", $lang['text']['updateFieldSuccessfully']);
					}else{
						$page->blocks['content'] = build_form($lang['formTitle']['updateOption'], "", $lang['text']['updateFieldFailed']);
					}
				}
			}else{
				if($optionID == 0){
					$page->blocks['content'] = build_form($lang['formTitle']['createOption'], $errorMessage);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['updateOption'], $errorMessage);
				}
			}
		}else{
			if(init_post_value($optionID, $fieldID)){
				if($optionID == 0){
					$page->blocks['content'] = build_form($lang['formTitle']['createOption']);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['updateOption']);
				}
			}else{
				$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
			}
		}
	}else{
		$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/option_detail.php?optionID=".$optionID."&fieldID=".$fieldID);
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
	global $_fieldType;
	global $fieldID;
	$html = "";
	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> ";
		$html .= $resultMessage;
		$html .= "</div>";
	}
	$html .= "<div class=\"formDiv\">";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/option_detail.php")."\" method=\"post\" onSubmit=\"return disablePage();\" class=\"formLayer\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['optionID'];
	$html .= "</label>";
	$html .= "<span class=\"value\">";
	if($_POST['optionID'] == 0){
		$html .= $lang['text']['notAssigned'];
	}else{
		$html .= $_POST['optionID'];
	}
	$html .= "<input type=\"hidden\" name=\"optionID\" value=\"".$_POST['optionID']."\">";
	$html .= "<input type=\"hidden\" name=\"fieldID\" value=\"".$_POST['fieldID']."\">";
	$html .= "</span>";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['internalOptionCaption'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"defaultCaption\" value=\"".htmlspecialchars($_POST['defaultCaption'])."\" size=\"50\">";
	$html .= "<br>";
	for($i = 0; $i < count($cfg['languages']); $i++){
		$OptionLangID = $cfg['languages'][$i]['id'];
		$OptionLangName = $cfg['languages'][$i]['display'];
		$OptionCaption = "";
		for($j = 0; $j < $_POST['captions']; $j++){
			if($OptionLangID == $_POST["optionCaption".$j."LangID"]) $OptionCaption = $_POST["optionCaption".$j."Caption"];
		}
		$html .= "<label>";
		$html .= sprintf($lang['field']['optionCaption'],  "(".$OptionLangName.")");;
		$html .= "</label>";
		$html .= "<input type=\"text\" name=\"optionCaption".$i."Caption\" value=\"".htmlspecialchars($OptionCaption)."\" size=\"50\">";
		$html .= "<input type=\"hidden\" name=\"optionCaption".$i."LangID\" value=\"".htmlspecialchars($OptionLangID)."\" size=\"50\">";
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['sort'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"sort\" value=\"".$_POST['sort']."\" size=\"10\">";
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
	$html .= "<br>";
	$html .= "<label></label>";
	if($_POST['fieldID'] == 0){
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['create']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}else{
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['submit']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}
	$html .= " ";
	$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<input type=\"button\" value=\"Go Manage Options\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"javascript:location.href='".sess_url($cfg['site']['folder']."admin/manage_options.php".($fieldID==0?"":"?fieldID=$fieldID"))."'\" >";
	
	$html .= "<input type=\"hidden\" name=\"captions\" value=\"".count($cfg['languages'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "<br>";
	$html .= "</fieldset>";
	$html .= "</form>";
	$html .= "</div>";
	return $html;	
}

function init_post_value($optionID, $fieldID){
	$return = true;
	$option = new umFieldOption();
	$option->optionID = $optionID;
	$option->fieldID = $fieldID;
	if($option->optionID != 0){
		// try to load field
		if(!$option->get_option()){
			$return = false;
		}
	}
	$_POST['optionID'] = $option->optionID;
	$_POST['fieldID'] = $option->fieldID;
	$_POST['defaultCaption'] = $option->defaultCaption;
	for($i = 0; $i < count($option->captions); $i++){
		$_POST["optionCaption".$i."LangID"] = $option->captions[$i]['langID'];
		$_POST["optionCaption".$i."Caption"] = $option->captions[$i]['caption'];
	}
	$_POST['captions'] = count($option->captions);
	$_POST['sort'] = $option->sort;
	$_POST['status'] = $option->status;
	return $return;
}

function trim_post_value(){
	$_POST['defaultCaption'] = trim($_POST['defaultCaption']);
	for($i = 0; $i < $_POST['captions']; $i++){
		$_POST["optionCaption".$i."Caption"] = trim($_POST["optionCaption".$i."Caption"]);
	}
}

function validate_post_value(){
	global $lang;
	global $cfg;
	
	$errorMessage = "";
	
	if(strlen($_POST['defaultCaption']) < OPTION_CAPTION_LENGTH_MIN || strlen($_POST['defaultCaption']) > OPTION_CAPTION_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['defaultOptionCaptionInvalidLength'], OPTION_CAPTION_LENGTH_MIN, OPTION_CAPTION_LENGTH_MAX)."</li>";
	}
	for($i = 0; $i < count($cfg['languages']); $i++){
		if(strlen($_POST["optionCaption".$i."Caption"]) < OPTION_CAPTION_LENGTH_MIN || strlen($_POST["optionCaption".$i."Caption"]) > OPTION_CAPTION_LENGTH_MAX){
			$errorMessage .= "<li>".sprintf($lang['error']['fieldNameInvalidLength'], $cfg['languages'][$i]['display'], OPTION_CAPTION_LENGTH_MIN, OPTION_CAPTION_LENGTH_MAX)."</li>";
		}
	}
	if(!is_numeric($_POST['sort']) || abs(intval($_POST['sort'])) != $_POST['sort']){
		$errorMessage .= "<li>".$lang['error']['sortIsNotInt']."</li>";;
	}
	return $errorMessage;
}
?>