<?php
// addnews ready
// translator ready
// mail ready
require_once("lib/modules.php");

function holidayize($text,$type='unknown'){
	global $session;
	if (!isset($session['user']['prefs']['ihavenocheer']))
		$session['user']['prefs']['ihavenocheer'] = 0;
	if ($session['user']['prefs']['ihavenocheer']) {
		return $text;
	}

	$args = array('text'=>$text,'type'=>$type);
	$args = modulehook("holiday", $args);
	$text = $args['text'];

	return $text;
}

?>
