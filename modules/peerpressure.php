<?php
// translator ready
// addnews ready
// mail ready

function peerpressure_getmoduleinfo(){
	$info = array(
		"name"=>"Peer Pressure",
		"version"=>"1.1",
		"author"=>"`\$Red Yates",
		"category"=>"Village Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Peer Pressure Settings,title",
			"daysfree"=>"Days until player is a potential victim,int|2",
		),
		"prefs"=>array(
			"Peer Pressure Preferences,title",
			"dayspast"=>"Days since user hit level 15,int|0",
		),
	);
	return $info;
}

function peerpressure_victimtest(){
	global $session;
	if ($session['user']['level'] != 15) return 0;
	if (get_module_pref("dayspast", "peerpressure") <
			get_module_setting("daysfree", "peerpressure")) return 0;
	return 100;
}


function peerpressure_install(){
	module_addeventhook("village", "require_once(\"modules/peerpressure.php\"); return peerpressure_victimtest();");
	module_addhook("newday");
	module_addhook("battle-defeat");
	return true;
}

function peerpressure_uninstall(){
	return true;
}

function peerpressure_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "newday":
		$dayspast=get_module_pref("dayspast");
		$dayspast++;
		set_module_pref("dayspast",
				($session['user']['level']==15?$dayspast:0));
		break;
	case "battle-defeat":
		// If they have a special inc set for the dragon and they just
		// lost, reset it so they don't keep coming back here!
		global $options;
		if ($session['user']['specialinc'] == "module:peerpressure" && $options['type'] == "dragon") {
			$session['user']['specialinc'] = "";
		}
		break;
	}
	return $args;
}

function peerpressure_runevent($type)
{
	global $session;
	$session['user']['specialinc']="module:peerpressure";
	// For translation reasons, you cannot really substitute in his/her
	// since the gender can change other things
	if ($session['user']['sex']) {
		addnews("`&%s`7 heroically decided to seek out `@The Green Dragon`7 with cheers of encouragement from her peers ringing in her ears.",$session['user']['name']);
	} else {
		addnews("`&%s`7 heroically decided to seek out `@The Green Dragon`7 with cheers of encouragement from his peers ringing in his ears.",$session['user']['name']);
	}
	output("`2Wandering the village, going about your business, you are suddenly surrounded by a group of villagers.");
	output("They wonder why such an experienced adventurer as yourself hasn't slain a dragon yet.");
	output("You mutter some embarrassed excuses but they aren't listening.");
	output("They crowd around you closer, and lift you up on their shoulders.");
	$isforest = 0;
	$vloc = modulehook('validforestloc', array());
	foreach($vloc as $i=>$l) {
		if ($session['user']['location'] == $l) {
			$isforest = 1;
			break;
		}
	}
	if ($isforest || count($vloc)==0) {
		output("`n`nCheering your name the whole way, they carry you into the forest, and right to the mouth of a cave outside the town!`n`n");
	} else {
		$key = array_rand($vloc);
		output("`n`nCheering your name the whole way, they carry you far into the forest, and right to the mouth of a cave outside the town of %s!`n`n", $key);
		$session['user']['location'] = $key;
	}
	output("Still cheering your name, they put you down and eagerly wait for you to enter and slay that dragon.`n`n");
	output("You know that you'd never live it down if you tried to back out now.");
	output("Swallowing your fear as best you can, you enter the cave.");
	if (is_module_active("dragonplace")) {
		addnav("Enter the cave", "runmodule.php?module=dragonplace&op=cave");
	} else {
		addnav("Enter the cave", "dragon.php?nointro=1");
	}
	$session['user']['specialinc']="";
	checkday(); //increment buffs, newday buffs, and heal... and probably throw people off in general
	$session['user']['specialinc']="module:peerpressure";
	apply_buff('peerpressure', array(
		"name"=>"`2Heroic Valor",
		"rounds"=>20,
		"atkmod"=>(1+(get_module_pref("dayspast")/100)),
		"defmod"=>(1+(get_module_pref("dayspast")/100)),
		"startmsg"=>"`2You fight bravely, considering the pressure you're under.",
		"wearoff"=>"`@The Green Dragon`2 has beaten and burnt the bravery out of you.",
		"schema"=>"module-peerpressure",
		)
	);
}

function peerpressure_run(){
}
?>
