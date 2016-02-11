<?php

require_once("lib/http.php");
require_once("lib/villagenav.php");

function dagmanticore_getmoduleinfo(){
	$info = array(
		"name"=>"Manticore Quest",
		"version"=>"1.0",
		"author"=>"`%Sneakabout`^",
		"category"=>"Quest",
		"download"=>"core_module",
		"settings"=>array(
			"Manticore Quest Settings,title",
			"rewardgold"=>"What is the gold reward for the Manticore Quest?,int|2000",
			"rewardgems"=>"What is the gem reward for the Manticore Quest?,int|3",
			"experience"=>"What is the quest experience multiplier for the Manticore Quest?,floatrange,1.01,1.2,0.01|1.1",
			"minlevel"=>"What is the minimum level for this quest?,range,1,15|10",
			"maxlevel"=>"What is the maximum level for this quest?,range,1,15|14",
		),
		"prefs"=>array(
			"Manticore Quest Preferences,title",
			"status"=>"How far has the player gotten in the Manticore Quest?,int|0",
		),
		"requires"=>array(
			"dagquests"=>"1.1|By Sneakabout",
		),
	);
	return $info;
}

function dagmanticore_install(){
	module_addhook("village");
	module_addhook("dragonkilltext");
	module_addhook("newday");
	module_addhook("dagquests");
	return true;
}

function dagmanticore_uninstall(){
	return true;
}

function dagmanticore_dohook($hookname,$args){
	global $session;
	switch ($hookname) {
	case "village":
		if ($session['user']['location']==
				getsetting("villagename", LOCATION_FIELDS)) {
			tlschema($args['schemas']['gatenav']);
			addnav($args['gatenav']);
			tlschema();
			if (get_module_pref("status")==1) {
				addnav("Search the Trails (1 turn)",
					"runmodule.php?module=dagmanticore&op=search");
			}
		}
		break;
	case "dragonkilltext":
		set_module_pref("status",0);
		break;
	case "newday":
		if (get_module_pref("status")==1 &&
				$session['user']['level']>(get_module_setting("maxlevel")+1)){
			set_module_pref("status",4);
			output("`n`6You hear that another adventurer defeated the manticore which had slaughtered the travellers.`0`n");
			require_once("modules/dagquests.php");
			dagquests_alterrep(-1);
		}
		break;
	case "dagquests":
		if ($args['questoffer']) break;
		if (get_module_setting("minlevel")<=$session['user']['level'] &&
				$session['user']['level']<=get_module_setting("maxlevel") &&
				!get_module_pref("status")) {
			output("He seems very busy, but when you ask him about work, he nods and leans in closer.`n`n");
			output("\"Yer just the person I be needin'. There be a wagon found destroyed, the sides filled with spikes. The survivor be tellin' the tale o' the trouble. I figger one manticore, maybe more, t' be claimin' that trail fer their territory. They be very aggressive, so ye should be havin' no trouble findin' the beasts, and yer trained well enough that I be willin' t' trust this t' ye. There be no bounty though 'cause we ain't let the information get out, but the wagon owner be killed in th' attack, so whatever ye find at the scene ye can keep.  Do ye be wantin' t' take on the beast?\"");
			output("You almost take a full second to consider how mean this creature might be, but you're a hero after all.");
			output("How hard could killing some small monster blocking the trails be?");
			addnav("Take the Job","runmodule.php?module=dagmanticore&op=take");
			addnav("Refuse","runmodule.php?module=dagmanticore&op=nottake");
			$args['questoffer']=1;
		}
		break;
	}
	return $args;
}

function dagmanticore_runevent($type) {
}

