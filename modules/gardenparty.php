<?php
// translator ready
// mail ready
// addnews ready
require_once("lib/buffs.php");
require_once("lib/commentary.php");

function gardenparty_getmoduleinfo(){
	$info = array(
		"name"=>"Garden Party",
		"author"=>"Eric Stevens",
		"category"=>"Gardens",
		"version"=>"1.0",
		"download"=>"core_module",
		"settings"=>array(
			"Garden Party Settings,title",
			"partytype"=>"Type of party?|birthday party for MightyE",
			"partystart"=>"When does the part start,dayrange,+10 days,+1 day|2004-06-30 00:00:00",
			"partyduration"=>"How long does the party last,datelength|24 hours",
			"cedrikclothes"=>array("What is %s`0 wearing?|a Hawaiian shirt", getsetting('barkeep', '`tCedrik')),
			"buff"=>"Text to output as the player fights in the forest?|In the distance you hear the sounds of \"Happy Birthday\" being sung.",
			"cakename"=>"Name of the cake?|Birthday Cake",
			"cakecost"=>"Cost per level for cake,int|20",
			"cakeemote"=>"What will display in the conversation when you order cake?|pigs out and takes a huge bite of Birthday Cake.",
			"maxcake"=>"How many slices of cake can a player buy in one day?,int|3",
			"drinkname"=>"Name of the drink?|Grape Soda",
			"drinkcost"=>"Cost per level for drink,int|50",
			"drinkemote"=>"What will display in the conversation when you order drink?|takes a big swig of Grape Soda.",
			"maxdrink"=>"How many party drinks can a player buy in one day?,int|3",
		),
		"prefs"=>array(
			"Garden Party User Preferences,title",
			"caketoday"=>"How many pieces of cake have they eaten today?,int|0",
			"drinkstoday"=>"How many drinks have they had today in the partY?,int|0"
		)
	);
	return $info;
}

function gardenparty_install(){
	module_addhook("gardens");
	module_addhook("newday");
	return true;
}

function gardenparty_uninstall(){
	debug("Uninstalling module.");
	return true;
}

function gardenparty_dohook($hookname, $args) {
	global $session;

	switch($hookname){
	case "newday":
		set_module_pref("caketoday",0);
		set_module_pref("drinkstoday",0);
		break;
	case "gardens":
		// See if the party is currently running.
		$start = strtotime(get_module_setting("partystart"));
		$end = strtotime(get_module_setting("partyduration"), $start);
		$now = time();
		if ($start <= $now && $end >= $now) {
			output("There's a party going on!  It's a %s!",
					get_module_setting("partytype"));
			output("%s`0 is here, wearing (of all things) %s, serving food and drinks.`n`n", getsetting('barkeep', '`tCedrik'), get_module_setting("cedrikclothes"));
			addnav("Party Treats!");
			$caketoday = get_module_pref("caketoday");
			$drinkstoday = get_module_pref("drinkstoday");
			$cakecost = get_module_setting("cakecost")*$session['user']['level'];
			$drinkcost = get_module_setting("drinkcost")*$session['user']['level'];
			if ($caketoday < get_module_setting("maxcake") &&
					$session['user']['gold'] >= $cakecost) {
				$cake = get_module_setting("cakename");
				addnav(array("%s (`^%s gold`0)", $cake, $cakecost),
						"runmodule.php?module=gardenparty&buy=cake");
			}
			if ($drinkstoday < get_module_setting("maxdrink") &&
					$session['user']['gold']>=$drinkcost) {
				$drink = get_module_setting("drinkname");
				addnav(array("%s (`^%s gold`0)", $drink, $drinkcost),
						"runmodule.php?module=gardenparty&buy=drink");
			}
		}
		break;
	}
	return $args;
}

function gardenparty_run(){
	global $session;

	// See if the party is currently running.
	$start = strtotime(get_module_setting("partystart"));
	$end = strtotime(get_module_setting("partyduration"), $start);
	$now = time();
	if ($now < $start || $now > $end) {
		redirect("gardens.php");
	}

	$missed = "a bogus item";
	$comment = "mutters something that you cannot make out.";
	switch(httpget("buy")){
	case "cake":
		$caketoday = get_module_pref("caketoday");
		$cost = get_module_setting("cakecost")*$session['user']['level'];
		if ($session['user']['gold'] >= $cost){
			$session['user']['gold'] -= $cost;
			$comment = get_module_setting("cakeemote");
			set_module_pref("caketoday",$caketoday+1);
		}else{
			//they probably timed out, and got PK'd.
			//Let's handle it gracefully.
			$cantafford = true;
			$missed = get_module_setting("cakename");
		}
		break;
	case "drink":
		$cost = get_module_setting("drinkcost")*$session['user']['level'];
		$drinkstoday = get_module_pref("drinkstoday");
		if ($session['user']['gold'] >= $cost) {
			$session['user']['gold'] -= $cost;
			$comment = get_module_setting("drinkemote");
			set_module_pref("drinkstoday",$drinkstoday+1);
		}else{
			//they probably timed out, and got PK'd.
			//Let's handle it gracefully.
			$cantafford = true;
			$missed = get_module_setting("drinkname");
		}
		break;
	}

	if ($cantafford){
		page_header("%s in %s", getsetting("barkeep", "`tCedrik"), get_module_setting("cedrikclothes"));
		output("You wander over to where %s`0 is standing in the gardens, and ask to buy %s, but he tells you that you don't have enough gold to buy it.", getsetting("barkeep", "`tCedrik"), $missed);
		output("You think it's a little odd that you're being charged for food and drinks at %s, but ignore this, eager to get back to the revelry.", get_module_setting("partytype"));
		addnav("Back to the party","gardens.php");
		page_footer();
	}else{
		injectcommentary("gardens", "whispers", ":".addslashes($comment));
		$buff = array(
			"name"=>"Party Fever",
			"minioncount"=>1,
			"maxbadguydamage"=>0,
			"minbadguydamage"=>0,
			"effectnodmgmsg"=>get_module_setting("buff"),
			"rounds"=>-1,
			"schema"=>"module-gardenparty",
		);
		apply_buff('gardenparty', $buff);
		redirect("gardens.php");
	}
}

?>