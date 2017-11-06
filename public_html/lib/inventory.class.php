<?php

class Inventory {
  
  private $db;
  private $config;
  private $instance;
  
  private $log_file = null;
  
  private $do_log = false;
  
  
  public function __construct($db=null,$config=null,$do_log = false) {
  
    $this->db = $db;
    $this->config = $config;
    
    $this->do_log = $do_log;
    
    $this->log_file = LOG_DIR."inventory/inv-".date("Y-m-d").".log";
    
  }
  
  
  /**
  * returns an inventory for a specific day
  * it defaults to the current day
  */
  public function getInventoryDay($day = null) {
    
    //make sure we use a date - date validity is not checked!
    $day = $day!==null ? $day : date('Y-m-d');
    
   
    //build query
    $query = 'SELECT * FROM mem_inventory WHERE inventory_day=?';
    
    $sth = $this->db->prepare($query);
    
    $sth->execute(array($day));
    
    $result = $sth->fetch(PDO::FETCH_ASSOC);
    
    if($result !== FALSE) {
      return intval($result['inventory_value']);
    } else {
      //we should create the inventory date, based on the previous entry
      if($day == date('Y-m-d')) 
      {
         $query = 'SELECT * FROM mem_inventory ORDER BY inventory_id DESC LIMIT 1';
         $sth = $this->db->prepare($query);    
         $sth->execute();
         $result = $sth->fetch(PDO::FETCH_ASSOC);
         
         if($result !== FALSE) {
          
          //create entry for day, then return it
          $this->setInventoryDay($result['inventory_value'],$day);
          
          return intval($result['inventory_value']);
          
          
         }
       
       }
       
      
    }
    
    //have a default
    return 0;
    
    
  }
  
  /**
  * Retrieves a set of entries for a given start/end date
  **/
  public function getInventoryOnInterval($start_date=null,$end_date=null) {
  
    //make sure we use a date - date validity is not checked!
    $start_date = $start_date!==null ? $start_date : date('Y-m-d');
    $end_date = $end_date!==null ? $end_date : date('Y-m-d');
    
    //build the query
    $query = 'SELECT SUM(inventory_value) AS total FROM mem_inventory WHERE inventory_day>=? AND inventory_day<=?';
    
    $sth = $this->db->prepare($query);
    
    $sth->execute(array($start_date,$end_date));
    
    $result = $sth->fetch(PDO::FETCH_ASSOC);
    
    if($result !== FALSE) {
      return intval($result['total']);
    } 
    
    //a default
    return 0;
    
    
  }
  
  
  public function getInventoryBetween($start_date=null,$end_date=nul) {
    //make sure we use a date - date validity is not checked!
    $start_date = $start_date!==null ? $start_date : date('Y-m-d');
    $end_date = $end_date!==null ? $end_date : date('Y-m-d');
    
    //build the query
    $query = 'SELECT * FROM mem_inventory WHERE inventory_day>=? AND inventory_day<=?';
    
    $sth = $this->db->prepare($query);
    
    $sth->execute(array($start_date,$end_date));
    
    $result = array();
    
    while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      $result[] = $row;
    }
    
    return $result;
    
    
  }
  
  /**
  * Updates todays entry
  */
  public function updateInventoryDay($value) {
  
    //build query
    $query = 'UPDATE mem_inventory SET inventory_value=? WHERE inventory_day="'.date("Y-m-d").'"';
    
    $log_text = date("Y-m-d H:i:s")." - updateInventoryDay with value ".$value."\n";
    
    if($this->do_log) {
        file_put_contents($this->log_file,$log_text);
    }
    
    
    $sth = $this->db->prepare($query);
    
    return $sth->execute(array($value));    
  
  }
  
  public function alterStock($qty=0,$sign="-") {
  
    $log_text = date("Y-m-d H:i:s")." - altering stock with qty ".$qty." and sign='".$sign."'\n";
    if($this->do_log) {
        file_put_contents($this->log_file,$log_text,FILE_APPEND);
    }
    
    if(is_numeric($qty)) {
    
    //make sure we have a value to update
    $today_value = $this->getInventoryDay();
    
      //build query
      $query = 'UPDATE mem_inventory SET inventory_value=inventory_value '.$sign.' '.$qty.' WHERE inventory_day="'.date("Y-m-d").'"';
    
      $sth = $this->db->prepare($query);
    
      return $sth->execute();
    }
  }
  
  
  /**
  * Creates a new entry for an inventory date
  */
  public function setInventoryDay($value,$day=null) {
    //make sure we use a date - date validity is not checked!
    $day = $day!==null ? $day : date('Y-m-d');
    
     $log_text = date("Y-m-d H:i:s")." - setInventoryDay with val ".$value." and day='".$day."'\n";
    if($this->do_log) {
        file_put_contents($this->log_file,$log_text,FILE_APPEND);
    }
    
    //build query
    $query = 'INSERT INTO mem_inventory SET inventory_value=?,inventory_day=?';
    
    $sth = $this->db->prepare($query);
    
    return $sth->execute(array($value,$day));    
    
  }
  
}