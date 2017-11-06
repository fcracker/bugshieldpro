$(document).ready(function(){


$("#dialog_actions").dialog({
                            modal:true,
                            autoOpen:false,
                            resizable:false,
                            hide:"hide",
                            width:800,
                            height:600,
                            buttons: {
                                "Close": function() {
                                    jQuery(this).dialog("close");
                                    //if(reload_flag)location.reload();
                                }
                            }
                        });

$("a.actions").click(function(evt){

  evt.preventDefault();
  
  
  
  //get ID
  var id = $(this).text();
  
  //get possbile actions 
   trigger_action('getactions',id);
   //$("#dialog_actions").html("FEATURE NOT YET IMPLEMENTED.");
  
  $("#dialog_actions").dialog("open");

});


});

var trigger_action = function(action,id) {


	$('#dialog_actions').dialog('option', 'title','Actions - LOADING, PLEASE WAIT');
	
  var params = {action:action,id:id};
  
  //check for extra params
  if($("#"+action+"-wrapper").length) {
    $("#"+action+"-wrapper input.param").each(function(){    
      params[$(this).attr("id").replace(action+"_","")] = $(this).val();    
    });
    
    $("#"+action+"-wrapper select.param").each(function(){    
      params[$(this).attr("id").replace(action+"_","")] = $(this).val();    
    });
	
	$("#"+action+"-wrapper input.param-radio").filter(":checked").each(function(){ 	
      params[$(this).attr("name").replace(action+"_","")] = $(this).val();    
    });
	$("#"+action+"-wrapper .param-textarea").each(function(){ 	
      params[$(this).attr("name").replace(action+"_","")] = $(this).val();    
    });
  }
  
  $.post(
    "ajax_actions.php",
    params,
    function(data) {
      $("#dialog_actions").html(data);
	  $('#dialog_actions').dialog('option', 'title','Actions');
    }
  ); 
}

var toggle_preaction = function(action) {

  $("#"+action+"-wrapper").slideToggle(250);

}