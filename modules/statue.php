<?php
// translator ready
// addnews ready
// mail ready

require_once("lib/http.php");

function statue_getmoduleinfo(){
	$info = array(
		"name"=>"Village Statue",
		"author"=>"Eric Stevens",
		"version"=>"1.0",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"Village Statue Settings,title",
			"hero"=>"Who is the statue of?|",
			"showonindex"=>"Should the last dragonkill be shown on the home page?,bool|1",
		),
	);
	return $info;
}

function statue_install(){
	module_addhook("village-desc");
	module_addhook("dragonkill");
	module_addhook("namechange");
	module_addhook("index");
	return true;
}

function statue_uninstall(){
	debug("Uninstalling module.");
	return true;
}

function statue_dohook($hookname, $args) {
	global $REQUEST_URI;
	global $session;
	$capital = getsetting("villagename", LOCATION_FIELDS);
	$hero = get_module_setting("hero");

	switch($hookname){
	case "village-desc":
		if ($session['user']['location']!=$capital) break;
		if ($hero == 0) {
			output("`n`@The people wandering past periodically stop to admire a statue of the ancient hero, `&MightyE`@.`0`n");
		} else {
			$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$hero'";
			$result = db_query_cached($sql, "lasthero");
			$row = db_fetch_assoc($result);
			output("`n`@The inhabitants of %s are busy erecting a statue for their newest hero, `&%s`@ on the only statue pedestal around.  The remains of the statue that had stood there before lie in such ruins around the pedestal that it is no longer recognizable.`0`n",$session['user']['location'],$row['name']);
		}
		break;
	case "index":
		if (!get_module_setting("showonindex")) break;
		$heroname = "MightyE";
		if ($hero != 0) {
			$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$hero'";
			$result = db_query_cached($sql, "lasthero");
			$row = db_fetch_assoc($result);
			$heroname = $row['name'];
		}
		output("`@The most recent hero of the realm is: `&%s`0`n`n",$heroname);
		break;
	case "dragonkill":
		set_module_setting("hero", $session['user']['acctid']);
		invalidatedatacache("lasthero");
		break;
	case "namechange":
		if ($hero == $session['user']['acctid']) {
			invalidatedatacache("lasthero");
		}
		break;
	}
	return $args;
}

?>
