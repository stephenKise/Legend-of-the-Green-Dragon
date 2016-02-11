<?php
// addnews ready
// mail ready
// translator ready

/* Ice Castle */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 11th Nov 2004 */


require_once("lib/villagenav.php");
require_once("lib/http.php");

function icecastle_getmoduleinfo(){
    $info = array(
        "name"=>"The Ice Castle",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Village",
		"download"=>"core_module",
        "settings"=>array(
            "Ice Castle - Settings,title",
			"icecastleloc"=>"Where does the castle appear,location|".getsetting("villagename", LOCATION_FIELDS)
        ),
        "prefs"=>array(
            "Ice Castle - User Preferences,title",
			"scaretoday"=>"Has the user been scared already today?,bool|0",
        )
    );
    return $info;
}

function icecastle_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
    return true;
}

function icecastle_uninstall(){
    return true;
}

function icecastle_dohook($hookname,$args){
    global $session;
    switch($hookname){
   	case "newday":
		set_module_pref("scaretoday",0);
		break;
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("icecastleloc")) {
				set_module_setting("icecastleloc", $args['new']);
			}
		}
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("icecastleloc")) {
            tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
            tlschema();
			addnav("The Ice Castle","runmodule.php?module=icecastle");
		}
		break;
	}
    return $args;
}

function icecastle_explore(){
	global $session;

	require_once("lib/partner.php");
	$partner = get_partner();

	$person="";
	if (is_module_active("stafflist")){
		$sql = "SELECT a.name FROM " . db_prefix("accounts") . " AS a, " . db_prefix("module_userprefs") . " AS m WHERE m.modulename = 'stafflist' AND m.setting = 'rank' AND m.value > 0 AND m.userid = a.acctid ORDER by rand(".e_rand().") LIMIT 1";
		$result=db_query($sql);
		$row = db_fetch_assoc($result);
		$person = $row['name'];
	}
	// Talk will be empty if we don't have a stafflist or if noone is set
	// as staff
	if ($person==""){
		// These don't get translated since they are proper names.
		$people = array(getsetting('barkeep', '`tCedrik'), "MightyE");
		$person = $people[e_rand(0, count($people)-1)];
	}

	// in case marquee is not installed
	if (!is_module_active("marquee")) {
		$suit="snowman";
	}else{
		$suits = array();
		for ($i = 1; $i < 8; $i++) {
			$suit = get_module_setting("suit$i","marquee");
			if ($suit) array_push($suits, $suit);
		}
		$suits = translate_inline($suits);
		$suit = $suits[e_rand(0, count($suits)-1)];
	}

	$result=e_rand(1,10);
	switch($result){
	case 1:
		output("You look carefully, seeing only ice crystals and lights.`n`n");
		output("As you turn to leave, something glistens in the corner of your eye.`n`n");
		output("You find a `5gem!");
		$session['user']['gems']++;
		break;
	case 2:
	case 3:
		output("You look around you, at first seeing only ice crystals and lights.`n`n");
		output("But as you gaze about, you are amazed to find `^100 gold!");
		$session['user']['gold']+=100;
		break;
	case 4:
		output("A hollow laugh echoes out from somewhere close by.`n`n");
		output("The sound is oddly familiar, and you decide to investigate.`n`n");
		output("Behind a sculpture of ice, you discover `&%s`7, in a rather awful `&%s `7suit.`n`n",$person,$suit);
		output("You laugh in good humor and make your way back outside.`n`n");
		output("You are `@amused!");
		$session['user']['hitpoints']*=1.05;
		break;
	case 5:
		output("A detailed diorama is spread before you.");
		output("Small polar bears, seals, fish and eskimos adorn a lovingly crafted landscape of snow and water.`n`n");
		output("At the front is a bowl of mints.`n`n");
		output("You help yourself to one for yourself, and a second one for %s`7.",$partner);
		output("You feel `5charming!");
		$session['user']['charm']++;
		break;
	case 6:
		output("Before you can even blink, you are confronted by a bizarrely-dressed dancing queen who runs towards you, yelling, \"`&KISS ME!!!`7\"`n`n");
		output("You high-tail it out of there and away from the disturbing creature.`n`n");
		output("You `4lose `7some charm!");
		$session['user']['charm']--;
		set_module_pref("scaretoday",1);
		break;
	case 7:
		output("Just as you begin to relax, `&%s`7 jumps out from behind an ice wall and laughs at you, and you nearly wet your pants in fright.`n`n",$person);
		output("`7You run out of there in a hurry.`n`n");
		output("You `4lose `7some of your hitpoints!");
		$session['user']['hitpoints']*=0.9;
		if ($session['user']['hitpoints'] < 1)
			$session['user']['hitpoints'] = 1;
		set_module_pref("scaretoday",1);
		break;
	case 8:
		output("You have found a small dish of candy canes!`n`n");
		output("You help yourself to one and munch greedily.`n");
		output("You feel `@vigorous!");
		$session['user']['turns']++;
		// be nice to farmies
		if ($session['user']['dragonkills']<=5) $session['user']['turns']++;
		break;
	case 9:
		output("You hear a roar, and terrified of polar bears, you make a mad rush to get out of there!");
		output("As you begin to run, you lose your footing on the slippery ice floor.");
		output("You fall heavily and manage to knock yourself out.`n");
		output("You `4lose `7some of your hitpoints!`n");
		if ($session['user']['turns']>=1) {
			$session['user']['turns']--;
			 output("You `4lose `7a forest fight!`n");
		}
		$session['user']['hitpoints']*=0.7;
		if ($session['user']['hitpoints'] < 1)
			$session['user']['hitpoints'] = 1;
		set_module_pref("scaretoday",1);
		break;
	case 10:
		output("An icy hand appears from nowhere, then clutches at your throat and tries to drag you towards a hole in the ice.`n`n");
		output("Sobbing in terror, you flee blindly out onto the street.`n`n");
		output("You `4lose `7some of your hitpoints!");
		$session['user']['hitpoints']*=0.9;
		if ($session['user']['hitpoints'] < 1)
			$session['user']['hitpoints'] = 1;
		set_module_pref("scaretoday",1);
		break;
	}
}

