<?php
die();
include('../lib/config.inc.php');
include_once("../lib/page.class.php");
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
include_once("../lib/user.class.php");

$user = new umUser();
$user->get_session();
$url = $cfg['site']['folder'];
if(isset($_SERVER['HTTP_REFERER'])) $url = $_SERVER['HTTP_REFERER'];
$style = 'v';
if(isset($_GET['style'])) $style = $_GET['style'];
$separator = ' | ';
if(isset($_GET['separator'])) $separator = $_GET['separator'];
if($user->userID != 0){
	if($style == 'v'){
		$html = "<ul>";
		$html .= "<li><a href=\"".$cfg['site']['folder']."\">".$lang['menu']['myProfile']."</li></a>";
		$html .= "<li><a href=\"".$cfg['site']['folder']."change_email.php\">".$lang['menu']['changeEmail']."</a></li>";
		$html .= "<li><a href=\"".$cfg['site']['folder']."change_password.php\">".$lang['menu']['changePassword']."</a></li>";
		$html .= "<li><a href=\"".$cfg['site']['folder']."logout.php\">".$lang['menu']['logout']."</a></li>";
		$html .= "</ul>";
	}else{
		$html = "<a href=\"".$cfg['site']['folder']."\">".$lang['menu']['myProfile']."</a>";
		$html .= $separator;
		$html .= "<a href=\"".$cfg['site']['folder']."change_email.php\">".$lang['menu']['changeEmail']."</a>";
		$html .= $separator;
		$html .= "<a href=\"".$cfg['site']['folder']."change_password.php\">".$lang['menu']['changePassword']."</a>";
		$html .= $separator;
		$html .= "<a href=\"".$cfg['site']['folder']."logout.php\">".$lang['menu']['logout']."</a>";
	}
}else{
	if($style == 'v'){
		$html = "<form action=\"".$cfg['site']['folder']."login.php\" id=\"jsLoginForm\" method=\"post\">";
		$html .= $lang['field']['email']."<br />";
		$html .= "<input type=\"text\" name=\"email\" id=\"jsEmailInput\" /><br />";
		$html .= $lang['field']['password']."<br />";
		$html .= "<input type=\"password\" name=\"password\" id=\"jsPasswordInput\" /><br />";
		$html .= "<input type=\"submit\" value=\"Login\" id=\"jsSubmitInput\" /><br />";
		$html .= "<input type=\"hidden\" name=\"url\" value=\"".$url."\" />";
		$html .= "<a href=\"".$cfg['site']['folder']."\">".$lang['menu']['signUp']."</a>";
		$html .= "</form>";
	}else{
		$html = "<form action=\"".$cfg['site']['folder']."login.php\" id=\"jsLoginForm\" method=\"post\">";
		$html .= $lang['field']['email']." ";
		$html .= "<input type=\"text\" name=\"email\" id=\"jsEmailInput\" /> ";
		$html .= $lang['field']['password']." ";
		$html .= "<input type=\"password\" name=\"password\" id=\"jsPasswordInput\" /> ";
		$html .= "<input type=\"submit\" value=\"Login\" id=\"jsSubmitInput\" /> ";
		$html .= "<input type=\"hidden\" name=\"url\" value=\"".$url."\" />";
		$html .= "<a href=\"".$cfg['site']['folder']."\">".$lang['menu']['signUp']."</a>";
		$html .= "</form>";
	}
}
print "document.write('".$html."');";;
?>