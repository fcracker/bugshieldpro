<?php
function setProtocol($protocol = "https") {
	global $cfg;
	if ($protocol == "https" && $_SERVER["HTTPS"] != "on") {
		$page = preg_replace("#^http\:#", "https:", CURRENT_URL);
		header("location: $page");
	}
	else ;
	
	if ($protocol == "http" && $_SERVER["HTTPS"] == "on") {
		$page = preg_replace("#^https\:#", "http:", CURRENT_URL);
		header("location: $page");
	}

	if ($protocol == "https") {
		$cfg['site']['url'] = preg_replace("#^http\:#", "https:", $cfg['site']['url']);
	}
	else {
		$cfg['site']['url'] = preg_replace("#^http\:#", "https:", $cfg['site']['url']);
	}
}

function setPageStatus($days, $alt) {
	setcookie("days", $days, time() + 3600 * 24 * 365 * 3, '/', '');
	setcookie("alt", $alt, time() + 3600 * 24 * 365 * 3, '/', '');
	if ((!isset($_COOKIE["days"]) || (isset($_COOKIE["days"]) && $_COOKIE["days"] != $days)) || (!isset($_COOKIE["alt"]) || (isset($_COOKIE["alt"]) && $_COOKIE["alt"] != $alt))) {
		header("location: " . CURRENT_URL);
	}
}

