<?php
// translator ready
// addnews ready
// mail ready

function newbieisland_getmoduleinfo(){
	$info = array(
		"name"=>"Newbie Island",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"General",
		"download"=>"core_module",
		"settings"=>array(
			"Newbie Island,title",
			"villagename"=>"Name for the newbie island|Isle of Wen",
			"chatiscentralvillage"=>"Is chat actually tied to the central village?,bool|0"
		),
		"prefs"=>array(
			"Newbie Island,title",
			"leftisland"=>"Left the newbie island,bool|0",
		),
	);
	return $info;
}

function newbieisland_install(){
	module_addhook("newday");
	module_addhook("villagetext");
	module_addhook("stabletext");
	module_addhook("charstats");
	module_addhook("validlocation");
	module_addhook("moderate");
	module_addhook("changesetting");
	module_addhook("forest");
	module_addhook("travel");
	module_addhook("village-desc");
	module_addhook("battle-defeat");
	module_addhook("pvpcount");
	module_addhook_priority("everyhit-loggedin",25);
	module_addhook("scrylocation");
	return true;
}

function newbieisland_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	return true;
}

function newbieisland_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	// to handle this.
	// Pass it as an arg?
	global $session,$resline;
	$city = get_module_setting("villagename");

	switch($hookname){
	case "everyhit-loggedin":
		global $SCRIPT_NAME;

		// We need to do this so that we can do the location test
		// correctly and exclude non-basic races, and all the gewgaws on
		// the newday page(s).
		if ($SCRIPT_NAME == "newday.php") newbieisland_checkcity();

		// Exit early if the user isn't in the newbie island.
		if ($session['user']['location'] != $city) break;
		// Do not block anything in the grotto!
		if ($SCRIPT_NAME == "superuser.php") break;
		// actually since we're doing this sorta globally, let's just
		// do it globally.
		// Block all modules by default
		blockmodule(true);
		// Make sure to unblock ourselves
		unblockmodule("newbieisland");
		// You need to explicitly allow newbies to interact with a module
		// in the village or forest
		unblockmodule("tutor");
		unblockmodule("raceelf");
		unblockmodule("racehuman");
		unblockmodule("racedwarf");
		unblockmodule("racetroll");
		unblockmodule("specialtydarkarts");
		unblockmodule("specialtythiefskills");
		unblockmodule("specialtymysticpower");
		unblockmodule("specialtychickenmage");
		// Even newbies get advertising
		unblockmodule("advertising");
		unblockmodule("advertising_google");
		unblockmodule("advertising_amazon");
		unblockmodule("advertising_splitreason");
		unblockmodule("funddrive");
		unblockmodule("funddriverewards");
		unblockmodule("customeq");
		unblockmodule('expbar');
		unblockmodule("healthbar");
		unblockmodule("serversuspend");
		unblockmodule("timeplayed");
		unblockmodule("collapse");
		unblockmodule("mutemod");
		unblockmodule("faqmute");
		unblockmodule("extlinks");
		unblockmodule("pvpimmunity");
		unblockmodule("deputymoderator");
		unblockmodule("unclean");
		unblockmodule("stattracker");
		//Let newbies see the Travel FAQ
		//Nobody ever looks at the FAQ more than once
		//so newbies have to see it right at the start
		if ($SCRIPT_NAME == "petition.php") unblockmodule("cities");
		break;
	case "pvpcount":
		if ($args['loc'] != $city) break;
		$args['handled'] = 1;
		break;
	case "battle-defeat":
		if ($session['user']['location'] != $city) break;
		global $options;
		static $runonce = false;
		if ($runonce !== false) break;
		$runonce = true;
		if ($options['type'] == 'forest') {
			rawoutput($args['fightoutput']);
			output("`n`n`\$You have been slain by %s!",$args['creaturename']);
			addnav("Continue","runmodule.php?module=newbieisland&op=resurrect");
			page_footer();
		}
		break;
	case "changesetting":
		// Ignore anything other than villagename setting changes
		if ($args['setting'] == "villagename" && $args['module']=="newbieisland") {
			if ($session['user']['location'] == $args['old'])
				$session['user']['location'] = $args['new'];
			$sql = "UPDATE " . db_prefix("accounts") .
				" SET location='" . addslashes($args['new']) .
				"' WHERE location='" . addslashes($args['old']) . "'";
			db_query($sql);
			if (is_module_active("cities")) {
				$sql = "UPDATE " . db_prefix("module_userprefs") .
					" SET value='" . addslashes($args['new']) .
					"' WHERE modulename='cities' AND setting='homecity'" .
					"AND value='" . addslashes($args['old']) . "'";
				db_query($sql);
			}
		}
		break;
	case "newday":
		newbieisland_checkcity();
		global $session;
		if ($session['user']['location'] == $city){
			$turns = getsetting("turns",10);
			$turns = round($turns/2);
			$args['turnstoday'] .= ", Newbie Island: $turns";
			$session['user']['turns']+= $turns;
			output("`n`&The very air of this island invigorates you; you receive `^%s`& turns!`n`0",$turns);
			apply_buff("newbiecoddle",array(
				"name"=>"",
				"rounds"=>-1,
				"minioncount"=>1,
				"mingoodguydamage"=>0,
				"maxgoodguydamage"=>"(<hitpoints><<maxhitpoints>?-1:0)",
				"effectfailmsg"=>"`#Eibwen`3 flits past and heals you for {damage}.",
				"effectnodmgmsg"=>""
				)
			);
		}
		break;
	case "validlocation":
		if (is_module_active("cities"))
			$args[$city]="village-newbie";
		break;
	case "moderate":
			tlschema("commentary");
			$args["village-newbie"]=sprintf_translate("%s", $city);
			tlschema();
		break;
	case "travel":
		$capital = getsetting("villagename", LOCATION_FIELDS);
		$hotkey = substr($city, 0, 1);
		tlschema("module-cities");
		if ($session['user']['superuser'] & SU_EDIT_USERS){
			addnav("Superuser");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city&su=1");
		}
		tlschema();
		break;
	case "forest":
		if ($session['user']['location'] == $city){
			blocknav("forest.php?op=search&type=suicide");
			blocknav("forest.php?op=search&type=thrill");
			blocknav("runmodule.php?module=outhouse");
			if ($session['user']['level'] >= 5){
				output("`^This forest no longer poses any challenge to your superior skills.");
				output("You feel the urge to move forward in the world, to seek out new adventures.");
				blocknav("forest.php?op=search",true);
			}
		}
		break;
	case "villagetext":
		newbieisland_checkcity();
		if ($session['user']['location'] == $city){
			$args['text']=array("`&`c`b%s`b`cYou are in a village whose very buildings emanate with the feel of magic.  Around you are the scared looking faces of other young adventurers.  You have no idea exactly how it is that you got to this place, but you feel that it is very safe to remain here for some time.`n", $city);
			$args['schemas']['text'] = "module-newbieisland";
			$args['clock']="`n`7From the position of the sun, you can tell that it is: `&%s`7.`n";
			$args['schemas']['clock'] = "module-newbieisland";
			//newbies don't want to know what day it is.
			$args['title']=array("%s, Home to New Adventurers", $city);
			$args['schemas']['title'] = "module-newbieisland";
			$args['sayline']="says";
			$args['schemas']['sayline'] = "module-newbieisland";
			$args['talk']="`n`&Nearby some local residents talk:`n";
			$args['schemas']['talk'] = "module-newbieisland";
			$new = get_module_setting("newest-$city", "cities");
			if ($new != 0) {
				$sql =  "SELECT name FROM " . db_prefix("accounts") .
					" WHERE acctid='$new'";
				$result = db_query_cached($sql, "newest-$city");
				$row = db_fetch_assoc($result);
				$args['newestplayer'] = $row['name'];
				$args['newestid']=$new;
			} else {
				$args['newestplayer'] = $new;
				$args['newestid']="";
			}
			if ($new == $session['user']['acctid']) {
				$args['newest']="`n`7As you wander your new home, you feel your jaw dropping at the wonders around you.";
			} else {
				$args['newest']="`n`7Wandering the island, looking frightened, is `&%s`7.";
			}
			$args['schemas']['newest'] = "module-newbieisland";
			if (get_module_setting("chatiscentralvillage")){
				$args['section']="village";
			}else{
				$args['section']="village-newbie";
			}
			$args['gatenav']="Village Gates";
			$args['fightnav']="Village Gates";
			$args['infonav']="Other";
			$args['schemas']['gatenav'] = "module-newbieisland";
			$args['schemas']['fightnav'] = "module-newbieisland";
			$args['schemas']['infonav'] = "module-newbieisland";
			blocknav("pvp.php");
			blocknav("lodge.php");
			blocknav("gypsy.php");
			blocknav("pavilion.php");
			blocknav("inn.php");
			blocknav("stables.php");
			blocknav("gardens.php");
			blocknav("rock.php");
			blocknav("clan.php");
			blocknav("runmodule.php",true);
			blocknav("mercenarycamp.php");
			blocknav("hof.php");
			// Make sure that Blusprings can show up on newbie island.
			unblocknav("train.php");
			//if you want your module to appear in the newbie village, you'll have to hook on village
			//and unblocknav() it.  I warn you, very very few modules will ever be allowed in the newbie
			//village and get support for appearing in the core distribution; one of the major reasons
			//FOR the newbie village is to keep the village very simple for new players.
		}
		break;
	case "village-desc":
		if ($session['user']['location'] == $city){
			addnav($args['gatenav']);
			addnav(array("Leave %s",$city),"runmodule.php?module=newbieisland&op=leave");
			unblocknav("runmodule.php?module=newbieisland&op=leave");
		}
		break;
	case "scrylocation":
		if (get_module_setting("chatiscentralvillage")) {
			$flipped = array_flip($args);
			if (isset($flipped['village-newbie'])) {
				unset($flipped['village-newbie']);
				$args = array_flip($flipped);
			}
		}
		break;
	}
	return $args;
}

