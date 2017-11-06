$(function(){
			
		Calendar.setup({inputField: "from_date",ifFormat: "%Y-%m-%d",showsTime: false,button: "from_date_trigger"});
		Calendar.setup({inputField: "to_date",ifFormat: "%Y-%m-%d",showsTime: false,button: "to_date_trigger"}); 
    
    $(".localcorrelation").click(function(evt){
      evt.preventDefault();
      var oid = $(this).attr("rel");
      $.post(
        'ajax_handler.php',
        {action:'recompute_local',order:$(this).attr("rel"),zip:$(this).attr("zip")},
        function(data) {
          console.log(data);          
          var span = $("#correlationlocal" + oid);
          console.log("oid:" + oid);
          if(data.status=='OK') {
            span.text(data.distance);
          } else {
            span.text("Error!MSG: " + data.error);
          }
          
        },
        "json"      
      );
    
    });
	
  
  
  
	
	});
  
  function search_by_orderno() {
    var oid = $("#orderno_search").val();
    location.href = 'anti-fraud.php?action=search&o=' + oid;
    return false;
  }
  
  function search_by_affiliate() {
    var affiliateid = $("#affiliateno_search").val();
    location.href = 'anti-fraud.php?action=search&affiliate=' + affiliateid;
    return false;
  }
  
  function reset_panel() {
    
    location.href = 'anti-fraud.php';
    return false;
  }
  
  function recheck_all(event){
      event.stopPropagation();
      event.preventDefault();      
      location.href = 'anti-fraud-recheck-all.php';
      return false;
  }