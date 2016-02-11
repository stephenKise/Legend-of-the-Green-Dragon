<?php
output("`)`c`bThe Mausoleum`b`c");
$name = httpget('name');
$sql = "SELECT name,level,hauntedby,acctid FROM " . db_prefix("accounts") . " WHERE login='$name'";
$result = db_query($sql);
if (db_num_rows($result)>0){
	$row = db_fetch_assoc($result);
	if ($row['hauntedby']!=""){
		output("That person has already been haunted, please select another target");
	}else{
		$session['user']['deathpower']-=25;
		$roll1 = e_rand(0,$row['level']);
		$roll2 = e_rand(0,$session['user']['level']);
		if ($roll2>$roll1){
			output("You have successfully haunted `7%s`)!", $row['name']);
			$sql = "UPDATE " . db_prefix("accounts") . " SET hauntedby='".addslashes($session['user']['name'])."' WHERE login='$name'";
			db_query($sql);
			addnews("`7%s`) haunted `7%s`)!",$session['user']['name'],$row['name']);
			$subj = array("`)You have been haunted");
			$body = array("`)You have been haunted by `&%s`).",$session['user']['name']);
			require("lib/systemmail.php");
			systemmail($row['acctid'], $subj, $body);
		}else{
			addnews("`7%s`) unsuccessfully haunted `7%s`)!",$session['user']['name'],$row['name']);
			switch (e_rand(0,5)){
			case 0:
				$msg = "Just as you were about to haunt `7%s`) good, they sneezed, and missed it completely.";
				break;
			case 1:
				$msg = "You haunt `7%s`) real good like, but unfortunately they're sleeping and are completely unaware of your presence.";
				break;
			case 2:
				$msg = "You're about to haunt `7%s`), but trip over your ghostly tail and land flat on your, um... face.";
				break;
			case 3:
				$msg = "You go to haunt `7%s`) in their sleep, but they look up at you, and roll over mumbling something about eating sausage just before going to bed.";
				break;
			case 4:
				$msg = "You wake `7%s`) up, who looks at you for a moment before declaring, \"Neat!\" and trying to catch you.";
				break;
			case 5:
				$msg = "You go to scare `7%s`), but catch a glimpse of yourself in the mirror and panic at the sight of a ghost!";
				break;
			}
			output($msg, $row['name']);
		}
	}
}else{
	output("`\$%s`) has lost their concentration on this person, you cannot haunt them now.",$deathoverlord);
}
addnav(array("Question `\$%s`0 about the worth of your soul",$deathoverlord),"graveyard.php?op=question");
$max = $session['user']['level'] * 5 + 50;
$favortoheal = round(10 * ($max-$session['user']['soulpoints'])/$max);
addnav(array("Restore Your Soul (%s favor)", $favortoheal),"graveyard.php?op=restore");
addnav("Places");
addnav("S?Land of the Shades","shades.php");
addnav("G?The Graveyard","graveyard.php");
addnav("M?Return to the Mausoleum","graveyard.php?op=enter");
?>