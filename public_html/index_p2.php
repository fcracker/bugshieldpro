<?php
  define('IS_INDEX',1);
  
  	include_once("./lib/config.inc.php");
	
	include_once("./lib/database.inc.php");
  
  global $cfg;
	
	$con = connect_database();
  
     //check if we have a pre-selected merchant, and use its rebill price
  $pre_selected_merchant = get_preselected_merchant();
  $rebill_price = $cfg['product_price_rebill'];
  $rebill_period = $cfg['rebill_period'];
  if($pre_selected_merchant!==false) {
      $rebill_price = $pre_selected_merchant->monthly_price;
      $rebill_period = $pre_selected_merchant->rebill_period;
  }
include_once('./inc/func.php');
$loc = get_landing_location(2);
$hash = $loc["hash"];
$target = "redir.php?hash=".$hash;
$loc = get_specified_target("howitworks_p2.php");
$howitworks = "redir.php?hash=".$loc["hash"];
$loc = get_specified_target("proof_p2.php");
$proof = "redir.php?hash=".$loc["hash"];

session_start();

if ($_SESSION['frompath'] == 'bp')
		header('location:bp/');
	else
		header('location:ap/');
?>