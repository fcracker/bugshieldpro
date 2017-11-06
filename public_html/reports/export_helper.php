<?php

//helper class for making various classifications
class Export_Help_Tables {
  
  //repeating strings
  const MAIL_CLASS_US_INFM_6 = "First-Class Package Service";
  const MAIL_CLASS_US_7_9 = "Priority Mail Flat Rate Envelope";
  const MAIL_CLASS_US_10_15 = "Priority Mail Legal Flat Rate Envelope";
  const MAIL_CLASS_US_16_INFP = "";
  const MAIL_CLASS_OTHER_INFM_12 = "First-Class Package Intl Service";
  const MAIL_CLASS_OTHER_13_INFP = "";
  
  //define compare tables
  
  //qty and country dependencies
  static public $mail_class = array(
    "INFM-6"  =>  array("US"=>self::MAIL_CLASS_US_INFM_6,"OTHER"=>self::MAIL_CLASS_OTHER_INFM_12),
    "7-9"     =>  array("US"=>self::MAIL_CLASS_US_7_9,"OTHER"=>self::MAIL_CLASS_OTHER_INFM_12),
    "10-12"   =>  array("US"=>self::MAIL_CLASS_US_10_15,"OTHER"=>self::MAIL_CLASS_OTHER_INFM_12),
    "13-15"   =>  array("US"=>self::MAIL_CLASS_US_10_15,"OTHER"=>self::MAIL_CLASS_OTHER_13_INFP),
    "16-INFP" =>  array("US"=>self::MAIL_CLASS_US_16_INFP,"OTHER"=>self::MAIL_CLASS_OTHER_13_INFP),    
    );
    
    static public $tracking_type = array(
      "US"    =>  "Delivery Confirmation",
      "CA"    =>  "E-DelCon",
      "OTHER" =>  "",
    );
    
    static public $length = array(
    "INFM-3"  =>  array("US"=>7,"OTHER"=>7),
    "4-6"     =>  array("US"=>9,"OTHER"=>9),
    "7-12"    =>  array("US"=>11,"OTHER"=>10),
    "13-15"   =>  array("US"=>11,"OTHER"=>""),
    "15-INFP" =>  array("US"=>"","OTHER"=>""),    
    );
    
    static public $width = array(
    "INFM-3"  =>  array("US"=>9,"OTHER"=>9),
    "4-6"     =>  array("US"=>11,"OTHER"=>11),
    "7-9"     =>  array("US"=>12,"OTHER"=>13),
    "10-12"   =>  array("US"=>14,"OTHER"=>13),
    "13-15"   =>  array("US"=>14,"OTHER"=>""),
    "16-INFP" =>  array("US"=>"","OTHER"=>""),    
    );
    
    static public $height = array(
    "INFM-6"  =>  array("US"=>2,"OTHER"=>2),
    "7-11"    =>  array("US"=>2,"OTHER"=>3),
    "12"      =>  array("US"=>3,"OTHER"=>3),
    "13-15"   =>  array("US"=>3,"OTHER"=>""),
    "16-INFP" =>  array("US"=>"","OTHER"=>""),    
    );
    
    static public $weight = array(
    "INFM-1"  =>  array("US"=>3,"OTHER"=>3),
    "2"       =>  array("US"=>5,"OTHER"=>5),
    "3"       =>  array("US"=>6,"OTHER"=>6),
    "4"       =>  array("US"=>8,"OTHER"=>8),
    "5"       =>  array("US"=>10,"OTHER"=>10),
    "6"       =>  array("US"=>12,"OTHER"=>12),
    "7"       =>  array("US"=>16,"OTHER"=>13),
    "8"       =>  array("US"=>16,"OTHER"=>15),
    "9"       =>  array("US"=>17,"OTHER"=>17),
    "10"       =>  array("US"=>19,"OTHER"=>18),
    "11"       =>  array("US"=>21,"OTHER"=>20),
    "12"       =>  array("US"=>23,"OTHER"=>22),
    "13"       =>  array("US"=>25,"OTHER"=>""),
    "14"       =>  array("US"=>27,"OTHER"=>""),
    "15"       =>  array("US"=>29,"OTHER"=>""),
    "16-INFP" =>  array("US"=>"","OTHER"=>""),    
    );
    
    static public $description = array(
      "US"    =>  "",
      "OTHER" =>  "all natural bed protec. kit",
    );
    
    static public $exempt = array(
      "US"    =>  "",
      "OTHER" =>  "NOEEI 30.37(a)",
    );  
  
    static public $content = array(
      "US"    =>  "",
      "OTHER" =>  "Merchandise",
    );
    
    static public $origin_country = array(
      "US"    =>  "",
      "OTHER" =>  "UNITED STATES",
    );
  
  
  
  /*
    function that gives value based on a 2 dimensional table (matrix)
    params:
    $table - defines the table against which we are comparing
    $first - first param value
    $second - second param
    $default - what to return in case of (column,row) not found or table does not exist
  */
  static public function val2d($table,$first,$second,$default="") {
    
    
       
    //do we have the table ?
    if(!isset(self::${$table}) || !is_array(self::${$table})) return $default;
    
    //internal table value
    $tbl = self::${$table};
    
    //is it 2 dimensional ?
    if(!is_array(reset($tbl))) return $default;
   
    //parse the table
    foreach($tbl as $row => $columns) {
      
      $condition1 = Export_Help_Tables::compare($row,$first);      
      
      
      if($condition1) {
        foreach($columns as $column_key=>$column_value) {        
        
          if(Export_Help_Tables::compare($column_key,$second)) {

            return $column_value;//return the value!
            
          }
          
        }
      }      
      
    }
    
    return $default;
    
  }
  
  /*
  function to get the value from a straighht table
  */
  static public function val1d($table,$key,$default="") {
    
    
       
    //do we have the table ?
    if(!isset(self::${$table}) || !is_array(self::${$table})) return $default;
    
    //internal table value
    $tbl = self::${$table};    
   
    //parse the table
    foreach($tbl as $row => $value) {               
        
          if(Export_Help_Tables::compare($row,$key)) {

            return $value;//return the value!
            
          }      
    }
    
    return $default;
    
  }
  
  //compares 2 possible values, one of which could be a range
  static public function compare($first,$second) {
    
    $result = false;
    
    if($first==$second) {
        $result = true;
      } else if(strpos($first,"-") && is_numeric($second)) {
        $parts = explode("-",$first);
        if($parts[0]=="INFM") {//minus infinite
          if($second<=(int)$parts[1]) {
            $result = true;
          }
        } else if($parts[1]=="INFP") {//plus infinite
          if($second>=(int)$parts[1]) {
            $result = true;
          }
        } else if((int)$parts[0]<=$second && $second<=$parts[1]) {
          $result = true;
        }
      } else if($first=="OTHER") {
        $result = true;
      }
      
      return $result;
      
  }
  
  //small utility to parse country strings, as according to our needs
  static public function country($country) {
    $return = $country;
    switch($country) {
      case "CA":$return = "CANADA";break;
      default:break;
    }
    return $return;
  }
  
  //small utility to parse value, based on country
  static public function value($country,$qty=1,$unit_value=19.9) {
    $return = $qty*$unit_value;
    switch($country) {
      case "US":$return = "";break;
      default:break;
    }
    return $return;
  }
  
}