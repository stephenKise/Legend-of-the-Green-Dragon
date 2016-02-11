<?php
// addnews ready
// mail ready
// translator ready
require_once("lib/villagenav.php");
require_once("lib/commentary.php");

function scry_getmoduleinfo(){
	$info = array(
		"name"=>"Scrying Pool",
		"author"=>"JT Traub",
		"category"=>"Village",
		"version"=>"1.0",
		"download"=>"core_module",
		"settings"=>array(
			"Scrying Pool Settings,title",
			"cost"=>"How much gold does scrying cost per level?,range,1,50,1|20",
			"uses"=>"How many times can you speak before you must pay again? (0 unlimited),range,0,50,1|5",
		),
		"prefs"=>array(
			"Scrying Pool User Preferences,title",
			"talks"=>"Current number of times user has spoken,int|0",
		),
	);
	return $info;
}

function scry_install(){
	if (!is_module_installed("cities")) {
		output("`\$This module requires the multiple villages mod to be installed before it is used.`0");
		return false;
	}
	module_addhook("gypsy");
	module_addhook("commentary");
	return true;
}

function scry_uninstall(){
	debug("Uninstalling module.");
	return true;
}

function scry_dohook($hookname, $args) {
	global $session;

	switch($hookname){
	case "commentary":
		set_module_pref("talks", get_module_pref("talks")+1);
		break;
	case "gypsy":
		$cost = get_module_setting("cost") * $session['user']['level'];
		output("`n`nOff in the corner, you see a dark bowl of water sitting atop a silver tray.");
		output("Noticing your gaze, the old gypsy woman rasps, \"`!Ahh, perhaps you seek to talk to people in one of the other towns?  I could do that -- for the very small price of `^%s`! gold.`5\"", $cost);
		addnav(array("Scrying (%s gold)", $cost));
		$vloc = array();
		$vname = getsetting("villagename", LOCATION_FIELDS);
		$vloc[$vname] = "village";
		$vloc = modulehook("validlocation", $vloc);
			// this is a different modulehook call because
			// there is more than one "validlocation" modulehook
		$vloc = modulehook("scrylocation", $vloc);
		ksort($vloc);
		reset($vloc);
		foreach($vloc as $loc=>$val) {
			if ($loc == $session['user']['location']) continue;
			addnav(array("Scry %s", $loc), "runmodule.php?module=scry&op=pay&area=".htmlentities($val, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."&village=".htmlentities($loc, ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
		}
		break;
	}
	return $args;
}

function scry_run(){
	global $session;
	addcommentary();
	$area = httpget("area");
	$village = httpget("village");
	$op = httpget("op");
	$cost = $session['user']['level']*get_module_setting("cost");
	if ($op == "pay") {
		if ($session['user']['gold']>=$cost) {
			$session['user']['gold']-= $cost;
			set_module_pref("talks", 0);
			debuglog("spent $cost gold to scry a remote village");
			redirect("runmodule.php?module=scry&op=talk&area=".htmlentities($area, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."&village=".htmlentities($village, ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
			page_footer();
		} else {
			page_header("Gypsy Seer's tent");
			villagenav();
			addnav("Continue looking around", "gypsy.php");
			output("`5You offer the old gypsy woman your `^%s`5 gold for her scrying services, but she informs you that such a pittance is far too little.", $session['user']['gold']);
			page_footer();
		}
	} elseif($op == "talk") {
		$times = get_module_setting("uses");
		if ($times && (get_module_pref("talks") >= $times)) {
			page_header("Gypsy Seer's tent");
			output("`5Looking around dazedly, it takes you a moment to realize that you are no longer viewing the village of %s and that the gypsy woman is staring at you with her hand out.", $village);
			output("`5\"`!I'll need more gold if you want to keep taking up space for my other paying customers!`5\", she demands.`n`n");
			output("You start to demand, \"`&What other customers?`5\" but decide that it's best not to annoy someone who has such power.");
			addnav(array("Scrying (%s gold)", $cost));
			addnav(array("Scry %s", $village), "runmodule.php?module=scry&op=pay&area=".htmlentities($area, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."&village=".htmlentities($village, ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
			addnav("Other");
			addnav("Return to the tent", "gypsy.php");
			villagenav();

			page_footer();
		} else {
			page_header("Peering in the bowl, you view %s", $village);
			output("`5While staring into the inky water, you are able to make out the people of %s:`n", $village);
			commentdisplay("", $area,"Project",25,"projects");
			addnav("Look up from the bowl","gypsy.php");
			page_footer();
		}
	}
}

?>
