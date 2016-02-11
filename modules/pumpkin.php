<?php
// translator ready
// addnews ready
// mail ready

/*
Pumpkin Carving Contest
File:    pumpkin.php
Author:  Red Yates aka Deimos
Date:    10/23/2004
Version: 1.0 (10/23/2004)

Written for the Esoterra Halloween celebration on Central server, 2004.
Player pays 25(default) gold to carve a pumpkin, with 1(default) try per day.
Player has a 1:30 (~3%) chance of getting their pumpkin put on display in
the square,
and getting a small, Audrey-like buff, and  winning 5 times the price.
30% chance of winning 3 times the price as a prize.
30% chance of winning the price as a prize.
*/
function pumpkin_getmoduleinfo(){
	$info = array(
		"name"=>"Pumpkin Carving Contest",
		"version"=>"1.0",
		"author"=>"`\$Red Yates",
		"download"=>"core_module",
		"category"=>"Village",
		"settings"=>array(
			"Pumpkin Module Settings, title",
			"perday"=>"Tries at carving per day, int|1",
			"cost"=>"How much to carve a pumpkin, int|25",
			"pumpkinloc"=>"Village the pumpkin carving is in, location|",
			"winner"=>"Whose pumpkin is on display, int|0",
		),
		"prefs"=>array(
			"Pumpkin User Prefs, title",
			"tries"=>"Times carved today,int|0"
		),
	);
	return $info;
}

function pumpkin_install(){
	module_addhook("village");
	module_addhook("newday");
	module_addhook("changesetting");
	module_addhook("village-desc");
	return true;
}

function pumpkin_uninstall(){
	return true;
}

function pumpkin_dohook($hookname, $args){
	global $session;
	switch ($hookname){
	case "village":
		if ($session['user']['location'] == get_module_setting("pumpkinloc")){
			tlschema($args['schemas']['marketnav']);
			addnav($args["marketnav"]);
			tlschema();
			addnav("Pumpkin Patch","runmodule.php?module=pumpkin");
		}
		break;
	case "newday":
		set_module_pref("tries",0);
		break;
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("pumpkinloc")) {
				set_module_setting("pumpkinloc", $args['new']);
			}
		}
		break;
	case "village-desc":
		$winner=get_module_setting("winner");
		if ($session['user']['location']==get_module_setting("pumpkinloc") &&
				$winner>0){
			$sql = "SELECT name FROM ". db_prefix("accounts") .
				" WHERE acctid='$winner'";
			$result = db_query_cached($sql, "pumpkinwinner");
			$row = db_fetch_assoc($result);
			output("`n`QA very intricately carved pumpkin sits on a pedestal, with a shiny placard reading \"`&%s`Q\".`n",$row['name']);
		}
		break;
	}
	return $args;
}

