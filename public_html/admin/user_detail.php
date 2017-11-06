<?php
include_once("../lib/config.inc.php");
$defaultLang = $cfg['language'];
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
include_once("../lib/email.inc.php");
include_once("../lib/phpmailer/class.phpmailer.php");
include_once("../lib/phpmailer/class.smtp.php");

$con = connect_database();

/*
 * create content blocks
 * page is built in this part
 */
$userID = 0;
if(isset($_GET['userID'])) $userID = $_GET['userID'];
if(isset($_POST['userID'])) $userID = $_POST['userID'];

$user = new umUser();
$user->get_session();
$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	if($userID == 0){
		$page->blocks['title'] = $lang['title']['createUser'];
	}else{
		$page->blocks['title'] = $lang['title']['updateUser'];
	}
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/user_detail.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(isset($_POST['userID'])){
		trim_post_value();
		$errorMessage = "";
		$errorMessage = validate_post_value($userID);
		if($errorMessage == ""){
			$objUser = new umUser();
			$objUser->userID = $_POST['userID'];
			
			if($objUser->userID == 0){
				// create new user
				$objUser->emailAddress = $_POST['emailAddress'];
				$objUser->email = $_POST['email'];
				$objUser->emailVerified = $_POST['emailVerified'];
				$objUser->verificationCode = $objUser->generate_verification_code();
				$objUser->password = md5($_POST['password']);
				$objUser->createTime = date("Y-m-d H:i:s");
				$objUser->status = $_POST['status'];
				$objUser->memo = $_POST['memo'];								
				$objUser->days = $_POST['days'];
				
				$data["emailAddress"] = $objUser->emailAddress;
				$data["email"] = $objUser->email;
				$data["emailVerified"] = $objUser->emailVerified;
				$data["verificationCode"] = $objUser->verificationCode;
				$data["password"] = $objUser->password;
				$data["createTime"] = $objUser->createTime;
				$data["status"] = $objUser->status;
				$data["memo"] = $objUser->memo;								
				$data["days"] = $objUser->days;								
				list($y,$m,$d) = explode("-", date("Y-n-j"));				
				$data["expiration"] = ($y+1)."-".$m."-".$d;
				
				if($objUser->create_user($data, $_POST['groupID'][0]) && $objUser->userID != 0){
					// assign groups
					if (($_POST['groupID'] && $_POST['groupID'][0]== $cfg['group']['superAdmin']) || $objUser->check_groups(array($cfg['group']['superAdmin']))){
						if ($user->check_groups($cfg['group']['superAdmin'])){
							$objUser->assign_groups($_POST['groupID']);
						}
					}else{
						$objUser->assign_groups($_POST['groupID']);
					}
					// send email
					if($_POST['sendCode'] == 1){
						$emailTags = array();
						$emailTags['siteName'] = $cfg['site']['name'];
						$emailTags['siteURL'] = $cfg['site']['url'];
						$emailTags['systemURL'] = $cfg['site']['url'].$cfg['site']['folder'];
						$emailTags['link'] = $cfg['site']['url'].$cfg['site']['folder']."verify.php?id=".$objUser->userID."&code=".$objUser->verificationCode;
						sendTemplateEmail(
							$cfg['email']['systemEmail'],
							$cfg['site']['name'],
							$cfg['email']['systemEmail'],
							$objUser->emailAddress,
							"",
							"../templates/".$defaultLang."/emails/welcome_verify.txt",
							$emailTags
						);					
					}
					init_post_value($objUser->userID);
					$page->blocks['content'] = build_form($lang['formTitle']['updateUser'], "", $lang['text']['createUserSuccessfullyAdmin']);
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['createUser'], "", $lang['text']['createUserFailedAdmin']);
				}
			}else{
				// update user
				if($objUser->get_user(false)){
//					$data["emailAddress"] = $objUser->emailAddress;
//					$data["email"] = $objUser->email;

					$create_time = strtotime($_POST['createTime']);
					$cr = date("Y-m-d H:i:s",$create_time);
					$objUser->createTime = $cr;
					
										
					//last time
					if(strlen($_POST['lastLoginTime'])){
						$last_time = strtotime($_POST['lastLoginTime']);
						$la = date("Y-m-d H:i:s",$last_time);
						
						$objUser->lastLoginTime = $la;
					}
					
					$objUser->loginCount = intval($_POST['loginCount']);
					
					$objUser->emailAddress = $_POST['emailAddress'];
					$objUser->email = $_POST['email'];
					$objUser->emailVerified = $_POST['emailVerified'];
					if($_POST['password'] != '') $objUser->password = md5($_POST['password']);
					$objUser->status = $_POST['status'];
					$objUser->memo = $_POST['memo'];					$objUser->days = $_POST['days'];
					// re-assign groups
					if (($_POST['groupID'] && $_POST['groupID'][0]== $cfg['group']['superAdmin']) || $objUser->check_groups(array($cfg['group']['superAdmin']))){
						if ($user->check_groups($cfg['group']['superAdmin'])){
							$objUser->assign_groups($_POST['groupID']);
						}
					}else{
						$objUser->assign_groups($_POST['groupID']);
					}
					if($objUser->update_user()){
						if($_POST['sendCode'] == 1){
							$emailTags = array();
							$emailTags['siteName'] = $cfg['site']['name'];
							$emailTags['siteURL'] = $cfg['site']['url'];
							$emailTags['systemURL'] = $cfg['site']['url'].$cfg['site']['folder'];
							$emailTags['link'] = $cfg['site']['url'].$cfg['site']['folder']."verify.php?id=".$objUser->userID."&code=".$objUser->verificationCode;
							sendTemplateEmail(
								$cfg['email']['systemEmail'],
								$cfg['site']['name'],
								$cfg['email']['systemEmail'],
								$objUser->emailAddress,
								"",
								"../templates/".$defaultLang."/emails/verify_code.txt",
								$emailTags
							);
						}
						$page->blocks['content'] = build_form($lang['formTitle']['updateUser'], "", "The user information has been successfully updated.");
					}else{
						$page->blocks['content'] = build_form($lang['formTitle']['updateUser'], "", "Failed to change the user information.");
					}
				}else{
					$page->blocks['content'] = build_form($lang['formTitle']['updateUser'], "", "Please select a user.");
				}
			}
		}else{
			if($userID == 0){
				$page->blocks['content'] = build_form($lang['formTitle']['createUser'], $errorMessage);
			}else{
				$page->blocks['content'] = build_form($lang['formTitle']['updateUser'], $errorMessage);
			}
		}
	}else{
		if(init_post_value($userID)){
			if($userID == 0){
				$page->blocks['content'] = build_form($lang['formTitle']['createUser']);
			}else{
				$page->blocks['content'] = build_form($lang['formTitle']['updateUser']);
			}
		}else{
			$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
		}
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/user_detail.php?userID=".$userID);
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
	global $userID;
	// load all groups
	$result = new umResult();
	$tempGroup = new umGroup();
	$result = $tempGroup->search_groups(NULL);
	
	$html = "";
	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> ";
		$html .= $resultMessage;
		$html .= "</div>";
	}
	$html .= "<div class=\"formDiv\">";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/user_detail.php")."\" method=\"post\" onSubmit=\"return disablePage();\" class=\"formLayer\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	$html .= "<label>";
	$html .= $lang['field']['userID'];
	$html .= "</label>";
	$html .= "<span class=\"value\">";
	if($_POST['userID'] == 0){
		$html .= $lang['text']['notAssigned'];
	}else{
		$html .= $_POST['userID'];
	}
	$html .= "<input type=\"hidden\" name=\"userID\" value=\"".$_POST['userID']."\" size=\"15\">";
	$html .= "</span>";
	$html .= "<br>";
	$html .= "<label>";
	$html .= "User Name:";
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"email\" value=\"".htmlspecialchars($_POST['email'])."\" size=\"50\"><br>";
	$html .= "<label>";
	$html .= $lang['field']['email'];
	$html .= "</label>";
  
  
  if($_POST['userID'] != 0){
	$html .= "<input type=\"text\" name=\"emailAddress\" value=\"".htmlspecialchars($_POST['emailAddress'])."\" size=\"50\" disabled='true'>";
  $html.="<input type='hidden' name='emailAddress' value='".htmlspecialchars($_POST['emailAddress'])."' />";
  } else {
  $html .= "<input type=\"text\" name=\"emailAddress\" value=\"".htmlspecialchars($_POST['emailAddress'])."\" size=\"50\" disabled='true'>";  
  }
	if($_POST['userID'] != 0){
		$html .= " <a href=\"mailto:".htmlspecialchars($_POST['email'])."\"><img src=\"".$cfg['site']['folder'] ."images/email_button.gif\" align=\"absmiddle\" border=\"0\" alt=\"".$lang['text']['writeEmail']."\" title=\"".$lang['text']['writeEmail']."\"></a>";
		$html .= " <a href=\"".sess_url("login_as.php?userID=".$_POST['userID'])."\"><img src=\"".$cfg['site']['folder'] ."images/login_button.gif\" align=\"absmiddle\" border=\"0\" alt=\"".$lang['text']['loginAsUser']."\" title=\"".$lang['text']['loginAsUser']."\"></a>";
	}
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['emailVerified'];
	$html .= "</label>";
	if($_POST['emailVerified'] == 1){
		$html .= "<input type=\"radio\" name=\"emailVerified\" value=\"1\" class=\"radio\" checked> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"emailVerified\" value=\"0\" class=\"radio\"> ".$lang['text']['no'];
	}else{
		$html .= "<input type=\"radio\" name=\"emailVerified\" value=\"1\" class=\"radio\"> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"emailVerified\" value=\"0\" class=\"radio\" checked> ".$lang['text']['no'];
	}
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['verificationCode'];
	$html .= "</label>";
	$checked = "";
	if($_POST['sendCode'] == 1) $checked = "checked";
	$html .= "<input type=\"checkbox\" value=\"1\" name=\"sendCode\" class=\"checkbox\" ".$checked."> ".$lang['text']['sendVerificationEmail'];
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['newPassword'];
	$html .= "</label>";
	$html .= "<input type=\"password\" name=\"password\" value=\"\" size=\"25\">";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['retypePassword'];
	$html .= "</label>";
	$html .= "<input type=\"password\" name=\"password2\" value=\"\" size=\"25\">";
	$html .= "<br>";
	if($_POST['userID'] != 0){
		$html .= "<label>";
		$html .= $lang['field']['createTime'];
		$html .= "</label>";
		//$html .= "<span class=\"value\">";
		//$html .= date($lang['timeFormat'], strtotime($_POST['createTime']));
		$html .= "<input type=\"text\" name=\"createTime\" value=\"".date($lang['timeFormat'], strtotime($_POST['createTime']))."\" readonly id=\"cre_date\">";
		$html .= "&nbsp;&nbsp;<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"cre_dateTrigger\" align=\"absmiddle\"></a>";
		//$html .= "</span>";
		$html .= "<br>";
		$html .= "<label>";
		$html .= $lang['field']['lastLoginTime'];
		$html .= "</label>";
		//$html .= "<span class=\"value\">";
	
			//$html .= date($lang['timeFormat'], strtotime($_POST['lastLoginTime']));
			//$html .= "<input type=\"hidden\" name=\"lastLoginTime\" value=\"".date($lang['timeFormat'], strtotime($_POST['lastLoginTime']))."\">";
			
		$html .= "<input type=\"text\" name=\"lastLoginTime\" value=\"".($_POST['lastLoginTime'] != "" ? date($lang['timeFormat'], strtotime($_POST['lastLoginTime'])) : "")."\" readonly id=\"last_date\">";
		$html .= "&nbsp;&nbsp;<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"last_dateTrigger\" align=\"absmiddle\"></a>";	
			
		
		//$html .= "</span>";
		$html .= "<br>";
		$html .= "<label>";
		$html .= $lang['field']['lastLoginIP'];
		$html .= "</label>";
		$html .= "<span class=\"value\">";
		$html .= htmlspecialchars($_POST['lastLoginIP']);
		$html .= "<input type=\"hidden\" name=\"lastLoginIP\" value=\"".htmlspecialchars($_POST['lastLoginIP'])."\">";
		$html .= "</span>";
		$html .= "<br>";
		$html .= "<label>";
		$html .= $lang['field']['logins'];
		$html .= "</label>";
	//	$html .= "<span class=\"value\">";
		//$html .= htmlspecialchars($_POST['loginCount']);
		$html .= "<input type=\"text\" name=\"loginCount\" value=\"".htmlspecialchars($_POST['loginCount'])."\">";
		//$html .= "</span>";
		$html .= "<br>";
	}
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
	$html .= "Notice:";
	$html .= "</label>";
	$html .= "<textarea name=\"memo\" cols=\"50\" rows=\"3\">".htmlspecialchars($_POST['memo'])."</textarea>";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['groups'];
	$html .= "</label>";
	$html .= "<select name=\"groupID[]\" size=\"5\">";
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
	$html .= "<br>";	$html .= "<label>";	$html .= "Days:";	$html .= "</label>";	$html .= "<select name=\"days\">";	$html .= '<option value="365"' . ( $_POST['days'] == 365 ? ' selected="selected"' : '' ) . '>365 Days</option>';	$html .= '<option value="30"' . ( $_POST['days'] == 30 ? ' selected="selected"' : '' ) . '>30 Days</option>';	$html .= "</select>";	
	$html .= "<br/>";	$html .= "<br/>";
	$html .= "<label></label>";
	if($_POST['userID'] == 0){
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['create']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
		$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}else{		
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['submit']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
		$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
		$html .= "<input type=\"button\" value=\"Go Manage Users\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"javascript:location.href='".sess_url($cfg['site']['folder']."admin/manage_users.php")."'\" >";
	}


	$html .= "<br>";
	$html .= "<br>";
	$html .= "</fieldset>";
	$html .= "</form>";
	$html .= "</div>";
	
	//js
	if($_POST['userID'] != 0){
	$html .= "
	<script type=\"text/javascript\">
		Calendar.setup(
			{
			inputField: \"cre_date\",
			ifFormat: \"%b %e %Y %k:%M:%S\",//M j Y H:i:s
			showsTime: true,
			button: \"cre_dateTrigger\"
			}
		);
		
		Calendar.setup(
			{
			inputField: \"last_date\",
			ifFormat: \"%b %e %Y %k:%M:%S\",//M j Y H:i:s
			showsTime: true,
			button: \"last_dateTrigger\"
			}
		);
		
	</script>";
	}
	
	return $html;
	// load custom forms
	if($_POST['userID'] != 0){
		$result = new umResult();
		$tempForm = new umForm();
		$result = $tempForm->search_forms(NULL);
	
		for($i = 0; $i < count($result->list); $i++){
			$form = new umForm();
			$form = $result->list[$i];
			if($form->formType == 0){
				$form->load_form($_POST['userID']);
				$html .= "<div class=\"formDiv\">";
				$html .= "<div class=\"formLayer\">";
				$html .= "<fieldset>";
				$html .= "<legend>".htmlspecialchars($form->formTitle)."</legend>";
				$html .= "<br>";
				for($j = 0; $j < count($form->fields); $j++){
					$field = new umField();
					$field = $form->fields[$j];
					$html .= "<label>";
					$html .= $field->fieldName.$lang['field']['CM'];
					$html .= "</label>";
					if($field->fieldType == 0 || $field->fieldType == 1 || $field->fieldType == 5 || $field->fieldType == 6){
						$html .= htmlspecialchars($field->value);	
					}
					if($field->fieldType == 2){
						$optionValues = explode(",", $field->value);
						$display = "";
						for($k = 0; $k < count($field->fieldOptions); $k++){
							for($l = 0; $l < count($optionValues); $l++){
								if($optionValues[$l] == $field->fieldOptions[$k]->optionID){
									if($display != "") $display .= ", ";
									$display .= $field->fieldOptions[$k]->caption;
								}
							}
						}
						$html .= $display;
					}
					if($field->fieldType == 3 || $field->fieldType == 4){
						$display = "";
						for($k = 0; $k < count($field->fieldOptions); $k++){
							if($field->value == $field->fieldOptions[$k]->optionID){
								$display = $field->fieldOptions[$k]->caption;
							}
						}
						$html .= $display;
					}
					if($field->fieldType == 7){
						$lines = explode("\n", $field->value);
						for($k = 0; $k < count($lines); $k++){
							if($k != 0) $html .= "<br>\n<label></label>";
							$html .= $lines[$k];
						}
					}
					$html .= "&nbsp;<br>";
				}
				$html .= "<br>";
				$html .= "</fieldset>";
				$html .= "</div>";
				$html .= "</div>";
			}
		}
	}
	
	return $html;	
}

