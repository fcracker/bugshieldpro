<?php

include_once("antifraud.redflag.class.php");

class antifraud {

    public $tbl = "antifraud";
    public $order = null;
    public $user = null;
    public $config = null;
    public $possible_avs_keys = array(
        "avsresponse",
        "AVSCODE",
        "AVSResponseCode"
    );
    public $possible_cvv_keys = array(
        "cvvresponse",
        "CVV2MATCH",
        "CVV2ResponseCode",
    );
    public $pi = 3.1415;
    public $earth_radius = 6373; //km  

    public function __construct() {
        $this->order = new order;
        $this->user = new umUser;

        include_once("config.inc.php");
        global $cfg;
        $this->config = $cfg;
    }

    public function check_order($order_id, $recheck = false) {

        $cache = $this->get_order_cache($order_id);

        if (!$recheck && $cache !== NULL) {
            return $cache;
        }

        $classes = array();

        //we need to gather the info for this order
        $o_array = $this->order->get_specific_orders(array($order_id));
        if (!isset($o_array[0])) {
            return NULL;
        }

        $o = $o_array[0];

        $response = new stdClass; //basic response class

        $response->order_id = $order_id;

        $response->card_processor_result_raw = $o->raw_response;

        $response->avs_response = $this->get_gateway_response($o, "avs");
        $response->cvv_response = $this->get_gateway_response($o, "cvv");

        //get the user
        $user = $this->user->get_user_info_by_id($o->user_id);

        if ($recheck == false) {
            $maxmind = $this->query_maxmind($user);
            $response->external_result_raw = serialize($maxmind);
        } else {
            $maxmind = new stdClass();
            if ($cache->external_result_raw) {
                $maxmind = unserialize($cache->external_result_raw);
            }
        }



        //local correlation
        if (in_array($o->country, array("CA"))) {

            $extern_coords = array("lat" => $maxmind["ip_latitude"], "lng" => $maxmind["ip_longitude"]);

            //get internally determined coords (from DB)
            $zip_stub = strtoupper(substr($o->zip, 0, 3));
            $sql = "select lat,lng from canada_zip_lookup where first_three_zip='" . $zip_stub . "'";
            $result = mysql_query($sql);

            if (mysql_num_rows($result)) {
                $internal_data = mysql_fetch_assoc($result);
                $intern_coords = array("lat" => $internal_data["lat"], "lng" => $internal_data["lng"]);

                $response->ip_location_correlation_local = $this->getGeoDistanceBetweenCords($extern_coords, $intern_coords);
            } else {
                $response->ip_location_correlation_local = "Invalid zip (" . $o->zip . ")";
            }
        } else {
            $response->ip_location_correlation_local = "Not computed";
        }

        //external correlation
        $response->ip_location_correlation_external = $maxmind['distance']; //km

        $response->bin_country_match = $maxmind['binCountry'] . ";" . $maxmind['binName'] . ";" . $user['country'];

        $response->bin_prepaid_match = $maxmind['prepaid'];

        $response->ip_is_proxy = $maxmind['anonymousProxy'];

        $response->is_ip_high_risk = $maxmind['highRiskCountry'];

        $response->is_email_high_risk = $maxmind['carderEmail'];

        $response->is_address_high_risk = $maxmind['highRiskCountry'];

        $response->risk_score = $maxmind['riskScore'];

        $response->bin_phone_match = $maxmind['binPhoneMatch'];

        $response->bin_name_match = $maxmind['binNameMatch'];

        $response->generated_time = date("Y-m-d H:i:s");


        //do the checks
        //avs
        $this->add_to_class($classes, $this->check_avs($response->avs_response));

        //cvv
        $this->add_to_class($classes, $this->check_cvv($response->cvv_response));

        //correlation local 
        $this->add_to_class($classes, $this->check_correlation_local($response->ip_location_correlation_local));

        //correlation external 
        $this->add_to_class($classes, $this->check_correlation_external($response->ip_location_correlation_external));


        //bin country match
        $this->add_to_class($classes, $this->check_bin_country_match($response->bin_country_match));



        //bin_prepaid_match  
        $this->add_to_class($classes, $this->check_bin_prepaid_match($response->bin_prepaid_match));

        //ip_is_proxy   
        $this->add_to_class($classes, $this->check_ip_is_proxy($response->ip_is_proxy));

        //is_ip_high_risk
        $this->add_to_class($classes, $this->check_is_ip_high_risk($response->is_ip_high_risk));

        //is_email_high_risk
        $this->add_to_class($classes, $this->check_is_email_high_risk($response->is_email_high_risk));

        //is_address_high_risk
        $this->add_to_class($classes, $this->check_is_address_high_risk($response->is_address_high_risk));

        //risk_score
        $this->add_to_class($classes, $this->check_risk_score($response->risk_score));

        //bin_phone_match
        $this->add_to_class($classes, $this->check_bin_phone_match($response->bin_phone_match));

        //bin_name_match
        $this->add_to_class($classes, $this->check_bin_name_match($response->bin_name_match));


        //check server eamil.
        $this->add_to_class($classes, $this->check_bin_eamil_match($o->email));

        //Red flag orders that are 9.95 and have an affiliate ID attached to them
        $this->add_to_class($classes, $this->check_bin_affiliate($user['hasoffers_aff_id'], $o->total));

        //Red flag affiliate Id Rules
        $this->add_to_class($classes, $this->check_bin_affiliate_rules($user['hasoffers_aff_id']));

        //yellow falg if order is flaged as fraudulent

        $this->add_to_class($classes, $this->check_fraudulent_flag($user['fraudulent_flag']));




        $response->classes = implode(";", $classes);



        if ($cache == NULL) {
            //insert
            $this->insert($response);
        } else {
            //update
            $this->update($response);
        }


        return $response;
    }

