<?php

function display_fraud($data,$context) {

$class = "green";
$value=$data;

$func = "check_".$context;
if(function_exists($func)) {
  list($class,$value) = $func($data);
}

return array("class"=>$class,"value"=>$value);

}

function check_avs($data){

  switch($data) {
    
    case "N":$class="red";break;
    case "G":$class="yellow";break;
    default:$class="green";break;
  
  }

  return array($class,"(".$data.")");
}

function check_cvv($data){

  switch($data) {
    
    case "N":$class="red";break;
    default:$class="green";break;
  
  }

  return array($class,"(".$data.")");
}

function check_generic_no_yes($data) {
  switch($data) {
    
    case "Yes":$class="red";break;
    case "NA":$class="grey";break;
    default:$class="green";break;
  
  }

  return array($class,$data);
}


function check_generic_yes_no($data) {
  switch($data) {
    
    case "No":$class="red";break;
    case "NA":$class="grey";break;
    default:$class="green";break;
  
  }

  return array($class,$data);
}

function check_correlation_external($data) {
  //just in km now
  //hard limit to 50/100
  $num = intval($data);
  
  if($num<=50) {
    $class = "green";
  }
  elseif($num>50 && $num<=100) {
    $class = "yellow";
  } else {
    $class = "red";
  }
  
  return array($class,$data." KM");
}

function check_correlation_local($data) {
  return array("grey",$data);
}

function check_bin_country_match($data) {

  $parts = explode(";",$data);
  $class = "red";
  if($parts[0]==$parts[2]) {
    $class = "green";
  }
  
  return array($class,implode("<br />",$parts));

}


function check_bin_prepaid_match($data) {return check_generic_no_yes($data);}
function check_ip_is_proxy($data) {return check_generic_no_yes($data);}
function check_is_ip_high_risk($data) {return check_generic_no_yes($data);}
function check_is_email_high_risk($data) {return check_generic_no_yes($data);}
function check_is_address_high_risk($data) {return check_generic_no_yes($data);}

function check_risk_score($data) {
  return array("grey",$data);
}

function check_bin_phone_match($data) {return check_generic_yes_no($data);}
function check_bin_name_match($data) {return check_generic_yes_no($data);}