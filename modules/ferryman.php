<?php

/*  Title:  The Ferryman
 *  Author: Ben "blarg" Wong (blargeth -at- gmail -dot- com)
 *  Notes:  - Based on a concept and text written by
 *	          Ken "muad" Stephens (inquisitor.muad -at- gmail -dot- com)
 *
 *  Description:
 *  This is a simple module in which the player finds a ferryman in the forest
 *  and can choose to cross the river or not, which will result in a variety of
 *  outcomes (good and bad). Muad's inspiration was drawn from, of course,
 *  Charon of Greek mythology fame. Well, actually, only one of the possible
 *  outcomes is based on this. For the others, it's just some guy with a boat.
 *  And green glowing eyes. :)
 *
 *  Version History:
 *  1.1 (2005/02/21)  - Added a more descriptive encounter beginning (thanks
 *                      Muad)
 *                    - Fixed pluralization (thanks Sichae and Kendaer)
 *                    - Added two more outcomes: being hit by the ferryman for
 *                      some damage and fighting him directly
 *
 *  1.0 (2005/02/18)  - Officially released to Dragonprime
 *                    - Minor tweaks and fixes
 *
 *  0.3 (2005/02/18)  - Added support for Lonny's module updater
 *                    - Fixed maxfavorgain setting
 *                    - Added readme file
 *                    - Other minor tweaks
 *
 *  0.2 (2005/02/17)  - Improvements for translation-readiness
 *                    - Reworded some of the flavour text for the outcomes
 *                    - Changed the buff messages
 *                    - Added a new outcome and renumbered the switch cases
 *                    - Moved a bunch of options to be admin-configurable
 *                    - Added nav headers
 *                    - Other small tweaks, fixes, and cleanup
 *
 *  0.1 (2005/02/15)  - first alpha release
 */

require_once("lib/http.php");
require_once("lib/fightnav.php");

function ferryman_getmoduleinfo() {
	$info = array(
		"name"=>"Ferryman",
		"author"=>"Ben Wong<br>based on a concept by Ken Stephens",
		"version"=>"1.2",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Ferryman Settings,title",
			"ferrycost"=>"Cost in gold of passage on the ferry,int|25",
			"highferrycost"=>"Inflated cost of passage on the ferry,int|100",
			"drownchance"=>"Percent chance of drowning when player goes overboard,range,0,100,5|30",
			"gaingemchance"=>"Percent chance of gaining a gem reward instead of gold,range,0,100,5|25",
			"losegoldpercent"=>"Percent of on-hand gold to lose when fleeing riverbank,range,0,100,5|5",
			"losegemamount"=>"Max number of gems to lose when fleeing riverbank,int|5",
			"losehppercent"=>"Percent of HP to lose when fleeing riverbank,range,0,100,5|20",
			"maxfavorgain"=>array("Max amount of favor gained when %s`0 greets them,int|15", getsetting('deathoverlord', '`$Ramius')),
			"maxfflost"=>"Max number of forest fights to lose when ferryman is killed,int|5",
		),
		"prefs"=>array(
			"Ferryman Preferences, title",
			"paidferry"=>"Did the player pay for passage on the ferry,bool|0"
		)
	);
	return $info;
}

function ferryman_install() {
	module_addeventhook("forest", "return 100;");
	return true;
}

function ferryman_uninstall() {
	return true;
}

function ferryman_dohook($hookname, $args) {
	return $args;
}

