<?php
/*
 * Country Object (2010-10-13)
 * this object is for get country with IP
 */

class umCountry{
	var $IP = "";		//	IP String
	var $IP_number = 0;	//	IP Number
	
	function getRealIpAddr(){
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
	    return $ip;
	}
	
	function set_ip($ip_string=""){
		if($ip_string=="") $this->IP = $this->getRealIpAddr();
		else $this->IP = $ip_string;
		$ip_string = explode(".", $this->IP);
		$this->IP_number = (int)$ip_string[3]+(int)$ip_string[2]*256+(int)$ip_string[1]*256*256+(int)$ip_string[0]*256*256*256;
	}
	
	function get_ip($is_number = true){
		if($is_number) return $this->IP_number;
		return $this->IP;
	}
	
	function get_country_iso(){
		global $cfg;
		$sql = "SELECT ISO2 ";
		$sql .= "FROM ".$cfg['database']['prefix']."ip_area ";
		$sql .= "WHERE fromIP<=".$this->IP_number." AND toIP>=".$this->IP_number." ";
		$sql .= "LIMIT 0,1";
		$result = mysql_query($sql);
		$return = "";
		if(mysql_num_rows($result)){
			$fields = mysql_fetch_array($result, MYSQL_NUM);
			$return = $fields[0];
		}
		mysql_free_result($result);
		return $return;
	}
}

?>