function init_post_value($userID){
	global $cfg;
	
	$return = true;
	$user = new umUser();
	$user->userID = $userID;
	if($user->userID != 0){
		// try to load groups
		if(!$user->get_user(true)){
			$return = false;
		}
		$_POST['sendCode'] = 0;
	}else{
		$query['defaultGroup'] = 1;
		$result = new umResult();
		$tempGroup = new umGroup();
		$result = $tempGroup->search_groups($query);
		$user->belongToGroups = $result->list;
		$_POST['sendCode'] = $cfg['site']['requireVerification'];
		$user->status = $cfg['site']['autoEnable'];
	}
	$_POST['userID'] = $user->userID;
	$_POST['email'] = $user->email;
	$_POST['emailAddress'] = $user->emailAddress;
	$_POST['emailVerified'] = $user->emailVerified;
	$_POST['createTime'] = $user->createTime;
	$_POST['lastLoginTime'] = $user->lastLoginTime;
	$_POST['lastLoginIP'] = $user->lastLoginIP;
	$_POST['loginCount'] = $user->loginCount;
	$_POST['status'] = $user->status;
	$_POST['memo'] = $user->memo;		$_POST['days'] = $user->days;
	$_POST['groupID'] = array();
	for($i = 0; $i < count($user->belongToGroups); $i++){
		$_POST['groupID'][] = $user->belongToGroups[$i]->groupID;
	}
	return $return;
}

