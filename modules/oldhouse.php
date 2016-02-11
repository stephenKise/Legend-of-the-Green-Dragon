<?php
// addnews ready
// mail ready
// translator ready

/* Old House */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 21st Sept 2004 */


require_once("lib/villagenav.php");
require_once("lib/http.php");

function oldhouse_getmoduleinfo(){
    $info = array(
        "name"=>"The Old House",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Village",
        "download"=>"core_module",
        "settings"=>array(
            "Old House - Settings,title",
			"oldhouseloc"=>"Where does the old house appear,location|".getsetting("villagename", LOCATION_FIELDS)
        ),
        "prefs"=>array(
            "Old House - User Preferences,title",
			"scaretoday"=>"Has the user been scared already today?,bool|0",
        )
    );
    return $info;
}

function oldhouse_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
    return true;
}

function oldhouse_uninstall(){
    return true;
}

function oldhouse_dohook($hookname,$args){
    global $session;
    switch($hookname){
   	case "newday":
		set_module_pref("scaretoday",0);
		break;
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("oldhouseloc")) {
				set_module_setting("oldhouseloc", $args['new']);
			}
		}
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("oldhouseloc")) {
            tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
            tlschema();
			addnav("O?The Old House","runmodule.php?module=oldhouse");
		}
		break;
	}
    return $args;
}

function oldhouse_scare(){
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
		$people = array(getsetting("barkeep", "`tCedrik"), "MightyE");
		$person = $people[e_rand(0, count($people)-1)];
	}

	$suits = array("`)ghost`7", "`4ghoul`7","huge blow-up `Qpumpkin`7","`\$pirate`7","`&skeleton`7","`%cheerleader`7","`#clown`7");
	$suits = translate_inline($suits);
	$suit = $suits[e_rand(0, count($suits)-1)];

	$result=e_rand(1,10);
	switch($result){
		case 1:
			output("You look carefully, but see nothing of interest.`n`n");
			output("As you turn to leave, something glistens in the corner of your eye.`n`n");
			output("You find a `5gem!");
			$session['user']['gems']++;
			break;
		case 2:
		case 3:
		case 4:
			output("You look carefully, but see nothing of interest.`n`n");
			output("As you gaze about, you are amazed to find `^100 gold!");
			$session['user']['gold']+=100;
			break;
		case 5:
			output("A hollow laugh echoes out from somewhere close by.`n`n");
			output("The sound is oddly familiar, and you decide to investigate.`n`n");
			output("Behind a nearby door, you discover `&%s`7, in a rather awful `&%s `7suit.`n`n",$person,$suit);
			output("You laugh in good humor and make your way back outside.`n`n");
			output("You are `@amused!");
			$session['user']['hitpoints']*=1.05;
			break;
		case 6:
			output("You have found a pile of heart-shaped chocolates!`n`n");
			output("You help yourself to one for yourself, and a second one for %s`7.",$partner);
			output("You feel `5charming!");
			$session['user']['charm']++;
			break;
		case 7:
			output("Before you can even blink, you are confronted by a bizarrely-dressed dancing queen who runs towards you, yelling, \"`&KISS ME!!!`7\"`n`n");
			output("You high-tail it out of there and away from the disturbing creature.`n`n");
			output("You `4lose `7some charm!");
			$session['user']['charm']--;
			set_module_pref("scaretoday",1);
			break;
		case 8:
			output("Just as you begin to relax, `&%s`7 jumps out from another doorway and laughs at you, and you nearly wet your pants in fright.`n`n",$person);
			output("`7You run out of there in a hurry.`n`n");
			output("You `4lose `7some of your hitpoints!");
			$session['user']['hitpoints']*=0.9;
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints'] = 1;
			set_module_pref("scaretoday",1);
			break;
		case 9:
			output("You look to one side, only to discover a wizened, decaying head that laughs at you as you run away in terror.");
			output("You `4lose `7some of your hitpoints!");
			$session['user']['hitpoints']*=0.95;
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints'] = 1;
			set_module_pref("scaretoday",1);
			break;
		case 10:
			output("An icy hand appears from nowhere, then clutches at your throat and tries to drag you towards it.`n`n");
			output("Sobbing in terror, you flee blindly out onto the street.`n`n");
			output("You `4lose `7some of your hitpoints!");
			$session['user']['hitpoints']*=0.9;
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints'] = 1;
			set_module_pref("scaretoday",1,"oldhouse");
		break;
	}
	return $args;
}

