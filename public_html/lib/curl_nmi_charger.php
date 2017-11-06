<?php
    include_once 'html/Snoopy.class.php';
    include_once 'html/simple_html_dom.php';
    
    function do_nmi_charge($transaction,$amount,$debug=false) {

	  $snoopy = new Snoopy; 
	  
	  /**
	  
		[FORM_POSTED] => true
    [Action] => Login
    [username] => bedroomguardian
    [password] => pop2nowpop2now2
	  
	  **/
    $host = "https://secure.nmi.com/merchants/";
    $url  = $host."login.php";
    
    //set up some stuff
    $snoopy->agent = "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.76 Safari/537.36";
    $snoopy->refer = $url;
    
    $snoopy->host = "secure.nmi.com";
    
    $snoopy->port = "443";
    
    $snoopy->maxredirs = 0;//fuck that
    

    
    $snoopy->rawheaders['Origin'] = 'https://secure.nmi.com';
   // $snoopy->rawheaders['Host'] = 'secure.nmi.com';
    $snoopy->rawheaders['Connection'] = 'keep-alive';
    
	  

      //login part 
      $submit_vars["username"] = "bedroomguardian"; //username
      $submit_vars["password"] = "pop2nowpop2now2";  //password
      $submit_vars["FORM_POSTED"] = "true"; 
      $submit_vars["Action"] = "Login";
      
      $tid = "";
      if($debug) {
        echo "<pre>";
        echo "\n Logging in ... \n";    
      }
      
      $snoopy->submit($url,$submit_vars); 
      
      //process the headers, get the session tracker
      if($debug) {
          echo "\n Headers: \n";
          echo print_r ($snoopy->headers,1);
     }
      
      if(is_array($snoopy->headers)) {
        foreach($snoopy->headers as $header) {
          if(preg_match("/^(Location: |URI: )/i",$header)) {
            preg_match("/^(Location: |URI:)(.*)/",$header,$matches); 
              //echo print_r($matches,1);
              if(isset($matches[2])) {
                //process is a bit
                $tid = chop(str_replace("index.php?tid=","",$matches[2]));
                if($debug) {
                  echo  "\n TID: @".$tid."@ \n";
                }
              }
             
              
            
          }
        }
      }
      
      


      $snoopy->setcookies();
      $cookies = $snoopy->cookies;   
       if($debug) { 
        echo "<pre>";
      }
      
  if(strlen($tid))      {
  
    //continue
    
    if($debug) {
      echo " - GETTING FORM -";
     }
    
    //$transaction = "2003794341XX";
    
    $terminal_url = $host."virtualterminal.php?transaction_id=".$transaction."&tid=".$tid;
    
    $snoopy->fetch($terminal_url);
    
   //echo "<pre>TERMINAL: \n\n ".print_r($snoopy->results,1)."</pre>";
   
   $html = str_get_html($snoopy->results);
   
  
   
   $inputs = $html->find("#virtualterminal_ccsale input");
   
   $selects = $html->find("#virtualterminal_ccsale select");
   
   $vars = array();
   
   foreach($inputs as $input) {
    if($input->hasAttribute("name")) {
      $vars[$input->getAttribute("name")] = $input->getAttribute("value");
    }
   }
   
    foreach($selects as $select) {
    if($select->hasAttribute("name")) {
      //grab the selected child
      $got_selected = false;
      foreach($select->children() as $child) {
        if($child->hasAttribute('selected')) {
           $vars[$select->getAttribute("name")] = $child->getAttribute("value");
           $got_selected = true;
        }
      }
      
      if(!$got_selected) {
        $first_option = $select->first_child();
        if($first_option!=null) {
          $vars[$select->getAttribute("name")] = $first_option->getAttribute("value");
        }
      }
     
    }
   }
   
   //echo "<pre>TERMINAL VARS: \n\n ".print_r($vars,1)."</pre>";
   if($debug) {
      echo " - AMOUNT: ".$amount." -";
     }
   
   if(ceil($amount)>0) {
   
     if($debug) {
      echo " - DOING CHARGE -";
     }
    //mod what we need
    $vars['amount'] = $amount;
    
    //set the refer
    $snoopy->refer = $terminal_url;
   
    //post it away!
    $snoopy->submit($terminal_url,$vars);
    
    if($debug) {
      echo "<pre>TERMINAL: \n\n ".print_r($snoopy->results,1)."</pre>";
    }
    
    //Transaction Successful
    $done  = (strpos($snoopy->results,"Transaction Successful") !== FALSE);
    
   
    
    return $done;
    
    
    
   }
   
    
    
  
  }
  
  return false;
  
  }
       
     
     
     
       