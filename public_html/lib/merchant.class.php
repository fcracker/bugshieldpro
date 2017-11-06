<?php


class umMerchant{
	var $bankID = 0;
	
	
	// merchant테블의 내용을 읽는다.
	function get_merchant(){
		global $cfg;
		$sql = "SELECT * FROM " . $cfg['database']['prefix']."merchant WHERE payment_period<2";
		if($this->bankID!=0) $sql .= " AND bankID='".$this->bankID."'";
		$sql .= " ORDER BY tier";
		
		if($this->bankID!=0) $result = single_query_assoc($sql);
		else  $result = multi_query_assoc($sql);
		
		return $result;
	}
	
	//merchant테블에 자료를 추가한다.
	function create_merchant($merchant){
		global $cfg;
		$sql = "INSERT INTO ".$cfg['database']['prefix']."merchant SET 
				BankName='".$merchant['BankName']."'
				,cap_per_month='".$merchant['cap_per_month']."'
				,payment_period='".$merchant['payment_period']."'
				,getwayID='".$merchant['getwayID']."'
				,getwayKey='".$merchant['getwayKey']."'";
		mysql_query($sql);
		$this->refreshTier();
	}
	
	// tier 값들을 정리한다.
	function refreshTier(){
		global $cfg;
		$sql = "SELECT BankID FROM ".$cfg['database']['prefix']."merchant 
				WHERE payment_period<2 AND cap_per_month<>0
				ORDER BY cap_per_month ASC, payment_period ASC";
		$rows = multi_query_assoc($sql);
		
		$tierIndex = 1;
		if(count($rows)){
			foreach($rows as $row){
				$sql = "UPDATE ".$cfg['database']['prefix']."merchant
						SET tier='".$tierIndex."'
						WHERE BankID='".$row['BankID']."'";
				mysql_query($sql);
				$tierIndex++;
			}
		}
		
		$sql = "SELECT bankID FROM ".$cfg['database']['prefix']."merchant 
				WHERE payment_period<2 AND cap_per_month=0
				ORDER BY cap_per_month ASC, payment_period ASC";
		$rows = multi_query_assoc($sql);
		if(count($rows)){
			foreach($rows as $row){
				$sql = "UPDATE ".$cfg['database']['prefix']."
						SET tier='".$tierIndex."'
						WHERE BankID='".$row['BankID']."'";
				mysql_query($sql);
				$tierIndex++;
			}
		}
	}
	
	//merchant 테블의 내용을 변경한다.
	function update_merchant($merchant){
		global $cfg;
		$sql = "UPDATE ".$cfg['database']['prefix']."merchant SET 
					BankName='".$merchant['BankName']."'
					,cap_per_month='".$merchant['cap_per_month']."'
					,payment_period='".$merchant['payment_period']."'
					,getwayID='".$merchant['getwayID']."'
					,getwayKey='".$merchant['getwayKey']."'
				WHERE payment_period<2 AND BankID='".$this->bankID."'";
		
		mysql_query($sql);
		$this->refreshTier();
		
		
	}
	
	// merchant 테블의 내용을 삭제한다.
	function delete_merchant($tier){
		global $cfg;
		$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
				SET payment_period=2, tier=0
				WHERE BankID='".$this->bankID."'";
		mysql_query($sql); 
		
		$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
				SET tier=tier-1
				WHERE payment_period<2 AND tier>$tier";
		mysql_query($sql);
	}
	
	function exchange_tier($tier, $diff){
		global $cfg;
		$changeTier = $tier + $diff;
		
		$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
				SET tier=$tier
				WHERE payment_period<2 AND tier=$changeTier";
		mysql_query($sql); 
		
		$sql = "UPDATE ".$cfg['database']['prefix']."merchant 
				SET tier=$changeTier
				WHERE BankID='".$this->bankID."'";
		mysql_query($sql);
	}
	
	//merchant 테블의 자료를 탐색한다.
	function search_merchant(){
		global $cfg;
		
		
	}
	
	//merchant_history  테블에 자료를 넣는다.
	function insert_history($bankID, $amount){
		global $cfg;
		$sql = "INSERT INTO ".$cfg['database']['prefix']."merchant_history 
				SET BankID='".$bankID."'
				,hDate='".date("Y-n-j")."'
				,hAmount='".$amount."'";
		mysql_query($sql);
	}
	
	// tier 를 얻는다.
	function get_tier($amount){
		global $cfg;
		$sql = "SELECT m.BankID, m.tier, m.getwayID, m.getwayKey FROM ".$cfg['database']['prefix']."merchant AS m
				WHERE m.payment_period<2 AND 
					  (m.cap_per_month-".$amount.")>=(SELECT IFNULL(SUM(hAmount),0) AS amount 
									FROM ".$cfg['database']['prefix']."merchant_history 
									WHERE BankID=m.BankID AND  
										Year(hDate)=YEAR(CURDATE()) 
										AND MONTH(hDate)=MONTH(CURDATE()))
				ORDER BY m.tier 
				LIMIT 1";
		$row = single_query_assoc($sql);
				
		if(count($row)) $this->insert_history($row['BankID'], $amount);
		return $row;		
	}
}



?>