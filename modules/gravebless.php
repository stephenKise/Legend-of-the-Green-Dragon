<?php
/*  Title:    Ramius' Blessing
 *  Author:   Ben "blarg" Wong (blargeth -at- gmail -dot- com)
 *
 *  Description:
 *  This is a favour reward from Ramius. For a (default) cost of 500 favor,
 *  one can seek to have their weapon or armor blessed, which is kept until
 *  DK. However, there is a (default) 1% chance of Ramius cursing the item
 *  instead. Inspired by the rumour that there already exists something to
 *  get at 500 favour. :)
 *
 *  Version History:
 *  1.1 (2006/03/05)  - added a decay for curses
 *
 *  1.0 (2005/08/15)  - adjusted decay calcs as recommended by Kendaer
 *                    - added setting for a base multiplier to start the
 *                      buff at (default of 1.0)
 *                    - official release
 *
 *  0.3 (2005/08/11)  - adjusted range settings for the curse chance
 *                    - added a requirement for a minimum number of
 *                      resurrections before use (default of one)
 *                    - other minor tweaks and fixes
 *
 *  0.2 (2005/08/04)  - added a decay rate to the blessed buff effect
 *                    - changed to use the dragonkilltext hook
 *                    - other minor tweaks and fixes
 *
 *  0.1 (2005/07/20)  - first alpha release
 */

require_once("lib/http.php");
require_once("lib/buffs.php");

function gravebless_getmoduleinfo() {
	$info = array(
		"name"=>"Ramius' Blessing",
		"author"=>"Ben Wong",
		"version"=>"1.1",
		"category"=>"Graveyard",
		"download"=>"core_module",
		"description"=>"For a (large) favor cost, Ramius will bless (possibly curse) a player's armor or weapon.",
		"settings"=>array(
			"Ramius' Blessing Settings,title",
			"blesscost"=>array("Cost in favor for a blessing from %s,int|500", getsetting("deathoverlord", '`$Ramius')),
			"minrez"=>"Minimum number of resurrections the player must have accrued in a DK,range,0,10,1|0",
			"cursechance"=>"Percent chance the blessing becomes a curse,range,0,50,1|1",
			"basemultiplier"=>"Base multiplier to start the buff at,floatrange,0.25,2.0,0.25|1.0",
			"Note: This value is used as a multiplier to lifetap and damageshield.,note",
			"cursemultiplier"=>"Multiplier to start the curse at,floatrange,0.1,1.0,0.1|0.8",
			"Note: This value is used as a multiplier to defmod and atkmod.,note",
			"curserate"=>"Rate the curse degrades at in percent per day,range,0,5,1|1"
		),
		"prefs"=>array(
			"Ramius' Blessing Preferences,title",
			"ramiusblessed"=>array("Has the player been blessed by %s this DK?,bool|0", getsetting("deathoverlord", '`$Ramius')),
			"ramiuscursed"=>array("Has the player been cursed by %s this DK?,bool|0", getsetting("deathoverlord", '`$Ramius')),
			"ramiusarmor"=>"Was this done to the armor?,bool|0",
			"ramiusweapon"=>"Was this done to the weapon?,bool|0",
			"firstday"=>"Is this the first day of use for the buff?,bool|0",
			"currmultiplier"=>"Current buff effect multiplier,float|0"
		),
	);
	return $info;
}

function gravebless_install() {
	module_addhook("ramiusfavors");
	module_addhook("dragonkilltext");
	module_addhook("newday");
	return true;
}

function gravebless_uninstall() {
	return true;
}

