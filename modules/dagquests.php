<?php

require_once("lib/http.php");
require_once("lib/villagenav.php");

function dagquests_getmoduleinfo(){
	$info = array(
		"name"=>"Dags Quests",
		"version"=>"1.1",
		"author"=>"`%Sneakabout`^",
		"category"=>"Quest",
		"download"=>"core_module",
		"prefs"=>array(
			"dkrep"=>"This is a measure of how much the player has done for Dag this DK.,int|0",
			"permrep"=>"This is a more permanent measure of reputation.,int|0",
        ),
	);
	return $info;
}

function dagquests_install(){
	module_addhook("dagnav");
	module_addhook("dragonkilltext");
	return true;
}

function dagquests_uninstall(){
	return true;
}

function dagquests_dohook($hookname,$args){
	global $session;
	switch ($hookname) {
	case "dagnav":
		addnav("Bounties");
		addnav("Ask About Special Bounties",
				"runmodule.php?module=dagquests&op=askquest");
		break;
	case "dragonkilltext":
		set_module_pref("dkrep",0,"dagquests");
		break;
	}
	return $args;
}

function dagquests_runevent($type) {
}

function dagquests_alterpermrep($amount) {
	global $session;
	// We'll see if we want all this maths.
	$permrep=get_module_pref("permrep","dagquests");
	if (!$amount) $amount=1;
	/*dagquests_alterrep($amount);
	do {
		if (abs($permrep)>=100) {
			$permrep+=$amount/100;
			if (abs($permrep)<100) {
				if ($amount<0) {
					$amount=(($permrep-100)*100)+1;
					$permrep=99;
				} else {
					$amount=(($permrep+100)*100)-1;
					$permrep=-99;
				}
			} else $amount=0;
		} elseif (abs($permrep)>=10) {
			$permrep+=$amount/10;
			if (abs($permrep)>100) {
				if ($amount<0) {
					$amount=(100-abs($permrep))*10;
					$permrep=-100;
				} else {
					$amount=(abs($permrep)-100)*10;
					$permrep=100;
				}
			} elseif (abs($permrep)<10) {
				if ($amount<0) {
					$amount=(($permrep-10)*10)+1;
					$permrep=9;
				} else {
					$amount=(($permrep+10)*10)-1;
					$permrep=-9;
				}
			} else $amount=0;
		} else {
			$permrep+=$amount;
			if (abs($permrep)>10) {
				if ($amount<0) {
					$amount=10-abs($permrep);
					$permrep=-10;
				} else {
					$amount=abs($permrep)-10;
					$permrep=10;
				}
			} else $amount=0;
		}
	} while ($amount);*/
	$permrep+=$amount;
	set_module_pref("permrep",round($permrep),"dagquests");
}

function dagquests_alterrep($amount) {
	global $session;
	$dkrep=get_module_pref("dkrep","dagquests");
	// Similarly, I'll comment this out for now.
	if (!$amount) $amount=1;
	/*modulehook("merchantrep");
	do {
		if (abs($dkrep)>=10) {
			$dkrep+=$amount/10;
			if (abs($dkrep)<10) {
				if ($amount<0) {
					$amount=(($dkrep-10)*10)+1;
					$dkrep=9;
				} else {
					$amount=(($dkrep+10)*10)-1;
					$dkrep=-9;
				}
			} else $amount=0;
		} else {
			$dkrep+=$amount;
			if (abs($dkrep)>10) {
				if ($amount<0) {
					$amount=10-abs($dkrep);
					$dkrep=-10;
				} else {
					$amount=abs($dkrep)-10;
					$dkrep=10;
				}
			} else $amount=0;
		}
	} while ($amount);*/
	$dkrep+=$amount;
	set_module_pref("dkrep",round($dkrep),"dagquests");
}

function dagquests_run(){
	global $session;
	$op = httpget('op');

	switch($op){
	case "askquest":
		$iname = getsetting("innname", LOCATION_INN);
		page_header($iname);
		rawoutput("<span style='color: #9900FF'>");
		output_notl("`c`b");
		output($iname);
		output_notl("`b`c");
		output("`3You lean over the table to Dag to inquire if there are any jobs for you to do.");
		$quest=modulehook("dagquests",array("questoffer"=>0));
		if (!$quest['questoffer'])
			output("He shakes his head gruffly, and points you towards the Forest.");
		addnav("I?Return to the Inn","inn.php");
		break;
	}
	page_footer();
}
?>
