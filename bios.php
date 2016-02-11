<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/systemmail.php");
require_once("lib/http.php");

tlschema("bio");
check_su_access(SU_EDIT_COMMENTS);

$op = httpget('op');
$userid = httpget('userid');
if ($op=="block"){
	$sql = "UPDATE " . db_prefix("accounts") . " SET bio='`iBlocked for inappropriate usage`i',biotime='9999-12-31 23:59:59' WHERE acctid='$userid'";
	$subj = array("Your bio has been blocked");
	$msg = array("The system administrators have decided that your bio entry is inappropriate, so it has been blocked.`n`nIf you wish to appeal this decision, you may do so with the petition link.");
	systemmail($userid, $subj, $msg);
	db_query($sql);
}
if ($op=="unblock"){
	$sql = "UPDATE " . db_prefix("accounts") . " SET bio='',biotime='0000-00-00 00:00:00' WHERE acctid='$userid'";
	$subj = array("Your bio has been unblocked");
	$msg = array("The system administrators have decided to unblock your bio.  You can once again enter a bio entry.");
	systemmail($userid,$subj,$msg);
	db_query($sql);
}
$sql = "SELECT name,acctid,bio,biotime FROM " . db_prefix("accounts") . " WHERE biotime<'9999-12-31' AND bio>'' ORDER BY biotime DESC LIMIT 100";
$result = db_query($sql);
page_header("User Bios");
$block = translate_inline("Block");
output("`b`&Player Bios:`0`b`n");
$number=db_num_rows($result);
for ($i=0;$i<$number;$i++){
	$row = db_fetch_assoc($result);
	if ($row['biotime']>$session['user']['recentcomments'])
		rawoutput("<img src='images/new.gif' alt='&gt;' width='3' height='5' align='absmiddle'> ");
	output_notl("`![<a href='bios.php?op=block&userid={$row['acctid']}'>$block</a>]",true);
	addnav("","bios.php?op=block&userid={$row['acctid']}");
	output_notl("`&%s`0: `^%s`0`n", $row['name'], soap($row['bio']));
}
db_free_result($result);
require_once("lib/superusernav.php");
superusernav();

addnav("Moderation");

if ($session['user']['superuser'] & SU_EDIT_COMMENTS)
	addnav("Return to Comment Moderation","moderate.php");

addnav("Refresh","bios.php");
$sql = "SELECT name,acctid,bio,biotime FROM " . db_prefix("accounts") . " WHERE biotime>'9000-01-01' AND bio>'' ORDER BY biotime DESC LIMIT 100";
$result = db_query($sql);
output("`n`n`b`&Blocked Bios:`0`b`n");
$unblock = translate_inline("Unblock");
$number=db_num_rows($result);
for ($i=0;$i<$number;$i++){
	$row = db_fetch_assoc($result);

	output_notl("`![<a href='bios.php?op=unblock&userid={$row['acctid']}'>$unblock</a>]",true);
	addnav("","bios.php?op=unblock&userid={$row['acctid']}");
	output_notl("`&%s`0: `^%s`0`n", $row['name'], soap($row['bio']));
}
db_free_result($result);
page_footer();
?>