<?php
// translator ready
// addnews ready
// mail ready

/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* includes creative and technical input by JT Traub */
/* 24 Aug 2004 */
/* Hitchhike module */

require_once("lib/http.php");
require_once("lib/villagenav.php");

function hitch_getmoduleinfo(){
	$info = array(
		"name"=>"Hitchhiking Module",
		"version"=>"1.1",
		"author"=>"Shannon Brown",
		"category"=>"Travel",
		"download"=>"core_module",
		"settings"=>array(
			"danger"=>"Chance of being hurt or killed,range,1,25,1|10",
		),
		"prefs"=>array(
			"ridetoday"=>"Has player hitchhiked today,bool|0",
			"oweerrands"=>"Does player owe errands after new day,bool|0",
			"ditched"=>"How many ditched errand attempts does the player have?,int|0",
		)
	);
	return $info;
}

function hitch_install(){
	module_addhook("pre-travel");
	module_addhook("newday");
	module_addhook("inn");
	module_addhook("dragonkill");
	return true;
}

function hitch_uninstall(){
	return true;
}

function hitch_dohook($hookname,$args){
	global $session;
	switch($hookname){

	case "dragonkill":
		set_module_pref("ridetoday", 0);
		set_module_pref("oweerrands", 0);
		set_module_pref("ditched", 0);
		break;
	case "newday":
		$vname = getsetting("villagename", LOCATION_FIELDS);
		if ($session['user']['location'] == $vname) {
			// They stayed in the capital and can do errands as promised
			if (get_module_pref("oweerrands")==1) {
				output("`n`n`3You remember your promise to the driver, and rush off to do the errands he has asked.");
				output("You're rather tired after this, and as a result `\$lose`3 one forest fight, and some of your hitpoints.`n");
				$session['user']['hitpoints']*=0.8;
				$session['user']['turns']--;
				$args['turnstoday'] .= ", Hitchhiking: -1";
				// Lower their 'ditched' score randomly
				$ditch = get_module_pref("ditched");
				if ($ditch && e_rand(1, 10) < 8) {
					// 70% chance of lowering
					set_module_pref("ditched", $ditch-1);
				}
			} else {
				// Have a chance of the farmers slowly forgetting about
				// ditched errands.
				$ditch = get_module_pref("ditched");
				if ($ditch && e_rand(1, 10) < 4) {
					// 30% chance of lowering
					set_module_pref("ditched", $ditch-1);
				}
			}
		} else {
			if (get_module_pref("oweerrands") == 1) {
				// They ditched out on doing their errands! Bad Bad BAD.
				set_module_pref("ditched", get_module_pref("ditched")+1);
				output("`7Belatedly, you remember the errands you had promised that you would do for the farmer, and only hope he will forgive you.`n");
				// need to check if module exists on this server
				if (is_module_active("matthias")){
					$astute=get_module_pref("astuteness","matthias");
					$astute--;
					set_module_pref("astuteness",$astute,"matthias");
				}
			}
		}
		set_module_pref("ridetoday",0);
		set_module_pref("oweerrands",0);
		break;
	case "pre-travel":
		$vname = getsetting("villagename", LOCATION_FIELDS);
		if ($session['user']['location'] != $vname) { // all but capital
			// Get the counts of available travels
			$args = modulehook("count-travels", array('available'=>0,'used'=>0));
			$free = max(0, $args['available'] - $args['used']);
			$ridetoday=get_module_pref("ridetoday");

			$canhitch = 0;
			if ($free==0 && $session['user']['turns']==0 && $ridetoday==0)
				$canhitch = 1;

			// Now, see if their reputation screws them
			$ditch = get_module_pref("ditched");
			debug("Canhitch: $canhitch, Ditch: $ditch");
			if ($canhitch && $ditch) {
				// Use a bell random function here so that you can find a
				// ride, but it just gets more and more unlikely.
				require_once("lib/bell_rand.php");
				$rand = bell_rand(0, 5);
				debug("Rand: $rand, Ditch: $ditch");
				if ($rand < $ditch) $canhitch = 0;
			}

			if ($canhitch) {
				output("`n`7Being an adventurous soul, you decide to look around and see if anyone is heading your way.`n");
				addnav("Hitch a Ride");
				addnav("Hitchhike","runmodule.php?module=hitch");
			} else {
				// They cannot hitch, why?
				if ($free==0 && $session['user']['turns']==0){
					$oweerrands =get_module_pref("oweerrands");
					if ($ridetoday && $oweerrands) {
						// They already own errands.  No penalty here as they
						// will be penalized on the new day.
						output("`n`7You spend some time looking around for another ride to %s, but are unable to find one.", $vname);
						output("You only hope the farmer you promised your help to eventually forgives you.`n");
					} elseif ($ridetoday && !$oweerrands) {
						output("`n`7You briefly consider hitchhiking, but after what happened when you tried to hitchhike earlier, you won't be trying that again today!`n");
					} elseif (!$ridetoday && $ditch) {
						output("`n`7Being an adverturous soul, you decide to look around and see if anyone is heading your way.");
						output("`n`7You find a farmer heading towardy %s, but after you introduce yourself, he gets icily cold.", $vname);
						output("You dejectedly walk away and realize that word of the times you have ditched your rides after promising help with errands has spread.`n");
						set_module_pref("ridetoday", 1);
					}
				}
			}
		}
		break;
	case "inn":
		$oweerrands=get_module_pref("oweerrands");
		if ($oweerrands==1){
			output("`n`3You know that %s`3 has comfortable rooms for the night, but you remember that you promised to stay in the fields with the driver and do errands in the morning.`n", getsetting("barkeep", "`tCedrik"));
			blocknav("inn.php?op=room");
			blockmodule("inncoupons");
		}
		break;
	}

	return $args;
}


