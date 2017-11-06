<?php
/*
 * init web page
 */
include_once("./lib/config.inc.php");
include_once("./lib/database.inc.php");
include_once("./lib/menu.class.php");
include_once("./lib/menu.block.php"); // load menu function
include_once("./languages/eng.php"); // load language file
include_once("./lib/user.class.php");
include_once("./lib/email.inc.php");
include_once("./lib/phpmailer/class.phpmailer.php");
include_once("./lib/phpmailer/class.smtp.php");
include_once("./lib/antispam.functions.php");
include_once("./lib/pagerenderer.class.php");
include_once("./lib/templatemanager.class.php");
$con = connect_database();
/*
 * create content blocks
 * page is built in this part
 */
$tm = new TemplateManager();
$tmpl = $tm->getActivatedTemplate();
$pr = new PageRenderer($tmpl["name"]);


$pr->setTitle($lang['title']['retrievePassword']);

//$page->blocks['selectLanguage'] = $page->build_language_form();

if(isset($_POST['email'])){
	trim_post_value();
	$errorMessage = "";
	$user = new umUser();
	$user->email = $_POST['email'];
	if($user->get_user(true)){
		if($user->status != 1){
			$errorMessage .= "<li>".$lang['error']['accountDisabled']."</li>";
		}
	}else{
		$errorMessage .= "<li>User name doesn't exist.</li>";
	}
	if(show_antispam_code()){
		if(md5(md5($_POST['aCode']).$cfg['site']['cookieToken']) != $_POST['encryptACode']){
			$errorMessage .= "<li>".$lang['error']['inputNumberIncorrect']."</li>";
		}
	}
	if($errorMessage == ""){
		$password = generat