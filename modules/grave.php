<?php
function grave_getmoduleinfo(){
	$info = array(
		"name"=>"Traveller's Grave",
		"version"=>"1.1",
		"author"=>"Nightwind",
		"category"=>"Travel Specials",
		"download"=>"core_module",
		// This module is travel only, so it depends on cities
		"requires"=>array(
			"cities"=>"1.0|By Eric Steves, part of the core download",
		),
		"settings"=>array(
			"Traveller's Grave Module Settings,title",
			"minfavor"=>"Minimum amount of favor given,range,0,10,1|9",
			"maxfavor"=>"Maximum amount of favor given,range,1,25,1|11",
		),
	);
	return $info;
}

function grave_install(){
	module_addeventhook("travel",
			"return (is_module_active('cities')?100:0);");
	return true;
}

function grave_uninstall(){
	return true;
}

function grave_dohook($hookname,$args){
	return $args;
}

function grave_runevent($type,$from)
{
	global $session;
	$favorgain=e_rand(get_module_setting("minfavor"),
			get_module_setting("maxfavor"));

	$op = httpget('op');
	$session['user']['specialinc'] = "module:grave";
	if ($op==""){
		output("`2By the roadside you see the crudely marked grave of a dead traveller.`n`n");
		if ($session['user']['turns'] == 0) {
			output("`2You consider for a moment the forlorn grave, but know that you have not the time you need to wait here.`0");
		} else {
			output("`3Do you want to stop to pray?`0");
			addnav("`2Pray at the Grave`0",$from."op=pray");
		}
		addnav("`2Continue on the Trail`0",$from."op=go");
	}elseif ($op=="go") {
		output("`@Dragons `2wait for no ".($session['user']['sex']?"woman":"man").".`n");
		output("`^You continue on your way down the trail without another thought.`n`0");
		$session['user']['specialinc'] = "";
	}elseif ($op=="pray") {
		output("`2You kneel at the edge of the crude grave and offer a few words to the dead soul interred here.`n");
		output("`2As time passes you get the feeling that `4Ramius`2 is pleased with your gesture.`n");
		output("`)You gain %s favor with %s`).`0`n",$favorgain, getsetting("deathoverlord", '`$Ramius'));
		$session['user']['turns']--;
		$session['user']['deathpower']+=$favorgain;
		$session['user']['specialinc'] = "";
	}
}

function grave_run(){
}
?>
