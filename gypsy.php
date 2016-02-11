<?php
// addnews ready
// translator ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

tlschema("gypsy");

addcommentary();

$cost = $session['user']['level']*20;
$op = httpget('op');

if ($op=="pay"){
	if ($session['user']['gold']>=$cost){ // Gunnar Kreitz
		$session['user']['gold']-=$cost;
		debuglog("spent $cost gold to speak to the dead");
		redirect("gypsy.php?op=talk");
	}else{
		page_header("Gypsy Seer's tent");
		villagenav();
		output("`5You offer the old gypsy woman your `^%s`5 gold for your gen-u-wine say-ance, however she informs you that the dead may be dead, but they ain't cheap.", $session['user']['gold']);
	}
}elseif ($op=="talk"){
	page_header("In a deep trance, you talk with the shades");
	commentdisplay("`5While in a deep trance, you are able to talk with the dead:`n", "shade","Project",25,"projects");
	addnav("Snap out of your trance","gypsy.php");
}else{
	checkday();
	page_header("Gypsy Seer's tent");
	output("`5You duck into a gypsy tent like many you have seen throughout the realm.");
	output("All of them promise to let you talk with the deceased, and most of them surprisingly seem to work.");
	output("There are also rumors that the gypsy have the power to speak over distances other than just those of the afterlife.");
	output("In typical gypsy style, the old woman sitting behind a somewhat smudgy crystal ball informs you that the dead only speak with the paying.");
	output("\"`!For you, %s, the price is a trifling `^%s`! gold.`5\", she rasps.", translate_inline($session['user']['sex']?"my pretty":"my handsome"), $cost);
	addnav("Seance");
	addnav(array("Pay to talk to the dead (%s gold)", $cost),"gypsy.php?op=pay");
	if ($session['user']['superuser'] & SU_EDIT_COMMENTS)
		addnav("Superuser Entry","gypsy.php?op=talk");
	addnav("Other");
	addnav("Forget it","village.php");
	modulehook("gypsy");
}
page_footer();
?>