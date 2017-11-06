function MM_openBrWindow(theURL,winName,features) {
	var mywin = window.open(theURL,winName,features);
}
	
function gotoTop() {
	window.scrollTo(0, 0);
}

function renderExitConsoles() {
	html = '';
	
	for(i = downsellstep + 1; i < 4; i++) {
		html += '<div class="outerwrap" id="exitconsole' + i + '" style="display: none;">';
		html += '<div id="innerwrap" style="overflow:visible">'; 
		html += '<div id="sidebar_1" style="overflow:visible;float:left;">';
		html += '<div id="badge_l"><img style="position: relative;" id="badgeimg" src="images/salebadge' + i + '.png" alt="NOW ONLY $' + prices[i] + '. Super Limited Time Offer." width="190" height="179"/></div>';
		html += '</div> <!-- sidebar_l -->';
		html += '<div id="content">';
		html += '<p style="padding: 0; margin-bottom: 20px;">';
		html += '<img width="364" height="250" id="waitimg" src="images/wait' + i + '.png" alt="WAIT! Can You Believe This? NOW ONLY $' + prices[i] + '."/>';
		html += '</p>';
		html += '<p style="padding: 0; margin-bottom: 30px;">';
		html += '<a onclick="turnOffExitConsole();" title="Order Now!"><img src="images/ordernow.png" width="304" height="54" /></a><br />';
		html += '<a onclick="turnOnExitConsole();" title="No Thanks">No Thanks</a>';
		html += '</p>';
		html += '</div> <!-- content -->';
		html += '<div id="sidebar_r" style="overflow:hidden; width:250px !important">';
		html += '<h2>100% Money-Back Guarantee</h2>';
		html += '<img src="images/guarantee.png" width="258" height="237"  title="RISK FREE 100% Money Back Guarantee" />';
		html += '</div> <!-- sidebar_r -->';
		html += '<div class="relax"></div>';
		html += '</div> <!-- innerwrap -->';
		html += '</div> <!-- outerwrap -->';
	}
	
	$('#maincontent').before(html);
}

function hideAllExitConsoles() {
	for(var i = 0; i < 4; i++) {
		$("#exitconsole" + i).css("display", "none");
	}
}

function turnOnExitConsole() {
	if(downsellstep == 3) {
		turnOffExitConsole();
		return;
	}
	
	downsellstep++;
	hideAllExitConsoles();
	
	$("#maincontent").hide();
	$("#exitconsole" + downsellstep).css("display", "block");
	
	return "*** Press CANCEL now and get your online training package at the lowest price ever! ***";
}

function turnOffExitConsole() {
	hideAllExitConsoles();
	$("#maincontent").css("display", "block");
	document.forms[0].price.value = tprices[downsellstep];
	if(document.forms[0].id == "creditform") {
		document.getElementById("img_seal").src = "images/97_seal" + downsellstep + ".png";
		try {
			document.getElementById("img_add").src = "images/additional" + downsellstep + ".png";
			document.getElementById("youpay").innerHTML = "$" + prices[downsellstep];
			document.getElementById("price_value").innerHTML = "$" + prices[downsellstep];
		} catch(e) {}
	} else {
		window.onbeforeunload = null;
		document.forms[0].submit();
	}
}


function checkEmail(email) {
	var regExp = /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i;
	
	return regExp.test(email);
}