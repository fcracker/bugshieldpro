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
$userEmail = "";
if(isset($_GET['email'])) $userEmail = $_GET['email'];
if(isset($_POST['emailAddress'])) $userEmail = $_POST['emailAddress'];

$user = new umUser();
$user->get_session();
//$_GET['email']
$allowGroups = explode(",", $cfg['group']['adminTemplateAccessGroupIds']);
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "Manually Register";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manually_register.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(isset($_POST['email'])){
		trim_post_value();
		$errorMessage = "";
		$errorMessage = validate_post_value();
		if($errorMessage == ""){
			$utInfo = getTempUser($_POST['emailAddress']);;
			
			$objUser = new umUser();
			// create new user
			$objUser->emailAddress = $_POST['emailAddress'];
			$objUser->email = $_POST['email'];
			$objUser->emailVerified = 0;
			
			$objUser->verificationCode = $objUser->generate_verification_code();
			$objUser->password = md5($_POST['password']);
			$objUser->createTime = date("Y-m-d H:i:s");
			$objUser->status = 0;
			$objUser->memo = $_POST['memo'];
			$data["emailAddress"] = $objUser->emailAddress;
			$data["email"] = $objUser->email;
			$data["emailVerified"] = $objUser->emailVerified;
			$data["verificationCode"] = $objUser->verificationCode;
			$data["password"] = $objUser->password;
			$data["createTime"] = $objUser->createTime;
			$data["status"] = $objUser->status;
			$data["memo"] = $objUser->memo;


			$data["firstname"] = $utInfo['firstname'];
			$data["lastname"] = $utInfo['lastname'];
			$data["phone"] = $utInfo['phone'];
			$data["country"] = $utInfo['country'];
			$data["state"] = $utInfo['state'];
			$data["city"] = $utInfo['city'];
			$data["address"] = $utInfo['address'];
			$data["postalcode"] = $utInfo['postalcode'];
			$data["cardtype"] = $utInfo['cardtype'];
			$data["cardnumber"] = $objUser->Cipher->decrypt($utInfo['cardnumber']);
			$data["cardname"] = $objUser->Cipher->decrypt($utInfo['cardname']);
			$data["expiration"] = $utInfo['expiration'];
			$data["cvvcode"] = $objUser->Cipher->decrypt($utInfo['cvvcode']);
			$data["days"] = $utInfo['days'];
			if($objUser->create_user($data, $utInfo['groupID'])){
				$objUser->assign_groups(array($utInfo['groupID']));
				$sql = "DELETE FROM ".$cfg['database']['prefix']."user_temp WHERE Email='".$_POST['emailAddress']."'";
				mysql_query($sql);
				// send email
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
			
				redirect($cfg['site']['folder']."service/order.php");
			}else{
				$page->blocks['content'] = build_form("", "");
			}
		}else{
			$page->blocks['content'] = build_form($errorMessage);
		}
	}else{
		init_post_value($userEmail);
		$page->blocks['content'] = build_form();
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."service/manually_register.php?email=".$userEmail);
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
function build_form($errorMessage = "", $resultMessage = ""){
	global $lang;
	global $cfg;
	global $userEmail;

	$html = "";
	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> ";
		$html .= $resultMessage;
		$html .= "</div>";
	}
	$html .= "<div class=\"formDiv\">";
	$html .= "<form method=\"post\" onSubmit=\"return disablePage();\" class=\"formLayer\">";
	$html .= "<fieldset>";
	$html .= "<legend>Manually Register</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	
	$html .= "<label>";
	$html .= "User Name:";
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"email\" value=\"".htmlspecialchars($_POST['email'])."\" size=\"50\"><br>";
		
	$html .= "<label>";
	$html .= $lang['field']['email'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"emailAddress\" value=\"".htmlspecialchars($_POST['emailAddress'])."\" size=\"50\">";
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

	$html .= "<label>";
	$html .= "Notice:";
	$html .= "</label>";
	$html .= "<textarea name=\"memo\" cols=\"50\" rows=\"3\">".htmlspecialchars($_POST['memo'])."</textarea>";
	$html .= "<br>";
	
	$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"Register\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<input type=\"button\" value=\"Go Unregisterd Users\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"javascript:location.href='".sess_url($cfg['site']['folder']."service/unregisterd_users.php")."'\" >";

	$html .= "<br>";
	$html .= "<br>";
	$html .= "</fieldset>";
	$html .= "</form>";
	$html .= "</div>";
	return $html;
}

function init_post_value($userEmail){
	global $cfg;
	
	$_POST['email'] = "";
	$_POST['emailAddress'] = $userEmail;
	$_POST['memo'] = "";
}

function trim_post_value(){
	$_POST['email'] = trim($_POST['email']);
	$_POST['password'] = trim($_POST['password']);
	$_POST['password2'] = trim($_POST['password2']);
	$_POST['memo'] = trim($_POST['memo']);
}

function validate_post_value(){
	global $lang;
	$errorMessage = "";
	
	if(strlen($_POST['emailAddress']) < EMAIL_ADDRESS_LENGTH_MIN || strlen($_POST['emailAddress']) > EMAIL_ADDRESS_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['emailInvalidLength'], EMAIL_ADDRESS_LENGTH_MIN, EMAIL_ADDRESS_LENGTH_MAX)."</li>";;
	}
	if(!preg_match(EMAIL_FORMAT, $_POST['emailAddress'])){
		$errorMessage .= "<li>".$lang['error']['emailInvalidFormat']."</li>";
	}
	
	if(trim($_POST['email']) == ""){
		$errorMessage .= "<li>User Name does not empty.</li>";
	}
	/*
	$utInfo = getTempUser($_POST['emailAddress']);
	if(!count($utInfo)){
		$errorMessage .= "<li>Email unregister order information list.</li>";
	}
	*/
	$existingUser = new umUser();
	if($existingUser->isExistEmail($_POST['email'])){
		$errorMessage .= "<li>User Name already exists.</li>";
	}
	if($existingUser->isExistEmail($_POST['emailAddress'])){
		$errorMessage .= "<li>Email already exists.</li>";
	}
	if(strlen($_POST['memo']) > MEMO_LENGTH_MAX){
		$errorMessage .= "<li>".sprintf($lang['error']['memoInvalidLength'], MEMO_LENGTH_MAX)."</li>";;
	}
	return $errorMessage;
}

function getTempUser($userEmail){
	global $cfg;
	static $userTempData = array();
	if(isset($userTempData[$userEmail])) return $userTempData[$userEmail];
	$sql = "SELECT UT.* , MH.`hAmount`
			FROM ".$cfg['database']['prefix']."user_temp AS UT
				INNER JOIN ".$cfg['database']['prefix']."merchant_history AS MH
				ON UT.`Email`=MH.`user_email`
			WHERE UT.`Email`='".$userEmail."'
			";
	
	$row = single_query_assoc($sql);
	if(!count($row)){
		$userTempData[$userEmail] = $row;
	}else{
		$row['groupID'] = 0;
		if(in_array($row['hAmount'], explode(",", $cfg['prices']['bronze']))) 	$row['groupID'] = $cfg['group']['bronze'];
//		if(in_array($row['hAmount'], explode(",", $cfg['prices']['silver']))) 	$row['groupID'] = $cfg['group']['silver'];
		if(in_array($row['hAmount'], explode(",", $cfg['prices']['gold']))) 	$row['groupID'] = $cfg['group']['gold'];
		if($row['groupID']==0){
			$userTempData[$userEmail] = array();
		}else{
			$userTempData[$userEmail] = $row;
		}
	}
	return $userTempData[$userEmail];
}