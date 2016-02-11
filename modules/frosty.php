<?php
// translator ready
// addnews ready
// mail ready
function frosty_getmoduleinfo(){
	$info = array(
		"name"=>"Frosty the Snowman",
		"version"=>"1.0",
		"author"=>"Talisman",
		"category"=>"Village Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Frosty the Snowman Settings, title",
			"rawchance"=>"Raw chance of encountering Frosty,range,5,100,1|50",
			"frostyloc"=>"Where does the Frosty appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
		"prefs"=>array(
			"Frosty the Snowman User Prefs, title",
			"seentoday"=>"Has the player rebuilt Frosty today,bool|0",
		)
	);
	return $info;
}

function frosty_test() {
	global $session;
	$city = get_module_setting("frostyloc", "frosty");
	if ($city != $session['user']['location']) return 0;
	return get_module_setting("rawchance", "frosty");
}

function frosty_install(){
	module_addeventhook("village", "require_once(\"modules/frosty.php\"); return frosty_test();");
	module_addhook("newday");
}

function frosty_uninstall(){
	return true;
}

function frosty_dohook($hookname,$args){
	switch($hookname){
	case "newday":
		set_module_pref("seentoday",0);
		break;
	}
	return $args;
}

function frosty_runevent($type){

	require_once("lib/http.php");
	global $session;
	$session['user']['specialinc'] = "module:frosty";
	$from="village.php?";
	$op=httpget('op');


	if ($op ==""){
		if (get_module_pref("seentoday")==1) {
			output("`7You notice a young girl dancing around...with a `&Snowman`7!");
			output("`7You recognize them as the ones you helped earlier, and shake your head as the `&Snowman`7 winks at you once again.`n`n");
			$session['user']['specialinc'] = "";
		} else{
			addnav("What will you do?");
			addnav("Talk to the kid",$from."op=talk");
			addnav("Ignore the kid",$from."op=ignore");
			output("`n`7While walking through the square, you take note of a young girl standing beside a pile of `&snow`7.");
			output("Looking closer, you see several objects in the snow...a `Qcarrot`7, a couple chunks of `)coal`7 and a tophat.");
			output("The child seems to be sobbing quietly.`n`n");
			output("`@Do you carry on your way, or enquire as to the cause of her upset?`n`n");
		}
	} elseif ($op == "talk") {
		output("`n`n`7You ask the girl why she is so sad.");
		output("She replies, \"`3My snowman melted.  He was my best friend, and now he's melted and gone and I don't know if I can put him back together again...and he's all melted.`7\"`n`n");
		output("`7She looks up at you, tears welling in her eyes.  \"`3Please...could you help me put him back together?  He's my best friend.`7\"");

		if ($session['user']['turns'] > 0) {
			output("`n`n`@Will you use a turn to help her?");
			addnav("Rebuild the Snowman",$from."op=help");
			addnav("Don't help",$from."op=leave");
		} else {
			output("`n`nYou feel very sorry for the poor girl, but you are just too tired to help.`n");
			output("You spy an aquaintance of yours across the way, however, and point him out to the girl.");
			output("`n`nYou then stand and watch while your friend and the girl rebuild the snowman.");
			$session['user']['specialinc'] = "";
		}

	} elseif ($op == "help") {
		output("`n`n`7You put aside your weapons and start rolling a new snowball.");
		output("The girl pitches in, and soon three snowballs are stacked up.");
		output("You find the `Qcarrot`7 for the nose, two lumps of `)coal`7 for eyes and some buttons which form the mouth.");
		output("Two sticks laying nearby quickly serve to become arms, and you donate your own scarf to decorate the snowman.`n`n");
		output("The child finally places the tophat on it's head.");
		output("\"`3Oh thank you so much! Now I can play with Frosty again!`7\"`n`n");
		set_module_pref("seentoday",1);
		$session['user']['specialinc'] = "";
		if ($session['user']['turns'] > 0) $session['user']['turns']--;

		switch (e_rand(1, 3)) {
		case 1:
			output("`@The child's joy warms your heart and rejuvenates your soul.");
			output("`^You gain two forest fights!");
			$session['user']['turns']+=2;
			break;
		case 2:
			output("`@The child holds out a solitary gem.`n");
			output("`7\"`3I was going to use this for an eye, but only had the one.  You can have it!`7\"`n`n");
			output("`^You gain a `%gem`^!");
			debuglog("got a gem helping frosty");
			$session['user']['gems']++;
			break;
		case 3:
			$fgold=(20*$session['user']['level']);
			output("`@As you turn to depart, you notice the gleam of gold poking out from some snow.`n`n");
			output("`^You find %s gold.", $fgold);
			debuglog("found $fgold gold helping frosty");
			$session['user']['gold']+=$fgold;
			break;
		}
		output("`n`n`7As you walk away, you glance over your shoulder.");
		output("You could almost swear the snowman winked at you...but no, that's not possible.");
		output("Is it?");
	} elseif ($op == "leave") {
		output("`n`n`6\"I'm sorry, kid, I'm too busy to play with you\"`7, you say, then turn to walk away as the girl sobs loudly.");
		switch (e_rand(1, 4)) {
		case 1:
			$cur = $session['user']['hitpoints'];
			$lhitpoints=round($cur*.3);
			$session['user']['hitpoints']=($cur-$lhitpoints);
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints'] = 1;
			output("`n`nYou avert your gaze so as to not look at the poor girl and her pile of snow.");
			output("Too bad you also don't see that runaway bobsled careening towards you.`n`n");
			$loss = $cur - $session['user']['hitpoints'];
			output("`^The bobsled hits you!`n");
			output("You `\$lose %s hitpoints.", $loss);
			break;
		case 2:
			output("`n`@Your attempts to ignore the cries fail, and your spirit flags.`n`n");
			if ($session['user']['turns'] > 0){
				output("`^You `\$lose`^ a forest fight.");
				$session['user']['turns']--;
			}else{
				output("`^You `\$lose`^ some charm.");
				$session['user']['charm']--;
			}
			break;
		case 3:
			output("`n`n`@You walk away, oblivious to many things.");
			output("If she wasn't so busy crying, the girl could have told you about the hole in your gold purse.");
			output("`n`^You've lost some of your gold!`n");
			$loss = round($session['user']['gold'] * .4);
			$session['user']['gold'] -= $loss;
			if ($session['user']['gold'] < 0)
				$session['user']['gold'] = 0;
			output("Counting your gold later, you discover that you lost %s gold.", $loss);
			debuglog("lost $loss gold ignoring frosty");
			break;
		case 4:
			output("`n`nThe girl's crying fades quickly as you stride away.");
			break;
		}
		$session['user']['specialinc'] = "";
	} elseif ($op == "ignore") {
		output("`n`n`7You choose not to find out why the child is so sad, and continue on your way.`n`n");
		output("`@What a Scrooge you are!`n`n");
		switch (e_rand(1, 4)) {
		case 1:
			$cur = $session['user']['hitpoints'];
			$lhitpoints=round($cur*.1);
			$session['user']['hitpoints']=($cur-$lhitpoints);
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints'] = 1;
			output("`7You're so busy minding your own business that you didn't notice the black ice on the sidewalk.");
			output("You slip, falling flat on your back!");
			$loss = $cur - $session['user']['hitpoints'];
			output("You `\$lose %s hitpoints.", $loss);
			break;
		default:
			break;
		}
		$session['user']['specialinc'] = "";
	}
}

function frosty_run(){
}
?>
