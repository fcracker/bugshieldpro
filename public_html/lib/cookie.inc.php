<?php
function cookie_get($name) {
	global $cfg;
	$cipher = new Cipher();
	if(isset($_COOKIE[$cfg['site']['cookiePrefix'].$name]))
		return $cipher->decrypt($_COOKIE[$cfg['site']['cookiePrefix'].$name]);
	else
		return false;
}

function cookie_set($name, $val) {
	global $cfg;
	$cipher = new Cipher();
	setcookie($cfg['site']['cookiePrefix'].$name, $cipher->encrypt($val), null, "/", $cfg['site']['cookieDomain']);
}


function cookie_delete($name) {
	global $cfg;
	setcookie($cfg['site']['cookiePrefix'].$name,  "");
}

function cookie_truncate() {
	foreach($_COOKIE as $key=>$value)
		cookie_delete($key);
}

function cookie_exists($name) {
	global $cfg;
	return isset($_COOKIE[$cfg['site']['cookiePrefix'].$name]);
}

function cookie_all($keys = "") {
	global $cfg;
	
	$return = array();
	
	
	if (is_array($keys)) {
		foreach($_COOKIE as $key=>$value) {
			$key = preg_replace("#^".$cfg['site']['cookiePrefix']."#", "", $key);
			
			$flag = false;
			
			for($j = 0; $j < sizeof($keys); $j++) {
				if($key == $keys[$j]) {
					$flag = true;
					break;
				}
			}
			
			if(!$flag)
				$return[$key] = cookie_get($key);
		}
	} else if ($keys != "") {
		foreach($_COOKIE as $key=>$value) {
			$key = preg_replace("#^".$cfg['site']['cookiePrefix']."#", "", $key);
			
			if($key != $keys)
				$return[$key] = cookie_get($key);
		}
	} else {
		foreach($_COOKIE as $key=>$value) {
			$key = preg_replace("#^".$cfg['site']['cookiePrefix']."#", "", $key);
			
			$return[$key] = cookie_get($key);
		}
	}
	
	if (!sizeof($return)) return false;
	
	return $return;
}