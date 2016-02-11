<?php
// translator ready
// addnews ready
// mail ready

// Written by Christian Rutsch

require_once("common.php");
require_once("lib/http.php");

check_su_access(SU_EDIT_CONFIG);

tlschema("gamelog");

page_header("Game Log");
addnav("Navigation");
require_once("lib/superusernav.php");
superusernav();

$category = httpget('cat');
if ($category > "") {
	$cat = "&cat=$category";
	$sqlcat = "WHERE ".db_prefix("gamelog").".category = '$category'";
} else {
	$cat='';
	$sqlcat='';
}

$sql = "SELECT count(logid) AS c FROM ".db_prefix("gamelog")." $sqlcat";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$max = $row['c'];

$start = (int)httpget('start');
$sql = "SELECT ".db_prefix("gamelog").".*, ".db_prefix("accounts").".name AS name FROM ".db_prefix("gamelog")." LEFT JOIN ".db_prefix("accounts")." ON ".db_prefix("gamelog").".who = ".db_prefix("accounts").".acctid $sqlcat LIMIT $start,500";
$next = $start+500;
$prev = $start-500;
addnav("Operations");
addnav("Refresh", "gamelog.php?start=$start$cat");
if ($category > "") addnav("View all", "gamelog.php");
addnav("Game Log");
if ($next < $max) {
	addnav("Next page","gamelog.php?start=$next$cat");
}
if ($start > 0) {
	addnav("Previous page", "gamelog.php?start=$prev$cat");
}
$result = db_query($sql);
$odate = "";
$categories = array();

$i=0;
while ($row = db_fetch_assoc($result)) {
	$dom = date("D, M d",strtotime($row['date']));
	if ($odate != $dom){
		output_notl("`n`b`@%s`0`b`n", $dom);
		$odate = $dom;
	}
	$time = date("H:i:s", strtotime($row['date']))." (".reltime(strtotime($row['date'])).")";
	output_notl("`7(%s) %s `7(`&%s`7)", $row['category'], $row['message'], $row['name']);
	if (!isset($categories[$row['category']]) && $category == "") {
		addnav("Operations");
		addnav(array("View by `i%s`i", $row['category']), "gamelog.php?cat=".$row['category']);
		$categories[$row['category']] = 1;
	}
	output_notl("`n");
}

page_footer();

?>