function oldhouse_run() {
    global $session;
	page_header("Old House");
	$city = get_module_setting("villagename","ghosttown");
	$op = httpget('op');
	$scaretoday=get_module_pref("scaretoday");
	output("`&`c`bThe Old House`7`b`c`n");

    if ($op == "") {
		output("`7An old and rickety house stands on one side of the darkened laneway.`n`n");
		output("Sickly weeds have overrun what was once a garden.`n`n");
		output("The weathered front door stands slightly ajar.`n`n");
		if ($scaretoday==0) {
			output("What will you do?");
			addnav("Enter","runmodule.php?module=oldhouse&op=enter");
		}elseif ($scaretoday!=0){
			output("There's no way you're going near that place again today.");
			output("Maybe you'll want to visit tomorrow.");
		}
		addnav("Leave","village.php");
	}elseif($op=="enter"){
		output("`7You push the door and enter the foyer.");
		output("The hairs on the back of your neck stand on end.`n`n");
		output("Ahead of you is a staircase.");
		output("Beside the bottom stair is an archway.");
		output("To your left is a blue door.");
		output("To the right is a corridor.");
		output("Which part will you explore?");
		addnav("Door","runmodule.php?module=oldhouse&op=door");
		addnav("Stairs","runmodule.php?module=oldhouse&op=stairs");
		addnav("Archway","runmodule.php?module=oldhouse&op=arch");
		addnav("Corridor","runmodule.php?module=oldhouse&op=corridor");
		addnav("Leave","village.php");
    }elseif($op=="stairs"){
		output("`7You walk up the stairs and onto a wide landing.`n`n");
		output("`7Will you open the chamber door, or visit the study, or will you leave?`n`n");
		addnav("Enter the Chamber","runmodule.php?module=oldhouse&op=chamber");
		addnav("Visit the Study","runmodule.php?module=oldhouse&op=study");
		addnav("Leave","village.php");
    }elseif($op=="study"){
		output("`7You walk forwards and make your way into the Study.`n`n");
		output("Beneath the cobwebs and dust, you see a old chest.");
		output("To one side is a writing desk.`n`n");
		output("What will you do?");
		addnav("C?Look at the Chest","runmodule.php?module=oldhouse&op=chest");
		addnav("D?Look at the Desk","runmodule.php?module=oldhouse&op=desk");
		addnav("Leave","village.php");
    }elseif($op=="chamber"){
		output("`7In the chamber are an ancient four-poster bed, and a large bureau.");
		output("Will you look under the bed, or examine the bureau?");
		addnav("U?Look Under the Bed","runmodule.php?module=oldhouse&op=bed");
		addnav("E?Examine the Bureau","runmodule.php?module=oldhouse&op=bureau");
		addnav("Leave","village.php");
    }elseif($op=="door"){
		output("`7You push the door open, and enter an old, but lavishly furnished parlor.");
		output("Many adventurers have been here before you, and the room is now in disarray.`n`n");
		oldhouse_scare();
		villagenav();
    }elseif($op=="arch"){
		output("`7You step through the archway and into the dusty kitchen.`n`n");
		oldhouse_scare();
		villagenav();
    }elseif($op=="corridor"){
		output("`7You cautiously creep forwards into the corridor.`n`n");
		oldhouse_scare();
		villagenav();
    }elseif($op=="bureau"){
		output("`7You approach the bureau to get a better look.");
		oldhouse_scare();
		villagenav();
    }elseif($op=="chest"){
		output("`7You move across the room to the chest, and lift the heavy lid.");
		oldhouse_scare();
		villagenav();
    }elseif($op=="desk"){
		output("`7You make your way across the room, to the desk.`n`n");
		oldhouse_scare();
		villagenav();
    }elseif($op=="bed"){
		output("`7You get down on all fours and peer into the darkness under the bed.");
		oldhouse_scare();
		villagenav();
    }
	page_footer();
}
?>
