<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/dhms.php");

tlschema("stats");

check_su_access(SU_EDIT_CONFIG);

page_header("Stats");
require_once("lib/superusernav.php");
superusernav();
//addnav("Refresh the stats","stats.php");
addnav("Stats Types");
addnav("Totals & Averages","stats.php?op=stats");
addnav("Top Referers","stats.php?op=referers");
addnav("Logon Graph","stats.php?op=graph");

$op = httpget("op");

if ($op=="stats" || $op==""){
	$sql = "SELECT sum(gentimecount) AS c, sum(gentime) AS t, sum(gensize) AS s, count(acctid) AS a FROM " . db_prefix("accounts");
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	output("`b`%For existing accounts:`b`n");
	output("`@Total Accounts: `^%s`n",number_format($row['a']));
	output("`@Total Hits: `^%s`n",number_format($row['c']));
	output("`@Total Page Gen Time: `^%s`n",dhms($row['t']));
	output("`@Total Page Gen Size: `^%sb`n",number_format($row['s']));
	output("`@Average Page Gen Time: `^%s`n",dhms($row['t']/$row['c'],true));
	output("`@Average Page Gen Size: `^%s`n",number_format($row['s']/$row['c']));
}elseif ($op=="referers"){
	output("`n`%`bTop Referers:`b`0`n");
	rawoutput("<table border='0' cellpadding='2' cellspacing='1' bgcolor='#999999'>");
	$name = translate_inline("Name");
	$refs = translate_inline("Referrals");
	rawoutput("<tr class='trhead'><td><b>$name</b></td><td><b>$refs</b></td></tr>");
	$sql = "SELECT count(*) AS c, acct.acctid,acct.name AS referer FROM " . db_prefix("accounts") . " INNER JOIN " . db_prefix("accounts") . " AS acct ON acct.acctid = " . db_prefix("accounts") . ".referer WHERE " . db_prefix("accounts") . ".referer>0 GROUP BY " . db_prefix("accounts") . ".referer DESC ORDER BY c DESC";
	$result = db_query($sql);
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
		output_notl("`@{$row['referer']}`0");
		rawoutput("</td><td>");
		output_notl("`^{$row['c']}:`0  ");
		$sql = "SELECT name,refererawarded FROM " . db_prefix("accounts") . " WHERE referer = ${row['acctid']} ORDER BY acctid ASC";
		$res2 = db_query($sql);
		$number2=db_num_rows($res2);
		for ($j = 0; $j < $number2; $j++) {
			$r = db_fetch_assoc($res2);
			output_notl(($r['refererawarded']?"`&":"`$") . $r['name'] . "`0");
			if ($j != $number2-1) output_notl(",");
		}
		rawoutput("</td></tr>");
	}
	rawoutput("</table>");
}elseif($op=="graph"){
	$sql = "SELECT count(acctid) AS c, substring(laston,1,10) AS d FROM " . db_prefix("accounts") . " GROUP BY d DESC ORDER BY d DESC";
	$result = db_query($sql);
	output("`n`%`bDate accounts last logged on:`b");
	rawoutput("<table border='0' cellpadding='0' cellspacing='0'>");
	$class="trlight";
	$odate=date("Y-m-d");
	$j=0;
	$cumul = 0;
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		$diff = (strtotime($odate)-strtotime($row['d']))/86400;
		for ($x=1;$x<$diff;$x++){
			//if ($j%7==0) $class=($class=="trlight"?"trdark":"trlight");
			//$j++;
			$class=(date("W",strtotime("$odate -$x days"))%2?"trlight":"trdark");
			rawoutput("<tr class='$class'><td>".date("Y-m-d",strtotime("$odate -$x days"))."</td><td>&nbsp;&nbsp;</td><td>0</td><td>&nbsp;&nbsp;</td><td align='right'>$cumul</td></tr>");
		}
	//	if ($j%7==0) $class=($class=="trlight"?"trdark":"trlight");
	//	$j++;
		$class=(date("W",strtotime($row['d']))%2?"trlight":"trdark");
		$cumul+=$row['c'];
		rawoutput("<tr class='$class'><td>{$row['d']}</td><td>&nbsp;&nbsp;</td><td><img src='images/trans.gif' width='{$row['c']}' border='1' height='5'>{$row['c']}</td><td>&nbsp;&nbsp;</td><td align='right'>$cumul</td></tr>");
		$odate = $row['d'];
	}
	rawoutput("</table>");
}
page_footer();
?>