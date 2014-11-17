

function showLogin(url) {
	TINY.box.show({iframe:url,boxid:'frameless',width:420,height:270,fixed:false,maskid:'bluemask',maskopacity:40});
	return false;
}

function showloginbox() {
	showLogin(G_AJAXLOGIN_URL);
	return false;
}
