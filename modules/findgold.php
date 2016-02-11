<?php
// translator ready
// mail ready
// addnews ready
function findgold_getmoduleinfo(){
	$info = array(
		"name"=>"Find Gold",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Find Gold Event Settings,title",
			"mingold"=>"Minimum gold to find (multiplied by level),range,0,50,1|10",
			"maxgold"=>"Maximum gold to find (multiplied by level),range,20,150,1|50"
		),
	);
	return $info;
}

function findgold_install(){
	module_addeventhook("forest", "return 100;");
	module_addeventhook("travel", "return 20;");
	return true;
}

function findgold_uninstall(){
	return true;
}

function findgold_dohook($hookname,$args){
	return $args;
}

function findgold_runevent($type,$link)
{
	global $session;
	$min = $session['user']['level']*get_module_setting("mingold");
	$max = $session['user']['level']*get_module_setting("maxgold");
	$gold = e_rand($min, $max);
	output("`^Fortune smiles on you and you find %s gold!`0", $gold);
	$session['user']['gold']+=$gold;
	debuglog("found $gold gold in the dirt");
}

function findgold_run(){
}
?>
