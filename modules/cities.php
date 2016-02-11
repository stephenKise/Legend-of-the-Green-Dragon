<?php
// translator ready
// addnews ready
// mail ready

function cities_getmoduleinfo(){
	$info = array(
		"name"=>"Multiple Cities",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"Village",
		"download"=>"core_module",
		"allowanonymous"=>true,
		"override_forced_nav"=>true,
		"settings"=>array(
			"Cities Settings,title",
			"allowance"=>"Daily Travel Allowance,int|3",
			"coward"=>"Penalise Cowardice for running away?,bool|1",
			"travelspecialchance"=>"Chance for a special during travel,int|7",
			"safechance"=>"Chance to be waylaid on a safe trip,range,1,100,1|50",
			"dangerchance"=>"Chance to be waylaid on a dangerous trip,range,1,100,1|66",
		),
		"prefs"=>array(
			"Cities User Preferences,title",
			"traveltoday"=>"How many times did they travel today?,int|0",
			"homecity"=>"User's current home city.|",
		),
		"prefs-mounts"=>array(
			"Cities Mount Preferences,title",
			"extratravel"=>"How many free travels does this mount give?,int|0",
		),
		"prefs-drinks"=>array(
			"Cities Drink Preferences,title",
			"servedcapital"=>"Is this drink served in the capital?,bool|1",
		),
	);
	return $info;
}

function cities_install(){
	module_addhook("villagetext");
	module_addhook("village");
	module_addhook("travel");
	module_addhook("count-travels");
	module_addhook("cities-usetravel");
	module_addhook("validatesettings");
	module_addhook("newday");
	module_addhook("charstats");
	module_addhook("mountfeatures");
	module_addhook("faq-toc");
	module_addhook("drinks-check");
	module_addhook("stablelocs");
	module_addhook("camplocs");
	module_addhook("master-autochallenge");
	return true;
}

function cities_uninstall(){
	// This is semi-unsafe -- If a player is in the process of a page
	// load it could get the location, uninstall the cities and then
	// save their location from their session back into the database
	// I think I have a patch however :)
	$city = getsetting("villagename", LOCATION_FIELDS);
	$inn = getsetting("innname", LOCATION_INN);
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='".addslashes($city)."' WHERE location!='".addslashes($inn)."'";
	db_query($sql);
	$session['user']['location']=$city;
	return true;
}

