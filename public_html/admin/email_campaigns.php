<?php

//session_start();
//$_SESSION['result'] = array();
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
$page->template = "../templates/".$cfg['language']."/default.html"; // load template

include_once("../lib/user.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];




$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "Email Campaigns";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/email_campaigns.php");
}

$errors = array();

if(isset($_POST["submit_new"])) {
  if(!strlen($_POST["campaign_new"]) || !strlen($_POST["webform_new"])) {
    $errors[] = "Not all needed fields were defined!";
  } else  {
    
    mysql_query("insert into email_campaigns set name='".mysql_real_escape_string($_POST["campaign_new"])."',webform_id='".mysql_real_escape_string($_POST["webform_new"])."',active=".(isset($_POST["active_new"]) ? 1:0));
    
  }
  
}

if(isset($_POST["update"])) {

  $err_exists = false;
  
  foreach($_POST["campaign"] as $key=>$value) {
    
    if(!strlen($value) || !strlen($_POST["webform"][$key])) {
    $err_exists = true;
  } else  {
  
    mysql_query("update email_campaigns set name='".mysql_real_escape_string($value)."',webform_id='".mysql_real_escape_string($_POST["webform"][$key])."',active=".(isset($_POST["active"][$key]) ? 1:0)." where id=".$key);
    
  }
    
    
  }
  
  if($err_exists) {
    $errors[] = "Not all needed fields were filled in,some entries were not updated!";
  }
}

if(isset($_GET["delete"])) {
  if(intval($_GET["delete"])>0) {
    mysql_query("delete from email_campaigns where id=".intval($_GET["delete"]));
  }
}

//handle offers
if(isset($_POST["submit_new_offer"])) {
  if(!strlen($_POST["offer_new"]) || !strlen($_POST["offer_new_price"]) || !is_numeric($_POST["offer_new_price"])) {
    $errors[] = "Not all needed fields were defined, or the price was not a number!";
  } else  {
    
    mysql_query("insert into campaign_offers set name='".mysql_real_escape_string($_POST["offer_new"])."',product_price='".mysql_real_escape_string($_POST["offer_new_price"])."'");
    
  }
  
}

if(isset($_POST["update_offers"])) {

  $err_exists = false;
  
  foreach($_POST["offer"] as $key=>$value) {
    
    if(!strlen($value) || !strlen($_POST["offer_price"][$key]) || !is_numeric($_POST["offer_price"][$key])) {
    $err_exists = true;
  } else  {
  
    mysql_query("update campaign_offers set name='".mysql_real_escape_string($value)."',product_price='".mysql_real_escape_string($_POST["offer_price"][$key])."' where id=".$key);
    
  }
    
    
  }
  
  if($err_exists) {
    $errors[] = "Not all needed fields were filled in,some entries were not updated!";
  }
}

if(isset($_GET["delete_offer"])) {
  if(intval($_GET["delete_offer"])>0) {
    mysql_query("delete from campaign_offers where id=".intval($_GET["delete_offer"]));
  }
}


//split test
if(isset($_POST["submit_split"])) {

 
  
  //remove all split tests first
  mysql_query("update email_campaigns set split=0 where 1");
  
  //now, see if we need to activate a split test
  if(isset($_POST['active_split'])) {
    
    //check the 2 options are different
    if($_POST['c1']==$_POST['c2']) {
      $errors[] = "You need different campaigns to split test!";
    } else {
      //add the split
      mysql_query("update email_campaigns set split=1 where id IN(".intval($_POST['c1']).",".intval($_POST['c2']).")");
    }
    
  }
  
}


//grab the campaigns
$campaigns = multi_query_assoc("select * from email_campaigns where 1");

//grab the offers
$offers = multi_query_assoc("select * from campaign_offers where 1");

$page->blocks['content'] = em_camp($campaigns,$offers,$errors);	

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

