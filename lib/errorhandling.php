<?php
// addnews ready
// translator ready
// mail ready
// Set error reporting to all but notice (for now)
if (defined('E_DEPRECATED')) {
	error_reporting (E_ALL ^ (E_NOTICE | E_DEPRECATED));
} else {
	error_reporting (E_ALL ^ E_NOTICE);
}
#error_reporting (E_ALL);

function set_magic_quotes(&$vars) {
	if (is_array($vars)) {
		foreach ($vars as $key => $val) {
			set_magic_quotes($vars[$key]);
		}
	}else{
		$vars = addslashes($vars ?? '');
	}
}


// The codebase expects request data to arrive pre-escaped the way
// magic_quotes_gpc used to deliver it.  Magic quotes were removed in PHP 5.4
// and the get_magic_quotes_* functions in PHP 8, so emulate the escaping
// whenever it isn't already provided by the engine.
if (!function_exists("get_magic_quotes_gpc") || !get_magic_quotes_gpc()) {
	set_magic_quotes($_GET);
	set_magic_quotes($_POST);
	set_magic_quotes($_COOKIE);
}
