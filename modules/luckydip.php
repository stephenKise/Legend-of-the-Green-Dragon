<?php
// translator ready
// addnews ready
// mail ready

/* Lucky Dip */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 10th Nov 2004 */

require_once("lib/villagenav.php");
require_once("lib/http.php");

function luckydip_getmoduleinfo(){
	$info = array(
		"name"=>"Lucky Dip",
		"version"=>"1.0",
		"author"=>"Shannon Brown",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"Lucky Dip - Settings,title",
			"tryallowed"=>"How many tries may the player have?,int|3",
			"cost"=>"Price to play small?,int|2",
			"lcost"=>"Price to play large?,int|5",
			"luckydiploc"=>"Where does the stand appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
		"prefs"=>array(
			"Lucky Dip - User Preferences,title",
			"trytoday"=>"How many times has the player tried today?,int|0",
		)
	);
	return $info;
}

function luckydip_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
	return true;
}

function luckydip_uninstall(){
	return true;
}

function luckydip_dohook($hookname,$args){
	global $session;
	switch($hookname){
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("luckydiploc")) {
				set_module_setting("luckydiploc", $args['new']);
			}
		}
		break;
   	case "newday":
		set_module_pref("trytoday",0);
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("luckydiploc")) {
			tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
			tlschema();
			addnav("Lucky Dip","runmodule.php?module=luckydip");
		}
		break;
	}
	return $args;
}

function luckydip_run() {
	global $session;
	$op = httpget('op');
	$cost=get_module_setting("cost");
	$lcost=get_module_setting("lcost");
	$tryallowed=get_module_setting("tryallowed");
	$trytoday=get_module_pref("trytoday");

	page_header("Lucky Dip Stand");
	output("`&`c`bElias' Lucky Dip`b`c");
	if ($trytoday>=$tryallowed){
		output("`n`7Much as you'd like to play again, Elias scowls at you and turns to the child beside you.");
		addnav("Leave","village.php");
	}elseif ($session['user']['gold']<$cost){ // less than 2
		output("`n`7Much as you'd like to play, your purse doesn't yield enough to pay for the privilege.");
		addnav("Leave","village.php");
	}elseif ($op==""){
		output("`n`7You begin to approach the Lucky Dip, peering into the bins with interest.");
		output("You dimly make out small colored packages in the darkness.");
		output("Elias stands behind the brightly-colored bins.`n`n");
		output("`&\"Hello traveler!");
		output("So you think yourself lucky?");
		output("There are many treasures in these boxes!\"`n`n");
		output("`7He motions to the smaller box, and then to the larger one.");
		output("`&\"%s gold for the small, %s for the large.",$cost,$lcost);
		output("Who knows what you will find?\"");
		addnav(array("Small (%s gold)",$cost),"runmodule.php?module=luckydip&op=small");
		addnav(array("Large (%s gold)",$lcost),"runmodule.php?module=luckydip&op=large");
		addnav("Leave","village.php");
	}elseif ($op=="small"  || ($session['user']['gold']>=$lcost && $op=="large")){
		$trytoday++;
		set_module_pref("trytoday",$trytoday);
		if ($op=="small") {
			$dipchance=(e_rand(1,50));
			$session['user']['gold']-=$cost;
			debuglog("spent $cost gold on a lucky dip.");
			output("`n`7You hand Elias your %s gold, and reach one arm into the blue and white box.`n",$cost);
		}else{
			$dipchance=(e_rand(1,25));
			$session['user']['gold']-=$lcost;
			debuglog("spent $lcost gold on a lucky dip.");
			output("`n`7You hand Elias your %s gold, and reach one arm into the red and white box.`n",$lcost);
		}
		output("Dragging a package out, you unwrap it with excitement.`n");
		output("Elias smiles.`n`n");
		output("`&\"So you see, a treasure!");
		$gift=(e_rand(1,4));
		if ($dipchance==1){
			output("A treasure indeed!");
			output("I hope you shall keep it safe, noble warrior!\"`n`n");
			output("`7In your hands is a `6calle shell`7!");
			output("`7You're rather amazed to find such a treasure in a simple lucky dip!");
			$callecount=get_module_pref("callecount","calletrader");
			$callecount++;
			set_module_pref("callecount",$callecount,"calletrader");
		}elseif ($gift==4){
			output("I hope you shall keep it safe, noble warrior!\"`n`n");
			output("`7In your hands is a `5gem`7!");
			$session['user']['gems']++;
		}elseif ($gift==3){
			output("I hope you shall spend it wisely!\"");
			output("`n`n`7You look down to find `^10 gold`7.");
			$session['user']['gold']+=10;
		}elseif ($gift==2){
			output("You shall have hours of joy playing with such a treasure!\"");
			output("`n`n`7You look down to find a cheap children's toy.");
			output("`n`n`7You frown in annoyance, before handing it to the nearest small child, who is delighted.");
		}else{
			output("I hope you enjoy it!\"");
			output("`n`n`7You look down to find a small iced cookie.");
			output("`n`n`^You bite into it with joy!");
			// Don't let it heal them too far
			if ($session['user']['hitpoints'] <=
					$session['user']['maxhitpoints']*1.1) {
				$session['user']['hitpoints']*=1.05;
				output("`@You feel healthy!");
				$addturn=(e_rand(1,4));
				if ($addturn==1) {
					$session['user']['turns']++;
					output("`@You feel `@vigorous!");
				}
			}
		}
		addnav("Try again");
		addnav(array("Small (%s gold)",$cost),"runmodule.php?module=luckydip&op=small");
		addnav(array("Large (%s gold)",$lcost),"runmodule.php?module=luckydip&op=large");
		addnav("Leave","village.php");
	}elseif ($op!=""){
		output("Elias looks at you with annoyance.`n`n");
		output("`&\"I told you!");
		output("That box costs %s to play!\"`n`n",$lcost);
		addnav("Leave","village.php");
	}
	page_footer();
}
?>
