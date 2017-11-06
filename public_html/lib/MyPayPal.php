<?php
/*
 * This class is used for DoDirectPayment and Express Checkout.
 * For Express Checkout this class use the SESSION["express"] and SESSION["token"] variables.
 */

/**
 * Description of MyPayPal
 *
 * @author hotmoney
 */
class MyPayPal {

    private $api_username   =   "hotmon_1287553974_biz_api1.gmail.com";
    private $api_password   =   "1287554071";
    private $api_signature  =   "An5ns1Kso7MWUdW4ErQKJJJ4qi4-Ao6KgVbO-wn3EiYB-gzci7GrC7n8";
    
    private $api_version    =   "64.0";
    private $api_subject    =   "";
	private $api_endpoint   =   "https://api-3t.paypal.com/nvp";
	private $paypal_url     =   "https://www.paypal.com/webscr&cmd=_express-checkout&token=";
	// private $api_endpoint   =   "https://api-3t.sandbox.paypal.com/nvp";
	// private $paypal_url     =   "https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=";
    private $proxy_use      =   false;
    private $proxy_host     =   "127.0.0.1";
    private $proxy_port     =   "808";

     /*********************************
     * This function will set Config for PayPal API.     *
     * @access  :   public
     * @param   :   Association Array
     * @return  :   Boolean
     *********************************/
    public function setConfig($param = array()){
        $this->api_username = $param['username'];
        $this->api_password = $param['password'];
        if(isset($param['signature']))  $this->api_signature= $param['signature'];
        if(isset($param['endpoint']))   $this->api_endpoint = $param['endpoint'];
        if(isset($param['version']))    $this->version = $param['version'];
        if(isset($param['subject']))    $this->subject = $param['subject'];
        if(isset($param['paypal_url'])) $this->paypal_url = $param['paypal_url'];
        if(isset($param['proxy_use']))  $this->proxy_use = $param['proxy_use'];
        if(isset($param['proxy_host'])) $this->proxy_host = $param['proxy_host'];
        if(isset($param['proxy_port'])) $this->proxy_port = $param['proxy_port'];
        return true;
    }

    /*********************************
     * This function will take DoDirectPayment Request and make payment via hash_call function.     *
     * @access  :   public
     * @param   :   Assocication Array()
     * @return  :   Array()
     *********************************/

    public function directPay($param, $user_id=0,$is_monthly=false,$force_merchant=0,$is_upsell=false){
		global $handle;
    
    
        $paymentType    =   urlencode( $param['paymentType']);
        $firstName      =   urlencode( $param['firstName']);
        $lastName       =   urlencode( $param['lastName']);
        
        $allowed_ccs = array('Visa','MasterCard','Discover','Amex','Maestro');
        $cctype = $param['creditCardType'];
        if(!in_array($param['creditCardType'],$allowed_ccs)) {
          $cctype = "";//just empty it, so we do not get rejected
          if($param['creditCardType']=='American Express') {
            $cctype = 'Amex';
          }
        }
        $creditCardType =   urlencode( $cctype);
        
        
        $creditCardNumber = urlencode( $param['creditCardNumber']);
        $expDateMonth   =   urlencode( $param['expDateMonth']);
        // Month must be padded with leading zero
        $padDateMonth   =   str_pad($expDateMonth, 2, '0', STR_PAD_LEFT);
        $expDateYear    =   urlencode( $param['expDateYear']);
        $cvv2Number     =   urlencode( $param['cvv2Number']);
        $address1       =   urlencode( $param['address1']);
        $address2       =   urlencode( $param['address2']);
        $city           =   urlencode( $param['city']);
        $state          =   urlencode( $param['state']);
        $zip            =   urlencode( $param['zip']);
        $amount         =   urlencode( $param['amount']);
        $currencyCode   =   isset($param['currency']) ? urlencode( $param['currency']) : "USD";
        $paymentType    =   isset($param['paymentType']) ? urlencode($param['paymentType']) : "Sale";

        $nvpstr="&PAYMENTACTION=$paymentType&AMT=$amount&CREDITCARDTYPE=$creditCardType&ACCT=$creditCardNumber&EXPDATE=".$padDateMonth.$expDateYear;
        
        if(isset($param['forward_cvv'])) {
          if($param['forward_cvv']) {
            $nvpstr.="&CVV2=$cvv2Number";
          }
        } else {
          $nvpstr.="&CVV2=$cvv2Number";
        }
        
        $nvpstr.="&FIRSTNAME=$firstName&LASTNAME=$lastName&STREET=$address1&CITY=$city&STATE=$state&ZIP=$zip&CURRENCYCODE=$currencyCode";
        $nvpstr = $this->get_nvpheader() . $nvpstr;
        $nvpResArray = $this->hash_call("doDirectPayment", $nvpstr);
		
        return $nvpResArray;
    }
     
