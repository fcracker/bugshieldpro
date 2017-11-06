<?php

class conversions{
  
  private $data;
  private $cache_life = 3600;
  private $start_time = null,$stop_time=null;
  private $dwhere = " ";
  private $cache_add = "";
  
  public function __construct($prefetch=false){
    if($prefetch) {
      $this->fetch_data();
    }
    
    //set default start-stop dates
    $sql = "SELECT MIN( datetime) AS start_time, MAX(datetime) AS stop_time FROM  mem_stats";
  
  $res = mysql_query($sql);
  if($res) {
  $object = mysql_fetch_object($res);  
    if($this->start_time==null) {
      $this->start_time = strtotime($object->start_time);
      $this->stop_time = strtotime($object->stop_time);    
    } else {
      $this->start_time = time();
      $this->stop_time  = time();
    }
  } else {
    //the dates are not set, set them to today
    $this->start_time = time();
    $this->stop_time  = time();
  }
  
  //set the used where
  $this->where_set();
  }
  
  private function where_set() {
    $this->dwhere = " AND (datetime >= '".date("Y-m-d 00:00:00",$this->start_time)."' AND datetime<='".date("Y-m-d 00:00:00",$this->stop_time)."') ";
    
    //set the cache add as well
    $this->cache_add = $this->start_time.$this->stop_time."_";
  }
  
  public function get_dates(){
    return array(
      "start"=>$this->start_time,
      "stop"=>$this->stop_time,
    );
  }
  
  public function set_dates($dates = array()) {
    if(isset($dates["start"]) && isset($dates["stop"])) {
      $this->start_time = $dates["start"];
      $this->stop_time = $dates["stop"];
      
      $this->where_set();
      
    }
  }
  
  public function fetch_data(){
    $sql = "SELECT * from mem_stats where 1 ".$this->dwhere." order by datetime DESC";
    $result = mysql_query($sql);
    
    $r = array();
    while($res = mysql_fetch_object($result)) {
      $r[] = $res;
    }
    
    $this->data = $r;
  }
  
  public function get_data() {
    return $this->data;
  } 
  
  private function get_cache($file="xx") {
    
    $f = dirname(__FILE__)."/../cache/".$this->cache_add.$file;
    
    if(file_exists($f)) {
      //check if we are still within cache bounds
      if(filemtime($f)>(time()-$this->cache_life)){
        return file_get_contents($f);
      }
    }
    return false;
  }
  
  private function write_cache($file,$content="") {
    $f = dirname(__FILE__)."/../cache/".$this->cache_add.$file;
    file_put_contents($f,$content);
  }
  
  public function flush_cache() {
      $cache = dirname(__FILE__)."/../cache/";   

    $mydir = opendir($cache);
    while(false !== ($file = readdir($mydir))) {
        if($file != "." && $file != "..") {          
                unlink($cache.$file) or DIE("couldn't delete $dir$file<br />");
        }
    }
    closedir($mydir);      
    clearstatcache();
    
    return true;
  }
  
  
  public function get_total_by_membership($type="bronze"){
    
    $r = $this->get_cache("total_".$type);
    
    if(!$r) {
      
      $result = mysql_query("select count(id) as K from mem_stats where membership_status='".$type."'".$this->dwhere);
     
      $o = mysql_fetch_object($result);
      
      $this->write_cache("total_".$type,$o->K);
      
      $r = $o->K;
      
    }
    
    return $r;
    
  }
  
  public function get_indexes() {
    
    $r = $this->get_cache("indexes");
    
   if(!$r) {
      
      $result = mysql_query("select distinct entry_page from mem_stats where 1".$this->dwhere);
      
      $tmp = array();
      
      while($o = mysql_fetch_object($result)){
        $tmp[] = $o->entry_page;
      }
      
      $this->write_cache("indexes",implode(";",$tmp));
      
      $r = $tmp;
      
    } else {
      $r = explode(";",$r);
    }
    
    return $r;
  }
  
  public function get_total_by_index_membership($index="index.php",$type="bronze") {
   
    $file = "total_".str_replace(".php","",$index)."_".$type;
   $r = $this->get_cache($file);
    
    if(!$r) {
      
      $result = mysql_query("select count(id) as K from mem_stats where membership_status='".$type."' and entry_page='".$index."'".$this->dwhere);
      
      $o = mysql_fetch_object($result);
      
      $this->write_cache($file,$o->K);
      
      $r = $o->K;
      
    }
    
    return $r;

   
  }
  
  
  
}