function em_camp($campaigns=array(),$offers=array(),$errors=array()) {

	global $cfg;
  
$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.8.0.min.js"></script>';
$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/email_campaigns.js"></script>';

$html .= "<div class=\"listContent\">\n";

  if(is_array($errors) && count($errors)) {
    foreach($errors as $err) {
      $html.="<p class='error'>".$err."</p>\n";
    }
  }

  $html.= "<h1>Email campaigns</h1>";
  
   $html.= "<div style='clear:both'>The email campaigns are right now hosted by MailChimp. The List input below refers to the List ID, which can be found in the lists settings.<br /><br />
   
   The general link for Campaigns is:<br />
   <a href='".$cfg['site']['url']."/checkout_p2.php?email_campaign=*|UNIQUEID|*'>".$cfg['site']['url']."/checkout_p2.php?email_campaign=*|UNIQUEID|*</a> (no special offer in this link. for that add: \"&offer=XXX\", where XXX is the offer ID, shown below for each offer)
   <br /><br />
   </div>\n";
   
   //check to see if we have a split
   $split_campaigns = multi_query_assoc("select * from email_campaigns where split=1 AND active=1");
   $split_active=false;
   $split_c1 = $split_c2 = 0;
   if(count($split_campaigns)>=2) {
    $split_active=true;
    $split_c1 = $split_campaigns[0]["id"];
    $split_c2 = $split_campaigns[1]["id"];
    
    
    //grab split test data
    
    //clicks
    $split_c1_clicks = single_query_assoc("select count(id) as clicks from email_campaign_clicks where campaign_id=".$split_c1);
    $split_c2_clicks = single_query_assoc("select count(id) as clicks from email_campaign_clicks where campaign_id=".$split_c2);
    
    //sales
    $split_c1_sales = single_query_assoc("select count(UserID) as sales from mem_user where email_campaign=".$split_c1);
    $split_c2_sales = single_query_assoc("select count(UserID) as sales from mem_user where email_campaign=".$split_c2);
    
    
   }
   
   $html.= "<div id='split_wrapper' class='campaign_wrapper_new' style='display:block;'>\n";
    $html.= "<h2>Split Test</h2>";
    $html.= "<form action='' name='split_form' method='post'>\n";
    
      $html.= "<span>Campaign 1:</span>\n";
        $html.= "<select name='c1'>\n";
          foreach($campaigns as $campaign) {
            if($campaign['active']) {
              $html.="<option value='".$campaign['id']."'".(($campaign['id']==$split_c1) ? " selected":"").">".$campaign['name']."</option>\n";
              }
          }
        $html.= "</select>\n";
		
		$html.= "<div style='clear:both;'></div>\n";
        
        $html.= "<span>Campaign 2:</span>\n";
        $html.= "<select name='c2'>\n";
          foreach($campaigns as $campaign) {
            if($campaign['active']) {
              $html.="<option value='".$campaign['id']."'".(($campaign['id']==$split_c2) ? " selected":"").">".$campaign['name']."</option>\n";
            }  
          }
        $html.= "</select>\n";
        
        $html.= "<span>Active ?:</span> <input type='checkbox' name='active_split' value='1'".($split_active ? " checked":"")."  />";       
        
        
        $html.= "<input type='submit' name='submit_split' class='email_submit' value='Save' />"; 
        
        
        
        
    $html.= "</form>\n"; 
    
    if($split_active) {
      
      $html.= "<div style='clear:both'>&nbsp;</div>\n";
      
      $html.= "<h3>Split results</h3>";
      $html.= "<table cellpadding='3' cellspacing='3' class='split_stats'>\n";
        $html.= "<thead>\n";
          $html.= "<th>Stat/Campaign</th>\n";
          $html.= "<th>".$split_campaigns[0]["name"]."</th>\n";
          $html.= "<th>".$split_campaigns[1]["name"]."</th>\n";
        $html.= "</thead>\n";
        
        $html.= "<tr>\n";
          $html.= "<td>Clicks</td>\n";
          $html.= "<td>".$split_c1_clicks['clicks']."</td>\n";
          $html.= "<td>".$split_c2_clicks['clicks']."</td>\n";
        $html.= "</tr>\n";
        
        $html.= "<tr>\n";
          $html.= "<td>Sales</td>\n";
          $html.= "<td>".$split_c1_sales['sales']."</td>\n";
          $html.= "<td>".$split_c2_sales['sales']."</td>\n";
        $html.= "</tr>\n";
        
      $html.= "</table>\n";
    
    }
    
   $html.= "</div>\n";
  
  $html.= "[<a href='#' class='add_campaign'>Add campaign</a>]\n";
  
  $html.= "[<a href='#' class='add_offer'>Add campaign offer</a>]<br />\n";
  
 
  
  
    $html.= "<div class='campaign_wrapper_new' id='new_campaign_wrapper' style='display:block;'>\n";
    
      $html.= "<form action='' name='new_campaign_form' method='post'>\n";
        $html.= "<h2>Add Campaign</h2>";
        
        $html.= "<span>Name:</span> <input class='campaign_name campaign_tf' name='campaign_new' value='' placeholder='Internal Name' />";
        
        $html.= "<span>List:</span> <input class='webform_id campaign_tf' name='webform_new' value='' placeholder='List/Webform ID' />";
        
        $html.= "<span>Active ?:</span> <input type='checkbox' name='active_new' value='1'  />";
        
        $html.= "<input type='submit' name='submit_new' class='email_submit' value='Save' />"; 
        
       $html.= "</form>\n";  
       
      $html.= "</div>\n";
      
      //new offer
      $html.= "<div class='campaign_wrapper_new' id='new_offer_wrapper'>\n";
    
      $html.= "<form action='' name='new_offer_form' method='post'>\n";
      
        $html.= "<h2>Add Offer</h2>";
        
        $html.= "<span>Name:</span> <input class='offer_name campaign_tf' name='offer_new' value='' placeholder='Internal Name' />";
        $html.= "<span>Product Price:</span> <input class='offer_new_price campaign_tf' name='offer_new_price' value='' placeholder='Product Price in USD' />";
        
        $html.= "<input type='submit' name='submit_new_offer' class='email_submit' value='Save' />"; 
        
       $html.= "</form>\n";  
       
      $html.= "</div>\n";
    
    
    $html.= "<div style='clear:both'>&nbsp;</div>\n";
    
    $html.= "<h2>Campaigns</h2>";
    
  
  $html.= "<form action='' name='campaigns_form' method='post'>\n";
  
  foreach($campaigns as $campaign) {
    
    $html.= "<div class='campaign_wrapper'>\n";
      $html.= "<span>Name:</span> <input class='campaign_name campaign_tf' name='campaign[".$campaign['id']."]' value='".$campaign['name']."' />";
     // $html.= "<br />";
      $html.= "<span>List:</span> <input class='webform_id campaign_tf' name='webform[".$campaign['id']."]' value='".$campaign['webform_id']."' />";
      //$html.= "<br />";
      $html.= "<span>Active ?:</span> <input type='checkbox' name='active[".$campaign['id']."]' value='1' ".($campaign['active']>0 ? "checked":"")." />";
      
      //clicks
      $clicks = single_query_assoc("select count(id) as clicks from email_campaign_clicks where campaign_id=".$campaign['id']);    
      //sales
      $sales = single_query_assoc("select count(UserID) as sales from mem_user where email_campaign=".$campaign['id']);
      
      $html.= "<span>Clicks: <strong>".$clicks['clicks']."</strong> </span>";
      $html.= "<span>Sales: <strong>".$sales['sales']."</strong></span> ";
      
      
      
      //$html.= "<br />";
      $html.= "<span>[<a href='email_campaigns.php?delete=".$campaign['id']."' onclick='return confirm(\"Are you sure?\")'>delete</a>]</span>";
    $html.= "</div>\n";
    
  }
  
  $html.= "<br />";
  if(count($campaigns)) {
    $html.= "<input type='submit' name='update' class='email_submit' value='Save Campaign Modifications' />";   
  }
  $html.= "</form>\n";
  
  
  
      $html.= "<div style='clear:both'>&nbsp;</div>\n";
    
    $html.= "<h2>Offers</h2>";
    
  
  $html.= "<form action='' name='offers_form' method='post'>\n";
  
  foreach($offers as $offer) {
    
    $html.= "<div class='campaign_wrapper'>\n";
      $html.= "<h3>Offer ID: ".$offer['id']."</h3>\n";
      $html.= "<span>Name:</span> <input class='campaign_name campaign_tf' name='offer[".$offer['id']."]' value='".$offer['name']."' />";
     // $html.= "<br />";
      $html.= "<span>Product Price:</span> <input class='webform_id campaign_tf' name='offer_price[".$offer['id']."]' value='".$offer['product_price']."' />";  
      
      $html.= "<span><a href='".$cfg['site']['url']."/checkout_p2.php?email_campaign=*|UNIQUEID|*&offer=".$offer['id']."'>LINK</a></span>";
      
      //sales
      $sales = single_query_assoc("select count(UserID) as sales from mem_user where campaign_offer=".$offer['id']);
      
      $html.= "<span>Sales: <strong>".$sales['sales']."</strong> </span>";
      
      
      $html.= "<span>[<a href='email_campaigns.php?delete_offer=".$offer['id']."' onclick='return confirm(\"Are you sure?\")'>delete</a>]</span>";
    $html.= "</div>\n";
    
  }
  
  $html.= "<br />";
  if(count($offers)) {
    $html.= "<input type='submit' name='update_offers' class='email_submit' value='Save Offer Modifications' />";   
  }
  $html.= "</form>\n";
  

$html .= "</div>\n";

return $html;


}