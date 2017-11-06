<?php
/*
 * init web page
 */
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
include_once("../lib/xml2array.inc.php");
include_once("../lib/filesystem.class.php");
include_once("../lib/templatemanager.class.php");

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
	$page->blocks['title'] = "Template Manager";
	if($userID == 0){
	
	}else{
		//$page->blocks['title'] = $lang['title']['updateUser'];
	}
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/template_setting.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$tm = new TemplateManager();
	$error = "";
	
	if(isset($_POST["work"])) {
		switch ($_POST["work"]) {
			case 'upload':
				if(!empty($_FILES["template_file"])) { //Template Pack uploaded
					if($_FILES["template_file"]["type"] == "application/zip") {
						$zipfile = FileSystem::upload("template_file", $tm->tempRoot);
						if($zipfile) {
							$tm->add($tm->tempRoot . $zipfile);
							$error = $tm->getError();
						} else {
							$error = "Upload failed.";
						}
					} else {
						$error = "Uploaded package is not a zip file"; 
					}
				}
				break;
			case "uninstall":
				echo "uninstalling...";
				if(!$tm->uninstall($_POST["themename"])) {
					$error = $tm->getError();
				}
				break;
			case "activate":
				if(!$tm->activate($_POST["themename"])) {
					$error = $tm->getError();
				}
				break;
		}
	}
	
	$r = $tm->getTemplateList();
	while($row = mysql_fetch_assoc($r)){
		$data[] = $row;
	}
	$page->blocks['content'] = build_form($lang['formTitle']['setTemplate'], $data, $error);
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/template_setting.php");
}

/*
 * construct and print page
 */

$page->construct_page(); // construct html page
$page->output_page(); // output page

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
function build_form($formTitle, $data, $errorMessage){
	global $lang;
	global $cfg;
	global $data;
	
	$html = '<script type="text/javascript">
			 function doAction(act, themename) {
				document.forms[0].work.value =act;
				document.forms[0].themename.value = themename;
				document.forms[0].submit();
			 }
			 </script>
			 <style type="text/css">
			 .formDiv table {
				border-collapse: collapse;
				border:1px solid gray;
			 }
			 .formDiv table td, th { border: 1px solid gray; text-align: center; padding: 10px; }
			 </style>
			 ';
	$html .= '<link rel="stylesheet" href="' . $cfg['site']['folder'] . 'js/jquery/colorpicker/css/colorpicker.css" type="text/css" />';
	$html .= '<script src="' . $cfg['site']['folder'] . 'js/jquery/colorpicker/js/jquery.js" type="text/javascript"></script>';
	$html .= '<script src="' . $cfg['site']['folder'] . 'js/jquery/colorpicker/js/colorpicker.js" type="text/javascript"></script>';
	$html .= '<script src="' . $cfg['site']['folder'] . 'js/jquery/colorpicker/js/eye.js" type="text/javascript"></script>';
	$html .= '<script src="' . $cfg['site']['folder'] . 'js/jquery/colorpicker/js/utils.js" type="text/javascript"></script>';
	$html .= '<script src="' . $cfg['site']['folder'] . 'js/jquery/colorpicker/js/layout.js?ver=1.0.2" type="text/javascript"></script>';
	$html .= '<link rel="stylesheet" href="' . $cfg['site']['folder'] . 'js/jquery/css/ui-lightness/jquery-ui.css" type="text/css"/>';
	$html .= "<div class=\"formDiv\">";
	$html .= "<form id=\"mainform\" enctype=\"multipart/form-data\" method=\"post\" class=\"formLayer\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	
	$html .= '<div style="text-align: right;">Install a new template:<input type="file" name="template_file" /><input class="btn" value="Upload" type="button" onclick="doAction(\'upload\', \'\');"/></div>';
	$html .= '<div style="">';
	$html .= '<table width="100%">';
	$html .= '<tr><th>#</th><th>Small Preview</th><th>Template Name</th><th>Activated</th><th>Uninstall</th></tr>';
	for($i = 0; $i < sizeof($data); $i++) {
		$html .= "<tr>";
		$html .= "<td>" . ( $i + 1 ) . "</td>";
		$html .= "<td><img src=\"" . $cfg['site']['trainingAreaTemplateUrl'] . urlencode($data[$i]["name"]) . "/" . $data[$i]["thumbnail"] . "\"/></td>";
		$html .= "<td>" . $data[$i]["name"] . "</td>";
		$html .= "<td><input type=\"radio\" name=\"activated\" onclick=\"doAction('activate', &quot;" . $data[$i]["name"] . "&quot;);\" value=\"" . $data[$i]["name"] . "\"" . ($data[$i]["activated"] =='y' ? " checked=\"checked\"" : "") . "/></td>";
		$html .= "<td><a href=\"javascript: doAction('uninstall', &quot;" . $data[$i]["name"] . "&quot;);\">Uninstall</a></td>";
		$html .= "</tr>";
	}
	$html .= "</table>";
	$html .= "<input type='hidden' name='work' value=''/>";
	$html .= "<input type='hidden' name='themename' value=''/>";
	$html .= "</form></div>";
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
	$_POST['memo'] = $user->memo;
	$_POST['groupID'] = array();
	for($i = 0; $i < count($user->belongToGroups); $i++){
		$_POST['groupID'][] = $user->belongToGroups[$i]->groupID;
	}
	return $return;
}