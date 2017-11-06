$(document).ready(function(){

$("#first_name").focus();

$("#country").change(getStateList).trigger("change");


$("#check_email").click(function(ex){
ex.preventDefault();
var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
var mail = $("#email").val();
if (filter.test(mail)) {
$("#check_email_result").text("Checking...");
$.ajax({
		type: "POST",
		url: "ajax_echeck.php",
		data:{action:'check_mail',email:mail},
		dataType: "text",
		success: function(data) {
			$("#check_email_result").text(data);
			
			$("#username").val(mail);
		}
	});
} else {
	$("#check_email_result").text("This is not a valid email address!");
}	

});


$("#generate_password").click(function(ex){
ex.preventDefault();
$("#password").attr("disabled","true").val("Generating...");
$.ajax({
		type: "POST",
		url: "ajax_echeck.php",
		data:{action:'generate_password'},
		dataType: "text",
		success: function(data) {
			$("#password").val(data).removeAttr("disabled");
		}
	});
}

);


$("#echeck_form").submit(function(){

$("#submit_button").val("Please Wait...");
$("#submit_result").html("Loading...");

var s = $(this).serialize();

$.ajax({
		type: "POST",
		url: "ajax_echeck.php",
		data:"action=payment&" + s,
		dataType: "json",
		success: function(data) {
			
			
			$("#submit_result").html(data.message);
			
			if(data.result=="OK") {
				//go on 
			}			
			
			$("#submit_button").val("Submit");
		}
	});

return false;
});

$("#switch_button").click(function(){	
	$("#echeck_form").attr("action","invoice-billing.php").unbind("submit").submit();	
});

if($("#email").val().length) {
	$("#username").val($("#email").val());
}

$("#email").blur(function(){
	$("#username").val($("#email").val());
});


});

function getStateList() {
	var states = $("#state");
	var country = $("#country").val();
	var selected_state = states.attr("rel");
	
	states.html("<option style=\'background-color: red; color: white;\'>[Loading...]</option>");
	$.ajax({
		url: "../request.processor.php?action=getstate&country=" + country,
		dataType: "text",
		success: function(data) {
			states.html(data);
			
			$("#state option").each(function(){
				if($(this).val()==selected_state) {
					$(this).attr("selected","selected");
				}
			});
			
		}
	});
}