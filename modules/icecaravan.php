<?php
// addnews ready
// mail ready
// translator ready

/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 17 Sept 2004 */
// ver 1.2 JT Traub, includes moons activation for the ghost town
// ver 1.3 for the ice town, 9th Dec 2004

require_once("lib/http.php");
require_once("lib/villagenav.php");

function icecaravan_getmoduleinfo(){
	$info = array(
		"name"=>"Ice Caravan",
		"version"=>"1.3",
		"author"=>"Shannon Brown",
		"category"=>"Travel",
		"download"=>"core_module",
		"requires"=>array(
			"icetown"=>"1.3|By Shannon Brown, part of the core download",
		),
		"settings"=>array(
			"Ice Caravan Settings,title",
			"activate"=>"Activation date for special event (mm-dd)|12-15",
			"days"=>"How many days will it be open for?,range,1,28,1|21",
			"ticketcost"=>"How much gold should ticket sellers charge?,int|5",
			"canvisit"=>"Can players visit?,bool|0",
			"canticket"=>"Can players get random tickets?,bool|0",
		),
		"prefs"=>array(
			"Ice Caravan User Preferences,title",
			"fromvillage"=>"Which village did the player travel from?|Degolburg",
            "hasticket"=>"Does the player have a ticket today?,bool|0",
		)
	);
	return $info;
}

function icecaravan_install(){
	module_addhook("village");
	module_addhook("newday");
	module_addhook("newday-runonce");
	return true;
}

function icecaravan_uninstall(){
	return true;
}

function icecaravan_update_status(){
	$canvisit = 0;

	// check for activation time
	$mytime = get_module_setting("activate");
	list($amonth,$aday) = split("-", $mytime);
	$amonth = (int)$amonth;
	$aday = (int)$aday;
	$month = (int)date("m");
	$day = (int)date("d");

	// Now figure out when it closes
	$d1 = date("Y") . "-" . $mytime;
	$days = get_module_setting("days");
	$opentill = date("m-d", strtotime($d1 . " +$days days"));
	list($smonth,$sday) = split("-", $opentill);

	$tstart = date("m-d", strtotime($d1 . " -4 days"));
	list($tmonth,$tday) = split("-", $tstart);
	$canticket = 0;
	if($tmonth == $amonth) {
		if ($month == $amonth &&
				$day >= $tday && $day <= $aday)
			$canticket = 1;
	} else {
		if (($month == $tmonth && $day >= $tday) ||
				($month == $amonth && $day <= $aday))
			$canticket = 1;
	}

	// Now, it's open if we're between those dates
	if ($amonth == $smonth) {
		if ($month == $amonth &&
				$day >= $aday && $day <= $sday)
			$canvisit = 1;
	} else {
		if (($month == $amonth && $day >= $aday) ||
				($month == $smonth && $day <= $sday))
			$canvisit = 1;
	}

	$isactive=is_module_active("icetown");
	$cantravel = false;
	if (!$isactive) {
		$canvisit=0; // nowhere to catch the caravan to
		$canticket = 0;
	} elseif (get_module_setting("allowtravel", "icetown")) {
		$cantravel = true;
	}
	if ($canvisit) $canticket = 1;

	set_module_setting("canvisit",$canvisit);
	set_module_setting("canticket",$canticket);

	if (!$canvisit && !$cantravel) {
		$vname = getsetting("villagename", LOCATION_FIELDS);
		// This is valid if the module is installed but deactivated
		$gname = get_module_setting("villagename", "icetown");
		// Okay, move anyone who got stuck in the icetown
		$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location='$gname'";
		db_query($sql);
	}
}

function icecaravan_dohook($hookname,$args){
    global $session;
	switch($hookname){
	case "newday-runonce":
		icecaravan_update_status();
		break;
	case "newday":
		$hasticket = get_module_pref("hasticket");
		$hadticket = get_module_pref("hadticket");
		$gname = get_module_setting("villagename", "icetown");
		$canvisit = get_module_setting("canvisit");
		if ($canvisit) set_module_pref("hasticket",0);
		if (($hasticket || $hadticket) &&
				$session['user']['location'] == $gname) {
			set_module_pref("hadticket", 1);
		} else {
			set_module_pref("hadticket", 0);
		}
		break;
	case "village":
		$fromvillage = $session['user']['location'];
		$gname = get_module_setting("villagename","icetown");
		$hasticket = get_module_pref("hasticket");
		$hadticket = get_module_pref("hadticket");
		$canvisit = get_module_setting("canvisit");

		if ($fromvillage != $gname &&
				$fromvillage != "world" &&
				$canvisit == 1 && $hasticket == 1) {
			// We are active at any village (not the world) except the ice
			// village and only if you have a ticket and only if the caravan
			// is running.
			output("`n`7Clutching the ticket marked `@Admit One`7, you see others with similar day tickets, queueing for a ride on a nearby wagon.");
			output("A sign proclaims that it will be touring `%%s`7 today.`n",
					$gname);
			tlschema($args['schemas']['gatenav']);
			addnav($args["gatenav"]);
			tlschema();
			addnav(array("R?Ride the Caravan to %s",$gname),
					"runmodule.php?module=icecaravan");
			set_module_pref("fromvillage",$fromvillage);
		}elseif ($session['user']['location'] == $gname &&
				($hasticket || $hadticket)) {
			// We allow anyone in the ice town to get out of there, in case
			// the ticket was cancelled or the village removed.
			// so no check for $canvisit or $hasticket
			$fromvillage = get_module_pref("fromvillage");
			tlschema($args['schemas']['gatenav']);
			addnav($args["gatenav"]);
			tlschema();
			addnav(array("R?Ride the Caravan back to %s",$fromvillage),
					"runmodule.php?module=icecaravan&op=return");
		}
		break;
	}
	return $args;
}

function icecaravan_run(){
	global $session;
	$op = httpget("op");
	$gname = get_module_setting("villagename","icetown");
	$fromvillage = get_module_pref("fromvillage");

	page_header("Caravan");
	output("`&`c`bThe %s Caravan`b`c",$gname);
	if ($op == "") {
		$village = getsetting("villagename", LOCATION_FIELDS);
		output("`7You move forward into the queue, until a small man smiles at your ticket, and waves you aboard.");
		output("You place the ticket back in your purse, realizing that you'll need it on the return journey.");
		output("In the back of the wagon, excited villagers are discussing what %s might be like.",$gname);
		output("You're rather interested to find out for yourself!`n`n");
		output("The ticket collector reminds all of the passengers that they will be returned to %s if they are foolhardy enough to fall asleep in %s when the tourist season ends in a few days.`n`n",$village,$gname);
		output("The wagon stops at several other villages, collecting more passengers, until finally you see a glittering sign on the side of the road.");
		output("`n`n`%Welcome to %s.`n`n",$gname);
		output("`7Passengers move excitedly in their seats.");
		addnav(array("E?Explore %s",$gname),"village.php");
		$session['user']['location'] = $gname;
	}elseif ($op == "return"){
		output("`7A demure ticket lady smiles at you, and waves you aboard.");
		output("You place the ticket back in your purse, knowing that it will still be valid if you decide to travel there again later today.");
		output("You sit quietly, thinking about all that you saw in %s.",$gname);
		output("The caravan stops in several towns, dropping passengers off, until you finally arrive in %s.",$fromvillage);
		addnav(array("R?Return to %s",$fromvillage),"village.php");
		$session['user']['location'] = $fromvillage;
		set_module_pref("hadticket", 0);
	}
	page_footer();
}

?>
