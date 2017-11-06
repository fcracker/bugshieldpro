var proceed_with_update = false;
$(function(){

$("#dialog_confirm").dialog({
                            modal:true,
                            autoOpen:false,
                            resizable:false,
                            hide:"hide",
                            width:400,
                            height:300,
                            buttons: {
                                "Close": function() {
                                    jQuery(this).dialog("close");
                                }
                            }
                        });
                        
                        

});

function update_inventory() {

    if(!proceed_with_update) {
      $("#dialog_confirm").dialog("open");
      return false;
   }
   return true;
}

function inventory_update() {
  proceed_with_update = true;
  $("#inventory_cp_form").submit();
}

function inventory_update_cancel() {  
      $("#dialog_confirm").dialog("close");
}