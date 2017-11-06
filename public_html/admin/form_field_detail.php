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

$fieldID = 0;
if(isset($_GET['fieldID'])) $fieldID = $_GET['fieldID'];
if(isset($_POST['fieldID'])) $fieldID = $_POST['fieldID'];
$menuActiveIndex = $_GET['activeID'];
if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['attachField'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
} else {
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/form_field_detail.php");	
}

$BackURL = "admin/";
if($formID==0){
	if($fieldID==0){
		$BackURL .= "manage_fields.php";
	}else{
		$BackURL .= "manage_fields.php?formID=".$formID;
	}
}else{
	if($fieldID==0){
		$BackURL .= "manage_forms.php";
	}else{
		$BackURL .= "manage_fields.php?formID=".$formID;
	}
}

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if($formID != 0){
		if(isset($_POST['fieldID'])){
			trim_post_value();
			$errorMessage = "";
			$errorMessage = validate_post_value();
			if($errorMessage == ""){
				$field = new umField();
				$field->fieldID = $_POST['fieldID'];
				$field->sort = $_POST['sort'];
				$form = new umForm();
				$form->formID = $_POST['formID'];
			
				if($form->attach_field($field)){
					redirect($cfg['site']['folder']."admin/manage_fields.php?formID=".$formID);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['attachField'], "", $lang['text']['attachFieldFailed']);
				}
			}else{
				$page->blocks['content'] = build_form($lang['formTitle']['attachField'], $errorMessage);
			}
		}else{
			if(init_post_value($fieldID, $formID)){
				$page->blocks['content'] = build_form($lang['formTitle']['attachField']);
			}else{
				$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
			}
		}
	}else{
		$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/form_field_detail.php?formID=".$formID."&fieldID=".$fieldID);
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
	global $menuActiveIndex;
	global $BackURL;
	// load all fields
	$result = new umResult();
	$tempField = new umField();
	$result = $tempField->search_fields(NULL);
	
	$html = "";
	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> ";
		$html .= $resultMessage;
		$html .= "</div>";
	}
	$html .= "<div class=\"formDiv\">";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/form_field_detail.php?activeID=".$menuActiveIndex)."\" method=\"post\" onSubmit=\"return disablePage();\" class=\"formLayer\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['field'];
	$html .= "</label>";
	$html .= "<select name=\"fieldID\">";
	$html .= "<option value=\"0\"></option>";
	for($i = 0; $i < count($result->list); $i++){
		$selected = "";
		if($result->list[$i]->fieldID == $_POST['fieldID']) $selected = "selected";
		$html .= "<option value=\"".$result->list[$i]->fieldID."\" ".$selected.">".htmlspecialchars($result->list[$i]->defaultFieldName)."</option>";
	}
	$html .= "</select>";
	$html .= "<input type=\"hidden\" name=\"formID\" value=\"".$_POST['formID']."\">";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['sort'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"sort\" value=\"".$_POST['sort']."\" size=\"10\">";
	$html .= "<br>";
	$html .= "<br>";
	$html .= "<label></label>";
	$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['submit']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<input type=\"button\" value=\"Back\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"javascript:location.href='".sess_url($cfg['site']['folder'].$BackURL)."'\" >";
	
	
	
	$html .= "<br>";
	$html .= "<br>";
	$html .= "</fieldset>";
	$html .= "</form>";
	$html .= "</div>";
	return $html;	
}

function init_post_value($fieldID, $formID){
	$return = true;
	$form = new umForm();
	$field = new umField();
	$form->formID = $formID;
	$field->fieldID = $fieldID;
	if($field->fieldID != 0){
		// try to load field
		if(!$form->get_field($field)){
			$return = false;
		}
	}
	$_POST['formID'] = $form->formID;
	$_POST['fieldID'] = $field->fieldID;
	$_POST['sort'] = $field->sort;
	return $return;
}

function trim_post_value(){
	$_POST['sort'] = trim($_POST['sort']);
}

function validate_post_value(){
	global $lang;
	global $cfg;
	
	$errorMessage = "";
	if($_POST['fieldID'] == 0){
		$errorMessage .= "<li>".$lang['error']['fieldNotSelected']."</li>";;
	}
	if(!is_numeric($_POST['sort']) || abs(intval($_POST['sort'])) != $_POST['sort']){
		$errorMessage .= "<li>".$lang['error']['sortIsNotInt']."</li>";;
	}
	return $errorMessage;
}
?>