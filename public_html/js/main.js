var baseUrl = 'http://gaia.com';
var state_changed = 0;var $form;
//console.log('JS loaded ');
$(document).ready(function(){
	
	$("#quantity").change(update_checkout_prices);
  
  
});

function update_checkout_prices() {
	
	var qty = $("#quantity").val();
	
	$.post(
		baseUrl +"/price_computer.php",
		{qty:qty},
		function(json) {				
				$("#subt").text(json.subtotal);
				$("#act").text(json.total);
			
			},
		"json"
	);
	
}






	


	function downCounter(totalTime) {

		var timer2 = setInterval(function() {

			if (totalTime == 0) {

				clearInterval(timer2);
				//reset it
				downCounter(20 * 60);

			} else {

				var m = Math.floor(totalTime / 60);

				var s = totalTime - m * 60;	

				m = m.toString();

				s = s.toString();

				if (m.length < 2) m = '0' + m;


				if (s.length < 2) s = '0' + s;

				var timestr = m + s;


				$('#count_a').html(timestr.charAt(0));

				$('#count_b').html(timestr.charAt(1));

				$('#count_c').html(timestr.charAt(2));

				$('#count_d').html(timestr.charAt(3));

				totalTime--;

			}


		}, 100);


	}
	
	function payment__check(checkterms) {
	
		if(checkterms==true) {
		//check if the user agreed
		if(!$("#agree:checked").length) {
		alert("You must agree with the Terms of Service and the Privacy Policy before continuing.");
		return false;
		}
		}
		
		errorMessages = [];
	
		//check the rest
		var cardnumber = removeDS($("input[name=cardnumber]").val());
		var cardtype = "";
		
		if(/^4/.test(cardnumber)) {					//Visa
			if (/^4\d{15}$/.test(cardnumber)) {
				cardtype = "Visa";
				} else {
				errorMessages.push("Card Number invalid");
			}
			} else if (/^6011/.test(cardnumber)) {		//Discover
			if(/^6011\d{12}$/.test(cardnumber)){
				cardtype = "Discover";
				} else {
				errorMessages.push("Card Number invalid");
			}
			} else if (/^5[1-5]/.test(cardnumber)) {	//Master
			if(/^5[1-5]\d{14}$/.test(cardnumber)){
				cardtype = "Master";
				} else {
				errorMessages.push("Card Number invalid");
			}
			} else if (/^3(7|4)/.test(cardnumber)) {	//American Express
			if(/^3(7|4)\d{13}$/.test(cardnumber)){
				cardtype = "Amex";
				} else {
				errorMessages.push("Card Number invalid");
			}
			} else {
			errorMessages.push("Card Number invalid");
		}
		
		//check the cvv
		if($("input[name=cvvcode]").val().length<3 || $("input[name=cvvcode]").val().length>4) {
			errorMessages.push("Security Code invalid");
		}
		
		if(errorMessages.length) {
			alert("Errors:\n " + errorMessages.join("\n"));
			return false;
		}
		
		return true;
	
	}
	
	function removeDS(val) {
	return val.replace(/[\- ]/g, '');
}