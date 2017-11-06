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
		WaitMsg.msg.firstChild.style.left = document.body.scrollLeft + (document.body.parentNode.offsetWidth - WaitMsg.msg.firstChild.offsetWidth) / 2 + "px";
		WaitMsg.msg.firstChild.style.top = document.body.scrollTop + (document.body.parentNode.offsetHeight - WaitMsg.msg.firstChild.offsetHeight) / 2 + "px";
		WaitMsg.msg.style.visibility = "visible";
		WaitMsg.msg.focus();
	},
	hide: function() {
		WaitMsg.msg.style.visibility = "hidden";
	},
	init: function () {
		if(WaitMsg.msg.parentNode != document.body)
			document.body.appendChild(WaitMsg.msg);
	}
}