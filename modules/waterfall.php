<?php
// translator ready
// addnews ready
// mail ready
function waterfall_getmoduleinfo(){
	$info = array(
		"name"=>"Waterfall",
		"version"=>"1.1",
		"download"=>"core_module",
		"author"=>"Kevin Kilgore with help from Jake Taft",
		"category"=>"Forest Specials",
		"settings"=>array(
			"Waterfall Forest Event Settings,title",
			"carrydk"=>"Do max hitpoints gained carry across DKs?,bool|1",
		),
		"prefs"=>array(
			"Waterfall Forest Event User Preferences,title",
			"extrahps"=>"How many extra hitpoints has the user gained?,int",
		),
	);
	return $info;
}

function waterfall_install(){
	module_addeventhook("forest", "return 100;");
	module_addhook("hprecalc");
	return true;
}

function waterfall_uninstall(){
	return true;
}

function waterfall_dohook($hookname,$args){
	switch($hookname){
	case "hprecalc":
		$args['total'] -= get_module_pref("extrahps");
		if (!get_module_setting("carrydk")) {
			$args['extra'] -= get_module_pref("extrahps");
			set_module_pref("extrahps", 0);
		}
		break;
	}
	return $args;
}

function waterfall_runevent($type)
{
	global $session;
	// We assume this event only shows up in the forest currently.
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:waterfall";

	$op = httpget('op');
	switch($op){
	case "search":
	case "":
		output("`n`2You see a small path that leads away from the main trail. The path is overgrown and you almost didn't see it as you crept by.`n`n");
		output("As you crouch down to study the trail, you notice footprints leading down the path but, oddly, none coming out. While studying the path you hear what sounds like running water.`n");
		addnav("T?Follow the Trail",$from."op=trail");
		addnav("Continue in the forest",$from."op=leave");
		break;
	case "trail":
		output("`2You take the path and begin exploring...`n`n");
		$rand = e_rand(1,12);
		switch ($rand) {
		case 1:
		case 2:
		case 3:
		case 4:
		case 5:
			output("`2After a few hours of exploring you become lost.`n`n");
			output("`&You `\$lose `&one forest fight finding your way back.`n`n");
			if ($session['user']['turns']>0) $session['user']['turns']--;
			$session['user']['specialinc']="";
			break;
		case 6:
		case 7:
		case 8:
			output("`^After a few minutes of exploring you find a waterfall!`n`n");
			output("`2You also notice a small ledge along the rock face of the waterfall.`n");
			output("Should you walk the ledge?");
			addnav("Walk the ledge",$from."op=ledge");
			addnav("Return to the forest",$from."op=leaveleave");
			break;
		case 9:
		case 10:
		case 11:
		case 12:
			output("`^After a few minutes exploring the area you find a waterfall!`n");
			output("`2Thirsty from the walk to the falls you are trying to decide whether or not to take a drink.`n");
			addnav("Take a drink",$from."op=drink");
			addnav("Return to the forest",$from."op=leaveleave");
			break;
		}
		break;
	case "ledge":
		$session['user']['specialinc']="";
		$fall = e_rand(1,9);
		switch ($fall) {
		case 1:
		case 2:
		case 3:
		case 4:
			$gems = e_rand(1,2);
			output("`&You carefully walk the ledge behind the waterfall and find... `%%s %s`n", $gems, translate_inline($gems == 1 ? "gem" : "gems"));
			$session['user']['gems'] += $gems;
			debuglog("found $gems gem(s) behind the waterfall.");
			break;
		case 5:
		case 6:
		case 7:
		case 8:
			$lhps = round($session['user']['hitpoints']*.25);
			$session['user']['hitpoints'] -= $lhps;
			output("`&You carefully walk the ledge behind the waterfall but not carefully enough!`n");
			output("You slip and fall, hurting yourself.`n`n");
			output("`4You have lost `\$%s `4hitpoints during your fall.", $lhps);
			if ($session['user']['gold']>0) {
				$gold = round($session['user']['gold']*.15);
				output("`4You also notice that you lost `^%s gold `4during the ordeal.`n`n", $gold);
				$session['user']['gold'] -= $gold;
				debuglog("lost $gold gold when he fell in the water by the waterfall.");
			}
			break;
		case 9:
			output("`7As you are walking the ledge you slip and fall,`n");
			output("hitting the rocks and the water below!`n`n");
			output("`4`nYou have died and lost all your gold!");
			output("`nYou may continue playing tomorrow.`n");
			$session['user']['turns'] = 0;
			$session['user']['hitpoints'] = 0;
			debuglog("lost {$session['user']['gold']} gold when he fell from the top of the waterfall.");
			$session['user']['gold'] = 0;
			$session['user']['alive'] = false;
			addnews("`%The broken body of %s`% was found partially submerged by the rocks under a waterfall.",$session['user']['name']);
			addnav("Daily News","news.php");
			break;
		}
		break;
	case "drink":
		$session['user']['specialinc']="";
		$cnt = e_rand(1,6);
		switch ($cnt) {
			case 1:
			case 2:
			case 3:
				output("`2You drink from the falls and feel refreshed!`n`n");
				output("`^You have been restored to full health!");
				if ($session['user']['hitpoints'] <
						$session['user']['maxhitpoints'])
					$session['user']['hitpoints'] =
						$session['user']['maxhitpoints'];
				break;
			case 4:
				output("`2You walk to the base of the waterfall and drink deeply of the pure water.`n");
				output("As you drink, you feel a tingling sensation spread all over your body...`n");
				output("You feel refreshed and healthier than ever!`n`n");

				$hptype = "permanently";
				if (!get_module_setting("carrydk") ||
						(is_module_active("globalhp") &&
						 !get_module_setting("carrydk", "globalhp")))
					$hptype = "temporarily";
				$hptype = translate_inline($hptype);

				output("`^Your hitpoints have been restored and your maximum hitpoints have been %s increased by 1.", $hptype);

				$session['user']['maxhitpoints']++;
				if ($session['user']['hitpoints'] <
						$session['user']['maxhitpoints'])
					$session['user']['hitpoints'] =
						$session['user']['maxhitpoints'];
				set_module_pref("extrahps", get_module_pref("extrahps") + 1);
				break;
			case 5:
			case 6:
				output("`2You drink from the falls and you start feeling weird.  You sit down and become ill.`n");
				output("`4You lose a forest fight while recovering!");
				if ($session['user']['turns']>0) $session['user']['turns']--;
				break;
		}
		break;
	case "leave":
		output("`^You stare at the path for a few more moments trying to get the courage to explore it. A piercing chill runs up your spine that makes you start trembling.  At this point you have decided to stay on the main trail.  You quickly move away from the mysterious trail.");
		$session['user']['specialinc']="";
		break;
	case "leaveleave":
		output("`^You decide that discretion is the better part of valor, or at least survival, and return to the forest.");
		$session['user']['specialinc']="";
		break;
	}
	output_notl("`0");
}

function waterfall_run(){
}
?>
