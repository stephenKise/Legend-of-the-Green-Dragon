<?php

/**
 * Sanitizes global variables $PATH_INFO, $SCRIPT_NAME, and $REQUEST_URI
 * to be used throughout the core.
 * 
 * @return void
 */
function sanitize_uri(): void
{
	global $PATH_INFO, $SCRIPT_NAME, $REQUEST_URI;
	if (isset($PATH_INFO) && $PATH_INFO != '') {
		$SCRIPT_NAME = $PATH_INFO;
		$REQUEST_URI = '';
	}
	if ($REQUEST_URI == '') {
		//necessary for some IIS installations (CGI in particular)
		$httpAllGet = httpallget();
		if (count($httpAllGet) > 0) {
			$REQUEST_URI = "$SCRIPT_NAME?";
			reset($httpAllGet);
			$i = 0;
			foreach ($httpAllGet as $key => $val) {
				if ($i > 0) $REQUEST_URI .= '&';
                $encodedVal = urlencode($val);
				$REQUEST_URI .= "$key=$encodedVal";
				$i++;
			}
		} else {
			$REQUEST_URI = $SCRIPT_NAME;
		}
		$_SERVER['REQUEST_URI'] = $REQUEST_URI;
	}
    // decbin
	$SCRIPT_NAME = substr($SCRIPT_NAME, strrpos($SCRIPT_NAME, '/') + 1);
	if (strpos($REQUEST_URI, '?')) {
		$REQUEST_URI = $SCRIPT_NAME
            . substr($REQUEST_URI, strpos($REQUEST_URI, '?'));
	} else {
		$REQUEST_URI = $SCRIPT_NAME;
	}
}

/**
 * Sets up our environment, registers global variables, and ensures the request
 * path is referenced with multiple global scope variables.
 * 
 * @return void
 */
function php_generic_environment(): void
{
	require_once('lib/register_global.php');
	register_global($_SERVER);
	sanitize_uri();
}
