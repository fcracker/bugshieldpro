$(document).ready(function(){

$("#show_dates").click(function(elem){
	elem.preventDefault();
	$("#dates_picker_wrapper").slideDown("fast");
});

$("#conversion_dates_change").click(function(){
	var base = "gateway_stats.php?";
	
	var from="from=" + $("#txt_from").val();
	var to="to=" + $("#txt_to").val();
	
	location.href = base + from + "&" + to;
});


Calendar.setup({
	inputField: "txt_from",
	ifFormat: "%Y-%m-%d",
	showsTime: false,
	button: "fromTrigger"
				});
				
Calendar.setup({
	inputField: "txt_to",
	ifFormat: "%Y-%m-%d",
	showsTime: false,
	button: "toTrigger"
				});
});