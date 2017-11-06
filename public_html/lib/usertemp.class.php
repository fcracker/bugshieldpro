<?php
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "mcrypt.class.php");

if(!function_exists("supersession")) 
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR ."session.php");

class umUserTemp{
	var $email = "";
  var $ip = "";
	var $Cipher;
	function __construct(){
		$this->Cipher = new Cipher();
		connect_database();
	}
	function getUser($isAll=false){
		global $cfg;
		$sql = "SELECT id,email, firstname, lastname, phone, country, state, city, address, postalcode, 
				cardtype, cardnumber, cardname, expiration, cvvcode, tempid, lifetime, days, alt, registered_time,monthly_fee  
				FROM ".$cfg['database']['prefix']."user_temp ";
				
		if(!$isAll) $sql.= "WHERE Email='" . $this->email . "'";
		
		$return = multi_query_assoc($sql);
		if(!count($return)) return array();
		for($i=0;$i<count($return);$i++){
			foreach($return[$i] as $key => $value){
				if(in_array($key,array("cardnumber","cardname","cvvcode","routing_number","account_number","name_on_check")))
					$return[$i][$key] = $this->Cipher->decrypt($value);
			}
		}
		if(!$isAll) $return = $return[0];
		return $return;
	}
	
	function isExist(){
		global $cfg;
		$sql = "SELECT Email
				FROM ".$cfg['database']['prefix']."user_temp 
				WHERE Email='" . $this->email . "'";
		if(count(single_query_assoc($sql))) return true;
		return false;
	}
	
	function setUser($User){
		global $cfg;
		
		$sql = "SELECT * FROM " . $cfg['database']['prefix'] . "user_temp WHERE tempid='" . $User["tempid"] . "'";
		$result = mysql_query($sql);
		if (mysql_num_rows($result)) {
			$this->updateUser($User);
			return;
		}
		
		$sql = "INSERT INTO	".$cfg['database']['prefix']."user_temp SET  
				Email=\"".mysql_escape_string($User["email"])."\",
				firstname=\"".mysql_escape_string($User["firstname"])."\",
				lastname=\"". mysql_escape_string($User["lastname"])."\",
				phone=\"". mysql_escape_string($User["phone"])."\",
				country=\"".$User["country"]."\",
				state=\"".$User["state"]."\",
				city=\"".$User["city"]."\",
				address=\"".$User["address"]."\",
				postalcode=\"".$User["postalcode"]."\",
				cardtype=\"".$User["cardtype"]."\",
				cardnumber=\"".$this->Cipher->encrypt($User["cardnumber"])."\",
				cardname=\"".$this->Cipher->encrypt($User["cardname"])."\",
				expiration=\"".$User["expiration"]."\",
				cvvcode=\"".$this->Cipher->encrypt($User["cvvcode"])."\",
				tempid=\"".$User["tempid"]."\",
				days=\"3650\",
				alt=\"". $User["alt"]."\",
				ip=\"". $_SERVER['REMOTE_ADDR']."\",
				registered_time=NOW()";
			
			if(isset($User["routing_number"]))  $sql.=",routing_number='".$this->Cipher->encrypt($User["routing_number"])."'";
			if(isset($User["account_number"])) $sql.=",account_number='".$this->Cipher->encrypt($User["account_number"])."'";
			if(isset($User["name_on_check"])) $sql.=",name_on_check='".$this->Cipher->encrypt($User["name_on_check"])."'";
			
			if($User["payment_method"]=="check") $sql.=",is_check='1'";			

		mysql_query($sql);
    
	$temp_id = mysql_insert_id();
    
    //since temp users are created only when a bronze membership is set, and updated on membership change
    //so, when a user gets bronze, this code is reached, and then never again for the same email
    
    $entrypoint =  supersession("entrypoint");
    
    if($entrypoint!==false) {
      //we have an entry point registered
      $query =$cfg['database']['prefix']."stats SET ";
      $query.= "`datetime`=NOW()";
      $query.= ",`action_type`='sale'";
      $query.= ",`user_email`='".mysql_escape_string($User["email"])."'";
      $query.= ",`entry_page`='".$entrypoint."'";
      $query.= ",`membership_status`='bronze'";
      
      //check if we already have the user (maybe he tried another entrypoint in the past)
      $_test = mysql_query("SELECT id from ".$cfg['database']['prefix']."stats where user_email='".mysql_escape_string($User["email"])."' limit 1");
      
      if(mysql_num_rows($_test)) {
        //update
        $query = "UPDATE ".$query." where user_email='".mysql_escape_string($User["email"])."'";
      } else {
        //insert
        $query = "INSERT INTO ".$query;
      }
      
      mysql_query($query);
    }
	
	return $temp_id;
    
	}
	
