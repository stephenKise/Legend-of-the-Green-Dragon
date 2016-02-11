<?php
// translator ready
// addnews ready
// mail ready

function racehuman_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Human",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"Races",
		"download"=>"core_module",
		"settings"=>array(
			"Human Race Settings,title",
			"villagename"=>"Name for the human village|Romar",
			"minedeathchance"=>"Chance for Humans to die in the mine,range,0,100,1|90",
			"bonus"=>"How many extra forest fights for humans?,range,1,3,1|2",
		),
	);
	return $info;
}

function racehuman_install(){
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("villagetext");
	module_addhook("stabletext");
	module_addhook("travel");
	module_addhook("validlocation");
	module_addhook("validforestloc");
	module_addhook("moderate");
	module_addhook("changesetting");
	module_addhook("raceminedeath");
	module_addhook("stablelocs");
	module_addhook("racenames");
	return true;
}

function racehuman_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	// Force anyone who was a Human to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Human'";
	db_query($sql);
	if ($session['user']['race'] == 'Human')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racehuman_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	// to handle this.
	// Pass it as an arg?
	global $session,$resline;
	$city = get_module_setting("villagename");
	$race = "Human";
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
		}
		break;
	case "changesetting":
		// Ignore anything other than villagename setting changes
		if ($args['setting'] == "villagename" && $args['module']=="racehuman") {
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
		output("`0<a href='newday.php?setrace=$race$resline'>On the plains in the city of %s</a>, the city of `&men`0; always following your father and looking up to his every move, until he sought out the `@Green Dragon`0, never to be seen again.`n`n", $city, true);
		addnav("`&Human`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			$bonus = get_module_setting("bonus");
			$one = translate_inline("an");
			$two = translate_inline("two");
			$three = translate_inline("three");
			$word = $bonus==1?$one:$bonus==2?$two:$three;
			$fight = translate_inline("fight");
			$fights = translate_inline("fights");
			output("`&As a human, your size and strength permit you the ability to effortlessly wield weapons, tiring much less quickly than other races.`n`^You gain %s extra forest %s each day!", $word, $bonus==1?$fight:$fights);
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
			racehuman_checkcity();

			$bonus = get_module_setting("bonus");
			$one = translate_inline("an");
			$two = translate_inline("two");
			$three = translate_inline("three");
			$word = $bonus==1?$one:$bonus==2?$two:$three;
			$fight = translate_inline("fight");
			$fights = translate_inline("fights");

			$args['turnstoday'] .= ", Race (human): $bonus";
			$session['user']['turns']+=$bonus;
			$fight = translate_inline("fight");
			$fights = translate_inline("fights");
			output("`n`&Because you are human, you gain `^%s extra`& forest fights for today!`n`0", $word, $bonus==1?$fight:$fights);
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
		racehuman_checkcity();
		if ($session['user']['location'] == $city){
			$args['text']=array("`&`c`b%s, City of Men`b`c`n`7You are standing in the heart of %s.  Though called a city, this stronghold of humans is little more than a fortified village.  The city's low defensive walls are surrounded by rolling plains which gradually turn into thick forest in the distance.  Some residents are engaged in conversation around the well in the village square.`n", $city, $city);
			$args['schemas']['text'] = "module-racehuman";
			$args['clock']="`n`7The great sundial at the heart of the city reads `&%s`7.`n";
			$args['schemas']['clock'] = "module-racehuman";
			if (is_module_active("calendar")) {
				$args['calendar'] = "`n`7A smaller contraption next to it reads `&%s`7, `&%s %s %s`7.`n";
				$args['schemas']['calendar'] = "module-racehuman";
			}
			$args['title']=array("%s, City of Men", $city);
			$args['schemas']['title'] = "module-racehuman";
			$args['sayline']="says";
			$args['schemas']['sayline'] = "module-racehuman";
			$args['talk']="`n`&Nearby some villagers talk:`n";
			$args['schemas']['talk'] = "module-racehuman";
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
				$args['newest']="`n`7As you wander your new home, you feel your jaw dropping at the wonders around you.";
			} else {
				$args['newest']="`n`7Wandering the village, jaw agape, is `&%s`7.";
			}
			$args['schemas']['newest'] = "module-racehuman";
			$args['section']="village-$race";
			$args['stablename']="Bertold's Bestiary";
			$args['schemas']['stablename'] = "module-racehuman";
			$args['gatenav']="Village Gates";
			$args['schemas']['gatenav'] = "module-racehuman";
			unblocknav("stables.php");
		}
		break;
	case "stabletext":
		if ($session['user']['location'] != $city) break;
		$args['title'] = "Bertold's Bestiary";
		$args['schemas']['title'] = "module-racehuman";
		$args['desc'] = array(
			"`6Just outside the outskirts of the village, a training area and riding range has been set up.",
			"Many people from all across the land mingle as Bertold, a strapping man with a wind-weathered face, extols the virtues of each of the creatures in his care.",
			array("As you approach, Bertold smiles broadly, \"`^Ahh! how can I help you today, my %s?`6\" he asks in a booming voice.", translate_inline($session['user']['sex']?'lass':'lad', 'stables'))
		);
		$args['schemas']['desc'] = "module-racehuman";
		$args['lad']="friend";
		$args['schemas']['lad'] = "module-racehuman";
		$args['lass']="friend";
		$args['schemas']['lass'] = "module-racehuman";
		$args['nosuchbeast']="`6\"`^I'm sorry, I don't stock any such animal.`6\", Bertold say apologetically.";
		$args['schemas']['nosuchbeast'] = "module-racehuman";
		$args['finebeast']=array(
			"`6\"`^Yes, yes, that's one of my finest beasts!`6\" says Bertold.`n`n",
			"`6\"`^Not even Merick has a finer specimen than this!`6\" Bertold boasts.`n`n",
			"`6\"`^Doesn't this one have fine musculature?`6\" he asks.`n`n",
			"`6\"`^You'll not find a better trained creature in all the land!`6\" exclaims Bertold.`n`n",
			"`6\"`^And a bargain this one'd be at twice the price!`6\" booms Bertold.`n`n",
			);
		$args['schemas']['finebeast'] = "module-racehuman";
		$args['toolittle']="`6Bertold looks over the gold and gems you offer and turns up his nose, \"`^Obviously you misheard my price.  This %s will cost you `&%s `^gold  and `%%s`^ gems and not a penny less.`6\"";
		$args['schemas']['toolittle'] = "module-racehuman";
		$args['replacemount']="`6Patting %s`6 on the rump, you hand the reins as well as the money for your new creature, and Bertold hands you the reins of a `&%s`6.";
		$args['schemas']['replacemount'] = "module-racehuman";
		$args['newmount']="`6You hand over the money for your new creature, and Bertold hands you the reins of a new `&%s`6.";
		$args['schemas']['newmount'] = "module-racehuman";
		$args['nofeed']="`6\"`^I'm terribly sorry %s, but I don't stock feed here.  I'm not a common stable after all!  Perhaps you should look elsewhere to feed your creature.`6\"";
		$args['schemas']['nofeed'] = "module-racehuman";
		$args['nothungry']="`&%s`6 picks briefly at the food and then ignores it.  Bertold, being honest, shakes his head and hands you back your gold.";
		$args['schemas']['nothungry'] = "module-racehuman";
		$args['halfhungry']="`&%s`6 dives into the provided food and gets through about half of it before stopping.  \"`^Well, %s wasn't as hungry as you thought.`6\" says Bertold as he hands you back all but %s gold.";
		$args['schemas']['halfhungry'] = "module-racehuman";
		$args['hungry']="`6%s`6 seems to inhale the food provided.  %s`6, the greedy creature that it is, then goes snuffling at Bertold's pockets for more food.`nBertold shakes his head in amusement and collects `&%s`6 gold from you.";
		$args['schemas']['hungry'] = "module-racehuman";
		$args['mountfull']="`n`6\"`^Well, %s, your %s`^ is full up now.  Come back tomorrow if it hungers again, and I'll be happy to sell you more.`6\" says Bertold with a genial smile.";
		$args['schemas']['mountfull'] = "module-racehuman";
		$args['nofeedgold']="`6\"`^I'm sorry, but that is just not enough money to pay for food here.`6\"  Bertold turns his back on you, and you lead %s away to find other places for feeding.";
		$args['schemas']['nofeedgold'] = "module-racehuman";
		$args['confirmsale']="`n`n`6Bertold eyes your mount up and down, checking it over carefully.  \"`^Are you quite sure you wish to part with this creature?`6\"";
		$args['schemas']['confirmsale'] = "module-racehuman";
		$args['mountsold']="`6With but a single tear, you hand over the reins to your %s`6 to Bertold's stableboy.  The tear dries quickly, and the %s in hand helps you quickly overcome your sorrow.";
		$args['schemas']['mountsold'] = "module-racehuman";
		$args['offer']="`n`n`6Bertold strokes your creature's flank and offers you `&%s`6 gold and `%%s`6 gems for %s`6.";
		$args['schemas']['offer'] = "module-racehuman";
		break;
	case "stablelocs":
		tlschema("mounts");
		$args[$city]=sprintf_translate("The Village of %s", $city);
		tlschema();
		break;
	}
	return $args;
}

function racehuman_checkcity(){
	global $session;
	$race="Human";
	$city=get_module_setting("villagename");

	if ($session['user']['race']==$race && is_module_active("cities")){
		//if they're this race and their home city isn't right, set it up.
		if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
			set_module_pref("homecity",$city,"cities");
		}
	}
	return true;
}

function racehuman_run(){

}
?>
