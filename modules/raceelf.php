<?php
// translator ready
// addnews ready
// mail ready

function raceelf_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Elf",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"Races",
		"download"=>"core_module",
		"settings"=>array(
			"Elven Race Settings,title",
			"villagename"=>"Name for the elven village|Glorfindal",
			"minedeathchance"=>"Chance for Elves to die in the mine,range,0,100,1|90",
		),
	);
	return $info;
}

function raceelf_install(){
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("villagetext");
	module_addhook("travel");
	module_addhook("validlocation");
	module_addhook("validforestloc");
	module_addhook("moderate");
	module_addhook("changesetting");
	module_addhook("raceminedeath");
	module_addhook("pvpadjust");
	module_addhook("adjuststats");
	module_addhook("racenames");
	module_addhook("weaponstext");
	return true;
}

function raceelf_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	// Force anyone who was a Elf to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Elf'";
	db_query($sql);
	if ($session['user']['race'] == 'Elf')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function raceelf_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	//to handle this.
	// Pass it in via args?
	global $session,$resline;
	$city = get_module_setting("villagename");
	$race = "Elf";
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "pvpadjust":
		if ($args['race'] == $race) {
			$args['creaturedefense']+=(1+floor($args['creaturelevel']/5));
		}
		break;
	case"adjuststats":
		if ($args['race'] == $race) {
			$args['defense'] += (1+floor($args['level']/5));
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
		}
		break;
	case "changesetting":
		// Ignore anything other than villagename setting changes
		if ($args['setting'] == "villagename" && $args['module']=="raceelf") {
			if ($session['user']['location'] == $args['old'])
				$session['user']['location'] = $args['new'];
			$sql = "UPDATE " . db_prefix("accounts") .
				" SET location='" . addslashes($args['new']) .
				"' WHERE location='" . addslashes($args['old']) . "'";
			db_query($sql);
			if (is_module_active("cities")) {
				$sql = "UPDATE " . db_prefix("module_userprefs") .
					" SET value='" . addslashes($args['new']) .
					"' WHERE modulename='cities' AND setting='homecity'" .
					"AND value='" . addslashes($args['old']) . "'";
				db_query($sql);
			}
		}
		break;
	case "chooserace":
		output("<a href='newday.php?setrace=$race$resline'>High among the trees</a> of the %s forest, in frail looking elaborate `^Elvish`0 structures that look as though they might collapse under the slightest strain, yet have existed for centuries.`n`n", $city, true);
		addnav("`^Elf`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`^As an elf, you are keenly aware of your surroundings at all times; very little ever catches you by surprise.`n");
			output("You gain extra defense!");
			if (is_module_active("cities")) {
				if ($session['user']['dragonkills']==0 &&
						$session['user']['age']==0){
					//new farmthing, set them to wandering around this city.
					set_module_setting("newest-$city",
							$session['user']['acctid'],"cities");
				}
				set_module_pref("homecity",$city,"cities");
				if ($session['user']['age'] == 0)
					$session['user']['location']=$city;
			}
		}
		break;
	case "newday":
		if ($session['user']['race']==$race){
			raceelf_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`@Elvish Awareness`0",
				"defmod"=>"(<defense>?(1+((1+floor(<level>/5))/<defense>)):0)",
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-raceelf",
				)
			);
		}
		break;
	case "validforestloc":
	case "validlocation":
		if (is_module_active("cities"))
			$args[$city]="village-$race";
		break;
	case "moderate":
		if (is_module_active("cities")) {
			tlschema("commentary");
			$args["village-$race"]=sprintf_translate("City of %s", $city);
			tlschema();
		}
		break;
	case "travel":
		$capital = getsetting("villagename", LOCATION_FIELDS);
		$hotkey = substr($city, 0, 1);
		$ccity=urlencode($city);
		tlschema("module-cities");
		if ($session['user']['location']==$capital){
			addnav("Safer Travel");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$ccity");
		}elseif ($session['user']['location']!=$city){
			addnav("More Dangerous Travel");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$ccity&d=1");
		}
		if ($session['user']['superuser'] & SU_EDIT_USERS){
			addnav("Superuser");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$ccity&su=1");
		}
		tlschema();
		break;
	case "villagetext":
		raceelf_checkcity();
		if ($session['user']['location'] == $city){
			$args['text']=array("`^`c`b%s, Ancestral Home of the Elves`b`c`n`6You stand on the forest floor.  %s rises about you, appearing to be one with the forest.  Ancient, frail-looking buildings appear to grow from the forest floor, the tree limbs, and on the very treetops.  The magnificent trees clutch delicately to these homes of elves.  Bright motes of light swirl around you as you move about.`n", $city, $city);
			$args['schemas']['text'] = "module-raceelf";
			$args['clock']="`n`6Capturing one of the tiny lights, you peer delicately into your hands.`nThe fairy within tells you that it is `^%s`6 before disappearing in a tiny sparkle.`n";
			$args['schemas']['clock'] = "module-raceelf";
			if (is_module_active("calendar")) {
				$args['calendar']="`n`6Another fairy whispers in your ear, \"`^Today is `&%3\$s %2\$s`^, `&%4\$s`^.  It is `&%1\$s`^.`6\"`n";
				$args['schemas']['calendar'] = "modules-raceelf";
			}
			$args['title']= array("%s City", $city);
			$args['schemas']['title'] = "module-raceelf";
			$args['sayline']="converses";
			$args['schemas']['sayline'] = "module-raceelf";
			$args['talk']="`n`^Nearby some villagers converse:`n";
			$args['schemas']['talk'] = "module-raceelf";
			$new = get_module_setting("newest-$city", "cities");
			if ($new != 0) {
				$sql =  "SELECT name FROM " . db_prefix("accounts") .
					" WHERE acctid='$new'";
				$result = db_query_cached($sql, "newest-$city");
				$row = db_fetch_assoc($result);
				$args['newestplayer'] = $row['name'];
				$args['newestid']=$new;
			} else {
				$args['newestplayer'] = $new;
				$args['newestid']="";
			}
			if ($new == $session['user']['acctid']) {
				$args['newest']="`n`6You stare around in wonder at the excessively tall buildings and feel just a bit queasy at the prospect of looking down from those heights.";
			} else {
				$args['newest']="`n`6Looking at the buildings high above, and looking a little queasy at the prospect of such heights is `^%s`6.";
			}
			$args['schemas']['newest'] = "module-raceelf";
			$args['gatenav']="Village Gates";
			$args['schemas']['gatenav'] = "module-raceelf";
			$args['fightnav']="Honor Avenue";
			$args['schemas']['fightnav'] = "module-raceelf";
			$args['marketnav']="Mercantile";
			$args['schemas']['marketnav'] = "module-raceelf";
			$args['tavernnav']="Towering Halls";
			$args['schemas']['tavernnav'] = "module-raceelf";
			$args['section']="village-$race";
			$args['schemas']['weaponshop'] = "module-raceelf";
			$args['weaponshop'] = "Gadriel's Weapons";
		}
		break;
	case "weaponstext":
		$tradeinvalue = round(($session['user']['weaponvalue']*.75),0);
		if ($session['user']['location'] == $city) {
			$args['schemas']['title'] = "module-raceelf";
			$args['title']="Gadriel's Weapons";
			$args['schemas']['desc'] = "module-raceelf";
			$args['schemas']['tradein'] = "module-raceelf";
			if ($session['user']['race'] == $race) {
				$args['desc'] = array(
					"`7The Elven Ranger pads gracefully towards you as you enter, examining you from head to foot, an expression of piqued interest upon his fine elven features.",
					"The tiny elf has magnificent blond hair reaching almost to his knees, and he spends a long moment memorizing your every facial feature.",
					"His assessment finally concluded, a gleam settles in his eyes, and a small smile graces his expression.`n`n",
					"`5\"You will be richly rewarded for respecting the acquired skill of your own blood,`7\" he says. `5\"Before you lie the greatest feats of workmanship known in all the lands, the grace and power that only the Elves can create. To wield an Elven weapon is an honor unsurpassed.`7\"",
					array("`7You warm to his pride in your shared Elven blood, and proudly display your %s.`n`n",$session['user']['weapon']),
				);
				$args['tradein'] = array(
					array("`5Gadriel`7 looks at you and says, \"`5I'll give you `^%s`5 trade-in value for your `~%s`5.", $tradeinvalue, $session['user']['weapon']),
					array("Tell me which instrument of battle you wish to own.\""),
				);
			}else{
				$args['desc'] = array(
					"`7The Elven Ranger pads gracefully towards you as you enter, examining you from head to foot, an expression of piqued interest upon his fine elven features.",
					"The tiny elf has magnificent blond hair reaching almost to his knees, and he spends a long moment memorizing your every facial feature.",
					"Gleaming eyes narrow as his assessment is concluded, and his face becomes a hardened mask which sets your teeth on edge. Despite his tiny stature, you feel intimidated, and he revels in your discomfort.`n`n",
					"`7Speaking at last, his words are measured and calculated. `5\"You have shown surprising intelligence,`7\" he barks coldly, `5\"for having sought the finest workmanship in all the lands. No other being can hope to posess the grace and pure power of we Elves. No other weapons have one tenth the nobility and quality of what you see here. And no creature shall wield them as well as an Elf would, though you may try as you might.`7\"",
					array("`7You attempt bravado in the face of such arrogance, and thrust forward your %s for inspection.`n`n",$session['user']['weapon']),
				);
				$args['tradein'] = array(
					array("`5Gadriel`7 takes your `5%s`7 and examines it again quitely. After some seconds he gives it back to you with a cold look in his eyes.", $session['user']['weapon']),
					array("`5\"`^%s`5 gold is all I can give you for this.\"`7, he says.`n`n", $tradeinvalue),
				);
			}
			$args['schemas']['payweapon'] = "module-raceelf";
			$args['payweapon'] = "Gadriel takes your `5%s`7 and puts it on a rack behind him. Then, with a flourish, he pick up a new `5%s`7, deftly demonstrating its use, before handing it to you with gallantry and grace.";
		}
		break;
	}
	return $args;
}

function raceelf_checkcity(){
	global $session;
	$race="Elf";
	$city=get_module_setting("villagename");

	if ($session['user']['race']==$race && is_module_active("cities")){
		//if they're this race and their home city isn't right, set it up.
		if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
			set_module_pref("homecity",$city,"cities");
		}
	}
	return true;
}

function raceelf_run(){

}
?>
