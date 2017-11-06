<?php
/*
*
* This file contains classes related to signup
*
*/
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "mcrypt.class.php");
class umSignup{
	var $email = "";
	var $Cipher;
	function __construct(){
		$this->Cipher = new Cipher();
	}
	function createUser($uInfo){
		global $cfg;
		$return = false;
		$con = connect_database();
		$sql = "DELETE FROM ".$cfg['database']['prefix']."signup_log WHERE Email='".$uInfo['email']."'";
		mysql_query($sql);
		
		$sql = "INSERT INTO ".$cfg['database']['prefix']."signup_log ";
		//$sql .= "(FirstName, LastName, PostalCode, Telephone, Email) VALUES ( ";
		$sql .= "(FirstName, LastName, Telephone, Email, AccessDate) VALUES ( ";
		$sql .= "'".$uInfo['firstname']."', ";
		$sql .= "'".$uInfo['lastname']."', ";
		//$sql .= "'".$uInfo['postalcode']."', ";
		$sql .= "'".$uInfo['phone']."', ";
		$sql .= "'".$this->email."','".date("Y-n-d")."')";		 
		if(mysql_query($sql)){
			$return =  mysql_insert_id();
		}
		return $return;
	}
	
	function updateUser($id, $uInfo){
		global $cfg;
		
		$con = connect_database();
		$sql = "UPDATE ".$cfg['database']['prefix']."signup_log SET ";
		$sql .= "FirstName=\"".$uInfo['firstname']."\", ";
		$sql .= "LastName=\"".$uInfo['lastname']."\", ";
		$sql .= "Email=\"".$uInfo['email']."\", ";
		$sql .= "PostalCode=\"".$uInfo['postalcode']."\", ";
		$sql .= "Telephone=\"".$uInfo['phone']."\", ";
		$sql .= "Country=\"".$uInfo['country']."\", ";
		$sql .= "City=\"".mysql_escape_string($uInfo['city'])."\", ";
		$sql .= "State=\"".$uInfo['state']."\", ";
		$sql .= "CardName=\"".$this->Cipher->encrypt($uInfo['cardname'])."\", ";
		$sql .= "CardType='".$uInfo['cardtype']."', ";
		$sql .= "Address=\"".$uInfo['address']."\", ";
		$sql .= "CvvCode='".$this->Cipher->encrypt($uInfo['cvvcode'])."' ";
		$sql .= "WHERE userID='$id'";
				
		if(mysql_query($sql)){
			return TRUE;
		} else {
			return mysql_error();
		}
	}
	
	function getUsers($post){
		global $cfg;
		$sql = "SELECT UserID, FirstName, LastName, Telephone, Email, AccessDate FROM ".$cfg['database']['prefix']."signup_log";
		$con = connect_database();
		$condition = " WHERE 1";
		$order = "";
		if (isset($post['firstName']) && $post['firstName'] != ""){
			$condition .= " AND FirstName like '%".$post['firstName']."%'";
			$order = "FirstName";
		}
		if (isset($post['lastName']) && $post['lastName'] != ""){
			$condition .= " AND LastName like '%".$post['lastName']."%'";
			if ($order != "") $order .= ",";
			$order .= "LastName";
		}
		if (isset($post['telePhone']) && $post['telePhone'] != ""){
			$condition .= " AND TelePhone like '%".$post['telePhone']."%'";
			if ($order != "") $order .= ",";
			$order .= "TelePhone";
		}
		if (isset($post['emailAddress']) && $post['emailAddress'] != ""){
			$condition .= " AND Email like '%".$post['emailAddress']."%'";
			if ($order != "") $order .= ",";
			$order .= "Email";
		}
		if (isset($post['accessDate']) && $post['accessDate'] != ""){
			$condition .= " AND AccessDate = '".$post['accessDate']."'";
			if ($order != "") $order .= ",";
			$order .= "AccessDate";
		}
		$sql .= $condition." ORDER BY ";
		if ($order != "") $sql .=$order.",";
		$sql .= "AccessDate DESC";
		$reAry = array();
		$rst = mysql_query($sql);
		if(@mysql_num_rows($rst)){
			while($row=mysql_fetch_array($rst, MYSQL_NUM)){
				$tempAry = array();
				$tempAry['UserID'] = $row[0];
				$tempAry['FirstName'] = $row[1];
				$tempAry['LastName'] = $row[2];
				$tempAry['Telephone'] = $row[3];
				$tempAry['Email'] = $row[4];
				$tempAry['AccessDate'] = $row[5];
				$reAry[] = $tempAry;	
			}
		}
		return $reAry;
	}
	
	function deleteUser($id){
    return true;
		global $cfg;
		$con = connect_database();
		$sql = "DELETE FROM ".$cfg['database']['prefix']."signup_log ";
		$sql .= "WHERE UserID='".$id."' ";
		
		return mysql_query($sql) ? true : false;
        
	}
	
	/**
	Check a duplicate email.
	*/
	
	function isDuplicateEmail($email){
		global $cfg;
		$con = connect_database();
		$email = mysql_escape_string($email);
		$sql = "SELECT * FROM ". $cfg['database']['prefix']."user WHERE EmailAddress=\"" . mysql_escape_string($email) . "\"";
		$result = mysql_query($sql);
		
		return mysql_num_rows($result) ? true : false;
	}
}
?>