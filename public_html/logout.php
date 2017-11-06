<?php
include_once ("./lib/config.inc.php");
include_once ("./lib/database.inc.php");
include_once ("./lib/user.class.php");

$con = connect_database();
$sessionUser = new umUser;
$sessionUser->get_session();

// get user

$sessionUser->get_user(true);

// check the groups
// $no_logout_groups = array(1,2,3,4);

$no_logout_groups = array();
$logout = true;

foreach($sessionUser->belongToGroups as $grp) {
	if (in_array($grp->groupID, $no_logout_groups)) {
		$logout = false;
	}
}

if ($logout) {
	supersession_clear();
}

/*if($cfg['site']['cookieDomain'] != ''){
setcookie($cfg['site']['cookiePrefix']."auth_user", NULL, time(), "/", $cfg['site']['cookieDomain']);
setcookie($cfg['site']['cookiePrefix']."auth_groups", NULL, time(), "/", $cfg['site']['cookieDomain']);
}else{
setcookie($cfg['site']['cookiePrefix']."auth_user", NULL, time(), "/");
setcookie($cfg['site']['cookiePrefix']."auth_groups", NULL, time(), "/");
}

$_COOKIE[$cfg['site']['cookiePrefix']."auth_user"] = NULL;
$_COOKIE[$cfg['site']['cookiePrefix']."auth_groups"] = NULL;

// Remove Cookie for Vanilla Forum

setcookie("Vanilla", "", time(), "/");
setcookie("Vanilla-Volatile", "", time(), "/");
$_COOKIE["Vanilla"] = NULL;
$_COOKIE["Vanilla-Volatile"] = NULL;
*/

// redirect url

$nextScript = $sessionUser->run_after_logout();

if ($nextScript == '') {

	// redirect($cfg['site']['url']);//just go a 'you have been logged out page'
	// redirect($cfg['site']['folder']."login.php");

	redirect($cfg['site']['folder'] . "logged_out.html");
}
else {
	redirect($nextScript);
}

close_database($con);
