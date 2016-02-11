<?php
// translator ready
// addnews ready
// mail ready
function fairydust_getmoduleinfo(){
	$info = array(
		"name"=>"Fairy Dust",
		"author"=>"Copied and Pasted by Sneakabout, requested by the JCP.",
		"version"=>"1.0",
		"category"=>"Lodge",
		"download"=>"core_module",
		"settings"=>array(
			"Fairy Dust Module Settings,title",
			"cost"=>"How much does one bottle cost?,int|200",
			"buff"=>"Does it give you a buff?,bool|1",
			"carrydk"=>"Do max hitpoints gained carry across DKs?,bool|1",
			"hptoaward"=>"How many HP are given by the fairy?,range,1,5,1|1",
			"fftoaward"=>"How many FFs are given by the fairy?,range,1,5,1|1",
		),
		"prefs"=>array(
			"Fairy Dust User Preferences,title",
			"fairydustbottles"=>"How many bottles of Fairy Dust does the player have?,int|0",
			"extrahps"=>"How many extra hitpoints has the user gained?,int",
		),
	);
	return $info;
}

function fairydust_install(){
	module_addhook("lodge");
	module_addhook("pointsdesc");
	module_addhook("hprecalc");
	module_addhook("forest");
	return true;
}
function fairydust_uninstall(){
	return true;
}

function fairydust_dohook($hookname,$args){
	global $session;
	$cost = get_module_setting("cost");
	switch($hookname){
	case "hprecalc":
		$args['total'] -= get_module_pref("extrahps");
		if (!get_module_setting("carrydk")) {
			$args['extra'] -= get_module_pref("extrahps");
			set_module_pref("extrahps", 0);
		}
		break;
	case "pointsdesc":
		$args['count']++;
		$format = $args['format'];
		$str = translate("A bottle of Fairy Dust costs %s points as well as a gem, and can be a little unpredictable.");
		$str = sprintf($str, $cost);
		output($format, $str, true);
		break;
	case "lodge":
		addnav(array("Fairy Dust (%s points+1 gem)", $cost),"runmodule.php?module=fairydust&op=bottlebuy");
		break;
	case "forest":
		$dust = get_module_pref("fairydustbottles");
		if ($dust>=1) {
			addnav("U?Use a Bottle of Fairy Dust","runmodule.php?module=fairydust&op=usebottle");
		}
		break;
	}
	return $args;
}

function fairydust_runevent() {
}

function fairydust_run(){
	global $session;
	$op = httpget("op");

	$cost = get_module_setting("cost");
	$buff = get_module_setting("buff");
	require_once("lib/increment_specialty.php");
	if ($op == "usebottle") {
		page_header("Fairy Dust");
		output("`%You cautiously unstopper the tiny vial of dust and, holding your breath, tip the contents over your head with your eyes shut.");
		output("A shimmering rain of dust cascades over you!`n`n`^");
		$dust = get_module_pref("fairydustbottles");
		$dust--;
		set_module_pref("fairydustbottles", $dust);
		if ($buff==1) {
			apply_buff('Fairy Dust',
				array(
					"name"=>"`\$Fairy Dust",
					"rounds"=>10,
					"wearoff"=>"You have shaken all the dust off.",
					"defmod"=>1.1,
					"roundmsg"=>"The dust shimmers around you, making you harder to hit!",
					"schema"=>"module-fairydust",
					)
				);
			output("The dust conceals you from the eyes of enemies!`n`n");
		}
		debuglog("used Fairy Dust");
		switch(e_rand(1,7)){
		case 1:
			$extra = get_module_setting("fftoaward");
			if ($extra == 1) output("You receive an extra forest fight!");
			else output("You receive %s extra forest fights!", $extra);
			$session['user']['turns'] += $extra;
			break;
		case 2:
		case 3:
			output("You feel perceptive and notice `%TWO gems`^ nearby!");
			$session['user']['gems']+=2;
			debuglog("found 2 gem from fairy dust");
			break;
		case 4:
		case 5:
			$hptype = "permanently";
			if (!get_module_setting("carrydk") ||
					(is_module_active("globalhp") &&
					 !get_module_setting("carrydk", "globalhp")))
				$hptype = "temporarily";
			$hptype = translate_inline($hptype);
			$extra = get_module_setting("hptoaward");

			output("Your maximum hitpoints are `b%s`b increased by %d!",
					$hptype, $extra);

			$session['user']['maxhitpoints'] += $extra;
			$session['user']['hitpoints'] += $extra;
			set_module_pref("extrahps", get_module_pref("extrahps")+$extra);
			break;
		case 6:
		case 7:
			increment_specialty("`^");
			break;
		}
		output("You now have %s bottles of Fairy dust left!", $dust);

		output("`n`nMoments later, you open your eyes, blinking away remnants of glittering dust.");
		addnav("O?Open your Eyes","forest.php");
	} elseif ($op=="bottlebuy"){
		page_header("Hunter's Lodge");
		output("`7J. C. Petersen turns to you. \"`&A bottle of Fairy Dust costs %s points, and a gem for our suppliers,`7\" he says.  \"`&Will this suit you?`7\"`n`n", $cost);
		addnav("Confirm Fairy Dust Purchase");
		addnav("Yes", "runmodule.php?module=fairydust&op=bottlebuyconfirm");
		addnav("No", "lodge.php");
	}elseif ($op=="bottlebuyconfirm"){
		page_header("Hunter's Lodge");
		addnav("L?Return to the Lodge","lodge.php");
		$pointsavailable = $session['user']['donation'] -
			$session['user']['donationspent'];
		if($pointsavailable >= $cost && $session['user']['gems'] > 0){
			output("`7J. C. Petersen reaches behind a painting and hands you a tiny vial of shimmering fairy dust. In return you grudgingly give him one of your precious gems.");
			$dust = get_module_pref("fairydustbottles");
			$dust++;
			set_module_pref("fairydustbottles", $dust);
			$session['user']['gems']--;
			$session['user']['donationspent'] += $cost;
		} else {
			if ($pointsavailable < $cost && $session['user']['gems'] < 1) {
				// Missing points and gem
				output("`7J. C. Petersen looks down his nose at you. \"`&I'm sorry, but you do not have the %s points, nor the gem,  required to procure a bottle of fairy dust.  Please return when you do and I'll be happy to sell them to you.`7\"", $cost);
			} elseif ($pointsavailable < $cost) {
				// missing only points
				output("`7J. C. Petersen looks down his nose at you. \"`&I'm sorry, but you do not have the %s points required to procure a bottle of fairy dust.  Please return when you do and I'll be happy to sell them to you.`7\"", $cost);
			} else {
				// missing only gem
				output("`7J. C. Petersen looks down his nose at you. \"`&I'm sorry, but you do not have the gem required to procure a bottle of fairy dust.  Please return when you do and I'll be happy to sell them to you.`7\"");
			}
		}
	}
	page_footer();
}
?>
