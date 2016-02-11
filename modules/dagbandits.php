<?php

require_once("lib/http.php");
require_once("lib/villagenav.php");

function dagbandits_getmoduleinfo(){
	$info = array(
		"name"=>"Bandits Quest",
		"version"=>"1.0",
		"author"=>"`%Sneakabout`^",
		"category"=>"Quest",
		"download"=>"core_module",
		"settings"=>array(
			"Bandits Quest Settings,title",
			"rewardgold"=>"What is the gold reward for the Bandits Quest?,int|5000",
			"rewardgems"=>"What is the gem reward for the Bandits Quest?,int|2",
			"experience"=>"What is the quest experience multiplier for the Bandits Quest?,floatrange,1.01,1.3,0.01|1.1",
			"minlevel"=>"What is the minimum level for this quest?,range,1,15|5",
			"maxlevel"=>"What is the maximum level for this quest?,range,1,15|11",
			"minrep"=>"What is the minimum reputation for this quest?,int|2",
		),
		"prefs"=>array(
			"Bandits Quest Preferences,title",
            "status"=>"How far in the Bandits Quest has the player got?,int|0",
        ),
        "requires"=>array(
	       "dagquests"=>"1.1|By Sneakabout",
		),
	);
	return $info;
}

function dagbandits_install(){
	module_addhook("village");
	module_addhook("dragonkilltext");
	module_addhook("newday");
	module_addhook("dagquests");
	return true;
}

function dagbandits_uninstall(){
	return true;
}

function dagbandits_dohook($hookname,$args){
	global $session;
	switch ($hookname) {
	case "village":
		if ($session['user']['location']==
				getsetting("villagename", LOCATION_FIELDS)) {
			tlschema($args['schemas']['gatenav']);
			addnav($args['gatenav']);
			tlschema();
			if (get_module_pref("status")==1) {
				addnav("Search for Bandits (3 turns)","runmodule.php?module=dagbandits&op=search");
			}
		}
		break;
	case "dragonkilltext":
		set_module_pref("queststatus",0);
		set_module_pref("status",0);
		break;
	case "newday":
		if (get_module_pref("status")==1 &&
				$session['user']['level']>get_module_setting("maxlevel")) {
			set_module_pref("status",4);
			output("`n`2You hear that Dag can't pay enough for adventurers of your stature, and you abandon the bounty.`0`n");
			require_once("modules/dagquests.php");
			dagquests_alterrep(-1);
		}
		break;
	case "dagquests":
		if (get_module_pref("status")==5) {
			$goldgain=get_module_setting("rewardgold");
			$gemgain=get_module_setting("rewardgems");
			$session['user']['gold']+=$goldgain;
			$session['user']['gems']+=$gemgain;
			debuglog("gained $goldgain gold and $gemgain gems for killing bandits.");
			if ($goldgain && $gemgain) {
				output("You hand Dag the assorted rings, and he grins before producing `^%s gold`3 and `%%s %s`3!",$goldgain,$gemgain,translate_inline(($gemgain==1)?"gem":"gems"));
			} elseif ($gemgain) {
				output("You hand Dag the assorted rings, and he grins before producing `%%s %s`3!",$gemgain,translate_inline(($gemgain==1)?"gem":"gems"));
			} elseif ($goldgain) {
				output("You hand Dag the assorted rings, and he grins before producing `^%s gold`3!",$goldgain);
			} else {
				output("You hand Dag the assorted rings, and he grimaces before shrugging, and saying that they're not giving out rewards for those anymore.");
			}
			addnews("`&%s`^ has been heard boasting about defeating a huge group of bandits!`0",$session['user']['name']);
			set_module_pref("status",2);
			require_once("modules/dagquests.php");
			dagquests_alterrep(3);
			$args['questoffer']=1;
		}
		if ($args['questoffer']) break;
		if (get_module_setting("minlevel")<=$session['user']['level'] &&
				$session['user']['level']<=get_module_setting("maxlevel") &&
				get_module_pref("dkrep","dagquests") >=
					get_module_setting("minrep") &&
				!get_module_pref("status")) {
			output("He seems very busy, but when you ask him about work, he nods and leans in closer.`n`n");
			output("\"Aye, ye've shown yerself t'be capable, I'll be givin' ye a shot at a problem that cropped up right recent.  There be a new group o' bandits that be workin' the area. Normally that tain't nothing t' shout about, but they been using some kind o' control over th' animals, be usin' them as pets, help them in their raids. If ye can be takin' out one of th' teams at their headquarters, I'd be givin' ye a good bounty off'n their rings. Do ye want t' be givin' it a shot?\"");
			output("You wonder how you're going to survive in their own base, but the reward sounds good.");
			addnav("Take the Job","runmodule.php?module=dagbandits&op=take");
			addnav("Refuse","runmodule.php?module=dagbandits&op=nottake");
			$args['questoffer']=1;
		}
		break;
	}
	return $args;
}

