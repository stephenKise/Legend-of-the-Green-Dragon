<?php
	page_header("Clan Halls");
	addnav("Clan Options");
	output("`b`c`&Clan Halls`c`b");
	output("You stroll off to the side where there are some plush leather chairs, and take a seat.");
	output("There are several other warriors sitting here talking amongst themselves.");
	output("Some Ye Olde Muzak is coming from a fake rock sitting at the base of a potted bush.`n`n");
	commentdisplay("", "waiting","Speak",25);
	if ($session['user']['clanrank']==CLAN_APPLICANT) {
		addnav("Return to the Lobby","clan.php");
	} else {
		addnav("Return to your Clan Rooms","clan.php");
	}
?>