<?php
// addnews ready
// mail ready
// translator ready

define("EXTLINK_NONE", 0);
define("EXTLINK_INDEX", 1);
define("EXTLINK_VILLAGE", 2);
define("EXTLINK_SHADES", 4);

function extlinks_getmoduleinfo(){
	$info = array(
		"name"=>"External Links",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"allowanonymous"=>true,
		"category"=>"General",
		"download"=>"core_module",
		"settings"=>array(
			"External Links Settings,title",
			"Link 1,title",
			"link1heading"=>"Nav heading for Link 1|Forums",
			"link1title"=>"Text for Link 1|LoGD Main Forums",
			"link1link"=>"URL for Link 1|http://lotgd.net/forum",
			"link1show"=>"Link 1 display locations,bitfield, ". 0xffffffff ."
				,".EXTLINK_INDEX.",Index page
				,".EXTLINK_VILLAGE.",Village
				,".EXTLINK_SHADES.",Shades|7",
			"Link 2,title",
			"link2heading"=>"Nav heading for Link 2|",
			"link2title"=>"Text for Link 2|",
			"link2link"=>"URL for Link 2|",
			"link2show"=>"Link 2 display locations,bitfield,". 0xffffffff ."
				,".EXTLINK_INDEX.",Index page
				,".EXTLINK_VILLAGE.",Village
				,".EXTLINK_SHADES.",Shades|0",
			"Link 3,title",
			"link3heading"=>"Nav heading for Link 3|",
			"link3title"=>"Text for Link 3|",
			"link3link"=>"URL for Link 3|",
			"link3show"=>"Link 3 display locations,bitfield,". 0xffffffff ."
				,".EXTLINK_INDEX.",Index page
				,".EXTLINK_VILLAGE.",Village
				,".EXTLINK_SHADES.",Shades|0",
			"Link 4,title",
			"link4heading"=>"Nav heading for Link 4|",
			"link4title"=>"Text for Link 4|",
			"link4link"=>"URL for Link 4|",
			"link4show"=>"Link 4 display locations,bitfield,". 0xffffffff ."
				,".EXTLINK_INDEX.",Index page
				,".EXTLINK_VILLAGE.",Village
				,".EXTLINK_SHADES.",Shades|0",
			"Link 5,title",
			"link5heading"=>"Nav heading for Link 5|",
			"link5title"=>"Text for Link 5|",
			"link5link"=>"URL for Link 5|",
			"link5show"=>"Link 5 display locations,bitfield,". 0xffffffff ."
				,".EXTLINK_INDEX.",Index page
				,".EXTLINK_VILLAGE.",Village
				,".EXTLINK_SHADES.",Shades|0"
		),
	);
	return $info;
}

function extlinks_install(){
	module_addhook("village");
	module_addhook("footer-shades");
	module_addhook("index");
	module_addhook("validatesettings");
	return true;
}

function extlinks_uninstall(){
	return true;
}

function extlinks_dohook($hookname,$args){
	$testloc = EXTLINK_NONE;
	switch($hookname){
	case "validatesettings":
		// Convert the bitfields back to numbers
		$val = $args['link1show'];
		if ($val != "") {
			$value = 0;
			while(list($k, $v)=each($val)) {
				if ($v) $value += (int)$k;
			}
			$args['link1show'] = $value;
		}
		$val = $args['link2show'];
		if ($val != "") {
			$value = 0;
			while(list($k, $v)=each($val)) {
				if ($v) $value += (int)$k;
			}
			$args['link2show'] = $value;
		}
		$val = $args['link3show'];
		if ($val != "") {
			$value = 0;
			while(list($k, $v)=each($val)) {
				if ($v) $value += (int)$k;
			}
			$args['link3show'] = $value;
		}
		$val = $args['link4show'];
		if ($val != "") {
			$value = 0;
			while(list($k, $v)=each($val)) {
				if ($v) $value += (int)$k;
			}
			$args['link4show'] = $value;
		}
		$val = $args['link5show'];
		if ($val != "") {
			$value = 0;
			while(list($k, $v)=each($val)) {
				if ($v) $value += (int)$k;
			}
			$args['link5show'] = $value;
		}
		break;
	case "village":
		$testloc = EXTLINK_VILLAGE;
		break;
	case "footer-shades":
		$testloc = EXTLINK_SHADES;
		break;
	case "index":
		$testloc = EXTLINK_INDEX;
		break;
	default: return $args;
	}
	if ($testloc == EXTLINK_NONE) return $args;
	for ($i = 1; $i <= 5; $i++) {
		$pref = "link$i";
		$loc = get_module_setting($pref."show");
		// This link isn't shown here.
		if (($loc & $testloc) == 0) continue;
		$head = get_module_setting($pref."heading");
		addnav($head);
		$title = get_module_setting($pref."title");
		$link = get_module_setting($pref."link");
		if ($title && $link) addnav($title, $link, false, true, "");
	}
	return $args;
}

function extlinks_run(){
}
?>
