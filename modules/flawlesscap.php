<?php

function flawlesscap_getmoduleinfo(){
	$info = array(
		"name"=>"Flawless Fight Cap",
		"author"=>"<a href='http://www.sixf00t4.com' target=_new>Sixf00t4</a> - hints by XChrisX",
		"version"=>"20050909",
		"description"=>"Limits the number of flawless fight rewards",
		"category"=>"Forest",
		"download"=>"core_module",
		"description"=>"Caps turn rewards from flawless fights.",
		"settings"=>array(
			"maxflaw"=>"How many Flawless wins allowed per day?,int|5",
		),
		"prefs"=>array(
			"flaw"=>"How many Flawless wins today?,int|0",
		),
	);
	return $info;
}

function flawlesscap_install(){
	module_addhook("battle-victory");
	module_addhook("newday");
	return true;
}

function flawlesscap_uninstall(){
	return true;
}

function flawlesscap_dohook($hookname, $args){

	switch($hookname) {
	case "battle-victory";
		global $options;
		static $runonce = false;
		if ($runonce !== false) break;
//		$runonce = true; // Don't want this hear, as we need to check every badguy
		if ($args['type'] == "forest" && (!isset($args['diddamage']) || $args['diddamage'] != 1)) {
			//flawless fight
			// We only want this run once per fight, even if flawless against multiple badguys
			$runonce = true;
			if(get_module_pref("flaw","flawlesscap") >=
					get_module_setting("maxflaw","flawlesscap")){
				$options['denyflawless'] = "`nYou have already received the maximum flawless fight rewards for today.`n`n";
			}else{
				increment_module_pref("flaw");
			}
		}
		break;
	case "newday";
		set_module_pref("flaw",0,"flawlesscap");
		break;
	}
	return $args;
}

function flawlesscap_run() {
}
?>
