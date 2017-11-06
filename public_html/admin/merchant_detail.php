<?php
/*
 * init web page
 */
include_once("../lib/config.inc.php");
$defaultLang = $cfg['language'];
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/country.class.php");
include_once("../lib/form.class.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
$page->template = "../templates/".$cfg['language']."/default.html"; // load template

include_once("../lib/user.class.php");
include_once("../lib/PayBackEnd.php");

$con = connect_database();

/*
 * create content blocks
 * page is built in this part
 */
$userID = 0;
if(isset($_GET['userID'])) $userID = $_GET['userID'];
if(isset($_POST['userID'])) $userID = $_POST['userID'];

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = (isset($_GET["menuIndex"])?$_GET["menuIndex"]:0);
if($menuActiveIndex>0){
	if($userID == 0){
		$page->blocks['title'] = $lang['title']['createUser'];
	}else{
		$page->blocks['title'] = $lang['title']['updateUser'];
	}
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/merchant_detail.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$merchant = new PayBackEnd();
	$BankID = 0;
	if(isset($_GET['BankID'])) $BankID = $_GET['BankID'];
	if(isset($_POST['BankID'])) $BankID = $_POST['BankID'];
	
	if(isset($_POST["BankID"])){
		$merchant->bankID = $BankID;
		trim_post_value();
		$errorMessage = "";
		$errorMessage = validate_post_value();
    
    $country_rules = array();
    //create the country rules and validate them
    if(is_array($_POST["country_rule_counter"])) {
    
    //we have some rules set!
    foreach($_POST["country_rule_counter"] as $key) {
      
      if(strlen($_POST["country_rules_country"][$key])> 1) {
      $country_rules[] = array(
        "country"=>     $_POST["country_rules_country"][$key],        
        "use_upsell"=>  isset($_POST["country_rules_use_upsell"][$key]) ? 1:0,
        "upsell_price"=>	$_POST["country_rules_upsell_price"][$key], 
        "upsell_text"=>	$_POST["country_rules_upsell_text"][$key], 
        "use_monthly"=>	isset($_POST["country_rules_use_monthly"][$key]) ? 1:0, 
        "monthly_price"=>	$_POST["country_rules_monthly_price"][$key], 
        "monthly_text"=>	$_POST["country_rules_monthly_text"][$key],      
      );
      }
      
    }
    
    }
    
    $_POST["monthly_country_exceptions"] = serialize($country_rules);
    
    //check the rebill forwards based on value
    $rebill_fwd_value_based = array();
    if(/*isset($_POST['use_fwd_value_rules']) && */is_array($_POST['vfr_start']) && count($_POST['vfr_start'])) {
      foreach($_POST['vfr_start'] as $key=>$start_value) {
        $rebill_fwd_value_based[] = array(
          "start" =>  floatval($start_value),
          "end" =>  floatval($_POST['vfr_end'][$key]),
          "merchant" =>  intval($_POST['vfr_merchant'][$key]),
        );
      }
    }
    
     $_POST["rebill_fwd_value_based_rules"] = serialize($rebill_fwd_value_based);
    
    
    $custom_upsell_rules = array();
    
    if(isset($_POST['custom_upsell'])) {
      $custom_upsell_rules = $_POST['upsell_charges'];
    }
    $_POST["upsell_charges"] = serialize($custom_upsell_rules);
    
    //forward rebill balancing
    $forward_rebill_balance = "";
    if($_POST['forward_rebill'] == -1) {
      
      $forward_balance_pairs  = array();
      
      //get the percentages for the rebill balance
      foreach($_POST['refill_forward_load_balance'] as $mid=>$percentage_value) {
        
        $forward_balance_pairs[] = $mid.":".$percentage_value;
        
      }
      
       $forward_rebill_balance = implode(";",$forward_balance_pairs);
      
      
    }
    
    $_POST["forward_rebill_balance"] = $forward_rebill_balance;
    
    
		if($errorMessage==""){
			if($BankID==0){		//insert
				$merchant->create_merchant($_POST);
			}else{				//update
				$merchant->update_merchant($_POST);
			}
			redirect($cfg['site']['folder']."admin/manage_merchant.php");
		}else{
			if($BankID == 0){
				$page->blocks['content'] = build_form("Create New Merchant", $errorMessage);
			}else{
				$page->blocks['content'] = build_form("Update Merchant", $errorMessage);
			}
		}
	}else{
		if(init_post_value($BankID)){
			if($BankID==0)
				$page->blocks['content'] = build_form("Create New Merchant");
			else
				$page->blocks['content'] = build_form("Update Merchant");
		}else{
			$page->blocks['content'] = show_message($lang['text']['errorOccurred']);
		}
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/merchant_detail.php");
}

/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

close_database($con);

/*
* ============================================== page complete here ==============================================
* The following functions construct content for this page
*/

/*
* show message
*/
function show_message($messageText){
	$html = "";
	$html .= "<div style=\"margin: 20px; height: 300px\">";
	$html .= $messageText;
	$html .= "</div>";
	return $html;
}

/*
* build form of this page
*/
function build_form($formTitle, $errorMessage = ""){
	global $lang;
	global $cfg;
	global $menuActiveIndex;
	
	$html = "";
  
  $html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.8.0.min.js"></script>';
  $html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/colresizable.js"></script>';
  $html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/merchant_detail.js"></script>';
	
	$html .= "<script type=\"text/javascript\">\n";
	$html .= "
	function change_type(){
		if(document.mainform.gatewayType.value=='nmi'){
			document.mainform.gatewaySign.value='';
			document.mainform.gatewaySign.disabled=true;
		}else{
			document.mainform.gatewaySign.disabled=false;
		}
	}\n";
	
	$html .= "
	function change_period(){
		switch(document.mainform.payment_period.value){
		case '0':
			selectText = 'Daily  Limit'; 
			break;
		case '1':
			selectText = 'Monthly  Limit';
			break;
		}
		document.getElementById('label_capacity').innerHTML = selectText+'($)';
		
	}\n";
	$html .= "</script>\n";
  
  $current_merchant_id = 0;
  
  if($_POST['BankID'] != 0){
    $current_merchant_id = (int)$_POST['BankID'];
    $backend = new PayBackEnd();
    //get monthly sum
    $monthly_sum = $backend->getMontlySpent($_POST['BankID'],$_POST['payment_period']);
    $spent_cap = $_POST['cap_per_month'];
    $spent_sum = $monthly_sum;
    $spent_percentage = sprintf("%0.2f",($monthly_sum * 100)/$_POST['cap_per_month']);
    $spent = "<br />Spent for the month of ".date("F Y").": \$".money_format("%i",$spent_sum)." / \$".money_format("%i",$spent_cap)." (".$spent_percentage."%) <br />";
    $html.= $spent;
    }
	
	$html .= "<div class=\"formDiv merchant_detail_form\">";
	$html .= "<form name=\"mainform\" action=\"".sess_url($cfg['site']['folder']."admin/merchant_detail.php?menuIndex=".$menuActiveIndex)."\" method=\"post\" onSubmit=\"return disablePage();\" class=\"formLayer\">";
	$html .= "<fieldset>";
	$html .= "<legend>".htmlspecialchars($formTitle)."</legend>";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br />";
	}
	
	
	$html .= "<label>Gateway Name</label>";
	$html .= "<input type=\"text\" name=\"BankName\" value=\"".htmlspecialchars($_POST['BankName'])."\" size=\"50\">";
	$html .= "<br />";
	
	$html .= "<label>Tier</label>";
	$html .= "<input type=\"text\" name=\"tier\" value=\"".$_POST['tier']."\" size=\"10\">";
	$html .= "<br />";
	
	$html .= "<label>Payment Period</label>";
	$html .= "<select name=\"payment_period\" width=\"80\" onchange1=\"change_period()\">";
	$html .= "<option value=\"1\" ".($_POST['payment_period']=="1"?"selected":"").">Monthly&nbsp;&nbsp;</option>";
	$html .= "<option value=\"0\" ".($_POST['payment_period']=="0"?"selected":"").">Daily</option>";	
	$html .= "</select>";
	$html .= "<br />";
	
	$html .= "<label id=\"label_capacity\">Monthly Limit($)</label>";
	$html .= "<input type=\"text\" name=\"cap_per_month\" value=\"".$_POST['cap_per_month']."\" size=\"20\">";
	$html .= "<br />";
	
  /*
	$html .= "<label>Percent(%)</label>";
	$html .= "<input type=\"text\" name=\"persent\" value=\"".$_POST['persent']."\" size=\"10\">";
	$html .= "<br />";
  */
	
	$html .= "<label>Gateway Type</label>";
	$html .= "<select name=\"gatewayType\" width=\"80\" onchange=\"change_type()\" >";
	$html .= "<option value=\"pp\" ".($_POST['gatewayType']=="pp"?"selected":"").">Paypal</option>";
	$html .= "<option value=\"nmi\" ".($_POST['gatewayType']=="nmi"?"selected":"").">NMI</option>";	
	$html .= "<option value=\"paymentxp\" ".($_POST['gatewayType']=="paymentxp"?"selected":"").">PaymentXP</option>";	
	$html .= "<option value=\"payvt\" ".($_POST['gatewayType']=="payvt"?"selected":"").">Payvt</option>";	
	$html .= "<option value=\"remote-payvt\" ".($_POST['gatewayType']=="remote-payvt"?"selected":"").">Remote Payvt</option>";	
	$html .= "<option value=\"remote-nmi\" ".($_POST['gatewayType']=="remote-nmi"?"selected":"").">Remote NMI</option>";
	$html .= "<option value=\"remote-pp\" ".($_POST['gatewayType']=="remote-pp"?"selected":"").">Remote PayPal</option>";
    $html .= "<option value=\"remote-FD\" ".($_POST['gatewayType']=="remote-FD"?"selected":"").">Remote First Data</option>";
    $html .= "<option value=\"remote-GPN\" ".($_POST['gatewayType']=="remote-GPN"?"selected":"").">Remote GPN</option>";
    $html .= "<option value=\"besecure\" ".($_POST['gatewayType']=="besecure"?"selected":"").">BeSecure</option>";
    $html .= "<option value=\"ecpss\" ".($_POST['gatewayType']=="eepcs"?"selected":"").">Ecpss</option>";
	$html .= "<option value=\"offline\" ".($_POST['gatewayType']=="offline"?"selected":"").">Offline</option>";
	$html .= "</select>";
	

	$html .= "<br />";
	
	$html .= "<label>Gateway ID</label>";
	$html .= "<input type=\"text\" name=\"gatewayID\" value=\"".htmlspecialchars($_POST['gatewayID'])."\" size=\"50\">";
	$html .= "<br />";
	
	$html .= "<label>Gateway Key</label>";
	$html .= "<input type=\"text\" name=\"gatewayKey\" value=\"".htmlspecialchars($_POST['gatewayKey'])."\" size=\"50\">";
	$html .= "<br />";
	
	$html .= "<label>Gateway Sign</label>";
	$html .= "<input type=\"text\" name=\"gatewaySign\" value=\"".htmlspecialchars($_POST['gatewaySign'])."\" size=\"50\">";
	$html .= "<br />";
  
  $merchant = new PayBackEnd();
  $merchants = $merchant->get_merchant(1);//active merchants
  
  
  $html .= "<h3>Initial Sale Fail Forward</h3>";
  
  $html .= "<label>Forward in case of initial sale fail?</label>";
	$html .= "<input type=\"checkbox\" value='1' name=\"can_initial_forward\" ".($_POST['initial_forward']>0 ? "checked='checked'":"")." onclick='$(\"#initial_forward\").slideToggle();'>";
	$html .= "<br />";
  $html.= "<div id='initial_forward' style='display:".($_POST['initial_forward']>0 ? "inline":"none")."'>";
  $html .= "<label>Forward Initial Sale to</label>";
  $html .= "<select name='initial_forward'>";
    $html.= "<option value='0'> --- No Initial Sale forward --- </option>\n";     
     foreach($merchants as $m) {
        if($current_merchant_id != $m['BankID']) {
          $html.= "<option value='".$m['BankID']."'".($m['BankID'] == $_POST['initial_forward'] ? " selected":"")."> ".$m['BankName']." </option>\n";
          }
     }    
  $html .= "</select>";
  
  $html .= "<br />";
  $html.="</div>";
  
  
  $html .= "<br />";
  
  
  $html .= "<h3>Capabilities</h3>";
	
  
  
  /* ------------- CAN PROCESS AMEX --------- */
  $html .= "<label>Can Process AMEX?</label>";
	$html .= "<input type=\"checkbox\" value='1' name=\"can_process_amex\" ".($_POST['can_process_amex']==1 ? "checked='checked'":"")." onclick='$(\"#amex_forward\").slideToggle();'>";
	$html .= "<br />";
  
  $html.= "<div id='amex_forward' style='display:".($_POST['can_process_amex']==1 ? "inline":"none")."'>";
  $html .= "<label>Forward AMEX rebills to</label>";
  $html .= "<select name='amex_forward'>";
    $html.= "<option value='0'> --- No AMEX forward --- </option>\n";     
     foreach($merchants as $m) {
        if($current_merchant_id != $m['BankID'] && $m['can_process_amex']>0) {
          $html.= "<option value='".$m['BankID']."'".($m['BankID'] == $_POST['amex_forward'] ? " selected":"")."> ".$m['BankName']." </option>\n";
          }
     }    
  $html .= "</select>";
  
  $html .= "<br />";
  $html.="</div>";
  
  /* ------------- /CAN PROCESS AMEX --------- */
  
   /*------ Soft Declines Rule ----*/
  
  $html .= "<label>Soft Declines on Rebills Should</label>";
	$html .= "<select name='soft_decline_rebill'>\n";
  $html .= "<option value='0'>NOT Cancel Rebill</option>\n";
  $html .= "<option value='1'".($_POST['soft_decline_rebill'] == '1' ? ' selected':'').">Cancel Rebill</option>\n";
  $html .= "</select>\n";
  
	$html .= "<br />";
  
  /*------END Soft Declines Rule ---*/
  
  //we do not need some of the below, add them as predefined fakes
  $html.= "<input type='hidden' name='bundle' value='1' />\n";
  $html.= "<input type='hidden' name='combined_charge' value='no' />\n";
  $html.= "<input type='hidden' name='use_upsell' value='1' />\n";
  $html.= "<input type='hidden' name='upsell_price' value='1' />\n";
  $html.= "<input type='hidden' name='upsell_text' value='1' />\n";
  /*
  $html .= "<label for='bundle'>Use Bundle ?</label>";
	$html .= "<input id='bundle' type='checkbox' name='bundle' ".($_POST['bundle']==1 ? "checked":"")." />";
	$html .= "<br />";
	
	$html .= "<label>Combined Charge</label>";
	$html .= "<input value='no' type='radio' id='ch1' name='combined_charge'".($_POST['combined_charge']=='no' ? " checked":"")." />&nbsp;<span>No Combined Charge</span>\n &nbsp;&nbsp;";
	$html .= "<input value='upsell' type='radio' id='ch2' name='combined_charge'".($_POST['combined_charge']=='upsell' ? " checked":"")." />&nbsp;<span>First Charge + Upsell</span>\n &nbsp;&nbsp;";
	$html .= "<input value='bundle' type='radio' id='ch3' name='combined_charge'".($_POST['combined_charge']=='bundle' ? " checked":"")." />&nbsp;<span>First Charge + Upsell + Bundle</span>\n &nbsp;&nbsp;";
	$html .= "<br />";
  
  */
  
  
  /*------UPSELL------*/
  /*
  $html .= "<label>Use Upsell Flat Fee ?</label>";
	$html .= "<input type='checkbox' name='use_upsell' ".($_POST['use_upsell']==1 ? "checked='checked'":"")." onclick='$(\"#upsell_data\").slideToggle();' />";
	$html .= "<br />";
  
  $html.= "<div id='upsell_data' style='display:".($_POST['use_upsell']==1 ? "inline":"none")."'>";
  
  $html .= "<label>Upsell Price</label>";
	$html .= "<input type=\"text\" name=\"upsell_price\" value=\"".htmlspecialchars($_POST['upsell_price'])."\" size=\"10\"> (an integer value)";
	$html .= "<br />";
  
  $html .= "<label>Upsell Text</label>";
	$html .= "<input type=\"text\" name=\"upsell_text\" value=\"".htmlspecialchars($_POST['upsell_text'])."\" size=\"50\"> (eg: 'one hundred seventy eight' for an upsell price of 178)";
	$html .= "<br />";
  
  $html.="</div>";
  
  */
  
  /*------END UPSELL------*/
  
  $html .= "<h3>Rebill Settings</h3>";
  
   /*------Rebill Forwards------*/
   
  $html .= "<label>Forward rebills to</label>";
	$html .= "<select name='forward_rebill' id='forward_rebill'>";
    $html.= "<option value='0'> --- No forward --- </option>\n";
     $merchant = new PayBackEnd();
     $merchants = $merchant->get_merchant(1);//active merchants
     foreach($merchants as $m) {
        if($current_merchant_id != $m['BankID']) {
          $html.= "<option value='".$m['BankID']."'".($m['BankID'] == $_POST['forward_rebill'] ? " selected":"")."> ".$m['BankName']." </option>\n";
          }
     }
     $html.= "<option value='-1'".($_POST['forward_rebill']==-1 ? ' selected':'')."> Load Balance the rebill forwards </option>\n";
    
  $html .= "</select>";
	$html .= "<br />";
  
  //rebill forward balancing  
  
    $possible_colors = array(
  
    "Red",
    "Blue",
    "Yellow",
    "Green",
    "DeepPink",
    "ForestGreen",
    "Gold",
    "LightCoral"
  
    );
     $html .= "<div id='rebill_forward_balance_wrapper' class='".(($_POST['forward_rebill'] == -1 ) ? " visible":"")."' style='display:".(($_POST['forward_rebill'] == -1 ) ? "block":"none").";'>";
    //we need to show the balancing table
    $html .= "<label>Rebill Forward Load Balancing</label>";
    
    
    $html .= "<br />";
    
    $html .= "<table class='listTable' id='rebill_forward_balance_table' cellpadding='5' cellspacing='0' width='80%'>";
    $html.= "<tr>\n";
     $merchant = new PayBackEnd();
     $merchants = $merchant->get_merchant(1);//active merchants
     $merchant_data = array(); 
     
     $default_percentage = ceil(100/count($merchants));
     
     //grab existing percentages
     $existing_percentages = array();
     
     if(strlen($_POST['forward_rebill_balance'])) {
      $parts = explode(";",$_POST['forward_rebill_balance']);
      if(count($parts)) {
        foreach($parts as $part) {
          if(strpos($part,":")) {
            $subparts = explode(":",$part);
            if(count($subparts) == 2) {
              if(is_numeric($subparts[0]) AND is_numeric($subparts[1])) {
                $existing_percentages[$subparts[0]] = $subparts[1];
              }
            }
          }
        }
      }
     }
     
     
     for($j=0;$j<count($merchants);$j++) {
      if($current_merchant_id != $merchants[$j]['BankID']) {
      
      $new_color = isset($possible_colors[$j]) ? $possible_colors[$j] : $possible_colors[rand(0,count($possible_colors)-1)];
          
      $html .= "<td rel='".$merchants[$j]['BankID']."' height='30' bgcolor='".($new_color)."' width='".(isset($existing_percentages[$merchants[$j]['BankID']]) ? $existing_percentages[$merchants[$j]['BankID']] : $default_percentage)."%'>\n";
      
      $html .= "<input type='hidden' name='refill_forward_load_balance[".$merchants[$j]['BankID']."]' value='".(isset($existing_percentages[$merchants[$j]['BankID']]) ? $existing_percentages[$merchants[$j]['BankID']] : $default_percentage)."' />";    

      $html .= "</td>\n"; 

      //save the color
      $merchant_data[$merchants[$j]['BankName']] = array(
        "color"=>$new_color,
        "percentage"=>(isset($existing_percentages[$merchants[$j]['BankID']]) ? $existing_percentages[$merchants[$j]['BankID']] : $default_percentage),      
        "id"=>$merchants[$j]['BankID']
        );    

        }
          
          
     }    
  $html .= "</tr>";  
  $html .= "</table>";
  
  $html .= "<br />";
  
  $html.= "<table cellpadding=5 cellspacing=5>";   
     $html .= "<tr>\n";
    $merchant_local_counter = 1; 
  foreach($merchant_data as $merchant_name=>$merchant_data) {
  
   $html .= "<td bgcolor='".$merchant_data['color']."' width='50'>&nbsp;</td>\n";
   $html .= "<td>".$merchant_name." ( <span id='mfri".$merchant_data['id']."'>".$merchant_data['percentage']."</span>% )</td>\n";      
  if($merchant_local_counter < count($merchant_data)) {
    $html .= "<td bgcolor='#C0FAF0' width='10'></td>\n";
  }
   $merchant_local_counter++; 
  }
  
  $html .= "</tr>\n";
    
    $html .= "<tr>\n";
      $html .= "<td bgcolor='#C0FAF0' height='1' colspan='".(count($merchant_data)*3 - 1)."'></td>\n";
    $html .= "</tr>\n";
  
    
  $html .= "</table>\n";
  
	$html .= "<br />";
  
	$html .= "</div>";
  

   
  /*------END Rebill Forwards------*/
  
  /*------Rebill Fallback------*/
  
  $html .= "<label>If Rebill Fails ... </label>";
  
  $html .= "<select name='rebill_fail_action' id='rebill_fail_action'>\n";
    $html.= "<option value='0' ".(($_POST['rebill_fail_action'] == 0 ) ? "selected":"").">Cancel Rebill (Default)</option>";
    $html.= "<option value='1' ".(($_POST['rebill_fail_action'] == 1 ) ? "selected":"").">Try again later with same merchant</option>";
    $html.= "<option value='2' ".(($_POST['rebill_fail_action'] == 2 ) ? "selected":"").">Try again later with different merchant</option>";
  $html .= "</select>\n";
  
  
  $html.= "&nbsp;&nbsp;\n";
  $html .= "<select name='rebill_fail_try_after' id='rebill_fail_try_after' style='display:".(($_POST['rebill_fail_action'] >0 ) ? "inline":"none")."'>\n";
    for($hours=1;$hours<25;$hours++) {
      $html.= "<option value='".$hours."' ".(($_POST['rebill_fail_try_after'] == $hours ) ? "selected":"").">Try After ".$hours." HRS</option>";
    }
  $html .= "</select>\n";
  
  $html.= "&nbsp;&nbsp;\n";
  $html .= "<select name='rebill_fail_merchant' id='rebill_fail_merchant' style='display:".(($_POST['rebill_fail_action'] >1 ) ? "inline":"none")."'>\n";
    foreach($merchants as $merch) {
      if($current_merchant_id != $merch['BankID']) {
      $html.= "<option value='".$merch['BankID']."' ".(($_POST['rebill_fail_merchant'] == $merch['BankID'] ) ? "selected":"").">".$merch['BankName']."</option>";
      }
    }
  $html .= "</select>\n";
  
	$html .= "<br />";
  
  /*------END Rebill Fallback------*/
  
  
  /*------Rebill------*/
  
  $html .= "<label>Use Custom Rebill ?</label>";
	$html .= "<input type='checkbox' name='use_monthly' ".($_POST['use_monthly']==1 ? "checked='checked'":"")." onclick='$(\"#monthly_data\").slideToggle();' />";
	$html .= "<br />";
  
  $html.= "<div id='monthly_data' style='display:".($_POST['use_monthly']==1 ? "inline":"none")."'>";
  
  $html .= "<label>Rebill Period</label>";
	$html .= "<input type=\"text\" name=\"rebill_period\" value=\"".htmlspecialchars($_POST['rebill_period'])."\" size=\"10\">(default is ".$cfg['rebill_period'].")";
	$html .= "<br />";
  
  $html .= "<label>Rebill Unit Price</label>";
	$html .= "<input type=\"text\" name=\"monthly_price\" value=\"".htmlspecialchars($_POST['monthly_price'])."\" size=\"10\">(default is ".$cfg['product_price_rebill'].")";
	$html .= "<br />";
  
  $html .= "<label>Rebill Unit Price Text</label>";
	$html .= "<input type=\"text\" name=\"monthly_text\" value=\"".htmlspecialchars($_POST['monthly_text'])."\" size=\"50\"> (eg: 'two dollars ninenty nine' for an unit rebill price of USD 2.99, default is '".$cfg['product_price_rebill_text']."')";
	$html .= "<br />";
  
  $html.="</div>";
  
  
  
  /*------END Rebill------*/
  
  /*----- Forward CVV------*/
  
  $html.="<div>";
  $html .= "<label>Forward CVV on NON-forwarded rebill ?</label>";
	$html .= "<input type='checkbox' name='forward_cvv_rebill' ".($_POST['forward_cvv_rebill']=="1" ? "checked='checked'":"")." value='1' />";
	$html .= "<br />";
  
  $html .= "<label>Forward CVV on FORWARDED rebill ?</label>";
	$html .= "<input type='checkbox' name='forward_cvv_rebill_forwarded' ".($_POST['forward_cvv_rebill_forwarded']=="1" ? "checked='checked'":"")." value='1' onclick='$(\"#forward_cvv_rebill_initial_wrapper\").slideToggle();' />";
	$html .= "<br />";
  
  $html .= "<div id='forward_cvv_rebill_initial_wrapper' style='display:".($_POST['forward_cvv_rebill_forwarded']=="1" ? "block":"none;")."'>";
    $html .= "<label>Forward CVV only on INITIAL FORWARDED rebill ?</label>";
    $html .= "<input type='checkbox' name='forward_cvv_rebill_initial' ".($_POST['forward_cvv_rebill_initial']=="1" ? "checked='checked'":"")." value='1' />";
    $html .= " (if the above condition is not ticked, then this option will not get checked)";
    $html .= "<br />";
  $html.="</div>";
  
  $html.="</div>";
  
  /*------END Forward CVV------*/
  
  $html .= "<h4>Rebill Forward based on value($)</h4>";
  
  $html .= "<label>Use Value Forward Rules ?</label>";
  $html .= "<input type='checkbox' name='use_fwd_value_rules' ".($_POST['use_fwd_value_rules']=="1" ? "checked='checked'":"")." value='1' /><br />";
  $html.="<div id='rebill_fwd_wrapper'>";
  if(is_array($_POST['rebill_fwd_value_based_rules']) && count($_POST['rebill_fwd_value_based_rules'])) {
    foreach($_POST['rebill_fwd_value_based_rules'] as $val_based_fwd_rule) {
    
      $html.="<div class='rebill_fwd_rule'>";
        $html.="When value is between $<input type='text' name='vfr_start[]' placeholder='$' value='".$val_based_fwd_rule['start']."' />";
        $html.=" and $<input type='text' name='vfr_end[]' placeholder='$' value='".$val_based_fwd_rule['end']."' />";
        $html.=" forward to: ";
        $html .= "<select name='vfr_merchant[]'>\n";
        foreach($merchants as $merch) {
          if($current_merchant_id != $merch['BankID']) {
          $html.= "<option value='".$merch['BankID']."' ".(($val_based_fwd_rule['merchant'] == $merch['BankID']) ? "selected":"").">".$merch['BankName']."</option>";
          }
        }
        $html .= "</select>\n";
      $html.="</div>\n";
    }
  }
  $html.="</div>";
	$html .= "<a href='#' id='add_rebill_fwd_rule'>[Add Rule]</a>";
  
  $html.="<div id='rebill_fwd_rule_tpl' style='display:none;'>";
    $html.="<div class='rebill_fwd_rule' style='display:none;'>";
      $html.="When value is between $<input type='text' name='__TPL__vfr_start[]' placeholder='$' />";
      $html.=" and $<input type='text' name='__TPL__vfr_end[]' placeholder='$' />";
      $html.=" forward to: ";
      $html .= "<select name='__TPL__vfr_merchant[]'>\n";
      foreach($merchants as $merch) {
        if($current_merchant_id != $merch['BankID']) {
        $html.= "<option value='".$merch['BankID']."' ".((false ) ? "selected":"").">".$merch['BankName']."</option>";
        }
      }
      $html .= "</select>\n";
    $html.="</div>\n";
  $html.="</div>\n";
   
	$html .= "<br />";
  
  
  $html .= "<h3>Upsell Settings</h3>";
  
  
   /*------Upsell------*/
  
  $html .= "<label>Use Custom Upsell Charging paths ?</label>";
	$html .= "<input type='checkbox' name='custom_upsell' ".($_POST['custom_upsell']=="1" ? "checked='checked'":"")." onclick='$(\"#custom_upsell\").slideToggle();' value='1' />";
	$html .= "<br />";
  
  $html.= "<div id='custom_upsell' style='display:".($_POST['custom_upsell']=="1" ? "inline":"none")."'>";
  
  $upsells = array(
    "one_year",
    "six_months",
    "stinkstopper",
    "couch",
    "travel",
    "shipping"
  );
  foreach($upsells as $upsell) {
  $html .= "<label>".ucfirst($upsell)." Upsell Charge On</label>";
	$html .= "<select name='upsell_charges[".$upsell."]'>";  
     foreach($merchants as $m) {
          $html.= "<option value='".$m['BankID']."'".($m['BankID'] == $_POST['upsell_charges'][$upsell] ? " selected":"")."> ".$m['BankName']." </option>\n";          
     }
    
  $html .= "</select>";
	$html .= "<br />";
  }
  
  $html.="</div>";
  
  /*------END Upsell------*/
  
  
  /*------Country Specific Rules------*/
  
  //get the countries
  $country = new umCountry();
  $field = new umField();
  $field->fieldID = 8;		//define country filed ID 
  $field->get_field_options();
  
 // $html.= "<br /><a href='#' onclick='return add_country_detail_fields();'>[Add another Country Rule]</a>\n";  

 
  $html.="<div id='new_country_exceptions_template' style='display:none;'>";
            $html.= "<div class='one_country_rule' style='display:none;'>";
            
            $html.= "<input type='hidden' name='country_rule_counter[%%ID%%]' value='%%ID%%' />\n";
        
            $html.= "<a href='#' onclick='return remove_country_rule(this);'>[Remove this rule]</a>\n<br />\n";
            
            $html.= "Rule for: ";
            
            $html.= "<select name='__TPL__country_rules_country[%%ID%%]'>";
              $html.= "<option value=''>Country...</option>\n";
              for($j = 0; $j < count($field->fieldOptions); $j++) {
              $html.='<option value="' . $field->fieldOptions[$j]->defaultCaption. '">'.htmlspecialchars($field->fieldOptions[$j]->caption). '</option>';
              }             
            $html.= "</select>\n";
				

			
            $html.= "<br />";
          
            $html .= "<label>Use Upsell Flat Fee ?</label>";
            $html .= "<input type='checkbox' name='__TPL__country_rules_use_upsell[%%ID%%]' checked='checked' onclick='$(\"#upsell_data_%%ID%%\").slideToggle();' value='%%ID%%' />";
            $html .= "<br />";
            
            $html.= "<div id='upsell_data_%%ID%%' style='display:inline'>";
            
            $html .= "<label>Upsell Price</label>";
            $html .= "<input type=\"text\" name=\"__TPL__country_rules_upsell_price[%%ID%%]\" value=\"".htmlspecialchars($_POST['upsell_price'])."\" size=\"10\"> (an integer value)";
            $html .= "<br />";
            
            $html .= "<label>Upsell Text</label>";
            $html .= "<input type=\"text\" name=\"__TPL__country_rules_upsell_text[%%ID%%]\" value=\"".htmlspecialchars($_POST['upsell_text'])."\" size=\"50\"> (eg: 'one hundred seventy eight' for an upsell price of 178)";
            $html .= "<br />";
            
            $html.="</div>";
            
            $html .= "<label>Use Monthly Fee ?</label>";
            $html .= "<input type='checkbox' name='__TPL__country_rules_use_monthly[%%ID%%]' checked='checked' onclick='$(\"#monthly_data_%%ID%%\").slideToggle();' value='%%ID%%' />";
            $html .= "<br />";
            
            $html.= "<div id='monthly_data_%%ID%%' style='display:inline'>";
            
            $html .= "<label>Monthly Fee Price</label>";
            $html .= "<input type=\"text\" name=\"__TPL__country_rules_monthly_price[%%ID%%]\" value=\"".htmlspecialchars($_POST['monthly_price'])."\" size=\"10\">(eg: 2.99)";
            $html .= "<br />";
            
            $html .= "<label>Monthly Text</label>";
            $html .= "<input type=\"text\" name=\"__TPL__country_rules_monthly_text[%%ID%%]\" value=\"".htmlspecialchars($_POST['monthly_text'])."\" size=\"50\"> (eg: 'two dollars ninenty nine' for an monthly fee of 2.99)";
            $html .= "<br />";
            
            $html.="</div>";
          
        
        $html.="</div>";
  $html.="</div>\n";
  
  
        $html.= "<div id='country_rules'>";
        
  //check if we have any defined rules so far
  if(is_array($_POST["monthly_country_exceptions"])) {
    $country_rules = $_POST["monthly_country_exceptions"];
    if(is_array($country_rules)) {

      
      foreach($country_rules as $key=>$rule) {
      
        $html.= "<div class='one_country_rule'>";
        
            $html.= "<input type='hidden' name='country_rule_counter[".$key."]' value='".$key."' />\n";
        
            $html.= "<a href='#' onclick='return remove_country_rule(this);'>[Remove this rule]</a>\n<br />\n";
        
            $html.= "Rule for: ";
            
            $html.= "<select name='country_rules_country[".$key."]'>";
              $html.= "<option value=''>Country...</option>\n";
              for($j = 0; $j < count($field->fieldOptions); $j++) {
              $html.='<option value="' . $field->fieldOptions[$j]->defaultCaption. '" '.($field->fieldOptions[$j]->defaultCaption == $rule["country"] ? 'selected':'').'>'.htmlspecialchars($field->fieldOptions[$j]->caption). '</option>';
              }             
            $html.= "</select>\n<br />";
          
            $html .= "<label>Use Upsell Flat Fee ?</label>";
            $html .= "<input type='checkbox' name='country_rules_use_upsell[".$key."]' ".($rule['use_upsell']==1 ? "checked='checked'":"")." onclick='$(\"#upsell_data_".$key."\").slideToggle();' value='".$key."' />";
            $html .= "<br />";
            
            $html.= "<div id='upsell_data_".$key."' style='display:".($rule['use_upsell']==1 ? "inline":"none")."'>";
            
            $html .= "<label>Upsell Price</label>";
            $html .= "<input type=\"text\" name=\"country_rules_upsell_price[".$key."]\" value=\"".($rule['upsell_price'])."\" size=\"10\"> (an integer value)";
            $html .= "<br />";
            
            $html .= "<label>Upsell Text</label>";
            $html .= "<input type=\"text\" name=\"country_rules_upsell_text[".$key."]\" value=\"".($rule['upsell_text'])."\" size=\"50\"> (eg: 'one hundred seventy eight' for an upsell price of 178)";
            $html .= "<br />";
            
            $html.="</div>";
            
            $html .= "<label>Use Monthly Fee ?</label>";
            $html .= "<input type='checkbox' name='country_rules_use_monthly[".$key."]' ".($rule['use_monthly']==1 ? "checked='checked'":"")." onclick='$(\"#monthly_data_".$key."\").slideToggle();' value='".$key."' />";
            $html .= "<br />";
            
            $html.= "<div id='monthly_data_".$key."' style='display:".($rule['use_monthly']==1 ? "inline":"none")."'>";
            
            $html .= "<label>Monthly Fee Price</label>";
            $html .= "<input type=\"text\" name=\"country_rules_monthly_price[".$key."]\" value=\"".($rule['monthly_price'])."\" size=\"10\">(eg: 2.99)";
            $html .= "<br />";
            
            $html .= "<label>Monthly Text</label>";
            $html .= "<input type=\"text\" name=\"country_rules_monthly_text[".$key."]\" value=\"".($rule['monthly_text'])."\" size=\"50\"> (eg: 'two dollars ninenty nine' for an monthly fee of 2.99)";
            $html .= "<br />";
            
            $html.="</div>";
          
        
        $html.="</div>";
      
      }
      
      
    }
    
  }
  $html.="</div>";
  
  /*------END Country Specific Rules------*/
  
  
	

	
	
	$html .= "<br />";
	$html .= "<label></label>";
	
	if($_POST['BankID'] == 0){
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['create']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}else{
		$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"".$lang['buttonCaption']['submit']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	}
	$html .= "<input type=\"reset\" name=\"resetBtn\" value=\"".$lang['buttonCaption']['reset']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
	$html .= "<input type=\"button\" value=\"Go Manage Merchant\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"javascript:location.href='".sess_url($cfg['site']['folder']."admin/manage_merchant.php")."'\" >";
	$html .= "<br />";
	$html .= "<br />";
	$html .= "</fieldset>";
	$html .= "<input type=\"hidden\" name=\"BankID\" value=\"".$_POST['BankID']."\" size=\"15\">";
	$html .= "</form>";
	$html .= "</div>";
	
	return $html;
}

function init_post_value($BankID){
	global $cfg;
	$return = true;
	if($BankID==0){
		$_POST['BankID'] = "0";
		$_POST['BankName'] = "";
		$_POST['tier'] = "";
		$_POST['cap_per_month'] = "";
		$_POST['payment_period'] = "";
		$_POST['persent'] = "";
		$_POST['gatewayType'] = "nmi";
		$_POST['gatewayID'] = "";
		$_POST['gatewayKey'] = "";
		$_POST['gatewaySign'] = "";
		$_POST['combined_charge'] = "no";
		$_POST['bundle'] = 0;    
    $_POST['use_upsell'] = 1;//default
    $_POST['upsell_price'] = 178;//default
    $_POST['upsell_text'] = "one hundred seventy eight";//default
    $_POST['use_monthly'] = 0;//default
    $_POST['rebill_period'] = $cfg['rebill_period'];//default
    $_POST['monthly_price'] = $cfg['product_price_rebill'];//default
    $_POST['monthly_text'] = $cfg['product_price_rebill_text'];//default
    $_POST['monthly_country_exceptions'] = array();//default
    $_POST['forward_rebill'] = "0";
    $_POST['upsell'] = array();
    $_POST['custom_upsell'] = "0";
    $_POST['forward_cvv_rebill'] = "0";
    $_POST['forward_cvv_rebill_forwarded'] = "0";
    $_POST['forward_cvv_rebill_initial'] = "0";
    $_POST['can_process_amex'] = "0";
    $_POST['amex_forward'] = "0";
    $_POST['soft_decline_rebill'] = "0";    
    $_POST['forward_rebill_balance'] = "0";
    $_POST['rebill_fail_action'] = "0";               
    $_POST['rebill_fail_try_after'] = "1";
    $_POST['rebill_fail_merchant'] = "0";      
    $_POST['use_fwd_value_rules'] = "0";
    $_POST['rebill_fwd_value_based_rules'] = array();
    $_POST['initial_forward'] = "0";
    
	}else{
		$merchant = new PayBackEnd();
		$merchant->bankID = $BankID;
		$merchantData = $merchant->get_merchant(!isset($_GET["inactive"]));
		if(count($merchantData)){
			$_POST['BankID'] = $BankID;
			$_POST['BankName'] = $merchantData["BankName"];
			$_POST['tier'] = $merchantData["tier"];
			$_POST['cap_per_month'] = $merchantData["cap_per_month"];
			$_POST['payment_period'] = $merchantData["payment_period"];
			$_POST['persent'] = $merchantData["persent"];
			$_POST['gatewayType'] = $merchantData["gatewayType"];
			$_POST['gatewayID'] = $merchantData["gatewayID"];
			$_POST['gatewayKey'] = $merchantData["gatewayKey"];
			$_POST['gatewaySign'] = $merchantData["gatewaySign"];
			$_POST['combined_charge'] = $merchantData["combined_charge"];
			$_POST['bundle'] = $merchantData["bundle"];      
      $_POST['use_upsell'] = $merchantData["use_upsell"];
      $_POST['upsell_price'] = $merchantData["upsell_price"];
      $_POST['upsell_text'] = $merchantData["upsell_text"]; 
      $_POST['rebill_period'] = $merchantData["rebill_period"];
      $_POST['use_monthly'] = $merchantData["use_monthly"];
      $_POST['monthly_price'] = $merchantData["monthly_price"];
      $_POST['monthly_text'] = $merchantData["monthly_text"];
      $_POST['monthly_country_exceptions'] = strlen($merchantData["monthly_country_exceptions"]) ? unserialize($merchantData["monthly_country_exceptions"]):array();      
      $_POST['forward_rebill'] = $merchantData["forward_rebill"];
      $_POST['upsell_charges'] = strlen($merchantData["upsell_charges"]) ? unserialize($merchantData["upsell_charges"]):array();  
      $_POST['custom_upsell'] = count($_POST['upsell_charges']) > 0 ? "1":"0";
      $_POST['forward_cvv_rebill'] = $merchantData["forward_cvv_rebill"];
      $_POST['forward_cvv_rebill_forwarded'] = $merchantData["forward_cvv_rebill_forwarded"];
      $_POST['forward_cvv_rebill_initial'] = $merchantData["forward_cvv_rebill_initial"];
      $_POST['can_process_amex'] = $merchantData["can_process_amex"];
      $_POST['amex_forward'] = $merchantData["amex_forward"];      
      $_POST['soft_decline_rebill'] = $merchantData["soft_decline_rebill"];
      $_POST['forward_rebill_balance'] = $merchantData["forward_rebill_balance"];      
      $_POST['rebill_fail_action'] = $merchantData["rebill_fail_action"];                
      $_POST['rebill_fail_try_after'] = $merchantData["rebill_fail_try_after"];
      $_POST['rebill_fail_merchant'] = $merchantData["rebill_fail_merchant"];
      $_POST['use_fwd_value_rules'] = $merchantData["use_fwd_value_rules"];
      $_POST['rebill_fwd_value_based_rules'] = strlen($merchantData["rebill_fwd_value_based_rules"]) ? unserialize($merchantData["rebill_fwd_value_based_rules"]):array();
      $_POST['initial_forward'] = $merchantData["initial_forward"]; 
		}else{
			$return = false;
		}
	}
	return $return;
}

function trim_post_value(){
	$_POST['BankName'] = trim($_POST["BankName"]);
	$_POST['cap_per_month'] = trim($_POST["cap_per_month"]);
	$_POST['payment_period'] = trim($_POST["payment_period"]);
	$_POST['gatewayID'] = trim($_POST["gatewayID"]);
	$_POST['gatewayKey'] = trim($_POST["gatewayKey"]);
}

function validate_post_value(){
	global $lang;
	$errorMessage = "";
	
	if($_POST['BankName']==""){
		$errorMessage .= "<li>Bank Name is Empty</li>";;
	}
	
	if($_POST['cap_per_month']==""){
		$errorMessage .= "<li>Capacity period is Empty</li>";;
	}
	
	if($_POST['payment_period']==""){
		$errorMessage .= "<li>Payment Period is Empty</li>";;
	}
	
	if($_POST['gatewayID']==""){
		$errorMessage .= "<li>gateway ID is Empty</li>";;
	}
	
	if($_POST['gatewayKey']==""){
		$errorMessage .= "<li>gateway Key is Empty</li>";;
	}
	
	return $errorMessage;
}
?>