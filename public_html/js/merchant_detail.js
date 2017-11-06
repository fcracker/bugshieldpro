$(document).ready(function(){
  
  if($("#rebill_forward_balance_wrapper").hasClass("visible")) {
    init_forward_balance_table();
  }
  
  $("#forward_rebill").change(function(){
    
    var val = $(this).val();
    if(parseInt(val)==-1) {
      $("#rebill_forward_balance_wrapper").slideDown("fast");
      if(!$("#rebill_forward_balance_table").hasClass("CRZ")) {
        init_forward_balance_table();
      }
    } else {
      $("#rebill_forward_balance_wrapper").slideUp("fast");
    }
    
  });
  
  $("#rebill_fail_action").change(function(){
    var val = $(this).val();
    if(parseInt(val)==1) {
      $("#rebill_fail_try_after").show();
      $("#rebill_fail_merchant").hide();
    } else if(parseInt(val)==2) {
      $("#rebill_fail_try_after").show();
      $("#rebill_fail_merchant").show();
    } else {
      $("#rebill_fail_try_after").hide();
      $("#rebill_fail_merchant").hide();
    }
    
  });
  
  $("#add_rebill_fwd_rule").click(function(e){
    e.preventDefault();
    
    var tpl = $("#rebill_fwd_rule_tpl").html().replace(/__TPL__/g,"");
    
    $("#rebill_fwd_wrapper").append(tpl);
    
    $("#rebill_fwd_wrapper .rebill_fwd_rule:last").slideDown("fast");
    
    return false;
    
    
  });
 

});


function init_forward_balance_table() {

    //callback function
		var onrebillbalanceSlide = function(e){
			var columns = $(e.currentTarget).find("td");
			var ranges = [], total = 0, i, s ="Ranges: ", w,current_id;
			for(i = 0; i<columns.length; i++){
			//w = columns.eq(i).width()-10 - (i==0?1:0);        
        w = columns.eq(i).width();
				ranges.push(w);
				total+=w;
			}		 
			for(i=0; i<columns.length; i++){			
				ranges[i] = 100*ranges[i]/total;
        current_id = columns.eq(i).attr("rel");
        columns.eq(i).children("input").val(Math.round(ranges[i]));
        $("#mfri" + current_id).text(Math.round(ranges[i]));
			}		
		};

   $("#rebill_forward_balance_table").colResizable({
			liveDrag:true, 
			gripInnerHtml:"<div class='grip'></div>", 
			draggingClass:"dragging", 
			onResize:onrebillbalanceSlide});
}


function add_country_detail_fields(){

//get the next ID
var next_id = ($(".one_country_rule").length - 1);//one of them is the template

//get the template
var tpl = $("#new_country_exceptions_template").html().replace(/__TPL__/g,"").replace(/%%ID%%/g,next_id);
//push it on top, ready to be edited
$("#country_rules").prepend(tpl);

$("#country_rules .one_country_rule:first").slideDown("slow");

return false;

}

function remove_country_rule(el) {
	$(el).parent(".one_country_rule").slideUp("fast",function(){$(el).parent(".one_country_rule").remove()});
	
	return false;
}