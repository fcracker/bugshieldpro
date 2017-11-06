(function() {
	var a = new Image(); a.src = "images/wait.gif";
	var b = new Image(); b.src = "images/pleasewait.png";
	var c = new Image(); c.src = "images/searching.png";
})();

var WaitMsg = {
	msg: function() {
		var msg = document.createElement("div");
		msg.className = "wait-msg";
		msg.appendChild(document.createElement('div')).className = "wait-icon";
		msg.firstChild.appendChild(document.createElement('div')).className = "wait-circle";
		msg.appendChild(document.createElement('div')).className = "wait-back";
		msg.style.visibility = "hidden";
		
		return msg;
	}(),
	show: function(msg){
		WaitMsg.init();
		//var left = window.innerHeight ? window.innerHeight : document.body.clientHeight
		var innerHeight = window.innerHeight || document.body.parentNode.offsetHeight;
		var innerWidth = window.innerWidth || document.body.parentNode.offsetWidth;
		var scrollTop = document.body.scrollTop || document.body.parentNode.scrollTop;
		var scrollLeft = document.body.scrollLeft || document.body.parentNode.scrollLeft;
		
		WaitMsg.msg.firstChild.style.left = scrollLeft + (innerWidth - WaitMsg.msg.firstChild.offsetWidth) / 2 + "px";
		WaitMsg.msg.firstChild.style.top = scrollTop + (innerHeight - WaitMsg.msg.firstChild.offsetHeight) / 2 + "px";
		WaitMsg.msg.style.height = Math.max(document.body.scrollHeight, document.body.parentNode.scrollHeight) + "px";
		WaitMsg.msg.style.width = Math.max(document.body.scrollWidth, document.body.parentNode.scrollWidth) + "px";
		WaitMsg.msg.childNodes[1].style.height = WaitMsg.msg.style.height;
		WaitMsg.msg.childNodes[1].style.width = WaitMsg.msg.style.width;
		WaitMsg.msg.style.visibility = "visible";
		$('select').css('visibility', 'hidden');
    
    //check for long processing times
    var long_processing = ["US","IN"];
    var the_country = $("select[name=country]").val();
    
    if(($.inArray(the_country,long_processing))>-1) {
    
    $(".wait-icon").append("<div id='long_process' style='display:none;padding:5px;background-color:white;position:absolute;left:1px;width:148px;font-weight:bold;margin-top:50px;'>This may take up to 30 seconds. Please DO NOT press your back button</div>");
    $("#long_process").slideDown("slow");
    
    }
    
    
		//WaitMsg.msg.focus();
	},
	hide: function() {
		WaitMsg.msg.style.visibility = "hidden";
		$('select').css('visibility', 'visible');
	},
	init: function () {
		if(WaitMsg.msg.parentNode != document.body)
			document.body.appendChild(WaitMsg.msg);
	}
}