function pumpkin_run(){
	page_header("The Pumpkin Patch");
	require_once("lib/villagenav.php");
	global $session;
	$cost=get_module_setting("cost");
	$perday=get_module_setting("perday");
	$tries=get_module_pref("tries");
	$op=httpget('op');
	if ($op==""){
		if ($tries<$perday){
			$title=translate_inline($session['user']['sex']?"Lady":"Sir");
			output("`7You walk into the pumpkin patch, and notice that towards the center, by a scarecrow, there is a larger group of pumpkins.");
			output("Some of the pumpkins are mostly pulp, and others are carved with intricate patterns.");
			output("You wonder what (or who) has happened to these pumpkins, and as if in answer to your unspoken question, the scarecrow taps you on the shoulder.`n`n");
			output("You spin at the scarecrow, ready to attack after a fright like that, when he laughs politely and says, \"`QEasy there, my good %s.",$title);
			output("I didn't mean to frighten you.");
			output("My name is Redis, and this is my pumpkin patch.");
			output("I get bored hanging around here all the time, and so I've decided to have a pumpkin carving contest.");
			output("There's a good carving-pumpkin to your left, if you'd like to give it a try.");
			output("It's only `^%s`Q gold.`7\"",$cost);
			if ($session['user']['gold']<$cost){
				output("`n`nIf only you had enough gold.");
				output("You head back to %s.",$session['user']['location']);
			}else{
				addnav(array("Carve a Pumpkin (`^%s`0 gold)",$cost),
						"runmodule.php?module=pumpkin&op=carve");
			}
		}else{
			output("`7You walk into the pumpkin patch, taking a gander at all the pumpkins. You approach the scarecrow.`n`n");
			output("It just hangs there.");
		}
		villagenav();
	}elseif ($op=="carve"){
		$tries++;
		set_module_pref("tries",$tries);
		$session['user']['gold']-=$cost;
		debuglog("spent $cost gold to carve a pumpkin.");
		villagenav();
		output("`7You decide to carve a pumpkin.");
		output("You pay `QRedis`7 his `^%s`7 gold, and walk over to the pumpkin.",$cost);
		output("Wielding your %s with skill, you go to work on the pumpkin, as `QRedis`7 watches with interest.`n`n",$session['user']['weapon']);
		output("After a lot of effort, you wipe your brow and look at what you've accomplished.`n`n");
		$result=e_rand(1,30);
		if ($result==30){ //30, 1:30
			set_module_setting("winner",$session['user']['acctid']);
			apply_buff('pumpkinwin',array("name"=>"`QFirst Prize","rounds"=>10, "atkmod"=>1.02,"defmod"=>1.02, "schema"=>"module-pumpkin"));
			$reward=5*$cost;
			$session['user']['gold']+=$reward;
			$richer=$reward-$cost;
			debuglog("won $reward gold from carving a pumpkin.");
			output("`QRedis`7 cheers and applauds your pumpkin.");
			output("\"`QWonderful! Just wonderful! A most excellent job indeed.");
			output("I can't remember the last time I've seen such a pumpkin.");
			output("You win First Prize.`7\"");
			output("`QRedis`7 tosses you a small bag with some gold in it.`n`n");
			output("`QRedis`7 continues, \"`QThis needs to go on display.");
			output("I'll have it put on a pedestal in the square, as a matter of fact.`7\"`n`n");
			output("You beam with pride as you head back to %s, `^%s`7 gold richer than when you came in.",$session['user']['location'], $richer);
		}elseif ($result<6){ //1 to 5, 5:30, 1:6
			output("Your pumpkin is now a lot of pulp.");
			output("What did you expect to happen when you applied your %s to it?",$session['user']['weapon']);
			output("`QRedis`7 laughs softly and shakes his head, \"`QPerhaps next time, eh?`7\"");
			output("`n`nDisappointed, you head back to %s.",$session['user']['location']);
		}elseif ($result<30 && $result >20){ //21 to 29, 9:30, 3:10
			$reward=3*$cost;
			$session['user']['gold']+=$reward;
			$richer=$reward-$cost;
			debuglog("won $reward gold from carving a pumpkin.");
			output("Your pumpkin is carved with a relatively intricate pattern.");
			output("`QRedis`7 looks at your pumpkin closely and says, \"`QYes, very nice.");
			output("This is a high quality pumpkin. Very well done!`7\"");
			output("`QRedis`7 tosses you a small bag with some gold in it.`n`n");
			output("You head back to %s, `^%s`7 gold richer than when you came in.",$session['user']['location'], $richer);
		}elseif ($result>5 && $result<11){ //6 to 10, 5:30, 1:6
			output("Your pumpkin is crudely carved with what might be called a pattern.");
			output("`QRedis`7 looks at your pumpkin skeptically, \"`QI can tell you put a lot of, err, effort into this.");
			output("I suppose I have seen worse, just not recently.");
			output("Well, you did your best, right?");
			output("Hopefully you'll do better next time.`7\"`n`n");
			output("Disappointed, you head back to %s.",$session['user']['location']);
		}else{ //11 to 20, 9:30, 3:10
			$session['user']['gold']+=$cost;
			debuglog("won $cost gold from carving a pumpkin.");
			output("Your pumpkin is carved with a standard pattern, looking rather average.");
			output("`QRedis`7 looks at your pumpkin and says, \"`QNot bad.");
			output("This is relatively good, really.");
			output("A very nice attempt, I must say.");
			output("With a little more intricacy, this could easily win First Prize.`7\"`n`n");
			output("`QRedis`7 tosses you a small bag of gold.");
			output("You head back to %s, having won back your `^%s`7 gold.",$session['user']['location'], $cost);
		}
	}
	page_footer();
}
?>
