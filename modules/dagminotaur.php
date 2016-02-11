<?php

require_once("lib/http.php");
require_once("lib/villagenav.php");

function dagminotaur_getmoduleinfo(){
	$info = array(
		"name"=>"Minotaur Quest",
		"version"=>"1.0",
		"author"=>"`%Sneakabout`^",
		"category"=>"Quest",
		"download"=>"core_module",
		"settings"=>array(
			"Minotaur Quest Settings,title",
			// reward types.
			"rewardgold"=>"What is the gold reward for the Minotaur Quest?,int|1000",
			"rewardgems"=>"What is the gem reward for the Minotaur Quest?,int|2",
			"experience"=>"What is the quest experience multiplier for the Minotaur Quest?,floatrange,1.01,1.1,0.01|1.04",
			"minlevel"=>"What is the minimum level for this quest?,range,1,15|5",
			// in the future min DKs might get put here too.
			"maxlevel"=>"What is the maximum level for this quest?,range,1,15|9",
		),
		"prefs"=>array(
			"Minotaur Quest Preferences,title",
			"status"=>"How far has the player gotten in the Minotaur Quest?,int|0",
			// 0 is not taken, 1 is in progress, 2 is completed, 3 is failed,
			// 4 is failed through choice/ignoring it and 5 is reward pending.
			// Above 5 can be used for stages of completion.
        ),
        "requires"=>array(
	       "dagquests"=>"1.1|By Sneakabout",// central module to hook into.
		),
	);
	return $info;
}

function dagminotaur_install(){
	module_addhook("village");
	module_addhook("dragonkilltext");
	module_addhook("newday");
	module_addhook("dagquests");
	return true;
}

function dagminotaur_uninstall(){
	return true;
}

function dagminotaur_dohook($hookname,$args){
	global $session;
	switch ($hookname) {
	case "village":
		if ($session['user']['location']==
				getsetting("villagename", LOCATION_FIELDS)) {
			tlschema($args['schemas']['gatenav']);
			addnav($args['gatenav']);
			tlschema();
			// The turns get checked later, so that people don't ask where
			// the link is :(
			if (get_module_pref("status")==1) {
				addnav("Search the Caves (1 turn)",
						"runmodule.php?module=dagminotaur&op=search");
			}
		}
		break;
	case "dragonkilltext":
		// DK reset.
		set_module_pref("status",0);
		break;
	case "newday":
		if (get_module_pref("status")==1 &&
				$session['user']['level']>get_module_setting("maxlevel")) {
			// if they get beyond the level range.
			set_module_pref("status",4);
			output("`n`6You hear that another adventurer defeated the minotaur plaguing the Caves.`0`n");
			require_once("modules/dagquests.php");
			dagquests_alterrep(-1);
		}
		break;
	case "dagquests":
		if (get_module_pref("status")==5) {
			// giving the reward if quest completed. No chance of both
			// triggering.
			$goldgain=get_module_setting("rewardgold");
			$gemgain=get_module_setting("rewardgems");
			$session['user']['gold']+=$goldgain;
			$session['user']['gems']+=$gemgain;
			debuglog("got a reward of $goldgain gold and $gemgain gems for slaying a minotaur.");
			if ($goldgain && $gemgain) {
				output("`3`n`nYou hand Dag the minotaur's head, and Dag pays you the bounty of `^%s gold`3 and a pouch of `%%s %s`3!",$goldgain,$gemgain,translate_inline(($gemgain==1)?"gem":"gems"));
			} elseif ($gemgain) {
				output("`3`n`nYou hand Dag the minotaur's head, and Dag pays you the bounty of a pouch of `%%s %s`3!",$gemgain,translate_inline(($gemgain==1)?"gem":"gems"));
			} elseif ($goldgain) {
				output("`3`n`nYou hand Dag the minotaur's head, and Dag pays you the bounty of `^%s gold`3!",$goldgain);
			} else {
				output("`3`n`nYou hand Dag the minotaur's head, but Dag cannot find the bounty to pay you!");
			}
			set_module_pref("status",2);
			require_once("modules/dagquests.php");
			dagquests_alterrep(2);
			$args['questoffer']=1;
			// complete after reward is given.
		}
		// Another quest is set!
		if ($args['questoffer']) break;

		// checking requirements and setting status.
		if (get_module_setting("minlevel")<=$session['user']['level'] &&
				$session['user']['level']<=get_module_setting("maxlevel") &&
				!get_module_pref("status")) {
			output("He seems very busy, but when you ask him about work, he looks at you carefully and motions you closer.`n`n");
			output("\"Aye, there be something ye might be helpin' me wit'.... there be rumours of a half-man beast that be preyin' on adventurers. It be operatin' from a nearby cave. It seems t' be reasonably smart, and the normal guards ain't bein' the sort to take the thing on. Ye look like ye can handle yerself, and there be a bounty from one o' the relatives if'n yer interested.  Do ye be takin' the job?\"`n`n");
			output("It almost crosses your mind to wonder why Dag would be offering this to you, but the caves aren't that far away after all.");
			output("It shouldn't be any problem to search them.");
			addnav("Take the Job","runmodule.php?module=dagminotaur&op=take");
			addnav("Refuse","runmodule.php?module=dagminotaur&op=nottake");
			// Necessary! If this wasn't there then you would get presented
			// with a quest you might not want to do and miss other ones.
			$args['questoffer']=1;
		}
		break;
	}
	return $args;
}

