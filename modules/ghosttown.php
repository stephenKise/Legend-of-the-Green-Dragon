<?php
// translator ready
// addnews ready
// mail ready

/* Ghost Town - Halloween */
/* ver 1.0 8th Sept 2004 */
/* Shannon Brown => SaucyWench -at- gmail -dot- com */

function ghosttown_getmoduleinfo(){
	$info = array(
		"name"=>"Ghost Town",
		"version"=>"1.0",
		"author"=>"Shannon Brown",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"Ghost Town Settings,title",
			"villagename"=>"Name for the ghost town|Esoterra",
			"defacedname"=>"Defaced name for the ghost town|Eso`^Terron`6",
			"allowtravel"=>"Allow 'standard' travel to town?,bool|0",
		),
		"prefs"=>array(
			"Ghost Town User Preferences,title",
			"allow"=>"Is player allowed in?,bool|0",
		),
	);
	return $info;
}

function ghosttown_install(){
	module_addhook("villagetext");
	module_addhook("village");
	module_addhook("travel");
	module_addhook("validlocation");
	module_addhook("moderate");
	module_addhook("changesetting");
	module_addhook("pvpstart");
	module_addhook("pvpwin");
	module_addhook("pvploss");
	return true;
}

function ghosttown_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	return true;
}

function ghosttown_dohook($hookname,$args){
	global $session,$resline;
	$city = get_module_setting("villagename");
	switch($hookname){
	case "pvpwin":
		if ($session['user']['location'] == $city) {
			$args['handled']=true;
			addnews("`4%s`3 defeated `4%s`3 in fair combat near the campfire in %s.", $session['user']['name'],$args['badguy']['creaturename'], $args['badguy']['location']);
		}
		break;
	case "pvploss":
		if ($session['user']['location'] == $city) {
			$args['handled']=true;
			addnews("`%%s`5 has been slain while attacking `^%s`5 near the campfire in `&%s`5.`n%s`0", $session['user']['name'], $args['badguy']['creaturename'], $args['badguy']['location'], $args['taunt']);
		}
		break;
	case "pvpstart":
		if ($session['user']['location'] == $city) {
			$args['atkmsg'] = "`4You wander through the city gates, to where a large group of warriors are singing around the campfire. At the edges of the scrub, in the darkness and away from the others, some foolish warriors have bedded down for the night...`n`nYou have `^%s`4 PvP fights left for today.`n`n";
			$args['schemas']['atkmsg'] = 'module-ghosttown';
		}
		break;
	case "travel":
		$allow = get_module_pref("allow") || get_module_setting("allowtravel");
		$capital = getsetting("villagename", LOCATION_FIELDS);
		$hotkey = substr($city, 0, 1);
		tlschema("module-cities");
		// Esoterra is always dangerous travel.
		if ($session['user']['location']!=$city && $allow){
			addnav("More Dangerous Travel");
			// Actually make the travel dangerous
			addnav(array("%s?Go to %s", $hotkey, $city),
					"runmodule.php?module=cities&op=travel&city=$city&d=1");
		}
		if ($session['user']['superuser'] & SU_EDIT_USERS && $allow){
			addnav("Superuser");
			addnav(array("%s?Go to %s", $hotkey, $city),
					"runmodule.php?module=cities&op=travel&city=$city&su=1");
		}
		tlschema();
		break;
	case "changesetting":
		// Ignore anything other than villagename setting changes
		if ($args['setting']=="villagename" && $args['module']=="ghosttown") {
			if ($session['user']['location'] == $args['old']) {
				$session['user']['location'] = $args['new'];
			}
			$sql = "UPDATE " . db_prefix("accounts") . " SET location='" .
				addslashes($args['new']) . "' WHERE location='" .
				addslashes($args['old']) . "'";
			db_query($sql);
		}
		break;
	case "validlocation":
		$canvisit = 0;
		if (is_module_active("caravan") &&
				get_module_setting("canvisit", "caravan"))
			$canvisit = 1;
		if(get_module_pref("allow") || get_module_setting("allowtravel"))
			$canvisit = 1;
		if (!$canvisit && (!isset($arg['all']) || !$args['all'])) break;
		if (is_module_active("cities"))
			$args[$city]="village-ghosttown";
		break;
	case "moderate":
		if (is_module_active("cities")) {
			tlschema("commentary");
			$args["village-ghosttown"]=sprintf_translate("City of %s", $city);
			tlschema();
		}
		break;
	case "villagetext":
		$deface = get_module_setting("defacedname");
		if ($session['user']['location'] == $city){
			$args['text']=array("`&`c`b%s`b`c`n`6You are standing in a deserted ghost town. Swinging on rusty hooks, a sign proclaiming \"%s\" has been vandalized to read \"%s\". Eerie silence greets you from the shadows, and the nearby cemetery echoes the sounds of departed unrestful souls.`n", $city, $city, $deface);
			$args['schemas']['text'] = "module-ghosttown";
			$args['clock']="`n`7The rusted iron clock on the old church reads `&%s`7.`n";
			$args['schemas']['clock'] = "module-ghosttown";
			if (is_module_active("calendar")) {
				$args['calendar']="`n`7A ghostly voice whispers from nowhere that it is `&%s`7, `&%s %s %s`7.`n";
				$args['schemas']['calendar'] = "module-ghosttown";
			}
			$args['title']=array("%s, the Ghost Town", $city);
			$args['schemas']['title'] = "module-ghosttown";
			$args['sayline']="whispers";
			$args['schemas']['sayline'] = "module-ghosttown";
			$args['talk']="`n`&Nearby some visitors whisper in hushed tones:`n";
			$args['schemas']['talk'] = "module-ghosttown";
			$args['newest'] = "";
			blocknav("lodge.php");
			blocknav("weapons.php");
			blocknav("armor.php");
			blocknav("clan.php");
			blocknav("pvp.php");
			blocknav("forest.php");
			blocknav("gardens.php");
			blocknav("gypsy.php");
			blocknav("bank.php");
			$allow = get_module_pref("allow") || get_module_setting("allowtravel");
			if(!$allow) {
				blockmodule("cities");
			}
			blockmodule("tynan");
			blockmodule("abigail");
			blockmodule("crazyaudrey");
			blockmodule("icecaravan");
			$args['schemas']['newest'] = "module-ghosttown";
			$args['gatenav']="Village Gates";
			$args['schemas']['gatenav'] = "module-ghosttown";
			$args['fightnav']="Aisle of Slain Warriors";
			$args['schemas']['fightnav'] = "module-ghosttown";
			$args['marketnav']="Hauntings";
			$args['schemas']['marketnav'] = "module-ghosttown";
			$args['tavernnav']="Drunkard's Lane";
			$args['schemas']['tavernnav'] = "module-ghosttown";
			$args['section']="village-ghosttown";
			$args['infonav']="Headstones";
			$args['schemas']['infonav'] = "module-ghosttown";
		}
		break;
	case "village":
		if ($session['user']['location']==$city){
			tlschema($args['schemas']['gatenav']);
			addnav($args['gatenav']);
			tlschema();
			addnav("V?Visit the Campsite","pvp.php?campsite=1");
		}
		break;
	}
	return $args;
}

function ghosttown_run(){
}
?>
