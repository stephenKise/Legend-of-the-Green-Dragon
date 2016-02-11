<?php
// translator ready
// addnews ready
// mail ready
require_once("lib/dbwrapper.php");
require_once("lib/e_rand.php");
require_once("lib/substitute.php");

function select_taunt() {
	global $session, $badguy;

	$sql = "SELECT taunt FROM " . db_prefix("taunts") .
		" ORDER BY rand(".e_rand() . ") LIMIT 1";

	$result = db_query($sql);
	if ($result) {
		$row = db_fetch_assoc($result);
		$taunt = $row['taunt'];
	} else {
		$taunt = "`5\"`6%w's mother wears combat boots`5\", screams %W.";
	}

	$taunt = substitute($taunt);
	return $taunt;
}

function select_taunt_array(){
	global $session, $badguy;

	$sql = "SELECT taunt FROM " . db_prefix("taunts") .
		" ORDER BY rand(".e_rand() . ") LIMIT 1";

	$result = db_query($sql);
	if ($result) {
		$row = db_fetch_assoc($result);
		$taunt = $row['taunt'];
	} else {
		$taunt = "`5\"`6%w's mother wears combat boots`5\", screams %W.";
	}

	$taunt = substitute_array($taunt);
	array_unshift($taunt, true, "taunts");
	return $taunt;
}
?>
