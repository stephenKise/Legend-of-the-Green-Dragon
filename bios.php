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
$userId = httpget('userid');
$accountsPrefix = db_prefix('accounts');
if ($op=="block"){
	$sql = "UPDATE $accountsPrefix
        SET biotime = NULL
        WHERE acctid='$userId'";
	$subj = "Your bio has been blocked";
	$msg = "Your bio entry has been blocked by administrators. You can appeal via 'Petition for Help'.";
	systemmail($userId, $subj, $msg);
	db_query($sql);
}
if ($op=="unblock"){
	$sql = "UPDATE $accountsPrefix
        SET biotime = NOW()
        WHERE acctid='$userId'";
	$subj = "Your bio has been unblocked";
	$msg = "The system administrators unblocked your bio. You can update it in your Preferences.";
	systemmail($userId, $subj, $msg);
	db_query($sql);
}
$sql = "SELECT name, acctid, bio, biotime
    FROM $accountsPrefix 
    WHERE biotime IS NOT NULL
    ORDER BY biotime DESC
    LIMIT 100";
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
$sql = "SELECT name, acctid, bio, biotime
    FROM $accountsPrefix
    WHERE biotime IS NULL
    AND bio <> 'I am new here.'
    ORDER BY biotime DESC
    LIMIT 100";
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