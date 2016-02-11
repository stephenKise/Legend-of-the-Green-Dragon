<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/sanitize.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");
require_once("lib/names.php");

tlschema("lodge");

addcommentary();

$op = httpget('op');
if ($op == "") checkday();

$pointsavailable =
	$session['user']['donation']-$session['user']['donationspent'];
$entry = ($session['user']['donation'] > 0) || ($session['user']['superuser'] & SU_EDIT_COMMENTS);
if ($pointsavailable < 0) $pointsavailable = 0; // something weird.

page_header("Hunter's Lodge");
addnav("Referrals", "referral.php");
if ($op != "" && $entry)
	addnav("L?Back to the Lodge", "lodge.php");
addnav("Describe Points","lodge.php?op=points");
villagenav();


if ($op==""){
	output("`b`c`!The Hunter's Lodge`0`c`b");
	output("`7You follow a narrow path away from the stables and come across a rustic Hunter's Lodge.");
	output("A guard stops you at the door and asks to see your membership card.`n`n");

	if ($entry){
		output("Upon showing it to him, he says, `3\"Very good %s, welcome to the J. C. Petersen Hunting Lodge.", translate_inline($session['user']['sex']?"ma'am":"sir"));
		output("You have earned `^%s`3 points and have `^%s`3 points available to spend,\"`7 and admits you in.`n`n", $session['user']['donation'], $pointsavailable);
		output("You enter a room dominated by a large fireplace at the far end.");
		output("The wood-panelled walls are covered with weapons, shields, and mounted hunting trophies, including the heads of several dragons that seem to move in the flickering light.`n`n");
		output("Many high-backed leather chairs fill the room.");
		output("In the chair closest to the fire sits J. C. Petersen, reading a heavy tome entitled \"Alchemy Today.\"`n`n");
		output("As you approach, a large hunting dog at his feet raises her head and looks at you.");
		output("Sensing that you belong, she lays down and goes back to sleep.`n`n");
		commentdisplay("Nearby some other rugged hunters talk:`n", "hunterlodge","Talk quietly",25);
		addnav("Use Points");
		modulehook("lodge");
	}else{
		$iname = getsetting("innname", LOCATION_INN);
		output("You pull out your Frequent Boozer Card from %s, with 9 out of the 10 slots punched out with a small profile of %s`0's Head.`n`n", $iname,getsetting('barkeep','`tCedrik'));
		output("The guard glances at it, advises you not to drink so much, and directs you down the path.");
	}
}else if ($op=="points"){
	output("`b`3Points:`b`n`n");
	$points_messages = modulehook(
		"donator_point_messages",
		array(
			'messages'=>array(
				'default'=>tl("`7For each $1 donated, the account which makes the donation will receive 100 contributor points in the game.")
			)
		)
	);
	foreach($points_messages['messages'] as $id => $message){
		output_notl($message, true);
	}
	output("\"`&But what are points,`7\" you ask?");
	output("Points can be redeemed for various advantages in the game.");
	output("You'll find access to these advantages in the Hunter's Lodge.");
	output("As time goes on, more advantages will likely be added, which can be purchased when they are made available.`n`n");
	output("Donating even one dollar will gain you a membership card to the Hunter's Lodge, an area reserved exclusively for contributors.");
	output("Donations are accepted in whole dollar increments only.`n`n");
	output("\"`&But I don't have access to a PayPal account, or I otherwise can't donate to your very wonderful project!`7\"`n");
           // yes, "referer" is misspelt here, but the game setting was also misspelt
	if (getsetting("refereraward", 25)) {
		output("Well, there is another way that you can obtain points: by referring other people to our site!");
		output("You'll get %s points for each person whom you've referred who makes it to level %s.", getsetting("refereraward", 25), getsetting("referminlevel", 4));
		output("Even one person making it to level %s will gain you access to the Hunter's Lodge.`n`n", getsetting("referminlevel", 4));
	}
	output("You can also gain contributor points for contributing in other ways that the administration may specify.");
	output("So, don't despair if you cannot send cash, there will always be non-cash ways of gaining contributor points.`n`n");
	output("`b`3Purchases that are currently available:`0`b`n");
	$args = modulehook("pointsdesc", array("format"=>"`#&#149;`7 %s`n", "count"=>0));
	if ($args['count'] == 0) {
		output("`#&#149;`7None -- Please talk to your admin about creating some.`n", true);
	}
}

page_footer();
?>