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

if($fieldID == 0){
	$page->blocks['title'] = $lang['title']['createField'];
}else{
	$page->blocks['title'] = $lang['title']['updateField'];
}
$page->blocks['menu'] = get_menu(2);
$page->blocks['folder'] = $cfg['site']['folder'];
$page->blocks['selectLanguage'] = $page->build_language_form();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(isset($_POST['fieldID'])){
		trim_post_value();
		$errorMessage = "";
		$errorMessage = validate_post_value();
		if($errorMessage == ""){
			$field = new umField();
			$field->fieldID = $_POST['fieldID'];
			$field->defaultFieldName = $_POST['defaultFieldName'];
			for($i = 0; $i < count($cfg['languages']); $i++){
				$fieldName['langID'] = $_POST["fieldName".$i."LangID"];
				$fieldName['caption'] = $_POST["fieldName".$i."Caption"];
				$field->fieldNames[] = $fieldName;
			}
			$field->fieldType = $_POST['fieldType'];
			$field->isRequired = $_POST['isRequired'];
			$field->format = $_POST['format'];
			$field->minLength = $_POST['minLength'];
			$field->maxLength = $_POST['maxLength'];
			
			if($field->fieldID == 0){
				// create new field
				if($field->create_field() && $field->fieldID != 0){
					init_post_value($field->fieldID);
					$page->blocks['content'] = build_form($lang['formTitle']['updateField'], "", $lang['text']['createFieldSuccessfully']);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['createField'], "", $lang['text']['createFieldFailed']);
				}
			}else{
				// update field
				if($field->update_field()){
					$page->blocks['content'] = build_form($lang['formTitle']['updateField'], "", $lang['text']['updateFieldSuccessfully']);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['updateField'], "", $lang['text']['updateFieldFailed']);
				}
			}
		}else{
			if($fieldID == 0){
				$page->blocks['content'] = build_form($lang['formTitle']['createField'], $errorMessage);
			}else{
				$page->blocks['content'] = build_form($lang['formTitle']['updateField'], $errorMessage);
			}
		}
	}else{
		if(init_post_value($fieldID)){
			if($fieldID == 0){
				$page->blocks['content'] = build_form($lang['formTitle']['createField']);
			}else{
				$page->blocks['content'] = build_form($lang['formTitle']['updateField']);
			}
		}else{
			$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
		}
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/field_detail.php?fieldID=".$fieldID);
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
	
	$html = "";
	$html .= "<script language=\"javascript\">\n";
	$html .= "function showDiv(){";
	$html .= "var ft = document.detailForm.fieldType;";
	$html .= "if(ft.value == '0' || ft.value == '1' || ft.value == '7'){";
	$html .= "document.getElementById('inputFormat').style.display = 'block';";
	$html .= "}else{";
	$html .= "document.getElementById('inputFormat').style.display = 'none';";
	$html .= "}";
	$html .= "if(ft.value == '1' || ft.value == '7'){";
	$html .= "document.getElementById('inputLength').style.display = 'block';";
	$html .= "}else{";
	$html .= "document.getElementById('inputLength').style.display = 'none';";
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
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/field_detail.php")."\" method=\"post\" onSubmit=\"return disablePage();\" class=\"formLayer\" name=\"detailForm\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['fieldID'];
	$html .= "</label>";
	$html .= "<span class=\"value\">";
	if($_POST['fieldID'] == 0){
		$html .= $lang['text']['notAssigned'];
	}else{
		$html .= $_POST['fieldID'];
	}
	$html .= "<input type=\"hidden\" name=\"fieldID\" value=\"".$_POST['fieldID']."\" size=\"15\">";
	$html .= "</span>";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['internalFieldName'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"defaultFieldName\" value=\"".htmlspecialchars($_POST['defaultFieldName'])."\" size=\"50\">";
	$html .= "<br>";
	for($i = 0; $i < count($cfg['languages']); $i++){
		$fieldLangID = $cfg['languages'][$i]['id'];
		$fieldLangName = $cfg['languages'][$i]['display'];
		$fieldCaption = "";
		for($j = 0; $j < $_POST['fieldNames']; $j++){
			if($fieldLangID == $_POST["fieldName".$j."LangID"]) $fieldCaption = $_POST["fieldName".$j."Caption"];
		}
		$html .= "<label>";
		$html .= sprintf($lang['field']['fieldName'],  "(".$fieldLangName.")");;
		$html .= "</label>";
		$html .= "<input type=\"text\" name=\"fieldName".$i."Caption\" value=\"".htmlspecialchars($fieldCaption)."\" size=\"50\">";
		$html .= "<input type=\"hidden\" name=\"fieldName".$i."LangID\" value=\"".htmlspecialchars($fieldLangID)."\">";
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['fieldType'];
	$html .= "</label>";
	if($_POST['fieldID'] == 0){
		$html .= "<select name=\"fieldType\" onChange=\"showDiv()\">";
		$html .= "<option value=\"-1\"></option>";
		for($i = 0; $i < count($_fieldType); $i++){
			$selected = "";
			if($i == $_POST['fieldType']) $selected = "selected";
			$html .= "<option value=\"".$i."\" ".$selected.">".$lang['text'][$_fieldType[$i]]."</option>";
		}
		$html .= "</select>";
	}else{
		$html .= "<input type=\"text\" name=\"temp2\" value=\"".$lang['text'][$_fieldType[$_POST['fieldType']]]."\" size=\"50\" class=\"showText\" readonly>";
		$html .= "<input type=\"hidden\" name=\"fieldType\" value=\"".$_POST['fieldType']."\">";
	}
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['isRequired'];
	$html .= "</label>";
	if($_POST['isRequired'] == 1){
		$html .= "<input type=\"radio\" name=\"isRequired\" value=\"1\" class=\"radio\" checked> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"isRequired\" value=\"0\" class=\"radio\"> ".$lang['text']['no'];
	}else{
		$html .= "<input type=\"radio\" name=\"isRequired\" value=\"1\" class=\"radio\"> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"isRequired\" value=\"0\" class=\"radio\" checked> ".$lang['text']['no'];
	}
	$html .= "<br>";
	$displayMode = "none";
	if($_POST['fieldType'] == 0 || $_POST['fieldType'] == 1 ||$_POST['fieldType'] == 7) $displayMode = "block";
	$html .= "<div id=\"inputFormat\" style=\"display: ".$displayMode."\">";
	$html .= "<label>";
	$html .= $lang['field']['inputFormat'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"format\" value=\"".htmlspecialchars($_POST['format'])."\" size=\"30\">";
	$html .= "<br>";
	$html .= "</div>";
	$displayMode = "none";
	if($_POST['fieldType'] == 1 || $_POST['fieldType'] == 7) $displayMode = "block";
	$html .= "<div id=\"inputLength\" style=\"display: ".$displayMode."\">";
	$html .= "<label>";
	$html .= $lang['field']['minLength'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"minLength\" value=\"".$_POST['minLength']."\" size=\"10\">";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['maxLength'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"maxLength\" value=\"".$_POST['maxLength']."\" size=\"10\">";
	$html .= "<br>";
	$html .= "</div>";
	$html .= "<br>";
	$html .= "<label></label>";
	if($_POST['fieldID'] == 0){
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['create']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}else{
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['submit']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}
	$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<input type=\"button\" value=\"Go Manage Forms Fields\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"javascript:location.href='".sess_url($cfg['site']['folder']."admin/manage_fields.php")."'\" >";
	$html .= "<input type=\"hidden\" name=\"fieldNames\" value=\"".count($cfg['languages'])."\">";
	$html .= "<br>";
	$html .= "<br>";
	$html .= "</fieldset>";
	$html .= "</form>";
	$html .= "</div>";
	return $html;	
}

function init_post_value($fieldID){
	$return = true;
	$field = new umField();
	$field->fieldID = $fieldID;
	if($field->fieldID != 0){
		// try to load field
		if(!$field->get_field()){
			$return = false;
		}
	}
	$_POST['fieldID'] = $field->fieldID;
	$_POST['defaultFieldName'] = $field->defaultFieldName;
	for($i = 0; $i < count($field->fieldNames); $i++){
		$_POST["fieldName".$i."LangID"] = $field->fieldNames[$i]['langID'];
		$_POST["fieldName".$i."Caption"] = $field->fieldNames[$i]['caption'];
	}
	$_POST['fieldNames'] = count($field->fieldNames);
	$_POST['fieldType'] = $field->fieldType;
	$_POST['isRequired'] = $field->isRequired;
	$_POST['format'] = $field->format;
	$_POST['minLength'] = $field->minLength;
	$_POST['maxLength'] = $field->maxLength;
	return $return;
}

function trim_post_value(){
	$_POST['defaultFieldName'] = trim($_POST['defaultFieldName']);
	for($i = 0; $i < $_POST['fieldNames']; $i++){
		$_POST["fieldName".$i."Caption"] = trim($_POST["fieldName".$i."Caption"]);
	}
	$_POST['format'] = trim($_POST['format']);
	$_POST['minLength'] = trim($_POST['minLength']);
	$_POST['maxLength'] = trim($_POST['maxLength']);
}

function validate_post_value(){
	global $lang;
	global $cfg;
	
	$errorMessage = "";
	
	if(strlen($_POST['defaultFieldName']) < FIELD_NAME_LENGTH_MIN || strlen($_POST['defaultFieldName']) > FIELD_NAME_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['defaultFieldNameInvalidLength'], FIELD_NAME_LENGTH_MIN, FIELD_NAME_LENGTH_MAX)."</li>";
	}
	for($i = 0; $i < count($cfg['languages']); $i++){
		if(strlen($_POST["fieldName".$i."Caption"]) < FIELD_NAME_LENGTH_MIN || strlen($_POST["fieldName".$i."Caption"]) > FIELD_NAME_LENGTH_MAX){
			$errorMessage .= "<li>".sprintf($lang['error']['fieldNameInvalidLength'], $cfg['languages'][$i]['display'], FIELD_NAME_LENGTH_MIN, FIELD_NAME_LENGTH_MAX)."</li>";
		}
	}
	if($_POST['fieldType'] == -1){
		$errorMessage .= "<li>".$lang['error']['fieldTypeNotSelected']."</li>";;
	}
	if(strlen($_POST['format']) > INPUT_FORMAT_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['inputFormatInvalidLength'], INPUT_FORMAT_LENGTH_MAX)."</li>";;
	}
	if(!is_numeric($_POST['minLength']) || abs(intval($_POST['minLength'])) != $_POST['minLength']){
		$errorMessage .= "<li>".$lang['error']['minLengthIsNotInt']."</li>";;
	}
	if(!is_numeric($_POST['maxLength']) || abs(intval($_POST['maxLength'])) != $_POST['maxLength']){
		$errorMessage .= "<li>".$lang['error']['maxLengthIsNotInt']."</li>";;
	}
	
	return $errorMessage;
}
?>