	function updateUser($User){
		global $cfg;
		$sql = "UPDATE " . $cfg['database']['prefix'] . "user_temp SET  
				Email=\"".mysql_escape_string($User["email"])."\",
				firstname=\"".mysql_escape_string($User["firstname"])."\",
				lastname=\"". mysql_escape_string($User["lastname"])."\",
				phone=\"". mysql_escape_string($User["phone"])."\",
				country=\"".$User["country"]."\",
				state=\"".$User["state"]."\",
				city=\"".$User["city"]."\",
				address=\"".$User["address"]."\",
				postalcode=\"".$User["postalcode"]."\",
				cardtype=\"".$User["cardtype"]."\",
				cardnumber=\"".$this->Cipher->encrypt($User["cardnumber"])."\",
				cardname=\"".$this->Cipher->encrypt($User["cardname"])."\",
				expiration=\"".$User["expiration"]."\",
				cvvcode=\"".$this->Cipher->encrypt($User["cvvcode"])."\",
				registered_time=NOW()
				WHERE tempid='" . $User["tempid"] . "'";
		mysql_query($sql);
	}
	
	
	
	function deleteUser(){
		global $cfg;
		$sql = "DELETE FROM ".$cfg['database']['prefix']."user_temp 
				WHERE Email='" . $this->email . "'";
		mysql_query($sql);
	}
	
	function getUsersToDelete() {
		global $cfg;
		$current_time = date("Y-m-d H:i:s");
		$sql = "SELECT email FROM " . $cfg['database']['prefix']."user_temp WHERE ADDTIME(registered_time, '00:10:00') <= NOW() AND emailed_to = 0";
		$result = mysql_query($sql);
		if($result && mysql_num_rows($result)) return $result;
		return FALSE;
	}
	
	function emailedTo($email) {
		global $cfg;
		$sql = "UPDATE " . $cfg['database']['prefix'] . "user_temp SET emailed_to = 1 WHERE Email=\"" . $email . "\"";
		$result = mysql_query($sql);
		return TRUE;
	}

	function setMembership($months) {
		global $cfg;
		/*
			$membership:
				12 : gold 1 year
				6 : gold 6 months
		*/
		$sql = "UPDATE " . $cfg['database']['prefix']."user_temp SET lifetime=$months WHERE Email=\"" . $this->email . "\"";
		
		$result = mysql_query($sql);
    
    //update stats,should complement with a bronze action
    if($months>=12) {//gold
    
      //check if the user has bronze action
      $_test = mysql_query("SELECT id from ".$cfg['database']['prefix']."stats where user_email='".$this->email."' limit 1");
      
      if(mysql_num_rows($_test)) {
        //update the membership status
        $query = "UPDATE ".$cfg['database']['prefix']."stats SET membership_status='gold' where user_email='".$this->email."'";
        mysql_query($query);
      }
    
    }
    
		return TRUE;
	}
  
  function setMonthlyPayment($value) {
		global $cfg;
    
		$sql = "UPDATE " . $cfg['database']['prefix']."user_temp SET monthly_fee='".$value."' WHERE Email=\"" . $this->email . "\"";
		
		$result = mysql_query($sql);
    
		return TRUE;
	}
}

?>