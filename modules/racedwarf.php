<?php
// translator ready
// addnews ready
// mail ready

function racedwarf_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Dwarf",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"Races",
		"download"=>"core_module",
		"settings"=>array(
			"Dwarven Race Settings,title",
			"villagename"=>"Name for the dwarven village|Qexelcrag",
			"minedeathchance"=>"Chance for Dwarves to die in the mine,range,0,100,1|5",
		),
		"prefs-drinks"=>array(
			"Dwarven Race Drink Preferences,title",
			"servedkeg"=>"Is this drink served in the dwarven inn?,bool|0",
		),
	);
	return $info;
}

function racedwarf_install(){
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("creatureencounter");
	module_addhook("villagetext");
	module_addhook("travel");
	module_addhook("village");
	module_addhook("validlocation");
	module_addhook("validforestloc");
	module_addhook("moderate");
	module_addhook("drinks-text");
	module_addhook("changesetting");
	module_addhook("drinks-check");
	module_addhook("raceminedeath");
	module_addhook("racenames");
	module_addhook("camplocs");
	module_addhook("mercenarycamptext");
	$sql = "SELECT companionid FROM ".db_prefix("companions")." WHERE name = 'Grizzly Bear'";
	$result = db_query($sql);
	if (db_num_rows($result) == 0) {
		$sql = "INSERT INTO " . db_prefix("companions") . " (`companionid`, `name`, `category`, `description`, `attack`, `attackperlevel`, `defense`, `defenseperlevel`, `maxhitpoints`, `maxhitpointsperlevel`, `abilities`, `cannotdie`, `cannotbehealed`, `companionlocation`, `companionactive`, `companioncostdks`, `companioncostgems`, `companioncostgold`, `jointext`, `dyingtext`, `allowinshades`, `allowinpvp`, `allowintrain`) VALUES (0, 'Grizzly Bear', 'Wild Beasts', 'You look at the beast knowing that this Grizzly Bear will provide an effective block against attack with its long curved claws and massive body of silver-tipped fur.', 1, 2, 5, 2, 25, 25, 'a:4:{s:5:\"fight\";s:1:\"0\";s:4:\"heal\";s:1:\"0\";s:5:\"magic\";s:1:\"0\";s:6:\"defend\";s:1:\"1\";}', 0, 0, '".get_module_setting("villagename", "racedwarf")."', 1, 0, 4, 600, 'You hear a low, deep belly growl coming from a shadowed corner of the Bestiarium.  Curious you walk over to investigate your purchase. As you approach a large form shuffles on all four legs towards the front of its hewn rock enclosure.`n`nThe hunched shoulders of the largest bear you have ever seen ripple as its front haunches push against the ground causing it to stand on its hind legs.  It makes another low growl before dropping back on all four legs to follow you on your adventure.', 'The grizzly gets scared by the multitude of blows and hits he has to take and flees into the forest.', 1, 0, 0)";
		db_query($sql);
		debug("Inserted new companion: Grizzly Bear");
	}
	return true;
}

function racedwarf_uninstall(){
	global $session;
	$vname = get_module_setting("villagename", "racedwarf");;
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	// Force anyone who was a Dwarf to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Dwarf'";
	db_query($sql);
	if ($session['user']['race'] == 'Dwarf')
		$session['user']['race'] = RACE_UNKNOWN;
	$sql = "UPDATE ". db_prefix("companions") ." SET location='all' WHERE location ='$vname'";
	db_query($sql);
	return true;
}

