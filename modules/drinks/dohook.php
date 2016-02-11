<?php
function drinks_dohook_private($hookname,$args) {
	global $session;

	switch($hookname) {
	case "dragonkill":
		set_module_pref("drunkeness",0);
		break;
	case "ale":
		require_once("modules/drinks/misc_functions.php");
		$texts = drinks_gettexts();
		$drinktext = modulehook("drinks-text",$texts);

		$drunk = get_module_pref("drunkeness");
		$drunklist = array(
				-1=>"stone cold sober",
				0=>"quite sober",
				1=>"barely buzzed",
				2=>"pleasantly buzzed",
				3=>"almost drunk",
				4=>"barely drunk",
				5=>"solidly drunk",
				6=>"sloshed",
				7=>"hammered",
				8=>"really hammered",
				9=>"almost unconscious",
				10=>"about to pass out");
		$drunklist = translate_inline($drunklist);
		$drunk = round($drunk/10-.5, 0);
		if ($drunk > 10) $drunk = 10;
		$hard = "";
		if (get_module_pref('harddrinks')>=get_module_setting('hardlimit')) {
			tlschema($drinktexts['schemas']['toomany']);
			output_notl("`n`n");
			$remark = translate_inline($drinktexts['toomany']);
			$remark = str_replace("{lover}",$partner."`0", $remark);
			$remark = str_replace("{barkeep}", $drinktext['barkeep']."`0", $remark);
			output_notl("%s`n", $remark);
			output($drinktexts['toomany']);
			output_notl("`n");
			$hard = "AND harddrink=0";
		}
		output("`n`n`7You now feel %s.`n`n", $drunklist[$drunk]);
		$sql = "SELECT * FROM " . db_prefix("drinks") . " WHERE active=1 $hard ORDER BY costperlevel";
		$result = db_query($sql);
		while ($row = db_fetch_assoc($result)) {
			$row['allowdrink'] = 1;
			$row = modulehook("drinks-check", $row);
			if ($row['allowdrink']) {
				$drinkcost = $row['costperlevel']*$session['user']['level'];
				// No hotkeys on drinks.  Too easy for them to interfere
				// with and modify stock navs randomly.
				addnav(array(" ?%s  (`^%s`0 gold)", $row['name'], $drinkcost),
						"runmodule.php?module=drinks&act=buy&id={$row['drinkid']}");
			}
		}
		break;
	case "newday":
		set_module_pref("harddrinks", 0);
		$drunk = get_module_pref("drunkeness");
		if ($drunk > 66) {
			output("`n`&Waking up in the gutter after your last little 'adventure with alcohol', you `\$lose 1`& turn crawling back to your normal lodging.`n");
			$args['turnstoday'] .= ", Hangover: -1";
			$session['user']['turns']--;
			// Sanity check
			if ($session['user']['turns'] < 0) $session['user']['turns'] = 0;
		}
		set_module_pref("drunkeness",0);
		break;
	case "header-graveyard":
		set_module_pref("drunkeness",0);
		break;
	case "soberup":
		$soberval = $args['soberval'];
		$sobermsg = $args['sobermsg'];
		$drunk = get_module_pref("drunkeness");
		if ($drunk > 0) {
			$drunk = round($drunk * $soberval, 0);
			set_module_pref("drunkeness", $drunk);
			if ($sobermsg) {
				if ($args['schema']) tlschema($args['schema']);
				output($sobermsg);
				if ($args['schema']) tlschema();
			}
		}
		break;
	case "commentary":
		if (($session['user']['superuser'] & SU_IS_GAMEMASTER) && substr($args['commentline'], 0, 5) == "/game") break;
		require_once("modules/drinks/drunkenize.php");
		$drunk = get_module_pref("drunkeness");
		if ($drunk > 50) {
			$args['commenttalk'] = "drunkenly {$args['commenttalk']}";
		}
		$commentline = $args['commentline'];
		if (substr($commentline, 0, 1) != ":" &&
				substr($commentline, 0, 2) != "::" &&
				substr($commentline, 0, 3) != "/me" &&
				$drunk > 0) {
			$args['commentline'] = drinks_drunkenize($commentline, $drunk);
		}
		break;
	case "superuser":
		if (($session['user']['superuser'] & SU_EDIT_USERS) || get_module_pref("canedit")) {
			addnav("Module Configurations");
			// Stick the admin=true on so that when we call runmodule it'll
			// work to let us edit drinks even when the module is deactivated.
			addnav("Drinks Editor","runmodule.php?module=drinks&act=editor&admin=true");
		}
		break;
	}//end select
	return $args;
}//end function
?>
