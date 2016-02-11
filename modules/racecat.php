<?php
// translator ready
// addnews ready
// mail ready

/* Felyne Race */
/* ver 1.0 */
/* Shannon Brown => SaucyWench -at- gmail -dot- com */
/* concept by JC Petersen */

function racecat_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Felyne",
		"version"=>"1.0",
		"author"=>"Shannon Brown",
		"category"=>"Races",
		"download"=>"core_module",
		"settings"=>array(
			"Felyne Race Settings,title",
			"minedeathchance"=>"Percent chance for Felynes to die in the mine,range,0,100,1|40",
			"gemchance"=>"Percent chance for Felynes to find a gem on battle victory,range,0,100,1|5",
			"gemmessage"=>"Message to display when finding a gem|`&Your Felyne senses tingle, and you notice a `%gem`&!",
			"goldloss"=>"How much less gold (in percent) do the Felynes find?,range,0,100,1|15",
			"mindk"=>"How many DKs do you need before the race is available?,int|0",
		),
	);
	return $info;
}

function racecat_install(){
	// The felines share the city with humans, so..
	if (!is_module_installed("racehuman")) {
		output("The Felyne only choose to live with humans.   You must install that race module.");
		return false;
	}

	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
//	module_addhook("charstats");
	module_addhook("raceminedeath");
	module_addhook("alter-gemchance");
	module_addhook("creatureencounter");
	module_addhook("pvpadjust");
	module_addhook("adjuststats");
	module_addhook("racenames");
	return true;
}

function racecat_uninstall(){
	global $session;
	// Force anyone who was a Felyne to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Felyne'";
	db_query($sql);
	if ($session['user']['race'] == 'Felyne')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racecat_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	//to handle this.
	// It could be passed as a hook arg?
	global $session,$resline;

	if (is_module_active("racehuman")) {
		$city = get_module_setting("villagename", "racehuman");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Felyne";
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "pvpadjust":
		if ($args['race'] == $race) {
			$args['creaturedefense']+=(2+floor($args['creaturelevel']/5));
			$args['creaturehealth']-= round($args['creaturehealth']*.05, 0);
		}
		break;
	case"adjuststats":
		if ($args['race'] == $race) {
			$args['defense'] += (2+floor($args['level']/5));
			$args['maxhitpoints'] -= round($args['maxhitpoints']*.05, 0);
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Fortunately your felyne athleticism lets you escape unscathed.`n";
			$args['schema']="module-racecat";
		}
		break;
	case "charstats":
		if ($session['user']['race']==$race){
			addcharstat("Vital Info");
			addcharstat("Race", translate_inline($race));
		}
		break;
	case "chooserace":
		if ($session['user']['dragonkills'] < get_module_setting("mindk"))
			break;
		output("<a href='newday.php?setrace=Felyne$resline'>On the plains surrounding the city of %s</a>, the city of men, your race of `5Felynes`0, or cat-people in the tongue of the city men, travelled behind the great herds for generations.  Your nimble agility allows you to visit places that larger races can only dream of.`n`n",$city, true);
		addnav("`5Felyne`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){ // it helps if you capitalize correctly
			output("`&As a Felyne, your cat-like reflexes allow you to respond very quickly to any attacks.`n");
			output("You gain extra defense!`n");
			output("You have the eye for glittering gems typical of your race, but your childhood as a nomad has left you less knowledgable about currency.`n");
			output("You gain extra gems from forest fights, but you also do not gain as much gold!");
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
	case "alter-gemchance":
		global $options;
		if ($session['user']['race'] == $race && $options['type'] == "forest") {
			if ($session['user']['level'] < 15) {
				$args['chance'] = round($args['chance'] * (1-get_module_setting("gemchance")));
			}
		}
		break;
	// Lets actually lower their gold a bit.. really
	case "creatureencounter":
		if ($session['user']['race']==$race){
			//get those folks who haven't manually chosen a race
			racecat_checkcity();
			$loss = (100 - get_module_setting("goldloss"))/100;
			$args['creaturegold']=round($args['creaturegold']*$loss,0);
		}
		break;
	case "newday":
		if ($session['user']['race']==$race){
			racecat_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`@Cat-like Reflexes`0",
				"defmod"=>"(<defense>?(1+((1+floor(<level>/5))/<defense>)):0)",
				"badguydmgmod"=>1.05,
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racecat",
				)
			);
		}
		break;
	}

	return $args;
}

function racecat_checkcity(){
	global $session;
	$race="Felyne";
	if (is_module_active("racehuman")) {
		$city = get_module_setting("villagename", "racehuman");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}

	if ($session['user']['race']==$race && is_module_active("cities")){
		//if they're this race and their home city isn't right, set it up.
		if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
			set_module_pref("homecity",$city,"cities");
		}
	}
	return true;
}

function racecat_run(){
}
?>