function racedwarf_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	//to handle this.
	// It could be passed as a hook arg?
	global $session,$resline;
	$city = get_module_setting("villagename");
	$race = "Dwarf";
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Fortunately your dwarven skill let you escape unscathed.`n";
			$args['schema'] = "module-racedwarf";
		}
		break;
	case "changesetting":
		// Ignore anything other than villagename setting changes for myself
		if ($args['setting'] == "villagename" && $args['module']=="racedwarf") {
			if ($session['user']['location'] == $args['old'])
				$session['user']['location'] = $args['new'];
			$sql = "UPDATE " . db_prefix("accounts") .
				" SET location='" . addslashes($args['new']) .
				"' WHERE location='" . addslashes($args['old']) . "'";
			db_query($sql);
			$sql = "UPDATE ".db_prefix("companions")." SET location='".$args['new']." WHERE location='".$args['old']."'";
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
		output("<a href='newday.php?setrace=$race$resline'>Deep in the subterranean strongholds of %s</a>, home to the noble and fierce `#Dwarven`0 people whose desire for privacy and treasure bears no resemblance to their tiny stature.`n`n", $city, true);
		addnav("`#Dwarf`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`#As a dwarf, you are more easily able to identify the value of certain goods.`n");
			output("`^You gain extra gold from forest fights!");
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
	case "validforestloc":
	case "validlocation":
		if (is_module_active("cities"))
			$args[$city] = "village-$race";
		break;
	case "moderate":
		if (is_module_active("cities")) {
			tlschema("commentary");
			$args["village-$race"]=sprintf_translate("City of %s", $city);
			tlschema();
		}
		break;
	case "creatureencounter":
		if ($session['user']['race']==$race){
			//get those folks who haven't manually chosen a race
			racedwarf_checkcity();
			$args['creaturegold']=round($args['creaturegold']*1.2,0);
		}
		break;
	case "travel":
		$capital = getsetting("villagename", LOCATION_FIELDS);
		$hotkey = substr($city, 0, 1);
		$ccity = urlencode($city);
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
		racedwarf_checkcity();
		if ($session['user']['location'] == $city){
			// Do this differently
			$args['text']=array("`#`c`bCavernous %s, home of the dwarves`b`c`n`3Deep in the heart of Mount %s lie the ancient caverns that the Dwarves have called home for centuries.  Colossal columns, covered with deeply carved geometric shapes, stretch up into the darkness, supporting the massive weight of the mountain above.  All around you, stout dwarves discuss legendary treasures and drink heartily from mighty steins, which they readily fill from tremendous barrels nearby.`n", $city, $city);
			$args['schemas']['text'] = "module-racedwarf";
			$args['clock']="`n`3A cleverly crafted crystal prism allows a beam of light to fall through a crack in the great ceiling.`nIt illuminates age old markings carved into the cavern floor, telling you that on the surface it is `#%s`3.`n";
			$args['schemas']['clock'] = "module-racedwarf";
			if (is_module_active("calendar")) {
				$args['calendar'] = "`n`3A second prism marks out the date on the calendar as `#Year %4\$s`3, `#%3\$s %2\$s`3.`nYet a third shows the day of the week as `#%1\$s`3.`nSo finely wrought are these displays that you marvel at the cunning and skill involved.`n";
				$args['schemas']['calendar'] = "module-racedwarf";
			}
			$args['title']= array("The Caverns of %s", $city);
			$args['schemas']['title'] = "module-racedwarf";
			$args['sayline']="brags";
			$args['schemas']['sayline'] = "module-racedwarf";
			$args['talk']="`n`#Nearby some villagers brag:`n";
			$args['schemas']['talk'] = "module-racedwarf";
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
				$args['newest']="`n`3Being rather new to this life, you pound an empty stein against an ale keg in an attempt to get some of the fabulous ale therein.";
			} else {
				$args['newest']="`n`3Pounding an empty stein against a yet unopened barrel of ale, wondering how to get to the sweet nectar inside is `#%s`3.";
			}
			$args['schemas']['newest'] = "module-racedwarf";
			$args['gatenav']="Village Gates";
			$args['schemas']['gatenav'] = "module-racedwarf";
			$args['fightnav']="Th' Arena";
			$args['schemas']['fightnav'] = "module-racedwarf";
			$args['marketnav']="Ancient Treasures";
			$args['schemas']['marketnav'] = "module-racedwarf";
			$args['tavernnav']="Ale Square";
			$args['schemas']['tavernnav'] = "module-racedwarf";
			$args['mercenarycamp']="A Bestiarium";
			$args['schemas']['mercenarycamp'] = "module-racedwarf";
			$args['section']="village-$race";
			unblocknav("mercenarycamp.php");
		}
		break;
	case "village":
		if ($session['user']['location'] == $city) {
			tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
			tlschema();
			addnav("K?Great Kegs of Ale","runmodule.php?module=racedwarf&op=ale");
		}
		break;
	case "drinks-text":
		if ($session['user']['location'] != $city) break;
		$args["title"]="Great Kegs of Ale";
		$args['schemas']['title'] = "module-racedwarf";
		$args["return"]="B?Return to the Bar";
		$args['schemas']['return'] = "module-racedwarf";
		$args['returnlink']="runmodule.php?module=racedwarf&op=ale";
		$args["demand"]="Pounding your fist on the bar, you demand another drink";
		$args['schemas']['demand'] = "module-racedwarf";
		$args['barkeep'] = "`\$G`4argoyle";
		$args['schemas']['barkeep'] = "module-racedwarf";
		$args["toodrunk"]=" but `0{barkeep}`0 the bartender continues to clean the stein he was working on and growls,  \"`qNo more of my drinks for you!`0\"";
		$args['schemas']['toodrunk'] = "module-racedwarf";
		$args["toomany"]="`\$G`4argoyle`0 the bartender furrows his balding head.  \"`qYou're too weak to handle any more of `QMY`q brew.  Begone!`0\"";
		$args['schemas']['toomany'] = "module-racedwarf";
		$args['drinksubs']=array(
				"/Cedrik/"=>$args['barkeep']."`0",
				"/ Violet /"=>translate_inline(" a stranger "),
				"/ Seth /"=>translate_inline(" a stranger "),
				"/ `.Violet`. /"=>translate_inline(" a stranger "),
				"/ `.Seth`. /"=>translate_inline(" a stranger "),
				);
		break;
	case "drinks-check":
		if ($session['user']['location'] == $city) {
			$val = get_module_objpref("drinks", $args['drinkid'], "servedkeg");
			$args['allowdrink'] = $val;
		}
		break;
	case "camplocs":
		$args[$city] = sprintf_translate("The Village of %s", $city);
		break;
	case "mercenarycamptext":
		if ($session['user']['location'] == $city) {
			$args['title'] = "A Bestiarium";
			$args['schemas']['title'] = "module-racedwarf";

			$args['desc'] = array(
				"`5You are making your way to the Bestiarium deep in the bowels of the dwarven mountain stronghold.",
				"The sounds of a massive struggle echo off the hewn rock walls of the cavernous passageway.",
				"Scuffling is punctuated with the sounds of snarling and the impact of a heavy body slamming into another.`n`n",

				"As you round the corner you find yourself at the edge of an arena.",
				"Around the walls are carved out stalls which contain beasts of various shapes, sizes and abilities.`n`n",

				"In the arena, a `&white wolf `5whose size equals that of a mountain pony is lunging towards a massive `~black bear`5.",
				"`~The bear`5 on his hind legs stands as tall as an oak.",
				"It raises a paw as `&the wolf `5leaps towards him, then with a movement so quick you nearly miss it, `&the wolf `5is batted away to fall on its side.",
				"Apparently enraged, `&the wolf`5 leaps snarling to its feet to prepare to lunge again.`n`n",

				"At that moment a stocky dwarf standing at the edge of the arena raises his finger and thumb to his mouth.",
				"A piercing whistle cuts through the air.",
				"`~The black bear `5lowers himself to all fours and shakes his body, then yawns.",
				"`&The white wolf `5pauses, then lays down with his tongue hanging in a pant.",
				"Its yellow eyes never leaving you as you walk towards the dwarf.`n`n",

				"\"`tGreetings, Dwalin!`5\" you call out as you approach.",
				"\"`tI am in need of a beast to accompany me on my adventures.",
				"What do you have available this day?`5\"`n`n"
			);
			$args['schemas']['desc'] = "module-racedwarf";

			$args['buynav'] = "Buy a Beast";
			$args['schemas']['buynav'] = "module-racedwarf";

			$args['healnav'] = "";
			$args['schemas']['healnav'] = "";

			$args['healtext'] = "";
			$args['schemas']['healtext'] = "";

			$args['healnotenough'] = "";
			$args['schemas']['healnotenough'] = "";

			$args['healpaid'] = "";
			$args['schemas']['healpaid'] = "";

			// We don not want the healer in this camp.
			blocknav("mercenarycamp.php?op=heal", true);
		}
		break;
	}
	return $args;
}

function racedwarf_checkcity(){
	global $session;
	$race="Dwarf";
	$city= get_module_setting("villagename");

	if ($session['user']['race']==$race && is_module_active("cities")){
		//if they're this race and their home city isn't right, set it up.
		if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
			set_module_pref("homecity",$city,"cities");
		}
	}
	return true;
}

function racedwarf_run(){
	$op = httpget("op");
	switch($op){
	case "ale":
		require_once("lib/villagenav.php");
		page_header("Great Kegs of Ale");
		output("`3You make your way over to the great kegs of ale lined up near by, looking to score a hearty draught from their mighty reserves.");
		output("A mighty dwarven barkeep named `\$G`4argoyle`3 stands at least 4 feet tall, and is serving out the drinks to the boisterous crowd.");
		addnav("Drinks");
		modulehook("ale");
		addnav("Other");
		villagenav();
		page_footer();
		break;
	}
}
?>
