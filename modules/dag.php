<?php
//addnews ready
// mail ready
// translator ready
require_once("lib/sanitize.php");
require_once("lib/datetime.php");
require_once("lib/http.php");

function dag_getmoduleinfo(){
	$info = array(
		"name"=>"Dag Durnick Bounties",
		"author"=>"Darrel Morrone<br>Updates by Andrew Senger, JT Traub, and Eric Stevens",
		"version"=>"1.3",
		"category"=>"Inn",
		"download"=>"core_module",
		"settings"=>array(
			"Dag Durnick Bounty Settings,title",
			"bountymin"=>"Minimum amount per level of target for bounty,int|50",
			"bountymax"=>"Maximum amount per level of target for bounty,int|200",
			"bountylevel"=>"Minimum player level for being a bounty target,int|3",
			"bountyfee"=>"Percentage of bounty kept by Dag Durnick,int|10",
			"maxbounties"=>"How many bounties can a person set per day,int|5"
		),
		"prefs"=>array(
			"Dag Durnick Bounty User Preferences,title",
			"bounties"=>"Bounties set today,int|0"
		)
	);
	return $info;
}

function dag_install(){
	require_once("modules/dag/install.php");
	$args = func_get_args();
	return call_user_func_array("dag_install_private",$args);
}

function dag_uninstall(){
	output("Dropping bounty table");
	$sql = "DROP TABLE IF EXISTS " . db_prefix("bounty");
	db_query($sql);
	return true;
}

function dag_dohook($hookname, $args){
	require_once("modules/dag/dohook.php");
	$args = func_get_args();
	return call_user_func_array("dag_dohook_private",$args);
}

function dag_run(){
	require_once("modules/dag/run.php");
	$args = func_get_args();
	return call_user_func_array("dag_run_private",$args);
}
?>
