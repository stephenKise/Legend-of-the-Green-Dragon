<?php
// addnews ready
// mail ready
// translator ready
function tynan_getmoduleinfo(){
	$info = array(
		'name'=>"Tynan the Bodybuilder",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"General",
		"download"=>"core_module",
		"prefs"=>array(
			"defense"=>"Defense Points Gained,float|0",
			"attack"=>"Attack Points Gained,float|0",
			"hitpoints"=>"Hitpoints Gained,float|0",
		),

		"settings"=>array(
			"Tynan's Gym Module Settings,title",
			"all_locs"=>"Does Tynan's appear everywhere?,bool|1",
			"gymloc"=>"Where does the gym appear?,location|".getsetting("villagename", LOCATION_FIELDS),
		),
	);
	return $info;
}

function tynan_install(){
	module_addhook("village");
	module_addhook_priority("newday", 10);
	module_addhook("changesetting");
	module_addhook("pvpadjust");
	module_addhook("adjuststats");
	return true;
}

function tynan_uninstall(){
	return true;
}

function tynan_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "village":
		if (($session['user']['location'] == get_module_setting("gymloc")) ||
				get_module_setting("all_locs") == 1) {
			tlschema($args['schemas']['fightnav']);
			addnav($args['fightnav']);
			tlschema();
			addnav("Tynan's Gym","runmodule.php?module=tynan&op=gym");
		}
		break;
	case "pvpadjust":
		// fetch the tynan buff values for the target.
		$thp = round(get_module_pref("hitpoints", false, $args['acctid']), 0);
		$tatk = round(get_module_pref("attack", false, $args['acctid']), 0);
		$tdef = round(get_module_pref("defense", false, $args['acctid']), 0);
		$args['maxhitpoints'] += $thp;
		$args['defense'] += $tdef;
		$args['attack'] += $tatk;
		break;
	case "adjuststats":
		// fetch the tynan buff values for the target.
		$thp = round(get_module_pref("hitpoints", false, $args['acctid']), 0);
		$tatk = round(get_module_pref("attack", false, $args['acctid']), 0);
		$tdef = round(get_module_pref("defense", false, $args['acctid']), 0);
		$args['creaturehealth'] += $thp;
		$args['creaturedefense'] += $tdef;
		$args['creatureattack'] += $tatk;
		break;
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("gymloc")) {
				set_module_setting("gymloc", $args['new']);
			}
		}
		break;
	case "newday":
		$oldattack = get_module_pref("attack");
		$olddefense = get_module_pref("defense");
		$oldhitpoints = get_module_pref("hitpoints");
		$attack = round($oldattack * 0.8, 2);
		$defense = round($olddefense * 0.8, 2);
		$hitpoints = round($oldhitpoints * 0.8, 2);
		if ($attack == $oldattack) $attack = 0;
		if ($defense == $olddefense) $defense = 0;
		if ($hitpoints == $oldhitpoints) $hitpoints = 0;
		if ($attack || $defense || $hitpoints) {
			apply_buff("tynanSTAT",
				array(
				"name"=>"",
				"rounds"=>-1,
				"tempstat-maxhitpoints"=>round($hitpoints,0),
				"tempstat-defense"=>round($defense, 0),
				"tempstat-attack"=>round($attack, 0),
				"schema"=>"module-tynan",
				)
			);
			// accomodate the change in maxhitpoints in their regular
			// hitpoints also.
			$session['user']['hitpoints'] += round($hitpoints,0);
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints'] = 1;
		}
		set_module_pref("attack",$attack);
		set_module_pref("defense",$defense);
		set_module_pref("hitpoints",$hitpoints);
		break;
	}

	return $args;
}

