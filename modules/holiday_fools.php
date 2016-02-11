<?php
// addnews ready
// mail ready

function holiday_fools_getmoduleinfo(){
	$info = array(
		"name"=>"Holiday - April Fool's day",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"category"=>"Holiday Texts",
		"download"=>"core_module",
		"settings"=>array(
			"April Fool's Holiday Settings,title",
			"activate"=>"Activation date (mm-dd)|4-1",
		),
		"prefs"=>array(
			"April Fool's Holiday User Preferences,title",
			"user_ignore"=>"Ignore April Fools holiday text,bool|0",
		),
	);
	return $info;
}

function holiday_fools_install(){
	module_addhook("holiday");
	return true;
}

function holiday_fools_uninstall(){
	return true;
}

function holiday_fools_munge($in) {
	$out = $in;
	$out = str_replace(". ",". Bork bork. ",$out);
	$out = str_replace(", ",", bork, ",$out);
	$out = str_replace(" h"," hoor",$out);
	$out = str_replace(" v"," veer",$out);
	$out = str_replace("g ","gen ",$out);
	$out = str_replace(" p"," pere",$out);
	$out = str_replace(" qu"," quee",$out);
	$out = str_replace("n ","nen ",$out);
	$out = str_replace("e ","eer ",$out);
	$out = str_replace("s ","ses ",$out);
	return $out;
}

function holiday_fools_dohook($hookname,$args){
	switch($hookname){
	case "holiday":
		if(get_module_pref("user_ignore")) break;
		$mytime = get_module_setting("activate");
		list($amonth,$aday) = split("-", $mytime);
		$amonth = (int)$amonth;
		$aday = (int)$aday;
		$month = (int)date("m");
		$day = (int)date("d");
		if ($month == $amonth && $day == $aday) {
			$args['text'] = holiday_fools_munge($args['text']);
		}
		break;
	}
	return $args;
}

function holiday_fools_run(){

}
?>
