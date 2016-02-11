<?php
// translator ready
// addnews ready
// mail ready
function innchat_getmoduleinfo(){
	$info = array(
		"name"=>"Additional Inn Chat topics",
		"author"=>"JT Traub (as a demo for Anpera)",
		"version"=>"1.0",
		"category"=>"Inn",
		"download"=>"core_module",
	);
	return $info;
}

function innchat_install(){
	module_addhook("innchatter");
	module_addhook("namechange");
	module_addhook("dragonkill");
	return true;
}

function innchat_uninstall(){
	return true;
}

function innchat_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "innchatter":
		$id = $session['user']['acctid'];
		if (e_rand(1,2)==1){
			$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE locked=0 AND acctid != '$id' ORDER BY rand(".e_rand().") LIMIT 1";
		}else{
			$sql = "SELECT creaturename AS name FROM " . db_prefix("masters") . " WHERE 1 ORDER BY rand(".e_rand().") LIMIT 1";
		}
		// Only let this hit the database once every 10 minute if we're
		// using data caching.  Otherwise it could be expensive.  If they
		// hit it multiple times within ten minutes, it'll use the same
		// random name of player or master.  We'll invalidate the name when someone's name changes
		// for any reason.
		$res = db_query_cached($sql, "innchat-names");
		$row = db_fetch_assoc($res);
		// Give 2 out of X (currently 7 (5+these 2)) chances of hearing about
		// a player.
		$noplayers = translate_inline("loneliness in town");
		if ($row['name']=="") $row['name']=$noplayers;
		$args[] = $row['name'];
		$args[] = $row['name'];
		$args[] = translate_inline("Frequently Asked Questions");
		$args[] = translate_inline("dwarf tossing");
		$args[] = translate_inline("YOU");
		$args[] = getsetting("villagename", LOCATION_FIELDS);
		$args[] = translate_inline("today's weather");
		$args[] = translate_inline("the elementary discord of being"); // "Das elementare Zerwürfnis des Seins" no idea if that makes any sense in english. (Ok, it doesn't make sense in german too.) It's from a "Jägermeister" commercial spot :)
		break;
	case "namechange":
	case "dragonkill":
		// Someone just did a dragonkill or had their name changed.  Since it
		// it could have been this person, we'll just invalidate the cache
		invalidatedatacache("innchat-names");
		break;
	}

	return $args;
}

function innchat_runevent($type){
}

function innchat_run(){
}

?>