function trim_post_value(){
	$_POST['email'] = trim($_POST['email']);
	if(!isset($_POST['sendCode'])) $_POST['sendCode'] = 0;
	$_POST['password'] = trim($_POST['password']);
	$_POST['password2'] = trim($_POST['password2']);
	$_POST['memo'] = trim($_POST['memo']);
	if(!isset($_POST['groupID'])) $_POST['groupID'] = array();
}

function validate_post_value($userID){
	global $lang;
	$errorMessage = "";
	
	if(strlen($_POST['emailAddress']) < EMAIL_ADDRESS_LENGTH_MIN || strlen($_POST['emailAddress']) > EMAIL_ADDRESS_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['emailInvalidLength'], EMAIL_ADDRESS_LENGTH_MIN, EMAIL_ADDRESS_LENGTH_MAX)."</li>";;
	}
	if(!preg_match(EMAIL_FORMAT, $_POST['emailAddress'])){
		$errorMessage .= "<li>".$lang['error']['emailInvalidFormat']."</li>";
	}
	$existingUser = new umUser();
	if($existingUser->isExistUserName($_POST['email'], $_POST['userID'])){
		$errorMessage .= "<li>User Name already exists.</li>";
	}
	if($existingUser->isExistEmail($_POST['emailAddress'], $_POST['userID'])){
		$errorMessage .= "<li>Email already exists.</li>";
	}
	if(strlen($_POST['memo']) > MEMO_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['memoInvalidLength'], MEMO_LENGTH_MAX)."</li>";;
	}
	if($userID == 0 || $_POST['password'] != ''){
		if(strlen($_POST['password']) < PASSWORD_LENGTH_MIN || strlen($_POST['password']) > PASSWORD_LENGTH_MAX){
			$errorMessage .= "<li>".sprintf($lang['error']['passwordInvalidLength'], PASSWORD_LENGTH_MIN, PASSWORD_LENGTH_MAX)."</li>";;
		}
		if($_POST['password'] != $_POST['password2']){
			$errorMessage .= "<li>".$lang['error']['passwordsDonotMatch']."</li>";
		}
	}
	
	return $errorMessage;
}
?>