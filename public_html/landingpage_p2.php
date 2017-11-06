<?php		
	define('IS_LANDINGPAGE', 1);
	include_once("./lib/config.inc.php");
	include_once("./lib/database.inc.php");
	include_once("./lib/form.class.php");
	include_once("./lib/country.class.php");
	
	$con = connect_database();
	
	$t = new tracker;
	$tracker = $t->get_data();
	
	//reset some stuff
	$tracker["upsell_price"] = 0;
	$tracker["shipping_price"] = 0;
	$tracker["quantity"] = 1;
	$tracker["upsell_step"] = 1;
	
	$tracker["path"] = "2";
	
	
	unset($tracker["has_year_upsell"]);
	unset($tracker["has_couch_upsell"]);
	unset($tracker["has_travel_upsell"]);
	unset($tracker["has_shipping_upsell"]);
	
	
	//$country = new umCountry();
	//$country->set_ip();
	//$client_country=$country->get_country_iso();
	
	$client_country = MY_COUNTRY;
	
	if(isset($tracker["country"])) {
		$client_country = $tracker["country"];
	}
	
	$zip_visible = true;
  
	//change 'State' label based on country
	
	switch(strtolower($client_country)) {
			case 'us':
						$state_label = 'State';
            $zip_label = 'Zip';
						break;
      case 'de':
      case 'in':
						$state_label = 'State';
            $zip_label = 'Postal Code';
						break;      
			case 'hk':  
      case 'mo':  
            $state_label = 'District';
            $zip_label = 'Postal Code';
            $zip_visible = false;
            break;
      case 'pa':
            $state_label = 'Province';
            $zip_label = 'Postal Code';
            $zip_visible = false;
            break;			
			default:	
            $state_label = 'Province';
            $zip_label = 'Postal Code';
            break;
			
	}
	$field = new umField();
	$field->fieldID = 8;		//define country filed ID 
	$field->get_field_options();
	
	if(isset($_POST["email"])) {
		//remove from transactions
		mysql_query("delete from mem_merchant_history where user_email='".mysql_real_escape_string($_POST["email"])."' and transaction_id='offline-firstsale'");
		//remove from temp users
		mysql_query("delete from mem_user_temp where Email='".mysql_real_escape_string($_POST["email"])."' limit 1");
	}
	
	
	//check if we have an email campaign id, and get the associated data
  $email_campaign_data = array();
  $email_campaign_id = supersession("email_campaign");
  if($email_campaign_id!==false) {
    $email_campaign_id = base64_decode($email_campaign_id);
    $email_campaign_result = mysql_query("select * from mem_signup_log where unique_id='".$email_campaign_id."'");
    if(mysql_num_rows($email_campaign_result)) {
      $ec_row = mysql_fetch_assoc($email_campaign_result);
      
      $email_campaign_data = array(
      "fullname"=>$ec_row["FullName"],
      "city"=>$ec_row["City"],
      "address"=>$ec_row["Address"],
      "postalcode"=>$ec_row["PostalCode"],
      "phone"=>$ec_row["Telephone"],
      "email"=>$ec_row["Email"],
      "state"=>$ec_row["State"],
      "country"=>$ec_row["Country"],);
      
      $client_country = $ec_row["Country"];
      
    }
    
  }
	
	
	$form_keys = array("fullname","city","address","postalcode","phone","email","state","country");
	
	$form_values = array();
	
	foreach($form_keys as $key) {
	
		if(isset($tracker[$key])) {
			$form_values[$key] = $tracker[$key];
		} else {
      if(isset($email_campaign_data[$key])) {
        $form_values[$key] = $email_campaign_data[$key];
      } else {
        $form_values[$key] = "";//empty value
      }
		}	
	}
	
	$error_message = isset($tracker['error_message']) ? $tracker['error_message'] : "";
	
	unset($tracker["error_message"]);
	
	//save the tracker back
	$t->set_data($tracker);
  
  global $cfg;
  
	session_start();
	if ($_SESSION['frompath'] == 'bp')
		header('location:bp/');
	else
		header('location:ap/');
	
?>