function dagminotaur_runevent($type) {
}

function dagminotaur_run(){
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
		output("`3Dag nods, and gives you directions to the rough area the beast has been seen in, as well as a description of a bull-headed humanoid, tough and strong.");
		output("You leave the table, ready to seek out the beast.");
		// In progress.
		set_module_pref("status",1);
		addnav("I?Return to the Inn","inn.php");
		break;
	case "nottake":
		$iname = getsetting("innname", LOCATION_INN);
		page_header($iname);
		rawoutput("<span style='color: #9900FF'>");
		output_notl("`c`b");
		output($iname);
		output_notl("`b`c");
		output("`3Dag nods, spits to one side and turns away, disgusted with your cowardice.");
		// Failed through choice
		set_module_pref("status",4);
		addnav("I?Return to the Inn","inn.php");
		break;
	case "search":
		page_header("The Caves");
		if (!$session['user']['turns']) {
			// coping with having the link appear at all times.
			output("`2You feel far too tired to hike to the caves today.");
			output("Maybe tomorrow.`n`n");
			villagenav();
			page_footer();
		}
		output("`2You hike up to the area riddled with caves, and start to check them out individually for traces of the beast.`n`n");
		$session['user']['turns']--;
		$rand=e_rand(1,10);
		switch($rand){// various things they can find.
		case 1:
		case 2:
			output("You search through the caves for a while, finding nothing but bleached bones and dust.");
			output("Dispirited after a few hours, you trudge back to the town and look for something else to do.");
			villagenav();
			break;
		case 3:
		case 4:
			output("You wander through the caves for a while, eventually hearing some cries for help from a distance.");
			output("You rush over, and find an injured traveller who had been attempting to travel across the countryside.");
			output("Spikes protude from his chest, and he is obviously mortally wounded - you do your best, but he dies after choking something about an attack from a powerful monster.");
			output("You hurry back to town, watching your back for whatever attacked the traveller.");
			villagenav();
			break;
		case 5:
			output("You wander through the caves for a while, finding that tracking something across rock is extremely difficult.");
			output("While looking in vain through an empty cave, you discover intricate patterns carved into the rock!");
			output("However, you're more interested in the gem embedded in the rock, and you pry it out as a souvenir before returning to town.");
			debuglog("gained a gem from an ancient cave");
			$session['user']['gems']++;
			villagenav();
			break;
		case 6:
			output("You wander through the caves for a while before hearing a roar from the top of a nearby outcropping!");
			output("A mountain lion has spotted you, and bounds towards you, snarling.");
			output("You have nowhere to run to, so you ready your %s`2 to fight!",$session['user']['weapon']);
			addnav("Fight the Lion",
					"runmodule.php?module=dagminotaur&fight=lionfight");
			break;
		case 7:// bingo!
		case 8:
		case 9:
		case 10:
			output("You wander through the caves for a while before finding a trail of blood from a dropped backpack.");
			output("You rush following the trail across the rocks to a sandy outcrop where you can see the minotaur, gorging on the body of the dead traveller in front of a small cave.");
			output("The beast sniffs the air, and you know you have been detected - you draw your %s`2 and charge down as the beast prepares with its club, snarling all the while.",$session['user']['weapon']);
			addnav("Fight the Minotaur","runmodule.php?module=dagminotaur&fight=minotaurfight");
			break;
		}
		break;
	}
	// handle fights separately - you can't use op because the fight
	// script uses that.
	$fight=httpget("fight");
	switch($fight){
	case "lionfight":
		// Set stats, but only at the start of the fight.
		$badguy = array(
			"creaturename"=>translate_inline("Lion"),
			"creaturelevel"=>$session['user']['level']-1,
			"creatureweapon"=>translate_inline("Savage Claws"),
			"creatureattack"=>$session['user']['attack'],
			"creaturedefense"=>round($session['user']['defense']*0.8, 0),
			"creaturehealth"=>round($session['user']['maxhitpoints']*0.9, 0),
			"diddamage"=>0,
			"type"=>"quest"
		);
		$session['user']['badguy']=createstring($badguy);
		$battle=true;
		// Drop through
	case "lionfighting":
		page_header("The Caves");
		require_once("lib/fightnav.php");
		include("battle.php");
		if ($victory) {
			// not the main quest, put them back in the village.
			output("`2The lion collapses on the ground, bleeding from its wounds.");
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`^Your staunch your own wounds with a bit of moss growing nearby, stopping your bloodloss before you are completely dead.`n");
				$session['user']['hitpoints'] = 1;
			}
			output("`2You quickly flee the scene, hoping that there are not more of them around.`n`n");
			$expgain=round($session['user']['experience']*(e_rand(2,4)*0.002));
			$session['user']['experience']+=$expgain;
			output("`&You gain %s experience from this fight!",$expgain);
			output("`2You return to town, shaken by your experience.");
			villagenav();
		} elseif ($defeat) {
			// not the main quest, they get to keep trying.
			output("`2Your vision blacks out as the lion tears the throat out of your already badly injured body.`n`n");
			output("`%You have died!");
			output("You lose 10% of your experience, and your gold is stolen by scavengers!");
			output("Your soul drifts to the shades.");
			debuglog("was killed by a lion and lost ".
					$session['user']['gold']." gold.");
			$session['user']['gold']=0;
			$session['user']['experience']*=0.9;
			$session['user']['alive'] = false;
			addnews("%s was slain by a Lion in the Caves!",
					$session['user']['name']);
			addnav("Return to the News","news.php");
		} else {
			fightnav(true,true,
				"runmodule.php?module=dagminotaur&fight=lionfighting");
		}
		break;
	case "minotaurfight":
		// main creature stats, make sure it isn't too easy.
		$badguy = array(
			"creaturename"=>translate_inline("Minotaur"),
			"creaturelevel"=>$session['user']['level']+1,
			"creatureweapon"=>translate_inline("Bone Club"),
			"creatureattack"=>round($session['user']['attack']*1.15, 0),
			"creaturedefense"=>round($session['user']['defense']*0.9, 0),
			"creaturehealth"=>round($session['user']['maxhitpoints']*1.2, 0),
			"diddamage"=>0,
			"type"=>"quest"
		);
		$session['user']['badguy']=createstring($badguy);
		$battle=true;
		// drop through
	case "minotaurfighting":
		page_header("The Caves");
		require_once("lib/fightnav.php");
		include("battle.php");
		if ($victory) {
			// they've won the quest..... but the reward isn't here!
			// Set the reward flag!
			output("`2The minotaur collapses to the ground with a thud, sending up a cloud of dust!");
			output("You have avenged the deaths of many travellers!`n`n");
			$expgain=round($session['user']['experience']*(get_module_setting("experience")-1), 0);
			$session['user']['experience']+=$expgain;
			output("`&You gain %s experience from this fight!`n`n",$expgain);
			output("`2You lop off the beast's head, and stash the gruesome thing in your backpack.");
			// Reward flag
			set_module_pref("status",5);
			addnews("%s defeated a Minotaur in the Caves! The deaths of many travellers have been avenged!",$session['user']['name']);
			villagenav();
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`^In one corner of the cave, amid the bones of a corpse, you spy the distinctive bottle of a healer's potion.");
				output("Grabbing for it as your vision dims, you see that a mere drop remains in the bottom.");
				output("Quickly, you drink it any way, hoping it will be enough.");
				$session['user']['hitpoints'] = 1;
			}
		} elseif ($defeat) {
			// Failed against the quest creature...
			output("`2Your vision blacks out as the minotaur clubs you to the ground.");
			output("You have failed your task to avenge the travellers!`n`n");
			output("`%You have died!`n");
			output("You lose 10% of your experience, and your gold is stolen by the minotaur!`n");
			output("Your soul drifts to the shades.");
			debuglog("was killed by a minotaur in the Caves and lost ".
					$session['user']['gold']." gold.");
			$session['user']['gold']=0;
			$session['user']['experience']*=0.9;
			$session['user']['alive'] = false;
			// They fail it!
			set_module_pref("status",3);
			addnews("%s was slain by a Minotaur in the Caves!",
					$session['user']['name']);
			addnav("Return to the News","news.php");
			require_once("modules/dagquests.php");
			dagquests_alterrep(-1);
		} else {
			fightnav(true,true,
				"runmodule.php?module=dagminotaur&fight=minotaurfighting");
		}
		break;
	}
	page_footer();
}
?>
