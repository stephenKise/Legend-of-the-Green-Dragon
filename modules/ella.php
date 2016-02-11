<?php
// translator ready
// mail ready
// addnews ready

/* Ella's Dance Studio */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */

/* 1.01 - Fixed an issue with Ella taking too many hitpoints
		  when tempstats have been applied (XChrisX)

/* 6th Nov 2004 */

require_once("lib/villagenav.php");
require_once("lib/http.php");

function ella_getmoduleinfo(){
	$info = array(
		"name"=>"Ella's Dance Studio",
		"version"=>"1.01",
		"author"=>"Shannon Brown",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"Ella's Dance Studio - Settings,title",
			"danceday"=>"How often can the player dance? 1=daily ~ 2=every second day,range,1,10,1|3",
			"ellaloc"=>"Where does Ella appear,location|".getsetting("villagename", LOCATION_FIELDS),
			"turncost"=>"How many turns does training at Ella's take?,range,1,7,1|3",
			"hitpointcost"=>"How many hitpoints does training at Ella's take?,range,1,7,1|3",
			"charmgain"=>"How much charm do you get for training?,range,1,10,1|5",
		),
		"prefs"=>array(
			"Ella's Dance Studio User Preferences,title",
			"candance"=>"Can the player dance today?,int|1",
			"dayswait"=>"Days the player must wait till next lesson,int|0",
		)
	);
	return $info;
}

function ella_install(){
	module_addhook("newday");
	module_addhook("village");
	module_addhook("changesetting");
	return true;
}

function ella_uninstall(){
	return true;
}

function ella_dohook($hookname,$args){
	global $session;

	switch($hookname){
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("ellaloc")) {
				set_module_setting("ellaloc", $args['new']);
			}
		}
		break;
	case "newday":
		$dayswait=get_module_pref("dayswait");
		// let's just correct if something goes wrong somehow.
		if ($dayswait<0) $dayswait = 0;
		if ($dayswait>0) $dayswait--;
		set_module_pref("dayswait",$dayswait);
		if ($dayswait==0) set_module_pref("candance",1);
		break;
	case "village":
		// Moved the check for candance down into the run.  Buildings
		// shouldn't just vanish!
		if ($session['user']['location'] == get_module_setting("ellaloc")){
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("Ella's Dance Studio","runmodule.php?module=ella");
		}
		break;
	}
	return $args;
}

function ella_run() {
	global $session;
	$op = httpget("op");

	require_once("lib/partner.php");
	$partner = get_partner();

	$candance = get_module_pref("candance");
	$dkhp=0;
	while(list($key,$val)=each($session['user']['dragonpoints'])){
		if ($val=="hp") $dkhp++;
	}
	$maxhitpoints = 10 * $session['user']['level'] + $dkhp * 5;

	suspend_temp_stats();
	if ($session['user']['maxhitpoints'] < ($maxhitpoints + get_module_setting("hitpointcost")))
		$notenough = true;
	else 
		$notenough = false;
	restore_temp_stats();

	page_header("Dance Studio");
	output("`&`c`bLady Ella's Dance Studio`b`c");

	if (!$candance) {
		output("`7Your muscles are still too stiff and sore from your last lesson.`n");
		output("`7Perhaps in a day or so, you'll feel up to another lesson.");
	} elseif ($notenough) {
		output("`7You don't feel like you could take a lesson with Ella today.`n");
		output("`7Perhaps if your constitution has risen a bit.");
	} elseif ($op == "") {
		output("`7A statuesque teacher in intricately-beaded garb stands at one end of a small studio, intently watching the movements of the  dancers in the room.");
		output("Partnered and solo dancers sway and spin in fast rhythms, matching their movements to the piano that sings from one side of the room, where a delicate Felyne moves her paws to create the sound.`n`n");
		output("Noting your interest, Lady Ella smiles at you as she walks towards you.`n`n");
		output("\"`&Lovely movement, everyone, keep going, I'll have you all as polished performers yet!`7\"`n`n");
		output("`7She approaches you and beckons you into her office.");
		$cost = get_module_setting("turncost");
		if ($session['user']['turns'] >= $cost) {
			output("Once there, she explains what she can offer.");
			output("\"`&You'll like our lessons very much.");
			output("We pride ourselves in making sure it's fun AND helpful.");
			output("Who knows, you might land the %s of your dreams!`n`n",
					translate_inline($session['user']['sex']?"man":"girl"));
			output("Now, it takes a lot of time and effort to learn to dance, so you can't expect to have nearly as much time for galivanting around the forest.");
			if ($session['user']['sex']) {
				output("So, are you sure you want to make that commitment and learn to make the men chase you?`7\"");
			} else {
			output("So, are you sure you want to make that commitment and learn to sweep the ladies off their feet?`7\"");
			}
			output("`qYou realize she's asking for a sacrifice of time you would normally spend hunting in the forest.");
			output("`qYou ponder for a moment on whether you want to make that large a commitment.");
			addnav(array("Take Lesson (%s %s)", $cost,
						translate_inline($cost == 1? "turn" : "turns")),
					"runmodule.php?module=ella&op=dance");
		}else{
			output("Once there, she regards you gravely.");
			output("\"`&Darling, there's nothing I love more than teaching someone the love of the dance.");
			output("I'd be more than happy to let you watch, but by the looks of how tired out you are, you'd fall over from the exertion of actually dancing with us today.`7\"`n`n");
			output("You nod your understanding, that you perhaps need more free time to get the most out of the strenuous training she has to offer.");
		}
	}else {
		output("`7You agree to take a dancing lesson today, and eagerly move forward to join the other dancers.`n`n");
		output("The music begins slowly, giving you the chance to start gradually, but it quickly becomes difficult and tiring.");
		output("You're not sure how impressed %s`7 would be with your efforts, but you're determined not to give up too easily.`n`n",$partner);
		output("After the lesson concludes, you feel weary, and wonder whether you have the strength to keep this up for many weeks to come.");
		output("You sure hope %s`7 appreciates your efforts today.`n`n",$partner);
		output("You feel `5charming!`n");
		$turncost = (int)get_module_setting("turncost");
		$hitpointcost = (int)get_module_setting("hitpointcost");
		if ($turncost > 0 && $hitpointcost > 0) {
			output("Your efforts had their price.");
			output("You feel a little tired and not that enduring anymore.");
		} else if ($turncost > 0) {
			output("Your efforts had their price.");
			output("You feel a little tired.");
		} else if ($hitpointcost > 0) {
			output("Your efforts had their price.");
			output("You feel not that enduring anymore.");
		}
		debuglog("lost $turncost turns and $hitpointcost maxhitpoints for taking a dancing lesson.");
		$session['user']['turns']-=$turncost;
		$session['user']['maxhitpoints'] -= $hitpointcost;
		if ($session['user']['maxhitpoints'] < $session['user']['level']*10)
			$session['user']['maxhitpoints'] = $session['user']['level']*10;
		$session['user']['charm']+=get_module_setting("charmgain");
		set_module_pref("candance",0);
		$danceday=get_module_setting("danceday");
		set_module_pref("dayswait",$danceday);
	}
	villagenav();
	page_footer();
}
?>
