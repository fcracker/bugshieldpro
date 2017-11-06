<?php
define('ADMIN_LOGIN', 1);
include_once ("./lib/config.inc.php");

global $cfg;

if (HOST_PROTOCOL != 'https' && $cfg['runconfig'] != 'dev') {
	header('Location:' . $cfg['site_mobile']['url_ssl'] . '/login1.php');
	exit;
}

include_once ("./pagestatekeeper.php");
include_once ("./lib/user.class.php");
include_once ("./lib/database.inc.php");
include_once ("./lib/mcrypt.class.php");
include_once ("./lib/forum.cookie.inc.php");
include_once ("./lib/menu.class.php");
include_once ("./languages/" . $cfg['language'] . ".php");
include_once ("./lib/pagerenderer.class.php");
include_once ("./lib/templatemanager.class.php");

// include_once("./web_form_bronze.php");

$con = connect_database();
$tm = new TemplateManager();
$tmpl = $tm->getActivatedTemplate();
$pr = new PageRenderer($tmpl["name"]);
$sessionUser = new umUser();
$userLoggedIn = FALSE;

if ($sessionUser->get_session()) {
	$userLoggedIn = TRUE;
}
else
if (isset($_POST['email'])) {
	trim_post_value();
	$errorMessage = "";
	$sessionUser->email = $_POST['email'];
	if ($sessionUser->get_user(true)) {
		if ($sessionUser->status == 1) {
			if ($sessionUser->emailVerified == 0 && $cfg['site']['requireVerification']) {
				$errorMessage = "<li>Your Name has not been verified. Please contact the administrator.</li>";
			}
			else {
				if ($sessionUser->password != md5($_POST['password'])) {
					$errorMessage = "<li>Password is incorrect.</li>";
				}
			}
		}
		else {
			$errorMessage = "<li>" . $lang['error']['accountDisabled'] . "</li>";
		}
	}
	else {
		$errorMessage = "<li>Invalid User Name</li>";
	}

	if ($errorMessage == "") {
		$userLoggedIn = true;
		setcookie('good_login', 'OK', time() + 10);
	}
	else {
		$pr->setContent(build_form($lang['formTitle']['login'], $errorMessage));
	}
}
else {
	init_post_value();
	$errorMessage = "";
}

if ($userLoggedIn) {
	$sessionUser->get_user(TRUE);

	// update the cookie that send him to the logged in home

	$sessionUser->set_client_exists_cookie();
	if (isset($_REQUEST["goto"])) $goto = base64_decode($_REQUEST["goto"]);
	else
	if (isset($_REQUEST["url"])) $goto = $_REQUEST["url"];
	else {
		$goto = '';
	}

	// check if this is the first login and this is a echeck or invoice billing client

	if ($sessionUser->loginCount == 0) {
		if (strlen($sessionUser->bank_account_number) || strlen($sessionUser->ssn)) {

			// the conditions are met

			$goto = "specialoffer_login.php";
		}
	}

	if ($goto == "") {
		$goto = $cfg['site']['folder'];
		$goto = $sessionUser->run_after_login();
	}

	$goto = sess_url($goto);
	supersession("days", $sessionUser->days, time() + 3600 * 24 * 365, '/');
	$dateDiff = 9999;
	if (stristr($goto, "specialoffer") == FALSE) {
		$dateDiff = $sessionUser->checkExpirationDate();
	}

	if ($dateDiff <= 0) {
		$pr->setContent(build_expiration_message($dateDiff, $goto));
	}
	else {
		$sessionUser->set_session(time() + 3600 * 24 * 365);

		// set cookie for forum

		$forum_sessionUser = new Forum_CookieIdentity();
		$forum_sessionUser->SetIdentity($sessionUser->userID);

		// update login info

		$sessionUser->lastLoginTime = date("Y-m-d H:i:s");
		$sessionUser->lastLoginIP = getenv("REMOTE_ADDR");
		$sessionUser->loginCount = $sessionUser->loginCount + 1;
		$sessionUser->update_user();
		if ($dateDiff <= 3) {
			$pr->setContent(build_expiration_message($dateDiff, $goto));
		}
		else {

			// $pr->setContent('<script type="text/javascript">location.replace("' . $goto . '");</script>');

			redirect($goto);
		}
	}
}
else {
	$pr->setContent(build_form($lang['formTitle']['login'], $errorMessage));
}

