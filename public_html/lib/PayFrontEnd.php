<?php
include_once 'PayBackEnd.php';
include_once 'MyPayPal.php';
include_once 'refund.class.php';
class PayFrontEnd extends MyPayPal{
    var $backEndObj = null;
    var $refundObj = null;
    var $gt_type = "pp";
    var $bank_id = "";
    var $user_id = "";
	var $processor_id = "";
    var $nmi_err_ary = array (
	"100" => "Transaction was Approved",
        "200" => "Transaction was Declined by Processor",
        "201" => "Insufficient Funds",
        "203" => "Over Limit",
        "204" => "Transaction not allowed",
        "220" => "Incorrect Payment Data",
        "221" => "No Such Card Issuer",
        "222" => "No Card Number on file with Issuer",
        "223" => "Expired Card",
        "224" => "Invalid Expiration Date",
        "225" => "Invalid Card Security Code",
        "240" => "Call Issuer for Further Information",
        "250" => "Pick Up Card",
        "251" => "Lost Card",
        "252" => "Stolen Card",
        "253" => "Fraudulant Card",
        "260" => "Declined with further Instructions Available",
        "300" => "Transaction was Rejected by Gateway",
        "400" => "Transaction Error Returned by Processor",
        "410" => "Invalid Merchant Configuration",
        "411" => "Merchant Account is Inactive",
        "420" => "Communication Error",
        "421" => "Communication Error with Issuer" );
		
		private $key = "st4rtursite_jj3do0";

    function PayFrontEnd(){
        $this->backEndObj = new PayBackEnd();
        $this->refundObj = new Refund();
    }
    function getMerchant($amount, $user_email, $type,$country="xx",$is_monthly=false,$card_type="") {
        $config_ary = array();
        $ret_param = $this->backEndObj->getGateway($amount, $user_email, $type,$country,$is_monthly,$card_type);
		
        if( count($ret_param ) > 0 ){
            $this->gt_type = $ret_param[0];
            $config_ary = $ret_param[1];
            $this->bank_id = $ret_param[2];
            $this->processor_id = $ret_param[3];
        }
        return $config_ary;
    }
    
    function getSpecificGateway($bankId,$amount, $user_email, $type,$country="xx",$is_monthly=false,$card_type="") {
        $config_ary = array();
        $ret_param = $this->backEndObj->getSpecificGateway($bankId,$amount, $user_email, $type,$country,$is_monthly,$card_type);
        
		
        if( count($ret_param ) > 0 ){
            $this->gt_type = $ret_param[0];
            $config_ary = $ret_param[1];
            $this->bank_id = $ret_param[2];
            $this->processor_id = $ret_param[3];
        }
        return $config_ary;
    }
    
    
    function doRefund($trans_id,$amount=0) {		
        $ret_param = $this->backEndObj->getGatewayOfTransID($trans_id);
        $config_ary = $ret_param[1];
        if(count($config_ary) == 0){
            $retAry = array ("ACK" => "NoGateway");
            return $retAry;
        }
        $this->setConfig($config_ary);
        if($ret_param[0] == "pp"){
            $nvpResArray = parent::doRefund($trans_id);
        }  
        else if($ret_param[0] == "nmi"){
            $config_ary['endpoint'] = "https://secure.networkmerchants.com/api/transact.php";
            $this->setConfig($config_ary);
            $nvpstr="username=".$config_ary['username']."&password=".$config_ary['password']."&type=refund&transactionid=$trans_id";
            if($amount != 0) $nvpstr .= "&amount=".$amount;
            $nvpResArray = $this->hash_call("",$nvpstr);
            if ( $nvpResArray['responsetext'] == "SUCCESS" || intval($nvpResArray['response_code'])==100){
                $nvpResArray['ACK'] = "Success";
            }else{
                $nvpResArray['L_LONGMESSAGE0'] = $this->nmi_err_ary[$nvpResArray['response_code']];
            }
        } 
		else if($ret_param[0] == "paymentxp"){
            $config_ary['endpoint'] = "https://webservice.paymentxp.com/wh/WebHost.aspx";
            $this->setConfig($config_ary);
			$refund_params = array(
				'MerchantID'		=>	$config_ary['username'],
				'MerchantKey'		=>	$config_ary['password'],
				'TransactionType'	=>	'CreditCardCredit',
				'TransactionID'		=>	$trans_id,
				'ReferenceNumber'	=>	'site_refund',			
			);			
			
			if($amount != 0) {
				$refund_params['TransactionAmount'] = $amount;
			}
			
			$nvpstr = http_build_query($refund_params);
            
            $nvpResArray = $this->hash_call("",$nvpstr);
            if ( $nvpResArray['StatusID'] == "0" ){
                $nvpResArray['ACK'] = "Success";
            }else{
				if ($nvpResArray['StatusID'] == "5" ){
				// Do a void instead
				$refund_params['TransactionType'] = 'CreditCardVoid';
				
				$nvpstr = http_build_query($refund_params);
				
				$nvpResArray = $this->hash_call("",$nvpstr);
				if ( $nvpResArray['StatusID'] == "0" ){
					$nvpResArray['ACK'] = "Success";
				}else{
					$nvpResArray['L_LONGMESSAGE0'] =$nvpResArray['ResponseMessage'];
				}
					
				} else {
                $nvpResArray['L_LONGMESSAGE0'] =$nvpResArray['ResponseMessage'];
				}
            }
        } 
        else  if(in_array($ret_param[0],array("remote-nmi","remote-pp","remote-FD","remote-GPN"))) {
          
            $config_ary['endpoint'] = $config_ary["username"];            
            $this->setConfig($config_ary);  

			$param = array(
				'firstName' => 'John',
				'lastName' => 'Cage',
							);
			
            //build hash
            $hash = md5($param['firstName'].$this->key.$param['lastName']);			
            $nvpstr = "hash=$hash&dorefund=1&transactionid=$trans_id&firstname=".$param['firstName']."&lastname=".$param['lastName'];
	     if($amount != 0) $nvpstr .= "&amount=".$amount;		
            //echo $nvpstr;
            $rr = $this->hash_call("",$nvpstr,true);//get it raw
            //echo "--- ".$rr." ---";
            $nvpResArray = json_decode($rr,true);          
        }
        
        
        
        
        
        
        if($nvpResArray['ACK'] == "Success"){
            $refundable_amount = $this->backEndObj->getRefundable_amount($trans_id);
            if($amount == 0 )$amount = $refundable_amount;
            if($amount>$refundable_amount) $amount = $refundable_amount;
            $set_amount = $refundable_amount - $amount;
            $this->backEndObj->setHistoryRefund($trans_id,$set_amount);
            $this->refundObj->set_refund_history($trans_id, $amount);
        }
		
		
		 $month_folder = date("Y-m-").strtolower(date("M"));
			if(!file_exists(LOG_DIR.$month_folder)) {
				@mkdir(LOG_DIR.$month_folder);
			}
    
        file_put_contents(
                    LOG_DIR.$month_folder."/".date("Y-m-d-")."transaction_log-refunds.log",
                    date("d-m-Y H:i:s")."\nParams:\n".$nvpstr."\nReceived:\n".print_r($nvpResArray,1).
                    "\n\n--------------------------\n",
                    FILE_APPEND);
		
        return $nvpResArray;
    }
    function  setExpress($param,$userid = "") {
        $this->user_id = $userid;
        $amount = $param['amount'];
        $config_ary = $this->getMerchant($amount, $user_id, "pp");
        if(count($config_ary) == 0){
            $retAry = array ("ACK" => "Full");
            return $retAry;
        }
        $this->setConfig($config_ary);
        $param["user_id"] = $userid;
        $param["bank_id"] = $this->bank_id;
        return parent::setExpress($param);
    }
    
    
    
