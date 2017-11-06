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
    public function getGateway($amount, $userEmail='' ,$gateway_type = "both" ){
		global $cfg;
		list($year, $month) = explode("-", date("Y-n"));
		
		$row = $this->get_existBankID($userEmail);
		if(count($row)){       
			if($row['persent']==0) return array();
			$sel_amount = $this->get_sumAmount($row['BankID'], $row['payment_period']);     
			$cap_per_month = $row['cap_per_month'];
			if($row['payment_period']==0){
				$cap_per_month = ceil($cap_per_month/$this->getDaysofMonth($month, $year));
			}
			if($sel_amount+$amount>$cap_per_month) return array();
		}else{
			$sql = "SELECT * , '' AS processer_id FROM ".$cfg['database']['prefix']."merchant
					WHERE payment_period<".$this->period_length." AND persent<>0 
					".($gateway_type!="both"?" AND gatewayType='".$gateway_type."' ":"")." 
					ORDER BY tier";
				
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
			$result[2] = $row['BankID'];
			$result[3] = $row['processer_id'];
      $result[4] = $row['upsell_price'];
      $result[5] = $row['upsell_text'];
		}
		return $result;	
    }
    
    public function setHistory($param = array()){
    	global $cfg;
    	if(!count($param)) return false;
    	extract($param);
		$sql = "INSERT INTO ".$cfg['database']['prefix']."merchant_history 
				SET BankID='".$bankid."'
				,transaction_id='".$transactionid."'
				,user_email='".$userid."'
				,mothodtype='".$methodtype."'
				,hAmount='".$amount."'
				,hDate= NOW()
				,processer_id = '".$processor_id."'
        ,merchant_transid = '".$merchant_transid."'
				,refundable_amount = '".$amount."'";        
       
        
        mysql_query($sql);
        
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
    			ORDER BY MH.hKey DESC
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
        ,upsell_price=".$merchant['upsell_price']."
        ,upsell_text='".$merchant['upsell_text']."'";
		mysql_query($sql);
//		$this->refreshTier();
	}
	
	public function update_merchant($merchant){
		global $cfg;
		$tier = $merchant['tier'];
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
		
		//check the combined charge
		$combined_charge = $merchant["combined_charge"];
		//bundle set ?
		$bundle = (isset($merchant["bundle"])) ? 1:0;
		
		$sql = "UPDATE ".$cfg['database']['prefix']."merchant SET 
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
          ,upsell_price=".$merchant['upsell_price']."
          ,upsell_text='".$merchant['upsell_text']."' 
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
