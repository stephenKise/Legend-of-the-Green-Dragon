<?php
if (!$skipgraveyardtext) {
	output("`)`c`bThe Graveyard`b`c");
	output("Your spirit wanders into a lonely graveyard, overgrown with sickly weeds which seem to grab at your spirit as you float past them.");
	output("Around you are the remains of many broken tombstones, some lying on their faces, some shattered to pieces.");
	output("You can almost hear the wails of the souls trapped within each plot lamenting their fates.`n`n");
	output("In the center of the graveyard is an ancient looking mausoleum which has been worn by the effects of untold years.");
	output("A sinister looking gargoyle adorns the apex of its roof; its eyes seem to follow  you, and its mouth gapes with sharp stone teeth.");
	output("The plaque above the door reads `\$%s`), Overlord of Death`).",$deathoverlord);
	modulehook("graveyard-desc");
}
modulehook("graveyard");
	if ($session['user']['gravefights']) {
	addnav("Look for Something to Torment","graveyard.php?op=search");
}
addnav("Places");
addnav("W?List Warriors","list.php");
addnav("S?Return to the Shades","shades.php");
addnav("M?Enter the Mausoleum","graveyard.php?op=enter");
module_display_events("graveyard", "graveyard.php");
?>