    public function insert($obj) {
        $data = get_object_vars($obj);
        $in = array();
        $int = "";
        foreach ($data as $k => $d) {
            $in[] = $k . "='" . mysql_real_escape_string($d) . "'";
        }
        $int = implode(",", $in);

        mysql_query("insert into " . $this->tbl . " set " . $int);
    }

    public function update($obj) {
        $data = get_object_vars($obj);
        $in = array();
        $int = "";
        foreach ($data as $k => $d) {
            $in[] = $k . "='" . mysql_real_escape_string($d) . "'";
        }
        $int = implode(",", $in);
        mysql_query("update " . $this->tbl . " set " . $int . " where order_id=" . $obj->order_id . " limit 1");
    }

    public function query_maxmind($data) {
        include_once("maxmind/CreditCardFraudDetection.php");

        // Create a new CreditCardFraudDetection object
        $ccfs = new CreditCardFraudDetection;

        // Enter your license key here (Required)
        $h["license_key"] = $this->config['maxmind_key'];

        // Required fields
        $h["i"] = $data['user_ip'];             // set the client ip address
        $h["city"] = $data['city'];             // set the billing city
        $h["region"] = $data['state'];                 // set the billing state
        $h["postal"] = $data['postalcode'];              // set the billing zip code
        $h["country"] = $data['country'];                // set the billing country
        // Recommended fields
        $email_parts = array_reverse(explode("@", $data["email"]));
        $h["domain"] = $email_parts[0];  // Email domain
        $h["bin"] = substr($data["cardnumber"], 0, 6);   // bank identification number
        //$h["forwardedIP"] = "24.24.24.25";	// X-Forwarded-For or Client-IP HTTP Header
        // CreditCardFraudDetection.php will take
        // MD5 hash of e-mail address passed to emailMD5 if it detects '@' in the string
        $h["emailMD5"] = $data["email"];
        // CreditCardFraudDetection.php will take the MD5 hash of the username/password if the length of the string is not 32
        $h["usernameMD5"] = "test_carder_username";
        $h["passwordMD5"] = "test_carder_password";

        // Optional fields
        //$h["binName"] = "MBNA America Bank";	// bank name
        //$h["binPhone"] = "800-421-2110";	// bank customer service phone number on back of credit card
        //$h["custPhone"] = "212-242";		// Area-code and local prefix of customer phone number
        $h["requested_type"] = $this->config['maxmind_fraud_request_type']; // Which level (free, city, premium) of CCFD to use
        /*
          $h["shipAddr"] = "145-50 157TH STREET";	// Shipping Address
          $h["shipCity"] = "Jamaica";	// the City to Ship to
          $h["shipRegion"] = "NY";	// the Region to Ship to
          $h["shipPostal"] = "11434";	// the Postal Code to Ship to
          $h["shipCountry"] = "US";	// the country to Ship to
         */

        // $h["txnID"] = "1234";			// Transaction ID
        // $h["sessionID"] = "abcd9876";		// Session ID
        // $h["accept_language"] = "de-de";
        //$h["user_agent"] = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_5; de-de) AppleWebKit/525.18 (KHTML, like Gecko) Version/3.1.2 Safari/525.20.1";
        // If you want to disable Secure HTTPS or don't have Curl and OpenSSL installed
        // uncomment the next line
        // $ccfs->isSecure = 0;
        // set the timeout to be five seconds
        $ccfs->timeout = 10;

        // uncomment to turn on debugging
        // $ccfs->debug = 1;
        // how many seconds to cache the ip addresses
        // $ccfs->wsIpaddrRefreshTimeout = 3600*5;
        // file to store the ip address for minfraud3.maxmind.com, minfraud1.maxmind.com and minfraud2.maxmind.com
        // $ccfs->wsIpaddrCacheFile = "/tmp/maxmind.ws.cache";
        // if useDNS is 1 then use DNS, otherwise use ip addresses directly
        $ccfs->useDNS = 0;

        $ccfs->isSecure = 0;

        // next we set up the input hash
        $ccfs->input($h);

        // then we query the server
        $ccfs->query();

        // then we get the result from the server
        $o = $ccfs->output();

        file_put_contents(LOG_DIR . "antifraud/" . date("Y-m-d-") . "query.log", date("d-m-Y H:i:s") . "\nSent:\n" . print_r($h, 1) . "\nReceived:\n" . print_r($o, 1) . "\n\n--------------------------\n", FILE_APPEND);

        return $o;
    }

