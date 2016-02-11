<?php
/*
Points Wage
File:   pointswage.php
Author: Red Yates aka Deimos
Date:   03/10/2005

Requested by the jcp:
A module to give staff a wage of donator points.
*/

function pointswage_getmoduleinfo(){
	$info = array(
		"name"=>"Points Wage",
		"version"=>"1.0",
		"author"=>"`\$Red Yates",
		"category"=>"Administrative",
		"download"=>"core_module",
		"settings"=>array(
			"Points Wage Settings,title",
			"period"=>"How many game days between each payday,int|28",
			"day"=>"Day in period,int|0",
		),
		"prefs"=>array(
			"Points Wage Preferences,title",
			"wage"=>"Donation Points Wage,int|0",
			"reason"=>"Reason For Wage|wages"
		),
	);
	return $info;
}

function pointswage_install(){
	module_addhook("newday-runonce");
	module_addhook("validateprefs");
	module_addhook("validatesettings");
	return true;
}

function pointswage_uninstall(){
	return true;
}

function pointswage_dohook($hookname, $args){
	global $session;
	switch ($hookname){
	case "newday-runonce":
		$day=get_module_setting("day");
		$day++;
		if ($day<get_module_setting("period")){
			set_module_setting("day",$day);
		}else{
			set_module_setting("day",0);
			$sql="SELECT userid, value FROM ".db_prefix("module_userprefs")." WHERE modulename='pointswage' AND setting='wage' AND value <> 0";
			$result=db_query($sql);
			$count=db_num_rows($result);
			for ($i=0; $i<$count; $i++){
				$row=db_fetch_assoc($result);
				$acctid=$row['userid'];
				$wage=$row['value'];
				$sql="UPDATE ".db_prefix("accounts")." SET donation=donation+$wage WHERE acctid=$acctid";
				db_query($sql);
				$reason=get_module_pref("reason", false, $acctid);
				systemmail($acctid,"`QDonation Points Added`0","`QYou have received `^$wage`Q donation points for $reason.`0");
			}
		}
		break;
	case "validateprefs":
	case "validatesettings":
		if (!($session['user']['superuser'] & SU_EDIT_DONATIONS)) {
			$args['validation_error']="You do not have Donation Editor access.";
		}
		break;
	}
	return $args;
}

function pointswage_run(){
}

?>