function ferryman_runevent($type) {
	global $session;
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:ferryman";
	$op = httpget('op');

	// module settings
	$ferrycost = get_module_setting("ferrycost");
	$highferrycost = get_module_setting("highferrycost");
	$drownchance = get_module_setting("drownchance");
	$gaingemchance = get_module_setting("gaingemchance");
	$losegoldpercent = get_module_setting("losegoldpercent");
	$losegemamount = get_module_setting("losegemamount");
	$losehppercent = get_module_setting("losehppercent");
	$maxfavorgain = get_module_setting("maxfavorgain");
	set_module_pref("paidferry",0);

	// encounter starts here
	if ($op=="" || $op=="search") {
		output("`2As you search through the forest for something to kill, you duck under some underbrush and hear the sound of water.");
		output("Looking for the source of the noise, you stumble out of the forest and you find yourself standing on the bank of a wide river.");
		output("Trying to come up with a way to cross, you glance upstream and notice what appears to be a small dock not too far away.`n`n");
		output("You make your way upstream where you find a cloaked figure standing next to a small boat tied to the dock.");
		output("Surely he could help you cross.");
		addnav("The Ferryman");
		addnav("Talk to the Figure", $from . "op=talkferryman");
		addnav("Go Back", $from . "op=ignoreferryman");
	} elseif ($op=="talkferryman") {
		output("`2You approach and the figure motions you onto the boat.");
		addnav("The Ferryman");
		addnav("Get Onboard", $from . "op=boardferry");
		addnav("Go Back",  $from . "op=leaveferryman");
	} elseif ($op=="boardferry") {
		output("`2The figure points to a cup.");
		output("You see a sign that reads, \"`@%s gold to cross`2\".",
				$ferrycost);
		addnav("The Ferryman");
		addnav(array("Pay %s Gold", $ferrycost), $from . "op=payferry");
		addnav("Don't Pay", $from . "op=dontpayferry");
		addnav("Get Out",  $from . "op=leaveferryman");
	} elseif ($op=="payferry") {
		if ($session['user']['gold'] < $ferrycost) {
			output("`2You dig through your pockets and pouches, but you can't find enough gold.`n`n");
			output("The ferryman waits expectantly.");
			addnav("The Ferryman");
			addnav("Board Anyway", $from . "op=dontpayferry");
			addnav("Leave", $from . "op=leaveferryman");
		} else {
			$session['user']['gold'] -= $ferrycost;
			debuglog("spent $ferrycost gold on ferryman");
			set_module_pref("paidferry", 1);
			output("`2The gold coins echo loudly as they fall into the cup.");
			output("The ferryman reaches out to a long pole.");
			output("Bony fingers grasp the pole and drive it deep into the water.");
			output("The ferry pushes off across the river.`n`n");
			output("About halfway across, the ferryman turns to you.");
			output("You see green glowing eyes shining from within the hood.`n`n");
			// Drop through gracefully to below to avoid a meaningless click.
			$op = "continueferry";
			httpset("op", $op);
		}
	} elseif ($op=="dontpayferry") {
		output("`2You get into the ferry without paying.");
		output("The ferryman waits for a short moment and then reaches out to a long pole.");
		output("Bony fingers grasp the pole and drive it deep into the water.");
		output("The ferry pushes off across the river.`n`n");
		output("About halfway across, the ferryman turns to you.");
		output("You see green glowing eyes shining from within the hood.`n`n");

		if ($session['user']['gold'] >= $highferrycost) {
			output("`n`nHe reaches out and points a bony finger at the cup. You hear a voice with a commanding tone demanding that you now pay `@%s `2gold.", $highferrycost);
			addnav("The Ferryman");
			addnav(array("Pay %s Gold", $highferrycost), $from . "op=payferrymore");
			addnav("Don't Pay", $from . "op=continueferry");
		}
		else {
			// Drop through below
			$op = "continueferry";
			httpset("op", $op);
		}
	}

	// Handle these ops seperately so we can drop out of the code above.
	if ($op=="continueferry" || $op=="payferrymore") {
		if ($op == "payferrymore") {
			$session['user']['gold'] -= $highferrycost;
			debuglog("paid $highferrycost to ferryman");
			set_module_pref("paidferry",1);
		}

		// slightly better chance of a good outcome if paid
		if (get_module_pref("paidferry")) {
			$randferry = e_rand(1,18);
		}
		else {
			$randferry = e_rand(1,9);
		}

		switch ($randferry) {
		case 1:  // nothing interesting happens
		case 10:
		case 14:
		case 18:
			output("`2After peering at you for a moment, the ferryman turns back and resumes poling.");
			output("A short time later, you reach the other shore.`n`n");
			output("The ferryman waits patiently as you disembark and return to the forest.");
			break;

		case 2:  // gain a short defensive buff
		case 11:
		case 15:
			output("`2A voice emanates from the figure and asks you how your fights in the forest have been going.");
			output("Surprised, you tell the ferryman about your day.");
			output("When you finish, he breaks into a song about you.");
			output("The song lifts your spirits and you arrive at the opposite riverbank without further incident.`n`n");
			output("`&You feel uplifted!");
			$ferrybuffrounds = e_rand(10,15);
			$ferrybuff = array(
				"name"=>"`&Ferryman's Song",
				"rounds"=>$ferrybuffrounds,
				"wearoff"=>"`7The effects of the ferryman's song fade into memory.",
				"defmod"=>1.2,
				"atkmod"=>1.0,
				"roundmsg"=>"`7You feel uplifted as you hum the ferryman's song!",
				"schema"=>"module-ferryman"
			);
			apply_buff("ferrymanbuff",$ferrybuff);
			$session['user']['specialinc']="";
			break;

		case 3:  // gain a small amount of gold or gems
		case 12:
		case 16:
			output("`2He peers at you for a moment, then turns back and continues to the other shore.");
			output("As you climb out of the boat, you notice an extra small leather pouch on your person!`n`n");
			$reward = e_rand(0, 100);
			if ($reward > $gaingemchance) {
				$rewardamt = e_rand(10,40);
				$session['user']['gold'] += $rewardamt;
				$rewardcol = "`^";
				$rewardtype = "gold";
			} else {
				$rewardamt = round(e_rand(1,3), 0);
				$session['user']['gems'] += $rewardamt;
				$rewardcol = "`%";
				if ($rewardamt == 1) {
					$rewardtype="gem";
				} else {
					$rewardtype="gems";
				}
			}
			$rewardstr = "$rewardamt $rewardtype";
			output("`&You have gained %s%s %s`&!",
					$rewardcol, $rewardamt, translate_inline($rewardtype));
			debuglog("gained $rewardstr from ferryman");
			$session['user']['specialinc']="";
			break;

		case 4:  // gain a forest fight
		case 13:
		case 17:
			output("`2The eyes seem to look straight into your mind.");
			output("Unable to turn away, you feel yourself slipping into a trance.");
			output("As you approach the shore, you snap out of the trance and feel refreshed.");
			output("In fact, you think you could face another forest creature!`n`n");
			output("`&You have gained a forest fight!");
			$session['user']['turns']++;
			$session['user']['specialinc']="";
			break;

		case 5:  // fall out of the boat
			output("`2The ferryman starts moving towards you.");
			output("Suddenly, something hits the boat, sending you into the river!");
			addnav("The Ferryman");
			addnav("Swim for the Shore", $from . "op=swim");
			addnav("Climb Back In", $from . "op=climb");
			break;

		case 6:  // lose some gold, gems, or HP
			output("`2Looking at the eyes, you find that you are unable to tear away your gaze from the hypnotic stare.`n`n");
			output("And then, darkness.`n`n");
			output("A short time later, you awaken.");
			output("You seem to have washed up on the other shore.");
			output("Not knowing exactly what happened, you decide it would best if you were to quickly leave this place.`n`n");
			$randloss = e_rand(1,3);

			if ($randloss == 1 && $session['user']['gold'] > 0) {
				$lostgold = round($session['user']['gold'] * $losegoldpercent / 100, 0);
				output ("`&In your haste, you drop `^%s gold`&!", $lostgold);
				$session['user']['gold'] -= $lostgold;
				debuglog("lost $lostgold gold from ferryman");
			} elseif ($randloss == 2 && $session['user']['gems'] > 0) {
				$hasgems = $session['user']['gems'];
				$lostgems = e_rand(1,$losegemamount);
				if ($hasgems < $lostgems) {
					output("`&In your haste, you drop all your gems!");
					debuglog("lost $hasgems from ferryman");
					$session['user']['gems'] = 0;
				} else {
					output ("`&In your haste, you drop `%%s %s`&!", $lostgems, translate_inline($lostgems==1?"gem":"gems"));
					$session['user']['gems'] -= $lostgems;
					debuglog("lost $lostgems gems from ferryman");
				}
			} else {
				$losthp = round($session['user']['hitpoints'] *
						$losehppercent / 100, 0);
				output("`&In your haste, you trip and do `\$%s %s of damage`& to yourself!", $losthp, translate_inline($losthp==1?"point":"points"));
				$session['user']['hitpoints'] -= $losthp;
			}
			$session['user']['specialinc']="";
			break;

		case 7:  // end up in underworld, gain some favor
			output("`2A dense fog rolls in out of nowhere and envelops the boat.");
			output("The bony hand of the ferryman reaches out and grasps your arm with a firm grip.");
			output("After a moment, the fog clears, only to be replaced with darkness.");
			output("As the ferryman steers toward the shore, you notice a figure standing there.");
			output("You suddenly realize that you have been ferried across the River Styx to the land of the dead!`n`n");
			output("%s`2 bids you welcome and seems pleased with your arrival.`n`n", getsetting("deathoverlord", '`$Ramius'));
			output("`&Because you have been physically transported to the underworld, you still have your gold.");
			addnav("Daily News","news.php");
			addnews("`%%s `7was last seen aboard a small boat.`0",
					$session['user']['name']);
			$favorgain = e_rand(1, $maxfavorgain);
			$session['user']['alive'] = false;
			$session['user']['hitpoints'] = 0;
			$session['user']['deathpower'] += $favorgain;
			$session['user']['specialinc']="";
			break;

		case 8:  // fight the ferryman!
			output("`2He seems to think for a moment, then turns back and resumes poling.");
			output("You relax and turn your attention back to the waters.`n`n");
			output("`2Suddenly, you hear a sharp whistling sound.");
			output("Looking up, you see the ferryman's pole swinging towards your head!");
			output("Quickly ducking, you manage to dodge the blow, but the ferryman starts swinging again!");
			addnav("The Ferryman");
			addnav("Duck and Fight", $from . "op=fightferryman");
			addnav("Duck and Run", $from . "op=fleeferryman");

			break;

		case 9:  // ferryman hits them
			output ("`2The ferryman starts moving towards you.");
			output("You start to edge away from him and he swings his ferry pole at you!");
			output ("Not having much room to maneuver, he delivers a painful blow!`n`n");
			$randdmg = e_rand (2, round($session['user']['hitpoints'] / 2, 0));
			$session['user']['hitpoints'] -= $randdmg;
			output ("`&You were struck for `\$%s %s of damage`&!`n`n",
					$randdmg, translate_inline($randdmg==1?"point":"points"));
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`&You have died!");
				$lostgold = $session['user']['gold'];
				$session['user']['alive'] = false;
				$session['user']['hitpoints'] = 0;
				$session['user']['gold'] = 0;
				debuglog("lost $lostgold gold from dying at ferryman");
				addnav("Daily News","news.php");
				addnews("`%%s `7was last seen aboard a small boat.`0",
						$session['user']['name']);
			} else {
				output("`2You let out a yelp of pain and ready yourself to block another blow.");
				output("Seemingly satisfied, the ferryman turns back and resumes poling.");
				output("Rubbing the painful welt, you reach the other shore and decide to quickly get away from the ferryman.`n`n");
			}
			$session['user']['specialinc']="";
			break;
		}
	} elseif ($op=="swim" || $op=="climb") {
		if ($op == "swim") {
			output("`2You try swimming for the shore, but the weight of your gear prevents you from making much progress.");
		} else {
			output("`2You try to climb back in, but the weight of your gear prevents you from making much progress.");
		}
		$randdrown = e_rand(1,100);
		if ($randdrown < $drownchance) {
			output("As you thrash about in the water, you notice the ferryman looking over at you from the boat.");
			output("A haunting sound drifts to you from the boat and you realize that the ferryman is laughing at your plight.");
			output("As you sink into the murky depths of the river, you hear naught but the ferryman's laughter echoing in your ears.`n`n");
			output("`&You have died!");
			$lostgold = $session['user']['gold'];
			$session['user']['alive'] = false;
			$session['user']['hitpoints'] = 0;
			$session['user']['gold'] = 0;
			debuglog("lost $lostgold gold from dying at ferryman");
			addnav("Daily News","news.php");
			addnews("`%%s `7was last seen aboard a small boat.`0",
					$session['user']['name']);
		} else {
			output("`2The ferryman extends his pole to you and helps you back onboard.`n`n");
			output("Thankfully, the rest of the journey passes without incident and you arrive on the other shore.");
			output("The ordeal has left you intact, though a little tired.");
			if ($session['user']['turns'] > 0) {
				output("`n`n`&You have lost a forest fight!");
				$session['user']['turns']--;
			}
		}
		$session['user']['specialinc']="";
	} elseif ($op == "fightferryman" || $op == "fight" || $op == "run") {
		ferryman_fight();
	} elseif ($op == "fleeferryman") {
		output("`2You dodge the second blow and turn to run.");
		output("Looking at the murky waters all around, you realize you have nowhere to run and turn to face the ferryman!");
		addnav("The Ferryman");
		addnav("Fight", $from . "op=fightferryman");
	} elseif ($op == "ignoreferryman") {
		output("`2You decide to ignore the cloaked figure and return to the forest.");
		$session['user']['specialinc']="";
	} elseif ($op == "leaveferryman") {
		output("`2You decide not to take the ferryman's offer and return to the forest.");
		$session['user']['specialinc']="";
	}
	output("`0");
}

