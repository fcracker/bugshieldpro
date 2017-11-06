<?php
	
//put any start-up stuff in here
session_start();

//check for subid
if(isset($_GET["subid"])) {
	supersession("bg_subid",base64_encode($_GET["subid"]));
}

//check for hasoffers codes
//?offer_id=2&aff_id=2
if(isset($_GET["offer_id"])) {
  supersession("hasoffers_offer_id",base64_encode($_GET["offer_id"]));
}

if(isset($_GET["aff_id"])) {
  supersession("hasoffers_aff_id",base64_encode($_GET["aff_id"]));
}

//check for email campaign id
if(isset($_GET["email_campaign"])) {
  supersession("email_campaign",base64_encode($_GET["email_campaign"]));
  
  if(isset($_GET["offer"])) {
    supersession("campaign_offer",base64_encode($_GET["offer"]));
  }
  
}

//add the referer url in the session (not cookie!)
if(session('referer') === false) {
  if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],'bedroomguardian.com')===FALSE) {
    session('referer',$_SERVER['HTTP_REFERER']);    
  }
}



require_once dirname(__FILE__).'/../../vendor/autoload.php';
  
use GeoIp2\Database\Reader;

if(!defined('RUNNING_REBILL') && !defined('ADMIN_LOGIN')) {

  $ip = getRealIpAddr_sess();
  $city_database_path = dirname(__FILE__).'/../../geo/db/GeoLite2-City.mmdb';
  $database_path = dirname(__FILE__).'/../../geo/db/GeoIP2-Country.mmdb';
  $reader = new Reader($database_path);
  $city_reader = new Reader($city_database_path);
  
  try {
  $record = $reader->country($ip);
  $city_record = $city_reader->city($ip);
  
  define('MY_COUNTRY',$record->country->isoCode);
  define('MY_STATE', $city_record->mostSpecificSubdivision->isoCode);
  
  } catch(Exception $e) {
      
    define('MY_COUNTRY','US');
    define('MY_STATE','NA');
    
  }
  
  // We need to set a few special things based on state
  $special_states = array(
      'WY','CO'
  );
  
  #define('SPECIAL_STATE','1');
  
  
  if(MY_COUNTRY=='US' && in_array(MY_STATE, $special_states)) {
      define('SPECIAL_STATE','1');
      /*
      if(!defined('BASE_PATH')) {
          header("Location:http://bedroomguardian.com/index_p1.php");
          exit;
      }
       * 
       */
      
  }
  
  # check the redirect
  if(!defined('BASE_PATH') && (defined('IS_INDEX')  /*|| defined('IS_MOBILE_INDEX') /*|| defined('IS_LANDINGPAGE')*/) && !defined('NO_REDIR')) {
	  check_redir();
  }
  
  if(defined('IS_LANDINGPAGE') && !defined('NO_REDIR')) {
      
      check_redir();
  }
  
  if(defined('IS_MOBILE_INDEX')) {
      check_redir();
  }

}



$authcookie = 'mem_auth_user';
$auth_user = supersession($authcookie);

if(MY_STATE != 'WY' || MY_COUNTRY != 'US') {

    if($auth_user == false && !defined('ADMIN_LOGIN') && defined('DO_MOBILE_REDIRECT')) {

            //check redirection to mobile
            include_once(dirname(__FILE__) . "/mobile_detect/Mobile_Detect.php");
            //device type
            $deviceType = "desktop";
            $detect = new Mobile_Detect;
            $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'desktop');

            if($deviceType=="phone"){

              if((supersession("ismobile")==false || defined('IS_INDEX')) && !defined('SPECIAL_STATE_CHECKOUT')) {
                     supersession("ismobile","phone");

                     $query_string =  $_SERVER['QUERY_STRING'];
                     
                     if(defined('DO_MOBILE_REDIRECT_AUTH')) {
                     
                      include_once(dirname(__FILE__).'/../inc/func.php');
                      
                      $_target = get_mobile_target_location();                      
                      $target = "redir.php?hash=".$_target["hash"];                      
                      header("Location:".$target);
                     } else {

                     header("Location:http://bedroomguardian.com/mobile_index.php".(strlen($query_string) ? "?".$query_string:""));
                     
                     }
                     exit;

              } 

            } else {
              //make sure we remove the mobile flag
              supersession("ismobile",null);
            }

    }

}