    /*********************************
     * This function will take SetExpressCheckout Request and excute that using hash_call function.     *
     * Then this will  go to return_url page.
     * @access  :   public
     * @param   :   Assocication Array()
     *              (essential param) return_url, cancel_url, currency_type, amount, payment_type
     *              (optional param) ship_street, ship_city, ship_state, ship_zip ...     * 
     * @return  :   Array()
     *********************************/
    public function setExpress($param){
        $payment_type = $param['payment_type'] == "" ? "Sale" : $param['payment_type'];
        $currency_type = $param['currency_type'] == "" ? "USD" : $param['currency_type'];
        $amount = $param['amount'];
        $return_url = urlencode( $param['return_url']."?payment_type=$payment_type&currency_type=$currency_type&amount=$amount&bank_id=".$param['bank_id']."&user_id=".$param['user_id'] );
        $cancel_url = urlencode( $param['cancel_url'] );
        $error_url  = urlencode( $param['error_url'] );
        $account = $param['pp_account'];
        $nvpstr = "&AMT=".$amount."&PAYMENTACTION=".$payment_type."&ReturnUrl=".$return_url."&CANCELURL=".$cancel_url ."&CURRENCYCODE=".$currency_type."&NOSHIPPING=1";
        if( $account != "" )$nvpstr .= "&EMAIL=".$account;
        $nvpstr = $this->get_nvpheader().$nvpstr;
        $resArray = $this->hash_call("SetExpressCheckout",$nvpstr);
        $_SESSION['reshash'] = $resArray;
        $ack = strtoupper($resArray["ACK"]);
        if($ack=="SUCCESS"){    // Redirect to paypal.com here
			$token = urldecode($resArray["TOKEN"]);
			$payPalURL = $this->paypal_url.$token;
			redirect($payPalURL);
		} else {//Redirecting to errorURL page to display errors.
			redirect($error_url);
		}
        return true;
    }

    public function doRefund($trans_id){
        $nvpstr = "&TRANSACTIONID=$trans_id&REFUNDTYPE=Full";
        $nvpstr = $this->get_nvpheader().$nvpstr;
        $resArray = $this->hash_call("RefundTransaction", $nvpstr);
        return $resArray;
    }


    /*********************************
     * This function will complet ExpressCheckout Transaction and go to success page*
     * @access  :   public
     * @param   :   Assocication Array()
     * @return  :   boolean
     *********************************/
    public function doExpress(){
        $token = urlencode( $_REQUEST['token']);
        $payerID = urlencode($_REQUEST['PayerID']);
        $paymentAmount = urlencode ($_REQUEST['amount']);
        $paymentType = urlencode($_REQUEST['payment_type']);
        $currCodeType = urlencode($_REQUEST['currency_type']);
        $serverName = urlencode($_SERVER['SERVER_NAME']);
        $bank_id = $_REQUEST['bank_id'];
        $user_id = $_REQUEST['user_id'];
        $nvpstr = '&TOKEN='.$token.'&PAYERID='.$payerID.'&PAYMENTACTION='.$paymentType.'&AMT='.$paymentAmount.'&CURRENCYCODE='.$currCodeType.'&IPADDRESS='.$serverName ;
        $nvpstr = $this->get_nvpheader().$nvpstr;
        $resArray = $this->hash_call("DoExpressCheckoutPayment",$nvpstr);
        $trans_id = $resArray["TRANSACTIONID"];
        $amount = $resArray['AMT'];
        if($resArray['ACK']=="Success"){
               $param = array(
                    "bankid"  =>  $bank_id,
                    "transactionid"  =>  $trans_id,
                    "userid"  =>  $user_id,
                    "methodtype"  =>  "checkout",
                    "amount"  =>  $amount
                );
               $this->backEndObj->setHistory($param);
        }
        return $resArray;
    }

