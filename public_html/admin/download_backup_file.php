<?php
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/user.class.php");
$con = connect_database();
$user = new umUser();
$user->get_session();
$allowGroups = $cfg['site']['adminGroupIDs'];
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$file = $cfg['database']['backupFolder']."/".$_GET['fileName'];
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$_GET['fileName'].'"');
	header("Content-Transfer-Encoding: binary\n");
	readfile($file);
}

close_database($con);
?>