//echo "<!-- country: ".MY_COUNTRY." -->";

function invalidSig() {
	header("Location:http://a357.com/redir");
	exit();
}

function check_redir() {
	return;
	// signature expiry in seconds, ie 3600=1hr
	define('EXPIRE_SECS', 259200);

	// encryption key, change this value, make sure its the same in both files
	define('SEC', '23fk9kjfhgas');
	define('TOKEN','sig');

	// check signature
	$sig = array();
	$sig['raw'] = (!empty($_GET[TOKEN]) ? $_GET[TOKEN] : invalidSig());

	$sig['parts'] = explode('.', $sig['raw']);
	if (count($sig['parts']) != 2) invalidSig();

	$sig['utc'] = base64_decode(strtr($sig['parts'][0], '-_', '+/'));
	if ($sig['utc'] === false || !is_numeric($sig['utc'])) invalidSig();
	if (time() >= $sig['utc']+EXPIRE_SECS) invalidSig();

	$sig['hash'] = base64_decode(strtr($sig['parts'][1], '-_', '+/'));
	if ($sig['hash'] === false) invalidSig();

	if (sha1($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].$sig['utc'].SEC, true) != $sig['hash'])
		invalidSig();

	unset($sig);
}

function getRealIpAddr_sess(){
	    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
	    {
	      $ip=$_SERVER['HTTP_CLIENT_IP'];
	    }
	    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
	    {
	      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	    }
	    else
	    {
	      $ip=$_SERVER['REMOTE_ADDR'];
	    }
      
      if(strpos($ip,",")) {
        $ip_parts = explode(",",$ip);
        $ip = array_pop($ip_parts);
      }
      
	    return $ip;
	}
  
  


function session($name = false, $value = false) {
	if ($name == false) {
		return $_SESSION;	
	}
	
	if ($value == false) {
		if (!isset($_SESSION[$name])) return false;
		return $_SESSION[$name];
	} else if ($value == null) {
		unset($_SESSION[$name]);
	} else {
		$_SESSION[$name] = $value;
	}
}

function cookie($name = false, $value = false, $expire = null, $domain = '/', $secure = '') {
	if ($name === false) {
		return $_COOKIE;	
	}

	if ($value === false) {
		if (!isset($_COOKIE[$name])) return false;
		else return $_COOKIE[$name];	
	} else if ($value == null) {
		setcookie($name, NULL, time(), "/", $secure);
		unset($_COOKIE[$name]);
	} else {
		$_COOKIE[$name] = $value;
		setcookie($name, $value, $expire, $domain, $secure);
	}
}

function session_clear() {
	foreach (session() as $key=>$value) {
		session($key, null);	
	}
	@session_destroy();
}

function cookie_clear() {
	foreach (cookie() as $key=>$value) {
		cookie($key, null);
	}
}

function supersession($name = false, $value = false, $expire = 0, $domain = '/', $secure = '') {
    
        if(is_array($value)) {
            $value = $value[0];
        }
        
	if (cookie_is_blocked()) {
		return session($name, $value);
	} else {
		return cookie($name, $value, $expire, $domain, $secure);
	}
}

function supersession_clear() {
	if (cookie_is_blocked()) {
		session_clear();
	} else {
		cookie_clear();
	}
}

function cookie_is_blocked() {
	return session('cookie_is_blocked') !== false;
}

function redirect($url) {
	header('location:' . sess_url($url));
	exit(0);
}

function sess_url($url) {
	if (cookie_is_blocked()){
		$sessid = 'PHPSESSID=' . session_id();
		if(strstr($url, 'PHPSESSID=')===false) {
			$t_url = explode('#', $url);
			if (strstr($url, '?') !== false) {
				$url = $t_url[0] . '&' . $sessid;
			} else {
				$url = $t_url[0] . '?' . $sessid;
			}
			if(count($t_url)>1) $url .= "#".$t_url[1];
		}else{
			$sessid = '?' . $sessid;
			$t_url = explode($sessid, $url);
			if(count($t_url)>1) $url = $t_url[0].$sessid.$t_url[1];
		}
	}
	
	return $url;
}

function session_key()
{

	if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $key = substr(preg_replace('/[^0-9A-Za-z\-]/is', '', base64_encode($ip) . base64_encode($userAgent)), 0, 64);
    
    return $key;
}