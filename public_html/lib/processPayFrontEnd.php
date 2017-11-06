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
    function getMerchant($amount, $user_email, $type,$country="xx",$is_monthly=false) {
        $config_ary = array();
        $ret_param = $this->backEndObj->getGateway($amount, $user_email, $type,$country,$is_monthly);
		
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
            if ( $nvpResArray['responsetext'] == "SUCCESS" ){
                $nvpResArray['ACK'] = "Success";
            }else{
                $nvpResArray['L_LONGMESSAGE0'] = $this->nmi_err_ary[$nvpResArray['response_code']];
            }
        } 
        else  if(in_array($ret_param[0],array("remote-nmi","remote-pp","remote-FD","remote-GPN"))) {
          
            $config_ary['endpoint'] = $config_ary["username"];            
            $this->setConfig($config_ary);            
            //build hash
            $hash = md5($param['firstName'].$this->key.$param['lastName']);			
            $nvpstr = "hash=$hash&dorefund=1&transactionid=$trans_id";
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
    function directPay($param, $user_id,$is_monthly=false){      
		global $handle;
        $amount = $param['amount'];    
        $cc_num =   $param['creditCardNumber'];
        
        $is_dev = false;
        
        //check if this is firstsale
        $firstsale_check = "SELECT hKey FROM  `mem_merchant_history` WHERE user_email='".$user_id."' AND hAmount IN ('97','59.94', '39.94', '24.94','36.95') limit 1";
		
		
		
       // $firstsale_result = mysql_query($firstsale_check);
        
        $is_firstsale = true;
		
        /*
        if(mysql_num_rows($firstsale_result)) {
          $is_firstsale = false;
        }   */
			
		$is_firstsale = false;
		
      
        
        //dev testing 
		/*
        if(in_array($_SERVER["REMOTE_ADDR"],array(
              "86.107.250.233",//vlad
              "92.86.216.93",//vlad second IP
              "66.31.78.214",//prescott              
        ))) {
        
        $is_dev = true;
        
         if($cc_num=="4111111111111115"){
          //this is a test from the devaloper
          
           $rr = array();
          $rr['ACK'] = "Success";
          return $rr;
          
        }         
         
        }
        */
        
        
        //forcing a gateway if the sale is a monthly payment
        $x_am = $amount;
        
        if($is_monthly) {
            
            $config_ary = $this->getMerchant($amount, $user_id, "both",$param["country"],true); 
      
        } else {
        
            
        
         //check if this card is between the declined cards
		 //allow for 3 attempts
         $sql_test_declined = "select error from declined_cards where card_hash='".md5($cc_num)."' and amount='".$amount."'";
         $result_test_declined = mysql_query($sql_test_declined);
         
         if(mysql_num_rows($result_test_declined)>2) {
          $declined_row = mysql_fetch_object($result_test_declined);
          //this card has already been declined !
          //return an error to the user, letting him know of this fact
          $declined = array();
          $declined['ACK'] = "Failed";
          $declined['L_LONGMESSAGE0'] = "This card has already been declined by the bank for reason: ".$declined_row->error." . Do not attempt to make this purchase again with this card.  If you are having trouble accessing your account, please e-mail info@internetprofitpacket.com with your problem or call (877) 345-2224 to set up your account.";
          return $declined;
         }
        
         $config_ary = $this->getMerchant($amount, $user_id, "offline",$param["country"]); 
         
        
        }
		
         
         /*
        if(!$is_firstsale) {
        
         $config_ary = $this->getMerchant($amount, $user_id, "offline",$param["country"]); 
        } else {
        
        $config_ary = $this->getMerchant($amount, $user_id, "both",$param["country"]);
        }
        */
        
        if(count($config_ary)==0){
            $retAry = array("ACK"=>"Full");
            return $retAry;
        }
        if($this->gt_type == "pp"){
            $this->setConfig($config_ary);
          
            $nvpResArray = parent::directPay( $param );
            $trans_id = $nvpResArray["TRANSACTIONID"];

        }else if($this->gt_type == "nmi"){
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
			$nvpstr .= "&cvv=$cvv2Number&amount=$amount&firstname=$firstname&lastname=$lastname&phone=$phone&address1=$address&city=$city&state=$state&zip=$zip&country=$country&email=$user_id";
			$nvpstr .= "&processor_id=".$this->processor_id;
            $nvpResArray = $this->hash_call("",$nvpstr);
            if ( $nvpResArray['response_code'] == "100"){
                $nvpResArray['ACK'] = "Success";
                $trans_id = $nvpResArray["transactionid"];
				$processor_id = $nvpResArray["processor_id"];
            }else{
                    $nvpResArray['ACK'] = "Failed";
                    $nvpResArray['L_LONGMESSAGE0'] = $nvpResArray['responsetext']."\n".$this->nmi_err_ary[$nvpResArray['response_code']];
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
			//echo "doing remote!";
			
      
          $config_ary['endpoint'] = $config_ary["username"];
          
          $madders_rerouting = false;        
           
          
          
          
            
            if($this->gt_type=="remote-GPN") {  
            
            //check for countries that are banned in GPN
            
              $banned_countries = array(
                "AF",//Afganistan
                "BD", //Bangladesh
                "GH",// Ghana
                "IQ", //Iraq
               "KE", // Kenya
                "MN",// Mongolia
                "NG",// Nigeria
                 "PK",//Pakistan
                "PS", //Palestinian Territories
                "RO",// Romania,
                "SD",// Sudan
                 "UG",//Uganda
                 "VE",//Venezuela,
               "VN", // Vietnam
               "ZM", // Zambia
                "ZW",// Zimbabwe
              );
              
              if(in_array($param["country"],$banned_countries) && !$is_dev) {
              
                $bad_country = array(
              "ACK"=>"Failed",
              "L_LONGMESSAGE0"=>"Sorry, we cannot process data from your country.",
              );
               return  $bad_country;            
              }
           
            
            
            
            //$rerouting = true;
            
            $offline_processing = false;
            $rerouting_first_sale = false;
            
            //process firstsale offline, in order to process the upsell via 3DS
            if($is_firstsale) {
            $offline_processing = false;
            $rerouting_first_sale = true;
            }
            
            
                 
                  
            $offline_rerouting_countries = array(
                     // "US",//United States
            );
                  
            if(in_array($param["country"],$offline_rerouting_countries)) {
            
              $offline_processing = true;
              $rerouting_first_sale = false;
            
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
			
			//DO MADDERS FOR US
			/*
			if($offline_processing) {
			
			$config_ary['endpoint'] = "http://internetprofitpacket.com/remote_gateway.php";
			
			$offline_processing = false;
			}
			*/
            
            if($offline_processing) {
            
                  
                            
                            
                            
                                  //check for mastercard
                                  preg_match('/^5[1-5][0-9]{14}/',$cc_num ,$mc_matches);
                                  
                                  if(!count($mc_matches)) {
                                  
                                        //save it as real
                                        $param = array(
                                              "bankid"  =>  30,
                                              "transactionid"  =>  "offline",
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
            
            
            
            
            
            
            
            
            
            }
			
			
        
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
			
            $nvpstr = "hash=$hash&creditcardno=$creditCardNumber&expiration_year=$expDateYear&expiration_month=$expDateMonth";
			$nvpstr .= "&securitycode=$cvv2Number&amount=$amount&firstname=$firstname&lastname=$lastname&cardname=$cardname&phone=$phone&address1=$address&city=$city&state=$state&zip=$zip&country=$country&email=$user_id&ip=$ip";
			$nvpstr .= "&processor_id=".$this->processor_id;
      
      
        //3ds
        if($amount != "5.99") {
              
              $nvpstr .= "&secure=1";
              
              
            }
        	
      
      
      /*
      //No delaying
      
       if(!$madders_rerouting) {
      
      //check if this user has more than 60 seconds passed from when he last did a purchase
      //we check this because of a GPN rule that rejects payments from the same IP that
      //are in that window of time
      $sql_check = "SELECT hKey FROM  `mem_merchant_history` WHERE user_email='".$user_id."' AND hDate >  ADDTIME(NOW(),'-00:01:00') AND transaction_id<>'offline-firstsale'  order by hDate desc limit 1";
    $result_check = mysql_query($sql_check);
    
    if(mysql_num_rows($result_check)) {
      //we have to put this on a waiting table
      $sql_delay = "INSERT into gpn_delay set user_email='".$user_id."',amount='".$amount."',ip='".$ip."',placed=NOW()";
      
      $result_delay = mysql_query($sql_delay);
      
      if($result_delay) {
        //ok, we palced it in the backburner, sent an OK result back
        $rr = array();
        $rr['ACK'] = "Success";
        return $rr;
      }
      
    }
    
    }
      */
      
			//echo $nvpstr;
            $rr = $this->hash_call("",$nvpstr,true);//get it raw
            
            file_put_contents("gpn_log.log",date("d-m-Y H:i:s")."\n".$rr."\n\n",FILE_APPEND);
        
            if($is_monthly) {
			//echo "\n--- ".$rr." ---\n";
            }
      
			$nvpResArray = json_decode($rr,true);
      if($is_monthly) {
     // echo print_r($nvpResArray,1)."\n";
      }
      //make sure the transaction ID is saved
      
      if(isset($nvpResArray["transactionid"])) {
          $trans_id = $nvpResArray["transactionid"];
      }
      
       if(isset($nvpResArray["processor_id"])) {
          $processor_id = $nvpResArray["processor_id"];
      }
      
      
         
		}
		else{
			$nvpResArray['ACK'] = "Failed";
			$nvpResArray['L_LONGMESSAGE0'] = "Bad Gateway";
		}

        if($nvpResArray['ACK'] == "Success"){
        
            //get user history, see if he has a previous sale
            $old_payments = $this->backEndObj->getPayHistory($user_id);
            
            $previous_sale = "";
            
            if(is_array($old_payments) && count($old_payments)) {
              $previous_sale = implode(";",$old_payments);
            }
        
            $param = array(
                    "bankid"  =>  $this->bank_id,
                    "transactionid"  =>  $trans_id,
                    "userid"  =>  $user_id,
                    "methodtype"  =>  "direct",
                    "amount"  =>  $amount,
                    "processor_id" => $processor_id,
                    "previous_sale" => $previous_sale,
            );
			
			
            $this->backEndObj->setHistory($param);
           
            //remove the offline transaction from the logs
            $this->backEndObj->delete_user_history($user_id,'offline-firstsale');
            
            //mysql_query("delete from mem_merchant_history where user_email='".$user_id."' and transaction_id='offline-firstsale'");
            
            //start, or continue, logging on this user
            require_once(dirname(__FILE__)."/log.class.php");
            Logger::log("transaction");
            
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
