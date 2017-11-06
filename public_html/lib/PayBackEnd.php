<?php
	/* 
		* To change this template, choose Tools | Templates
		* and open the template in the editor.
	*/
	
	//include_once($cfg['site']['root'].$cfg['site']['folder']."/lib/database.inc.php");
	/**
		* Description of PayBackEnd
		*
		* @author hotmoney
	*/
	class PayBackEnd {
		var $bankID = 0;
		private $monthdays;
		private $tier_Period;
		private $period_length;
		private $rand_factor;
		public function __construct(){
			$this->monthdays = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
			$this->tier_Period = array('0'=>1, '1'=>2);
			$this->period_length = 2;
			$this->rand_factor = 99;
		}
		/**
			* Get Payment and set payment history relation function's
		*/
		public function getGatewayOfTransID($trans_id){
			global $cfg;
			$sql = "SELECT M.gatewayType, M.BankID, M.gatewayID, M.gatewayKey, M.gatewaySign, MH.processer_id
			FROM ".$cfg['database']['prefix']."merchant_history AS MH
			LEFT JOIN ".$cfg['database']['prefix']."merchant AS M ON MH.BankID=M.BankID
			WHERE MH.transaction_id='$trans_id'";
			$row = single_query_assoc($sql);
			$result = array();
			if(count($row)){
				$result[0] = $row['gatewayType'];
				$result[1] = array();
				$result[1]["username"] = $row['gatewayID'];
				$result[1]["password"] = $row['gatewayKey'];
				$result[1]["signature"] = $row['gatewaySign'];
				$result[2] = $row['BankID'];
				$result[3] = $row['processer_id'];
				
			}
			return $result;
			
		}
		public function getRefundable_amount($trans_id){
			global $cfg;
			$sql = "SELECT refundable_amount FROM ".$cfg['database']['prefix']."merchant_history WHERE transaction_id='$trans_id'";
			$row = single_query_assoc($sql);
			return $row["refundable_amount"];
		}
		
		public function getRefundable_amount_by_hKey($hKey){
			global $cfg;
			$sql = "SELECT refundable_amount FROM ".$cfg['database']['prefix']."merchant_history WHERE hKey='$hKey'";
			$row = single_query_assoc($sql);
			return $row["refundable_amount"];
		}
		
		public function get_transid_from_hKey($hKey) {
			global $cfg;
			$sql = "SELECT transaction_id FROM ".$cfg['database']['prefix']."merchant_history WHERE hKey='$hKey'";
			$row = single_query_assoc($sql);
			return $row["transaction_id"];
		}
		
		public function setHistoryRefund($trans_id, $refundable_amount = ""){
			global $cfg;
			if($refundable_amount != "") $insert_sql = " , refundable_amount = '".$refundable_amount."' ";
			$sql = "
            UPDATE ".$cfg['database']['prefix']."merchant_history
            SET refunded_date = Now()
			$insert_sql
            WHERE transaction_id ='$trans_id'
            ";
			return single_query_assoc($sql);
		}
		public function getGateway($amount, $userEmail='' ,$gateway_type = "both",$country="xx",$is_monthly=false,$card_type="" ){
			global $cfg;
			list($year, $month) = explode("-", date("Y-n"));
			
			$is_capped = false;
      
			$row = $this->get_existBankID($userEmail);
      
			if($card_type=="American Express") {
       
				if($row['can_process_amex']<=0 || $row['amex_probability']<=0 || $row['payment_period']>$this->period_length) {
				  //trigger a new search, since this is AMEX and the previous merchant does not process AMEX
				  $row = array();
				}
				
			  } 
      
			if(count($row)){       
				if($row['persent']==0) return array();
				$sel_amount = $this->get_sumAmount($row['BankID'], $row['payment_period']);     
				$cap_per_month = $row['cap_per_month'];
				if($row['payment_period']==0){
					$cap_per_month = ceil($cap_per_month/$this->getDaysofMonth($month, $year));
				}
				
				if($sel_amount+$amount>$cap_per_month) {
					//we still need the data
					//return array();
					$is_capped = true;
				}
				
				}else{
        
       if($card_type=="American Express") {
        
        $sql_amex = "SELECT BankID,amex_probability as persent from ".$cfg['database']['prefix']."merchant WHERE can_process_amex>0 AND amex_probability>0 AND payment_period<".$this->period_length;
        $amex_rows = multi_query_assoc($sql_amex);
        $selectAmexPersent = rand(1, 100*$this->rand_factor);
				$amex_row = $this->selectMerchant($amex_rows, $selectAmexPersent); 
        
        //get this one
        $sql = "SELECT * , gatewaySign AS processer_id FROM ".$cfg['database']['prefix']."merchant
				WHERE BankID=".$amex_row['BankID'];
        
        } else {
        
				$sql = "SELECT * , gatewaySign AS processer_id FROM ".$cfg['database']['prefix']."merchant
				WHERE payment_period<".$this->period_length." AND persent<>0 
				".($gateway_type!="both"?" AND gatewayType='".$gateway_type."' ":"").
        ($card_type=="American Express" ? " AND can_process_amex=1 AND amex_probability>0 ":"").
        "ORDER BY tier";
        
        }
				
				
				
				$rows = multi_query_assoc($sql);
				
				$result = array();
				$row = array();
				$preTier = 0;
				$sumPersent = 0;
				for($i=0; $i<count($rows); $i++){
					
					if($preTier != $rows[$i]['tier']){
						if($preTier!=0){
					    	
							$selectPersent = rand(1, $sumPersent*$this->rand_factor);
							
							$row = $this->selectMerchant($result, $selectPersent);
							if(count($row)) break;
						}
						$result = array();
						$sumPersent = 0;
					}
					$preTier = $rows[$i]['tier'];
					$sel_amount = $this->get_sumAmount($rows[$i]['BankID'], $rows[$i]['payment_period']);
					
					$cap_per_month = $rows[$i]['cap_per_month'];
					
					if($rows[$i]['payment_period']==0){
						$cap_per_month = ceil($cap_per_month/$this->getDaysofMonth($month, $year));
					}
					
					if($sel_amount+$amount>$cap_per_month) continue;
					
					$sumPersent += $rows[$i]['persent'];
					$result[] = $rows[$i];
				}
				
				if(count($row)==0 && count($result)){
					$selectPersent = rand(1, $sumPersent*$this->rand_factor);
					
					$row = $this->selectMerchant($result, $selectPersent);
				}
			}
			
			$result = array();
			if(count($row)){ 
				$result[0] = $row['gatewayType'];
				$result[1] = array();
				$result[1]["username"] = $row['gatewayID'];
				$result[1]["password"] = $row['gatewayKey'];
				$result[1]["signature"] = $row['gatewaySign'];
        $result[1]["forward_rebill"] = $row['forward_rebill'];
        $result[1]["forward_cvv_rebill_forwarded"] = $row['forward_cvv_rebill_forwarded'];
        $result[1]["forward_cvv_rebill_inital"] = $row['forward_cvv_rebill_initial'];
        $result[1]["forward_cvv_rebill"] = $row['forward_cvv_rebill'];
        $result[1]["amex_forward"] = $row['amex_forward'];
        $result[1]["forward_rebill_balance"] = $row['forward_rebill_balance']; 
        $result[1]["use_fwd_value_rules"] = $row['use_fwd_value_rules']; 
        $result[1]["rebill_fwd_value_based_rules"] = $row['rebill_fwd_value_based_rules']; 		
        $result[1]["is_capped"] = $is_capped;
        
				$result[2] = $row['BankID'];
				$result[3] = $row['processer_id'];
				$result[4] = $row['upsell_price'];
				$result[5] = $row['upsell_text'];     
				$result[6] = $row['use_upsell'];
				$result[7] = $row['use_monthly'];
				$result[8] = $row['monthly_price'];
				$result[9] = $row['monthly_text'];
				$result[10] = $row['monthly_country_exceptions'];
			}
			return $result;	
		}
    
    public function getSpecificGateway($bankId,$amount=20, $userEmail='' ,$gateway_type = "both",$country="xx",$is_monthly=false,$card_type="" ) {
      global $cfg;
	  
      list($year, $month) = explode("-", date("Y-n"));
	  
	  $is_capped = false;
	  
      $sql = "SELECT * , gatewaySign AS processer_id FROM ".$cfg['database']['prefix']."merchant WHERE BankID='".(int)$bankId."' and payment_period < ".$this->period_length." LIMIT 1";
      $row = single_query_assoc($sql);
      
      if($card_type=="American Express") {  
      
        if($row['can_process_amex']<=0 || $row['amex_probability']<=0 || $row['payment_period']>$this->period_length) {
          return $this->getGateway($amount, $userEmail ,$gateway_type,$country,$is_monthly,$card_type);        
        }
        
      }
      
      
		$sel_amount = $this->get_sumAmount($row['BankID'], $row['payment_period']);     
		$cap_per_month = $row['cap_per_month'];
		if($row['payment_period']==0){
			$cap_per_month = ceil($cap_per_month/$this->getDaysofMonth($month, $year));
		}
				
		if($sel_amount+$amount>$cap_per_month) {
			$is_capped = true;
		}
      
      $result = array();
			if(count($row)){ 
				$result[0] = $row['gatewayType'];
				$result[1] = array();
				$result[1]["username"] = $row['gatewayID'];
				$result[1]["password"] = $row['gatewayKey'];
				$result[1]["signature"] = $row['gatewaySign'];
        $result[1]["forward_rebill"] = $row['forward_rebill'];        
        $result[1]["forward_cvv_rebill_forwarded"] = $row['forward_cvv_rebill_forwarded'];
        $result[1]["forward_cvv_rebill_inital"] = $row['forward_cvv_rebill_initial'];
        $result[1]["forward_cvv_rebill"] = $row['forward_cvv_rebill'];
        $result[1]["amex_forward"] = $row['amex_forward'];
        $result[1]["use_fwd_value_rules"] = $row['use_fwd_value_rules']; 
        $result[1]["rebill_fwd_value_based_rules"] = $row['rebill_fwd_value_based_rules'];
		$result[1]["is_capped"] = $is_capped;
        
				$result[2] = $row['BankID'];
				$result[3] = $row['processer_id'];
				$result[4] = $row['upsell_price'];
				$result[5] = $row['upsell_text'];     
				$result[6] = $row['use_upsell'];
				$result[7] = $row['use_monthly'];
				$result[8] = $row['monthly_price'];
				$result[9] = $row['monthly_text'];
				$result[10] = $row['monthly_country_exceptions'];
        return $result;	
			} else {
        return $this->getGateway($amount, $userEmail ,$gateway_type,$country,$is_monthly,$card_type);
      }
        
    }
		
		public function setHistory($param = array()){
			global $cfg;
			if(!count($param)) return false;
			extract($param);
      
      if(!isset($raw_response)) $raw_response = "";
      if(!isset($transaction_description)) $transaction_description = "";
      if(!isset($is_rebill)) $is_rebill = 0;
      if(!isset($is_yearly_rebill)) $is_yearly_rebill = 0;
      
			$sql = "INSERT INTO ".$cfg['database']['prefix']."merchant_history 
			SET BankID='".$bankid."'
			,transaction_id='".$transactionid."'
			,user_email='".$userid."'
			,user_id='0'
			,mothodtype='".$methodtype."'
			,hAmount='".$amount."'
			,hDate= NOW()
			,processer_id = '".$processor_id."'
			,refundable_amount = '".$amount."'
      ,raw_response = '".$raw_response."'
      ,transaction_description = '".$transaction_description."'
      ,is_rebill = '".$is_rebill."'
      ,is_yearly_rebill = '".$is_yearly_rebill."'";
			
			
			
			mysql_query($sql);
			
			
		}
		
		public function getPayHistory($user_email) {
			$query = sprintf('SELECT hAmount FROM mem_merchant_history WHERE user_email = "%s" ORDER BY hDate',$user_email);
			
			$result = mysql_query($query);
			
			$amounts = array();
			
			while($row = mysql_fetch_object($result)) {
				$amounts[]=$row->hAmount;
			}
			
			return $amounts;
		}
		
		public function getAllPayHistory($user_email) {
			$query = sprintf('SELECT * FROM mem_merchant_history WHERE user_email = "%s" ORDER BY hDate',$user_email);
			
			$result = mysql_query($query);
			
			$data = array();
			
			while($row = mysql_fetch_object($result)) {
				$data[]=$row;
			}
			
			return $data;
		}
		
		public function delete_user_history($user_email,$transaction_id) {
			global $cfg;
			
			$sql = "DELETE FROM ".$cfg['database']['prefix']."merchant_history WHERE user_email='".$user_email."' AND transaction_id='".$transaction_id."' LIMIT 1";
			mysql_query($sql);
		}
    
    public function get_last_used_bank($email) {
      //$query = sprintf('SELECT * FROM mem_merchant_history WHERE user_email = "%s" ORDER BY hDate DESC LIMIT 1',$email);
      
      global $cfg;
			$query = "SELECT M.*,MH.processer_id  
			FROM ".$cfg['database']['prefix']."merchant_history AS MH 
			INNER JOIN ".$cfg['database']['prefix']."merchant AS M
			ON MH.BankID=M.BankID
			WHERE MH.user_email='".$email."'
			ORDER BY MH.hDate DESC
			LIMIT 1";
      
      $result = mysql_query($query);
      
      if(mysql_num_rows($result)) {
        $data = mysql_fetch_object($result);
        
        return $data;
      }
      
      return null;
      
    }
	
	public function get_merchant_by_id($id) {
      
      global $cfg;
			$query = "SELECT * FROM ".$cfg['database']['prefix']."merchant WHERE BankID='".$id."' LIMIT 1";
      
      $result = mysql_query($query);
      
      if(mysql_num_rows($result)) {
        $data = mysql_fetch_object($result);
        
        return $data;
      }
      
      return null;
      
    }
		
		public function get_used_bank($email) {
			return $this->get_existBankID($email);
		}
		
		private function get_existBankID($userEmail){
			global $cfg;
			$sql = "SELECT M.*,MH.processer_id
			FROM ".$cfg['database']['prefix']."merchant_history AS MH 
			INNER JOIN ".$cfg['database']['prefix']."merchant AS M
			ON MH.BankID=M.BankID
			WHERE M.payment_period<".$this->period_length." AND MH.user_email='".$userEmail."'
			ORDER BY MH.hKey ASC
			LIMIT 1";
			
			$row = single_query_assoc($sql);
			return $row;
		}
		
		private function get_sumAmount($BankID, $is_monthly=true){
			global $cfg;
			
			$sql = "SELECT IFNULL(SUM(hAmount),0) AS amount 
			FROM ".$cfg['database']['prefix']."merchant_history 
			WHERE BankID='$BankID'
			AND Year(hDate)=YEAR(CURDATE()) 
			AND MONTH(hDate)=MONTH(CURDATE())";
			if(!$is_monthly) $sql .= " AND DAY(hDate)=DAY(CURDATE())";
			$row = single_query_assoc($sql);
			return $row['amount'];
		}
		
		public function getMontlySpent($merchant_id=0,$is_monthly=true) {
			if(intval($merchant_id)>0) {
				return $this->get_sumAmount($merchant_id,$is_monthly=true);
			}
			return 0;
		}
		
		private function selectMerchant($merchants, $selPersent){
			$persentSum = 0;
			
			for($i=0; $i<count($merchants); $i++){
				$persentSum += $merchants[$i]['persent'];
				
				if($selPersent<=$persentSum*$this->rand_factor){
					return $merchants[$i];
				}
			}
			return array();
		}
    
   
    
    
		/**
			* Manage Merchant relation function's
		*/
		public function get_merchant($is_active='1',$get_deleted=false){
			global $cfg;
			$sql = "SELECT * FROM " . $cfg['database']['prefix']."merchant WHERE 1";
			if($is_active=='1') $sql .= " AND payment_period<".$this->period_length;
			else				$sql .= " AND payment_period>=".$this->period_length;
			if($this->bankID!=0) $sql .= " AND bankID='".$this->bankID."'";
			if(!$get_deleted) $sql.=" AND deleted<>1";
			$sql .= " ORDER BY tier, persent DESC";
			
			if($this->bankID!=0) $result = single_query_assoc($sql);
			else  $result = multi_query_assoc($sql);
			
			return $result;
		}
		
		public function create_merchant($merchant){
			global $cfg;
			$tier = $merchant['tier'];
			
			$merchant['persent'] = (int) $merchant['persent'];
			if($merchant['persent']<1) $merchant['persent']=100;
			if($merchant['persent']>100) $merchant['persent']=100;
			
			$tier_merchants = $this->getMerchantOfTier($tier);
			
			if(!count($tier_merchants)) $merchant['persent']=100;
			else $this->setPersentOfTier($merchant['persent'], $tier_merchants);
			
			//check the combined charge
			$combined_charge = $merchant["combined_charge"];
			//bundle set ?
			$bundle = (isset($merchant["bundle"])) ? 1:0;
			
			$use_upsell = (isset($merchant["use_upsell"])) ? 1:0;
			$use_monthly = (isset($merchant["use_monthly"])) ? 1:0;
      
      $upsell_charges = (isset($merchant["upsell_charges"])) ? $merchant["upsell_charges"]:serialize(array());
      
      $forward_cvv_rebill = (isset($merchant["forward_cvv_rebill"])) ? 1:0;
      $forward_cvv_rebill_forwarded = (isset($merchant["forward_cvv_rebill_forwarded"])) ? 1:0;
      $forward_cvv_rebill_initial = (isset($merchant["forward_cvv_rebill_initial"])) ? 1:0;
      
      $can_process_amex = (isset($merchant["can_process_amex"])) ? 1:0;
      
      $amex_forward = (isset($merchant["amex_forward"])) ? $merchant["amex_forward"]:0;
      
      $soft_decline_rebill = (isset($merchant["soft_decline_rebill"])) ? $merchant["soft_decline_rebill"]:0;
      
      $forward_rebill_balance = (isset($merchant["forward_rebill_balance"])) ? $merchant["forward_rebill_balance"]:"";
      
      $rebill_fail_action = (isset($merchant["rebill_fail_action"])) ? $merchant["rebill_fail_action"]:"0";
      $rebill_fail_try_after = (isset($merchant["rebill_fail_try_after"])) ? $merchant["rebill_fail_try_after"]:"1";
      $rebill_fail_merchant = (isset($merchant["rebill_fail_merchant"])) ? $merchant["rebill_fail_merchant"]:"0";
      
      $use_fwd_value_rules = (isset($merchant["use_fwd_value_rules"])) ? $merchant["use_fwd_value_rules"]:"0";
      $rebill_fwd_value_based_rules = (isset($merchant["rebill_fwd_value_based_rules"])) ? $merchant["rebill_fwd_value_based_rules"]:serialize(array());
      
      $initial_forward = (isset($merchant["initial_forward"])) ? $merchant["initial_forward"]:0;
			
			//country specific rules
			//$merchant["monthly_country_exceptions"] = "";
			
			
			$sql = "INSERT INTO ".$cfg['database']['prefix']."merchant SET 
			BankName='".$merchant['BankName']."'
			,tier='".$tier."'
			,cap_per_month='".$merchant['cap_per_month']."'
			,payment_period='".$merchant['payment_period']."'
			,persent='".$merchant['persent']."'
			,gatewayType='".$merchant['gatewayType']."'
			,gatewayID='".$merchant['gatewayID']."'
			,gatewayKey='".$merchant['gatewayKey']."'
			,gatewaySign='".$merchant['gatewaySign']."'
			,combined_charge='".$combined_charge."'
			,bundle=".$bundle."        
			,use_upsell=".$use_upsell."
			,upsell_price=".$merchant['upsell_price']."
			,upsell_text='".$merchant['upsell_text']."'       
			,use_monthly=".$use_monthly."
			,monthly_price='".$merchant['monthly_price']."'
			,monthly_text='".$merchant['monthly_text']."'
			,monthly_country_exceptions='".$merchant['monthly_country_exceptions']."'
      ,forward_rebill='".$merchant['forward_rebill']."'
      ,forward_cvv_rebill=".$forward_cvv_rebill."
      ,forward_cvv_rebill_forwarded=".$forward_cvv_rebill_forwarded."
      ,forward_cvv_rebill_initial=".$forward_cvv_rebill_initial."
      ,upsell_charges='".$upsell_charges."'
      ,can_process_amex='".$can_process_amex."'
      ,amex_forward='".$amex_forward."'
      ,soft_decline_rebill='".$soft_decline_rebill."'
      ,forward_rebill_balance='".$forward_rebill_balance."'
      ,rebill_fail_action='".$rebill_fail_action."'
      ,rebill_fail_try_after='".$rebill_fail_try_after."'
      ,rebill_fail_merchant='".$rebill_fail_merchant."'
      ,use_fwd_value_rules='".$use_fwd_value_rules."'
      ,initial_forward='".$initial_forward."'
      ,rebill_fwd_value_based_rules='".$rebill_fwd_value_based_rules."'
			";
			mysql_query($sql);
			//		$this->refreshTier();
		}
    
    public function update_percent_for_merchant($merchant_id,$new_percentage=0) {
        global $cfg;
        if($new_percentage<0) $new_percentage = 0;
        if($new_percentage>100) $new_percentage = 100;
      
        $sql = "UPDATE ".$cfg['database']['prefix']."merchant SET persent='".$new_percentage."' WHERE BankID='".$merchant_id."'";
      
      	mysql_query($sql);
      
    }
    
    public function update_amex_percent_for_merchant($merchant_id,$new_percentage=0) {
        global $cfg;
        if($new_percentage<0) $new_percentage = 0;
        if($new_percentage>100) $new_percentage = 100;
      
        $sql = "UPDATE ".$cfg['database']['prefix']."merchant SET amex_probability='".$new_percentage."' WHERE BankID='".$merchant_id."'";
      
      	mysql_query($sql);
      
    }
		
		public function update_merchant($merchant){
			global $cfg;
			$tier = $merchant['tier'];
      
			/*
      if($merchant['persent']>100) $merchant['persent']=100;
			
      
			$oldMerchant = $this->get_merchant();
			if($oldMerchant['tier'] != $tier){
				$tier_merchants = $this->getMerchantOfTier($oldMerchant['tier'], $this->bankID);
				if(!count($tier_merchants)) $merchant['persent']=100;
				else $this->setPersentOfTier(0, $tier_merchants);
			}
			$tier_merchants = $this->getMerchantOfTier($tier, $this->bankID);
			if(!count($tier_merchants)) $merchant['persent']=100;
			else $this->setPersentOfTier($merchant['persent'], $tier_merchants);
			*/
      
      
			//check the combined charge
			$combined_charge = $merchant["combined_charge"];
			//bundle set ?
			$bundle = (isset($merchant["bundle"])) ? 1:0;
			
			$use_upsell = (isset($merchant["use_upsell"])) ? 1:0;
			$use_monthly = (isset($merchant["use_monthly"])) ? 1:0;
      $rebill_period = (isset($merchant["rebill_period"])) ? abs((int)$merchant["rebill_period"]):$cfg['rebill_period'];
      
      
      $upsell_charges = (isset($merchant["upsell_charges"])) ? $merchant["upsell_charges"]:serialize(array());
      
      $forward_cvv_rebill = (isset($merchant["forward_cvv_rebill"])) ? 1:0;
      $forward_cvv_rebill_forwarded = (isset($merchant["forward_cvv_rebill_forwarded"])) ? 1:0;
      $forward_cvv_rebill_initial = (isset($merchant["forward_cvv_rebill_initial"])) ? 1:0;
      
      $can_process_amex = (isset($merchant["can_process_amex"])) ? 1:0;
      $amex_forward = (isset($merchant["amex_forward"])) ? $merchant["amex_forward"]:0;
      
      $soft_decline_rebill = (isset($merchant["soft_decline_rebill"])) ? $merchant["soft_decline_rebill"]:0;
      
      $forward_rebill_balance = (isset($merchant["forward_rebill_balance"])) ? $merchant["forward_rebill_balance"]:"";
      
      $rebill_fail_action = (isset($merchant["rebill_fail_action"])) ? $merchant["rebill_fail_action"]:"0";
      $rebill_fail_try_after = (isset($merchant["rebill_fail_try_after"])) ? $merchant["rebill_fail_try_after"]:"1";
      $rebill_fail_merchant = (isset($merchant["rebill_fail_merchant"])) ? $merchant["rebill_fail_merchant"]:"0";
      
      $use_fwd_value_rules = (isset($merchant["use_fwd_value_rules"])) ? $merchant["use_fwd_value_rules"]:"0";
      $rebill_fwd_value_based_rules = (isset($merchant["rebill_fwd_value_based_rules"])) ? $merchant["rebill_fwd_value_based_rules"]:serialize(array());
      
      $initial_forward = (isset($merchant["initial_forward"])) ? $merchant["initial_forward"]:0;
			
			//country specific rules
			// $merchant["monthly_country_exceptions"] = "";
			
			$sql = "UPDATE ".$cfg['database']['prefix']."merchant SET 
			BankName='".$merchant['BankName']."'
			,tier='".$tier."'
			,cap_per_month='".$merchant['cap_per_month']."'
			,payment_period='".$merchant['payment_period']."'			
			,gatewayType='".$merchant['gatewayType']."'
			,gatewayID='".$merchant['gatewayID']."'
			,gatewayKey='".$merchant['gatewayKey']."'
			,gatewaySign='".$merchant['gatewaySign']."'
			,combined_charge='".$combined_charge."'
			,bundle=".$bundle."
			,use_upsell=".$use_upsell."
			,upsell_price=".$merchant['upsell_price']."
			,upsell_text='".$merchant['upsell_text']."' 
      ,rebill_period=".$rebill_period."      
			,use_monthly=".$use_monthly."
			,monthly_price='".$merchant['monthly_price']."'
			,monthly_text='".$merchant['monthly_text']."'
			,monthly_country_exceptions='".$merchant['monthly_country_exceptions']."' 
      ,forward_rebill='".$merchant['forward_rebill']."'
      ,forward_cvv_rebill=".$forward_cvv_rebill."
      ,forward_cvv_rebill_forwarded=".$forward_cvv_rebill_forwarded."    
      ,forward_cvv_rebill_initial=".$forward_cvv_rebill_initial."     
      ,upsell_charges='".$upsell_charges."'
      ,can_process_amex='".$can_process_amex."'      
      ,amex_forward='".$amex_forward."'  
      ,soft_decline_rebill='".$soft_decline_rebill."' 
      ,forward_rebill_balance='".$forward_rebill_balance."' 
      ,rebill_fail_action='".$rebill_fail_action."' 
      ,rebill_fail_try_after='".$rebill_fail_try_after."' 
      ,rebill_fail_merchant='".$rebill_fail_merchant."' 
      ,use_fwd_value_rules='".$use_fwd_value_rules."' 
      ,initial_forward='".$initial_forward."' 
      ,rebill_fwd_value_based_rules='".$rebill_fwd_value_based_rules."'  
			 WHERE payment_period<".$this->period_length." AND BankID='".$this->bankID."'";
			
			mysql_query($sql);
			//$this->refreshTier();
		}
		
		public function delete_merchant($tier){
			global $cfg;
			$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
			SET `payment_period`=`payment_period`+".$this->period_length."
			WHERE BankID='".$this->bankID."'";
			mysql_query($sql); 
			
			//		$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
			//				SET tier=tier-1
			//				WHERE payment_period<2 AND tier>$tier";
			mysql_query($sql);
		}
		
		public function really_delete_merchant() {
			global $cfg;
			$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
			SET `deleted`=1	WHERE BankID='".$this->bankID."'";
			mysql_query($sql); 
		}
		
		public function reinstate_merchant() {
			global $cfg;
			$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
			SET `payment_period`=1 WHERE BankID='".$this->bankID."'";		
			mysql_query($sql);
		}
		
		private function getDaysofMonth($month, $year){
			if($month != 2) return $this->monthdays[$month];
			if($year%4==0){
				if($year%100==0){
					if($year%400==0) return 29;
					return 28;
				}
				return 29;
			}
			return 28;
		}
		
		private function getMerchantOfTier($tier, $bankID=0){
			global $cfg;
			$sql = "SELECT * FROM ".$cfg['database']['prefix']."merchant WHERE BankID<>".$bankID." AND tier=$tier AND payment_period<".$this->period_length;
			$rows = multi_query_assoc($sql);
			return $rows;
		}
		
		private function setPersentOfTier($persent, $merchants){
			global $cfg;
			$length = count($merchants);
			if($length==0) return;
			$order_persent = 100-$persent;
			$unit_persent = ceil($order_persent/$length);
			$last_persent = $order_persent - $unit_persent*($length-1);
			
			for($i=0; $i<($length-1); $i++){
				$sql = "UPDATE ".$cfg['database']['prefix']."merchant SET persent=".$unit_persent ."
				WHERE BankID=".$merchants[$i]['BankID'];
				mysql_query($sql);
			}
			$sql = "UPDATE ".$cfg['database']['prefix']."merchant SET persent=".$last_persent ."
			WHERE BankID=".$merchants[$i]['BankID'];
			mysql_query($sql);
		}
		//	public function exchange_tier($tier, $diff){
		//		global $cfg;
		//		$changeTier = $tier + $diff;
		//		
		//		$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
		//				SET tier=$tier
		//				WHERE payment_period<2 AND tier=$changeTier";
		//		mysql_query($sql); 
		//		
		//		$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
		//				SET tier=$changeTier
		//				WHERE BankID='".$this->bankID."'";
		//		mysql_query($sql);
		//	}
		
		//	public function refreshTier(){
		//		return;
		//		global $cfg;
		//		$sql = "SELECT BankID FROM ".$cfg['database']['prefix']."merchant 
		//				WHERE payment_period<".$this->period_length." AND cap_per_month<>0
		//				ORDER BY cap_per_month ASC, payment_period ASC";
		//		$rows = multi_query_assoc($sql);
		//		
		//		$tierIndex = 1;
		//		if(count($rows)){
		//			foreach($rows as $row){
		//				$sql = "UPDATE ".$cfg['database']['prefix']."merchant
		//						SET tier='".$tierIndex."'
		//						WHERE BankID='".$row['BankID']."'";
		//				mysql_query($sql);
		//				$tierIndex++;
		//			}
		//		}
		//		
		//		$sql = "SELECT bankID FROM ".$cfg['database']['prefix']."merchant 
		//				WHERE payment_period<".$this->period_length." AND cap_per_month=0
		//				ORDER BY cap_per_month ASC, payment_period ASC";
		//		$rows = multi_query_assoc($sql);
		//		if(count($rows)){
		//			foreach($rows as $row){
		//				$sql = "UPDATE ".$cfg['database']['prefix']."
		//						SET tier='".$tierIndex."'
		//						WHERE BankID='".$row['BankID']."'";
		//				mysql_query($sql);
		//				$tierIndex++;
		//			}
		//		}
		//	}
	}
?>
