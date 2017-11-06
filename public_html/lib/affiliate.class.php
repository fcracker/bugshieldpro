<?php

class affiliate {
	
	public $config,
	$id,
	$aff_id,
	$first_seen,
	$last_seen,
	$sale_count;
	
	function __construct($config) {
		$this->config = $config;
	}
	
	
	public function add_affiliate($aff_id) {
		$sql = "insert into ".$this->config['database']['prefix']."affiliate SET aff_id='".mysql_real_escape_string($data['aff_id'])."', first_seen=NOW(), last_seen=NOW()";
		
		mysql_query($sql);
	}
	
	public function get_all_affiliates() {
		$sql = "select * from ".$this->config['database']['prefix']."affiliate";		
		
		$result = array();
		
		$r = mysql_query($sql);
		
		if($r && mysql_num_rows($r)) {
			while($row=mysql_fetch_assoc($r)) {
				$result[] = $row;
			}
		}
		
		return $result;
	}
	
	
	public function get_affiliate_rules($aff_id) {
		$sql = "select * from ".$this->config['database']['prefix']."affiliate_rules WHERE affiliate_id=".$aff_id;
		
		$result = array();
		
		$r = mysql_query($sql);
		
		if($r && mysql_num_rows($r)) {
			while($row=mysql_fetch_assoc($r)) {
				$result[] = $row;
			}
		}
		
		return $result;
		
	}
	
	public function get_rule($rule_id) {
		$sql = "select * from ".$this->config['database']['prefix']."affiliate_rules WHERE affiliate_rule_id=".$rule_id;
		
		$r = mysql_query($sql);
		
		$row=mysql_fetch_assoc($r);
		
		return $row;
		
		
	}
	
	public function update_affiliate_rule($rule_id, $data) {
		$sql = "update ".$this->config['database']['prefix']."affiliate_rules 
			SET rule_data = '".$data['rule_data']."', suppression_percentage = '".$data['suppression_percentage']."' 
		WHERE affiliate_rule_id=".$rule_id;
		
		mysql_query($sql);
	}
	
	public function set_affiliate_rule($aff_id, $data) {
		$sql = "INSERT INTO ".$this->config['database']['prefix']."affiliate_rules 
			SET rule_data = '".$data['rule_data']."', suppression_percentage = '".$data['suppression_percentage']."',
			affiliate_id = '".$data['affiliate_id']."'";
		
		mysql_query($sql);
		
		return mysql_insert_id();
	}
        
        public function get_affiliate_stats($aff_id, $start_date, $end_date) {
            
            /* we are interested in
             * - sales
             * - rebills
             * - combined sales + rebills
             * - refunds
             * 
            */
            
            $aff_data = array();
            $sql = array();
            $sql['Initial Sales'] = "SELECT "
                    . "u.hasoffers_aff_id aff_id, "
                    . "m.BankName bank, "
                    . "h.hAmount amnt, "
                    . "h.refunded_date "
                    . "FROM mem_user u "
                    . "INNER JOIN mem_merchant_history h ON h.user_email=u.Email "
                    . "INNER JOIN mem_merchant m on m.BankID=h.BankID "
                    . "WHERE u.hasoffers_aff_id>0 "
                    . "AND u.CreateTime BETWEEN '" . $start_date . "' AND '" . $end_date . " 23:59:59' "
                    . ($aff_id > 0 ? "AND u.hasoffers_aff_id=".$aff_id : ""); 	
		
                
                $sql['Rebills'] = "SELECT u.hasoffers_aff_id aff_id, 
                    m.BankName bank,
                    h.hAmount amnt, 
                    h.refunded_date 
                    FROM mem_merchant_history h
                    INNER JOIN mem_user u on u.Email=h.user_email 
                    INNER JOIN mem_merchant m on m.BankID=h.BankID 
                    WHERE u.hasoffers_aff_id > 0
                    AND h.hDate BETWEEN '" . $start_date . "' AND '" . $end_date . " 23:59:59' 
                    AND (h.is_rebill=1 OR h.is_yearly_rebill=1 ) ".
                    ($aff_id > 0 ? "AND u.hasoffers_aff_id=".$aff_id : "");
                
                $sql['Refunds'] = "SELECT u.hasoffers_aff_id aff_id, 
                    m.BankName bank,                    
                    h.hAmount amnt,                     
                    h.refunded_date 
                    FROM mem_merchant_history h
                    INNER JOIN mem_user u on u.Email=h.user_email 
                    INNER JOIN mem_merchant m on m.BankID=h.BankID 
                    WHERE u.hasoffers_aff_id > 0
                    AND h.hDate BETWEEN '" . $start_date . "' AND '" . $end_date . " 23:59:59' 
                    AND h.refunded_date IS NOT NULL ".
                    ($aff_id > 0 ? "AND u.hasoffers_aff_id=".$aff_id : "");
                
                foreach($sql as $k=>$s) { 
                
                    $r = mysql_query($s);
                    $tmp = array();
                    if($r && mysql_num_rows($r)) {
                            while($row=mysql_fetch_assoc($r)) {

                                    if(empty($tmp[$row['aff_id']])) {
                                        $tmp[$row['aff_id']] = array();
                                    }
                                    
                                    if(empty($tmp[$row['aff_id']][$row['bank']])) {
                                        $tmp[$row['aff_id']][$row['bank']] = 0;
                                        $tmp[$row['aff_id']][$row['bank']."_refund_count"] = 0;
                                        $tmp[$row['aff_id']][$row['bank']."_sum"] = 0;                                        
                                        $tmp[$row['aff_id']][$row['bank']."_refund_sum"] = 0;
                                    }
                                    
                                    $tmp[$row['aff_id']][$row['bank']]++;
                                    $tmp[$row['aff_id']][$row['bank']."_sum"]+=$row['amnt'];
                                    
                                    if(!empty($row['refunded_date'])) {
                                        $tmp[$row['aff_id']][$row['bank']."_refund_count"]++;
                                        $tmp[$row['aff_id']][$row['bank']."_refund_sum"]+=$row['amnt'];
                                    }
                                    
                            }
                    }
                    
                    
                    
                    $aff_data[$k] = $tmp;
                }
		
		return $aff_data;
        }
        
        // could be for initial orders or rebills
        function get_affiliate_refunds($affiliate_id, $initial_orders = True) {
            
            // $sql = "select "
            
        }
	
}