function tynan_run(){
	global $session;


	$op = httpget("op");

	page_header("Tynan's Gym");
	require_once("lib/villagenav.php");
	villagenav();
	addnav("Training");
	addnav("M?Train Muscles from Tone","runmodule.php?module=tynan&op=train&what=muscles");
	addnav("T?Train Tone from Muscles","runmodule.php?module=tynan&op=train&what=tonemuscles");
	addnav("o?Train Tone from Agility","runmodule.php?module=tynan&op=train&what=toneagility");
	addnav("A?Train Agility from Tone","runmodule.php?module=tynan&op=train&what=agility");

	output("`&`c`bTynan's Gym`b`c");

	$attack = get_module_pref("attack");
	$defense = get_module_pref("defense");
	$hitpoints = get_module_pref("hitpoints");

	if ($op=="gym"){
		output("You walk in to a large gleaming building, the outside of which is adorned with a sign reading, \"`#Tynan's Gym`&.\"");
		output("The sign itself is decorated with the appearance of a perfectly sculpted male silhouette holding a curved bar at arms length over his head, either side of which is a T representing the illustrious establishment's name.");
		output("A smaller slogan under the bold title reads, \"`#Turning the average boy into an Adonis, or girl into Artemis`&.\"`n`n");
		output("`&Walking in, you're greeted by the warm musk of concentrated human effort as around you other warriors work various weight machines.");
		output("Spotting you, Tynan approaches. \"`^Vaht do vee hav here?  Eet eez ein leetle scrawny perzon I sink.  Vell, I sink I can help you vit zee muscles odor ze tonink of your flahbby arms, ja.  Eef you verk on zee muscles, you vill heet ze badguys harder, odor eef you verk on zee tone, you veel hav ze endurance, und if you verk on zee ageelity you vill be harder for ze badguys to be hitting you.`&\"`n`n");
		output("`&After puzzling through what Tynan just said, you figure out that training for muscles will give you more attack, training for tone will give you more hitpoints, and training for agility will give you more defense.");
		output("Knowing what a rigorous workout Tynan typically gives, you realize you'll lose the endurance you'd usually use for a forest fight in the process of this workout.`n");
	}elseif ($op=="train"){
		if ($session['user']['turns']>0){
			$what = httpget("what");
			$weakmessage = translate_inline("`&\"`#Vaht, you seenk you can leeft zeese veights?  Look, zay are veighink two times as much as you!`&\" Tynan scoffs.`n`n`^You think you need more attack or defense to sustain this type of training.");
			$scrawnymessage = translate_inline("`&\"`#Vaht, you seenk you hav zee bones to support such muscles?  Your muscles, zey vould break you like ein twig!`&\" Tynan scoffs.`n`n`^You think you need more hitpoints to sustain this type of training.");
			$nodiemessage = translate_inline("`&\"`#Vaht are you tryink to do, be keelink yourselv?  All zat you be doink iz gettink blud on ze equipment if you don' bandeege ze woundz!`&\" smirks Tynan.`n`n`^You think you should heal yourself before you try to work out any more.");
			$workoutmessage = translate_inline("`&Tynan hands you a training schedule involving several machines which he's conveniently numbered.  Approaching the machines, you're not certain you even understand how to use them, but after fumbling around for a bit, you manage to seriously injure yourself.  Tynan, seeing this, hollers over to you, \"`#Ja, ja, zat is eet, you veel be zee person vit zee perfekt muscles soon!`&\"`n`n");
			if ($what=="muscles"){
				//attack
				// Let's see how many perm hitpoints can be carried over.
				reset($session['user']['dragonpoints']);
				$dkpoints = 0;
				while(list($key,$val)=each($session['user']['dragonpoints'])){
					if ($val=="hp") $dkpoints+=5;
				}

				$hpgain = array(
					'total' => $session['user']['maxhitpoints'],
					'dkpoints' => $dkpoints,
					'extra' => $session['user']['maxhitpoints'] - $dkpoints -
							($session['user']['level'] * 10),
					'base' => $dkpoints + ($session['user']['level'] * 10),
				);
				$hpgain = modulehook("hprecalc", $hpgain);
				// $extra + $dkpoint corresponds to the total we are
				// allowed by the code to carry over.  Because of the way
				// the stats code works, the user's stats already reflect
				// the change in stats from here.
				// so if that sum is < 10 we cannot work out here.
				if ($hpgain['extra'] + $hpgain['dkpoints'] < 10) {
					//too scrawny
					output_notl("%s", $scrawnymessage);
				} elseif ($session['user']['hitpoints'] <= 10) {
					//too damaged
					output_notl("%s", $nodiemessage);
				}else{
					output_notl("%s", $workoutmessage);
					output("`^You've gained muscles!");
					$attack++;
					$hitpoints-=10;
					$session['user']['turns']--;
					$session['user']['hitpoints'] -= 10;
				}
			}elseif ($what=="tonemuscles"){
				//hitpoints
				if ($session['user']['attack'] <
						($session['user']['level']+
						 $session['user']['weapondmg'] + 1)) {
					//too weak
					output_notl("%s", $weakmessage);
				}else{
					output_notl("%s", $workoutmessage);
					output("`^You've gained tone!");
					$hitpoints+=10;
					$attack -= 1;
					$session['user']['turns']--;
					$session['user']['hitpoints'] += 10;
				}
			}elseif ($what=="toneagility"){
				//hitpoints
				if ($session['user']['defense'] <
						($session['user']['level'] +
						 $session['user']['armordef']+1)) {
					//too weak
					output_notl("%s", $weakmessage);
				}else{
					output_notl("%s", $workoutmessage);
					output("`^You've gained tone!");
					$hitpoints+=10;
					$defense -= 1;
					$session['user']['turns']--;
					$session['user']['hitpoints'] += 10;
				}
			}elseif ($what=="agility"){
				//defense
				// Let's see how many perm hitpoints can be carried over.
				reset($session['user']['dragonpoints']);
				$dkpoints = 0;
				while(list($key,$val)=each($session['user']['dragonpoints'])){
					if ($val=="hp") $dkpoints+=5;
				}

				$hpgain = array(
					'total' => $session['user']['maxhitpoints'],
					'dkpoints' => $dkpoints,
					'extra' => $session['user']['maxhitpoints'] - $dkpoints -
							($session['user']['level'] * 10),
					'base' => $dkpoints + ($session['user']['level'] * 10),
				);
				$hpgain = modulehook("hprecalc", $hpgain);
				// $extra + $dkpoint corresponds to the total we are
				// allowed by the code to carry over.  Because of the way
				// the stats code works, the user's stats already reflect
				// the change in stats from here.
				// so if that sum is < 10 we cannot work out here.
				if ($hpgain['extra']+$hpgain['dkpoints'] < 10) {
					//too scrawny
					output_notl("%s", $scrawnymessage);
				} elseif ($session['user']['hitpoints'] <= 10) {
					//too damaged
					output_notl("%s", $nodiemessage);
				}else{
					output_notl("%s", $workoutmessage);
					output("`^You've gained agility!");
					$defense++;
					$hitpoints-=10;
					$session['user']['turns']--;
					$session['user']['hitpoints'] -= 10;
				}
			}
			apply_buff("tynanSTAT",array(
				"name"=>($session['user']['superuser'] & SU_DEBUG_OUTPUT?"DEBUG:tynanSTAT":""),
				"rounds"=>-1,
				"tempstat-maxhitpoints"=>round($hitpoints,0),
				"tempstat-defense"=>round($defense,0),
				"tempstat-attack"=>round($attack,0),
				"schema"=>"module-tynan",
			));
			set_module_pref("attack",$attack);
			set_module_pref("defense",$defense);
			set_module_pref("hitpoints",$hitpoints);
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints'] = 1;
		}else{
			output("`&Tynan scoffs, \"`#Vaht, you seenk you can verk on zeese machines veet your eyes almost goink to fall to ze sleepsies?  Nein, Ich veel not vaste mein time so you can fail you girly girl.`&\"");
		}
	}
	page_footer();
}
?>
