<?php
/*
 * init web page
 */
ini_set("magic_quote_gpc", "on");
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/user.class.php");

$con = connect_database();
/*
 * create content blocks
 * page is built in this part
 */
$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(!empty($_POST))
	{
		$query = "delete from " . $cfg['database']['prefix'] . "template";
		mysql_query($query);
		
		$postAry = Array();
		foreach($_POST as $key => $value){
			//if($key=="width" && $value=="30000")$value="sssssssss";
			$postAry[] = "$key=\"$value\"";
		}
				
		$update_sql = "insert into " . $cfg['database']['prefix'] . "template set ". join(",",$postAry);
		mysql_query($update_sql);
	}
	else 
	{
		echo "Perform code for page without POST data. ";
	}
	
}

close_database($con);

?>