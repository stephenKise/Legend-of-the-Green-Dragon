<?php
// addnews ready
// translator ready
// mail ready
/* Village Klutz ver 1.0 12th Sept 2004 => SaucyWench -at- gmail -dot- com */

function klutz_getmoduleinfo(){
    $info = array(
        "name"=>"Village Klutz",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Village Specials",
        "download"=>"core_module",
		"prefs"=>array(
			"Klutz User Preferences,title",
			"gotgem"=>"Has player received a gem today?,bool|0",
		),
    );
    return $info;
}

function klutz_install(){
	module_addhook("newday");
	global $session;
	module_addeventhook("village",
			"return (max(1,(200-\$session['user']['dragonkills'])/2));");
    return true;
}

function klutz_uninstall(){
    return true;
}

function klutz_dohook($hookname,$args){
    global $session;
	switch($hookname){
    case "newday":
		set_module_pref("gotgem",0);
		break;
    }
	return $args;
}

function klutz_runevent($type) {
    global $session;
	output("`7While you're minding your own business, a lady plows headlong into you.`n`n");
	output("`&\"Oh! Oh, I'm terribly sorry! I mustn't have been watching where I was going...\"`n`n");
	output("`7She scrambles on the ground, trying to collect all the things she has dropped.");
	output("You help her gather her belongings.`n`n");
	output("`^She is most grateful for your help!`n`n");
	if (get_module_pref("gotgem") == 0 && e_rand(1, 4) == 1) {
		output("`7As a thank you, she hands you a `5gem`7!");
		$session['user']['gems']++;
		set_module_pref("gotgem", 1);
	}
}

function klutz_run(){
}
?>
