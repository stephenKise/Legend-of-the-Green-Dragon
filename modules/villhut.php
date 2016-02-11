<?php
// addnews ready
// mail ready
// translator ready

/* Villager's Hut */
/* ver 1.1 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 11th Nov 2004 */

require_once("lib/villagenav.php");
require_once("lib/http.php");

function villhut_getmoduleinfo(){
    $info = array(
        "name"=>"Villager's Hut",
        "version"=>"1.1",
        "author"=>"Shannon Brown",
        "category"=>"Village",
        "download"=>"core_module",
        "settings"=>array(
            "Villager's Hut - Settings,title",
			"villhutloc"=>"Where does the hut appear,location|".getsetting("villagename", LOCATION_FIELDS)
        ),
        "prefs"=>array(
            "Villager's Hut - User Preferences,title",
			"eattoday"=>"How much has the user eaten today?,int|0",
        )
    );
    return $info;
}

function villhut_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
    return true;
}

function villhut_uninstall(){
    return true;
}

function villhut_dohook($hookname,$args){
    global $session;
    switch($hookname){
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("villhutloc")) {
				set_module_setting("villhutloc", $args['new']);
			}
		}
		break;
   	case "newday":
		set_module_pref("eattoday",0);
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("villhutloc")) {
            tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
            tlschema();
			addnav("H?Villager's Hut","runmodule.php?module=villhut");
		}
		break;
	}
    return $args;
}

function villhut_run() {
    global $session;
	$op=httpget('op');
	$eattoday=get_module_pref("eattoday");
	$turn=e_rand(1,8);
	page_header("Villager's Hut");
	output("`&`c`bThe Villager's Hut`b`c");
	if($eattoday>=3){
		output("`7`nYou walk towards the hut again, but your stomach can't bear the thought of more of those sweets today!`n`n");
		$turn=2;
	}elseif ($op==""){
		$turn=2;
		output("`7You walk towards the well-lit front porch of the house, wondering at the lovely colored lights in the windows.`n`n");
		output("As you get to the door, there's a bowl filled with bags of sweets, and a note.`n`n");
		output("`7\"`%Hello Children!`7\" it reads.`n`n");
		output("`7\"`%Please help yourselves to a bag!`7\"`n`n");
		output("You stand there for a moment, wondering what to do.");
		output("The residents obviously aren't home.`n`n");
		output("How many bags should you take?`n`n");
		addnav("Eat 1","runmodule.php?module=villhut&op=1");
		addnav("Eat 2","runmodule.php?module=villhut&op=2");
		addnav("Eat 3","runmodule.php?module=villhut&op=3");
		addnav("Eat 4","runmodule.php?module=villhut&op=4");
	}elseif ($op=="3"){
		output("`7A shout emerges from the shadows nearby.`n`n");
		output("\"`%You greedy pig!`7\" it screams.`n`n");
		output("The next thing you realize, you're sopping wet, and `QSaucy`\$Wench `7is standing there with an empty bucket in her hands.`n`n");
		output("You feel miserable, and `4lose `7some hitpoints.`n`n");
		$session['user']['hitpoints']=($session['user']['hitpoints']*0.85);
		if ($session['user']['hitpoints'] < 1)
			$session['user']['hitpoints'] = 1;
		$eattoday+=3;
		$turn=2;
		set_module_pref("eattoday",$eattoday);
	}elseif ($op=="4"){
		output("`7A shout emerges from the shadows nearby.`n`n");
		output("\"`%You greedy pig!`7\" it screams.`n`n");
		output("You flee in terror, jumping over some thorn bushes as you run, and your arms and legs are ripped painfully.`n`n");
		output("You finally make it to the village square, only to trip onto your face in a most embarrassing manner.`n`n");
		output("You flush in shame, and `4lose `7some hitpoints and some charm.`n`n");
		$session['user']['hitpoints']=($session['user']['hitpoints']*0.65);
		if ($session['user']['hitpoints'] < 1)
			$session['user']['hitpoints'] = 1;
		if ($session['user']['charm'] > 0)
			$session['user']['charm']--;
		$eattoday+=4;
		$turn=2;
		set_module_pref("eattoday",$eattoday);
	}elseif ($op=="1"){
		output("`7You help yourself to a bag and begin to eat as you walk away.`n`n");
		output("You feel `@healthy!`n`n");
		$session['user']['hitpoints'] =
			max($session['user']['hitpoints']+3,
					$session['user']['hitpoints']*1.02);
		$eattoday+=1;
		set_module_pref("eattoday",$eattoday);
		if ($eattoday>0 && $eattoday<3)
			addnav("M?Return for More","runmodule.php?module=villhut");
	}elseif ($op=="2"){
		output("`7You snatch two bags and greedily begin to eat as you walk away.`n`n");
		output("You feel `@healthy!`n`n");
		$session['user']['hitpoints'] =
			max($session['user']['hitpoints']+5,
					$session['user']['hitpoints']*1.03);
		$eattoday+=2;
		set_module_pref("eattoday",$eattoday);
		if ($eattoday>0 && $eattoday<3)
			addnav("M?Return for More","runmodule.php?module=villhut");
	}
	if ($turn==1){
		output("`7Swallowing the last sweet, you realize there was something unusual mixed in with the mountain of sugar.`n`n");
		output("You feel `@energized!`n`n");
		$session['user']['turns']+=2;
	}
	villagenav();
	page_footer();
}

?>
