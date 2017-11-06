<?php

define("TELEPHONE_NUMBER_LENGTH_MIN", 10);
define("TELEPHONE_NUMBER_LENGTH_MAX", 12);
define("TELEPHONE_FORMAT", "/[0-9]/");

define("EMAIL_ADDRESS_LENGTH_MAX", 250);
define("EMAIL_ADDRESS_LENGTH_MIN", 5);
define("EMAIL_FORMAT", "/[a-zA-Z0-9-_.]*@[a-zA-Z0-9-_.]*\\.[a-zA-Z0-9-_.]*/");

define("PASSWORD_LENGTH_MIN", 6);
define("PASSWORD_LENGTH_MAX", 20);

define("GROUP_TITLE_LENGTH_MIN", 1);
define("GROUP_TITLE_LENGTH_MAX", 250);

define("MEMO_LENGTH_MAX", 250);

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "mcrypt.class.php");
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "session.php");

class umUser {

    var $userID = 0;
    var $groupID = 0;
    var $email = "";
    var $emailAddress = "";
    var $emailVerified = 0;
    var $verificationCode = NULL;
    var $password = NULL;
    var $createTime = NULL;
    var $expirationDate = NULL;
    var $lastLoginTime = NULL;
    var $lastLoginIP = NULL;
    var $loginCount = 0;
    var $status = 0;
    var $memo = "";
    var $belongToGroups = array(); //
    var $_loginStep = NULL;
    var $_logoutStep = NULL;
    var $firstname = "";
    var $lastname = "";
    var $shipped = "";
    var $shippedFrom = "";
    var $shippedTo = "";
    var $cardNumber = "";
    var $cardName = "";
    var $address = "";
    var $state = "";
    var $city = "";
    var $country = "";
    var $postalcode = "";
    var $phone = "";
    var $days = 365;
    var $sent_mail = 0;
    var $notes = "";
    var $subid = 0;
    var $hasoffers_offer_id = 0;
    var $hasoffers_aff_id = 0;
    var $adjust_aff_id = '';
    var $has_monthly_fee = false;
    var $recurring_fee = 0;
    var $recurring_fee_period = 0; //in days
    var $user_ip = "";
    var $account_number = "";
    var $routing_number = "";
    var $name_on_check = "";
    var $lifetime = "";
    var $paymentData = array();
    var $device_type = "desktop";
    var $email_campaign = 0;
    var $campaign_offer = 0;
    var $Cipher;

    function __construct() {
        $this->Cipher = new Cipher();
    }

    function tempEcrypt() {
        global $cfg;
        $sql = "SELECT UserID, cardnumber, cardname, cvvcode FROM " . $cfg['database']['prefix'] . "user";
        $rows = multi_query_assoc($sql);
        foreach ($rows as $row) {
            $sql = "UPDATE " . $cfg['database']['prefix'] . "user 
					SET cardnumber='" . $this->Cipher->encrypt($row['cardnumber']) . "',
					cardname='" . $this->Cipher->encrypt($row['cardname']) . "',
					cvvcode='" . $this->Cipher->encrypt($row['cvvcode']) . "'
					";
            mysql_query($sql);
        }
    }

    function isExistUserID($userID) {
        global $cfg;
        $sql = "SELECT Email FROM " . $cfg['database']['prefix'] . "user WHERE Email='" . db_escape_characters($userID) . "'";
        $rst = mysql_query($sql);
        if (mysql_num_rows($rst))
            return true;
        return false;
    }

    function isExistEmail($email, $userID = 0) {
        global $cfg;
        $sql = "SELECT EmailAddress FROM " . $cfg['database']['prefix'] . "user WHERE EmailAddress='" . db_escape_characters($email) . "'  AND UserID<>'" . $userID . "'";
        $rst = mysql_query($sql);
        if (mysql_num_rows($rst))
            return true;
        return false;
    }

    function isExistUserName($userName, $userID = 0) {
        global $cfg;
        $sql = "SELECT Email FROM " . $cfg['database']['prefix'] . "user WHERE Email='" . db_escape_characters($userName) . "'  AND UserID<>'" . db_escape_characters($userID) . "'";
        $rst = mysql_query($sql);
        if (mysql_num_rows($rst))
            return true;
        return false;
    }

    function generate_verification_code() {
        $code = "";
        $code = md5(date("Y-m-d H:i:s"));
        return $code;
    }

    function get_user_info_by_email($email) {
        global $cfg;
        $query = "SELECT UserID, firstname, lastname, phone, cardnumber, cardtype, cvvcode,
                         month(expiration) as expiration_month, CreateTime, 
                         year(expiration) as expiration_year,
                         address, city, state, country, EmailAddress, Email as email, postalcode,user_ip,
						 recurring_fee, recurring_fee_period,subid,hasoffers_offer_id, hasoffers_aff_id,device_type   
                          FROM " . $cfg['database']['prefix'] . "user
                          WHERE Email='" . db_escape_characters($email) . "' LIMIT 1";

        $result = mysql_query($query) or file_put_contents("footage_error.log", mysql_error() . "\n\n", FILE_APPEND);

        if (mysql_num_rows($result)) {
            $r = mysql_fetch_assoc($result);
            if ($r["cardnumber"] != '')
                $r["cardnumber"] = $this->Cipher->decrypt($r["cardnumber"]);
            if ($r["cvvcode"] != '')
                $r["cvvcode"] = $this->Cipher->decrypt($r["cvvcode"]);
            //file_put_contents("footage_error.log",print_r($r,1)."\n\n",FILE_APPEND);
            return $r;
        } else {
            return FALSE;
        }
    }

    function get_user_info($userid) {
        global $cfg;
        $query = "SELECT firstname, lastname, phone, cardnumber, cardtype, cvvcode,
                         month(expiration) as expiration_month,
                         year(expiration) as expiration_year,
                         address, city, state, country, emailAddress as email, postalcode,user_ip,
						 recurring_fee, recurring_fee_period,subid,hasoffers_offer_id, hasoffers_aff_id,device_type   
                          FROM " . $cfg['database']['prefix'] . "user
                          WHERE Email='" . db_escape_characters($userid) . "' LIMIT 1";

        $result = mysql_query($query) or file_put_contents("footage_error.log", mysql_error() . "\n\n", FILE_APPEND);

        if (mysql_num_rows($result)) {
            $r = mysql_fetch_assoc($result);
            if ($r["cardnumber"] != '')
                $r["cardnumber"] = $this->Cipher->decrypt($r["cardnumber"]);
            if ($r["cvvcode"] != '')
                $r["cvvcode"] = $this->Cipher->decrypt($r["cvvcode"]);
            //file_put_contents("footage_error.log",print_r($r,1)."\n\n",FILE_APPEND);
            return $r;
        } else {
            return FALSE;
        }
    }

    function get_user_info_by_id($userid) {
        global $cfg;
        $query = "SELECT firstname, lastname, phone, cardnumber, cardtype, cvvcode,
                         month(expiration) as expiration_month,
                         year(expiration) as expiration_year,
                         address, city, state, country, emailAddress as email, postalcode,user_ip, notes, 
						 recurring_fee, recurring_fee_period,subid,hasoffers_offer_id, CreateTime, hasoffers_aff_id,device_type,fraudulent_flag,fraudulent_investigated   
                          FROM " . $cfg['database']['prefix'] . "user
                          WHERE UserID='" . db_escape_characters($userid) . "' LIMIT 1";

        $result = mysql_query($query) or file_put_contents("footage_error.log", mysql_error() . "\n\n", FILE_APPEND);

        if (mysql_num_rows($result)) {
            $r = mysql_fetch_assoc($result);

            $r["original_cardnumber"] = $r["cardnumber"];

            if ($r["cardnumber"] != '')
                $r["cardnumber"] = $this->Cipher->decrypt($r["cardnumber"]);
            if ($r["cvvcode"] != '')
                $r["cvvcode"] = $this->Cipher->decrypt($r["cvvcode"]);
            //file_put_contents("footage_error.log",print_r($r,1)."\n\n",FILE_APPEND);
            return $r;
        } else {
            return FALSE;
        }
    }

