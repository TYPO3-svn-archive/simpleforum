var _contextMenu = $('cm');
var _mouseOverCM = false;

// JavaScript Document
function txSimpleForumAdminMenu(event, id) {
	txSimpleForumAdminMenuClose;
	_contextMenu = $('cm'+id);
	_mouseOverCM = false;
	_contextMenu.onmouseover = function() { _mouseOverCM = true; };
	_contextMenu.onmouseout = function() { _mouseOverCM = false; };
	document.body.onmousedown = txSimpleForumAdminMenuClose;

	// IE is evil and doesn't pass the event object 
	//if (event == null)
	//	event = window.event; 
	
	// document.body.scrollTop does not work in IE 
	//var scrollTop = document.body.scrollTop ? document.body.scrollTop : document.documentElement.scrollTop; 
	//var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft : document.documentElement.scrollLeft; 
	
	// hide the menu first to avoid an "up-then-over" visual effect 
	//_contextMenu.style.display = 'none'; 
	//_contextMenu.style.left = event.clientX + scrollLeft + 'px'; 
	//_contextMenu.style.top = event.clientY + scrollTop + 'px'; 
	_contextMenu.style.display = 'block'; 
	return false;
}

function txSimpleForumAdminMenuClose() {
	if (!_mouseOverCM) {
		_contextMenu.style.display = 'none'; 
	}
}