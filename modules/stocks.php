<?php
// translator ready
// addnews ready
// mail ready

require_once("lib/http.php");

function stocks_getmoduleinfo(){
	$info = array(
		"name"=>"Village Stocks",
		"author"=>"Eric Stevens",
		"version"=>"1.0",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"Village Stocks Settings,title",
			"victim"=>"Who is in the stocks?|",
		),
	);
	return $info;
}

function stocks_install(){
	module_addhook("village-desc");
	module_addhook("dragonkill");
	module_addhook("namechange");
	return true;
}

function stocks_uninstall(){
	debug("Uninstalling module.");
	return true;
}

function stocks_dohook($hookname, $args) {
	global $REQUEST_URI;
	global $session;
	$stocks = get_module_setting("victim");
	$capital = getsetting("villagename", LOCATION_FIELDS);

	switch($hookname){
	case "village-desc":
		if ($session['user']['location']!=$capital) break;
		$op = httpget("op");
		if ($op == "stocks") {
			// Get rid of the op=stocks bit from the URI
			$REQUEST_URI = preg_replace("/[&?]?op=stocks/","",$REQUEST_URI);
			$_SERVER['REQUEST_URI'] = preg_replace("/[&?]?op=stocks/","",$_SERVER['REQUEST_URI']);
			if ($stocks == 0) {
				output("`n`^You head over to examine the stocks, and wondering how they work, you place your head and hands in the notches for them when SNAP, they clap shut, trapping you inside!`0`n");
				modulehook("stocksenter");
			} elseif ($stocks != $session['user']['acctid']) {
				$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$stocks'";
				$result = db_query_cached($sql, "stocks");
				$row = db_fetch_assoc($result);
				output("`n`^You head over to examine the stocks, and out of compassion, you help %s`^ out of the stocks.  ",$row['name']);
				output("Wondering how they got in there in the first place, you place your own head and hands in them when SNAP, they clap shut, trapping you inside! `0`n");
				modulehook("stocksenter");
			}
			set_module_setting("victim", $session['user']['acctid']);
			invalidatedatacache("stocks");
		} else {
			$examine = translate_inline("Examine Stocks");
			if ($stocks == 0) {
				output("`n`@Next to the stables is an empty set of stocks.");
				rawoutput(" [<a href='village.php?op=stocks'>$examine</a>]");
				output_notl("`0`n");
				addnav("", "village.php?op=stocks");
			} elseif ($stocks == $session['user']['acctid']) {
				output("`n`@You are now stuck in the stocks!  All around you, people gape and stare. Small children climb on your back, waving wooden swords, and declaring you to be the slain dragon, with them the victor.  This really grates you because you know you could totally take any one of these kids!  Nearby, artists are drawing caricatures of paying patrons pretending to throw various vegetables at you.`0`n");
			} else {
				$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$stocks'";
				$result = db_query_cached($sql, "stocks");
				$row = db_fetch_assoc($result);
				output("`n`@Next to the stables is a set of stocks in which `&%s`@ seems to have become stuck!",$row['name']);
				output_notl(" [");
				rawoutput("<a href='village.php?op=stocks'>$examine</a>");
				output_notl("]`0`n");
				addnav("", "village.php?op=stocks");
			}
		}
		break;
	case "dragonkill":
	case "namechange":
		if ($stocks == $session['user']['acctid']) {
			invalidatedatacache("stocks");
		}
		break;
	}
	return $args;
}

?>
