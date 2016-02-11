<?php
// addnews ready
// translator ready
// mail ready
define("ALLOW_ANONYMOUS",true);
define("OVERRIDE_FORCED_NAV",true);
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/nltoappon.php");
require_once("lib/http.php");
require_once("lib/motd.php");

tlschema("motd");

$op = httpget('op');
$id = httpget('id');

addcommentary();
popup_header("LoGD Message of the Day (MoTD)");

if ($session['user']['superuser'] & SU_POST_MOTD) {
	$addm = translate_inline("Add MoTD");
	$addp = translate_inline("Add Poll");
	rawoutput(" [ <a href='motd.php?op=add'>$addm</a> | <a href='motd.php?op=addpoll'>$addp</a> ]<br/><br/>");
}

if ($op=="vote"){
	$motditem = httppost('motditem');
	$choice = httppost('choice');
	$sql = "DELETE FROM " . db_prefix("pollresults") . " WHERE motditem='$motditem' AND account='{$session['user']['acctid']}'";
	db_query($sql);
	$sql = "INSERT INTO " . db_prefix("pollresults") . " (choice,account,motditem) VALUES ('$choice','{$session['user']['acctid']}','$motditem')";
	db_query($sql);
	invalidatedatacache("poll-$motditem");
	header("Location: motd.php");
	exit();
}
if ($op == "add" || $op == "addpoll" || $op == "del")  {
	if ($session['user']['superuser'] & SU_POST_MOTD) {
		if ($op == "add") motd_form($id);
		elseif ($op == "addpoll") motd_poll_form($id);
		elseif ($op == "del") motd_del($id);
	} else {
		if ($session['user']['loggedin']){
			$session['user']['experience'] =
				round($session['user']['experience']*0.9,0);
			addnews("%s was penalized for attempting to defile the gods.",
					$session['user']['name']);
			output("You've attempted to defile the gods.  You are struck with a wand of forgetfulness.  Some of what you knew, you no longer know.");
			saveuser();
		}
	}
}
if ($op=="") {
	$count = getsetting("motditems", 5);
	$newcount = httpget("newcount");
	if (!$newcount || !httppost('proceed')) $newcount=0;
	/*
	motditem("Beta!","Please see the beta message below.","","", "");
	*/
	$m = httpget("month");
	if ($m > ""){
		$sql = "SELECT " . db_prefix("motd") . ".*,name AS motdauthorname FROM " . db_prefix("motd") . " LEFT JOIN " . db_prefix("accounts") . " ON " . db_prefix("accounts") . ".acctid = " . db_prefix("motd") . ".motdauthor WHERE motddate >= '{$m}-01' AND motddate <= '{$m}-31' ORDER BY motddate DESC";
		$result = db_query_cached($sql,"motd-$m");
	}else{
		$sql = "SELECT " . db_prefix("motd") . ".*,name AS motdauthorname FROM " . db_prefix("motd") . " LEFT JOIN " . db_prefix("accounts") . " ON " . db_prefix("accounts") . ".acctid = " . db_prefix("motd") . ".motdauthor ORDER BY motddate DESC limit $newcount,".($newcount+$count);
		if ($newcount=0) //cache only the last x items
			$result = db_query_cached($sql,"motd");
			else
			$result = db_query($sql);
	}
	while ($row = db_fetch_assoc($result)) {
		if (!isset($session['user']['lastmotd']))
			$session['user']['lastmotd']=0;
		if ($row['motdauthorname']=="")
			$row['motdauthorname']="`@Green Dragon Staff`0";
		if ($row['motdtype']==0){
			motditem($row['motdtitle'], $row['motdbody'],
					$row['motdauthorname'], $row['motddate'],
					$row['motditem']);
		}else{
			pollitem($row['motditem'], $row['motdtitle'], $row['motdbody'],
					$row['motdauthorname'],$row['motddate'],
					$row['motditem']);
		}
	}
	/*
	motditem("Beta!","For those who might be unaware, this website is still in beta mode.  I'm working on it when I have time, which generally means a couple of changes a week.  Feel free to drop suggestions, I'm open to anything :-)","","", "");
	*/

	$result = db_query("SELECT mid(motddate,1,7) AS d, count(*) AS c FROM ".db_prefix("motd")." GROUP BY d ORDER BY d DESC");
	$row = db_fetch_assoc($result);
	rawoutput("<form action='motd.php' method='GET'>");
	output("MoTD Archives:");
	rawoutput("<select name='month' onChange='this.form.submit();' >");
	rawoutput("<option value=''>--Current--</option>");
	while ($row = db_fetch_assoc($result)){
		$time = strtotime("{$row['d']}-01");
		$m = translate_inline(date("M",$time));
		rawoutput ("<option value='{$row['d']}'".(httpget("month")==$row['d']?" selected":"").">$m".date(", Y",$time)." ({$row['c']})</option>");
	}
	rawoutput("</select>".tlbutton_clear());
	rawoutput("<input type='hidden' name='newcount' value='".($count+$newcount)."'>");
	rawoutput("<input type='submit' value='&gt;' name='proceed'  class='button'>");
	rawoutput(" <input type='submit' value='".translate_inline("Submit")."' class='button'>");
	rawoutput("</form>");

	commentdisplay("`n`@Commentary:`0`n", "motd");
}

$session['needtoviewmotd']=false;

$sql = "SELECT motddate FROM " . db_prefix("motd") ." ORDER BY motditem DESC LIMIT 1";
$result = db_query_cached($sql, "motddate");
$row = db_fetch_assoc($result);
$session['user']['lastmotd']=$row['motddate'];

popup_footer();
?>