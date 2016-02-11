<?php
// translator ready
// addnews ready
// mail ready
/* Village shell giver ver 1.0 */
/* 12th Sept 2004 => SaucyWench -at- gmail -dot- com */
/*
 * Intended as an optional dance partner to the Calle Trader, High City,
 * and ghost town
 */

require_once("lib/villagenav.php");
require_once("lib/http.php");

function calleman_getmoduleinfo(){
    $info = array(
        "name"=>"Village Shell Giver",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Village Specials",
        "download"=>"core_module",
    );
    return $info;
}

function calleman_test() {
	if (is_module_active("highcity")) return 10;
	if (is_module_active("caravan") &&
			get_module_setting("canticket", "caravan")) return 10;
	if (is_module_active("icecaravan") &&
			get_module_setting("canticket", "icecaravan")) return 10;
	return 0;
}

function calleman_install(){
	global $session;
	// only useful if high city is installed
	module_addeventhook("village",
			"require_once(\"modules/calleman.php\"); return calleman_test()");
	return true;
}

function calleman_uninstall(){
    return true;
}

function calleman_dohook($hookname,$args){
	return $args;
}

function calleman_runevent($type) {
    global $session;
	output("`7As you're chatting with the other villagers, a strange man approaches you and grabs your wrist.`n`n");
	$hasbrace = get_module_pref("hasbracelet", "crying");
	$giveticket = (is_module_active("caravan") && get_module_setting("canticket", "caravan"));
	$giveiceticket = (is_module_active("icecaravan") && get_module_setting("canticket", "icecaravan"));

	$calle = is_module_active("highcity");

	// temporarily used for free tickets to the ghost city
	$gotticket = get_module_pref("hasticket","caravan");
	$goticeticket = get_module_pref("hasticket", "icecaravan");

	if ($giveticket && !$gotticket){
		output("`&\"YES! It is you! YES!\"`n`n");
		output("`7He places a small piece of paper in your hand.");
		output("It reads, `@\"Admit One\".`n`n");
		output("`7You've no idea what it is for, but it might be useful, so you place it in your purse.");
		set_module_pref("hasticket",1,"caravan");
	}elseif ($giveiceticket && !$goticeticket){
		output("`&\"YES! It is you! YES!\"`n`n");
		output("`7He places a small piece of paper in your hand.");
		output("It reads, `@\"Admit One\".`n`n");
		output("`7You've no idea what it is for, but it might be useful, so you place it in your purse.");
		set_module_pref("hasticket",1,"icecaravan");
	}elseif ($hasbrace && (!$giveticket || $gotticket) &&
			(!$giveiceticket || $goticeticket) && $calle){
		output("He looks at your tiger tooth bracelet, the gift from the crying lady.`n`n");
		output("`&\"YES! One of those! YES!\"");
		output("`7He places a calle shell into your hand.");
		$callecount=get_module_pref("callecount","calletrader");
		$callecount++;
		set_module_pref("callecount", $callecount, "calletrader");
		// Do something with Matthias here??
		if (is_module_active("matthias")) {
		}
	}else{
		output("He looks at your wrist intently, raises his wide eyes to you, and says, `&\"NO! You aren't one of them!\"");
	}
	output("`n`n`7Without another word, he walks away, mumbling to himself.`n");
}

function calleman_run(){
}
?>
