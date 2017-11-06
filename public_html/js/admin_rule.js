$(function() {
    count_groups();
	
	$(".has_calendar").each(function(){
	
		Calendar.setup({inputField: $(this).attr("id"),ifFormat: "%Y-%m-%d",showsTime: false});	
		
	});
	
	$(".conditions_add_or a").click(function(){	
		var tpl = $($(".condition_wrapper")[0]).clone();		
		var group_count = $("[data-groupwrapper]").length-1;		
		$("#conditions_wrapper").append(tpl);		
		tpl.show().attr('data-groupwrapper',group_count);
		tpl.find(".and_link").attr("data-group",group_count);		
		tpl.find(".remove_link").attr("data-group",group_count).show();
		
		count_groups();
		
		return false;
	});
	
	$("#conditions_wrapper").on('click','.and_link',function(){
	
		var tpl = $($(".condition_rule")[0]).clone();
		var group = $(this).attr("data-group");		
		$(this).parent().after(tpl);		
		tpl.find(".and_link").attr("data-group",group);
		tpl.find(".remove_link").attr("data-group",group).show();
		count_groups();
		return false;
	});
	
	$("#conditions_wrapper").on('click','.remove_link',function(){
		var group = $(this).attr("data-group");	
		
		if($("#mainform .and_link[data-group="+group+"]").length == 1) {
		$(this).parent().parent().remove();	
		} else {
		$(this).parent().remove();	
		}
		count_groups();
		
		return false;
	});
	
	$("#conditions_wrapper").on('change','.condition_object',function(){
		var val = $(this).val();
		$(this).parent().find(".comparation_select").hide();
		$(this).parent().find("."+val+"_comparation").show();
		
		$(this).parent().find(".condition_value").hide();
		
		var element = $(this).parent().find("."+val+"_condition");
		
		element.show();
		
		if(typeof element.attr("id") == "undefined") {
		
			 var randId = "inputid"+(parseInt(Math.random()*100000));
			 element.attr("id",randId);
			 console.log($(this));
			 if($(this).find("option:selected").attr("data-type") == "date") {
					Calendar.setup({inputField: randId,ifFormat: "%Y-%m-%d",showsTime: false});				
				}
		} 
		
		
		
		
		
		
	});
	
});

function count_groups() {

		// count groups
		var group_count = $("#mainform [data-groupwrapper]").length;
		var i = 0,group_meta = [group_count];
		for(i=0;i<=group_count;i++) {
			group_meta.push($("#mainform [data-groupwrapper="+i+"] .condition_rule").length);
		}
		
		$("#groups").val(group_meta.join(";"));
}