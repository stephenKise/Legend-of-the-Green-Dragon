<?php
$max = $session['user']['level'] * 5 + 50;
$favortoheal = round(10 * ($max-$session['user']['soulpoints'])/$max);
output("`)`b`cThe Mausoleum`c`b");
output("You enter the mausoleum and find yourself in a cold, stark marble chamber.");
output("The air around you carries the chill of death itself.");
output("From the darkness, two black eyes stare into your soul.");
output("A clammy grasp seems to clutch your mind, and fill it with the words of the Overlord of Death, `\$%s`) himself.`n`n",$deathoverlord);
output("\"`7Your mortal coil has forsaken you.  Now you turn to me.  There are those within this land that have eluded my grasp and possess a life beyond life.  To prove your worth to me and earn my favor, go out and torment their souls.  Should you gain enough of my favor, I will reward you.`)\"");
addnav(array("Question `\$%s`0 about the worth of your soul",$deathoverlord),"graveyard.php?op=question");
addnav(array("Restore Your Soul (%s favor)", $favortoheal),"graveyard.php?op=restore");
addnav("Places");
addnav("S?Land of the Shades","shades.php");
addnav("G?Return to the Graveyard","graveyard.php");
modulehook("mausoleum");
?>