function icecastle_run() {
    global $session;
	page_header("The Ice Palace");
	$op = httpget('op');
	$scaretoday=get_module_pref("scaretoday");
	output("`&`c`bThe Ice Palace`7`b`c`n");

    if ($op == "") {
		output("`7Set back from the slurried street, a glittering white cave winks below the lights of the leafless winter trees above.`n`n");
		output("Sculpted ice swans line a cobbled path, leading to the entrance.`n`n");
		output("Pots of candles light them from below, the flames dancing in the gentle air.`n`n");
		if ($scaretoday==0) {
			output("What will you do?");
			addnav("Enter","runmodule.php?module=icecastle&op=enter");
		}elseif ($scaretoday!=0){
			output("There's no way you're going near that place again today.");
			output("Maybe you'll want to visit tomorrow.");
		}
		addnav("Leave","village.php");
	}elseif($op=="enter"){
		output("`7You move forward along the pathway, and enter the foyer.");
		output("The sight before you has you in awe.`n`n");
		output("Multiple doorways are lit in many colors, fairy lights adorning every available space.");
		output("Their glow bounces from surface to surface, the blues and yellows and greens shining everywhere like a many-faceted jewel.");
		output("To your left is a doorway in deep blue.");
		output("To the right is an archway of yellow light.");
		output("The dark green corridor is ahead and to the left.");
		output("Beside it is the red walkway.");
		output("Which part will you explore?");
		addnav("Blue","runmodule.php?module=icecastle&op=blue");
		addnav("Yellow","runmodule.php?module=icecastle&op=yellow");
		addnav("Green","runmodule.php?module=icecastle&op=green");
		addnav("Red","runmodule.php?module=icecastle&op=red");
		addnav("Leave","village.php");
    }elseif($op=="yellow"){
		output("`7You move through the yellow walkway and into another room.`n`n");
		output("In here you can see two more paths.");
		output("The left shows a shadowed, soft amber glow.`n`n");
		output("To your right is a majestic violet shine.`n`n");
		output("Which will you choose?`n`n");
		addnav("Amber","runmodule.php?module=icecastle&op=amber");
		addnav("Violet","runmodule.php?module=icecastle&op=violet");
		addnav("Leave","village.php");
    }elseif($op=="violet"){
		output("`7You walk forwards and make your way into the violet room.`n`n");
		output("Again, there are choices before you.");
		output("Glittering lights twinkle on the ceiling, and silver sparkles touch every angled surface, reflecting each other in splendid beauty.");
		output("The right doorway is in sparkling pink.");
		output("In front of you, the corridor continues into an orange incandescence.`n`n");
		output("Where will you go?");
		addnav("Pink","runmodule.php?module=icecastle&op=pink");
		addnav("Orange","runmodule.php?module=icecastle&op=orange");
		addnav("Leave","village.php");
    }elseif($op=="amber"){
		output("`7In the amber room are still more options.");
		output("A small set of steps lead into a pale green glow.");
		output("To your left is a winding corridor disappearing into ice blue light.");
		addnav("Green","runmodule.php?module=icecastle&op=palegreen");
		addnav("Blue","runmodule.php?module=icecastle&op=iceblue");
		addnav("Leave","village.php");
    }elseif($op=="red"){
		output("`7You cautiously creep forwards into the red.`n`n");
		output("Before you is a white sparkling doorway.`n`n");
		output("To your left is the green room you could see from the foyer.`n`n");
		output("Which way will you look?`n`n");
		addnav("White","runmodule.php?module=icecastle&op=white");
		addnav("Green","runmodule.php?module=icecastle&op=palegreen");
		addnav("Leave","village.php");
    }elseif($op=="iceblue"){
		output("`7You follow the blue corridor, and enter a lovely room of ice sculptures.`n`n");
		icecastle_explore();
		villagenav();
    }elseif($op=="palegreen"){
		output("`7You step up the stairs and into a sparkling chamber.`n`n");
		icecastle_explore();
		villagenav();
    }elseif($op=="blue"){
		output("`7You approach the blue cavern to get a better look.");
		icecastle_explore();
		villagenav();
    }elseif($op=="pink"){
		output("`7You move across to the pink room with wonder.");
		icecastle_explore();
		villagenav();
    }elseif($op=="orange"){
		output("`7You make your way across the room, to the orange end of this cavern.`n`n");
		icecastle_explore();
		villagenav();
    }elseif($op=="green"){
		output("`7You creep forwards into the green ice.");
		icecastle_explore();
		villagenav();
    }elseif($op=="white"){
		output("`7You move reverently towards the sparkling, pristine white light.");
		icecastle_explore();
		villagenav();
    }
	page_footer();
}
?>
