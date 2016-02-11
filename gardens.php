<?php
// addnews ready
// translator ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/villagenav.php");
require_once("lib/events.php");
require_once("lib/http.php");

tlschema("gardens");

page_header("The Gardens");

addcommentary();
$skipgardendesc = handle_event("gardens");
$op = httpget('op');
$com = httpget('comscroll');
$refresh = httpget("refresh");
$commenting = httpget("commenting");
$comment = httppost('insertcommentary');
// Don't give people a chance at a special event if they are just browsing
// the commentary (or talking) or dealing with any of the hooks in the village.
if (!$op && $com=="" && !$comment && !$refresh && !$commenting) {
	if (module_events("gardens", getsetting("gardenchance", 0)) != 0) {
		if (checknavs()) {
			page_footer();
		} else {
			// Reset the special for good.
			$session['user']['specialinc'] = "";
			$session['user']['specialmisc'] = "";
			$skipgardendesc=true;
			$op = "";
			httpset("op", "");
		}
	}
}
if (!$skipgardendesc) {
	checkday();

	output("`b`c`2The Gardens`0`c`b");

	output("`n`nYou walk through a gate and on to one of the many winding paths that makes its way through the well-tended gardens.");
	output("From the flowerbeds that bloom even in darkest winter, to the hedges whose shadows promise forbidden secrets, these gardens provide a refuge for those seeking out the Green Dragon; a place where they can forget their troubles for a while and just relax.`n`n");
	output("One of the fairies buzzing about the garden flies up to remind you that the garden is a place for roleplaying and peaceful conversation, and to confine out-of-character comments to the other areas of the game.`n`n");
}

villagenav();
modulehook("gardens", array());

commentdisplay("", "gardens","Whisper here",30,"whispers");

module_display_events("gardens", "gardens.php");
page_footer();
?>