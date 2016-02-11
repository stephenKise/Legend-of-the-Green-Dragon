<?php
// translator ready
// addnews ready
// mail ready
function inncoupons_getmoduleinfo(){
	$info = array(
		"name"=>"Inn Coupons",
		"author"=>"Eric Stevens",
		"version"=>"1.0",
		"category"=>"Lodge",
		"download"=>"core_module",
		"settings"=>array(
			"Inn Coupons Module Settings,title",
			"cost"=>"How many donator points needed for ten free stays?,int|30",
		),
		"prefs"=>array(
			"Inn Coupons User Preferences,title",
			"availablestays"=>"How many inn stays are available?,int|0",
		),
	);
	return $info;
}

function inncoupons_install(){
	module_addhook("lodge");
	module_addhook("pointsdesc");
	module_addhook("innrooms");
	return true;
}
function inncoupons_uninstall(){
	return true;
}

function inncoupons_dohook($hookname,$args){
	global $session;
	$cost = get_module_setting("cost");
	switch($hookname){
	case "pointsdesc":
		$args['count']++;
		$format = $args['format'];
		$str = translate("The cost for ten free stays at the inn is %s points");
		$str = sprintf($str, $cost);
		output($format, $str, true);
		break;
	case "lodge":
		// If they have less than what they need just ignore them
		addnav(array("Inn Coupons (%s points)", $cost),
				"runmodule.php?module=inncoupons&op=innstays");
		break;
	case "innrooms":
		$config = unserialize($session['user']['donationconfig']);
		if (!is_array($config)) $config = array();
		if (isset($config['innstays']) && $config['innstays']) {
			set_module_pref("availablestays", $config['innstays']);
			unset($config['innstays']);
			$session['user']['donationconfig'] = serialize($config);
		}
		$stays = get_module_pref("availablestays");
		if ($stays > 10) {
			addnav(array("Show him your coupons for %s inn stays", $stays),
					"runmodule.php?module=inncoupons&op=room");
		} elseif ($stays > 1) {
			addnav(array("Show him your coupon for %s inn stays", $stays),
					"runmodule.php?module=inncoupons&op=room");
		} elseif ($stays == 1) {
			addnav(array("Show him your coupon for %s inn stay", $stays),
					"runmodule.php?module=inncoupons&op=room");
		}
		break;
	}
	return $args;
}

function inncoupons_run(){
	require_once("lib/sanitize.php");
	global $session;
	$op = httpget("op");

	$iname = getsetting("innname", LOCATION_INN);
	$cost = get_module_setting("cost");
	$config = unserialize($session['user']['donationconfig']);
	if (!is_array($config)) $config = array();
		if (isset($config['innstays']) && $config['innstays']) {
		set_module_pref("availablestays", $config['innstays']);
		unset($config['innstays']);
		$session['user']['donationconfig'] = serialize($config);
	}
	if ($op == "room") {
		$num = get_module_pref("availablestays");
		$num--;
		set_module_pref("availablestays", $num);
		$session['user']['loggedin'] = 0;
		$session['user']['location']=$iname;
		$session['user']['boughtroomtoday'] = 1;
		$session['user']['restorepage']="inn.php?op=strolldown";
		saveuser();
		$session=array();
		redirect("index.php");
	} elseif ($op=="innstays"){
		page_header("Hunter's Lodge");
		output("`7J. C. Petersen turns to you. \"`&Ten free nights in %s will cost %s points,`7\" he says.", $iname, $cost);
		output("\"`&Will this suit you?`7\"`n`n");
		addnav("Confirm Inn Stays");
		addnav("Yes", "runmodule.php?module=inncoupons&op=innconfirm");
		addnav("No", "lodge.php");
	}elseif ($op=="innconfirm"){
		page_header("Hunter's Lodge");
		addnav("L?Return to the Lodge","lodge.php");
		$pointsavailable = $session['user']['donation'] -
			$session['user']['donationspent'];
		if($pointsavailable >= $cost){
			output("`7J. C. Petersen gives you a card that reads \"Coupon: Good for ten free stays at %s\"", $iname);
			$num = get_module_pref("availablestays");
			$num += 10;
			set_module_pref("availablestays", $num);
			$session['user']['donationspent'] += $cost;
		} else {
			output("`7J. C. Petersen looks down his nose at you.");
			output("\"`&I'm sorry, but you do not have the %s points required to purchase the coupons. Please return when you do and I'll be happy to sell them to you.`7\"", $cost);
		}
	}
	page_footer();
}
?>