    /*********************************
     * This function will really request to paypal for all Payment Request and return the result. *
     * @access  :   public
     * @param   :   $return_format, $_post[]
     * @return  :   XML format
     *********************************/
    public function hash_call($methodName, $nvpStr,$giveraw=false) {
		global $handle;
        $ch = curl_init();
        
        $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        $cookie_file = dirname(__FILE__)."/../cookiefile";
        
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); 
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        //--------
        curl_setopt($ch, CURLOPT_URL, $this->api_endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT,90); 
        
       // curl_setopt($ch, CURLOPT_HEADER, 1); 

       // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
       // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //curl_setopt($ch, CURLOPT_SSLVERSION,3); // verify ssl version 2 or 3
        
        //curl_setopt($ch, CURLOPT_SSLVERSION,CURL_SSLVERSION_TLSv1); // verify ssl version 2 or 3

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        if ($this->proxy_use)
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy_host . ":" . $this->proxy_port);

        if ($methodName != "" && strlen(str_replace('VERSION=', '', strtoupper($nvpStr))) == strlen($nvpStr)) {
            $nvpStr = "&VERSION=" . urlencode($this->api_version) . $nvpStr;
        }
        if($methodName != "") $nvpStr = "METHOD=" . urlencode($methodName) . $nvpStr;        

	curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpStr);

        $response = curl_exec($ch);        
  //echo "sent to [".$this->api_endpoint."][".$nvpStr."]..got back ..-> ".$response." <- ";
        
        if (curl_errno($ch)) {
            $this->error_proc(curl_error($ch));
			$response = curl_exec($ch);
			if (curl_errno($ch)) $this->error_proc(curl_error($ch));
			
			curl_close($ch);
			
        } else {
            curl_close($ch);
        }
        
        /*
       if (curl_errno($ch)) {
       $this->error_proc(curl_error($ch));
      }     */  
        
        
		if($giveraw) {
			return $response;
		}
		
		$nvpResArray = $this->deformatNVP($response);
		
        return $nvpResArray;
    }

    /************************************
     * This function will take NVPString and convert it to an Associative Array and it will decode the response.
     * It is usefull to search for a particular key and displaying arrays.
     * @nvpstr is NVPString.
     * @nvpArray is Associative Array.
     *************************************/
    private function deformatNVP($nvpstr) {

        $intial = 0;
        $nvpArray = array();

        while (strlen($nvpstr)) {
            $keypos = strpos($nvpstr, '=');
            $valuepos = strpos($nvpstr, '&') ? strpos($nvpstr, '&') : strlen($nvpstr);
            $keyval = substr($nvpstr, $intial, $keypos);
            $valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);
            $nvpArray[urldecode($keyval)] = urldecode($valval);
            $nvpstr = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
        }
        return $nvpArray;
    }

    /*********************************
     * This function will take error and return a error message.
     **********************************/
     private function error_proc($error_msg){
		global $handle;
     // echo "..ERR ..-> ".$error_msg." <- ";
        /////////////////////
		date_default_timezone_set('Asia/Shanghai'); 
        $handle = fopen("log_curl.txt","a+");
        fwrite($handle, "\n ---------DateTime=".date("Y-m-d H:i:s")."---------- \n".$error_msg."\n");
        fclose($handle);
        /////////////////////////
     }

    
    /**********************************
     * This function will get the nvpheader string and return that.
     **********************************/
    private function get_nvpheader(){
        $nvpHeader = "";
        $AuthMode = "3TOKEN"; //Merchant's API 3-TOKEN Credential is required to make API Call.
//        $AuthMode = "FIRSTPARTY"; //Only merchant Email is required to make EC Calls.
//        $AuthMode = "THIRDPARTY"; //Partner's API Credential and Merchant Email as Subject are required.

        switch($AuthMode) {
            case "3TOKEN" :
                    $nvpHeader = "&PWD=".urlencode($this->api_password)."&USER=".urlencode($this->api_username)."&SIGNATURE=".urlencode($this->api_signature);
                    break;
            case "FIRSTPARTY" :
                    $nvpHeader = "&SUBJECT=".urlencode($this->api_subject);
                    break;
            case "THIRDPARTY" :
                    $nvpHeader = "&PWD=".urlencode($this->api_password)."&USER=".urlencode($this->api_username)."&SIGNATURE=".urlencode($this->api_signature)."&SUBJECT=".urlencode($this->api_subject);
                    break;
        }
        return $nvpHeader;
    }
}

?>
