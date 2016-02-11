<?php
// translator ready
// addnews ready
// mail ready

/* Offering Special  */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 20th Nov 2004 */

// default settings add average 6 charm points per 10 gems spent

require_once("lib/villagenav.php");
require_once("lib/http.php");

function offering_getmoduleinfo(){
	$info = array(
		"name"=>"Offering Special",
		"version"=>"1.0",
		"author"=>"Shannon Brown",
		"category"=>"Village Specials",
		"download"=>"core_module",
		"prefs"=>array(
			"Offering Special User Preferences,title",
			"seen"=>"Seen special today?,bool|0",
		)
	);
	return $info;
}

function offering_install(){
	module_addhook("newday");
	module_addeventhook("village","\$seen=get_module_pref(\"seen\", \"offering\");return (\$seen>5?0:10);");
	return true;
}

function offering_uninstall(){
	return true;
}

function offering_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "newday":
		set_module_pref("seen",0);
		break;
	}
	return $args;
}

function offering_runevent($type) {
	global $session;
	$session['user']['specialinc'] = "module:offering";
	$seen=get_module_pref("seen");
	$amt=round(max(1,$session['user']['dragonkills']*10)*$session['user']['level']*(max(1,5000-$session['user']['maxhitpoints']))/20000);

	$op = httpget('op');
	if ($op == "") {
		output("`7While you are listening to others chatting, a bizarrely-dressed woman approaches with an outstretched hand. `n`n");
		output("\"`&For the offering!!! `^%s `&gold!`7\"",$amt);
		$seen++;
		set_module_pref("seen",$seen);
		addnav(array("Give her %s gold",$amt),"village.php?op=shop");
		addnav("Walk away","village.php?op=nope");
	}elseif($op=="nope"){
		output("`7You decide not to give any gold to this strange woman.`n");
		$session['user']['specialinc'] = "";
	}elseif($session['user']['gold']<$amt){
		output("`7The woman stares at your hand.`n`n");
		output("\"`&No no no no no!!! He would not be pleased!!!`7\"`n`n");
		output("`7Without another word, she walks away.`n");
		$session['user']['specialinc'] = "";
	}else{
		output("`7You hand her `^%s`7 gold, and she lifts her head up, looks intently at something above her that only she can see, and whispers, `&\"%s!`&\" `7with apparent urgency.`n`n",$amt, getsetting("deathoverlord", '`$Ramius'));
		output("`7Without another word she scurries off, a determined look on her face, and a purpose in her stride.`n`n");
		if ($session['user']['dragonkills']>30) {
			$session['user']['deathpower']+=10;
		}else{
			$session['user']['deathpower']+=15;
		}
		$session['user']['gold']-=$amt;
	}
}

?>