function cities_dohook($hookname,$args){
	global $session;
	$city = getsetting("villagename", LOCATION_FIELDS);
	$home = $session['user']['location']==get_module_pref("homecity");
	$capital = $session['user']['location']==$city;
	switch($hookname){
    case "validatesettings":
		if ($args['dangerchance'] < $args['safechance']) {
			$args['validation_error'] = "Danger chance must be equal to or greater than the safe chance.";
		}
		break;
	case "faq-toc":
		$t = translate_inline("`@Frequently Asked Questions on Multiple Villages`0");
		output_notl("&#149;<a href='runmodule.php?module=cities&op=faq'>$t</a><br/>", true);
		break;
	case "drinks-check":
		if ($session['user']['location'] == $city) {
			$val = get_module_objpref("drinks", $args['drinkid'], "servedcapital");
			$args['allowdrink'] = $val;
		}
		break;
	case "count-travels":
		global $playermount;
		$args['available'] += get_module_setting("allowance");
		if ($playermount && isset($playermount['mountid'])) {
			$id = $playermount['mountid'];
			$extra = get_module_objpref("mounts", $id, "extratravel");
			$args['available'] += $extra;
		}
		$args['used'] += get_module_pref("traveltoday");
		break;
	case "cities-usetravel":
		global $session;
		$info = modulehook("count-travels",array());
		if ($info['used'] < $info['available']){
			set_module_pref("traveltoday",get_module_pref("traveltoday")+1);
			if (isset($args['traveltext'])) output($args['traveltext']);
			$args['success']=true;
			$args['type']='travel';
		}elseif ($session['user']['turns'] >0){
			$session['user']['turns']--;
			if (isset($args['foresttext'])) output($args['foresttext']);
			$args['success']=true;
			$args['type']='forest';
		}else{
			if (isset($args['nonetext'])) output($args['nonetext']);
			$args['success']=false;
			$args['type']='none';
		}
		$args['nocollapse'] = 1;
		return $args;
		break;
	case "master-autochallenge":
		global $session;
		if (get_module_pref("homecity")!=$session['user']['location']){
			$info = modulehook("cities-usetravel",
				array(
					"foresttext"=>array("`n`n`^Startled to find your master in %s`^, your heart skips a beat, costing a forest fight from shock.", $session['user']['location']),
					"traveltext"=>array("`n`n`%Surprised at finding your master in %s`%, you feel a little less inclined to be gallivanting around the countryside today.", $session['user']['location']),
					)
				);
			if ($info['success']){
				if ($info['type']=="travel") debuglog("Lost a travel because of being truant from master.");
				elseif ($info['type']=="forest") debuglog("Lost a forest fight because of being truant from master.");
				else debuglog("Lost something, not sure just what, because of being truant from master.");
			}
		}
		break;
	case "mountfeatures":
		$extra = get_module_objpref("mounts", $args['id'], "extratravel");
		$args['features']['Travel']=$extra;
		break;
	case "newday":
		if ($args['resurrection'] != 'true') {
			set_module_pref("traveltoday",0);
		}
		set_module_pref("paidcost", 0);
		break;
	case "villagetext":
		if ($session['user']['location'] == $city){
			// The city needs a name still, but at least now it's a bit
			// more descriptive
			// Let's do this a different way so that things which this
			// module (or any other) resets don't get resurrected.
			$args['text'] = array("`Q`b`c%s, the Capital City`b`cAll around you, the people of the city of %s move about their business.  No one seems to pay much attention to you as they all seem absorbed in their own lives and problems.  Along various streets you see many different types of shops, each with a sign out front proclaiming the business done therein.  Off to one side, you see a very curious looking rock which attracts your eye with its strange shape and color.  People are constantly entering and leaving via the city gates to a variety of destinations.`n",$city,$city);
			$args['schemas']['text'] = "module-cities";
			$args['clock']="`n`QThe clock on the inn reads `^%s.`0`n";
			$args['schemas']['clock'] = "module-cities";
			if (is_module_active("calendar")) {
				$args['calendar']="`n`QYou hear a townsperson say that today is `^%1\$s`Q, `^%3\$s %2\$s`Q, `^%4\$s`Q.`n";
				$args['schemas']['calendar'] = "module-cities";
			}
			$args['title']=array("%s, the Capital City",$city);
			$args['schemas']['title'] = "module-cities";
			$args['fightnav']="Combat Avenue";
			$args['schemas']['fightnav'] = "module-cities";
			$args['marketnav']="Store Street";
			$args['schemas']['marketnav'] = "module-cities";
			$args['tavernnav']="Ale Alley";
			$args['schemas']['tavernnav'] = "module-cities";
			$args['newestplayer']="";
			$args['schemas']['newestplayer'] = "module-cities";
		}
		if ($home){
			//in home city.
			blocknav("inn.php");
			blocknav("stables.php");
			blocknav("rock.php");
			blocknav("hof.php");
			blocknav("mercenarycamp.php");
		}elseif ($capital){
			//in capital city.
			blocknav("forest.php");
			blocknav("train.php");
			blocknav("weapons.php");
			blocknav("armor.php");
		}else{
			//in another city.
			blocknav("train.php");
			blocknav("inn.php");
			blocknav("stables.php");
			blocknav("rock.php");
			blocknav("clans.php");
			blocknav("hof.php");
			blocknav("mercenarycamp.php");
		}
		break;
	case "charstats":
		if ($session['user']['alive']){
			addcharstat("Personal Info");
			addcharstat("Home City", get_module_pref("homecity"));
			$args = modulehook("count-travels", array('available'=>0,'used'=>0));
			$free = max(0, $args['available'] - $args['used']);
			addcharstat("Extra Info");
			addcharstat("Free Travel", $free);
		}
		break;
	case "village":
		if ($capital) {
			tlschema($args['schemas']['fightnav']);
			addnav($args['fightnav']);
			tlschema();
			addnav("H?Healer's Hut","healer.php?return=village.php");
		}
		tlschema($args['schemas']['gatenav']);
		addnav($args['gatenav']);
		tlschema();
		addnav("Travel","runmodule.php?module=cities&op=travel");
		if (get_module_pref("paidcost") > 0) set_module_pref("paidcost", 0);
		break;
	case "travel":
		addnav("Safer Travel");
		$hotkey = "C";
		if ($session['user']['location']!=$city){
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=".urlencode($city));
		}
		addnav("More Dangerous Travel");
		if ($session['user']['superuser'] & SU_EDIT_USERS){
			addnav("Superuser");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=".urlencode($city)."&su=1");
		}
		break;
	case "stablelocs":
		$args[$city] = sprintf_translate("The City of %s", $city);
		break;
	case "camplocs":
		$args[$city] = sprintf_translate("The City of %s", $city);
		break;
	}
	return $args;
}

