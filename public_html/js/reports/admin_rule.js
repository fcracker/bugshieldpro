$(function() {
	$(".has_calendar").each(function(){	
		Calendar.setup({inputField: $(this).attr("id"),ifFormat: "%Y-%m-%d",showsTime: false});
	});
	
	$(".emails_add_or a").click(function(){	
		var tpl = $($(".emails_wrapper")[0]).clone();		
		var group_count = $("[data-groupwrapper]").length-1;		
		$("#emails_wrapper").append(tpl);		
		tpl.show();
		return false;
	});
	

	$("#emails_wrapper").on('click','.remove_link',function(){
		var group = $(this).attr("data-group");			
		if($("#mainform .and_link[data-group="+group+"]").length == 1) {
		$(this).parent().parent().remove();	
		} else {
		$(this).parent().remove();	
		}			
		return false;
	});	
	
	$(".affiliate_add_or a").click(function(){	
		var tpl = $($(".affiliate_wrapper")[0]).clone();		
		var group_count = $("[data-groupwrapper]").length-1;		
		$("#affiliates_wrapper").append(tpl);		
		tpl.show();
		return false;
	});
	

	$("#affiliates_wrapper").on('click','.remove_link',function(){
		var group = $(this).attr("data-group");			
		if($("#mainform .and_link[data-group="+group+"]").length == 1) {
		$(this).parent().parent().remove();	
		} else {
		$(this).parent().remove();	
		}			
		return false;
	});	
	
});