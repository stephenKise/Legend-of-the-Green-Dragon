<?php
// translator ready
// addnews ready
// mail ready
function villagenav($extra=false)
{
	global $session;
	$loc = $session['user']['location'];
	if ($extra === false) $extra="";
	$args = modulehook("villagenav");
	if (array_key_exists('handled', $args) && $args['handled']) return;
	tlschema("nav");
	if ($session['user']['alive']) {
		addnav(array("V?Return to %s", $loc), "village.php$extra");
	} else {
		// user is dead
		addnav("S?Return to the Shades","shades.php");
	}
	tlschema();
}
?>
