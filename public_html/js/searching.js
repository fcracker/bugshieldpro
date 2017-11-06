(function() {
	var a = new Image(); a.src = "images/wait.gif";
	var b = new Image(); b.src = "images/pleasewait.png";
	var c = new Image(); c.src = "images/searching.png";
})();

var SearchMsg = {
	msg: function() {
		var msg = document.createElement("div");
		msg.className = "wait-msg";
		msg.appendChild(document.createElement('div')).className = "search-icon";
		msg.firstChild.appendChild(document.createElement('div')).className = "wait-circle";
		msg.appendChild(document.createElement('div')).className = "wait-back";
		msg.style.visibility = "hidden";
		
		return msg;
	}(),
	show: function(msg){
		SearchMsg.init();
		//var left = window.innerHeight ? window.innerHeight : document.body.clientHeight
		var innerHeight = window.innerHeight || document.body.parentNode.offsetHeight;
		var innerWidth = window.innerWidth || document.body.parentNode.offsetWidth;
		var scrollTop = document.body.scrollTop || document.body.parentNode.scrollTop;
		var scrollLeft = document.body.scrollLeft || document.body.parentNode.scrollLeft;
		
		SearchMsg.msg.firstChild.style.left = scrollLeft + (innerWidth - SearchMsg.msg.firstChild.offsetWidth) / 2 + "px";
		SearchMsg.msg.firstChild.style.top = scrollTop + (innerHeight - SearchMsg.msg.firstChild.offsetHeight) / 2 + "px";
		SearchMsg.msg.style.height = Math.max(document.body.scrollHeight, document.body.parentNode.scrollHeight) + "px";
		SearchMsg.msg.style.width = Math.max(document.body.scrollWidth, document.body.parentNode.scrollWidth) + "px";
		SearchMsg.msg.childNodes[1].style.height = SearchMsg.msg.style.height;
		SearchMsg.msg.childNodes[1].style.width = SearchMsg.msg.style.width;
		SearchMsg.msg.style.visibility = "visible";
		$('select').css('visibility', 'hidden');
		//SearchMsg.msg.focus();
	},
	hide: function() {
		SearchMsg.msg.style.visibility = "hidden";
		$('select').css('visibility', 'visible');
	},
	init: function () {
		if(SearchMsg.msg.parentNode != document.body)
			document.body.appendChild(SearchMsg.msg);
	}
}