<?php
// addnews ready
// mail ready
// translator ready

/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 17 Sept 2004 */

require_once("lib/http.php");
require_once("lib/villagenav.php");

function caravan_getmoduleinfo(){
	$info = array(
		"name"=>"Caravan Module",
		"version"=>"1.2",
		"author"=>"Shannon Brown",
		"category"=>"Travel",
		"download"=>"core_module",
		"requires"=>array(
			"ghosttown"=>"1.0|By Shannon Brown, part of the core download",
			"moons"=>"1.0|By JT Traub, part of the core download",
		),
		"settings"=>array(
			"Caravan Settings,title",
			"activate"=>"Activation date for special event (mm-dd)|10-24",
			"days"=>"How many days will it be open for?,range,1,28,1|14",
			"moon"=>"Does it open if two moons are new?,bool|0",
			"ticketcost"=>"How much gold should ticket sellers charge?,int|5",
			"canvisit"=>"Can players visit?,bool|0",
			"canticket"=>"Can players get random tickets?,bool|0",
		),
		"prefs"=>array(
			"Caravan User Preferences,title",
			"fromvillage"=>"Which village did the player travel from?|Degolburg",
            "hasticket"=>"Does the player have a ticket today?,bool|0",
		)
	);
	return $info;
}

function caravan_install(){
	module_addhook("village");
	module_addhook("newday");
	module_addhook("newday-runonce");
	module_addhook("moon-cyclechange");
	return true;
}

function caravan_uninstall(){
	return true;
}

function caravan_update_status(){
	$canvisit = 0;
	$moon=get_module_setting("moon");

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

	if ($moon && !$canvisit) {
		// we only do this part if the $canvisit value is not already
		// true, because we don't want to override it to zero if
		// halloween is not a new moon.
		$moon1place=get_module_setting("moon1place","moons");
		$moon2place=get_module_setting("moon2place","moons");
		$moon3place=get_module_setting("moon3place","moons");
		$moon1cycle=get_module_setting("moon1cycle","moons");
		$moon2cycle=get_module_setting("moon2cycle","moons");
		$moon3cycle=get_module_setting("moon3cycle","moons");
		$moon1=get_module_setting("moon1","moons");
		$moon2=get_module_setting("moon2","moons");
		$moon3=get_module_setting("moon3","moons");

		if ($moon1 && ($moon1place < $moon1cycle*0.12)) $moon1isnew=1;
		if ($moon2 && ($moon2place < $moon2cycle*0.12)) $moon2isnew=1;
		if ($moon3 && ($moon3place < $moon3cycle*0.12)) $moon3isnew=1;
		// only one moon and it is new
		if (($moon1+$moon2+$moon3 == 1) &&
				($moon1isnew+$moon2isnew+$moon3isnew == 1))  {
			// set the allowed visit
			$canvisit = 1;
		}elseif ($moon1isnew+$moon2isnew+$moon3isnew > 1) {
			// We have at least two moons new
			// set the allowed visit
			$canvisit = 1;
		}
	}
	$isactive=is_module_active("ghosttown");
	if (!$isactive) {
		$canvisit=0; // nowhere to catch the caravan to
		$canticket = 0;
	}
	if ($canvisit) $canticket = 1;

	set_module_setting("canvisit",$canvisit);
	set_module_setting("canticket",$canticket);

	if (!$canvisit) {
		$vname = getsetting("villagename", LOCATION_FIELDS);
		// This is valid if the module is installed but deactivated
		$gname = get_module_setting("villagename", "ghosttown");
		// Okay, move anyone who got stuck in the ghosttown
		$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location='$gname'";
		db_query($sql);
	}
}

function caravan_dohook($hookname,$args){
    global $session;
	switch($hookname){
	case "newday-runonce":
		caravan_update_status();
		break;
	case "moon-cyclechange":
		caravan_update_status();
		break;
	case "newday":
		$hasticket = get_module_pref("hasticket");
		$hadticket = get_module_pref("hadticket");
		$gname = get_module_setting("villagename", "ghosttown");
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
		$gname = get_module_setting("villagename","ghosttown");
		$hasticket = get_module_pref("hasticket");
		$hadticket = get_module_pref("hadticket");
		$canvisit = get_module_setting("canvisit");

		if ($fromvillage != $gname &&
				$fromvillage != "world" &&
				$canvisit == 1 && $hasticket == 1) {
			// We are active at any village (not the world) except the ghost
			// village and only if you have a ticket and only if the caravan
			// is running.
			output("`n`7Clutching the ticket marked `@Admit One`7, you see others with similar day tickets, queueing for a ride on a nearby wagon.");
			output("A sign proclaims that it will be touring `%%s`7 today.`n",
					$gname);
			tlschema($args['schemas']['gatenav']);
			addnav($args["gatenav"]);
			tlschema();
			addnav(array(" ?Ride the Caravan to %s",$gname),"runmodule.php?module=caravan");
			set_module_pref("fromvillage",$fromvillage);
		}elseif ($session['user']['location'] == $gname &&
				($hasticket || $hadticket)) {
			// We allow anyone in Esoterra to get out of there, in case
			// the ticket was cancelled or the village removed.
			// so no check for $canvisit or $hasticket
			$fromvillage = get_module_pref("fromvillage");
			tlschema($args['schemas']['gatenav']);
			addnav($args["gatenav"]);
			tlschema();
			addnav(array("R?Ride the Caravan back to %s",$fromvillage),
					"runmodule.php?module=caravan&op=return");
		}
		break;
	}
	return $args;
}

function caravan_run(){
	global $session;
	$op = httpget("op");
	$gname = get_module_setting("villagename","ghosttown");
	$fromvillage = get_module_pref("fromvillage");

	page_header("Caravan");
	if ($op == "") {
		$village = getsetting("villagename", LOCATION_FIELDS);
		output("`&`c`bThe Ghost Town Caravan`b`c");
		output("`7You move forward into the queue, until a small man smiles at your ticket, and waves you aboard.");
		output("You place the ticket back in your purse, realizing that you'll need it on the return journey.");
		output("In the back of the wagon, excited villagers are discussing what the ghost town might be like.");
		output("You're rather interested to find out for yourself!`n`n");
		output("The ticket collector reminds all of the passengers that they will be returned to %s if they are foolhardy enough to fall asleep in %s when the tourist season ends in a few days.`n`n",$village,$gname);
		output("The wagon stops at several other villages, collecting more passengers, until finally you see a rusty sign among the weeds on the side of the road.");
		output("`n`n`%%s, Population 720.`n`n",$gname);
		output("`7You find this difficult to believe, because as you pass through the gates and into the old town, you see that everything seems broken, run down and long-abandoned.");
		addnav(array("E?Explore %s",$gname),"village.php");
		$session['user']['location'] = $gname;
	}elseif ($op == "return"){
		output("`&`c`bThe Ghost Town Caravan`b`c");
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