    public function getGeoDistanceBetweenCords($coord1, $coord2) {

        $lat1 = $coord1['lat'] * ($this->pi / 180);
        $lng1 = $coord1['lng'] * ($this->pi / 180);

        $lat2 = $coord2['lat'] * ($this->pi / 180);
        $lng2 = $coord2['lng'] * ($this->pi / 180);

        $delta_lng = $lng2 - $lng1;
        $delta_lat = $lat2 - $lat1;

        $a = pow(sin($delta_lat / 2), 2) * cos($lat1) * cos($lat2) * pow(sin($delta_lng / 2), 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $this->earth_radius * $c;
    }

    public function get_gateway_response($order, $type = "avs") {
        if (isset($order->raw_response) && strlen($order->raw_response)) {
            try {
                $raw = unserialize($order->raw_response);
            } catch (Exception $ex) {
                die($order->raw_response);
                $raw = new stdClass();
            }
            //$raw = unserialize($order->raw_response);
            //if raw is empty, we do not have the raw data, but it should exist in the merchant history data
            if (!count($raw)) {
                $raw = $this->get_raw_response_for_user($order->email);
            }

            $possible_array = "possible_" . $type . "_keys";
            $possible_keys = $this->{$possible_array};

            if (is_array($raw)) {
                foreach ($possible_keys as $key) {
                    if (array_key_exists($key, $raw)) {
                        return $raw[$key];
                    }
                }
            }
        }

        return "N/A";
    }

    public function get_remaining_queries() {
        $res = mysql_query('select external_result_raw from ' . $this->tbl . ' order by id DESC limit 1');

        if (mysql_num_rows($res)) {
            $data = mysql_fetch_object($res);

            if (strlen($data->external_result_raw)) {
                $raw = unserialize($data->external_result_raw);

                if (isset($raw['queriesRemaining'])) {
                    return max(1, $raw['queriesRemaining']);
                }
            }
        }

        return 0;
    }

    public function get_order_cache($order_id) {
        //do we have cache ?
        $res = mysql_query('select * from ' . $this->tbl . ' where order_id=' . $order_id . ' limit 1');

        if (mysql_num_rows($res)) {
            return mysql_fetch_object($res);
        }

        return NULL;
    }

    public function get_raw_response_for_user($email) {
        $res = mysql_query('select raw_response from mem_merchant_history where user_email="' . $email . '" ORDER BY hDate DESC limit 1');

        if (mysql_num_rows($res)) {
            $data = mysql_fetch_object($res);
            if (strlen($data->raw_response)) {
                return unserialize($data->raw_response);
            }
        }

        return array();
    }

    public function get_orders($from = "", $to = "", $page = 0, $per_page = 10, $status = "all", $rebill = "all") {

        $sql = "select o.id as orderID,o.*,a.*,u.user_ip,u.hasoffers_aff_id affID, u.fraudulent_flag fraudulent_flag from mem_order o left join " . $this->tbl . " a on o.id=a.order_id left join mem_user u on o.user_id=u.UserID where 1=1";

        if (strlen($from)) {

            $sql.= " and o.date>='" . $from . "'";
        }

        if (strlen($to)) {
            $sql.= " and o.date<='" . $to . " 23:59:59'"; //make sure it covers the entire day
        }

        if ($status != "all") {
            $parts = explode(";", $status);
            $status_sql = array();
            foreach ($parts as $part) {
                $status_sql[] = "classes LIKE '%" . $part . "%'";
            }
            $sql.= " and (" . implode(" OR ", $status_sql) . ")";
        }

        if ($rebill) {
            if ($rebill == "inital") {
                $sql.= " and cast(total/qty as decimal) = cast(9.95 as decimal) AND `description` NOT LIKE '%Bedroom Guardian Rebill%'";
            } else if ($rebill == "rebill") {
                $sql.= " and  `description` LIKE '%Bedroom Guardian Rebill%'";
            }
        }

        //order
        $sql.=" order by o.date desc limit " . ($page * $per_page) . "," . $per_page;

        $result = mysql_query($sql);

        $data = array();

        while ($row = mysql_fetch_object($result)) {

            $data[] = $row;
        }

        return $data;
    }

    public function get_single_order($order_id) {

        if (intval($order_id) <= 0 || !is_numeric($order_id)) {
            return array();
        }

        $sql = "select o.id as orderID,o.*,a.*,u.user_ip,u.hasoffers_aff_id affID, u.fraudulent_flag from mem_order o left join " . $this->tbl . " a on o.id=a.order_id left join mem_user u on o.user_id=u.UserID where u.UserID=" . $order_id;


        $result = mysql_query($sql);

        $data = array();

        while ($row = mysql_fetch_object($result)) {

            $data[] = $row;
        }

        return $data;
    }

    public function get_orders_by_affiliate($affiliate_id, $page = 0, $per_page = 10) {
        if (intval($affiliate_id) <= 0 || !is_numeric($affiliate_id)) {
            return array();
        }

        $sql = "select o.id as orderID,o.*,a.*,u.user_ip,u.hasoffers_aff_id affID, u.fraudulent_flag from mem_order o left join " . $this->tbl . " a on o.id=a.order_id left join mem_user u on o.user_id=u.UserID where u.hasoffers_aff_id=" . $affiliate_id;


        $sql.=" order by o.date desc limit " . ($page * $per_page) . "," . $per_page;

        $result = mysql_query($sql);

        $data = array();

        while ($row = mysql_fetch_object($result)) {

            $data[] = $row;
        }


        return $data;
    }

    public function get_order_count_by_affiliate($affiliate_id) {
        if (intval($affiliate_id) <= 0 || !is_numeric($affiliate_id)) {
            return 0;
        }

        $sql = "select COUNT(o.id) as k from mem_order o left join " . $this->tbl . " a on o.id=a.order_id left join mem_user u on o.user_id=u.UserID where u.hasoffers_aff_id=" . $affiliate_id;


        $result = mysql_query($sql);

        $row = mysql_fetch_object($result);

        return $row->k;
    }

    public function get_total_orders($from = "", $to = "", $status = "all", $rebill = "") {

        $sql = "select COUNT(o.id) as k  from mem_order o left join " . $this->tbl . " a on o.id=a.order_id where 1=1";

        if (strlen($from)) {

            $sql.= " and o.date>='" . $from . "'";
        }

        if (strlen($to)) {
            $sql.= " and o.date<='" . $to . " 23:59:59'"; //make sure it covers the entire day
        }

        if ($status != "all") {
            $parts = explode(";", $status);
            $status_sql = array();
            foreach ($parts as $part) {
                $status_sql[] = "classes LIKE '%" . $part . "%'";
            }
            $sql.= " and (" . implode(" OR ", $status_sql) . ")";
        }

        if ($rebill) {
            if ($rebill == "inital") {
                $sql.= " and cast(total/qty as decimal) = cast(9.95 as decimal) AND `description` NOT LIKE '%Bedroom Guardian Rebill%'";
            } else if ($rebill == "rebill") {
                $sql.= " and  `description` LIKE '%Bedroom Guardian Rebill%'";
            }
        }
        //echo $sql;

        $result = mysql_query($sql);

        $row = mysql_fetch_object($result);

        return $row->k;
    }

    public function get_next_unchecked_order() {
        $sql = "select o.id as orderID,a.id as AID from mem_order o left join " . $this->tbl . " a on o.id=a.order_id where a.id IS NULL limit 1";

        $result = mysql_query($sql);

        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_object($result);
            return $row->orderID;
        }

        return false;
    }

