<?php
function generate_exit_popup_code($exit_id,$popup_text="",$discount_page_text="",$custom_price=10) {
  global $cfg;
  $uid = substr(md5(time()),0,8);
  
  $default_popup = '$$$$$CONSUMER SAVINGS ALERT$$$$$'."\n".
'Wait! Try Bedroom Guardian for just $6.95!'."\n".
'Protect your home from Bed Bugs for less than 25 cents per day!'."\n".
'To claim your ADDITIONAL DISCOUNT click *****Cancel***** Or *****Stay on this page***** now!'."\n".
'$$$$$CONSUMER SAVINGS ALERT$$$$$';
  
  $popup = strlen($popup_text) ? $popup_text : $default_popup;
  
  $popup = str_replace("\r","",$popup);
  
  $discount_text = strlen($discount_page_text) ? $discount_page_text : '<span class="c42">Discount Activated!</span>  Discount Reserved For';
  
  $discount_text = str_replace("'","\'",$discount_text);
  
  ?>
  <style>
  .confirm_wrapper {   
    position:fixed; 
    width:100%;
    height:100%;
    background:white;
    z-index:1000;
    padding-top:20px;
    font-size:26px;
    color:red;
    text-align:center;
    opacity:0.1;
    -webkit-transition: all 1s ease-in-out;
    -moz-transition: all 1s ease-in-out;
    -o-transition: all 1s ease-in-out;
    transition: all 1s ease-in-out;
  }
  </style>
  <script>
    var switch_offer_<?php echo $uid;?> = function() {
    
       
        var qty = $("#qty").val();
	
        $.post(
          "<?php echo $cfg['site']['url_ssl'];?>/price_computer.php",
          {qty:qty,exit:<?php echo $exit_id;?>},
          function(json) {		
              $("#unit_price").text(json.unit_price).effect("highlight",1000);
              $("#subtotal").text(json.subtotal).effect("highlight",1000);
              $("#total").text(json.total).effect("highlight",1000);
			  $("#discount_text").html('<?php echo $discount_text;?>').effect("highlight",1000);
            
            },
          "json"
        );
    };
    
    var unload_<?php echo $uid;?> = function(){
      var lines = [];
      var text = "";
      
      //some browsers do not show the returned text
      var is_not_showing_browser = 
      ( /Firefox[\/\s](\d+)/.test(navigator.userAgent) && new Number(RegExp.$1) >= 4); 
      
	  <?php 
	  $lines = explode("\n",$popup);
	  foreach($lines as $line):
		if(strlen($line)):
	  ?>
	  lines.push("<?php echo $line;?>");
	  <?php endif;endforeach;?>      
      
      if(is_not_showing_browser) {
      
        text = lines.join("<br />");
        var confirm_wrapper = $("<div class='confirm_wrapper' id='confirm_<?php echo $uid;?>'>"+(text.replace("\n\n","<br />"))+"</div>");
        $("body").append(confirm_wrapper);
        $("#confirm_<?php echo $uid;?>").css("opacity","0.9");
		switch_offer_<?php echo $uid;?>();
        
      } else {
      
        text = lines.join("\n\n");
        
        switch_offer_<?php echo $uid;?>();
        
      }
      
      return text;
    };
    
    var focus_<?php echo $uid;?> = function(){
      $("#confirm_<?php echo $uid;?>").css("opacity","0.1").remove();
    };
    
    window.onbeforeunload = unload_<?php echo $uid;?>;
    window.onfocus = focus_<?php echo $uid;?>;
  </script>
  <?php
  
}