<?php
// addnews ready
// mail ready

function holiday_pirate_getmoduleinfo(){
	$info = array(
		"name"=>"Holiday - Talk like a Pirate day",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"category"=>"Holiday Texts",
		"download"=>"core_module",
		"settings"=>array(
			"Talk Like A Pirate Day Settings,title",
			"activate"=>"Activation date (mm-dd)|9-19",
		),
		"prefs"=>array(
			"Talk Like A Pirate Day User Preferences,title",
			"user_ignore"=>"Ignore Talk Like A Pirate text,bool|0",
		),
	);
	return $info;
}

function holiday_pirate_install(){
	module_addhook("holiday");
	return true;
}

function holiday_pirate_uninstall(){
	return true;
}

function holiday_pirate_munge($in) {
	$out = $in;
	$out = str_replace("you","ye",$out);
	$out = str_replace("You","Ye",$out);
	$out = str_replace("the ", "th' ", $out);
	$out = str_replace("The ", "Th' ", $out);
	$out = str_replace("your","yer",$out);
	$out = str_replace("Your","Yer",$out);
	$out = str_replace("It's", "It be",$out);
	$out = str_replace("it's", "it be",$out);
	if (e_rand(0,4) == 1) $out = str_replace("[^`]!",", har!",$out);
	$out = str_replace("ers ","erz ",$out);
	$out = str_replace("for", "fer", $out);
	$out = str_replace("my ", "me ", $out);
	$out = str_replace(" of ", " o' ", $out);
	$out = str_replace(" is ", " be ", $out);
	$out = preg_replace("'[^[:alpha:]]you[^[:alpha:]]'i"," yer scurvey self ",$out);
	$out = preg_replace("'([^ .,!?]+)ing '","\\1in' ",$out);
	$out = preg_replace("/ [s]{0,1}he /i"," that scalliwag ",$out);
	$out = str_replace(" and "," an' ",$out);
	$out = str_replace(" am ", " be ", $out);
	$out = str_replace(" says "," sez ",$out);
	$out = preg_replace("' to ([^ .,!?]+) the '", " t' be \\1in' the ", $out);
	$out = str_replace(" to ", " t' ", $out);
	if (e_rand(0,4) == 1)
		$out = str_replace(". ",", arr. ",$out);
	if (e_rand(0,4) == 1)
		$out = str_replace(", ",", matey, ",$out);
	if (e_rand(0,4) == 1)
		$out = str_replace(". ", ". Avast, ", $out);
	$out = str_replace("hello ", "ahoy ", $out);
	$out = str_replace("Hello ", "Ahoy ", $out);
	$out = preg_replace("'( |`.)(money|gold)( |`.)'", "\\1pieces o' eight\\3", $out);
	$out = preg_replace("'(Money|Gold) '", "Pieces o' eight ", $out);
	return $out;
}

function holiday_pirate_dohook($hookname,$args){
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
			$args['text'] = holiday_pirate_munge($args['text']);
		}
		break;
	}
	return $args;
}

function holiday_pirate_run(){

}
?>