    //checkers

    public function add_to_class(&$classes, $new_class) {
        if (!in_array($new_class, $classes)) {
            $classes[] = $new_class;
        }
    }

    function check_avs($data) {

        switch ($data) {

            case "N":$class = "red";
                break;
            case "G":$class = "yellow";
                break;
            default:$class = "green";
                break;
        }

        return $class;
    }

    function check_cvv($data) {

        switch ($data) {

            case "N":$class = "red";
                break;
            default:$class = "green";
                break;
        }

        return $class;
    }

    function check_generic_no_yes($data) {
        switch ($data) {

            case "Yes":$class = "red";
                break;
            case "NA":$class = "grey";
                break;
            default:$class = "green";
                break;
        }

        return $class;
    }

    function check_generic_yes_no($data) {
        switch ($data) {

            case "No":$class = "red";
                break;
            case "NA":$class = "grey";
                break;
            default:$class = "green";
                break;
        }

        return $class;
    }

    function check_correlation_external($data) {
        //just in km now
        //hard limit to 50/100
        $num = intval($data);

        if ($num <= 50) {
            $class = "green";
        } elseif ($num > 50 && $num <= 100) {
            $class = "yellow";
        } else {
            $class = "red";
        }

        return $class;
    }

    function check_correlation_local($data) {
        return "grey";
    }