    //THE MEAT
    
    function directPay($param, $user_id,$is_monthly=false,$force_merchant=0,$is_upsell=false){      
		global $handle;
                global $cfg;
        $amount = $param['amount'];    
        $cc_num =   $param['creditCardNumber'];
        
        $is_dev = false;
        
        
        $masked_cc = "XXXXXXXXXXXXXXX";
        
        if(function_exists("mask_cc_number")) {
            $masked_cc = mask_cc_number($param["creditCardNumber"]);
        }
            
		
		
      
  
        
        
        //forcing a gateway if the sale is a monthly payment
        $x_am = $amount;
        
       $card_type = isset($param["creditCardType"]) ? $param["creditCardType"] : "";
       
      
      
      
        if($force_merchant==0) {
         $config_ary = $this->getMerchant($amount, $user_id, "both",$param["country"],false,$card_type); 
        } else {        
          $config_ary = $this->getSpecificGateway($force_merchant,$amount, $user_id, "both",$param["country"],false,$card_type); 
        }
        
        
        $forward_cvv = true;//default
        $forwarded_rebill = false;
        
        //the order in which the rebill forwards are checked is set manually here
        //there should be a mod to make them selectable
        
        //check if it is a rebill, and if the selected merchant has a forward
        
        if(isset($param['is_rebill']) || isset($param['is_yearly_rebill'])) {
          
          if($config_ary['forward_rebill'] > 0) {             
            
            //force this merchant
            $config_ary = $this->getSpecificGateway(
            $config_ary['forward_rebill'], $amount, $user_id, "both",$param["country"],false,$card_type);
            
            $forwarded_rebill = true;
            
          } else {
            
            //do we have a load balancing for the rebill ?
             if($config_ary['forward_rebill'] == -1) { 
             
              //grab the balance
             $existing_percentages = array();
             
              $parts = explode(";",$config_ary['forward_rebill_balance']);
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
              
              //build a min-max array
              $min_max_array = array();
              $local_sum = 0;
              foreach($existing_percentages as $mid=>$forward_rebill_percentage) {
                $min_max_array[$mid] = array(
                  "min" =>$local_sum,
                  "max" =>$local_sum+$forward_rebill_percentage,
                );
                $local_sum+=$forward_rebill_percentage;
              }
              
              //choose one, based on its percentage
              $random_forwarding_number = rand(1,$local_sum);
              
             foreach($min_max_array as $mid=>$min_max_part) {
             
              if($random_forwarding_number>$min_max_part['min'] AND $random_forwarding_number<=$min_max_part['max']) {
                //we got it, force it
                //force this merchant
                $config_ary = $this->getSpecificGateway(
                $mid, $amount, $user_id, "both",$param["country"],false,$card_type);
                
                $forwarded_rebill = true;
                
              }
              
             }
              
              
             
             } else {
              //check forward based on value
              if($config_ary['use_fwd_value_rules']>0) {
              
                if(strlen($config_ary['rebill_fwd_value_based_rules'])) {
                  $value_fwd_rules = unserialize($config_ary['use_fwd_value_rules']);
                  
                  if(is_array($value_fwd_rules)) {
                    foreach($value_fwd_rules as $value_fwd_rule) {
                      if(
                        ($value_fwd_rule['start']<=$amount) && 
                        ($amount<=$value_fwd_rule['start']) &&
                        (!$forwarded_rebill)
                        ) {
                          $config_ary = $this->getSpecificGateway(
                              $value_fwd_rule['merchant'], $amount, $user_id, "both",$param["country"],false,$card_type
                              );
                
                          $forwarded_rebill = true;                        
                        }
                    }
                  }
                }
                
              
              }              
             }
            
          }
          
          //check if it is an amex and it has an amex rebill forwars
          if($card_type == 'American Express' && $config_ary['amex_forward'] > 0) {
            //force this merchant
            $config_ary = $this->getSpecificGateway(
            $config_ary['amex_forward'], $amount, $user_id, "both",$param["country"],false,$card_type);
            
            $forwarded_rebill = true;
          }
          
        }
        
         
         
       
		
         
         /*
        if(!$is_firstsale) {
        
         $config_ary = $this->getMerchant($amount, $user_id, "offline",$param["country"]); 
        } else {
        
        $config_ary = $this->getMerchant($amount, $user_id, "both",$param["country"]);
        }
        */
        
        if(count($config_ary)==0 || (isset($config_ary["is_capped"]) && ($config_ary['is_capped']===true))){
            $retAry = array("ACK"=>"Full");			
            $retAry['L_LONGMESSAGE0'] = " UID: ".$user_id." \n FM: ".$force_merchant;
            return $retAry;
        }
        
        
        //check if we need to forward the CVV
        
        
          if((isset($param['is_rebill']) || isset($param['is_yearly_rebill']))) {
              
           // it might be a forwarded rebill, we chack against the latest transaction
           // $sql_check_if_forwarded = 'SELECT h.BankID, m.forward_cvv_rebill_forwarded, m.forward_cvv_rebill_initial from mem_merchant_history h inner join mem_merchant m on h.BankID=m.BankID WHERE h.user_email="'.$user_id.'" ORDER BY h.hDate DESC LIMIT 1'; 
           $sql_check_if_forwarded = 'SELECT DISTINCT h.BankID from mem_merchant_history h inner join mem_merchant m on h.BankID=m.BankID WHERE h.user_email="'.$user_id.'" ORDER BY h.hDate DESC'; 
           $result_check_if_forwarded = mysql_query($sql_check_if_forwarded);
	   $forwarded_rebill = false;
	  if(mysql_num_rows($result_check_if_forwarded) > 1){
		$forwarded_rebill = true;	
		}
          
            if($forwarded_rebill) {
		$sql_forwarded_settings = 'SELECT h.BankID, m.forward_cvv_rebill_forwarded, m.forward_cvv_rebill_initial from mem_merchant_history h inner join mem_merchant m on h.BankID=m.BankID WHERE h.user_email="'.$user_id.'" ORDER BY h.hDate DESC LIMIT 1'; 		
                $result_forwarded_settings = mysql_query($sql_forwarded_settings);
                $forwarded_settings = mysql_fetch_object($result_forwarded_settings);
                
                // get the config of the initial merchant
              $initial_config =$forwarded_settings;
                
              $forward_cvv = ($initial_config->forward_cvv_rebill_forwarded == 1);
              
              //check if we need to forward only the initial
              if($initial_config->forward_cvv_rebill_initial > 0) {
				  
                //is this the initial one (on this merchant)?
                  
                  if($initial_config->BankID == $this->bank_id) {
                    //DO NOT FORWARD CVV
                    $forward_cvv = false;
                  }
                  
              }
              
            } else {
              $forward_cvv = ($config_ary['forward_cvv_rebill'] == 1);
            }
            
          }
        
        //echo "fwd: ".print_r($forward_cvv,1);
        $param['forward_cvv'] = $forward_cvv ? 1 : 0;
        
		// $param['forward_cvv'] = 1;
		// $forward_cvv = true;
        
        //check for duplications
        $is_duplicate = false;
        
        $dupe_check_sql = "select transaction_id from transaction_log where email='".$user_id."' and amount='".$amount."' and timestamp > '".date("Y-m-d H:i:s",(time()-86400))."' and status=1";
        $dupe_check_result = mysql_query($dupe_check_sql);
        
        if(mysql_num_rows($dupe_check_result)) {
          $is_duplicate = true;
        }
        
        if($is_duplicate) {
            $nvpResArray['ACK'] = "Failed";
            $nvpResArray['L_LONGMESSAGE0'] = "Sorry, this looks like a duplicate transaction. Please try again later or contact us."."\n";
            return $nvpResArray;
        }
        
        // Check for disabled merchants
        if(isset($cfg['soft_disable_merchants'])) {
            $disabled_merchants = explode(',', $cfg['soft_disable_merchants']);
            
            foreach($disabled_merchants as $dm) {
                if($this->bank_id == $dm) {
                    $nvpResArray['ACK'] = "Failed";
                    $nvpResArray['L_LONGMESSAGE0'] = "Cannot process this merchant (SD)";
                    $this->gt_type = "dummy";
                }
            }            
        }
        
        
        if($this->gt_type == "pp"){
            $this->setConfig($config_ary);
            
            if(isset($cfg['disable_paypal']) && $cfg['disable_paypal']===true) {
                $nvpResArray['ACK'] = "Failed";
                $nvpResArray['L_LONGMESSAGE0'] = "Cannot process Paypal";
            } else {
                $nvpResArray = parent::directPay( $param );            
                $trans_id = $nvpResArray["TRANSACTIONID"];
                
            }

        }
        
        else if($this->gt_type == "dummy") {
            // We do nothing here, this will be handled elsewhere
        }
        
		else if($this->gt_type == "nmi"){
            $config_ary['endpoint'] = "https://secure.networkmerchants.com/api/transact.php";
            $this->setConfig($config_ary);
            $creditCardNumber = urlencode( $param['creditCardNumber']);
            $expDateMonth   =   urlencode( $param['expDateMonth']);
            $padDateMonth   =   str_pad( $expDateMonth, 2, '0', STR_PAD_LEFT );
            $expDateYear    =   urlencode( $param['expDateYear']);
            $expDateYear    =   (int)$expDateYear - 2000;
            $cvv2Number     =   urlencode( $param['cvv2Number']);
            $amount         =   urlencode( $param['amount']);
            $paymentType    =   isset($param['paymentType']) ? urlencode($param['paymentType']) : "Sale";
            $address        =	urlencode( $param['address1'] );
            $city           =	urlencode( $param['city'] );
            $state          =	urlencode( $param['state'] );
            $zip            =	urlencode( $param['zip'] );
            $country        =	urlencode( $param['country'] );
            $phone          =	urlencode( $param['phone'] );
            $firstname      =	urlencode( $param['firstName'] );
            $lastname       =	urlencode( $param['lastName'] );
            $nvpstr = "username=".$config_ary['username']."&password=".$config_ary['password']."&type=$paymentType&ccnumber=$creditCardNumber&ccexp=$padDateMonth$expDateYear";
            
            if($forward_cvv) {
              $nvpstr .= "&cvv=$cvv2Number";
            }
            
			$nvpstr .= "&amount=$amount&firstname=$firstname&lastname=$lastname&phone=$phone&address1=$address&city=$city&state=$state&zip=$zip&country=$country&email=$user_id";
     
        $nvpstr .= "&processor_id=".$this->processor_id;			
      			
			
            $nvpResArray = $this->hash_call("",$nvpstr);        
        
			
            if ( $nvpResArray['response_code'] == "100"){
                $nvpResArray['ACK'] = "Success";
                $trans_id = $nvpResArray["transactionid"];
				//$processor_id = $nvpResArray["processor_id"];
				$processor_id = $this->processor_id;
            }else{
                    $nvpResArray['ACK'] = "Failed";
                    $nvpResArray['L_LONGMESSAGE0'] = $nvpResArray['responsetext']."\n".$this->nmi_err_ary[$nvpResArray['response_code']];
            }
        }
		else if($this->gt_type == "paymentxp"){
		
            $config_ary['endpoint'] = "https://webservice.paymentxp.com/wh/WebHost.aspx";
            $this->setConfig($config_ary);
			
			
            $padDateMonth   =   str_pad( $param['expDateMonth'], 2, '0', STR_PAD_LEFT );
			$expDateYear    =   urlencode( $param['expDateYear']);
            $expDateYear    =   (int)$expDateYear - 2000;
			
			$paymentxp_params = array(
				'TransactionType'		=>	'CreditCardCharge',
				'MerchantID'			=>	$config_ary['username'],
				'MerchantKey'			=>	$config_ary['password'],
				'CardNumber'			=>	$param['creditCardNumber'],
				'ExpirationDateMMYY'	=>	$padDateMonth.$expDateYear,
				'TransactionAmount'		=>	$param['amount'],
				'BillingNameFirst'		=>	$param['firstName'],
				'BillingNameLast'		=>	$param['lastName'],
				'BillingFullName'		=>	$param['firstName']." ".$param['lastName'],
				'BillingAddress'		=>	$param['address1'],
				'BillingZipCode'		=>	$param['zip'],
				'BillingCity'			=>	$param['city'],
				'BillingState'			=>	$param['state'],
				'BillingCountry'		=>	$param['country'],
				'EmailAddress'			=>	$user_id,
				'PhoneNumber'			=>	$param['phone'],
				'ReferenceNumber'		=>	'site_payment',
				
			);
			
			 if($forward_cvv) {
              $paymentxp_params['CVV2'] = $param['cvv2Number'];
            }
			
			if(isset($param["ip"])) {
				$paymentxp_params['ClientIPAddress'] = $param["ip"];
			}			
      		
			$nvpstr = http_build_query($paymentxp_params);
			
                        $nvpResArray = $this->hash_call("",$nvpstr );
            
                    
     
			
            if ( $nvpResArray['StatusID'] == "0"){
                $nvpResArray['ACK'] = "Success";
                $trans_id = $nvpResArray["TransactionID"];
				$nvpResArray['transactionid'] = $trans_id;
				$processor_id = $this->processor_id;
            }else{
                    $nvpResArray['ACK'] = "Failed";
                    $nvpResArray['L_LONGMESSAGE0'] = $nvpResArray['ResponseMessage']."\n";
            }
			
        }
        else if($this->gt_type == "besecure") {        
          
            $config_ary['endpoint'] = $config_ary["username"];
            $this->setConfig($config_ary);
            $creditCardNumber = urlencode( $param['creditCardNumber']);
            $expDateMonth   =   urlencode( $param['expDateMonth']);
            $padDateMonth   =   str_pad( $expDateMonth, 2, '0', STR_PAD_LEFT );
            $expDateYear    =   urlencode( $param['expDateYear']);
            $expDateYear    =   (int)$expDateYear - 2000;
            $cvv2Number     =   urlencode( $param['cvv2Number']);
            $amount         =   urlencode( $param['amount']);
            $paymentType    =   isset($param['paymentType']) ? urlencode($param['paymentType']) : "Sale";
            $address        =	urlencode( $param['address1'] );
            $city           =	urlencode( $param['city'] );
            $state          =	urlencode( $param['state'] );
            $zip            =	urlencode( $param['zip'] );
            $country        =	urlencode( $param['country'] );
            $phone          =	urlencode( $param['phone'] );
            $firstname      =	urlencode( $param['firstName'] );
            $lastname       =	urlencode( $param['lastName'] );
            $email          =	urlencode( $param['email'] );
        
         

          // the object to hold the data
          $params = array(
            'x_version' => '3.1',
            'x_delim_data' => 'TRUE',
            'x_relay_response' => 'FALSE',
          // 	'x_test_request' => 'TRUE', // this is a test request variable
            'x_type' => 'AUTH_CAPTURE',
            'x_login' => $config_ary['password'], // the merchant id for the gateway
            'x_tran_key' => $config_ary['signature'], // the shared secret for this account

          // Payment information
            'x_amount' => $amount,
            'x_card_num' => $creditCardNumber,
            'x_exp_date' => $padDateMonth.'/'.$expDateYear,
            'x_card_code' => $cvv2Number,
            'x_first_name' => $firstname,
            'x_last_name' => $lastname,
            'x_address' => $address,
            'x_city' => $city,
            'x_state' => $state,
            'x_zip' => $zip,
            'x_phone' => $phone,
            'x_customer_ip' => $_SERVER['REMOTE_ADDR'],
            'x_email' => $email,
            'x_country' => $country ,
            //testing
            //'x_test_request'=>'TRUE'
          );

          $qs = ''; // the string in which to collect the parameters in Query String format
          foreach ($params as $key => $value)
            $qs .= $key.'='.urlencode($value).'&';
          $qs = substr($qs, 0, strlen($qs) - 1); // remove trailing & character
          echo "QS: ".$qs;
           $nvpResArray = $this->hash_call("",$qs);print_r($nvpResArray);die();
            if ( $nvpResArray[' ResponseCode'] == "1"){
                $nvpResArray['ACK'] = "Success";
                $trans_id = $nvpResArray[" TransactionID"];
                $processor_id = 0;
            }else{
                    $nvpResArray['ACK'] = "Failed";
                    $nvpResArray['L_LONGMESSAGE0'] = $nvpResArray[' ResponseReasonText']."\n";
            }
          
        }
		else if($this->gt_type == "payvt") { 
			
			
		
            $rerouting_first_sale = false;
            
            //process firstsale offline, in order to process the upsell along with this transaction
            if($is_firstsale) {
            $rerouting_first_sale = true;
            }
            
            
            if($rerouting_first_sale) {
              
                                  //check for mastercard
                                  preg_match('/^5[1-5][0-9]{14}/',$cc_num ,$mc_matches);
                                  
                                  if(!count($mc_matches)) {
                                  
                                        //save it as real
                                        $param = array(
                                              "bankid"  =>  30,
                                              "transactionid"  =>  "offline-firstsale",
                                              "userid"  =>  $user_id,
                                              "methodtype"  =>  "direct",
                                              "amount"  =>  $amount,
                                              "processor_id" => 0
                                      );
                                      $this->backEndObj->setHistory($param);
                                    
                                      //start, or continue, logging on this user
                                      require_once(dirname(__FILE__)."/log.class.php");
                                      Logger::log("transaction");
                                     
                                      $rr = array();
                                      $rr['ACK'] = "Success";
                                      return $rr;
                                 
                                 } else {
                                    //fail because of mastercard
                                     $nvpResArray['ACK'] = "Failed";
                                    $nvpResArray['L_LONGMESSAGE0'] = "Sorry, your card seems to be a Mastercad, which we do not support for the time being."."\n";
                                    return $nvpResArray;
                                 }
              
            }
			
			
			
          
            $config_ary['endpoint'] = "https://admin.payvt.com/api/cc.php";
            $this->setConfig($config_ary);
            $creditCardNumber = urlencode( $param['creditCardNumber']);
            $expDateMonth   =   urlencode( $param['expDateMonth']);
            $padDateMonth   =   str_pad( $expDateMonth, 2, '0', STR_PAD_LEFT );
            $expDateYear    =   urlencode( $param['expDateYear']);
            $expDateYear    =   (int)$expDateYear - 2000;
            $cvv2Number     =   urlencode( $param['cvv2Number']);
            $amount         =   urlencode( $param['amount']);
            $paymentType    =   isset($param['paymentType']) ? urlencode($param['paymentType']) : "Sale";
            $address        =	urlencode( $param['address1'] );
            $city           =	urlencode( $param['city'] );
            $state          =	urlencode( $param['state'] );
            $zip            =	urlencode( $param['zip'] );
            $country        =	urlencode( $param['country'] );
            $phone          =	urlencode( $param['phone'] );
            $firstname      =	urlencode( $param['firstName'] );
            $lastname       =	urlencode( $param['lastName'] );
            $email          =	urlencode( $param['email'] );
        
         
			$amount = strpos($amount,".")>0 ? $amount:($amount.".00");

          // the object to hold the data
          $request = array(
			"site_id" => $config_ary['username'],
			"password" => $config_ary['password'],
			"amount" => $amount,
			"type" => 1,//Sale
			"merchant_reference" => "Ref " . uniqid(),
			"currency" => "USD",
			"ip" => $_SERVER["REMOTE_ADDR"],
			"firstname" => $firstname,
			"lastname" => $lastname,
			"address" => $address,
			"city" => $city,
			"zipcode" => $zip,
			"state" => $state,
			"country" => $country,
			"phone" => $phone,
			"email" => $email,
			"product" => "Product",
			"card_holder" => $firstname." ".$lastname,
			"card_number" => $creditCardNumber,
			"expiry_month" => $padDateMonth,
			"expiry_year" => $expDateYear,
			"cvv" => $cvv2Number,
		);

          $qs = http_build_query($request); // the string in which to collect the parameters in Query String format
         // echo "QS: ".$qs;
           $nvpResArray = $this->hash_call("",$qs);//print_r($nvpResArray);die();
            if ( $nvpResArray['message'] == "Success"){
                $nvpResArray['ACK'] = "Success";
                $trans_id = $nvpResArray["transaction_id"];
                $processor_id = 0;
            }else{
                    $nvpResArray['ACK'] = "Failed";
                    $nvpResArray['L_LONGMESSAGE0'] = $nvpResArray[' message']." / ".$nvpResArray[' result']."\n";
            }
          
        }
		
		else if($this->gt_type == "ecpss") {        
          
            
             $ReturnURL = "http://greenopenings.com/ecpss_receive_post.php"; 
             
            $config_ary['endpoint'] = "https://security.sslepay.com/sslpayment";
            //$config_ary['endpoint'] = "http://greenopenings.com/ecpss_receive_post.php";
            $this->setConfig($config_ary);
            $creditCardNumber = ( $param['creditCardNumber']);
            $expDateMonth   =   ( $param['expDateMonth']);
            $padDateMonth   =   str_pad( $expDateMonth, 2, '0', STR_PAD_LEFT );
            $expDateYear    =   ( $param['expDateYear']);
            $expDateYear    =   (int)$expDateYear - 2000;
            $cvv2Number     =   ( $param['cvv2Number']);
            $amount         =   ( $param['amount']);
            $paymentType    =   isset($param['paymentType']) ? ($param['paymentType']) : "Sale";
            $address        =	( $param['address1'] );
            $city           =	( $param['city'] );
            $state          =	( $param['state'] );
            $zip            =	( $param['zip'] );
            $country        =	( $param['country'] );
            $phone          =	( $param['phone'] );
            $firstname      =	( $param['firstName'] );
            $firstname       =	( $param['lastName'] );
            $email          =	( $param['email'] );
        
         

         $amount = strpos($amount,".")>0 ? $amount:($amount.".00");
         
          // the object to hold the data
          
             $MD5key = "dRs[[R}~";		
             $MerNo = "5173";				
             $BillNo =date("his");		
             $Currency = "1";				
             $Language = "2";            
             
             $md5src = $MerNo.$BillNo.$Currency.$amount.$Language.$ReturnURL.$MD5key;		
             $MD5info = strtoupper(md5($md5src));		
          
          
          $params = array(
            "MerNo"=>$MerNo,
            "BillNo"=>$BillNo,
            "Amount"=>$amount,
            "Currency"=>$Currency,
            "Language"=>$Language,
            "ReturnURL"=>$ReturnURL,
            "MD5info"=>$MD5info,
            "Remark"=>"This is a test",
            "products"=>"various",
            "shippingFirstName"=>$firstname,
            "shippingLastName"=>$firstname,
            "shippingEmail"=>$email,
            "shippingPhone"=>$phone,
            "shippingZipcode"=>$zip,
            "shippingAddress"=>$address,
            "shippingCity"=>$city,
            "shippingSstate"=>$state,
            "shippingCountry"=>$country,
            "b1"=>"Payment",
          );

          $qs = ''; // the string in which to collect the parameters in Query String format
          foreach ($params as $key => $value)
            $qs .= $key.'='.($value).'&';
          $qs = substr($qs, 0, strlen($qs) - 1); // remove trailing & character
          echo "ECPSS: ".str_replace("&","\n",$qs);
           $nvpResArray = $this->hash_call("",$qs,true);
           echo "\n\n";
           print_r($nvpResArray);
           
           die();
            if ( $nvpResArray[' ResponseCode'] == "1"){
                $nvpResArray['ACK'] = "Success";
                $trans_id = $nvpResArray[" TransactionID"];
                $processor_id = 0;
            }else{
                    $nvpResArray['ACK'] = "Failed";
                    $nvpResArray['L_LONGMESSAGE0'] = $nvpResArray[' ResponseReasonText']."\n";
            }
          
        }
		else if($this->gt_type == "remote-nmi" || $this->gt_type == "remote-pp" || $this->gt_type == "remote-FD" || $this->gt_type == "remote-GPN") {
		
			
      
          $config_ary['endpoint'] = $config_ary["username"];
          
		  $remote_bank_id = $config_ary['password'];
			
        
            $this->setConfig($config_ary);
            $creditCardNumber = urlencode( $param['creditCardNumber']);
            $expDateMonth   =   urlencode( $param['expDateMonth']);
            $padDateMonth   =   str_pad( $expDateMonth, 2, '0', STR_PAD_LEFT );
            $expDateYear    =   urlencode( $param['expDateYear']);
            $expDateYear    =   (int)$expDateYear;
            $cvv2Number     =   urlencode( $param['cvv2Number']);
            $amount         =   urlencode( $param['amount']);
            $paymentType    =   isset($param['paymentType']) ? urlencode($param['paymentType']) : "Sale";
            $address        =	urlencode( $param['address1'] );
            $city           =	urlencode( $param['city'] );
            $state          =	urlencode( $param['state'] );
            $zip            =	urlencode( $param['zip'] );
            $country        =	urlencode( $param['country'] );
            $phone          =	urlencode( $param['phone'] );
            $firstname      =	urlencode( $param['firstName'] );
            $lastname       =	urlencode( $param['lastName'] );
			$cardname       =	urlencode( $param['cardname'] );
            $ip = isset($param["ip"]) ? urlencode($param["ip"]):urlencode($_SERVER['REMOTE_ADDR']);
            
           
			
			//build hash
			$hash = md5($param['firstName'].$this->key.$param['lastName']);
			
            $nvpstr = "hash=$hash&bank=$remote_bank_id&is_monthly=".($is_monthly ? "1":"0")."&creditcardno=$creditCardNumber&expiration_year=$expDateYear&expiration_month=$expDateMonth";
			$nvpstr .= "&securitycode=$cvv2Number&amount=$amount&firstname=$firstname&lastname=$lastname&cardname=$cardname&phone=$phone&address1=$address&city=$city&state=$state&zip=$zip&country=$country&email=$user_id&ip=$ip";
			$nvpstr .= "&processor_id=".$this->processor_id;
      
      
			//echo $nvpstr;
            $rr = $this->hash_call("",$nvpstr,true);//get it raw
            
            //file_put_contents("gpn_log.log",date("d-m-Y H:i:s")."\n".$rr."\n\n",FILE_APPEND);
        
            if($is_monthly) {
			//echo "\n--- ".$rr." ---\n";
            }
      
			$nvpResArray = json_decode($rr,true);
     
		  //make sure the transaction ID is saved
		  
		  if(isset($nvpResArray["transactionid"])) {
			  $trans_id = $nvpResArray["transactionid"];
		  }
		  
		  if(isset($nvpResArray["TRANSACTIONID"])) {
			  $trans_id = $nvpResArray["TRANSACTIONID"];
		  }
		  
		   if(isset($nvpResArray["processor_id"])) {
			  $processor_id = $nvpResArray["processor_id"];
		  }
      
      
         
	   } 
		else if($this->gt_type == "offline"){

      $transaction_description = isset($param["transaction_description"]) ? $param["transaction_description"] : "";
      $is_rebill = isset($param["is_rebill"]) ? $param["is_rebill"] : 0;
		

                                        $param = array(
                                              "bankid"  =>  $this->bank_id,
                                              "transactionid"  =>  "offline",
                                              "userid"  =>  $user_id,
                                              "methodtype"  =>  "direct",
                                              "amount"  =>  $amount,
                                              "processor_id" => 0,
                                              "transaction_description" =>  $transaction_description,
                                              "is_rebill" =>  $is_rebill,
                                      );
                                        
                                      $this->backEndObj->setHistory($param);
                                    
                                      //start, or continue, logging on this user
                                      require_once(dirname(__FILE__)."/log.class.php");
                                      Logger::log("transaction");
                                     
                                      $rr = array();
                                      $rr['ACK'] = "Success";
                                      return $rr;
		
		
	   }
		else{
			$nvpResArray['ACK'] = "Failed";
			$nvpResArray['L_LONGMESSAGE0'] = "Bad Gateway";
		}
    
        
		
    
    $month_folder = date("Y-m-").strtolower(date("M"));
    if(!file_exists(LOG_DIR.$month_folder)) {
      mkdir(LOG_DIR.$month_folder, 0777, true);
    }
    
    // Mask CC and CVV
    $param["creditCardNumber"] = $masked_cc;
    $cvv_length = strlen($param["cvv2Number"]);
    $param["cvv2Number"] = str_repeat("X", $cvv_length);
    
    file_put_contents(LOG_DIR.$month_folder."/".date("Y-m-d-")."transaction_log.log",date("d-m-Y H:i:s")."\nParams:\n".print_r($param,1)."\nReceived:\n".print_r($nvpResArray,1)."\n\n--------------------------\n",FILE_APPEND);
    
    $status_of_transaction = 0;
   if($nvpResArray['ACK'] == "Success" || $nvpResArray['ACK'] == "SuccessWithWarning"){
    $status_of_transaction = 1;
   }
   
   //what type of transaction to log ?
   $transaction_type = "direct";
   
   if($param['is_rebill']) {
     $transaction_type = "rebill";
   }
   
   //save the bank id into the response
   $nvpResArray['bank_id'] = $this->bank_id;
   
   //if he does not have a previous rebill, and this is not a rebill, this should be an upsell (probably)
  if($is_upsell) {
    $transaction_type = "upsell";
   }
   
   log_transaction(
        array(
        
          "email" =>  $user_id,
          "amount"  => $param['amount'],
          "raw"=> print_r($nvpResArray,1),
          "status" => $status_of_transaction,
          "txid" => $trans_id,
          "type" => $transaction_type,
        
        )      
      );
      
      
    
    

        if($nvpResArray['ACK'] == "Success" || $nvpResArray['ACK'] == "SuccessWithWarning"){
        
          
        
            //get user history, see if he has a previous sale
            $old_payments = $this->backEndObj->getPayHistory($user_id);
            
            $previous_sale = "";
            
            if(is_array($old_payments) && count($old_payments)) {
              $previous_sale = implode(";",$old_payments);
            }
            
            
            
            $transaction_description = isset($param["transaction_description"]) ? $param["transaction_description"] : "";
            $is_rebill = isset($param["is_rebill"]) ? $param["is_rebill"] : "";
            
            
            
            $param = array(
                    "bankid"  =>  $this->bank_id,
                    "transactionid"  =>  $trans_id,
                    "userid"  =>  $user_id,
                    "methodtype"  =>  "direct",
                    "amount"  =>  $amount,
                    "processor_id" => $processor_id,
                    "previous_sale" => $previous_sale,
                    "raw_response"  => serialize($nvpResArray),
                    "transaction_description" =>  $transaction_description,
                    "is_rebill" =>  $is_rebill,
            );
			
			
            $this->backEndObj->setHistory($param);
           
            //remove the offline transaction from the logs
          //  $this->backEndObj->delete_user_history($user_id,'offline-firstsale');
            
            //mysql_query("delete from mem_merchant_history where user_email='".$user_id."' and transaction_id='offline-firstsale'");
            
            //start, or continue, logging on this user
            require_once(dirname(__FILE__)."/log.class.php");
            Logger::log("transaction");
            
            //uniformize the success response
            $nvpResArray['ACK'] = "Success";
            
        } else {
        
          //save this card into the declined cards table except if it is pending a 3ds validation
          if($nvpResArray['ACK'] != "Pending") {
          /*
          //remove from transactions
          mysql_query("delete from mem_merchant_history where user_email='".$user_id."' and transaction_id='offline-firstsale'");
          //remove from temp users
          mysql_query("delete from mem_user_temp where Email='".$user_id."' limit 1");
          */
		  
		 
		  
          $sql = "insert into declined_cards set card_hash='".md5($cc_num)."',amount='".$amount."',user='".$user_id."',error='".(isset($nvpResArray['L_LONGMESSAGE0']) ? $nvpResArray['L_LONGMESSAGE0'] :"unknown")."',bank_id=".$this->bank_id.",`when`='".date("Y-m-d H:i:s")."',country='".$param["country"]."'";
          mysql_query($sql);
          
          }
          
        }
        
        
	
        return $nvpResArray;
    }
	
	
}


?>