function newbieisland_checkcity(){
	global $session;
	$city = get_module_setting("villagename");
	if (!get_module_pref("leftisland") && $session['user']['dragonkills'] ==0 && $session['user']['level'] <= 5){
		$session['user']['location'] = $city;
	}
}

function newbieisland_run(){
	global $session;
	require_once("lib/villagenav.php");
	switch(httpget("op")){
	case "leave":
		addnav("Stay");
		villagenav();
		addnav("Leave");
		addnav("Build a raft and set sail","runmodule.php?module=newbieisland&op=raft");
		page_header("Leave this place");
		output("`3You stroll down to the shore of this strange island.");
		output("In the distance, you can just make out the dark outlines of another land.");
		output("Nearby, you can see the discarded tools left here by another adventurer, along with some wooden planks and some heavy twine.");
		output("It occurs to you that these materials would make an excellent raft, which you could use to leave this place.");
		if ($session['user']['level'] <= 4){
			output("`n`n`#You are not so certain that you want to leave this island.");
			output("It feels so safe here on the island, and you're not quite sure that you really want to leave the peaceful solitude behind.");
		}else{
			output("`n`n`#Confident that you're ready for any challenge the world has to throw at you, you are ready to rid yourself of this island, and seek out adventure in the world at large.");
		}
		$city = get_module_setting("villagename");
		output("`n`n`^Once you leave %s, you can never journey back to it.",$city);
		page_footer();
		break;
	case "raft":
		page_header("The Raft");
		output("`#Without further thought, you set about crafting your raft.");
		output("The task is quickly accomplished, and with steadfast determination, you float the tiny barge, set foot in it, and push off shore.");
		output("You drift from the shore slowly at first, toward darker waters.");
		output("As you approach the obvious dropoff from the island, you feel your small craft begin to build up speed.");
		output("It seems as if a strong current has you in its grasp, and is ushering you rapidly toward the ever growing shadow that is the land you could see from shore.");
		output("`n`nSoon, you are caught in the shadow of the mighty land, and looking back you can barely make out the distant speck of soil where you started.");
		output("At first you are gleeful that your raft has served its purpose so well, but soon you begin to worry as the stormy shoals of this new place loom upon you.");
		output("Regretfully you did not build in any navigational means, and just as you near the shore, a mighty wave lifts you and your craft, slamming you against the beach with a mighty, plank-shattering thud.");
		output("`n`nYou lay in the sand, dazed and bleeding for a while.");
		output("As your short life begins to flash before your eyes, you notice a pair of eyes you do not recognize interfering with the visions.");
		output("Slowly, your head begins to clear, and you realize the eyes belong to a beautiful woman who is leaning over you.");
		output("You look around.");
		output("Your surroundings are no longer the beach where you landed, but instead a small hut.");
		output("Looking down, you realize your wounds have been bandaged, and your head is now clear of the death haze that once surrounded it.");
		output("`n`nWith much effort, you try to thank the woman for saving your life, but she places her fingers on your lips to silence you.");
		output("The effort to speak was all you could handle, and you soon pass into unconsciousness again.");
		output("`n`nYou wake, and an unknown amount of time has passed.");
		output("Looking around again, you discover that your surroundings have once again shifted.");
		output("You now lay on a soft mat with a feather pillow in a forest.");
		output("Nearby, you can hear the sounds of a village, and you feel as though much strength has come back.");
		output("Getting up from your mat, you are ready to take on the world once more.");
		set_module_pref("leftisland",true);
		addnav("Seek out the village","village.php");
		if (is_module_active("cities")) {
			//new farmthing, set them to wandering around this city.
			set_module_setting("newest-$city",
					$session['user']['acctid'],"cities");
			$session['user']['location'] = get_module_pref("homecity","cities");
		}else{
			$session['user']['location'] = getsetting("villagename", LOCATION_FIELDS);
		}
		page_footer();
		break;
	case "resurrect":
		page_header("Golinda");
		output("`^Clutching your side, you fall to the ground, the world dimming around you.");
		output("Just as the last motes of light fade from your vision, a bright yellow light comes upon you.");
		output("You feel yourself lifted from the ground, now weightless, to stare at a tiny fairy who flits just in front of your face.");
		output("\"`#My, aren't you a silly one?");
		output("Why did you let yourself get so beat up?");
		output("Don't you know that if a creature is too tough for you, you can run away?");
		output("`n`n`^\"`#Well, in any event, you should be more careful from now on.");
		output("I've brought you back from the brink of death, and I'll watch over you for as long as you reside on this island.");
		output("But be careful, one day you will want to set forth from this place, and when you do, I will no longer hold any power.");
		output("Other lands are the domain of %s, Overlord of Death`#, and his favor is not so easily found as mine.", getsetting("deathoverlord", '`$Ramius'));
		output("Please do be careful.");
		output("I recommend that you visit the healer and get fixed up before you try fighting anything in the forest again.`^\"");
		output("`n`nWith that, the tiny fairy winks out of existence, and you feel gravity once again begin to pull at your feet.");
		output("As the bright light fades from around you, you find yourself standing in the village square on wobbly knees once again.");
		$session['user']['hitpoints'] = 1;
		$session['user']['alive'] = true;
		villagenav();
		page_footer();
		break;
	}
}
?>
