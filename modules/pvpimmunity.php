<?php
/*
   PVP immunity module.   Allow players to give up PVP entirely but gain
   NO bonus at all from anything pvp related.
 */

function pvpimmunity_getmoduleinfo(){
	$info = array(
		"name"=>"PVP Immunity",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"category"=>"PVP",
		"download"=>"core_module",
		"prefs"=>array(
			"PVP Immunity User Preferences,title",
			"locked"=>"Is this player blocked from changing their immunity status?,bool|1",
			"If you are unwilling to engage in PvP you can turn this off.,note",
			"If you do you will not be able to gain ANY benefit from PvP.,note",
			"You will not be able to be attacked however until you reset this option.,note",
			"This option may ONLY be changed once per DK and will keep your current setting unless you deliberately change it.,note",
			"check_willing"=>"Willing to engage in PvP?,bool|1",
		),
	);
	return $info;
}

function pvpimmunity_install(){
	module_addhook_priority("pvpwarning", 20);
	module_addhook_priority("pvpmodifytargets", 100);
	module_addhook("heidi-end");
	module_addhook("dragonkill");
	module_addhook("village");
	module_addhook("checkuserpref");
	module_addhook("process-create");
	module_addhook("notifyuserprefchange");
	module_addhook("header-inn");
	return true;
}

function pvpimmunity_uninstall(){
	return true;
}

function pvpimmunity_dohook($hookname,$args){
	switch($hookname) {
	case "notifyuserprefchange":
		if ($args['name'] == "check_willing")
			set_module_pref("locked", 1);
		break;
	case "process-create":
		// Newly created players don't need to be pvplocked.  they cannot
		// have done a recent pvp.
		$acctid = $args['acctid'];
		set_module_pref("locked", 0, "pvpimmunity", $acctid);
		break;
	case "dragonkill":
		set_module_pref("locked", 0);
		break;
	case "checkuserpref":
		if ($args['name'] == "check_willing") {
			if (get_module_pref("locked")) {
				$args['pref'] =
					str_replace(",bool", ",viewonly", $args['pref']);
			}
			$args['allow'] = 1;
		}
		break;
	case "pvpmodifytargets":
		foreach ($args as $index=>$target) {
			if (!get_module_pref("check_willing", "pvpimmunity",
						$target['acctid'])) {
				$args[$index]['invalid'] = 1;
			}
		}
		break;
	case "village":
		if (!get_module_pref("check_willing"))
			blocknav("pvp.php", true);
		break;
	case "pvpwarning":
		if (!get_module_pref("locked")) {
			if (!$args['dokill']) {
				output("`\$WARNING:`7 You are currently set to allow PvP, but are not yet locked in for this dragon kill cycle.");
				output("Once you attack for the first time during a dragon kill you will be unable to stay safe from others until the next time you kill the dragon.");
				output("If you, like some, would prefer to completely avoid the player versus player aspect of the game, including the possibility of being killed by players much stronger than you, then you should enable PvP immunity from your Preferences page.`n`n");
			} else {
				output("`\$WARNING:`7 By attacking another player in PvP you have locked yourself into allowing PvP against you for this dragonkill cycle.");
				output("You may visit your preferences page to change this after your next dragon kill.`n`n");
				set_module_pref("locked", 1);
			}
		}
		break;
	case "heidi-end":
		// If the person has declined PVP, they cannot benefit from trading in
		// PVP turns either.
		if (!get_module_pref("check_willing")) {
			// block the blue candle nav
			blocknav("runmodule.php?module=heidi&op=blue");
		}
		$op = httpget("op");
		if ($op == "") {
			if (!get_module_pref("locked")) {
				// Okay.. they can change their pvp immunity preferences
				output("`n`n`7Heidi peers into your eyes for a moment, as if searching your soul for violence.");
				if (get_module_pref("check_willing")) {
					output("\"`&Be warned, child, should you burn a blue candle, you will be locked into a world of violence for another cycle!`7\"");
				} else {
					output("\"`&You have chosen the way of peace, and so long as you walk it, you may not make use of the blue candles. We all have the means to change our destinies, though, so if 'tis your wish, ye can still change the choice you made.`7\"");
				}
			} else {
				// They are already locked.  If they cannot use the blue let
				// them know why.
				if (!get_module_pref("check_willing")) {
					output("`n`n`7Heidi peers into your eyes for a moment, as if searching your soul for violence.");
					output("\"`&You are truly blessed, my child. You have completely forsaken violence against your fellows for this turn of the cycle! And so, the spirits won't impart the blessing of the blue candles to you, but you have a much more gentle soul.`7\"");
				}
			}
		} elseif ($op=="blue") {
			// Lock them out of changing.
			set_module_pref("locked", 1);
		}
		break;
	case "header-inn":
		if (!get_module_pref("check_willing")) blocknav("inn.php?op=bartender&act=listupstairs");
		break;
	}
	return $args;
}

function pvpimmunity_runevent($type,$link)
{
}

function pvpimmunity_run(){
}
?>
