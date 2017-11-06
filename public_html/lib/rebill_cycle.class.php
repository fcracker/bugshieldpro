<?php
	
	class rebill_cycle {
	
	
			public function create($data) {
			
				$sql = "insert into mem_rebill_cycle set ";
				
				foreach($data as $key=>$value) {
			
				$sql.= "`".$key."` = '".$value."',";
			
			}
			
			$sql = rtrim($sql,",");
			
			
			mysql_query($sql);
			
			return mysql_insert_id();
		
		}
		
		public function update($orderid,$data) {
		
			$sql = "UPDATE mem_rebill_cycle SET ";
			
			foreach($data as $key=>$value) {
			
				$sql.= "`".$key."` = '".$value."',";
			
			}
			
			$sql = rtrim($sql,",");
			
			$sql.= " WHERE id=".intval($orderid);
			
			mysql_query($sql);
		
		}
    
    public function postpone($rebill_id,$hours) {
      //$sql = "UPDATE mem_rebill_cycle SET last_payment=ADDDATE(last_payment,INTERVAL ".intval($hours)." HOUR) WHERE id=".intval($rebill_id)." LIMIT 1";
	  
	  $sql = "UPDATE mem_rebill_cycle SET last_payment=DATE_FORMAT(ADDDATE(last_payment, INTERVAL ".intval($hours)." HOUR),'%Y-%m-%d %k:%i:%s') WHERE id=".intval($rebill_id)." LIMIT 1";
      
      mysql_query($sql);
    }
    
    public function update_retry_index($rebill_id, $new_index) {
        $sql = "UPDATE mem_rebill_cycle SET retry_index= ".intval($new_index)." WHERE id=".intval($rebill_id)." LIMIT 1";
      
      mysql_query($sql);
    }
    
    public function update_forced_merchant($rebill_id, $merchant_id) {
      $sql = "UPDATE mem_rebill_cycle SET forced_merchant=".intval($merchant_id)." WHERE id=".intval($rebill_id)." LIMIT 1";
      
      mysql_query($sql);
    }
		
		public function remove_amount_from_rebill($id=0,$amount) {
			$reb = $this->get_rebill($id);
			$old_amount = $reb->amount;
			
			$new_amount = $old_amount - $amount;
			
			if($new_amount <=0) {
				$this->remove_by_id($id);
			} else {
				$this->update($id,array("amount"=>$new_amount));
			}
			
			
		}
		
		public function remove_for_user($user_id=0,$delete=false) {
		
    if($delete) {
		mysql_query("delete from mem_rebill_cycle where user_id='".intval($user_id)."'");		
    } else {
    mysql_query("update mem_rebill_cycle set active=0,cancel_date=NOW() where user_id='".intval($user_id)."'");		
    }
		
		}
    
    public function remove_by_id($id=0) {
		
		if(mysql_query("update mem_rebill_cycle set active=0,cancel_date=NOW() where id='".intval($id)."'")) {
      return true;
    }		
    
    return false;
		
		}
    
    static public function user_rebills($user_id=0) {
      $res = mysql_query("select id,amount,period,description from mem_rebill_cycle where user_id='".intval($user_id)."' and active=1");
      
      $rebills = array();
      while($r = mysql_fetch_object($res)) {
        $rebills[] = $r;
      }
      
      return $rebills;
    }   

      static public function user_rebills_for_period($user_id=0,$period=60) {
      $res = mysql_query("select id,amount,period,description from mem_rebill_cycle where user_id='".intval($user_id)."' and active=1 and period=".$period);
      
      $rebills = array();
      while($r = mysql_fetch_object($res)) {
        $rebills[] = $r;
      }
      
      return $rebills;
    }
	
	static public function get_rebill($id) {
		$res = mysql_query("select amount,period,description from mem_rebill_cycle where id=".intval($id));
      $r = mysql_fetch_object($res);
      return $r;
	}
		
		
		public function get_next_rebill($period=60,$date=""){
		
		global $cfg;
		
		
		
		if(!strlen($date)) $date = date("Y-m-d H:i:s");
  
		  //build the sql
		 $sql = "SELECT * from mem_rebill_cycle WHERE amount>0 AND active=1 AND period=".$period." AND last_payment>='2012-11-01 00:00:00' AND DATE_ADD(last_payment,INTERVAL ".intval($period)." DAY)<='".$date."' LIMIT 1";
     
     //TEMP ADDITION!
     //$sql= "SELECT * from mem_rebill_cycle WHERE amount>0 AND active=1 AND period=".$period." AND last_payment>='2012-11-01 00:00:00' AND DATE_ADD(last_payment,INTERVAL ".intval($period)." DAY)<='".$date."' AND (user_id<9869 OR user_id>10200) LIMIT 1";
		 //echo "\n".$sql."\n";
		 
		  $res = mysql_query($sql);
  
		  $r = array();
		  if(mysql_num_rows($res)) {
		  $r = mysql_fetch_assoc($res);
		  
		  return $r;
		  }
		  
		  return false;
		
		}
    
    public function get_next_rebills($period=60,$date=""){
		
		global $cfg;
		
		
		
		if(!strlen($date)) $date = date("Y-m-d H:i:s");
  
		  //build the sql
		 $sql = "SELECT r.user_id,r.amount,u.CreateTime as time from mem_rebill_cycle r LEFT JOIN mem_user u ON r.user_id = u.UserID WHERE r.amount>0 AND r.active=1 AND r.period=".$period." AND r.last_payment>='2012-11-01 00:00:00' AND DATE_ADD(r.last_payment,INTERVAL ".intval($period)." DAY)<='".$date."' AND u.CreateTime IS NOT NULL ORDER BY u.CreateTime ASC";
     
     
     
     //TEMP ADDITION!
    // $sql= "SELECT * from mem_rebill_cycle WHERE amount>0 AND active=1 AND period=".$period." AND last_payment>='2012-11-01 00:00:00' AND DATE_ADD(last_payment,INTERVAL ".intval($period)." DAY)<='".$date."' AND (user_id<9869 OR user_id>10200) LIMIT 1";
		 //echo "\n".$sql."\n";
		 
		  $res = mysql_query($sql);
  
		  $r = array();
		  if(mysql_num_rows($res)) {
      
        while($row = mysql_fetch_assoc($res)) {
          $r[] = $row;
        }
        
        return $r;
		  }
		  
		  return false;
		
		}
    
    
    
    public function get_possible_rebill_periods() {
      $sql = "select distinct period from mem_rebill_cycle where period <> 365";
      $res = mysql_query($sql);
      
      $rr = array();
		  if(mysql_num_rows($res)) {
		  while($r = mysql_fetch_assoc($res)) {
        $rr[]=$r["period"];
      }
		  
		  return $rr;
		  }
      
      return array(60);
      
    }
    
    public function get_rebill_settings() {
		$res = mysql_query("select * from mem_rebill_settings");
      
       $data = array();
       while($r = mysql_fetch_object($res)) {
          $data[$r->name] = $r->value;
      }
      return $data;
	}
        
      public function set_rebill_settings($name, $value) {
          $sql = 
                  "UPDATE mem_rebill_settings SET value=". 
                  mysql_real_escape_string($value).
                  " WHERE name='".  mysql_real_escape_string($name)."' LIMIT 1";
      
      mysql_query($sql);
      }
	
		}