<?php

function redirect(string $location, string $reason = ''): void
{
	global $session, $REQUEST_URI;
	if (!getSession('debug')) $session['message'] = '';
	if (strpos($location, 'badnav.php') === false) {
		$charset = getsetting('charset', 'UTF-8');
		$target = htmlentities($location, ENT_COMPAT, $charset);
		$label = translate_inline('Click here.', 'badnav');
		$session['allowednavs'] = [];
		addnav('', $location);
		$session['output'] = "<a href=\"$target\">$label</a><br /><br />";
		$session['output'] .= translate_inline(
            "If you cannot leave this page, notify the staff via
            <a href='petition.php'>petition</a>.
            Tell them where this happened and what you did. Thanks.",
            "badnav"
        );
	}
	restore_buff_fields();
	if (isset($session['debug'])) {
		$session['debug'] .= "Redirected to $location from $REQUEST_URI. $reason<br>";
	}
	else {
		$session['debug'] = "Redirected to $location from $REQUEST_URI. $reason<br>";
	}
	saveuser();
	@header("Location: $location");
	exit();
}
