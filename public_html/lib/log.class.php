<?php
/**
* This class logs all incoming traffic, being helped by an cookie
*
*/

//make sure we have our stuff
include_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__).'/database.inc.php');//database connection

class Logger {


static public function get_stamp($create_if_not_exist=false){

//can se set session(cookie/session) ?
 if(!function_exists("supersession")) 
    require_once(dirname(__FILE__)."/session.php");
    
    $value = supersession("__system_");
    
   if($value==false) {
   
      if($create_if_not_exist==true) {
    
      //are we logged in ?
      if(!class_exists("umUser")){
        //bring it in
        require_once(dirname(__FILE__)."/user.class.php");
      }
      
      $user_instance = new umUser();  
      
      
      if($user_instance->get_session()) {
        //there is a user, save the ID
        $user_id = $user_instance->userID;
        
        $con = connect_database(); //connect to the database first 
        
        //check if we already have an assigned cookie
        $sql_check = "select stamp from mem_log where user_id=".$user_id." LIMIT 1";
        $result_check = mysql_query($sql_check);
        
        if(mysql_num_rows($result_check)) {
          $row_check = mysql_fetch_object($result_check);
          $value = $row_check->stamp;
        }
        
      }
      
      
      if($value==false) {
      
        $value = md5(time());
      
      }
      
      //create it
      supersession("__system_",$value,time() + 3600 * 24 * 365);
      
      }
    
    }

  return $value;    

}


static public function log($type="pagehit"){

  //get out of cron transactions
  if("174.133.214.195"==$_SERVER['REMOTE_ADDR']) {
    return false;
  }

  $create_if_not_exists = ($type=="transaction") ? true:false;
  
  //create entries just after a transaction
  $stamp = Logger::get_stamp($create_if_not_exists);
  
  //if no stamp, bail
  if($stamp==false) {
    return false;
  }
  
  $page = $_SERVER['REQUEST_URI'].(strlen($_SERVER["QUERY_STRING"]) ? "?".$_SERVER["QUERY_STRING"]:"");
  
  $ip = $_SERVER['REMOTE_ADDR'];
  
  $browser = $_SERVER['HTTP_USER_AGENT'];
  
  
  $cook = $_COOKIE;
  
  //remove some 
  if(is_array($cook) && count($cook)) {
  
    if(isset($cook['PHPSESSID'])) unset($cook['PHPSESSID']);
    if(isset($cook['__system_'])) unset($cook['__system_']);//ours
   // if(isset($cook['entrypoint'])) unset($cook['entrypoint']);
    if(isset($cook['is_365_index'])) unset($cook['is_365_index']);
  
  } else {
    $cook = array();
  }
  
  $cookies = count($cook) ? serialize($cook):"";
  
    //user ?
    if(!class_exists("umUser")){
        //bring it in
        require_once(dirname(__FILE__)."/user.class.php");
      }
      
      $user_instance = new umUser();      
      
      
      if($user_instance->get_session()) {
        //there is a user, save the ID
        $user_id = $user_instance->userID;
      } else {
        $user_id = 0;
      }      
  
  //build the query
  $query = "INSERT INTO mem_log SET ".
          "user_id=".($user_id>0 ? $user_id:"NULL").
          ",action_time=NOW()".
          ",page='".$page."'".
          ",type='".$type."'".
          ",ip='".$ip."'".
          ",browser='".$browser."'".
          ",cookies='".$cookies."'".
          ",stamp='".$stamp."'";
          
  $con = connect_database(); //connect to the database first         
  
  if(mysql_query($query)) {
    return true;
  }  
  
  return false; 

}

public function get_latest_activity($page=0,$page_size=15) {

$sql = "select * from mem_log order by action_time desc LIMIT ".($page*$page_size).",".$page_size;
$result = mysql_query($sql);

$r = array();
/*
if(mysql_num_rows($result)) {
	while($row = mysql_fetch_object)
}
*/
	
}




}