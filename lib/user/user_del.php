<?php
$sql = "SELECT name from " . db_prefix("accounts") . " WHERE acctid='$userid'";
$res = db_query($sql);
require_once("lib/charcleanup.php");
char_cleanup($userid, CHAR_DELETE_MANUAL);
while ($row = db_fetch_assoc($res)) {
	addnews("`#%s was unmade by the gods.", $row['name'], true);
	debuglog("deleted user" . $row['name'] . "'0");
}
$sql = "DELETE FROM " . db_prefix("accounts") . " WHERE acctid='$userid'";
db_query($sql);
output( db_affected_rows()." user deleted.");
?>