function gravebless_dohook($hookname, $args) {
	global $session;
	$blesscost = get_module_setting("blesscost");
	$minrez = get_module_setting("minrez");
	$playerfavor = $session['user']['deathpower'];
	$playerrezzes = $session['user']['resurrections'];
	$blessed = get_module_pref("ramiusblessed");
	$cursed = get_module_pref("ramiuscursed");
	$ramiusarmor = get_module_pref("ramiusarmor");
	$ramiusweapon = get_module_pref("ramiusweapon");

	switch ($hookname) {
	case "dragonkilltext":
		if ($blessed || $cursed) {
			output("`n`nYou seem to hear a very faint humming noise, but as you look around for the source, it quickly fades to silence.");
		}
		set_module_pref("ramiusblessed", 0);
		set_module_pref("ramiuscursed", 0);
		set_module_pref("ramiusarmor", 0);
		set_module_pref("ramiusweapon", 0);
		set_module_pref("currmultiplier", 0);
		break;

	case "ramiusfavors":
		// only show the link if they have enough favor, haven't already
		// used it this DK, and have enough rezzes
		if (($playerfavor >= $blesscost) && (!($blessed || $cursed)) && ($playerrezzes >= $minrez)) {
			require_once("lib/sanitize.php");
			addnav(array("%s Favors", sanitize(getsetting("deathoverlord", '`$Ramius'))));
			addnav(array("Seek a Blessing (%s favor)", $blesscost),	"runmodule.php?module=gravebless&blessop=bless");
		}
		break;

	case "newday":
		$multiplier = get_module_pref("currmultiplier");
		$firstday = get_module_pref("firstday");
		if ($blessed) {
			if ($firstday) {
				set_module_pref("firstday",0);
			} else {
				$basemult = get_module_setting("basemultiplier");
				// start out with a quicker reduction, down to 20%
				if ($multiplier > 0.2) {
					$multiplier = round($multiplier - $basemult / 7, 2);
				} else {
					// flatten out the decay after 20%
					$multiplier = round($multiplier * 0.75, 2);
				}
				if ($multiplier < 0.01) {
					$multiplier = 0.01;  // bottoms out at 1% until DK
				}
				set_module_pref("currmultiplier", $multiplier);
			}
			$deathoverlord = getsetting("deathoverlord", "`\$Ramius");
			if ($ramiusarmor) {
				$ramiusbuff = array("name"=>array("%s`\$' Blessed Armor",$deathoverlord),
									"rounds"=>-1,
									"defmod"=>1.25,
									"damageshield"=>$multiplier,
									"roundmsg"=>"`\$Your armor hums to life in response to the battle!",
									"effectmsg"=>"`\$You feel a shock course through your armor as it deflects {damage} damage back to {badguy}!",
									"effectnodmg"=>"",
									"effectfailmsg"=>"",
									"schema"=>"module-gravebless");
			} elseif ($ramiusweapon) {
				$ramiusbuff = array("name"=>array("%s`4' Blessed Weapon",$deathoverlord),
									"rounds"=>-1,
									"atkmod"=>1.25,
									"lifetap"=>$multiplier,
									"roundmsg"=>"`\$Your weapon hums to life as you swing it at {badguy}!",
									"effectmsg"=>"`\$You feel a shock course through your weapon as it heals you for {damage}!",
									"effectnodmg"=>"",
									"effectfailmsg"=>"",
									"schema"=>"module-gravebless");
			} else {
				debug("Error: Ramius bless flagged, but not armor or weapon (gravebless)");
			}
		} elseif ($cursed) {
			if ($firstday) {
				set_module_pref("firstday",0);
			} else {
				$cursemult = get_module_setting("cursemultiplier");
				$curserate = get_module_setting("curserate");
				$multiplier += ($curserate / 100);  // curses degrade linearly..
				if ($multiplier > 0.99) {
					$multiplier = 0.99;  // ..but don't go away completely until DK
				}
				set_module_pref("currmultiplier", $multiplier);
			}
			$deathoverlord = getsetting("deathoverlord", "`\$Ramius");
			if ($ramiusarmor) {
				$ramiusbuff = array("name"=>array("%s`\$' Cursed Armor",$deathoverlord),
									"rounds"=>-1,
									"defmod"=>$multiplier,
									"badguyatkmod"=>1.25,
									"roundmsg"=>"`4Your armor hums to life in response to the battle!",
									"schema"=>"module-gravebless");
			} elseif ($ramiusweapon) {
				$ramiusbuff = array("name"=>array("%s`\$' Cursed Weapon",$deathoverlord),
									"rounds"=>-1,
									"atkmod"=>$multiplier,
									"badguydefmod"=>1.25,
									"roundmsg"=>"`4Your weapon hums to life as you swing it at {badguy}!",
									"schema"=>"module-gravebless");
			} else {
				debug("Error: Ramius curse flagged, but not armor or weapon (gravebless)");
			}
		} else {
			debug("neither blessed nor cursed are flagged (gravebless)");
		}
		if ($blessed || $cursed) {
			output("`nYou think you can hear a faint humming sound as you don your armor and grab your weapon. ");
			output("Your %s even feels slightly warmer to the touch than you remember.`n",
				   translate_inline($ramiusarmor==1?"armor":"weapon"));
			apply_buff("ramiusbuff", $ramiusbuff);
		}
		break;
	}
	return $args;
}