$pr->setTitle($lang['title']['login']);
$pr->setMenu();
$pr->setHead('
	<style type="text/css">
		.formDiv { float: none; display: block; clear: both; padding-top: 60px; margin-left: auto; margin-right: auto; width: 400px;}
		.formDiv a {color: #0076b4 }
		.formDiv form fieldset { border: 2px solid #c2c2c2; padding: 10px; -moz-border-radius: 10px; border-radius: 10px; -webkit-border-radius: 10px; }
		.formDiv form legend { font-size: 12pt; font-weight: bold; margin-left: 10px; padding-left: 3px; padding-right: 3px; }
		#errorMessage { list-style-position: inside; padding: 15px; background-color: #ffe9eb; color: #c00; margin: 0; margin-bottom: 20px; -moz-border-radius: 3px; border-radius: 3px; -webkit-border-radius: 3px; line-height: 1.5; }
		.formDiv form p { margin-bottom: 5px; float: left; clear: both; width: 100%; }
		.formDiv form p.navs { text-align: center; padding-top: 10px; }
		.formDiv form label { line-height: 24px; text-align: right; padding-right: 10px; width: 115px; float: left; }
		.txt { padding-left: 5px; border: 1px solid #ccc; width: 200px; height: 20px; line-height: 20px; -moz-border-radius: 5px; border-radius: 5px; -webkit-border-radius: 5px;}
		.txt:hover, .txt:focus { border-color: #5ea6d8; background-color: #f9f9f9; }
		.message { padding: 0; margin: 0; line-height: 2; margin-left: 20px; }
		.btn, input[type=button], input[type=submit], input[type=reset] {
			zoom: 1; /* zoom and *display = ie7 hack for display:inline-block */
			margin: 0 2px;
			outline: none;
			cursor: pointer;
			text-align: center;
			text-decoration: none;
			font: 14px/100% Arial, Helvetica, sans-serif;
			padding: .2em 1.5em .3em;
			text-shadow: 0 1px 1px rgba(0,0,0,.3);
			-webkit-border-radius: .5em;
			-moz-border-radius: .5em;
			border-radius: .5em;
			-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.2);
			-moz-box-shadow: 0 1px 2px rgba(0,0,0,.2);
			box-shadow: 0 1px 2px rgba(0,0,0,.2);
		}
		input[type=button]:hover, input[type=submit]:hover, input[type=reset]:hover {
			text-decoration: none;
		}
		input[type=button]:active, input[type=submit]:active, input[type=reset]:active {
			position: relative;
			top: 1px;
		}

		input[type=button], input[type=submit], input[type=reset] {
			color: #606060;
			border: solid 1px #b7b7b7;
			background: #fff;
			background: -webkit-gradient(linear, left top, left bottom, from(#fff), to(#ededed));
			background: -moz-linear-gradient(top,  #fff,  #ededed);
			filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr="#ffffff", endColorstr="#ededed");
		}
		input[type=button]:hover, input[type=submit]:hover, input[type=reset]:hover {
			background: #ededed;
			background: -webkit-gradient(linear, left top, left bottom, from(#fff), to(#dcdcdc));
			background: -moz-linear-gradient(top,  #fff,  #dcdcdc);
			filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr="#ffffff", endColorstr="#dcdcdc");
		}
		input[type=button]:active, input[type=submit]:active, input[type=reset]:active {
			color: #999;
			background: -webkit-gradient(linear, left top, left bottom, from(#ededed), to(#fff));
			background: -moz-linear-gradient(top,  #ededed,  #fff);
			filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr="#ededed", endColorstr="#ffffff");
		}
	</style>
');
/*
* construct and print page
*/
$pr->render();
$pr->display();
close_database($con);
/*
* ============================================== page complete here ==============================================
* The following functions construct content for this page
*/
/*
* show message
*/

function show_message($messageText)
{
	$html = "";
	$html.= "<div style=\"margin: 20px; height: 300px\">";
	$html.= $messageText;
	$html.= "</div>";
	return $html;
}

/*
* build form of this page
*/

function build_form($formTitle, $errorMessage = "")
{
	global $lang;
	global $cfg;
	$html = "";
	$html.= "<div class=\"formDiv\">";
	$html.= "<form method=\"post\" class=\"formLayer\" action='login1.php'>";
	$html.= "<fieldset>";
	$html.= "<legend>Administration Panel Login Area</legend>";
	if ($errorMessage != "") {
		$html.= "<ul id=\"errorMessage\">" . $lang['text']['errorsFoundList'] . $errorMessage . "</ul>";
	}
	else {
		$html.= "<br />";
	}

	$html.= "<p><label>";
	$html.= "Username:";
	$html.= "</label>";
	$html.= "<input type=\"text\" class=\"txt\" name=\"email\" value=\"" . htmlspecialchars($_POST['email']) . "\" size=\"40\">";
	$html.= "</p><p>";
	$html.= "<label>";
	$html.= $lang['field']['password'];
	$html.= "</label>";
	$html.= "<input type=\"password\" class=\"txt\" name=\"password\" value=\"\" size=\"40\">";
	$html.= "</p><p class=\"navs\">";
	$html.= "<input type=\"submit\" class=\"button\" name=\"submitBtn\" value=\"" . $lang['buttonCaption']['login'] . "\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html.= " ";
	$html.= "<input type=\"reset\" class=\"button\" name=\"resetBtn\" value=\"" . $lang['buttonCaption']['reset'] . "\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html.= "</p>";
	$html.= "<input type=\"hidden\" name=\"url\" value=\"" . sess_url(htmlspecialchars($_POST['url'])) . "\">";
	$html.= "</fieldset>";
	$html.= "</form><br/>";
	$html.= "<p class=\"message\">" . sprintf($lang['text']['signUpAccount'], $cfg['site']['folder']) . "</p>";
	$html.= "<p class=\"message\">" . sprintf($lang['text']['forgotPassword'], $cfg['site']['folder']) . "</p>";
	if ($cfg['site']['requireVerification']) {
		$html.= "<p>" . sprintf($lang['text']['retrieveVerificationCode'], $cfg['site']['folder']) . "</p>";
	}

	$html.= "</div>";
	return $html;
}

function init_post_value()
{
	global $cfg;
	$_POST['email'] = "";
	$_POST['password'] = "";
	if (isset($_GET['url'])) $_POST['url'] = htmlspecialchars($_GET['url']);
	else $_POST['url'] = '';
}

function trim_post_value()
{
	$_POST['email'] = htmlspecialchars(trim($_POST['email']));
	$_POST['password'] = trim($_POST['password']);
	$_POST['url'] = htmlspecialchars(trim($_POST['url']));
}

function build_expiration_message($dateDiff = 0, $goto = "")
{
	global $sessionUser;
	global $cfg;
	$userid = $sessionUser->userID;
	$rootUrl = $cfg['site']['url'] . $cfg['site']['folder'];
	$msg = '<script type="text/javascript" src="' . $rootUrl . 'js/jquery-1.4.2.js"></script>';
	$msg.= '<form id="upgradeForm" method="post" action="' . $rootUrl . 'upgrade.php"><input type="hidden" name="userid" value="' . $userid . '"/></form>';
	$msg.= '<script type="text/javascript">
		$(function() {
			$("#btnUpgrade").click(function() {
				$("#upgradeForm").trigger("submit");
			});
		});
		</script>';
	if ($dateDiff <= 0) {
		$email = $sessionUser->emailAddress;
		$name = $sessionUser->firstname . " " . $sessionUser->lastname;
		$msg.= '<div style="display: none">' . getBronzeWebForm() . '</div>';
		$msg.= '<iframe name="downgrader" style="display: none;"></iframe>';
		$msg.= '<script type="text/javascript" src="' . $rootUrl . 'Scripts/payment.js"></script>';
		$msg.= '<script type="text/javascript">
					$(function() {
						$("#btnNothanks").click(function() {
							$.post("' . $cfg['site']['url'] . $cfg['site']['folder'] . 'request.processor.php?action=setusermembership", { user: "' . $userid . '", membership: "bronze" }, function(result) {
								document.getElementById("aweber_form_bronze").target = "downgrader";
								addAccountToAweber("' . $name . '", "' . $email . '", "bronze");
								setTimeout(function() {
									location.replace("' . $rootUrl . 'login.php");
								}, 2000);
							});
						});
					});
				</script>';
		$msg.= "Gold membership expired.<br/>Click EXTEND A MONTH to extend it";
	}
	else {
		$msg.= '<script type="text/javascript">
					$(function() {
						$("#btnNothanks").click(function() {
							location.replace("' . $goto . '");
						});
					});
				</script>';
		$msg.= "Your gold membership will last the next " . $dateDiff . " day";
		if ($dateDiff > 1) $msg.= "s";
		$msg.= ".";
	}

	$html = "";
	$html.= "<div class=\"formDiv\">";
	$html.= "<fieldset>";
	$html.= "<div id=\"errorMessage\">" . $msg . "</div>";
	$html.= "<div align=\"center\">";
	$html.= "<input type=\"button\" id=\"btnUpgrade\" value=\"EXTEND A MONTH NOW\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">&nbsp;&nbsp;&nbsp;";
	$html.= "<input type=\"button\" value=\"NO THANKS\" class=\"btn\" id=\"btnNothanks\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html.= "</div>";
	$html.= "</fieldset>";
	$html.= "</div>";
	return $html;
}

?>

