<?php
// addnews ready
// translator ready
// mail ready

/* Holiday Hotel */
/* generic, for any holiday town */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 10th Nov 2004 */


require_once("lib/villagenav.php");
require_once("lib/http.php");

function holidayhotel_getmoduleinfo(){
	$info = array(
		"name"=>"Holiday Hotel",
		"version"=>"1.0",
		"author"=>"Shannon Brown",
		"category"=>"Village",
		"download"=>"core_module",
		"requires"=>array(
			"drinks"=>"1.1|By John J. Collins, from the core download",
		),
		"settings"=>array(
			"Holiday Hotel - Settings,title",
			"iname"=>"Name of the inn,|Igloo Inn",
			"aname"=>"Name of raspberry drink,|Bloodbath",
			"aprice"=>"Cost of first drink,int|5",
			"bname"=>"Name of cherry coconut drink,|Corpse Cocktail",
			"bprice"=>"Cost of second drink,int|10",
			"suit1"=>"Name of bartender suit,|frog",
			"suit2"=>"Name of bartender suit,|cat",
			"suit3"=>"Name of bartender suit,|monkey",
			"suit4"=>"Name of bartender suit,|panda",
			"holidayhotelloc"=>"Where does the holiday hotel appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
	);
	return $info;
}

function holidayhotel_install(){
	module_addhook("changesetting");
	module_addhook("village");
	return true;
}

function holidayhotel_uninstall(){
	return true;
}

function holidayhotel_dohook($hookname,$args){
	global $session;
	switch($hookname){
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("holidayhotelloc")) {
				set_module_setting("holidayhotelloc", $args['new']);
			}
		}
	break;
	case "village":
		$iname=get_module_setting("iname");
		if ($session['user']['location'] == get_module_setting("holidayhotelloc")) {
			tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
			tlschema();
			addnav(array("%s",$iname),"runmodule.php?module=holidayhotel");
		}
		break;
	}
	return $args;
}

function holidayhotel_run() {
	global $session;

	$iname=get_module_setting("iname");
	page_header("%s",$iname);
	$op=httpget("op");
	$aname=get_module_setting("aname");
	$aprice=get_module_setting("aprice");
	$bname=get_module_setting("bname");
	$bprice=get_module_setting("bprice");
	$drunkeness=0;
	$maxdrunk=0;
	$drunkeness=get_module_pref("drunkeness","drinks");
	$maxdrunk=get_module_setting("maxdrunk","drinks");
	$gold=$session['user']['gold'];

	$suit1 = get_module_setting("suit1");
	$suit2 = get_module_setting("suit2");
	$suit3 = get_module_setting("suit3");
	$suit4 = get_module_setting("suit4");
	$suits = array($suit1,$suit2,$suit3,$suit4);

	$suits = translate_inline($suits);
	$suit = $suits[e_rand(0, count($suits)-1)];
	if($op==""){
		output("`&`c`bThe %s`b`c",$iname);
		output("`7`nYou enter a lively drinking hall, populated by dancing costumes of every imaginable kind.`n`n");
		output("The bartender is dressed as a %s.`n`n",$suit);
		output("`7He approaches and asks, `7\"`&What can I get you today?`7\"`n`n");
		if($drunkeness>$maxdrunk){
			output("The bartender stops abruptly, and takes a step back as he notices the smell of alcohol on your breath.`n`n");
			output("He smiles.`n`n");
			output("\"`&Don't you think you've had enough to drink for today?`7\"`n`n");
		}else{
			addnav(array("%s (%s gold`0)",$aname,$aprice),"runmodule.php?module=holidayhotel&op=a");
			addnav(array("%s (%s gold`0)",$bname,$bprice),"runmodule.php?module=holidayhotel&op=b");
		}
	}elseif($op=="a" && $gold>=$aprice){
		output("The bartender places a glass of red liquid in front of you.");
		output("There are small white chunks floating in the top, and you're suddenly apprehensive about it.`n`n");
		output("You take a careful sip, and then realize it's raspberry flavored, with marshmallows!`n`n");
		output("You feel `@healthy!`n`n");
		$drunkeness+=20;
		$session['user']['hitpoints']*=1.07;
		$session['user']['gold']-=$aprice;
		debuglog("spent $aprice gold on $aname in the holiday hotel.");
		apply_buff('buzz',array("name"=>array("%s Buzz",$aname),"rounds"=>10,"atkmod"=>1.02, "schema"=>"module-holidayhotel"));
		set_module_pref("drunkeness",$drunkeness,"drinks");
		addnav("B?Back to the Bar","runmodule.php?module=holidayhotel&op=");
	}elseif($op=="b"&& $gold>=$bprice){
		output("The bartender places a glass of white lumpy liquid in front of you.");
		output("There are small pink flecks in it, and you're suddenly apprehensive about it.`n`n");
		output("You take a careful sip, and then realize it's cherry and coconut flavored, and is delicious!`n`n");
		output("You feel `@healthy!`n`n");
		$drunkeness+=30;
		$session['user']['hitpoints']*=1.15;
		$session['user']['gold']-=$bprice;
		debuglog("spent $bprice gold on $bname in the holiday hotel.");
		apply_buff('buzz',array("name"=>array("%s Buzz",$bname),"rounds"=>10,"atkmod"=>1.04, "schema"=>"module-holidayhotel"));
		set_module_pref("drunkeness",$drunkeness,"drinks");
		addnav("B?Back to the Bar","runmodule.php?module=holidayhotel&op=");
	}else{
		output("The bartender just frowns.`n`n");
		output("Maybe you'll return when you have enough gold?");
	}
	villagenav();
	page_footer();
}

?>