function gravebless_run() {
	global $session;
	page_header(array("Seek a Blessing from %s", getsetting("deathoverlord", '`$Ramius')));
	$blessop = httpget("blessop");
	$blesscost = get_module_setting("blesscost");
	$cursechance = get_module_setting("cursechance");
	$basemultiplier = get_module_setting("basemultiplier");
	$cursemultiplier = get_module_setting("cursemultiplier");

	if ($blessop == "bless") {
		output("%s`) speaks, \"`7You are a most persistent mortal.", getsetting("deathoverlord", '`$Ramius'));
		output("For that, I shall grant you a blessing upon your equipment. ");
		output("Perhaps it will enhance your survivability in the waking realm.`)\"");
		addnav("Bless Armor", "runmodule.php?module=gravebless&blessop=armor");
		addnav("Bless Weapon", "runmodule.php?module=gravebless&blessop=weapon");

	} elseif ($blessop == "armor") {
		output("%s`) looks over the battered armor you hand him and remarks, \"`7It is no small wonder you visit so often.\"", getsetting("deathoverlord", '`$Ramius'));
		output("`)Closing his eyes, he waves his hand over the armor and quietly speaks a few words you cannot make out.");

		$randcurse = e_rand(1,100);
		if ($randcurse > $cursechance) {
			// armor successfully blessed
			output("`)He finishes the incantation and sits still for a moment.`n`n");
			output("\"`7There,`)\" he says, handing you back your armor.");
			output("\"`7Perhaps this will help you when you next awaken.`)\"`n`n");
			output("`)You put on the armor and notice that it feels slightly warmer to the touch than you remember.");
			debuglog("spent $blesscost favor on armor blessing from Ramius");
			$session['user']['deathpower'] -= $blesscost;
			set_module_pref("ramiusblessed", 1);
			set_module_pref("ramiusarmor", 1);
			set_module_pref("currmultiplier",$basemultiplier);  // start at returning 3/4 x enemy damage (default)
			set_module_pref("firstday",1);
		} else {  // cursed armor!
			output("`)You hear a slight cough as he finishes the incantation and looks at your armor.`n`n");
			output("\"`7There,`)\" he says, handing it back to you.");
			output("\"`7Perhaps this will make you stronger when you next awaken.`)\"`n`n");
			output("`)You put on the armor and notice that it feels slightly warmer to the touch than you remember.");
			debuglog("spent $blesscost favor on armor curse from Ramius");
			$session['user']['deathpower'] -= $blesscost;
			set_module_pref("ramiuscursed", 1);
			set_module_pref("ramiusarmor", 1);
			set_module_pref("currmultiplier",$cursemultiplier);
			set_module_pref("firstday",1);
		}

	} elseif ($blessop == "weapon") {
		output("%s`) looks over the puny weapon you hand him and comments, \"`7I am impressed that you make any progress at all.\"", getsetting("deathoverlord", '`$Ramius'));
		output("`)Closing his eyes, he waves his hand over the weapon and quietly speaks a few words you cannot make out.");

		$randcurse = e_rand(1,100);
		if ($randcurse > $cursechance) {
			// weapon successfully blessed
			output("`)He finishes the incantation and sits still for a moment.`n`n");
			output("\"`7There,`)\" he says, handing you back your weapon.");
			output("\"`7Perhaps this will help you when you next awaken.`)\"`n`n");
			output("`)You grasp the weapon and notice that it feels slightly warmer to the touch than you remember.");
			debuglog("spent $blesscost favor on weapon blessing from ". getsetting("deathoverlord", '`$Ramius'));
			$session['user']['deathpower'] -= $blesscost;
			set_module_pref("ramiusblessed", 1);
			set_module_pref("ramiusweapon", 1);
			set_module_pref("currmultiplier",$basemultiplier);  // start at healing 3/4 x damage dealt (default)
			set_module_pref("firstday",1);
		} else { // cursed weapon!
			output("`)You hear a slight cough as he finishes the incantation and looks at your weapon.`n`n");
			output("\"`7There,`)\" he says, handing it back to you.");
			output("\"`7Perhaps this will make you stronger when you next awaken.`)\"`n`n");
			output("`)You grasp the weapon and notice that it feels slightly warmer to the touch than you remember.");
			debuglog("spent $blesscost favor on weapon curse from Ramius");
			$session['user']['deathpower'] -= $blesscost;
			set_module_pref("ramiuscursed", 1);
			set_module_pref("ramiusweapon", 1);
			set_module_pref("currmultiplier",$cursemultiplier);
			set_module_pref("firstday",1);
		}

	} else {
		output("`)Looking up at his grim visage, you reconsider asking for a blessing. ");
		output("As you turn to leave, %s`) speaks, ", getsetting("deathoverlord", '`$Ramius'));
		output("\"`7An impressive feat, mortal. You should not have been able to get here. Please Petition.`)\"");
	}

	addnav("Places");
	addnav("S?Land of the Shades","shades.php");
	addnav("G?Return to the Graveyard","graveyard.php");
	addnav("M?Return to the Mausoleum","graveyard.php?op=enter");
	page_footer();
}
?>
