<?php
// translator ready
// addnews ready
// mail ready
function redirect($location,$reason=false){
	global $session,$REQUEST_URI;
	// This function is deliberately not localized.  It is meant as error
	// handling.
	if (strpos($location,"badnav.php")===false) {
		//deliberately html in translations so admins can personalize this, also in once scheme
		$session['allowednavs']=array();
		addnav("",$location);
		$session['output']=
			"<a href=\"".HTMLEntities($location, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">".translate_inline("Click here.","badnav")."</a>";
		$session['output'].=translate_inline("<br><br>If you cannot leave this page, notify the staff via <a href='petition.php'>petition</a> and tell them where this happened and what you did. Thanks.","badnav");
	}
	restore_buff_fields();
	$session['debug'].="Redirected to $location from $REQUEST_URI.  $reason<br>";
	saveuser();
	@header("Location: $location");
	//echo "<html><head><meta http-equiv='refresh' content='0;url=$location'></head></html>";
	//echo "<a href='$location'>$location</a><br><br>";
	//echo $location;
	//echo $session['debug'];
	exit();
}
?>
