<?php
// addnews ready
// mail ready
// translator ready

function dragonattack_getmoduleinfo(){
	$info = array(
		"name"=>"The Dragon Attacks",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Dragon Attack Settings,title",
			"daysfree"=>"Days until player is a potential victim,int|2",
		),
		"prefs"=>array(
			"Dragon Attack Preferences,title",
			"dayspast"=>"Days since user hit level 15,int|0",
		),
	);
	return $info;
}

function dragonattack_victimtest() {
	global $session;
	if ($session['user']['level'] < 15) return 0;
	if (get_module_pref("dayspast", "dragonattack") <
			get_module_setting("daysfree", "dragonattack")) return 0;
	return 100;
}

function dragonattack_install(){
	module_addeventhook("forest",
		"require_once(\"modules/dragonattack.php\"); return dragonattack_victimtest();");
	module_addeventhook("travel",
		"require_once(\"modules/dragonattack.php\"); return dragonattack_victimtest();");
	module_addhook("newday");
	module_addhook("battle-defeat");
	return true;
}

function dragonattack_uninstall(){
	return true;
}

function dragonattack_dohook($hookname,$args){
	global $session;
	switch ($hookname) {
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
		if ($session['user']['specialinc'] == "module:dragonattack" && $options['type'] == "dragon") {
			$session['user']['specialinc'] = "";
		}
		break;
	}
	return $args;
}

function dragonattack_runevent($type)
{
	global $session;
	$session['user']['specialinc']="module:dragonattack";
	output("`2A loud roar from overhead causes you to look up.");
	output("You are utterly astounded to see a very large, very angry `@Green Dragon`2 winging right toward you!`n`n");
	output("You quickly look around for a place to hide, but the dragon was wily enough to have caught you in a relatively large clearing and you have no way of getting back to the trees in time.`n`n");
	output("You have a brief moment to wonder if perhaps you shouldn't have boasted so much about your prowess before the dragon swoops down and snags you in its talons with a deafening roar.`n`n");
	output("Moments later, it drops you unceremoniously to the ground inside the entrance of its cave, blocking your way out and starts advancing toward you with drool falling from its gaping jaws.`n`n");
	addnav("Attack the Dragon", "dragon.php?nointro=1");
}

function dragonattack_run(){
}
?>
