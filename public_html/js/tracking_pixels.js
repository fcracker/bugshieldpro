$(document).ready(function(){

$("#show_dates").click(function(elem){
	elem.preventDefault();
	$("#tracking_picker_wrapper").slideDown("fast");
});

$("#tracking_dates_change").click(function(){
	var base = "tracking_setup.php?";
	
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

function tracking_edit(id) {
	$("#"+id).toggle();
}