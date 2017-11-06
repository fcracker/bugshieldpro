<?php

//do not do any of this if we are on the bank path
if(!defined('BASE_PATH') || defined('SPECIAL_STATE')) {
 
//check if we even need to pre-select
$pre_select_cookie = 'bgcookie8';
$pre_select = supersession($pre_select_cookie);
$expiration = time() + (365*24*60*60);//1 year from now

if($pre_select === false || defined('SPECIAL_STATE')) {
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "database.inc.php");

//choose a merchant account for this user if not defined yet
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PayBackEnd.php');

  global $cfg;
	
  $con = connect_database();
  
 if(can_preselect_merchant()) {
    
  //pre-select and save it in a cookie
  $paybackend = new PayBackEnd();
  
  if(defined('SPECIAL_STATE')) {
      $random_merchant = false;
      for($i=0;$i<5;$i++) {
          $random_merchant = $paybackend->getGateway($cfg['product_price'],"rand".substr(md5(date("dmyHis")),0,5));
          $merchant_data = get_specific_merchant_data($random_merchant[2]);
          
          if($merchant_data->rebill_period == 30) {
              break;
          }
      }
      
      if(!$random_merchant) {
          # this is done so the IF condition from below fails and we get no cookie
          $random_merchant = array(false, false);
      }      
  } else {
      
      $random_merchant = $paybackend->getGateway($cfg['product_price'],"rand".substr(md5(date("dmyHis")),0,5));
  }
  
  if(isset($random_merchant[2])) {
    //the Bank ID
    
    //small processing, so people can't mess with it
    $cookie_value = md5($random_merchant[2].$random_merchant[0].$random_merchant[1]["username"]);
    
    supersession($pre_select_cookie,$cookie_value,$expiration);
    
    if(defined('IS_DEVELOPER')) {
      //debug
      supersession("clear_psm",$random_merchant[2],time()+60);
    }
    
  }
    
    
  } 
  
  
  
  
 } else {
  //re-enforce
  supersession($pre_select_cookie,null);
  supersession($pre_select_cookie,$pre_select,$expiration);
 }
 
 }