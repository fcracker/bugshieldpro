<?php
/*
 * init web page
 */
die();
include_once("../lib/config.inc.php");
$configValues = $cfg; // load $cfg and store it asisde for modification
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
$page->template = "../templates/".$cfg['language']."/default.html"; // load template

include_once("../lib/user.class.php");

$con = connect_database();
/*
 * create content blocks
 * page is built in this part
 */
$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['config'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/sysconfig.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(isset($_POST['cfg_site_name'])){
		trim_post_value();
		init_post_value($configValues);
		if(build_cfg_file($_POST)){
			$page->blocks['content'] = build_form($lang['formTitle']['config'], $lang['text']['updateConfigFileSuccessfully']);
		}else{
			$page->blocks['content'] = build_form($lang['formTitle']['config'], $lang['text']['updateConfigFileFailed']);
		}
	}else{
		init_post_value($configValues);
		$page->blocks['content'] = build_form($lang['formTitle']['config']);
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/sysconfig.php");
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
function build_form($formTitle, $resultMessage = ""){
	global $lang;
	global $cfg;
	$html = "";
	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> ";
		$html .= $resultMessage;
		$html .= "</div>";
	}
	$html .= "<div class=\"formDiv\">";
	$html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/sysconfig.php")."\" method=\"post\" onSubmit=\"return disablePage();\" class=\"formLayer\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['websiteName'];
	$html .= "</label>";
	$html .= "<input type=\"text\" name=\"cfg_site_name\" value=\"".htmlspecialchars($_POST['cfg_site_name'])."\" size=\"50\">";
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['autoEnableUsers'];
	$html .= "</label>";
	if($_POST['cfg_site_autoEnable']){
		$html .= "<input type=\"radio\" name=\"cfg_site_autoEnable\" value=\"1\" class=\"radio\" checked> ".$lang['text']['enabled'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"cfg_site_autoEnable\" value=\"0\" class=\"radio\"> ".$lang['text']['disabled'];
	}else{
		$html .= "<input type=\"radio\" name=\"cfg_site_autoEnable\" value=\"1\" class=\"radio\"> ".$lang['text']['enabled'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"cfg_site_autoEnable\" value=\"0\" class=\"radio\" checked> ".$lang['text']['disabled'];
	}
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['allowUsersSignUp'];
	$html .= "</label>";
	if($_POST['cfg_site_openSignUp']){
		$html .= "<input type=\"radio\" name=\"cfg_site_openSignUp\" value=\"1\" class=\"radio\" checked> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"cfg_site_openSignUp\" value=\"0\" class=\"radio\"> ".$lang['text']['no'];
	}else{
		$html .= "<input type=\"radio\" name=\"cfg_site_openSignUp\" value=\"1\" class=\"radio\"> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"cfg_site_openSignUp\" value=\"0\" class=\"radio\" checked> ".$lang['text']['no'];
	}
	$html .= "<br>";
	$html .= "<label>";
	$html .= $lang['field']['requireEmailVerification'];
	$html .= "</label>";
	if($_POST['cfg_site_requireVerification']){
		$html .= "<input type=\"radio\" name=\"cfg_site_requireVerification\" value=\"1\" class=\"radio\" checked> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"cfg_site_requireVerification\" value=\"0\" class=\"radio\"> ".$lang['text']['no'];
	}else{
		$html .= "<input type=\"radio\" name=\"cfg_site_requireVerification\" value=\"1\" class=\"radio\"> ".$lang['text']['yes'];
		$html .= " ";
		$html .= "<input type=\"radio\" name=\"cfg_site_requireVerification\" value=\"0\" class=\"radio\" checked> ".$lang['text']['no'];
	}
	$html .= "<br>";
	$html .= "<br>";
	$html .= "<label></label>";
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

function init_post_value($configValues){
	cfg_to_post($configValues, "cfg");
}

function trim_post_value(){
	$_POST['cfg_site_name'] = trim($_POST['cfg_site_name']);
}

function cfg_to_post($value, $path){
	if(is_array($value)){
		foreach($value as $k => $v){
			cfg_to_post($v, $path."_".$k);
		}
	}else{
		if(!isset($_POST[$path])) $_POST[$path] = $value;
	}
}

function build_cfg_file($values){
	$return = false;

	$fileContent = "<?php\n";
	foreach($values as $k => $v){
		$keys = array();
		$keys = explode("_", $k);
		if(count($keys) > 1){
			$line = '$cfg';
			for($i = 0; $i < count($keys); $i++){
				if($i != 0) $line .= "['".$keys[$i]."']";
			}
			if(is_numeric($v)){
				$line .= " = ".$v.";\n";
			}else{
				$line .= " = '".$v."';\n";
			}
		
			$fileContent .= $line;
		}
	}
	$fileContent .= "?>\n";
	
	$cfgFile = "../lib/config.inc.php";
	if($fh = fopen($cfgFile, 'w')){
		fwrite($fh, $fileContent);
		fclose($fh);
		$return = true;
	}
	
	return $return;
}
?>