<?php
$max = $session['user']['level'] * 5 + 50;
$favortoheal = round(10 * ($max-$session['user']['soulpoints'])/$max);
if ($session['user']['deathpower']>=100) {
	output("`\$%s`) speaks, \"`7You have impressed me indeed.  I shall grant you the ability to visit your foes in the mortal world.`)\"",$deathoverlord);
	addnav(array("%s Favors",sanitize($deathoverlord)));
	addnav("H?Haunt a foe (25 favor)","graveyard.php?op=haunt");
	addnav("e?Resurrection (100 favor)","graveyard.php?op=resurrection");
	addnav("Other");
}elseif ($session['user']['deathpower'] >= 25){
	output("`\$%s`) speaks, \"`7I am moderately impressed with your efforts.  A minor favor I now grant to you, but continue my work, and I may yet have more power to bestow.`)\"",$deathoverlord);
	addnav(array("%s Favors",sanitize($deathoverlord)));
	addnav("H?Haunt a foe (25 favor)","graveyard.php?op=haunt");
	addnav("Other");
}else{
	output("`\$%s`) speaks, \"`7I am not yet impressed with your efforts.  Continue my work, and we may speak further.`)\"",$deathoverlord);
}
output("`n`nYou have `6%s`) favor with `\$%s`).", $session['user']['deathpower'],$deathoverlord);
addnav(array("Restore Your Soul (%s favor)",$favortoheal),"graveyard.php?op=restore");
addnav("Places");
addnav("S?Land of the Shades","shades.php");
addnav("G?Return to the Graveyard","graveyard.php");
modulehook("ramiusfavors");
?>