function hitch_run(){
	global $session;
	$op = httpget("op");
	$chance = (e_rand(1,100));
	$danger = get_module_setting("danger");
	$death = ($danger/3);
	$thisvillage = $session['user']['location'];
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$capital = $session['user']['location']==$vname;

	page_header("Hitchhiking");
	output("`&`c`bHitchhiking`b`c");
	if ($op==""){
		addnav("H?Hitch a ride","runmodule.php?module=hitch&op=ride");
		addnav("F?Forget it","runmodule.php?module=hitch&op=leave");
		output("`7You spy a family packing their wagon, and timidly ask the father where their destination might be.`n`n");
		output("`&\"%s!\" he answers. \"Would ye like to ride along? I'll take ye there, if you'll run me some errands in the morn.\"`n`n",$vname);
		output("`7You realize you'll be expected to spend the night with them, in the fields of %s, if you agree to go with them.", $vname);
		output("You also wonder whether it's safe to trust a stranger.`n`n");
	} elseif ($op=="leave") {
		addnav("Return from whence you came","runmodule.php?module=cities&op=travel");
		output("`7You aren't sure that you can trust this man, and decide that you didn't really need to get to %s, anyway.", $vname);
		output("You bid him a safe trip, and leave him to his packing.`n`n");
	} elseif ($chance<=$danger) {
		set_module_pref("ridetoday",1);
		output("You help the travellers secure the last of their belongings, and climb aboard.");
		output("Just as you leave the outskirts of %s, the man pulls a knife on you and demands gold and gems.", $thisvillage);
		output("You leap from the wagon, falling heavily, scramble to your feet, and draw your %s!`n`n", $session['user']['weapon']);

		output("The burly man is very fit, and quickly leaps off the wagon and rushes you, meaning you have to rely on hand to hand combat.");
		output("In a fast, but deadly exchange of knife thrusts, he manages to wound you badly and your weapon falls from your nerveless fingers.");
		if ($chance<=$death) {
			output("Staggering, you fall to the ground as the blows continue relentlessly.");
			output("You feel the last remaining rays of daylight disappear from your vision, until you finally lose consciousness.");
			output("Your last thought is to wonder how you could be so foolish as to hitchhike with a stranger.`n`n");
			output("`^You have died!`n");
			output("`^You `\$lose`^ 5% of your experience.`n");
			output("You may continue playing again tomorrow.");
			$session['user']['alive']=false;
			$session['user']['hitpoints']=0;
			$session['user']['experience']*=0.95;
			addnav("Daily News","news.php");
			addnews("`&%s `7was foolhardy enough to hitchhike, and disappeared somewhere en route to the capital.", $session['user']['name']);
			debuglog("was killed by a stranger when trying to hitch.");
		} else {
			villagenav();
			output("In desperation, you finally manage to trip him, stun him for a few seconds, and then scramble away and hide under a hollow log.`n`n");
			output("You listen to him as he curses and searches for you, until he finally gives up and leaves.`n`n");
			output("Several hours later, you emerge from your hiding place, retrieve your %s and %s and limp back to %s for the night.", $session['user']['weapon'], $session['user']['armor'], $thisvillage);
			$session['user']['hitpoints'] = $session['user']['hitpoints']*0.1;
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints'] = 1;
			debuglog("was hurt by a stranger when trying to hitch.");
		}
	} else {
		$session['user']['location']=$vname;
		addnav(array("Explore %s",$vname),"village.php");
		output("`7You help the travellers secure the last of their belongings, and climb aboard.");
		output("In what seems like no time, you have arrived in %s.`n`n", $vname);
		output("You thank them profusely for bringing you here, and promise to meet them in the fields later tonight, so that you can run the errands first thing in the morning.");
		debuglog("succesfully hitched a ride to $vname.");
		set_module_pref("oweerrands",1);
		set_module_pref("ridetoday",1);
	}
	page_footer();
}

?>
