<?php
// translator ready
// addnews ready
// mail ready

/* gardener */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 3rd December 2004 */

require_once("lib/villagenav.php");
require_once("lib/http.php");

function gardener_getmoduleinfo(){
    $info = array(
        "name"=>"Gardener",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Gardens",
        "download"=>"core_module",
		"settings"=>array(
            "Gardener - Settings,title",
			"customtext"=>"Custom message for server,textarea|",
			"gardens"=>"Does the gazebo appear in the gardens? (setting yes nullifies city selector below),bool|0",
			"gardenerloc"=>"In which city does the gazebo appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
		"prefs"=>array(
            "Gardener - User Preferences,title",
			"seentoday"=>"Has the player visited today?,bool|0",
        )
    );
    return $info;
}

function gardener_install(){
	module_addhook("gardens");
	module_addhook("changesetting");
	module_addhook("village");
	module_addhook("newday");
	module_addhook("footer-runmodule");
    return true;
}

function gardener_uninstall(){
    return true;
}

function gardener_dohook($hookname,$args){
    global $session;
	$gardens=get_module_setting("gardens");
    switch($hookname){
	case "changesetting":
		if (!$gardens && $args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("gardenerloc")) {
				set_module_setting("gardenerloc", $args['new']);
			}
		}
		break;
	case "gardens":
		if ($gardens) {
			addnav("Gazebo","runmodule.php?module=gardener");
			$customtext=get_module_setting("customtext");
			output_notl("`n`%%s`0", $customtext);
		}
		break;
	case "footer-runmodule":
		if (httpget("module") != "newbieisland") break;
		break;
	case "village":
		if (!$gardens &&
				$session['user']['location']==get_module_setting('gardenerloc')) {
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("Gazebo","runmodule.php?module=gardener");
			$customtext=get_module_setting("customtext");
			output_notl("`n`n`c`@%s`0`c", $customtext);
		}
		break;
   	case "newday":
		set_module_pref("seentoday",0);
		break;
	}
    return $args;
}

function gardener_run() {
    global $session;
	$op=httpget('op');
	$seentoday=get_module_pref("seentoday");
	$gardens=get_module_setting("gardens");

	page_header("Gardener");
	output("`&`c`bThe Gardener`b`c");

	// set to == 1 when test complete
	if($seentoday==1){
		output("`7`nYou walk towards the gardener again.");
		output("He smiles and then turns back to his work.`n`n");
		if ($gardens) addnav("G?Return to the Gardens","gardens.php");
		if (!$gardens) villagenav();
	}elseif ($op==""){
		output("`7In one corner of the %s, a path leads away through the trees.",$gardens?"Gardens":"square");
		output("Beyond a white gazebo, a small man is tending a small patch of flowers.");
		output("As you approach, he straightens, and greets you with a smile.`n`n");
		output("`7\"`%Welcome to my gardens!`7\" he exclaims.");
		output("`7\"`%I know they're a little rough now, but I've a reward for you, if you know how to look after them!`7\" he exclaims.`n`n");
		output("Seeing as how the rest of the Gardens are meticulously well kept, you wonder what the small man is so concerned about.");
		output("The offer of a reward, however, is intriguing.`n`n");
		addnav("Try for a Reward","runmodule.php?module=gardener&op=ask");
		if ($gardens) addnav("G?Return to the Gardens","gardens.php");
		if (!$gardens) villagenav();
	}elseif ($op=="ask"){
		$question=e_rand(0,19);

		// The questions have their expected answer associated with them
		// now, so you can change questions at your whim as long as you
		// put the correct answers there.
		$phrases = array(
			"It is ok to get romantic in an adult manner in the Gardens.|0",
			"It is ok to hit players in the Gardens.|0",
			"It is ok to get undressed in the Gardens.|0",
			"The Gardens are a place where you can use websites and email addresses.|0",
			"You can be rude to other players in the Gardens.|0",
			"You can say anything you like in the Gardens.|0",
			"You may roleplay a violent fight in the Gardens.|0",
			"You may roleplay a bedroom love scene in the Gardens.|0",
			"You may roleplay that another player is defenseless.|0",
			"You may upset other players.|0",
			"You may try to annoy others.|0",
			"You may ask every player in the Gardens to marry you.|0",
			"It is ok to discuss game secrets in the Gardens.|0",
			"The Gardens are for adults only, so you can speak about anything there.|0",
			"You may ask players to behave if they are breaking rules.|1",
			"The Gardens are a good place to rest from fighting.|1",
			"It is ok to hug other players in the Gardens.|1",
			"You may roleplay in the Gardens.|1",
			"You may get married in the Gardens.|1",
			"You should report players being cruel to others.|1"
		);

		$myphrase=$phrases[$question];
		list($q,$a) = split("\\|", $myphrase);
		set_module_pref("expectanswer", $a);

		output("`7The old man asks you his question:`n`n");
		output("`7\"`%%s`7\"",$q);
		addnav("True","runmodule.php?module=gardener&op=answer&val=1");
		addnav("False","runmodule.php?module=gardener&op=answer&val=0");
	}else{
		$val = httpget('val');

		// Did we get it wrong?
		if ($val != get_module_pref('expectanswer')) {
			// bad result
			output("`7\"`%Oh dear, I hope you're new around here!`7\" he exclaims.");
			output("\"`%I'm afraid that answer is wrong.`7\"`n`n");
			output("`7You wander away sadly, determined to try harder next time.");
		}else{  // answer is correct
			output("`7\"`%Hey, you know what the Gardens are all about!`7\" he exclaims.");
			output("\"`%You're right, of course, and I have something for you.`7\"`n`n");
			$gift=e_rand(1, 7);
			if($gift==7) {
				output("`^He hands you a `%gem`^!");
				$session['user']['gems']++;
			}else{
				$vargold=e_rand(0,20);
				$addgold=$vargold+(round(max(10,(200-$session['user']['dragonkills']))*0.1)*$session['user']['level']);
				output("`^He hands you a bag containing %s gold!",$addgold);
				$session['user']['gold']+=$addgold;
			}
		}
		set_module_pref("seentoday",1);
		// And the correct return link(s)
		if ($gardens) {
			addnav("G?Return to the Gardens","gardens.php");
		}
		villagenav();
	}
	page_footer();
}

?>
