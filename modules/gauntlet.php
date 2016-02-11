<?php
/*
The Gauntlet
File:        gauntlet.php
Author:      Red Yates aka Deimos
Release:     04/10/2005
Version 1.1: 04/27/2005

Module to add maxhp in exchange for charm, gold, turns, and hitpoints.
Random costs and reward based on ranged settings.

Version 1.1:
Added/repaired per dk limits to visitation.
*/

function gauntlet_getmoduleinfo(){
	$info = array(
		"name"=>"The Gauntlet",
		"version"=>"1.1",
		"author"=>"`\$Red Yates",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"The Gauntlet Settings,title",
			"visits"=>"Visits allowed per day (0 for unlimited),int|1",
			"visitsdk"=>"Visits allowed per dk (0 for unlimited),int|0",
			"goldcost"=>"Gold cost,int|250",
			"minhpadd"=>"Minimum maxhitpoints to add,int|2",
			"maxhpadd"=>"Maximum maxhitpoints to add,int|4",
			"mincharmcost"=>"Minimum charm cost,int|2",
			"maxcharmcost"=>"Maximum charm cost,int|5",
			"minturnscost"=>"Minimum turns cost,int|1",
			"maxturnscost"=>"Maximum turns cost,int|2",
			"minhpcost"=>"Minimum health cost in percent,range,0,100,5|10",
			"maxhpcost"=>"Maximum health cost in percent,range,0,100,5|35",
			"(At 100 players won't be killed but will be left with 1 hp.),note",
			"gauntletloc"=>"Village in which to be located,location|".
				((is_module_active("cities") && is_module_active("racetroll"))?
					get_module_setting("villagename","racetroll"):
					getsetting("villagename", LOCATION_FIELDS)),
			"carrydk"=>"Do max hitpoints gained carry across DKs?,bool|1",
		),
		"prefs"=>array(
			"The Gauntlet Preferences,title",
			"seen"=>"Times seen today,int|0",
			"seendk"=>"Times seen this dragonkill,int|0",
			"extrahps"=>"How many extra hitpoints has the user gained?,int",
		),
	);
	return $info;
}

function gauntlet_install(){
	module_addhook("newday");
	module_addhook("village");
	module_addhook("dragonkill");
	module_addhook("changesetting");
	module_addhook("hprecalc");
	return true;
}

function gauntlet_uninstall(){
	return true;
}

function gauntlet_dohook($hookname, $args){
	global $session;
	switch ($hookname){
	case "hprecalc":
		$args['total'] -= get_module_pref("extrahps");
		if (!get_module_setting("carrydk")) {
			$args['extra'] -= get_module_pref("extrahps");
			set_module_pref("extrahps", 0);
		}
		break;
	case "dragonkill":
		set_module_pref("seendk",0);
		break;
	case "newday":
		set_module_pref("seen",0);
		break;
	case "village":
		if ($session['user']['location']==get_module_setting("gauntletloc")){
			tlschema($args['schemas']['fightnav']);
			addnav($args["fightnav"]);
			tlschema();
			addnav("The Gauntlet","runmodule.php?module=gauntlet");
		}
		break;
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("gauntletloc")) {
				set_module_setting("gauntletloc", $args['new']);
			}
		}
		break;
	}
	return $args;
}

function gauntlet_run(){
	page_header("The Gauntlet");
	require_once("lib/villagenav.php");
	global $session;
	$op=httpget("op");
	$goldcost=get_module_setting("goldcost");
	$gold=$session['user']['gold'];
	$minhpadd=get_module_setting("minhpadd");
	$maxhpadd=get_module_setting("maxhpadd");
	$maxhp=$session['user']['maxhitpoints'];
	$mincharmcost=get_module_setting("mincharmcost");
	$maxcharmcost=get_module_setting("maxcharmcost");
	$charm=$session['user']['charm'];
	$minturnscost=get_module_setting("minturnscost");
	$maxturnscost=get_module_setting("maxturnscost");
	$turns=$session['user']['turns'];
	$minhpcost=get_module_setting("minhpcost");
	$maxhpcost=get_module_setting("maxhpcost");
	$hp=$session['user']['hitpoints'];
	$seen=get_module_pref("seen");
	$seendk=get_module_pref("seendk");
	$visits = get_module_setting("visits");
	$visitsdk = get_module_setting("visitsdk");
	output("`c`b`4The Gauntlet`b`c`n");
	if ($op==""){
		output("`)A mean looking figure sits in front of a mean looking building.");
		output("A mean looking sign reading \"`4The Gauntlet`)\" hangs on the mean looking building.");
		if ($gold>=$goldcost &&
				$charm>=$maxcharmcost && $turns>=$maxturnscost &&
				($visitsdk == 0 || $seendk < $visitsdk) &&
				($visits == 0 || $seen < $visits)){
			output("`n`nThe mean looking figure gives you a mean look, summing you up, then says, \"`4Enter if you dare: only `^%s `4gold.`)\"",$goldcost);
			addnav("Enter The Gauntlet",
				"runmodule.php?module=gauntlet&op=enter");
		}else{
			output("`n`nThe mean looking figure gives you a mean look, summing you up, then says, \"`4Go away.`)\"");
		}
		villagenav();
	}elseif ($op=="enter"){
		set_module_pref("seen",$seen+1);
		set_module_pref("seendk",$seendk+1);
		$session['user']['gold']-=$goldcost;
		debuglog("paid $goldcost to enter The Gauntlet");
		$hpadd=e_rand($minhpadd,$maxhpadd);
		$session['user']['maxhitpoints']+=$hpadd;
		set_module_pref("extrahps", get_module_pref("extrahps")+$hpadd);
		$charmcost=e_rand($mincharmcost,$maxcharmcost);
		$session['user']['charm']-=$charmcost;
		$turnscost=e_rand($minturnscost,$maxturnscost);
		$session['user']['turns']-=$turnscost;
		$hpcost=e_rand($minhpcost,$maxhpcost);
		$session['user']['hitpoints']-=round(($hpcost/100)*$hp);
		if ($session['user']['hitpoints']<=0)
			$session['user']['hitpoints']=1;
		output("`)You pay your `^%s`) gold, hold your head high, and bravely enter The Gauntlet.`n`n", $goldcost);
		output("You're met by a hideous device with a moving floor and many dangerously swinging masses.");
		output("You're helplessly fed through the mechanism which puts you through a painful process of hits and blows.`n`n");
		output("Finally, The Gauntlet dumps you outside in a relatively undignified manner.`n`n");
		output("You're not quite sure what happened.`n");
		if ($hpcost) output("You took a beating.`n");
		if ($charmcost) output("You feel uglier.`n");
		if ($turnscost) output("You feel worn out.`n");
		if ($hpadd) output("However, for all of that, you feel tougher.");
		villagenav();
	}
	page_footer();
}
?>