    function get_user($loadGroups = false) {
        global $cfg;
        $return = false;

        if (!is_numeric($this->userID))
            $this->userID = 0;

        $sql = "SELECT `UserID`,`Email`,`EmailVerified`,`VerificationCode`,`Password`";
        $sql .= ",`CreateTime`,`LastLoginTime`,`LastLoginIP`,`LoginCount`,`Status`,`Memo`,`EmailAddress`,`expiration`,`phone`,`days`,`firstname`,`lastname`,`lifetime`,`user_ip`";
        $sql .= ",`state`,`city`,`address`,`postalcode`,`sent_mail`,`country`";
        $sql .= ",`recurring_fee`,`recurring_fee_period`, notes, subid,hasoffers_offer_id, hasoffers_aff_id, device_type  ";
        $sql .= "FROM " . $cfg['database']['prefix'] . "user ";
        if ($this->userID == 0) {
            $sql .= "WHERE Email='" . db_escape_characters($this->email) . "'";
        } else {
            $sql .= "WHERE UserID=" . db_escape_characters($this->userID);
        }
        $result = mysql_query($sql);
        if ($fields = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $this->userID = $fields["UserID"];
            $this->email = $fields["Email"];
            $this->emailAddress = $fields["EmailAddress"];
            $this->emailVerified = $fields["EmailVerified"];
            $this->verificationCode = $fields["VerificationCode"];
            $this->password = $fields["Password"];
            $this->createTime = $fields["CreateTime"];
            $this->lastLoginTime = $fields["LastLoginTime"];
            $this->lastLoginIP = $fields["LastLoginIP"];
            $this->loginCount = $fields["LoginCount"];
            $this->status = $fields["Status"];
            $this->memo = $fields["Memo"];

            $this->notes = $fields["notes"];

            $this->subid = $fields["subid"];

            $this->hasoffers_offer_id = $fields["hasoffers_offer_id"];
            $this->hasoffers_aff_id = $fields["hasoffers_aff_id"];

            $this->sent_mail = $fields["sent_mail"];

            $this->country = $fields["country"];
            $this->state = $fields["state"];
            $this->city = $fields["city"];
            $this->address = $fields["address"];
            $this->postalcode = $fields["postalcode"];

            $this->expirationDate = $fields["expiration"];
            $this->phone = $fields["phone"];
            $this->firstname = $fields["firstname"];
            $this->lastname = $fields["lastname"];
            $this->lifetime = $fields["lifetime"];
            $this->days = $fields["days"];

            $this->user_ip = $fields["user_ip"];

            $this->recurring_fee = $fields["recurring_fee"];
            $this->recurring_fee_period = $fields["recurring_fee_period"];

            $this->device_type = $fields["device_type"];


            $return = true;
        }
        mysql_free_result($result);

        if ($loadGroups) {
            $this->belongToGroups = array();
            $sql = "SELECT g.GroupID, g.GroupTitle, g.DefaultGroup, g.Token, g.Status, g.Memo ";
            $sql .= "FROM " . $cfg['database']['prefix'] . "group g, " . $cfg['database']['prefix'] . "user_group_mapping m ";
            $sql .= "WHERE m.GroupID = g.GroupID AND m.UserID=" . db_escape_characters($this->userID) . " ORDER BY g.GroupID";

            $result = mysql_query($sql);
            while ($fields = mysql_fetch_array($result, MYSQL_NUM)) {
                $group = new umGroup();
                $group->groupID = $fields[0];
                $group->groupTitle = $fields[1];
                $group->defaultGroup = $fields[2];
                $group->token = $fields[3];
                $group->status = $fields[4];
                $group->memo = $fields[5];
                $this->belongToGroups[] = $group;
            }
            mysql_free_result($result);
        }



        return $return;
    }

    function set_client_exists_cookie() {
        //update the client exists cookie, or insert it
        if (!strlen($this->country)) {

            $userinfo = $this->get_user_info($this->userID);

            $client_exists_cookie_text = $this->userID . "||" . md5($userinfo["country"] . $userinfo["state"] . $userinfo["city"]);
        } else {

            $client_exists_cookie_text = $this->userID . "||" . md5($this->country . $this->state . $this->city);
        }
        supersession("client_exists", $client_exists_cookie_text, time() + 3600 * 24 * 365);
    }

    function set_user_lifetime($months) {
        global $cfg;
        $query = "UPDATE " . $cfg['database']['prefix'] . "user SET lifetime=DATE_ADD(IF(NOW()>lifetime, NOW(), lifetime), INTERVAL $months MONTH) WHERE UserID='" . $this->userID . "'";
        mysql_query($query);
    }

    function create_user($data, $groupID = 0, $tempid = 0) {
        global $client_ip;
        global $cfg;
        /*
          if(($_SERVER["REMOTE_ADDR"]=="180.194.247.246") || ($client_ip=="180.194.247.246")) {
          return true;
          }
         */
        if (sizeof($data)) {
            if (!isset($data["emailVerified"]))
                $data["emailVerified"] = 0;
            if (!isset($data["verificationCode"]))
                $data["verificationCode"] = $this->generate_verification_code();
            if (!isset($data["createTime"]))
                $data["createTime"] = date("Y-m-d H:i:s");
            if (!isset($data["status"]))
                $data["status"] = (int) ($cfg['site']['autoEnable']);

            $data["lastLoginIp"] = $client_ip;
            $data["loginCount"] = 0;

            $data["memo"] = isset($data["memo"]) ? mysql_escape_string($data["memo"]) : "";

            $data["last_payment"] = $data["createTime"];

            //chedk the day, and make sure we do not run over the 28th, so we will not need to bother with 29-31 interval
            $dday = intval(date("d", strtotime($data["last_payment"])));
            if ($dday > 28) {
                $dday = 28;
                $ddmonth = date("m", strtotime($data["last_payment"]));
                $ddyear = date("Y", strtotime($data["last_payment"]));

                $his = date("H:i:s", strtotime($data["last_payment"]));
                if ($his == "00:00:00")
                    $his = date("H:i:s");

                $data["last_payment"] = $ddyear . "-" . $ddmonth . "-" . $dday . " " . $his;
            }

            $data["user_ip"] = $_SERVER["REMOTE_ADDR"];

            $fields = "";
            $values = "";

            foreach ($data as $key => $value) {
                $fields .= ",`$key`";
                if (in_array($key, array("cardnumber", "cardname", "cvvcode", "routing_number", "account_number", "name_on_check")))
                    $value = $this->Cipher->encrypt($value);
                $values .= ",\"" . mysql_escape_string($value) . "\"";
            }

            if ($fields == "")
                return false;

            $fields = substr($fields, 1);
            $values = substr($values, 1);
            /* $fields = "expiration" . $fields;
              $values = "\"$expiration\"" . $values; */

            $sql = "INSERT INTO " . $cfg['database']['prefix'] . "user($fields) VALUES($values);";

            $con = connect_database();

            if (mysql_query($sql)) {
                $id = mysql_insert_id();

                $sql = "INSERT INTO " . $cfg['database']['prefix'] . "user_psscdata SET UserID='" . $id . "'";
                mysql_query($sql);

                $sql = "INSERT INTO " . $cfg['database']['prefix'] . "user_group_mapping ";
                $sql .= "(UserID, GroupID) VALUES ";
                $sql .= "('$id','$groupID')";


                mysql_query($sql);

                /* load and run plug-ins */
                // load plug-ins
                /*
                  $list = array();
                  if ($handle = opendir($cfg['site']['root'].$cfg['site']['folder']."plug-ins")){
                  while (false !== ($file = readdir($handle))) {
                  if($file != '.' && $file != '..'){
                  if(is_dir($cfg['site']['root'].$cfg['site']['folder']."plug-ins/".$file)) $list[] = $file;
                  }
                  }
                  }
                  sort($list);
                  // run plug-ins
                  for($i = 0; $i < count($list); $i++){
                  if(!file_exists($cfg['site']['root'].$cfg['site']['folder']."plug-ins/".$list[$i].'/install.php')){
                  if(file_exists($cfg['site']['root'].$cfg['site']['folder']."plug-ins/".$list[$i].'/_create_user.inc.php')){
                  require_once($cfg['site']['root'].$cfg['site']['folder']."plug-ins/".$list[$i].'/_create_user.inc.php');
                  }
                  }
                  }
                 */

                // register user for forumDatabase
                /* $con = connect_database_forum();
                  $sql = "INSERT INTO GDN_User SET
                  UserID='".$id."'
                  ,Name='".mysql_escape_string($data["email"])."'
                  ,Password='\$P\$BS.FeRx64gtLmE.0PzxMoy2b5KP.1F.'
                  ,HashMethod='Vanilla'
                  ,Email='".mysql_escape_string($data["emailAddress"])."'
                  ,ShowEmail='0'
                  ,Gender='m'
                  ,CountVisits='0'
                  ,CountInvitations='0'
                  ,DiscoveryText='from membership'
                  ,DateFirstVisit='".date("Y-n-d h:i:s")."'
                  ,DateLastActive='".date("Y-n-d h:i:s")."'
                  ,DateInserted ='".date("Y-n-d h:i:s")."'
                  ,HourOffset='12'
                  ,Admin='0'
                  ,Deleted='0'
                  ";
                  @mysql_query($sql, $con);
                  $sql = "INSERT INTO GDN_UserRole SET UserID='".$id."', RoleID='8'";
                  @mysql_query($sql, $con); */

                $this->userID = $id;


                //add a cookie so we know this user is registered
                $client_exists_cookie_text = $id . "||" . md5($data["country"] . $data["state"] . $data["city"]);
                supersession("client_exists", $client_exists_cookie_text, time() + 3600 * 24 * 365);

                return $id;
            } else {
                die(mysql_error());
            }
        }
        return false;
    }

