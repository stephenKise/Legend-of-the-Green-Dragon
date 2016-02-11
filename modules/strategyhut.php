<?php
/* ver 1.0.1 by Atrus - blackwolf99@gmail.com */
/* ver 1.0.2 - slight cleanup by Kendaer */
/* 15 April 2005 */

require_once("lib/http.php");
require_once("lib/villagenav.php");

function strategyhut_getmoduleinfo(){
	$info = array(
		"name"=>"The Strategy Hut",
		"version"=>"1.0.2",
		"author"=>"Atrus",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"cost"=>"How much gold does a visit cost per level,int|5",
			"hutloc"=>"Where does it appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
	);
	return $info;
}

function strategyhut_install(){
	module_addhook("changesetting");
	module_addhook("village");
	return true;
}

function strategyhut_uninstall(){
	return true;
}

function strategyhut_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "village":
		if(($session['user']['location'] == get_module_setting("hutloc")) &&
				(!$session['user']['dragonkills'] ||
				 $session['user']['superuser'])){
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav(" ?The Strategy Hut","runmodule.php?module=strategyhut");
		}
		break;
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("hutloc")) {
				set_module_setting("hutloc", $args['new']);
			}
		}
		break;
	}
	return $args;
}

function strategyhut_run(){

	global $session;
	$cost = get_module_setting("cost");
	$op = httpget("op");

	page_header("The Strategy Hut");

	output("`5`c`bThe Strategy Hut`b`c");

	if ($op==""){
		addnav(array("Ask for Advice (`^%s gold`0)",$cost),
				"runmodule.php?module=strategyhut&op=ask");
		output("`&You enter the hut, to find `6Atrus `&busy at his desk.");
		output("\"`^Well, a young warrior in search of help!");
		output("For a small fee, I will offer advice to you.`3\"`n`n" );
		output("`&Hesitantly, you approach the burly warrior.`n`n");
		output("`&You blink a few times before you realize he was actually talking to you.");
		output("`6Atrus `&doesn't seem very patient, so you'd better decide quickly if you want to hear his advice!`n");
	} elseif ($session['user']['gold']<$cost){
		output("`&You go through your pockets, searching for money, but you don't have enough.");
		output("After a moment of intense searching, `6Atrus `&starts to scowl, and you decide to leave before he gets annoyed.`n`n");
	} else {
		$session['user']['gold']-=$cost;
		debuglog("spent $cost gold at the strategy hut");
		output("`&You give `6Atrus `^%s gold`7.", $cost);
		output("`&He nods, and thinks for a moment.`n`n");
		$phrases = array(
			"\"`^Heal often, bank often.`3\"",
			"\"`^Think balance: weapons and armor must be close in level, not enough defense and your first attack will be your last.`3\"",
			"\"`^Don't be afraid to slum, in the lower DK levels, speed is NOT a priority. Later is different.`3\"",
			"\"`^That stat bar is your life, when it gets into the yellow zone, heal. When it goes red, pray.`3\"",
			"\"`^In PvP, pick your targets with care. If not sure, DON'T... or you'll be explaining to {deathoverlord}`^ what happened.`3\"",
			"\"`^You don't always need to resurrect. There will be times to save favor for emergencies.`3\"",
			"\"`^If it's a game bug, petition it. If it's a gameplay issue, petition it.`3\"",
			"\"`^A good offense is not always a good defense, even the strongest players die in the forest.`3\"",
			"\"`^Confidence is one thing: attacking a God is suicide. Check the bio first.`3\"",
			"\"`^Keep an open mind and think it through. Only a fool fights blindly.`3\"",
			"\"`^There is a dragon, and when you are ready, it will be too. Patience.`3\"",
			"\"`^Travelling between towns can be dangerous. Heal first.`3\"",
			"\"`^Talk to everyone in all the villages, visit the shops and stalls. Explore. Learn.`3\"",
			"\"`^Lower DK players die often in the beginning. It happens to all of us.`3\"",
			"\"`^When you face the dragon, be ready and fully healed... or it will eat you for lunch.`3\"",
			"\"`^Your mount or familiar is an asset... learn what it can do, and know its limits. *And* yours, as well.`3\"",
			"\"`^There is no shame in knowing when to run. Better a bruised ego than a visit to {deathoverlord}`^.`3\"",
			"\"`^Log in ONCE per game day only, or you will be killed repeatedly... and lose experience as a result. There is no safe place.`3\"",
			"\"`^If you can't resurrect, log off and wait for New Day. You are already dead.`3\"",
			"\"`^A good player treats his fellows with courtesy and respect. A wise player knows that new friends can help him succeed.`3\"",
			"\"`^Don't forget to feed your mount or familiar.`3\""
		);
		$question=e_rand(0,count($phrases)-1);
		$phrases = translate_inline($phrases);
		$myphrase = $phrases[$question];
		$myphrase = str_replace('{deathoverlord}', getsetting('deathoverlord', '`$Ramius'), $myphrase);
		output_notl("%s`n`n",$myphrase);
		output("`&You ponder his advice for a moment, before thanking him and making your exit.`n`n");
	}
	villagenav();
	page_footer();
}
?>
