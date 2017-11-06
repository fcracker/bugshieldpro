<?php

include_once('json/jsonRPCClient.php');

class get_response {
  private $api_key = '35e80e992ddb61a4a79937f3a030e224';
  private $api_url = 'http://api2.getresponse.com';
  private $client;
  
  //this is a cached version of existing campaigns
  public $default_campaign_key = "ngwZ";  
  public $available_campaigns = array("ngwZ","n9sP");
  
  function __construct(){
    $this->client = new jsonRPCClient($this->api_url);
  }
  
  //returns a campaign key
  function get_campaign_by_name($name) {
    
    //grab all the campaigns(we could do it by filtering in the query)
    $campaigns = $this->get_campaigns();    
    
    if(is_array($campaigns)&& count($campaigns)) {
      foreach($campaigns as $key=>$campaign) {
        if($campaign["name"]==$name) {
          return $key;
        }
      }
    }
    
    return false;
  }
  
  public function get_campaigns() {
    return $this->execute("get_campaigns");  
  }
  
  function add_contact_to_campaign($contact,$campaign="") {
    if(!strlen($campaign)) $campaign=$this->default_campaign_key;
    
    //check we have necessary data
    if(!isset($contact["name"]) || !isset($contact["email"])) {
      return false;
    }
    
    $args = array_merge(array("campaign"=>$campaign),$contact);
    
   // print_r($args);
    return $this->execute("add_contact",$args);
    
  }
  
  function remove_by_email($email) {
    //grab the user
    $contacts = @$this->execute("get_contacts",array("email"=> array ( 'EQUALS' => $email )));
    if(is_array($contacts) && count($contacts)) {
      $keys = array_keys($contacts);
      $to_remove = $keys[0];
      
      return $this->execute("delete_contact",array("contact"=>$to_remove));
      
    }
    return false;
  }
  
  private function execute($action,$args=array()) {
    if(is_array($args) && count($args))
      return $this->client->{$action}($this->api_key,$args);
    else
      return $this->client->{$action}($this->api_key);
  }
  
}
