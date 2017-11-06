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

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$newUser = new umUser();
	$newUser->userID = $_GET['userID'];
	$newUser->get_user(true);
	$newUser->set_session();
	
	// redirect url
	$nextScript = $newUser->run_after_login();
	if($nextScript == ''){
		redirect($cfg['site']['folder']);
	}else{
		redirect($nextScript."?url=".$cfg['site']['folder']);
	}
}else{
	redirect($cfg['site']['folder']."login.php");
}
?>