function dagbandits_runevent($type) {
}

function dagbandits_run(){
	global $session;
	$op = httpget('op');

	switch($op){
	case "take":
		$iname = getsetting("innname", LOCATION_INN);
		page_header($iname);
		rawoutput("<span style='color: #9900FF'>");
		output_notl("`c`b");
		output($iname);
		output_notl("`b`c");
		output("`3Dag points out the location of their base on a map, and tells you that each team has two swordsmen, an archer, a scout and a captain before wishing you luck.");
		set_module_pref("status",1);
		villagenav();
		break;
	case "nottake":
		$iname = getsetting("innname", LOCATION_INN);
		page_header($iname);
		rawoutput("<span style='color: #9900FF'>");
		output_notl("`c`b");
		output($iname);
		output_notl("`b`c");
		output("`3Dag turns away, losing interest in your cowardice.");
		set_module_pref("status",4);
		villagenav();
		break;
	case "search":
		page_header("Outside Town");
		if ($session['user']['turns']<3) {
			output("`2You get a short way outside of town but realize you feel far too tired to hike to the bandits headquarters today.");
			output("Maybe tomorrow you'll be up to it.`n`n");
			villagenav();
			break;
		}
		output("`2Once outside the town limits, you hike out to the place `3Dag`2 indicated and halt at the edge of the area.");
		output("The trees have been cut in strange patterns, and it is obvious from their nature that to go further is dangerous.");
		$session['user']['turns'] -= 3;
		output("Hefting your %s`2, you carefully make your way down the trail, looking out for any possibility of an ambush.`n`n",$session['user']['weapon']);
		if (e_rand(0,3)) {
			output("With a rush from the side, a maddened wolf rushes towards you!");
			output("You will have to fight one of the bandit's pets!");
			addnav("Fight the Beast",
					"runmodule.php?module=dagbandits&fight=wolffight");
		} else {
			output("Though you see plenty of evidence of attacks by the bloody shreds which are scattered through these woods, you manage to avoid any ambush.");
			output("In the heart of these woods, you start to hear parties of bandits moving freely, chatting amongst themselves about their exploits.");
			output("There are far more of them than you expected!");
			output("You realise you are in over your head, and try to find one alone to fight.");
			addnav("Search for a Bandit",
					"runmodule.php?module=dagbandits&op=searchbandit");
		}
		break;
	case "searchbandit":
		page_header("The Bandits' Camp");
		$rand=e_rand(1,5);
		switch($rand){
		case 1:
			output("`2As you thread your way through pockets of bandits, you suddenly hear a snarling and growling from some bushes!`n`n");
			output("With a rush from the side, a maddened wolf rushes towards you!");
			output("You will have to fight one of the bandits' pets!");
			addnav("Fight the Beast",
					"runmodule.php?module=dagbandits&fight=wolffight");
			break;
		case 2:
			output("`2As you thread your way through pockets of bandits, you hear a distinctive flurry of twanging sounds from behind you.");
			// As long as they have at least 1/2 their hitpoints they survive
			if (round($session['user']['hitpoints']/
						$session['user']['maxhitpoints'])) {
				output("With a leap that would impress your master, you dive behind a tree and hear the arrows thud into the ground and the tree.");
				output("Unfortunately, they also managed to hit your exposed leg. Ouch.`n`n");
				$hploss=round($session['user']['hitpoints']*(e_rand(1,4)*0.1));
				$session['user']['hitpoints']-=$hploss;
				output("Limping away surprisingly fast, you manage to get the arrow out in some convenient bushes.");
				output("You lose %s hitpoints!`n`n",$hploss);
				output("Do you want to keep searching in your weakened condition?");
				addnav("Keep Searching",
						"runmodule.php?module=dagbandits&op=searchbandit");
				addnav("V?Flee back to town","village.php");
			} else {
				output("`6Your vision blacks out as you fail to dodge the volley of arrows, your already badly injured body betraying you.`n`n");
				output("`%You have died!");
				output("You lose 10% of your experience, and your gold is stolen by the bandits!");
				output("Your soul drifts to the shades.");
				debuglog("was killed by the bandits archers in the Forest, losing " . $session['user']['gold'] . " gold");
				$session['user']['gold']=0;
				$session['user']['experience']*=0.9;
				$session['user']['alive']=false;
				$session['user']['hitpoints']=0;
				addnews("%s's body turned up, filled with arrows!",
						$session['user']['name']);
				addnav("Return to the News","news.php");
			}
			break;
		case 3:
		case 4:
		case 5:
			output("`2While you sneak through the woods, you spot a lone bandit walking towards you.");
			$status=get_module_pref("queststatus");
			$status=unserialize($status);
			if(!isset($status['qthree'])) $status['qthree'] = 0;
			$status['qthree']++;
			switch($status['qthree']){
			case 1:
				output("While he notices you surprisingly early, it is too late for him to call for help, so he draws his dagger and grimly attacks you.");
				addnav("Fight the Scout",
						"runmodule.php?module=dagbandits&fight=banditfight&bandit=1");
				break;
			case 2:
				output("He walks right past you, and you charge him so fast he doesn't have time to draw his bow! He curses and draws a slim dagger to fend off your attack.");
				addnav("Fight the Archer",
						"runmodule.php?module=dagbandits&fight=banditfight&bandit=2");
				break;
			case 3:
			case 4:
				output("He walks right past you, but though you charge him fast he quickly draws his sword and attacks you in the measured way of an experienced swordsman.");
				addnav("Fight the Swordsman",
						"runmodule.php?module=dagbandits&fight=banditfight&bandit=3");
				break;
			case 5:
				output("He walks right past you, and when you charge, he turns with a smile, saying that you know not the enemies you make before hefting a wickedly sharp axe and attacking you.");
				addnav("Fight the Captain",
						"runmodule.php?module=dagbandits&fight=banditfight&bandit=4");
				break;
			}
			$status=serialize($status);
			set_module_pref("queststatus",$status);
			break;
		}
		break;
	}
	$fight=httpget("fight");
	switch($fight){
	case "wolffight":
		$badguy = array(
			"creaturename"=>translate_inline("Bandit Wolf"),
			"creaturelevel"=>$session['user']['level']-1,
			"creatureweapon"=>translate_inline("Rabid Snapping"),
			"creatureattack"=>$session['user']['attack'],
			"creaturedefense"=>round($session['user']['defense']*0.75, 0),
			"creaturehealth"=>round($session['user']['maxhitpoints']*1.1, 0),
			"diddamage"=>0,
			"type"=>"quest"
		);
		$session['user']['badguy']=createstring($badguy);
		$battle=true;
		// Drop through
	case "wolffighting":
		page_header("The Bandits' Camp");
		require_once("lib/fightnav.php");
		include("battle.php");
		if ($victory) {
			output("`2The wolf collapses from its wounds, and as it stills you notice a strange symbol branded into its side.");
			output("However, you don't have time to look further, as you can hear people coming to investigate the noise already.");

			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`^Your staunch your own wounds with a small bit of moss from the ground nearby, stopping your bloodloss before you are completely dead.");
				$session['user']['hitpoints'] = 1;
			}
			$expgain=round($session['user']['experience']*(e_rand(2,4)*0.003), 0);
			$session['user']['experience']+=$expgain;
			output("`n`n`&You gain %s experience from this fight!",$expgain);
			output("`2Do you want to keep searching for a bandit to kill, or flee the woods?.");
			addnav("Keep Searching",
					"runmodule.php?module=dagbandits&op=searchbandit");
			addnav("V?Flee back to town","village.php");
		} elseif ($defeat) {
			output("`6Your vision blacks out as the wolf tears the throat out of your already badly injured body.");
			output("`n`n`%You have died! You lose 10% of your experience, and your gold is stolen by the bandits!");
			output("Your soul drifts to the shades.");
			$session['user']['gold']=0;
			$session['user']['experience']*=0.9;
			$session['user']['alive']=false;
			debuglog("was killed by a pet of the bandits in the Forest.");
			addnews("%s's body turned up, torn to shreds!",$session['user']['name']);
			addnav("Return to the News","news.php");
		} else {
			fightnav(true,true,"runmodule.php?module=dagbandits&fight=wolffighting");
		}
		break;
	case "banditfight":
		$bandit=httpget("bandit");
		switch($bandit){
		case 1:
			$badguy = array(
				"creaturename"=>translate_inline("Bandit Scout"),
				"creaturelevel"=>$session['user']['level']-1,
				"creatureweapon"=>translate_inline("Slim Dagger"),
				"creatureattack"=>round($session['user']['attack']*0.75, 0),
				"creaturedefense"=>round($session['user']['defense']*0.8, 0),
				"creaturehealth"=>$session['user']['maxhitpoints'],
				"diddamage"=>0,
				"type"=>"quest"
			);
			break;
		case 2:
			$badguy = array(
				"creaturename"=>translate_inline("Bandit Archer"),
				"creaturelevel"=>$session['user']['level']-1,
				"creatureweapon"=>translate_inline("Slim Dagger"),
				"creatureattack"=>round($session['user']['attack']*0.85, 0),
				"creaturedefense"=>round($session['user']['defense']*0.8, 0),
				"creaturehealth"=>$session['user']['maxhitpoints'],
				"diddamage"=>0,
				"type"=>"quest"
			);
			break;
		case 3:
			$badguy = array(
				"creaturename"=>translate_inline("Bandit Swordsman"),
				"creaturelevel"=>$session['user']['level'],
				"creatureweapon"=>translate_inline("Keen Blade"),
				"creatureattack"=>$session['user']['attack'],
				"creaturedefense"=>round($session['user']['defense']*0.9, 0),
				"creaturehealth"=>round($session['user']['maxhitpoints']*1.05, 0),
				"diddamage"=>0,
				"type"=>"quest"
			);
			break;
		case 4:
			$badguy = array(
				"creaturename"=>translate_inline("Bandit Captain"),
				"creaturelevel"=>$session['user']['level']+1,
				"creatureweapon"=>translate_inline("Wicked Axe"),
				"creatureattack"=>round($session['user']['attack']*1.15, 0),
				"creaturedefense"=>round($session['user']['defense']*0.9, 0),
				"creaturehealth"=>round($session['user']['maxhitpoints']*1.15, 0),
				"diddamage"=>0,
				"type"=>"quest"
			);
			break;
		}
		$session['user']['badguy']=createstring($badguy);
		$battle=true;
		// drop through
	case "banditfighting":
		page_header("The Bandits' Camp");
		require_once("lib/fightnav.php");
		$bandit=httpget("bandit");
		include("battle.php");
		if ($victory) {
			output("`2The bandit collapses as you finish him off, and you quickly take his identifying ring as proof that you killed him.`n`n");
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`^Your staunch your own wounds with a bit of cloth torn from the bandit's clothing, stopping your bloodloss before you are completely dead.`n");
				$session['user']['hitpoints'] = 1;
			}
			$expgain=round($session['user']['experience']*(e_rand(2,4)*0.003), 0);
			$session['user']['experience']+=$expgain;
			output("`&You gain %s experience from this fight!`n`n",$expgain);
			if (httpget("bandit")==4) {
				output("You have killed 5 Bandits! You have completed the quest!");
				if (get_module_setting("experience")>1) {
					$expgain=round($session['user']['experience']*(get_module_setting("experience")-1), 0);
					$session['user']['experience']+=$expgain;
					output("You gain %s experience!",$expgain);
				}
				set_module_pref("status",5);
				villagenav();
			} else {
				output("`2Do you want to keep searching for a bandit to kill, or flee the woods?.");
				addnav("Keep Searching","runmodule.php?module=dagbandits&op=searchbandit");
				addnav("V?Flee back to town","village.php");
			}
		} elseif ($defeat) {
			output("`2Your vision blacks out as the bandit slices your throat open and you collapse, gagging, to the ground.");
			output("You have failed your mission to kill one of their bands!`n`n");
			output("`%You have died!");
			output("You lose 10% of your experience, and your gold is stolen by the bandits!");
			debuglog("was killed by a Bandit and lost " . $session['user']['gold'] . " gold.");
			output("Your soul drifts to the shades.");
			$session['user']['gold']=0;
			$session['user']['experience']*=0.9;
			$session['user']['alive']=false;
			set_module_pref("status",3);
			require_once("modules/dagquests.php");
			dagquests_alterrep(-2);
			addnews("%s's body turned up, throat slit!",
					$session['user']['name']);
			addnav("Return to the News","news.php");
		} else {
			fightnav(true,true,"runmodule.php?module=dagbandits&fight=banditfighting&bandit=$bandit");
		}
		break;
	}
	page_footer();
}
?>