function dagmanticore_run(){
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
		output("`3Dag nods, and tells you which trail the caravan was lost down.");
		output("He advises you to prepare before you go for the monster.");
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
		output("`3Dag chuckles a little then turns away, spitting to one side.");
		output("You leave that table, feeling a little cowardly.");
		set_module_pref("status",4);
		addnav("I?Return to the Inn","inn.php");
		require_once("modules/dagquests.php");
		dagquests_alterrep(-1);
		break;
	case "search":
		page_header("The Trails");
		if (!$session['user']['turns']) {
			output("`2You feel far too tired to hike to the trails today.");
			output("Maybe tomorrow.`n`n");
			villagenav();
			page_footer();
		}
		output("`2You hitch a ride with a wagon, and they drop you off at the start of the trail, leaving you to walk the remaining distance on your own.");
		$session['user']['turns']--;
		output("You start down the trail, looking for the remains of the wagon.");
		$rand=e_rand(1,7);
		switch($rand){
		case 1:
		case 2:
			output("You walk down the trail, eyes peeled, strung tight for ages, until you spot a wagon travelling towards you.");
			output("Confused, as you thought this part of the trail was closed until the threat had been dealt with, you hail the driver, only to find that you were left at the wrong trail!`n`n");
			output("Fortunately he is kind enough to give you a lift back to town and you return, your time wasted.");
			villagenav();
			break;
		case 3:
		case 4:
			output("You walk down the trail, eyes peeled, strung tight for ages, until you spot what seems to be the caravan in the distance.");
			output("However, as you drop your guard to move closer you hear a howl to your left and you back away as a grey wolf stalks towards you!");
			output("You must ready your %s`2 to defend yourself!",$session['user']['weapon']);
			addnav("Fight the Wolf","runmodule.php?module=dagmanticore&fight=wolffight");
			break;
		case 5:
		case 6:
		case 7:
			output("You walk down the trail, eyes peeled, strung tight for ages, until you spot what seems to be the wagon in the distance.");
			output("Remaining careful, you circle round, and spot the hideous creature lying in wait by the side of the road, spiked tail waving as it awaits its prey.");
			output("You charge the monster, which whips around to face you and hisses before pouncing with its terrible claws!");
			addnav("Fight the Manticore","runmodule.php?module=dagmanticore&fight=manticorefight");
			break;
		}
		break;
	}
	$fight=httpget("fight");
	switch($fight){
	case "wolffight":
		$badguy = array(
			"creaturename"=>translate_inline("Grey Wolf"),
			"creaturelevel"=>$session['user']['level']-1,
			"creatureweapon"=>translate_inline("Hungry Jaws"),
			"creatureattack"=>$session['user']['attack'],
			"creaturedefense"=>round($session['user']['defense']*0.75, 0),
			"creaturehealth"=>round($session['user']['maxhitpoints']*1.1, 0),
			"diddamage"=>0,
			"type"=>"quest"
		);
		$session['user']['badguy']=createstring($badguy);
		$battle=true;
		// drop through
	case "wolffighting":
		page_header("The Trails");
		require_once("lib/fightnav.php");
		include("battle.php");
		if ($victory) {
			output("`2The wolf collapses on the ground, bleeding from its wounds.");
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`^Your staunch your own wounds with a bit of moss growing nearby, stopping your bloodloss before you are completely dead.`n");
				$session['user']['hitpoints'] = 1;
			}
			output("`2You quickly flee the scene, hoping to avoid the rest of the pack.`n`n");
			$expgain=round($session['user']['experience']*(e_rand(2,4)*0.002));
			$session['user']['experience']+=$expgain;
			output("`&You gain %s experience from this fight!",$expgain);
			output("`2You return to town, shaken by the attack.");
			villagenav();
		} elseif ($defeat) {
			output("`6Your vision blacks out as the wolf tears the throat out of your already badly injured body.`n`n");
			output("`%You have died!`n");
			output("You lose 10% of your experience, and your gold is stolen by scavengers!`n");
			output("Your soul drifts to the shades.");
			$session['user']['gold']=0;
			$session['user']['experience']*=0.9;
			$session['user']['alive'] = false;
			debuglog("was killed by a wolf on a trail.");
			addnews("%s's body turned up, torn to shreds!",
				$session['user']['name']);
			addnav("Return to the News","news.php");
		} else {
			fightnav(true,true,
				"runmodule.php?module=dagmanticore&fight=wolffighting");
		}
		break;
	case "manticorefight":
		$badguy = array(
			"creaturename"=>translate_inline("Manticore"),
			"creaturelevel"=>$session['user']['level']+2,
			"creatureweapon"=>translate_inline("Terrible Claws"),
			"creatureattack"=>round($session['user']['attack']*1.15, 0),
			"creaturedefense"=>round($session['user']['defense']*1.1, 0),
			"creaturehealth"=>round($session['user']['maxhitpoints']*1.4, 0),
			"diddamage"=>0,
			"type"=>"quest"
		);
		apply_buff('manticorespike',array(
			"name"=>"`\$Manticore Spikes",
			"roundmsg"=>"The manticore flicks its tail over its head and sends a volley of spikes at you!",
			"effectmsg"=>"You are hit by one of the spikes for `4{damage}`) points!",
			"effectnodmgmsg"=>"You dodge one of the spikes!",
			"rounds"=>20,
			"wearoff"=>"The monster runs out of spikes!",
			"minioncount"=>3,
			"maxgoodguydamage"=>$session['user']['level'],
			"schema"=>"module-dagmanticore"
		));
		$session['user']['badguy']=createstring($badguy);
		$battle=true;
		// drop through
	case "manticorefighting":
		page_header("The Trails");
		require_once("lib/fightnav.php");
		include("battle.php");
		if ($victory) {
			output("`2Mortally wounded by your blows, the manticore falls to the ground with a scream which resonates over the hills!");
			output("You have avenged the spirits of the travellers who died in the wagon!`n`n");
			$expgain = round($session['user']['experience'] *
					(get_module_setting("experience")-1), 0);
			$session['user']['experience']+=$expgain;
			output("`&You gain %s experience from this fight!",$expgain);
			if ($session['user']['hitpoints']<1) {
				// Coping with the doublebuffkill scenario
				output("Near to death, you spot a potion in the pouch of a dead traveller.");
				output("Mortally wounded, you crawl there and quickly down the potion.");
				output("It eases your wounds somewhat, but you are still barely able to stand.");
				$session['user']['hitpoints']=1;
			}
			$goldgain=get_module_setting("rewardgold");
			$gemgain=get_module_setting("rewardgems");
			$session['user']['gold']+=$goldgain;
			$session['user']['gems']+=$gemgain;
			debuglog("found $goldgain gold and $gemgain gems after slaying a manticore.");
			output("`n`n`2With the monster dead, you search through the wagon, but most of the goods are missing!");
			output("Someone else has already been here and looted it without killing the manticore!");
			if ($goldgain && $gemgain) {
				output("All you can find is `^%s gold`2 lying around and `%%s %s`2 which were hidden by the wagon master.",$goldgain,$gemgain,translate_inline(($gemgain==1)?"gem":"gems"));
			} elseif ($gemgain) {
				output("All you can find is `%%s %s`2 which were hidden by the wagon master.",$gemgain,translate_inline(($gemgain==1)?"gem":"gems"));
			} elseif ($goldgain) {
				output("All you can find is `^%s gold`2 lying around.",$goldgain);
			} else {
				output("You don't find anything!");
			}
			output("You make your way back to the fork in the trail, and tell the next wagon the news on the way back to town.");
			set_module_pref("status",2);
			addnews("%s defeated a Manticore on the trails! The victims have been avenged!",$session['user']['name']);
			villagenav();
			strip_buff("manticorespike");
			require_once("modules/dagquests.php");
			dagquests_alterrep(3);
		} elseif ($defeat) {
			output("`2You fall backwards to the ground as the final volley of spikes from the manticore pierces your skull.");
			output("You have failed in your mission to kill the foul creature!`n`n");
			output("`%You have died!`n");
			output("You lose 10% of your experience, and your gold is stolen by scavengers!`n");
			output("Your soul drifts to the shades.");
			debuglog("was killed by a manticore on a trail and lost ".
					$session['user']['gold']." gold.");
			$session['user']['gold']=0;
			$session['user']['experience']*=0.9;
			$session['user']['alive'] = false;
			set_module_pref("status",3);
			addnews("%s was slain by a Manticore on a trail!",
					$session['user']['name']);
			addnav("Return to the News","news.php");
			strip_buff("manticorespike");
			require_once("modules/dagquests.php");
			dagquests_alterrep(-1);
		} else {
			fightnav(true,true,
				"runmodule.php?module=dagmanticore&fight=manticorefighting");
		}
		break;
	}
	page_footer();
}
?>