    function check_bin_country_match($data) {

        $parts = explode(";", $data);
        $class = "red";
        if ($parts[0] == $parts[2]) {
            $class = "green";
        }

        return $class;
    }

    function check_bin_prepaid_match($data) {
        return $this->check_generic_no_yes($data);
    }

    function check_ip_is_proxy($data) {
        return $this->check_generic_no_yes($data);
    }

    function check_is_ip_high_risk($data) {
        return $this->check_generic_no_yes($data);
    }

    function check_is_email_high_risk($data) {
        return $this->check_generic_no_yes($data);
    }

    function check_is_address_high_risk($data) {
        return $this->check_generic_no_yes($data);
    }

    function check_risk_score($data) {
        return "grey";
    }

    function check_bin_phone_match($data) {
        return $this->check_generic_yes_no($data);
    }

    function check_bin_name_match($data) {
        return $this->check_generic_yes_no($data);
    }

    function get_redflagemails() {
        static $flagemails = null;
        if ($flagemails === null) {
            $redflagRules = new antifraud_reflag();
            $flagemails = $redflagRules->getRulesByCategory('email');
        }
        return $flagemails;
    }

    function get_enableAfflicateRedflag() {
        static $enableAfflicateRedflag = null;
        if ($enableAfflicateRedflag === null) {
            $redflagRules = new antifraud_reflag();
            $enableAfflicateRedflag = $redflagRules->getSingleRuleByCategory('initial_order');
        }
        return $enableAfflicateRedflag;
    }

    function get_reflag_affiliaterules() {
        static $affiliaterules = null;
        if ($affiliaterules === null) {
            $redflagRules = new antifraud_reflag();
            $affiliaterules = $redflagRules->getRulesByCategory('affiliate_id');
        }
        return $affiliaterules;
    }

    function check_bin_eamil_match($email) {
        $redEmails = $this->get_redflagemails();
        if (in_array($email, $redEmails)) {
            return $this->check_generic_no_yes("Yes");
        } else {
            return $this->check_generic_no_yes("No");
        }
    }

