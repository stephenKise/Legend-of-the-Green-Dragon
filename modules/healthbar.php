<?php
// addnews ready
// mail ready
// translator ready

function healthbar_getmoduleinfo(){
	$info = array(
		"name"=>"Health Bar",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"category"=>"Stat Display",
		"download"=>"core_module",
		"settings"=>array(
			"Health Bar Module Settings,title",
			"showcurrent"=>"Show current health number,bool|1",
			"showmax"=>"Show max health (only if current ),bool|1",
			"showbar"=>"Show health level as a bar,bool|1",
		)
	);
	return $info;
}

function healthbar_install(){
	module_addhook("charstats");
	return true;
}

function healthbar_uninstall(){
	return true;
}

function healthbar_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "charstats":
		if ($session['user']['alive']) {
			$cur = $session['user']['hitpoints'];
			$realmax = $session['user']['maxhitpoints'];
			$stat = "Hitpoints";
			$cur_adjustment = check_temp_stat("hitpoints",1);
			$max_adjustment = check_temp_stat("maxhitpoints",1);
		} else {
			$cur = $session['user']['soulpoints'];
			$realmax = $session['user']['level'] * 5 + 50;
			$stat = "Soulpoints";
			$cur_adjustment = check_temp_stat("soulpoints",1);
			$max_adjustment = "";
		}
		if ($cur > $realmax) $max = $cur;
		else $max = $realmax;

		$pct = round($cur / $max * 100, 0);
		$nonpct = 100-$pct;
		if ($pct > 100) {
			$pct = 100;
			$nonpct = 0;
		}
		if ($pct < 0) {
			$pct = 0;
			$nonpct = 100;
		}
		if ($pct > 60) {
			if ($session['user']['alive']) $color = "#00ff00";
			else $color = "#dddddd";
			$ccode = "`@";
		} elseif ($pct > 25) {
			if ($session['user']['alive']) $color = "#ffff00";
			else $color = "#666666";
			$ccode = "`^";
		} else {
			if ($session['user']['alive']) $color = "#ff0000";
			else $color = "#880000";
			$ccode = "`$";
		}
		$hicode = "`&";
		if (!$session['user']['alive']) {
			$ccode = "`7";
		}

		$showcur = get_module_setting("showcurrent");
		$showmax = get_module_setting("showmax");
		$showbar = get_module_setting("showbar");
		$new = "";
		if (!$showcur && !$showbar) $new="`b`\$hidden`b";
		if ($showcur) $new .= $ccode . $cur . $cur_adjustment;
		if ($showcur && $showmax) $new .= "`0/`&$realmax`0" . $max_adjustment;
		if ($showbar) {
			if ($showcur) $new .= "<br />";
			$new .= "<table style='border: solid 1px #000000' bgcolor='#777777' cellpadding='0' cellspacing='0' width='70' height='5'><tr><td width='$pct%' bgcolor='$color'></td><td width='$nonpct%'></td></tr></table>";
		}
		setcharstat("Vital Info", $stat, $new);
		break;
	}
	return $args;
}

function healthbar_run(){

}
?>
