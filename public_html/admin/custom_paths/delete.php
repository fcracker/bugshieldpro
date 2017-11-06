<?php
include_once("../../lib/config.inc.php");
include_once("../../lib/database.inc.php");
include_once("../../lib/page.class.php");
include_once("../../lib/menu.class.php");
include_once("../../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../../languages/".$cfg['language'].".php"); // load language file
$page->template = "../../templates/".$cfg['language']."/default.html"; // load template

include_once("../../lib/user.class.php");
include_once("../../lib/custom_paths.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$custom_path = new custom_path($cfg);

$path_id = isset($_GET['path_id']) ? intval($_GET['path_id']) : -1;

if($path_id > 0) {
	$custom_path->delete_path($path_id);
} 


	header("Location:paths.php?deleted=1");
	exit;
