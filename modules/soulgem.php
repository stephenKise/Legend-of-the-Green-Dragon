<?php
// addnews ready
// mail ready
// translator ready

function soulgem_getmoduleinfo(){
	$info = array(
		"name"=>"Soul Gems",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"category"=>"Graveyard Specials",
		"download"=>"core_module",
	);
	return $info;
}

function soulgem_install(){
	module_addeventhook("graveyard", "return 100;");
	return true;
}

function soulgem_uninstall(){
	return true;
}

function soulgem_dohook($hookname,$args){
	return $args;
}

function soulgem_runevent($type)
{
	global $session;
	output("`^As you wander through the eerie graveyard, you see a `\$red glimmer `^from the ground ahead.`n");
	output("Bending down, you pick up a small eerily glowing gem.`n");
	$session['user']['gravefights']++;
	output("`&Your soul feels as if its capacity for torment has been increased.`n");
	$session['user']['deathpower']+=e_rand(1, 5);
	output("`&You feel that %s`& is pleased.`n`0", getsetting("deathoverlord", '`$Ramius'));
}

function soulgem_run(){
}
?>