function cities_dangerscale($danger) {
	global $session;
	$dlevel = ($danger ?
			get_module_setting("dangerchance"):
			get_module_setting("safechance"));
	if ($session['user']['dragonkills'] <= 1) $dlevel = round(.50*$dlevel, 0);
	elseif ($session['user']['dragonkills'] <= 30) {
		$scalef = 50/29;
		$scale = (($session['user']['dragonkills']-1)*$scalef + 50)/100;
		$dlevel = round($scale*$dlevel, 0);
	} // otherwise, dlevel is unscaled.
	return $dlevel;
}

function cities_run(){
	global $session;
	$op = httpget("op");
	$city = urldecode(httpget("city"));
	$continue = httpget("continue");
	$danger = httpget("d");
	$su = httpget("su");
	if ($op != "faq") {
		require_once("lib/forcednavigation.php");
		do_forced_nav(false, false);
	}

	// I really don't like this being out here, but it has to be since
	// events can define their own op=.... and we might need to handle them
	// otherwise things break.
	require_once("lib/events.php");
	if ($session['user']['specialinc'] != "" || httpget("eventhandler")){
		$in_event = handle_event("travel",
			"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1&",
			"Travel");
		if ($in_event) {
			addnav("Continue","runmodule.php?module=cities&op=travel&city=".urlencode($city)."&d=$danger&continue=1");
			module_display_events("travel",
				"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
			page_footer();
		}
	}

	if ($op=="travel"){
		$args = modulehook("count-travels", array('available'=>0,'used'=>0));
		$free = max(0, $args['available'] - $args['used']);
		if ($city==""){
			require_once("lib/villagenav.php");
			page_header("Travel");
			modulehook("collapse{", array("name"=>"traveldesc"));
			output("`%Travelling the world can be a dangerous occupation.");
			output("Although other villages might offer things not found in your current one, getting from village to village is no easy task, and might subject you to various dangerous creatures or brigands.");
			output("Be sure you're willing to take on the adventure before you set out, as not everyone arrives at their destination intact.");
			output("Also, pay attention to the signs, some roads are safer than others.`n");
			modulehook("}collapse");
			addnav("Forget about it");
			villagenav();
			modulehook("pre-travel");
			if (!($session['user']['superuser']&SU_EDIT_USERS) && ($session['user']['turns']<=0) && $free == 0) {
				// this line rewritten so as not to clash with the hitch module.
				output("`nYou don't feel as if you could face the prospect of walking to another city today, it's far too exhausting.`n");
			}else{
				addnav("Travel");
				modulehook("travel");
			}
			module_display_events("travel",
				"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
			page_footer();
		}else{
			if ($continue!="1" && $su!="1" && !get_module_pref("paidcost")){
				set_module_pref("paidcost", 1);
				if ($free > 0) {
					// Only increment travel used if they are still within
					// their allowance.
					set_module_pref("traveltoday",get_module_pref("traveltoday")+1);
					//do nothing, they're within their travel allowance.
				}elseif ($session['user']['turns']>0){
					$session['user']['turns']--;
				}else{
					output("Hey, looks like you managed to travel with out having any forest fights.  How'd you swing that?");
					debuglog("Travelled with out having any forest fights, how'd they swing that?");
				}
			}
			// Let's give the lower DK people a slightly better chance.
			$dlevel = cities_dangerscale($danger);
			if (e_rand(0,100)< $dlevel && $su!='1'){
				//they've been waylaid.

				if (module_events("travel", get_module_setting("travelspecialchance"),"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1&") != 0) {
					page_header("Something Special!");
					if (checknavs()) {
						page_footer();
					} else {
						// Reset the special for good.
						$session['user']['specialinc'] = "";
						$session['user']['specialmisc'] = "";
						$skipvillagedesc=true;
						$op = "";
						httpset("op", "");
						addnav("Continue","runmodule.php?module=cities&op=travel&city=".urlencode($city)."&d=$danger&continue=1");
						module_display_events("travel",
							"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
						page_footer();
					}
				}

				$args = array("soberval"=>0.9,
						"sobermsg"=>"`&Facing your bloodthirsty opponent, the adrenaline rush helps to sober you up slightly.", "schema"=>"module-cities");
				modulehook("soberup", $args);
				require_once("lib/forestoutcomes.php");
				$sql = "SELECT * FROM " . db_prefix("creatures") . " WHERE creaturelevel = '{$session['user']['level']}' AND forest = 1 ORDER BY rand(".e_rand().") LIMIT 1";
				$result = db_query($sql);
				restore_buff_fields();
				if (db_num_rows($result) == 0) {
					// There is nothing in the database to challenge you,
					// let's give you a doppleganger.
					$badguy = array();
					$badguy['creaturename']=
						"An evil doppleganger of ".$session['user']['name'];
					$badguy['creatureweapon']=$session['user']['weapon'];
					$badguy['creaturelevel']=$session['user']['level'];
					$badguy['creaturegold']=0;
					$badguy['creatureexp'] =
						round($session['user']['experience']/10, 0);
					$badguy['creaturehealth']=$session['user']['maxhitpoints'];
					$badguy['creatureattack']=$session['user']['attack'];
					$badguy['creaturedefense']=$session['user']['defense'];
				} else {
					$badguy = db_fetch_assoc($result);
					$badguy = buffbadguy($badguy);
				}
				calculate_buff_fields();
				$badguy['playerstarthp']=$session['user']['hitpoints'];
				$badguy['diddamage']=0;
				$badguy['type'] = 'travel';
				$session['user']['badguy']=createstring($badguy);
				$battle = true;
			}else{
				set_module_pref("paidcost", 0);
				//they arrive with no further scathing.
				$session['user']['location']=$city;
				redirect("village.php");
			}
		}
	}elseif ($op=="fight" || $op=="run"){
		if ($op == "run" && e_rand(1, 5) < 3) {
			// They managed to get away.
			page_header("Escape");
			output("You set off running through the forest at a breakneck pace heading back the way you came.`n`n");
			$coward = get_module_setting("coward");
			if ($coward) {
				modulehook("cities-usetravel",
				array(
					"foresttext"=>array("In your terror, you lose your way and become lost, losing time for a forest fight.`n`n", $session['user']['location']),
					"traveltext"=>array("In your terror, you lose your way and become lost, losing precious travel time.`n`n", $session['user']['location']),
					)
				);
			}
			output("After running for what seems like hours, you finally arrive back at %s.", $session['user']['location']);

			addnav(array("Enter %s",$session['user']['location']), "village.php");
			page_footer();
		}
		$battle=true;
	} elseif ($op == "faq") {
		cities_faq();
	} elseif ($op == "") {
		page_header("Travel");
		output("A divine light ends the fight and you return to the road.");
		addnav("Continue your journey","runmodule.php?module=cities&op=travel&city=".urlencode($city)."&continue=1&d=$danger");
		module_display_events("travel",
			"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
		page_footer();
	}

	if ($battle){
		page_header("You've been waylaid!");
		require_once("battle.php");
		if ($victory){
			require_once("lib/forestoutcomes.php");
			forestvictory($newenemies,"This fight would have yielded an extra turn except it was during travel.");
			addnav("Continue your journey","runmodule.php?module=cities&op=travel&city=".urlencode($city)."&continue=1&d=$danger");
			module_display_events("travel",
				"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
		}elseif ($defeat){
			require_once("lib/forestoutcomes.php");
			forestdefeat($newenemies,array("travelling to %s",$city));
		}else{
			require_once("lib/fightnav.php");
			fightnav(true,true,"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger");
		}
		page_footer();
	}

}

function cities_faq() {
	global $session;
	tlschema("faq");
	popup_header("Multi-Village Questions");
	$c = translate_inline("Return to Contents");
	rawoutput("<a href='petition.php?op=faq'>$c</a><hr>");
	output("`n`n`c`bQuestions about the multiple village system`b`c`n");
	output("`^1. Why, oh why did you activate such a (choose one [wondrous, horrible]) feature?`n");
	output("`@For kicks, of course. We like to mess with your head.`n");
	output("But seriously, have you looked at the user list?  On lotgd.net, we've got over 6,000 people cramming themselves into the Village Square and trying to get their voices heard! Too much! Too much!`n");
	output("In the interests of sanity, we've made more chat boards. And in the interests of game continuity, we've put them into separate villages with many cool new features.`n`n");
	output("If you are a smaller server, this might not be right for you, but we think it works okay there too.`n`n");
	output("`^2. How do I go to other villages?`n");
	output("`@Walk, skate, take the bus...`n");
	output("Or press the Travel link (in the City Gates or Village Gates category) in the navigation bar.`n`n");
	output("`^3. How does travelling work?`n");
	output("`@Pretty well, actually. Thanks for asking.`n");
	output("You get some number of  free travels per day (%s on this server) in which you can travel to any other village you want.", get_module_setting("allowance"));
	output("Also, it is possible for the admin to give additional free travels with some mounts.");
	output("After that, you use up one forest fight per travel.");
	output("After that...well, we hope you like where you end up.");
	output("Since all major economic transactions come through %s (the capital of the region), the roads to and from there have been fortified to protect against monsters from wandering onto them.", getsetting("villagename", LOCATION_FIELDS));
	output("That was a while back though, and the precautions are no longer perfect.`n");
	output("Travel between the other villages have no such precautions.`n");
	output("In either case, you might want to heal yourself before travelling.");
	output("You have been warned.`n`n");
	output("`^4. Where's (the Inn, the forest, my training master, etc.)?`n");
	output("`@Look around. Do you see it? No? Then it's not here.`n");
	output("The problem's usually:`n");
	output("a) It's actually there, you just missed it the first time around.`n");
	output("b) It's in another village, try travelling.`n");
	output("c) It's not on this server, check out the LoGD Net link on the login page.`n");
	output("d) Are you sure you didn't just see that feature in a dream?`n`n");
	output("`^5. I've used up my free travels and forest fights. How do I travel now?`n");
	output("`@We hope you like where you've ended up, because you're stuck there until the next new day.`n`n");
	output("`^6. Can I pay for more travels?`n");
	output("`@No, but you can just plain pay us.");
	if (file_exists("lodge.php")) {
		output("Check out the Hunter's Lodge.");
	} else {
		output("Speak to an admin about donating money.");
	}
	output("Actually, we are considering it.`n");
	if (is_module_active("newbieisland")) {
		$newbieisland=get_module_setting("villagename", "newbieisland");
		if($session['user']['location'] == $newbieisland){
			$newbieisland = translate_inline($newbieisland);
			output("`^7. I'm on %s.", $newbieisland);
			output("Why can't I see the Travel link or any of the other stuff this section talks about?`n");
			output("`@You need at least 5 levels in search, or a Ring of Finding +2 to see any of this stuff.`n");
			output("If you haven't figured it out by now, this second answer is always the real one.");
			output("You'll only be able to see Travel once you leave %s.", $newbieisland);
			output("Feel free to skip the rest of this section and come back to it later.`n`n");
		}
	}
	rawoutput("<hr><a href='petition.php?op=faq'>$c</a>");
	popup_footer();
}

?>
