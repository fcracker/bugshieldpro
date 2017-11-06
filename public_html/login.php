<?php
define('ADMIN_LOGIN', 1);

if (isset($_GET['redirected'])) {
	if (isset($_COOKIE['testcookie'])) {
		header('location: login1.php');
	}
	else {
		include_once 'lib/session.php';

		session_id(session_key());
		session('cookie_is_blocked', 1);
		redirect('login1.php');
	}
}
else {
	setcookie('testcookie', 'OK');
	header('location: login.php?redirected=1');
	exit(0);
}
