<?php
// addnews ready
// mail ready
// translator ready

/* Goblin Hotel */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 21st Sept 2004 */


require_once("lib/villagenav.php");
require_once("lib/http.php");

function goblinhotel_getmoduleinfo(){
    $info = array(
        "name"=>"Goblin Hotel",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Village",
        "download"=>"core_module",
		"requires"=>array(
			"drinks"=>"1.1|By John J. Collins, from the core download",
		),
        "settings"=>array(
            "Goblin Hotel - Settings,title",
			"price"=>"Cost per Bloodbath drink,int|5",
			"cprice"=>"Cost per Corpse Cocktail,int|10",
			"goblinhotelloc"=>"Where does the goblin hotel appear,location|".getsetting("villagename", LOCATION_FIELDS)
        ),
    );
    return $info;
}

function goblinhotel_install(){
	module_addhook("changesetting");
	module_addhook("village");
    return true;
}

function goblinhotel_uninstall(){
    return true;
}

function goblinhotel_dohook($hookname,$args){
    global $session;
    switch($hookname){
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("goblinhotelloc")) {
				set_module_setting("goblinhotelloc", $args['new']);
			}
		}
	break;
	case "village":
		if ($session['user']['location'] == get_module_setting("goblinhotelloc")) {
            tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
            tlschema();
			addnav("G?The Goblin Hotel","runmodule.php?module=goblinhotel");
		}
		break;
	}
    return $args;
}

function goblinhotel_run() {
    global $session;

	page_header("Goblin Hotel");
	$city = get_module_setting("villagename","ghosttown");
	$op=httpget("op");
	$price=get_module_setting("price");
	$cprice=get_module_setting("cprice");
	$drunkeness=0;
	$maxdrunk=0;
	$drunkeness=get_module_pref("drunkeness","drinks");
	$maxdrunk=get_module_setting("maxdrunk","drinks");
	$gold=$session['user']['gold'];
	$suits=(array("`2frog`7","stupid-looking `qdog`7, and his nametag says, `Q\"Toto\"`7"));
	$suits = translate_inline($suits);
	$suit = $suits[e_rand(0, count($suits)-1)];
	if($op==""){
		output("`&`c`bThe Goblin Hotel`b`c");
		output("`7You enter a lively drinking hall, populated by dancing costumes of every imaginable kind.`n`n");
		output("The bartender is dressed as a %s.`n`n",$suit);
		output("`7He approaches and asks, `7\"`&What can I get you today?`7\"`n`n");
		if($drunkeness>$maxdrunk){
			output("The bartender stops abruptly, and takes a step back as he notices the smell of alcohol on your breath.`n`n");
			output("He smiles.`n`n");
			output("\"`&Don't you think you've had enough to drink for today?`7\"`n`n");
		}else{
			addnav(array("B?Bloodbath (%s gold`0)",$price),"runmodule.php?module=goblinhotel&op=blood");
			addnav(array("C?Corpse Cocktail (%s gold`0)",$cprice),"runmodule.php?module=goblinhotel&op=corpse");
		}
	}elseif($op=="blood" && $gold>=$price){
		output("The bartender places a glass of red liquid in front of you.");
		output("There are small white chunks floating in the top, and you're suddenly apprehensive about it.`n`n");
		output("You take a careful sip, and then realize it's raspberry flavored, with marshmallows!`n`n");
		output("You feel `@healthy!`n`n");
		$drunkeness+=20;
		$session['user']['hitpoints']*=1.07;
		$session['user']['gold']-=$price;
		apply_buff('buzz',array("name"=>"Bloodbath Buzz","rounds"=>10,"atkmod"=>1.02, "schema"=>"module-goblinhotel"));
		set_module_pref("drunkeness",$drunkeness,"drinks");
		addnav("B?Back to the Bar","runmodule.php?module=goblinhotel&op=");
	}elseif($op=="corpse"&& $gold>=$cprice){
		output("The bartender places a glass of white lumpy liquid in front of you.");
		output("There are small pink flecks in it, and you're suddenly apprehensive about it.`n`n");
		output("You take a careful sip, and then realize it's cherry and coconut flavored, and is delicious!`n`n");
		output("You feel `@healthy!`n`n");
		$drunkeness+=30;
		$session['user']['hitpoints']*=1.15;
		$session['user']['gold']-=$cprice;
		apply_buff('buzz',array("name"=>"Corpse Cocktail Buzz","rounds"=>10,"atkmod"=>1.04, "schema"=>"module-goblinhotel"));
		set_module_pref("drunkeness",$drunkeness,"drinks");
		addnav("B?Back to the Bar","runmodule.php?module=goblinhotel&op=");
	}else{
		output("The bartender just frowns.`n`n");
		output("Maybe you'll return when you have enough gold?");
	}
	villagenav();
	page_footer();
}
?>
