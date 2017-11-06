$(function() {
    
    $(".add_campaign").click(function(evt){
      $("#new_offer_wrapper").hide();;
      $("#new_campaign_wrapper").slideDown("fast");
    });
    
    $(".add_offer").click(function(evt){
      $("#new_campaign_wrapper").hide();
      $("#new_offer_wrapper").slideDown("fast");
    });
    
    
});