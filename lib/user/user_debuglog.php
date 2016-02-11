<?php
if ($petition !=""){
	addnav("Navigation");
	addnav("Return to the petition","viewpetition.php?op=view&id=$petition");
}
$debuglog = db_prefix('debuglog');
$accounts = db_prefix('accounts');


// As mySQL cannot use two different indexes in a single query this query can take up to 25s on its own!
// This happens solely on larger debuglogs (where full table scans take quite long), smaller servers
// should not recognize a change.
// It may seem strange, but in this case two single queries are better!
// $sql = "SELECT count(id) AS c FROM $debuglog WHERE actor=$userid OR target=$userid";

$sql = "SELECT COUNT(id) AS c FROM $debuglog WHERE target=$userid";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$max = $row['c'];

$sql = "SELECT COUNT(id) AS c FROM $debuglog WHERE actor=$userid";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$max += $row['c'];

$start = (int)httpget('start');

$sql = "(
			SELECT $debuglog. * , a1.name AS actorname, a2.name AS targetname
				FROM $debuglog
				LEFT JOIN $accounts AS a1 ON a1.acctid = $debuglog.actor
				LEFT JOIN $accounts AS a2 ON a2.acctid = $debuglog.target
				WHERE $debuglog.actor = $userid
		) UNION (
			SELECT $debuglog. * , a2.name AS targetname, a1.name AS actorname
				FROM $debuglog
				LEFT JOIN $accounts AS a1 ON a1.acctid = $debuglog.actor
				LEFT JOIN $accounts AS a2 ON a2.acctid = $debuglog.target
				WHERE $debuglog.target = $userid
		)
		ORDER BY date DESC
		LIMIT $start,500";

$next = $start+500;
$prev = $start-500;
addnav("Operations");
addnav("Edit user info","user.php?op=edit&userid=$userid$returnpetition");
addnav("Refresh", "user.php?op=debuglog&userid=$userid&start=$start$returnpetition");
addnav("Debug Log");
if ($next < $max) {
	addnav("Next page","user.php?op=debuglog&userid=$userid&start=$next$returnpetition");
}
if ($start > 0) {
	addnav("Previous page",
			"user.php?op=debuglog&userid=$userid&start=$prev$returnpetition");
}
$result = db_query($sql);
$odate = "";
while ($row = db_fetch_assoc($result)) {
	$dom = date("D, M d",strtotime($row['date']));
	if ($odate != $dom){
		output_notl("`n`b`@%s`0`b`n", $dom);
		$odate = $dom;
	}
	$time = date("H:i:s", strtotime($row['date']))." (".reltime(strtotime($row['date'])).")";
	output_notl("`#%s (%s) `^%s - `&%s`7 %s`0", $row['field'], $row['value'], $time, $row['actorname'], $row['message']);
	if ($row['target']) {
		output(" \\-- Recipient = `\$%s`0", $row['targetname']);
	}
	output_notl("`n");
}
?>