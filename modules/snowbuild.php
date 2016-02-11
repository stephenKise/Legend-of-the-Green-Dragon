<?php
// translator ready
// addnews ready
// mail ready

/*
Snowman building Contest
File:   snowbuild.php
Author: Red Yates aka Deimos
Date:   10/23/2004
Version:1.0 (10/23/2004)

Written for the Esoterra Halloween celebration on Central server, 2004.
Player pays 25(default) gold to carve a pumpkin, with 1(default) try per day.
Player has a 1:30 (~3%) chance of getting their pumpkin put on display in
the square,
and getting a small, Audrey-like buff, and  winning 5 times the price.
30% chance of winning 3 times the price as a prize.
30% chance of winning the price as a prize.

snowbuild ver 1.0 snowman building for ice town
Shannon Brown - SaucyWench - at - gmail - dot - com
11 Dec 2004
*/
function snowbuild_getmoduleinfo(){
	$info = array(
		"name"=>"Snowman Building Contest",
		"version"=>"1.0",
		"author"=>"`\$Red Yates`# & Shannon Brown",
		"download"=>"core_module",
		"category"=>"Village",
		"settings"=>array(
			"Snowman Building Module Settings, title",
			"perday"=>"Tries at building per day, int|1",
			"cost"=>"How much to carve a snowman, int|25",
			"snowbuildloc"=>"Village the snowman building is in, location|",
			"winner"=>"Whose snowman is on display, int|0",
		),
		"prefs"=>array(
			"Snowman Building User Prefs, title",
			"tries"=>"Times built today,int|0"
		),
	);
	return $info;
}

function snowbuild_install(){
	module_addhook("village");
	module_addhook("newday");
	module_addhook("changesetting");
	module_addhook("village-desc");
	return true;
}

function snowbuild_uninstall(){
	return true;
}

function snowbuild_dohook($hookname, $args){
	global $session;
	switch ($hookname){
	case "village":
		if ($session['user']['location'] == get_module_setting("snowbuildloc")){
			tlschema($args['schemas']['marketnav']);
			addnav($args["marketnav"]);
			tlschema();
			addnav("Snowman Building","runmodule.php?module=snowbuild");
		}
		break;
	case "newday":
		set_module_pref("tries",0);
		break;
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("snowbuildloc")) {
				set_module_setting("snowbuildloc", $args['new']);
			}
		}
		break;
	case "village-desc":
		$winner=get_module_setting("winner");
		if ($session['user']['location']==get_module_setting("snowbuildloc") &&
				$winner>0){
			$sql = "SELECT name FROM ". db_prefix("accounts") .
				" WHERE acctid='$winner'";
			$result = db_query_cached($sql, "snowmanwinner");
			$row = db_fetch_assoc($result);
			output("`n`QAn almost lifelike snowman sits in the village, with a shiny placard reading \"`&%s`Q\".`n",$row['name']);
		}
		break;
	}
	return $args;
}