    function check_bin_affiliate($affiliateId, $totalAmount) {
        $enableAfflicate = $this->get_enableAfflicateRedflag();
        if (!$enableAfflicate) {
            return $this->check_generic_no_yes("No");
        }
        if ($affiliateId && $totalAmount == 9.95) {
            return $this->check_generic_no_yes("Yes");
        } else {
            return $this->check_generic_no_yes("No");
        }
    }

    public function d_get_order_count_by_affiliate($affiliate_id) {
        if (intval($affiliate_id) <= 0 || !is_numeric($affiliate_id)) {
            return 0;
        }

        $sql = "select COUNT(o.id) as k from mem_order o left join " . $this->tbl . " a on o.id=a.order_id left join mem_user u on o.user_id=u.UserID where u.hasoffers_aff_id=" . $affiliate_id;


        $result = mysql_query($sql);

        $row = mysql_fetch_object($result);

        return $row->k;
    }

    function check_bin_affiliateid_with_rule($affiliateId, $ruleName, $percentageValue = 0) {
        $totalOrderCount = $this->get_order_count_by_affiliate($affiliateId);

        $sql = "select COUNT(o.id) as k from mem_order o left join " . $this->tbl . " a on o.id=a.order_id left join mem_user u on o.user_id=u.UserID where u.hasoffers_aff_id=" . $affiliateId;

        switch ($ruleName) {
            case "initial_order":
                $sql.= " and cast(total/qty as decimal) = cast(9.95 as decimal) AND `description` NOT LIKE '%Bedroom Guardian Rebill%'";
                break;
            case "initial_order_wihout_upsell":
                $sql.= " and cast(total/qty as decimal) = cast(9.95 as decimal) AND `description` NOT LIKE '%Bedroom Guardian Rebill%' and `description` NOT LIKE '%Upsells%'";
                break;
            case "initial_order_wihout_upsell_995":
                $sql.= " and cast(total as decimal) = cast(9.95 as decimal) AND `description` NOT LIKE '%Bedroom Guardian Rebill%' and `description` NOT LIKE '%Upsells%'";
                break;
            default:
                break;
        }
        $result = mysql_query($sql);
        $row = mysql_fetch_object($result);
        $count = $row->k;
        if ($percentageValue * 1 == 0) {
            return true;
        }
        if ($totalOrderCount == 0 && !$totalOrderCount) {
            return false;
        }
        $pe = $count / $totalOrderCount * 100;
        if ($pe >= $percentageValue) {
            return true;
        }
        return false;
    }

    function check_bin_affiliate_rules($affiliateId) {
        $rules = $this->get_reflag_affiliaterules();
        if (!$rules || count($rules) == 0) {
            return $this->check_generic_no_yes("No");
        }
        $flag = "No";
        if ($affiliateId) {
            foreach ($rules as $rule) {
                $row = json_decode($rule);
                if (!$row->affiliate_id) {
                    continue;
                }
                if (($row->affiliate_id == "*") || ($row->affiliate_id == $affiliateId) || check_in_wildcards($row->affiliate_id, $affiliateId) == true) {
                    $check = $this->check_bin_affiliateid_with_rule($affiliateId, $row->rule_type, $row->percentage_value);
                    if ($check == true) {
                        $flag = "Yes";
                        break;
                    }
                }
            }
        }
        return $this->check_generic_no_yes($flag);
    }

    function check_fraudulent_flag($flag) {
        if ($flag == 1) {
            return "yellow";
        } else {
            return "green";
        }
    }

}

function check_in_wildcards($wildCard, $affiliateId) {
    $pattern = $wildCard;
    $preFix = substr($wildCard, 0, 1);
    if ($preFix == "*") {
        $pattern = substr($wildCard, 1);
    }
    $sufFix = substr($pattern, -1);
    if ($sufFix == "*") {
        $pattern = substr($pattern, 0, -1);
    }

    if ($preFix == "*" && $sufFix == "*") {
        if (strpos($affiliateId, $pattern) === false) {
            return false;
        } else {
            return true;
        }
    } else if ($preFix == "*") {
        $length = strlen($pattern);
        return (substr($affiliateId, -$length) === $pattern);
    } else if ($sufFix == "*") {
        if (strpos($affiliateId, $pattern) == 0) {
            return true;
        } else {
            return false;
        }
    } else {
        return $wildCard == $affiliateId;
    }
}
