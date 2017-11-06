<?php

require_once dirname(__FILE__).'/../../vendor/autoload.php';
  
use GeoIp2\Database\Reader;

function get_part($part) {
    if (file_exists(dirname(__FILE__) . "/" . $part . ".php")) {
        include_once(dirname(__FILE__) . "/" . $part . ".php");
    }
}

function get_tomorrow($format) {

    $tomorrow = mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"));

    return date($format, $tomorrow);
}

function get_date_shifted($format, $shift_days = 0, $other_shifts = array()) {
    $shift_hours = isset($other_shifts['hours']) ? $other_shifts['hours'] : 0;
    $shift_minutes = isset($other_shifts['minutes']) ? $other_shifts['minutes'] : 0;

    $timestamp = mktime(date("H") + $shift_hours, date("i") + $shift_minutes, 0, date("m"), date("d") + $shift_days, date("Y"));

    return date($format, $timestamp);
}

function _do_states_redirect($states_overwrite=null) {   

    $ip = getRealIpAddr();
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
        'WY','CO',
    );
    
    if($states_overwrite) {
        $special_states = $states_overwrite;
    }

    #define('SPECIAL_STATE','1');


    if(MY_COUNTRY=='US' && in_array(MY_STATE, $special_states)) {
        define('SPECIAL_STATE','1');
            header("Location:index_p1.php");
            exit;        
    }
    
}

function get_geo_data($check_redirect = true) {

    require_once("geoipcity.inc");
    require_once("geoipregionvars.php");

    global $GEOIP_REGION_NAME;

    $gi = geoip_open(dirname(__FILE__) . "/../../geo/GeoIPCity.dat", GEOIP_STANDARD);
    $ip = getRealIpAddr();

    $record = geoip_record_by_addr($gi, $ip);

//get ISP as well
    $giisp = geoip_open(dirname(__FILE__) . "/../../geo/GeoIPISP.dat", GEOIP_STANDARD);

    $isp = geoip_org_by_addr($giisp, $ip);

    if (!empty($record)) {
        $x = array(
            "city" => $record->city,
            "country" => $record->country_name,
            "isp" => $isp,
            "ip" => $ip,
            //"state"=>$GEOIP_REGION_NAME["".$record->country_code]["".$record->region]
        );

        geoip_close($gi);
        if ($check_redirect) {
            check_redirect_conditions($x);
        }
    } else
    {
        $x = array(
            "city" => "Our City",
            "country" => "Our Country",
            "isp" => "Unknown ISP",
            "ip" => $ip,
            //"state"=>$GEOIP_REGION_NAME["".$record->country_code]["".$record->region]
        );
    }
    return $x;
}

function check_redirect_conditions($data) {

    $isp = isset($data["isp"]) ? $data["isp"] : "";
    //AOL check
    if (strlen($isp)) {

        if ((stripos($isp, "america online") !== false)) {

            $url_add = array();
            $path = isset($_GET["p"]) ? intval($_GET["p"]) : 1;
            $subid = isset($_GET['subid']) ? $_GET['subid'] : 0;
            if ($path != 1) {
                $url_add[] = "xp=" . $path;
            }
            if ($subid != 0) {
                $url_add[] = "subid=" . $subid;
            }

            $location_add = count($url_add) ? "?" . implode("&", $url_add) : "";

            //redirect
            header("Location: " . $_SERVER['HTTP_HOST'] . "/monsters-inside-your-bed/" . $location_add);
            die();
        }
    }
}

function getRealIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {   //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function db_connect() {
    mysql_connect('localhost', 'betterho_ho', 'F1xT5cyV1D@v');
    mysql_select_db('betterho_ho');
}

function get_target_location($path = 1, $use_na = true) {

    $subid = isset($_GET['subid']) ? $_GET['subid'] : 0;
    $cid = isset($_GET['a_cid']) ? $_GET['a_cid'] : 0; //campaign id
    //hassoffer stuff
    $offid = isset($_GET['offer_id']) ? $_GET['offer_id'] : 0; //offer id
    $affid = isset($_GET['aff_id']) ? $_GET['aff_id'] : 0; //affiliate id  


    $location = "index_p" . $path . ($use_na ? "na" : "") . ".php";

    $query = array();

    if ($subid !== 0) {
        $query["subid"] = $subid;
    }

    if ($cid !== 0) {
        $query["a_cid"] = $cid;
    }

    if ($offid !== 0) {
        $query["offer_id"] = $offid;
    }
    if ($affid !== 0) {
        $query["aff_id"] = $affid;
    }

    if (count($query)) {
        $location.="?" . http_build_query($query);
    }

    return array("target" => $location, "hash" => base64_encode($location));
}

function get_landing_location($path = 1, $use_na = false) {

    $subid = isset($_GET['subid']) ? $_GET['subid'] : 0;
    $cid = isset($_GET['a_cid']) ? $_GET['a_cid'] : 0; //campaign id
    //hassoffer stuff
    $offid = isset($_GET['offer_id']) ? $_GET['offer_id'] : 0; //offer id
    $affid = isset($_GET['aff_id']) ? $_GET['aff_id'] : 0; //affiliate id  


    $location = "landingpage_p" . $path . ($use_na ? "na" : "") . ".php";

    $query = array();

    if ($subid !== 0) {
        $query["subid"] = $subid;
    }

    if ($cid !== 0) {
        $query["a_cid"] = $cid;
    }

    if ($offid !== 0) {
        $query["offer_id"] = $offid;
    }
    if ($affid !== 0) {
        $query["aff_id"] = $affid;
    }

    if (count($query)) {
        $location.="?" . http_build_query($query);
    }

    return array("target" => $location, "hash" => base64_encode($location));
}

function get_mobile_target_location() {
	$subid = isset($_GET['subid']) ? $_GET['subid'] : 0;
    $cid = isset($_GET['a_cid']) ? $_GET['a_cid'] : 0; //campaign id
    //hassoffer stuff
    $offid = isset($_GET['offer_id']) ? $_GET['offer_id'] : 0; //offer id
    $affid = isset($_GET['aff_id']) ? $_GET['aff_id'] : 0; //affiliate id  


    $location = "mobile_index.php";

    $query = array();

    if ($subid !== 0) {
        $query["subid"] = $subid;
    }

    if ($cid !== 0) {
        $query["a_cid"] = $cid;
    }

    if ($offid !== 0) {
        $query["offer_id"] = $offid;
    }
    if ($affid !== 0) {
        $query["aff_id"] = $affid;
    }

    if (count($query)) {
        $location.="?" . http_build_query($query);
	}
    
	return array("target" => $location, "hash" => base64_encode($location));
	
}

function get_specified_target($target) {
	$subid = isset($_GET['subid']) ? $_GET['subid'] : 0;
    $cid = isset($_GET['a_cid']) ? $_GET['a_cid'] : 0; //campaign id
    //hassoffer stuff
    $offid = isset($_GET['offer_id']) ? $_GET['offer_id'] : 0; //offer id
    $affid = isset($_GET['aff_id']) ? $_GET['aff_id'] : 0; //affiliate id  


    $location = $target;

    $query = array();

    if ($subid !== 0) {
        $query["subid"] = $subid;
    }

    if ($cid !== 0) {
        $query["a_cid"] = $cid;
    }

    if ($offid !== 0) {
        $query["offer_id"] = $offid;
    }
    if ($affid !== 0) {
        $query["aff_id"] = $affid;
    }

    if (count($query)) {
        $location.="?" . http_build_query($query);
	}
	
	
    
	return array("target" => $location, "hash" => base64_encode($location));
	
}
