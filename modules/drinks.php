<?php
// translator ready
// addnews ready
// mail ready
require_once("lib/e_rand.php");
require_once("lib/showform.php");
require_once("lib/http.php");
require_once("lib/buffs.php");

/*
 * Date:    Mar 07, 2004
 * Version: 1.0
 * Author:  JT Traub
 * Email:   jtraub@dragoncat.net
 * Purpose:	Provide basic drinks and drunkeness handling.
 *          Subsumes some of the functionality from the drinks module by
 *          John J. Collins (collinsj@yahoo.com)
 *
 * Date:    Mar 09, 2004
 * Version: 1.1
 * Purpose: Remove the 'activate' field
 */

function drinks_getmoduleinfo(){
	$info = array(
		"name"=>"Exotic Drinks",
		"author"=>"John J. Collins<br>Heavily modified by JT Traub",
		"category"=>"Inn",
		"download"=>"core_module",
		"settings"=>array(
			"Drink Module Settings,title",
			"hardlimit"=>"How many hard drinks can a user buy in a day?,int|3",
			"maxdrunk"=>array("How drunk before %s`0 won't serve you?,range,0,100,1|66", getsetting("barkeep", "`)Cedrik")),
		),
		"prefs"=>array(
			"Drink Module User Preferences,title",
			"drunkeness"=>"Drunkeness,range,0,100,1|0",
			"harddrinks"=>"How many hard drinks has the user bought today?,int|0",
			"canedit"=>"Has access to the drinks editor,bool|0",
			"noslur"=>"Don't slur speach when drunk,bool|0",
		),
		"version"=>"1.1"
	);
	return $info;
}

function drinks_install(){
	require_once("modules/drinks/install.php");
	$args = func_get_args();
	return call_user_func_array("drinks_install_private",$args);
}

function drinks_uninstall() {
	debug("Dropping table drinks");
	$sql = "DROP TABLE IF EXISTS " . db_prefix("drinks");
	db_query($sql);
	debug("Dropping objprefs related to drinks");
	$sql = "DELETE FROM " . db_prefix("module_objprefs") .
		" WHERE objtype='drinks'";
	db_query($sql);
	return true;
}

function drinks_dohook(){
	require_once("modules/drinks/dohook.php");
	$args = func_get_args();
	return call_user_func_array("drinks_dohook_private",$args);
}

function drinks_run(){
	require_once("modules/drinks/run.php");
	$args = func_get_args();
	return call_user_func_array("drinks_run_private",$args);
}


?>
