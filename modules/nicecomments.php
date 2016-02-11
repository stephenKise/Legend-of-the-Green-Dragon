<?php
// addnews ready
// mail ready

function nicecomments_getmoduleinfo(){
	$info = array(
		"name"=>"Nice Comments Comment Sanitizer",
		"author"=>"Eric Stevens",
		"download"=>"core_module",
		"version"=>"1.0",
		"category"=>"Commentary",
		"settings"=>array(
			"do_emotes"=>"Perform common emote replacements (such as hehe => /me chuckles),bool|1",
			"do_aol"=>"Perform common AOL chat replacements (such as ne1 => any one),bool|1",
			"do_caps"=>"Check for excessive capitalization,bool|1",
		)
	);
	return $info;
}

function nicecomments_install(){
	module_addhook_priority("commentary",25);
	return true;
}

function nicecomments_uninstall() {
	return true;
}

function nicecomments_dohook(){
	require_once("modules/nicecomments/dohook.php");
	$args = func_get_args();
	return call_user_func_array("nicecomments_dohook_private",$args);
}
?>