function snowbuild_run(){
	page_header("The Snowmen");
	require_once("lib/villagenav.php");
	global $session;
	$cost=get_module_setting("cost");
	$perday=get_module_setting("perday");
	$tries=get_module_pref("tries");
	$op=httpget('op');
	if ($op==""){
		if ($tries<$perday){
			$title=translate_inline($session['user']['sex']?"Lady":"Sir");
			output("`7You walk to the edge of the square, where snow has been swept into a huge pile.");
			output("Visitors are building snowmen all around you.");
			output("As you gaze about, someone kicks at a nearby snowman, destroying it, before using the snow to start another.`n`n");
			output("As you gasp, a smiling villager says, \"`QEh, it's alright, that wasn't a winning snowman!");
			output("We all have a try, and if we don't win... it's how it works!");
			output("I'm Russell, and I'll lend you the accessories for %s gold... the money goes to charity, you see!",$cost);
			output("There's a good pile of snow there to your left, if you'd like to give it a try.\"");
			if ($session['user']['gold']<$cost){
				output("`n`nIf only you had enough gold.");
				output("You head back to the other villagers.");
			}else{
				addnav(array("Create a Snowman (`^%s`0 gold)",$cost),
						"runmodule.php?module=snowbuild&op=build");
			}
		}else{
			output("`7You walk into the snow field, taking a look at all the snowmen.`n`n");
			output("A villager glares at you, and you don't have the heart to destroy anyone else's work today.");
		}
		villagenav();
	}elseif ($op=="build"){
		$tries++;
		set_module_pref("tries",$tries);
		$session['user']['gold']-=$cost;
		debuglog("spent $cost gold to build a snowman.");
		villagenav();
		output("`7You decide to build a snowman.");
		output("You pay `QRussell`7 the `^%s`7 gold for charity, and he hands you some buttons, a carrot, hat, and some stones.",$cost);
		output("As your hands begin to freeze, you go to work on the snow, and soon accomplish a large lump of vaguely-snowman form.");
		$result=e_rand(1,30);
		if ($result==30){ //30, 1:30
			set_module_setting("winner",$session['user']['acctid']);
			apply_buff('snowmanwin',array("name"=>"`QFirst Prize","rounds"=>10, "atkmod"=>1.02,"defmod"=>1.02, "schema"=>"module-snowman"));
			$reward=5*$cost;
			$session['user']['gold']+=$reward;
			$richer=$reward-$cost;
			debuglog("won $reward gold from building a snowman.");
			output("The villagers cheer and applaud your snowman.");
			output("Russell exclaims, \"`QWonderful! Just wonderful! A most excellent job indeed.");
			output("I can't remember the last time I've seen such a snowman.");
			output("You win First Prize!`7\"");
			output("`7He tosses you a small bag with some gold in it.`n`n");
			output("\"`QThis needs to stay on display!");
			output("I'll have it roped off, as a matter of fact.`7\"`n`n");
			output("You beam with pride as you head back to the other villagers, `^%s`7 extra gold in your hand.",$richer);
		}elseif ($result<6){ //1 to 5, 5:30, 1:6
			output("Your snowman is now a lot of pulpy slush.");
			output("What did you expect to happen when you applied your %s to it?",$session['user']['weapon']);
			output("`QRussell`7 laughs softly and shakes his head, \"`QPerhaps next time, eh?`7\"");
			output("`n`nDisappointed, you head back towards the other villagers.");
		}elseif ($result<30 && $result >20){ //21 to 29, 9:30, 3:10
			$reward=3*$cost;
			$session['user']['gold']+=$reward;
			$richer=$reward-$cost;
			debuglog("won $reward gold from building a snowman.");
			output("Your snowman has a relatively lifelike appearance.");
			output("`7Russell looks at your snowman closely and says, \"`QYes, very nice.");
			output("This is a high quality snowman. Very well done!`7\"");
			output("He tosses you a small bag with some gold in it.`n`n");
			output("You head back to the others, `^%s`7 gold richer than when you came in.",$richer);
		}elseif ($result>5 && $result<11){ //6 to 10, 5:30, 1:6
			output("Your snowman crudely resembles what might be called a person.");
			output("`7Russell looks at your snowman skeptically, \"`QI can tell you put a lot of, err, effort into this.");
			output("I suppose I have seen worse, just not recently.");
			output("Well, you did your best, right?");
			output("Hopefully you'll do better next time.`7\"`n`n");
			output("Disappointed, you head back to the other villagers.");
		}else{ //11 to 20, 9:30, 3:10
			$session['user']['gold']+=$cost;
			debuglog("won $cost gold from building a snowman.");
			output("Your snowman has a standard look about him, and is rather average.");
			output("`7Russell looks at your snowman and says, \"`QNot bad.");
			output("This is relatively good, really.");
			output("A very nice attempt, I must say.");
			output("With a little more intricacy, this could easily win First Prize.`7\"`n`n");
			output("`7Russell tosses you a small bag of gold.");
			output("You head back to the crowd having won back your `^%s`7 gold.",$cost);
		}
	}
	page_footer();
}
?>