function ferryman_fight() {
	$op = httpget("op");
	global $session;
	$from = "forest.php?";

	if ($op == "fightferryman") {
		$badguy = array(
			"creaturename"=>translate_inline("`7The Ferryman`0"),
			"creaturelevel"=>$session['user']['level']+2,
			"creatureweapon"=>translate_inline("Ferry Pole"),
			"creatureattack"=>$session['user']['attack'],
			"creaturedefense"=>$session['user']['defense'],
			"creaturehealth"=>round($session['user']['maxhitpoints'],0),
			"diddamage"=>0,
			// This next line looks odd, but it's basically telling the
			// battle code, not to do the determination for surprise.  This
			// means player gets first hit against the ferryman, he will never
			// go first.
			"didsurprise"=>1,
			"type"=>"ferryman");
		$session['user']['badguy']=createstring($badguy);
		$op = "fight";
		httpset('op', "fight");
	}

	if ($op == "run") {
		output ("`2With nothing but murky water all around, you have nowhere to run!`n`n");
		$op = "fight";
		httpset('op', "fight");
	}

	if ($op == "fight") {
		$battle = true;
	}

	if ($battle){
		require_once("battle.php");
		if ($victory) {
			output("`n`@You have managed to defeat the Ferryman!");
			output("You cautiously approach the body, and suddenly, it shimmers and disappears before your eyes!`n`n");
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`^Using a bit of river weed, you are able to staunch your own wounds, stopping your bloodloss before you are completely dead.`n");
				$session['user']['hitpoints'] = 1;
			}
			output("`2Without the ferryman, you are left to drift with the river currents.");
			output("It takes a long time, but you eventually get to the other shore.`n`n");
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`^Your staunch your own wounds with a bit of moss growing nearby, stopping your bloodloss before you are completely dead.`n");
				$session['user']['hitpoints'] = 1;
			}
			$maxfflost = get_module_setting("maxfflost");
			if ($session['user']['turns'] < $maxfflost) {
				$lostff = $session['user']['turns'];
			} else {
				$lostff = $maxfflost;
			}
			$session['user']['turns'] -= $lostff;
			output("`&You have lost %s forest %s!", $lostff, translate_inline($lostff==1?"fight":"fights"));
			$session['user']['specialinc']="";
			$session['user']['specialmisc']="";
		} elseif ($defeat) {
			require_once("lib/taunt.php");
			$taunt = select_taunt_array();
			$lostgold = $session['user']['gold'];
			output("`n`@You have been killed by the Ferryman!");
			output("As your last breath escapes you, all you hear is haunting laughter.");
			addnav("Daily News", "news.php");
			addnews("`%%s `7was last seen aboard a small boat.`0",
					$session['user']['name']);
			debuglog("killed by Ferryman, losing $lostgold");
			$session['user']['gold']=0;
			$session['user']['specialinc']="";
			$session['user']['specialmisc']="";
		}else{
			fightnav(true,true);
		}
	}
}

function ferryman_run(){
}
?>
