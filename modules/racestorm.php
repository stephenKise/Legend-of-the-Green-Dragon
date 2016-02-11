<?php
// translator ready
// addnews ready
// mail ready

// V1.01 -- moved the check for enough points to be consistant with other
//          modules for the lodge.

function racestorm_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Storm Giant",
		"version"=>"1.01",
		"author"=>"Chris Vorndran",
		"category"=>"Races",
		"download"=>"core_module",
		"requires"=>array(
			"racedwarf"=>"1.0|By Eric Stevens,part of core download",
		),
		"settings"=>array(
			"Storm Giant Race Settings,title",
			"minedeathchance"=>"Percent chance for a Storm Giant to die in the mine,range,0,100,1|10",
			"cost"=>"Cost of Race in Lodge Points,int|100",
		),
		"prefs"=>array(
			"Storm Giant Preferences,title",
			"bought"=>"Has Storm Giant race been bought,bool|0",
		)
	);
	return $info;
}

function racestorm_install(){
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("raceminedeath");
	module_addhook("pvpadjust");
	module_addhook("adjuststats");
	module_addhook("pointsdesc");
	module_addhook("lodge");
	module_addhook("racenames");
	module_addhook("battle-victory");
	module_addhook("battle-defeat");
	return true;
}

function racestorm_uninstall(){
	global $session;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Storm Giant'";
	db_query($sql);
	if ($session['user']['race'] == 'Storm Giant')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racestorm_dohook($hookname,$args){
	global $session,$resline;

	if (is_module_active("racedwarf")) {
		$city = get_module_setting("villagename", "racedwarf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Storm Giant";
	$cost = get_module_setting("cost");
	$bought = get_module_pref("bought");
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "pointsdesc":
		$args['count']++;
		$format = $args['format'];
		$str = translate("The race: Storm Giant; The Masters of the Hillfolk.  This costs %s points");
		$str = sprintf($str, $cost);
		output($format, $str, true);
		break;
	case "pvpadjust":
		if ($args['race'] == $race) {
			apply_buff("targetracialbenefit",array(
				"name"=>"",
				"minioncount"=>1,
				"mingoodguydamage"=>0,
				"maxgoodguydamage"=>3+ceil($args['creaturelevel']/2),
				"effectmsg"=>"`#{badguy} `#brings a Boulder crashing down on you for `\${damage}`# damage.",
				"effectnodmgmsg"=>"`#{badguy} flings a boulder at you,`# but it `\$MISSES`)!",
				"allowinpvp"=>1,
				"rounds"=>-1,
				"schema"=>"module-racestorm",
				)
			);
			$args['creaturedefense']+=(2+floor($args['creaturelevel']/3));
		}
		break;
	case "adjuststats":
		if ($args['race'] == $race) {
			$args['defense']+=(2+floor($args['level']/3));
		}
		break;
	case "battle-victory":
	case "battle-defeat":
		global $options;
		if ($options['type'] == 'pvp') {
			strip_buff("targetracialbenefit");
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "The massive girth of your Storm Giant muscles, allows you to escape unharmed.`n";
			$args['schema']="module-racestorm";
		}
		break;
	case "chooserace":
		if ($bought == 1){
		output("<a href='newday.php?setrace=$race$resline'>Over the mountains of %s</a>, the city of dwarves, your race of `#Storm Giants `3dwell in the caverns.",$city, true);
		output("They are the masters of the Mountain Halls, and hold all at their command.`n`n");
		addnav("`^Storm Giant`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		}
		break;
	case "lodge":
		if (!$bought) {
			addnav(array("Acquire Storm Giant blood (%s points)",$cost),
					"runmodule.php?module=racestorm&op=start");
		}
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`#Storm Giants `3are known for their superb strength, and their mastership over land masses.`n");
			output("`3You lift up a `qBoulder`3 and carry it into battle!`n");
			if (is_module_active("cities")) {
				if ($session['user']['dragonkills']==0 &&
						$session['user']['age']==0){
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
			racestorm_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`#Storm Giant's Boulder`0",
				"minioncount"=>1,
				"minbadguydamage"=>0,
				"maxbadguydamage"=>"3+ceil(<level>/2)",
				"effectmsg"=>"`#You lift your Boulder and it comes crashing down on {badguy}`# for `^{damage}`# damage.",
				"effectnodmgmsg"=>"`#The boulder is flung at {badguy},`# but it `\$MISSES`)!",
				"defmod"=>"(<defense>?(1+((2+floor(<level>/3))/<defense>)):0)",
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racestorm",
				)
			);
		}
		break;
	}

	return $args;
}

function racestorm_checkcity(){
	global $session;
	$race = "Storm Giant";
	if (is_module_active("racedwarf")) {
		$city = get_module_setting("villagename", "racedwarf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}

	if ($session['user']['race']==$race && is_module_active("cities")){
		if (get_module_pref("homecity","cities")!=$city){
			set_module_pref("homecity",$city,"cities");
		}
	}
	return true;
}

function racestorm_run(){
	global $session;
	page_header("Hunter's Lodge");
	$race = 'Storm Giant';
	$cost = get_module_setting("cost");
	$bought = get_module_pref("bought");
	$op = httpget('op');

	switch ($op){
		case "start":
			$pointsavailable = $session['user']['donation'] -
			$session['user']['donationspent'];
			if ($pointsavailable >= $cost && $bought == 0){
				output("`3J. C. Petersen looks upon you with a caustic grin.`n`n");
				output("\"`\$So, you wish to purchase the `^Storm Giant Blood`\$?`3\" he says with a smile.");
				addnav("Choices");
				addnav("Yes","runmodule.php?module=racestorm&op=yes");
				addnav("No","runmodule.php?module=racestorm&op=no");
			} else {
				output("`3J. C. Petersen stares at you for a moment then looks away as you realize that you don't have enough points to purchase this item.");
			}
			break;
		case "yes":
			output("`3J. C. Petersen hands you a tiny vial, with a cold crimson liquid in it.`n`n");
			output("\"`\$That is pure `^Storm Giant's Blood`\$.");
			output("Now, drink it all up!`3\"`n`n");
			output("You double over, spasming on the ground.");
			output("J. C. Petersen grins, \"`\$Your body shall finish its change upon newday... I suggest you rest.`3\"");
			$session['user']['race'] = $race;
			$session['user']['donationspent'] += $cost;
			set_module_pref("bought",1);
			break;
		case "no":
			output("`3J. C. Petersen looks at you and shakes his head.");
			output("\"`\$I swear to you, this stuff is top notch.");
			output("This isn't like the crud that %s `\$is selling.`3\"", getsetting("barkeep", "`tCedrik"));
			break;
	}
	addnav("Return");
	addnav("L?Return to the Lodge","lodge.php");
	page_footer();
}
?>
