<?php
// addnews ready
// translator ready
// mail ready

/* Ice Town for Dec/Jan */
/* ver 1.0 9th Nov 2004 */
/* Shannon Brown => SaucyWench -at- gmail -dot- com */

function icetown_getmoduleinfo(){
	$info = array(
		"name"=>"Ice Town",
		"version"=>"1.0",
		"author"=>"Shannon Brown",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"Ice Town Settings,title",
			"villagename"=>"Name for the ice town|Polareia Borealis",
			"allowtravel"=>"Allow 'standard' travel to town?,bool|1",
		),
		"prefs"=>array(
			"Ice Town User Preferences,title",
			"allow"=>"Is player allowed in?,bool|0",
		),
	);
	return $info;
}

function icetown_install(){
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

function icetown_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	return true;
}

function icetown_dohook($hookname,$args){
	global $session,$resline;
	$city = get_module_setting("villagename");
	switch($hookname){
	case "pvpwin":
		if ($session['user']['location'] == $city) {
			$args['handled']=true;
			addnews("`4%s`3 defeated `4%s`3 in fair combat on the ski slopes of %s.", $session['user']['name'],$args['badguy']['creaturename'], $args['badguy']['location']);
		}
		break;
	case "pvploss":
		if ($session['user']['location'] == $city) {
			$args['handled']=true;
			addnews("`%%s`5 has been slain while attacking `^%s`5 on the ski slopes of `&%s`5.`n%s`0", $session['user']['name'], $args['badguy']['creaturename'], $args['badguy']['location'], $args['taunt']);
		}
		break;
	case "pvpstart":
		if ($session['user']['location'] == $city) {
			$args['atkmsg'] = "`7You trek through the city gates, to where a large group of warriors are queueing for the ski lifts. Clutches of small huts are to one side of a clearing, where some foolish warriors have taken shelter for the night...`n`nYou have `^%s`7 PvP fights left for today.`n`n";
			$args['schemas']['atkmsg'] = 'module-icetown';
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
		if ($args['setting']=="villagename" && $args['module']=="icetown") {
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
		if (is_module_active("icecaravan") &&
				get_module_setting("canvisit", "icecaravan"))
			$canvisit = 1;
		if (get_module_pref("allow") || get_module_setting("allowtravel"))
			$canvisit = 1;
		if (!$canvisit && (!isset($arg['all']) || !$args['all'])) break;
		if (is_module_active("cities"))
			$args[$city]="village-icetown";
		break;
	case "moderate":
		if (is_module_active("cities")) {
			tlschema("commentary");
			$args["village-icetown"]=sprintf_translate("City of %s", $city);
			tlschema();
		}
		break;
	case "villagetext":
		$deface = get_module_setting("defacedname");
		if ($session['user']['location'] == $city){
			$args['text']=array("`&`c`b%s`b`c`n`7You are standing in a wonderland of white. Crystal icicles wink at you from the eaves of the buildings and huts nearby. Villagers are chatting, their words emerging in frosty fog.`n", $city, $city, $deface);
			$args['schemas']['text'] = "module-icetown";
			$args['clock']="`n`7The clock on the church reads `&%s`7.`n";
			$args['schemas']['clock'] = "module-icetown";
			if (is_module_active("calendar")) {
				$args['calendar']="`n`7The calendar on the nativity display reads `&%s`7, `&%s %s %s`7.`n";
				$args['schemas']['calendar'] = "module-icetown";
			}
			$args['title']=array("%s, the Ice Town", $city);
			$args['schemas']['title'] = "module-icetown";
			$args['sayline']="`7converses`3";
			$args['schemas']['sayline'] = "module-icetown";
			$args['talk']="`n`&Nearby some visitors excitedly chat:`n";
			$args['schemas']['talk'] = "module-icetown";
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
			blockmodule("spookygold");
			blockmodule("scavenge");
			blockmodule("caravan");
			// why would you see someone outside the clan halls that don't
			// exist in this village
			blockmodule("clantrees");
			$args['schemas']['newest'] = "module-icetown";
			$args['gatenav']="Village Gates";
			$args['schemas']['gatenav'] = "module-icetown";
			$args['fightnav']="Sparring Street";
			$args['schemas']['fightnav'] = "module-icetown";
			$args['marketnav']="Avenue of Ice";
			$args['schemas']['marketnav'] = "module-icetown";
			$args['tavernnav']="Blizzard Lane";
			$args['schemas']['tavernnav'] = "module-icetown";
			$args['section']="village-icetown";
			$args['infonav']="Snowflakes";
			$args['schemas']['infonav'] = "module-icetown";
		}
		break;
	case "village":
		if ($session['user']['location']==$city){
			tlschema($args['schemas']['gatenav']);
			addnav($args['gatenav']);
			tlschema();
			addnav("S?See the Slopes","pvp.php?campsite=1");
		}
		break;
	}
	return $args;
}

function icetown_run(){
}
?>
