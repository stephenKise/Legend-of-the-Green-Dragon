<?php
// addnews ready
// mail ready

function holiday_xmas_getmoduleinfo(){
	$info = array(
		"name"=>"Holiday - Christmas",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"category"=>"Holiday Texts",
		"download"=>"core_module",
		"settings"=>array(
			"Christmas Holiday Settings,title",
			"start"=>"Activation start date (mm-dd)|12-15",
			"end"=>"Activation end date (mm-dd)|12-25",
		),
		"prefs"=>array(
			"Christmas Holiday User Preferences,title",
			"user_ignore"=>"Ignore Christmas Holiday text,bool|0",
		),
	);
	return $info;
}

function holiday_xmas_install(){
	module_addhook("holiday");
	return true;
}

function holiday_xmas_uninstall(){
	return true;
}

function holiday_xmas_munge($in) {
	$out = $in;
	$out = preg_replace("'([^[:alpha:]])ale([^[:alpha:]])'i","\\1egg nog\\2",$out);
	$out = preg_replace("'([^[:alpha:]])hi([^[:alpha:]])'i","\\1Ho Ho Ho\\2",$out);
	$out = preg_replace("'([^[:alpha:]])hello([^[:alpha:]])'i","\\1Ho Ho Ho\\2",$out);
	$out = preg_replace("'Forest'i","Winter Wonderland",$out);
	$out = preg_replace("'Green Dragon'i","Abominable Snowman",$out);
	$out = preg_replace("'Dragon'i","Abominable Snowman",$out);
	$out = str_replace("Hall o' Fame","Santa's List",$out);
	$out = str_replace("MightyE","FrostE",$out);
	$out = str_replace("Bluspring", "Rudolph", $out);
	$out = preg_replace("'Bank'i","Scrooge's House",$out);
	$out = preg_replace("'([^[:alpha:]])inn([^[:alpha:]])'i","\\1Igloo\\2",$out);
	$out = preg_replace("'garden'i","Ice Rink",$out);
	$out = str_replace("Merick","Santa",$out);
	$out = str_replace("bounty","Naughty Points",$out);
	$out = str_replace("the dead", "the coal recipients", $out);
	$out = preg_replace("'([^[:alpha:]])dead([^[:alpha:]])'i",
			"\\1a coal recipient\\2",$out);
	$out = preg_replace("'Village'i","North Pole",$out);
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$out = preg_replace("'$vname'i","North Pole",$out);
	$out = preg_replace("'Farmboy'i","Snowman",$out);
	$out = preg_replace("'Farmgirl'i","Snowgirl",$out);
	$out = preg_replace("'Pony'i", "Baby Reindeer", $out);
	$out = preg_replace("'Stallion'i", "Magic Reindeer", $out);
	$out = preg_replace("'Gelding'i", "Reindeer", $out);
	$out = preg_replace("'thick mold'i", "heavy snowfall", $out);
	return $out;
}

function holiday_xmas_dohook($hookname,$args){
	switch($hookname){
	case "holiday":
		if(get_module_pref("user_ignore")) break;
		$mytime = get_module_setting("start");
		list($smonth,$sday) = split("-", $mytime);
		$smonth = (int)$smonth;
		$sday = (int)$sday;
		$mytime = get_module_setting("end");
		list($emonth,$eday) = split("-", $mytime);
		$emonth = (int)$emonth;
		$eday = (int)$eday;

		$month = (int)date("m");
		$day = (int)date("d");
		if ($month >= $smonth && $month <= $emonth &&
				$day >= $sday && $day <= $eday) {
			$args['text'] = holiday_xmas_munge($args['text']);
		}
		break;
	}
	return $args;
}

function holiday_xmas_run(){

}
?>