    function update_user() {
        global $cfg;
        $return = false;
        if (!is_numeric($this->userID))
            $this->userID = 0;
        $oldEmail = $this->email;
        $newEmail = $this->email;
        $tempUser = new umUser();
        $tempUser->userID = $this->userID;
        if ($tempUser->get_user()) {
            $oldEmail = $tempUser->email;
        }
        $sql = "UPDATE " . $cfg['database']['prefix'] . "user SET ";
        $sql .= "EmailAddress='" . $this->emailAddress . "', ";
        $sql .= "Email='" . $this->email . "', ";
        $sql .= "EmailVerified=" . $this->emailVerified . ", ";
        $sql .= "VerificationCode='" . $this->verificationCode . "', ";
        $sql .= "Password='" . $this->password . "', ";
        $sql .= "CreateTime='" . $this->createTime . "', ";
        if ($this->lastLoginTime == NULL) {
            $sql .= "LastLoginTime=NULL, ";
        } else {
            $sql .= "LastLoginTime='" . $this->lastLoginTime . "', ";
        }
        $sql .= "LastLoginIP='" . $this->lastLoginIP . "', ";
        $sql .= "LoginCount=" . $this->loginCount . ", ";
        $sql .= "Status=" . $this->status . ", ";
        $sql .= "days='" . $this->days . "', ";
        $sql .= "notes='" . $this->notes . "', ";

        if ($this->subid != 0) {
            $sql .= "subid='" . $this->subid . "', ";
        }

        if ($this->hasoffers_offer_id != 0) {
            $sql .= "hasoffers_offer_id='" . $this->hasoffers_offer_id . "', ";
        }

        if ($this->hasoffers_aff_id != 0) {
            $sql .= "hasoffers_aff_id='" . $this->hasoffers_aff_id . "', ";
        }

        if ($this->adjust_aff_id) {
            $sql .= "adjust_aff_id='" . $this->adjust_aff_id . "', ";
        }

        if ($this->device_type != "") {
            $sql .= "device_type='" . $this->device_type . "', ";
        }

        $sql .= "Memo='" . db_escape_characters($this->memo) . "' ";
        $sql .= "WHERE UserID=" . $this->userID;

        if (mysql_query($sql)) {
            $return = true;
        }

        if ($return && $oldEmail != $newEmail) {
            /* load and run plug-ins */
            // load plug-ins
            $list = array();
            if ($handle = opendir($cfg['site']['root'] . $cfg['site']['folder'] . "plug-ins")) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != '.' && $file != '..') {
                        if (is_dir($cfg['site']['root'] . $cfg['site']['folder'] . "plug-ins/" . $file))
                            $list[] = $file;
                    }
                }
            }
            sort($list);
            // run plug-ins
            for ($i = 0; $i < count($list); $i++) {
                if (!file_exists($cfg['site']['root'] . $cfg['site']['folder'] . "plug-ins/" . $list[$i] . '/install.php')) {
                    if (file_exists($cfg['site']['root'] . $cfg['site']['folder'] . "plug-ins/" . $list[$i] . '/_change_email.inc.php')) {
                        require_once($cfg['site']['root'] . $cfg['site']['folder'] . "plug-ins/" . $list[$i] . '/_change_email.inc.php');
                    }
                }
            }
        }
        /*
          if($return){
          //update forum user table
          $con = connect_database_forum();

          $sql = "UPDATE GDN_User SET ";
          $sql .= "Email='".$this->emailAddress."', ";

          $last_active_date = ($this->lastLoginTime == NULL) ? time() : strtotime($this->lastLoginTime);

          $sql .= "DateFirstVisit='".date("Y-n-d h:i:s",strtotime($this->createTime))."',";
          $sql .= "DateLastActive='".date("Y-n-d h:i:s",$last_active_date)."',";
          $sql .= "DateInserted ='".date("Y-n-d h:i:s",strtotime($this->createTime))."',";


          $sql .= "Name='".$this->email."' ";
          $sql .= "WHERE UserID='".$this->userID."'";

          @mysql_query($sql, $con);

          $con = connect_database();
          //-end updae forum user table
          } */

        return $return;
    }

    function update_address($data, $id) {
        global $cfg;

        $sql = "UPDATE " . $cfg['database']['prefix'] . "user SET ";

        $parts = array();

        foreach ($data as $key => $value) {

            if (in_array($key, array("cardnumber", "cardname", "cvvcode", "routing_number", "account_number", "name_on_check"))) {
                $parts[] = $key . '="' . $this->Cipher->encrypt($value) . '"';
            } else {
                $parts[] = $key . '="' . $value . '"';
            }
        }
        $sql.=implode(',', $parts);

        $sql .= " WHERE UserID='" . $id . "'";
        //echo $sql;
        mysql_query($sql);
    }

    function update_memo_user() {
        global $cfg;
        if (!is_numeric($this->userID))
            $this->userID = 0;
        $sql = "UPDATE " . $cfg['database']['prefix'] . "user SET ";
        $sql .= "Memo='" . db_escape_characters($this->memo) . "' ";
        $sql .= ",shipped_from = '" . $this->shippedFrom . "' ";
        $sql .= ",shipped_to = '" . $this->shippedTo . "' ";
        $sql .= "WHERE UserID='" . $this->userID . "'";
        mysql_query($sql);
    }

    function update_notes_user() {
        global $cfg;
        if (!is_numeric($this->userID))
            $this->userID = 0;
        $sql = "UPDATE " . $cfg['database']['prefix'] . "user SET ";
        $sql .= "notes='" . db_escape_characters($this->notes) . "' ";
        $sql .= "WHERE UserID='" . $this->userID . "'";
        mysql_query($sql);
    }

    function assign_groups($groupID = "") {
        global $cfg;
        if (!is_numeric($this->userID))
            $this->userID = 0;
        $return = false;
        $sql = "DELETE FROM " . $cfg['database']['prefix'] . "user_group_mapping WHERE UserID=" . $this->userID;
        if (mysql_query($sql)) {
            if ($groupID != "") {
                $sql = "INSERT INTO " . $cfg['database']['prefix'] . "user_group_mapping (UserID, GroupID) VALUES ";
                for ($i = 0; $i < count($groupID); $i++) {
                    if ($i != 0)
                        $sql .= ",";
                    $sql .= "(" . $this->userID . ", " . $groupID[$i] . ")";
                }
                if (mysql_query($sql)) {
                    $return = true;
                }
            } else {
                $return = true;
            }
        }

        if ($return) {
            //update forum userrole table
            $con = connect_database_forum();
            $sql = "DELETE FROM gdn_userrole WHERE UserID='" . $this->userID . "'";
            @mysql_query($sql, $con);

            $sql = "INSERT INTO gdn_userrole SET UserID='" . $this->userID . "', RoleID='" . ($cfg['site']['adminGroupIDs'] == trim($groupID[0]) ? "16" : "8") . "'";
            @mysql_query($sql, $con);
            $con = connect_database();
        }
        return $return;
    }

    function regroup($assignGroups = array(), $removeGroups = array()) {
        $belongToGroups = $this->belongToGroups;
        $groups = array();
        for ($i = 0; $i < count($assignGroups); $i++) {
            $existing = false;
            for ($j = 0; $j < count($belongToGroups); $j++) {
                if ($belongToGroups[$j]->groupID == $assignGroups[$i]->groupID)
                    $existing = true;
            }
            if (!$existing)
                $belongToGroups[] = $assignGroups[$i];
        }
        for ($i = 0; $i < count($belongToGroups); $i++) {
            $existing = false;
            for ($j = 0; $j < count($removeGroups); $j++) {
                if ($removeGroups[$j]->groupID == $belongToGroups[$i]->groupID)
                    $existing = true;
            }
            if ($existing)
                $belongToGroups[$i] = NULL;
        }
        for ($i = 0; $i < count($belongToGroups); $i++) {
            if ($belongToGroups[$i] != NULL) {
                if ($belongToGroups[$i]->groupID != 0)
                    $groups[] = $belongToGroups[$i]->groupID;
            }
        }
        $this->assign_groups($groups);
    }

    function search_users($query) {
        global $cfg;
        $return = new umResult();

        // trim all inputs
        if (isset($query['selectedID']))
            $query['selectedID'] = NULL;
        if (isset($query['operation']))
            $query['operation'] = NULL;
        if ($query != NULL) {
            foreach ($query as $name => $value)
                $query[$name] = trim($value);
        }

        // get page and page size
        if (isset($query['page'])) {
            if (!is_numeric($query['page']))
                $query['page'] = 1;
        }
        if (isset($query['pageSize'])) {
            if (!is_numeric($query['pageSize']))
                $query['pageSize'] = 10;
        }else {
            $query['pageSize'] = 0;
        }

        // construction conditions
        $c = "";
        if (isset($query['fromID'])) {
            if (is_numeric($query['fromID'])) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.UserID >= " . $query['fromID'];
            } else {
                $query['fromID'] = "";
            }
        } else {
            $query['fromID'] = "";
        }
        if (isset($query['toID'])) {
            if (is_numeric($query['toID'])) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.UserID <= " . $query['toID'];
            } else {
                $query['toID'] = "";
            }
        } else {
            $query['toID'] = "";
        }
        if (isset($query['keywords'])) {
            if (strlen($query['keywords']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $keywords = explode(" ", $query['keywords']);
                $c .= " (";
                for ($i = 0; $i < sizeof($keywords); $i++) {
                    if ($i != 0)
                        $c .= " OR";
                    $c .= " u.Email LIKE '%" . db_escape_characters($keywords[$i]) . "%'";
                }
                $c .= " )";
            }
        }else {
            $query['keywords'] = "";
        }
        if (isset($query['emailAddress'])) {
            if (strlen($query['emailAddress']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $keywords = explode(" ", $query['emailAddress']);
                $c .= " (";
                for ($i = 0; $i < sizeof($keywords); $i++) {
                    if ($i != 0)
                        $c .= " OR";
                    $c .= " u.EmailAddress LIKE '%" . db_escape_characters($keywords[$i]) . "%'";
                }
                $c .= " )";
            }
        }else {
            $query['emailAddress'] = "";
        }
        if (isset($query['emailVerified'])) {
            if (is_numeric($query['emailVerified'])) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.EmailVerified = " . $query['emailVerified'];
            }
        } else {
            $query['emailVerified'] = "-";
        }

        if (isset($query['fromCreateDate'])) {
            if (strlen($query['fromCreateDate']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.CreateTime >= '" . $query['fromCreateDate'] . " 00:00:00'";
            }
        } else {
            $query['fromCreateDate'] = "";
        }
        if (isset($query['toCreateDate'])) {
            if (strlen($query['toCreateDate']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.CreateTime <= '" . $query['toCreateDate'] . " 23:59:59'";
            }
        } else {
            $query['toCreateDate'] = "";
        }

        if (isset($query['fromExpirationDate'])) {
            if (strlen($query['fromExpirationDate']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.expiration >= '" . $query['fromExpirationDate'] . " 00:00:00'";
            }
        } else {
            $query['fromExpirationDate'] = "";
        }
        if (isset($query['toExpirationDate'])) {
            if (strlen($query['toExpirationDate']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.expiration <= '" . $query['toExpirationDate'] . " 23:59:59'";
            }
        } else {
            $query['toExpirationDate'] = "";
        }

        if (isset($query['fromLastLoginDate'])) {
            if (strlen($query['fromLastLoginDate']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.LastLoginTime >= '" . $query['fromLastLoginDate'] . " 00:00:00'";
            }
        } else {
            $query['fromLastLoginDate'] = "";
        }
        if (isset($query['toLastLoginDate'])) {
            if (strlen($query['toLastLoginDate']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.LastLoginTime <= '" . $query['toLastLoginDate'] . " 23:59:59'";
            }
        } else {
            $query['toLastLoginDate'] = "";
        }
        if (isset($query['fromLoginCount'])) {
            if (is_numeric($query['fromLoginCount'])) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.LoginCount >= " . $query['fromLoginCount'];
            } else {
                $query['fromLoginCount'] = "";
            }
        } else {
            $query['fromLoginCount'] = "";
        }
        if (isset($query['toLoginCount'])) {
            if (is_numeric($query['toLoginCount'])) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.LoginCount <= " . $query['toLoginCount'];
            } else {
                $query['toLoginCount'] = "";
            }
        } else {
            $query['toLoginCount'] = "";
        }
        if (isset($query['status'])) {
            if (is_numeric($query['status'])) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " Status = " . $query['status'];
            }
        } else {
            $query['status'] = "-";
        }
        if (isset($query['groupID'])) {
            if (is_numeric($query['groupID'])) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " m.GroupID = " . $query['groupID'] . " AND m.UserID=u.UserID";
            }
        } else {
            $query['groupID'] = "-";
        }

        if (isset($query['is_order'])) {
            if ($c == "") {
                $c .= " WHERE";
            } else {
                $c .= " AND";
            }
            $c .= " m.GroupID NOT IN (" . $cfg['group']['adminTemplateAccessGroupIds'] . ") ";
        }

        // count result
        $sql = "SELECT COUNT(*) ";
        $sql .= "FROM " . $cfg['database']['prefix'] . "user AS u";
        if ((isset($query['groupID']) && is_numeric($query['groupID'])) || isset($query['is_order'])) {
            $sql .= " INNER JOIN  " . $cfg['database']['prefix'] . "user_group_mapping AS m ON u.UserID=m.UserID";
        }
        $sql .= $c;

        $result = mysql_query($sql);
        $fields = mysql_fetch_array($result, MYSQL_NUM);
        mysql_free_result($result);
        $return->total = $fields[0];
        if ($query['pageSize'] == 0)
            $query['pageSize'] = $return->total; // if no page size assigned, try to return all
        if ($query['pageSize'] == 0)
            $query['pageSize'] = 10; // if no record and no page size assigned, set page size to 10, it does not matter anyway


            
// calucalate pages
        if (isset($query['page']))
            $return->page = $query['page'];
        if (isset($query['pageSize']))
            $return->pageSize = $query['pageSize'];
        $return->totalPages = intval($return->total / $return->pageSize);
        if ($return->total % $return->pageSize)
            $return->totalPages++;
        if ($return->page > $return->totalPages)
            $return->page = $return->totalPages;
        if ($return->page < 1)
            $return->page = 1;

        // search for result
        $sql = "SELECT u.* FROM " . $cfg['database']['prefix'] . "user AS u";
        if ((isset($query['groupID']) && is_numeric($query['groupID'])) || isset($query['is_order'])) {
            $sql .= " INNER JOIN  " . $cfg['database']['prefix'] . "user_group_mapping AS m ON u.UserID=m.UserID";
        }
        $sql .= $c;
        $return->orderBy = "UserID";
        if (isset($query['orderBy'])) {
            if (strlen($query['orderBy']) > 0)
                $return->orderBy = $query['orderBy'];
        }
        $offset = $return->pageSize * ($return->page - 1);
        $sql .= " ORDER BY " . $return->orderBy . " LIMIT " . $offset . ", " . $return->pageSize;

        // execute sql and parse result

        $result = mysql_query($sql);
        while ($fields = mysql_fetch_array($result)) {
            $user = new umUser();
            $user->userID = $fields['UserID'];
            $user->email = $fields['Email'];
            $user->emailVerified = $fields['EmailVerified'];
            $user->verificationCode = $fields['VerificationCode'];
            $user->password = $fields['Password'];
            $user->createTime = $fields['CreateTime'];
            $user->lastLoginTime = $fields['LastLoginTime'];
            $user->lastLoginIP = $fields['LastLoginIP'];
            $user->loginCount = $fields['LoginCount'];
            $user->status = $fields['Status'];
            $user->memo = $fields['Memo'];
            $user->notes = $fields['notes'];
            $user->firstname = $fields['firstname'];
            $user->lastname = $fields['lastname'];

            $user->country = $fields['country'];



            $user->has_monthly_fee = ($fields["monthly_fee"] != 0);

            $user->EmailAddress = $fields['EmailAddress'];
            $user->expirationDate = $fields['expiration'];
            $return->list[] = $user;
        }
        mysql_free_result($result);

        $return->query = $query;
        return $return;
    }

    function search_orders($query, $get_empty_cc = false) {
        global $cfg;
        $return = new umResult();

        // trim all inputs
        if (isset($query['selectedID']))
            $query['selectedID'] = NULL;
        if (isset($query['operation']))
            $query['operation'] = NULL;
        if ($query != NULL) {
            foreach ($query as $name => $value)
                $query[$name] = trim($value);
        }
        //echo "\n\n<!-- q: ".print_r($query,1)." -->\n\n";
        // get page and page size
        if (isset($query['page'])) {
            if (!is_numeric($query['page']))
                $query['page'] = 1;
        }
        if (isset($query['pageSize'])) {
            if (!is_numeric($query['pageSize']))
                $query['pageSize'] = 10;
        }else {
            $query['pageSize'] = 0;
        }

        // construction conditions
        $c = " WHERE 1";
        //	Order page

        if (isset($query['affiliateID'])) {
            if (is_numeric($query['affiliateID'])) {
                $c .= " AND u.hasoffers_aff_id = " . $query['affiliateID'];
            } else {
                $query['affiliateID'] = "";
            }
        } else {
            $query['affiliateID'] = "";
        }


        if (isset($query['fromID'])) {
            if (is_numeric($query['fromID'])) {
                $c .= " AND u.UserID >= " . $query['fromID'];
            } else {
                $query['fromID'] = "";
            }
        } else {
            $query['fromID'] = "";
        }
        if (isset($query['toID'])) {
            if (is_numeric($query['toID'])) {
                $c .= " AND u.UserID <= " . $query['toID'];
            } else {
                $query['toID'] = "";
            }
        } else {
            $query['toID'] = "";
        }

        if (isset($query['firstName'])) {
            if (strlen($query['firstName']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.firstname LIKE '%" . $query['firstName'] . "%'";
            } else {
                $query['firstName'] = "";
            }
        } else {
            $query['firstName'] = "";
        }
        if (isset($query['lastName'])) {
            if (strlen($query['lastName']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.lastname LIKE '%" . $query['lastName'] . "%'";
            } else {
                $query['lastName'] = "";
            }
        } else {
            $query['lastName'] = "";
        }

        if (isset($query['EmailAddress'])) {
            if (strlen($query['EmailAddress']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.EmailAddress LIKE '%" . $query['EmailAddress'] . "%'";
            } else {
                $query['EmailAddress'] = "";
            }
        } else {
            $query['EmailAddress'] = "";
        }


        if (isset($query['note'])) {
            if (strlen($query['note']) > 0) {
                if ($c == "") {
                    $c .= " WHERE";
                } else {
                    $c .= " AND";
                }
                $c .= " u.Memo LIKE '%" . $query['note'] . "%'";
            } else {
                $query['note'] = "";
            }
        } else {
            $query['note'] = "";
        }

        if (!$get_empty_cc) {
            if ($c == "") {
                $c .= " WHERE";
            } else {
                $c .= " AND";
            }
            $c .= " LENGTH(u.cardnumber)>0 ";
        }

        if (isset($query['fromCreateDate'])) {
            if (strlen($query['fromCreateDate']) > 0) {
                $c .= " AND u.CreateTime >= '" . $query['fromCreateDate'] . " 00:00:00'";
            }
        } else {
            $query['fromCreateDate'] = "";
        }
        if (isset($query['toCreateDate'])) {
            if (strlen($query['toCreateDate']) > 0) {
                $c .= " AND u.CreateTime <= '" . $query['toCreateDate'] . " 23:59:59'";
            }
        } else {
            $query['toCreateDate'] = "";
        }

        if (isset($query['fromShippedDate'])) {
            if (strlen($query['fromShippedDate']) > 0) {
                $c .= " AND u.shipped_to >= '" . $query['fromShippedDate'] . "'";
            } else {
                $query['fromShippedDate'] = "";
            }
        } else {
            $query['fromShippedDate'] = "";
        }
        if (isset($query['toShippedDate'])) {
            if (strlen($query['toShippedDate']) > 0) {
                $c .= " AND u.shipped_from <= '" . $query['toShippedDate'] . "'";
            }
        } else {
            $query['toShippedDate'] = "";
        }

        if (isset($query['cardNumber']) && $query['cardNumber'] != "") {
            
        } else {
            $query['cardNumber'] = "";
        }

        if (isset($query['cardName'])) {
            if (strlen($query['cardName']) > 0) {
                $c .= "  AND u.cardname like '%" . $this->Cipher->encrypt($query['cardName']) . "%'";
            }
        } else {
            $query['cardName'] = "";
        }

        if (isset($query['phone'])) {
            if (strlen($query['phone']) > 0) {
                $c .= "  AND u.phone like '%" . $query['phone'] . "%'";
            }
        } else {
            $query['phone'] = "";
        }

        //address

        if (isset($query['country'])) {
            if (strlen($query['country']) > 0) {
                $c .= " AND u.country like '" . $query['country'] . "'";
            }
        } else {
            $query['country'] = "";
        }

        if (isset($query['state'])) {
            if (strlen($query['state']) > 0) {
                $c .= " AND u.state like '" . $query['state'] . "'";
            }
        } else {
            $query['state'] = "";
        }
        if (isset($query['city'])) {
            if (strlen($query['city']) > 0) {
                $c .= " AND u.city like '" . $query['city'] . "%'";
            }
        } else {
            $query['city'] = "";
        }
        if (isset($query['address'])) {
            if (strlen($query['address']) > 0) {
                $c .= " AND u.address like '%" . $query['address'] . "%'";
            }
        } else {
            $query['address'] = "";
        }
        if (isset($query['postalcode'])) {
            if (strlen($query['postalcode']) > 0) {
                $c .= " AND u.postalcode like '" . $query['postalcode'] . "%'";
            } else {
                $query['postalcode'] = "";
            }
        } else {
            $query['postalcode'] = "";
        }
        /**
         * payment search
         */
        if (isset($query['fromPayDate'])) {
            if (strlen($query['fromPayDate']) > 0) {
                $c .= " AND mh.hDate>='" . $query['fromPayDate'] . " 00:00:00'";
            }
        } else {
            $query['fromPayDate'] = "";
        }
        if (isset($query['toPayDate'])) {
            if (strlen($query['toPayDate']) > 0) {
                $c .= " AND mh.hDate<='" . $query['toPayDate'] . " 00:00:00'";
            }
        } else {
            $query['toPayDate'] = "";
        }

        if (isset($query['fromRefundDate'])) {
            if (strlen($query['fromRefundDate']) > 0) {
                $c .= " AND mh.refunded_date>='" . $query['fromRefundDate'] . " 00:00:00'";
            }
        } else {
            $query['fromRefundDate'] = "";
        }
        if (isset($query['toRefundDate'])) {
            if (strlen($query['toRefundDate']) > 0) {
                $c .= " AND mh.refunded_date>='" . $query['toRefundDate'] . " 00:00:00'";
            }
        } else {
            $query['toRefundDate'] = "";
        }
        if (isset($query['pay_amount'])) {
            if (strlen($query['pay_amount']) > 0) {
                $c .= " AND mh.hAmount='" . $query['pay_amount'] . "'";
            }
        } else {
            $query['pay_amount'] = "";
        }


        // count result
        $sql = "SELECT COUNT(DISTINCT(u.UserID)) ";
        $sql .= "FROM " . $cfg['database']['prefix'] . "user AS u LEFT JOIN " . $cfg['database']['prefix'] . "merchant_history AS mh ON u.EmailAddress=mh.user_email ";

        $sql .= $c;
        $result = mysql_query($sql);
        $fields = mysql_fetch_array($result, MYSQL_NUM);
        mysql_free_result($result);
        $return->total = $fields[0];
        $return->totalPages = ceil($return->total / $return->pageSize);
        if ($query['pageSize'] == 0)
            $query['pageSize'] = $return->total; // if no page size assigned, try to return all
        if ($query['pageSize'] == 0)
            $query['pageSize'] = 10; // if no record and no page size assigned, set page size to 10, it does not matter anyway


            
// calucalate pages
        if (isset($query['page']))
            $return->page = $query['page'];
        if (isset($query['pageSize']))
            $return->pageSize = $query['pageSize'];

        if ($return->page > $return->totalPages)
            $return->page = 1;
        if ($return->page < 1)
            $return->page = 1;

        // search for result
        $return->orderBy = "UserID";
        $group_order = "mh.hKey";
        if (isset($query['orderBy'])) {
            if (strlen($query['orderBy']) > 0) {
                if (substr($query['orderBy'], 0, 1) == "u")
                    $return->orderBy = $query['orderBy'];
                else
                    $group_order = $query['orderBy'];
            }
        }

        $sql = "SELECT u.*, GROUP_CONCAT(concat_ws(',',mh.transaction_id,mh.hDate,mh.hAmount,mh.refunded_date,mh.transaction_description) ORDER BY $group_order SEPARATOR';') AS pay_data ";
        $sql .= "FROM " . $cfg['database']['prefix'] . "user AS u LEFT JOIN " . $cfg['database']['prefix'] . "merchant_history AS mh ON u.EmailAddress=mh.user_email ";

        $sql .= $c;
        $offset = $return->pageSize * ($return->page - 1);
        $sql .= " GROUP BY u.UserID ORDER BY " . $return->orderBy . ($query['cardNumber'] != "" ? "" : " LIMIT " . $offset . ", " . $return->pageSize);
        // execute sql and parse result
        //echo "<!-- ".$sql." -->\n\n";
        //card number search add 2011.5.13
        $i = 0;
        $result = mysql_query($sql);
        while ($fields = mysql_fetch_array($result)) {
            $cardNumber = $fields['cardnumber'] != '' ? $this->Cipher->decrypt($fields['cardnumber']) : '';
            $cardNumber = substr($cardNumber, 0, 4) . str_repeat("*", 2) . substr($cardNumber, -4);
            if ($query['cardNumber'] != "") {
                if (strpos($cardNumber, $query['cardNumber']) === false) {
                    $return->total--;
                    continue;
                }
                if ($i < $offset || $i > ($offset + $return->pageSize - 1)) {
                    $i++;
                    continue;
                } else {
                    $i++;
                }
            }

            $user = new umUser();
            $user->userID = $fields['UserID'];
            $user->email = $fields['Email'];
            $user->emailVerified = $fields['EmailVerified'];
            $user->verificationCode = $fields['VerificationCode'];
            $user->password = $fields['Password'];
            $user->createTime = $fields['CreateTime'];
            $user->lastLoginTime = $fields['LastLoginTime'];
            $user->lastLoginIP = $fields['LastLoginIP'];
            $user->loginCount = $fields['LoginCount'];
            $user->status = $fields['Status'];
            $user->memo = $fields['Memo'];
            $user->notes = $fields['notes'];
            $user->firstname = $fields['firstname'];
            $user->lastname = $fields['lastname'];

            $user->shippedFrom = $fields['shipped_from'];
            $user->shippedTo = $fields['shipped_to'];
//			$user->shipped = $fields['Shipped'];
            $user->cardNumber = $cardNumber;
            $user->cardName = ($fields['cardname'] != '' ? $this->Cipher->decrypt($fields['cardname']) : '');
            $user->phone = $fields['phone'];
            $user->EmailAddress = $fields['EmailAddress'];
            $user->country = $fields["country"];
            $user->state = $fields["state"];
            $user->city = $fields["city"];
            $user->address = $fields["address"];
            $user->postalcode = $fields["postalcode"];

            $user->has_monthly_fee = ($fields["monthly_fee"] != 0);

            $user->paymentData = $fields["pay_data"]; //array('pay_date'=>$fields["pay_date"], 'pay_amount'=>$fields["pay_amount"], 'refunded_date'=>$fields["refunded_date"]);
            $user->expirationDate = $fields['expiration'];

            $user->routing_number = $fields['routing_number'];
            $user->account_number = $fields['account_number'];
            $user->name_on_check = strlen($fields['name_on_check']) ? $this->Cipher->decrypt($fields['name_on_check']) : '';

            $user->hasoffers_offer_id = $fields["hasoffers_offer_id"];
            $user->hasoffers_aff_id = $fields["hasoffers_aff_id"];
            $user->fraudulent_flag = $fields["fraudulent_flag"];
            $user->fraudulent_investigated = $fields["fraudulent_investigated"];

            $return->list[] = $user;
        }
        mysql_free_result($result);
        $return->totalPages = intval($return->total / $return->pageSize);
        if ($return->total % $return->pageSize)
            $return->totalPages++;
        $return->query = $query;
        return $return;
    }

    function get_pssc_data($user_id = 0) {
        global $cfg;
        if ($user_id == 0)
            $user_id = $this->userID;
        $sql = "SELECT * FROM " . $cfg['database']['prefix'] . "user_psscdata WHERE UserID=$user_id";
        return single_query_assoc($sql);
    }

    function change_users_status($status, $userIDs) {
        global $cfg;
        $return = false;
        for ($i = 0; $i < count($userIDs); $i++) {
            if (!is_numeric($userIDs[$i]))
                $userIDs[$i] = 0;
        }
        $sql = "UPDATE " . $cfg['database']['prefix'] . "user SET ";
        $sql .= "Status=" . $status . " ";
        $sql .= "WHERE UserID IN (";
        for ($i = 0; $i < count($userIDs); $i++) {
            if ($i != 0)
                $sql .= ",";
            $sql .= $userIDs[$i];
        }
        $sql .= ")";
        if (mysql_query($sql)) {
            $return = true;
        }
        return $return;
    }

    function change_users_verification($verified, $userIDs) {
        global $cfg;
        $return = false;

        for ($i = 0; $i < count($userIDs); $i++) {
            if (!is_numeric($userIDs[$i]))
                $userIDs[$i] = 0;
        }
        $sql = "UPDATE " . $cfg['database']['prefix'] . "user SET ";
        $sql .= "EmailVerified=" . $verified . " ";
        $sql .= "WHERE UserID IN (";
        for ($i = 0; $i < count($userIDs); $i++) {
            if ($i != 0)
                $sql .= ",";
            $sql .= $userIDs[$i];
        }
        $sql .= ")";
        if (mysql_query($sql)) {
            $return = true;
        }
        return $return;
    }

    function assign_users_group($groupID, $userIDs, $assign = true) {
        global $cfg;
        $return = false;
        for ($i = 0; $i < count($userIDs); $i++) {
            if (!is_numeric($userIDs[$i]))
                $userIDs[$i] = 0;
        }
        if (!is_numeric($groupID))
            $groupID = 0;
        $sql = "DELETE FROM " . $cfg['database']['prefix'] . "user_group_mapping ";
        $sql .= "WHERE GroupID=" . $groupID . " AND (";
        for ($i = 0; $i < count($userIDs); $i++) {
            if ($i != 0)
                $sql .= " OR ";
            $sql .= "UserID=" . $userIDs[$i];
        }
        $sql .= ")";

        if (mysql_query($sql)) {
            if ($assign) {
                $sql = "INSERT INTO " . $cfg['database']['prefix'] . "user_group_mapping (UserID, GroupID) VALUES ";
                for ($i = 0; $i < count($userIDs); $i++) {
                    if ($i != 0)
                        $sql .= ",";
                    $sql .= "(" . $userIDs[$i] . ", " . $groupID . ")";
                }
                if (mysql_query($sql))
                    $return = true;
            }else {
                $return = true;
            }
        }
        return $return;
    }

    function check_groups($groupIDs) {
        global $cfg;
        $return = false;

        for ($i = 0; $i < count($groupIDs); $i++) {
            if (!is_numeric($groupIDs[$i]))
                $groupIDs[$i] = 0;
        }
        if (!is_numeric($this->userID))
            $this->userID = 0;

        if (count($groupIDs) > 0 && $this->userID > 0) {
            $sql = "SELECT COUNT(*) FROM " . $cfg['database']['prefix'] . "user_group_mapping ";
            $sql .= "WHERE UserID=" . $this->userID . " AND GroupID IN (";
            for ($i = 0; $i < count($groupIDs); $i++) {
                if ($i != 0)
                    $sql .= ", ";
                $sql .= $groupIDs[$i];
            }
            $sql .= ")";

            $result = mysql_query($sql);
            if ($fields = mysql_fetch_array($result, MYSQL_NUM)) {
                if ($fields[0] > 0)
                    $return = true;
            }
            mysql_free_result($result);
        }

        return $return;
    }

    function set_session($expire = 0) {
        global $cfg;
        $userCookieStr = $this->userID . '|' . md5($cfg['site']['cookieToken'] . $this->userID) . '|' . $this->loginCount . '|' . $this->lastLoginTime . '|' . $this->lastLoginIP;
        $authCookieStr = '';

        for ($i = 0; $i < count($this->belongToGroups); $i++) {
            if ($this->belongToGroups[$i]->status == 1) {
                $authCookieStr .= '.' . $this->belongToGroups[$i]->groupID . '-' . $this->belongToGroups[$i]->token . '.';
            }
        }

        //$userCookieStr = substr($userCookieStr, 0, 10);

        supersession($cfg['site']['cookiePrefix'] . "auth_user", $userCookieStr, $expire, "/");
        supersession($cfg['site']['cookiePrefix'] . "auth_groups", $authCookieStr, $expire, "/");

        /*
          if($cfg['site']['cookieDomain'] != ''){
          supersession($cfg['site']['cookiePrefix']."auth_user", $userCookieStr, $expire, "/", $cfg['site']['cookieDomain']);
          supersession($cfg['site']['cookiePrefix']."auth_groups", $authCookieStr, $expire, "/", $cfg['site']['cookieDomain']);
          }else{
          supersession($cfg['site']['cookiePrefix']."auth_user", $userCookieStr, $expire, "/");
          supersession($cfg['site']['cookiePrefix']."auth_groups", $authCookieStr, $expire, "/");
          } */
    }

    function get_session() {
        global $cfg;
        $return = false;
        $pw_reset = false;
        $zaret = false;
        //file_put_contents("pwwww1",$cfg['site']['root'] . $cfg['site']['folder']);
        $authcookie = $cfg['site']['cookiePrefix'] . 'auth_user';
        $auth_user = supersession($authcookie);
        //file_put_contents("pwwww1",print_r($_COOKIE,1));
        if ($auth_user != false) {
            $cookieValues = explode('|', $auth_user);
            if ($cookieValues[1] == md5($cfg['site']['cookieToken'] . $cookieValues[0])) {
                //file_put_contents("pwwww3",print_r($_COOKIE,1));
                //we also check if the password has been changed recently, and if so, kick out the user
                //only if admin!
                if (file_exists($cfg['site']['root'] . $cfg['site']['folder'] . "pw_reset_ott") && $cookieValues[0] == 100) {
                    // file_put_contents("pwwww4",print_r($_COOKIE,1));
                    //check if we have a reset cookie set
                    $reset_cookie = supersession("ottresetcookie");

                    //check the time the pw was last resetted
                    $time = file_get_contents($cfg['site']['root'] . $cfg['site']['folder'] . "pw_reset_ott");

                    if ($reset_cookie != false) {

                        //compare with the reset cookie
                        if ($reset_cookie != $time) {
                            //this is not the same reset              
                            $zaret = true;
                        }
                    } else {
                        //no reset cookie

                        if (!isset($_COOKIE['good_login'])) {
                            $zaret = true;
                        } else {
                            supersession("ottresetcookie", $time, time() + 3600 * 24 * 365, '/');
                        }
                    }



                    if ($zaret == true) {
                        supersession_clear();
                        supersession("ottresetcookie", $time, time() + 3600 * 24 * 365, '/');
                        redirect($cfg['site']['folder'] . "login.php");

                        //we resetted the pasword, so this user must re-login with the new pw
                        //in order to make the user re-login, we must remove the supercookie
                        supersession($authcookie, null);
                        //add the reset cookie, so when we login, we pass the tests
                        //kill the user ID as well
                        $this->userID = 0;
                        //set flag, the rest does not matter
                        $pw_reset = true;
                    }
                }

                if (!$pw_reset) {
                    $this->userID = $cookieValues[0];
                    $this->loginCount = $cookieValues[2];
                    if ($this->loginCount > 0) {
                        $this->lastLoginTime = $cookieValues[3];
                        $this->lastLoginIP = $cookieValues[4];
                    }
                    $return = true;
                }
            }

            //update the cookie as well
            //not gfor admin though
            if ($cookieValues[0] != 100)
                $this->set_client_exists_cookie();
        }

        return $return;
    }

    function reset_group_cookie() {
        global $cfg;

        if (!is_numeric($this->userID))
            $this->userID = 0;

        $authCookieStr = '';
        $sql = "SELECT g.GroupID, g.GroupTitle, g.DefaultGroup, g.Token, g.Status, g.Memo ";
        $sql .= "FROM " . $cfg['database']['prefix'] . "group g, " . $cfg['database']['prefix'] . "user_group_mapping m ";
        $sql .= "WHERE m.GroupID = g.GroupID AND m.UserID=" . $this->userID . " ORDER BY g.GroupID";
        $result = mysql_query($sql);
        while ($fields = mysql_fetch_array($result, MYSQL_NUM)) {
            $group = new umGroup();
            $group->groupID = $fields[0];
            $group->groupTitle = $fields[1];
            $group->defaultGroup = $fields[2];
            $group->token = $fields[3];
            $group->status = $fields[4];
            $group->memo = $fields[5];
            $authCookieStr .= '.' . $group->groupID . '-' . $group->token . '.';
        }
        mysql_free_result($result);
//		if($authCookieStr!=''){
//			if($cfg['site']['cookieDomain'] != ''){
//				setcookie($cfg['site']['cookiePrefix']."auth_groups", $authCookieStr, $expire, "/", $cfg['site']['cookieDomain']);
//			}else{
//				setcookie($cfg['site']['cookiePrefix']."auth_groups", $authCookieStr, $expire, "/");
//			}
//		}
    }

    function run_after_login($step = 0) {
        global $cfg;
        $sql = "SELECT M.value as url
				FROM " . $cfg['database']['prefix'] . "menu as M 
					INNER JOIN " . $cfg['database']['prefix'] . "menu_group_mapping AS G
					ON M.nor=G.MenuID AND M.value<>'' AND G.GroupID='" . $this->belongToGroups[0]->groupID . "'
				ORDER BY M.f1,M.f2,M.flag
				LIMIT 1";
        $row = single_query_assoc($sql);
        $nextScript = '';
        if (count($row)) {
            $nextScript = $row['url'];
        }
        return $nextScript;
        /*
          $nextScript = '';

          if(!is_numeric($step)) $step = 0;

          $sql = "SELECT RunID, Script FROM ".$cfg['database']['prefix']."after_login ";
          $sql .= "WHERE RunID > ".$step." ORDER BY RunID";
          $result = mysql_query($sql);
          if($fields = mysql_fetch_array($result, MYSQL_NUM)){
          $step = $fields[0];
          $nextScript = $fields[1].'?step='.$step;
          }
          mysql_free_result($result);
          //		if($this->expirationDate)
          return $nextScript;
         */
    }

    function run_after_logout($step = 0) {
        global $cfg;
        $nextScript = '';

        if (!is_numeric($step))
            $step = 0;

        $sql = "SELECT RunID, Script FROM " . $cfg['database']['prefix'] . "after_logout ";
        $sql .= "WHERE RunID > " . $step . " ORDER BY RunID";
        $result = mysql_query($sql);
        if ($fields = mysql_fetch_array($result, MYSQL_NUM)) {
            $step = $fields[0];
            $nextScript = $fields[1] . '?step=' . $step;
        }
        mysql_free_result($result);
        return $nextScript;
    }

    /**
     * get Date diff
     */
    function checkExpirationDate() {
        global $cfg;
        $adminTemplateAccess = explode(",", $cfg['group']['adminTemplateAccessGroupIds']);
        if (in_array($this->belongToGroups[0]->groupID, $adminTemplateAccess))
            return 1000;

        if ($this->belongToGroups[0]->groupID == $cfg['group']['bronze'])
            return 1000;

        $date = $this->lifetime;

        if ($date == "" || $date == "0000-00-00") {

//			$this->assign_groups(array($cfg['group']['bronze']));

            return 1000;
        }

        $cDate = explode("-", date("Y-n-d"));

        $date = explode("-", $date);



        $cTime = mktime(0, 0, 0, $cDate[1], $cDate[2], $cDate[0]);

        $dTime = mktime(0, 0, 0, $date[1], $date[2], $date[0]);

        if ($cTime > $dTime) {

            // $this->assign_groups(array($cfg['group']['bronze']));

            $return = 0;
        } else {

            $return = (int) ($dTime - $cTime) / 24 / 3600;

            $return = $return + 1;
        }

        return $return;
    }

    /*     * functions to check for the need for a start up email */

    function getSecondEmailUsers() {
        global $cfg;
        $sql = "SELECT EmailAddress as email FROM " . $cfg['database']['prefix'] . "user WHERE ADDTIME(CreateTime, '00:10:00') <= NOW() AND sent_mail = 0";
        $result = mysql_query($sql);
        if ($result && mysql_num_rows($result))
            return $result;
        return FALSE;
    }

    function SecondemailedTo($email) {
        global $cfg;
        $sql = "UPDATE " . $cfg['database']['prefix'] . "user SET sent_mail = 1 WHERE EmailAddress=\"" . $email . "\"";
        $result = mysql_query($sql);
        return TRUE;
    }

    //creates an account on a remote hosting site
    function create_remote_user($data, $where) {

        //get thge expiration
        $exp = explode("-", $data['expiration']);

        //arrange the data
        $d = array(
            "hash" => md5($data['firstname'] . "st4rtursite_jj3do0"),
            "firstname" => $data['firstname'],
            "lastname" => $data['lastname'],
            "creditcardno" => $data['cardnumber'],
            "expiration_year" => $exp[0],
            "expiration_month" => $exp[1],
            "securitycode" => $data['cvvcode'],
            "address1" => $data['address'],
            "address2" => "",
            "city" => $data['city'],
            "state" => $data['state'],
            "postcode" => $data['postalcode'],
            "country" => $data['country'],
            "phone" => $data['phone'],
            "email" => isset($data['emailAddress']) ? $data['emailAddress'] : $data['email'],
        );

        $call_data = array();
        foreach ($d as $k => $v) {
            $call_data[] = $k . "=" . $v;
        }

        $cd = implode("&", $call_data);

        //ready to make the call
        if (strlen($where)) {
            //endpoint is defined, it seems
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $where);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $cd);

            $response = curl_exec($ch);
            return $response;
        }

        return false;
    }

    //this function returns only one, so we don't get all the 
    //charges bundled up   
    function get_day_monthly_fee_users($date = "") {

        if (!strlen($date))
            $date = date("Y-m-d H:i:s");

        //build the sql
        $sql = "SELECT * from mem_user WHERE monthly_fee>0 AND last_payment>='2012-11-01 00:00:00' AND DATE_ADD(last_payment,INTERVAL 2 MONTH)<='" . $date . "' LIMIT 1";
        //
        //CUSTOM DATE
        //$sql = "SELECT * from mem_user WHERE monthly_fee>0 AND `last_payment`>='2012-04-01 00:00:00' AND `last_payment`<='2012-04-19 23:59:59' LIMIT 1";


        $res = mysql_query($sql);

        $r = array();

        while ($row = mysql_fetch_assoc($res)) {
            $r[] = $row;
        }

        return $r;
    }

    function get_monthly_fee_delayed() {

        $date = date("Y-m-d H:i:s");
        $sql = "SELECT * from mem_user WHERE monthly_fee>0 AND last_payment>='2012-04-20 00:00:00' AND DATE_ADD(last_payment,INTERVAL 7 DAY)<='" . $date . "' LIMIT 1";

        $res = mysql_query($sql);

        $r = array();

        while ($row = mysql_fetch_assoc($res)) {
            $r[] = $row;
        }

        return $r;
    }

    function get_monthly_fee_from_may_2011() {

        $date = date("Y-m-d H:i:s");
        $sql = "SELECT * from mem_user WHERE monthly_fee>0 AND last_payment>='2011-04-01 00:00:00' AND DATE_ADD(last_payment,INTERVAL 30 DAY)<='" . $date . "' and has_site=0 and country IN('US','GB','UK','CA') LIMIT 1";

        $res = mysql_query($sql);

        $r = array();

        while ($row = mysql_fetch_assoc($res)) {
            $r[] = $row;
        }

        return $r;
    }

    function set_monthly_fee_payed($email) {

        $sql = "update mem_user set last_payment=DATE_ADD(last_payment,INTERVAL 2 MONTH) where EmailAddress='" . $email . "' LIMIT 1";

        if (mysql_query($sql)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function set_domain_created($email) {

        $sql = "update mem_user set has_site=1 where EmailAddress='" . $email . "' LIMIT 1";

        if (mysql_query($sql)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function remove_monthly_fee($email) {

        $sql = "update mem_user set last_payment=NULL,monthly_fee=0 where EmailAddress='" . $email . "' LIMIT 1";

        if (mysql_query($sql)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function remove_monthly_fee_by_id($id) {

        $sql = "update mem_user set last_payment=NULL,monthly_fee=0 where UserID='" . $id . "' LIMIT 1";

        if (mysql_query($sql)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function get_monthly_fee_calendar() {
        $sql = "select DATE_ADD(last_payment,INTERVAL 1 MONTH)  as d,emailAddress as e,concat(firstname,' ',lastname) as n,monthly_fee as fee,user_ip,country,state from mem_user where monthly_fee>0 order by d asc";

        $r = mysql_query($sql);

        $rr = array();

        while ($row = mysql_fetch_assoc($r)) {
            $rr[] = $row;
        }

        return $rr;
    }

}

class umGroup {

    var $groupID = 0;
    var $groupTitle = "";
    var $defaultGroup = 0;
    var $token = "";
    var $status = 0;
    var $memo = "";
    var $price = 0;
    var $upkeep = 0;

    function get_group() {

        global $cfg;

        $return = false;



        if (!is_numeric($this->groupID))
            $this->groupID = 0;



        $sql = "SELECT GroupID, GroupTitle, DefaultGroup, Token, Status, Memo, Price, Upkeep ";

        $sql .= "FROM " . $cfg['database']['prefix'] . "group ";

        if ($this->groupID == 0) {

            $sql .= "WHERE GroupTitle='" . trim($this->groupTitle) . "'";
        } else {

            $sql .= "WHERE GroupID=" . $this->groupID;
        }



        $result = mysql_query($sql);



        if ($fields = mysql_fetch_array($result, MYSQL_NUM)) {

            $this->groupID = $fields[0];

            $this->groupTitle = $fields[1];

            $this->defaultGroup = $fields[2];

            $this->token = $fields[3];

            $this->status = $fields[4];

            $this->memo = $fields[5];

            $this->price = $fields[6];

            $this->upkeep = $fields[7];

            $return = true;
        }

        mysql_free_result($result);



        return $return;
    }

    /**

     * Get Next Group

     */
    function get_next_group() {

        global $cfg;

        if (!is_numeric($this->memo))
            return array();

        if (!is_numeric($this->groupID))
            $this->groupID = 0;



        $sql = "SELECT GroupID, GroupTitle, DefaultGroup, Token, Status, Memo, Price, Upkeep ";

        $sql .= "FROM " . $cfg['database']['prefix'] . "group ";

        if ($this->groupID == 0) {

            $sql .= "WHERE Memo>'0'";
        } else {

            $sql .= "WHERE Memo>" . $this->memo;
        }

        $sql .= " ORDER BY Memo ASC LIMIT 0,1";



        $result = mysql_query($sql);

        $reAry = array();



        if ($fields = mysql_fetch_array($result, MYSQL_NUM)) {

            $reAry['groupID'] = $fields[0];

            $reAry['groupTitle'] = $fields[1];

            $reAry['defaultGroup'] = $fields[2];

            $reAry['token'] = $fields[3];

            $reAry['statu'] = $fields[4];

            $reAry['memo'] = $fields[5];

            $reAry['price'] = $fields[6];

            $reAry['upkeep'] = $fields[7];
        }

        mysql_free_result($result);

        return $reAry;
    }

    function create_group() {

        global $cfg;

        $return = false;

        $sql = "INSERT INTO " . $cfg['database']['prefix'] . "group ";

        $sql .= "(GroupTitle, DefaultGroup, Token, Status, Memo, Price, Upkeep) VALUES (";

        $sql .= "'" . db_escape_characters($this->groupTitle) . "', ";

        $sql .= $this->defaultGroup . ", ";

        $sql .= "'" . md5(uniqid(rand(), true)) . "', ";

        $sql .= $this->status . ", ";

        $sql .= "'" . db_escape_characters($this->memo) . "', ";

        $sql .= $this->price . ", ";

        $sql .= $this->upkeep . ")";



        if (mysql_query($sql)) {

            $this->groupID = mysql_insert_id();

            if ($this->groupID > 0)
                $return = true;
        }



        return $return;
    }

    function update_group() {

        global $cfg;

        $return = false;

        if (!is_numeric($this->groupID))
            $this->groupID = 0;

        $sql = "UPDATE " . $cfg['database']['prefix'] . "group SET ";

        $sql .= "GroupTitle='" . db_escape_characters($this->groupTitle) . "', ";

        $sql .= "DefaultGroup=" . $this->defaultGroup . ", ";

        $sql .= "Status=" . $this->status . ", ";

        $sql .= "Memo='" . db_escape_characters($this->memo) . "', ";

        $sql .= "Price='" . $this->price . "', ";

        $sql .= "Upkeep='" . $this->upkeep . "' ";

        $sql .= "WHERE GroupID=" . $this->groupID;

        if (mysql_query($sql)) {

            $return = true;
        }

        return $return;
    }

    function search_groups($query) {

        global $cfg;



        $return = new umResult();



        // trim all inputs

        if (isset($query['selectedID']))
            $query['selectedID'] = NULL;

        if (isset($query['operation']))
            $query['operation'] = NULL;

        if ($query != NULL) {

            foreach ($query as $name => $value)
                $query[$name] = trim($value);
        }



        // get page and page size

        if (isset($query['page'])) {

            if (!is_numeric($query['page']))
                $query['page'] = 1;
        }

        if (isset($query['pageSize'])) {

            if (!is_numeric($query['pageSize']))
                $query['pageSize'] = 10;
        }else {

            $query['pageSize'] = 0;
        }



        // construction conditions

        $c = "";

        if (isset($query['fromID'])) {

            if (is_numeric($query['fromID'])) {

                if ($c == "") {

                    $c .= " WHERE";
                } else {

                    $c .= " AND";
                }

                $c .= " GroupID >= " . $query['fromID'];
            } else {

                $query['fromID'] = "";
            }
        } else {

            $query['fromID'] = "";
        }

        if (isset($query['toID'])) {

            if (is_numeric($query['toID'])) {

                if ($c == "") {

                    $c .= " WHERE";
                } else {

                    $c .= " AND";
                }

                $c .= " GroupID <= " . $query['toID'];
            } else {

                $query['toID'] = "";
            }
        } else {

            $query['toID'] = "";
        }

        if (isset($query['keywords'])) {

            if (strlen($query['keywords']) > 0) {

                if ($c == "") {

                    $c .= " WHERE";
                } else {

                    $c .= " AND";
                }

                $keywords = explode(" ", $query['keywords']);

                $c .= " (";

                for ($i = 0; $i < sizeof($keywords); $i++) {

                    if ($i != 0)
                        $c .= " OR";

                    $c .= " GroupTitle LIKE '%" . db_escape_characters($keywords[$i]) . "%'";
                }

                $c .= " )";
            }
        }else {

            $query['keywords'] = "";
        }

        if (isset($query['defaultGroup'])) {

            if (is_numeric($query['defaultGroup'])) {

                if ($c == "") {

                    $c .= " WHERE";
                } else {

                    $c .= " AND";
                }

                $c .= " DefaultGroup = " . $query['defaultGroup'];
            }
        } else {

            $query['defaultGroup'] = "-";
        }

        if (isset($query['status'])) {

            if (is_numeric($query['status'])) {

                if ($c == "") {

                    $c .= " WHERE";
                } else {

                    $c .= " AND";
                }

                $c .= " Status = " . $query['status'];
            }
        } else {

            $query['status'] = "-";
        }





        // count result

        $sql = "SELECT COUNT(*) ";

        $sql .= "FROM " . $cfg['database']['prefix'] . "group" . $c;

        $result = mysql_query($sql);

        $fields = mysql_fetch_array($result, MYSQL_NUM);

        mysql_free_result($result);

        $return->total = $fields[0];

        if ($query['pageSize'] == 0)
            $query['pageSize'] = $return->total; // if no page size assigned, try to return all

        if ($query['pageSize'] == 0)
            $query['pageSize'] = 10; // if no record and no page size assigned, set page size to 10, it does not matter anyway




            
// calucalate pages

        if (isset($query['page']))
            $return->page = $query['page'];

        if (isset($query['pageSize']))
            $return->pageSize = $query['pageSize'];

        $return->totalPages = intval($return->total / $return->pageSize);

        if ($return->total % $return->pageSize)
            $return->totalPages++;

        if ($return->page > $return->totalPages)
            $return->page = $return->totalPages;

        if ($return->page < 1)
            $return->page = 1;



        // search for result

        $sql = "SELECT GroupID, GroupTitle, DefaultGroup, Token, Status, Memo, Price, Upkeep ";

        $sql .= "FROM " . $cfg['database']['prefix'] . "group" . $c;

        $return->orderBy = "GroupID";

        if (isset($query['orderBy'])) {

            if (strlen($query['orderBy']) > 0)
                $return->orderBy = $query['orderBy'];
        }

        $offset = $return->pageSize * ($return->page - 1);

        $sql .= " ORDER BY " . $return->orderBy . " LIMIT " . $offset . ", " . $return->pageSize;



        // execute sql and parse result

        $result = mysql_query($sql);

        while ($fields = mysql_fetch_array($result, MYSQL_NUM)) {

            $group = new umGroup();

            $group->groupID = $fields[0];

            $group->groupTitle = $fields[1];

            $group->defaultGroup = $fields[2];

            $group->token = $fields[3];

            $group->status = $fields[4];

            $group->memo = $fields[5];

            $group->price = $fields[6];

            $group->upkeep = $fields[7];

            $return->list[] = $group;
        }

        mysql_free_result($result);



        $return->query = $query;

        return $return;
    }

    function change_groups_status($status, $groupIDs) {

        global $cfg;

        $return = false;

        for ($i = 0; $i < count($groupIDs); $i++) {

            if (!is_numeric($groupIDs[$i]))
                $groupIDs[$i] = 0;
        }

        $sql = "UPDATE " . $cfg['database']['prefix'] . "group SET ";

        $sql .= "Status=" . $status . " ";

        $sql .= "WHERE GroupID IN (";

        for ($i = 0; $i < count($groupIDs); $i++) {

            if ($i != 0)
                $sql .= ",";

            $sql .= $groupIDs[$i];
        }

        $sql .= ")";

        if (mysql_query($sql)) {

            $return = true;
        }

        return $return;
    }

    function change_users_status($status, $groupIDs) {

        global $cfg;

        $return = false;

        for ($i = 0; $i < count($groupIDs); $i++) {

            if (!is_numeric($groupIDs[$i]))
                $groupIDs[$i] = 0;
        }

        $sql = "UPDATE " . $cfg['database']['prefix'] . "user u, " . $cfg['database']['prefix'] . "user_group_mapping m SET ";

        $sql .= "u.Status=" . $status . " ";

        $sql .= "WHERE u.UserID = m.UserID AND m.GroupID IN (";

        for ($i = 0; $i < count($groupIDs); $i++) {

            if ($i != 0)
                $sql .= ",";

            $sql .= $groupIDs[$i];
        }

        $sql .= ")";

        if (mysql_query($sql)) {

            $return = true;
        }

        return $return;
    }

    /**

     * Change User Groups

     */
    function change_users_group($userID, $groupID) {

        global $cfg;

        $return = false;

        $sql = "UPDATE " . $cfg['database']['prefix'] . "user_group_mapping SET ";

        $sql .= "GroupID=" . $groupID . " ";

        $sql .= "WHERE UserID='" . $userID . "'";

        if (mysql_query($sql)) {

            $return = true;
        }

        return $return;
    }

    /**

     * get GoldGroup

     */
//	function get_goldGroup(){
//		global $cfg;
//		$return = 0;
//		$sql = "SELECT GroupID FROM ".$cfg['database']['prefix']."group  WHERE memo='".$cfg['group']['gold']."'";
//		$rst = mysql_query($sql);
//		if($row=mysql_fetch_array($rst, MYSQL_NUM)){
//			$return = $row[0]; 
//		}
//		return $return;
//		
//	}
}

?>