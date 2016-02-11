<?php
// translator ready
// addnews ready
// mail ready

// New Hall of Fame features by anpera
// http://www.anpera.net/forum/viewforum.php?f=27

require_once("common.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

tlschema("hof");

$superusermask = SU_HIDE_FROM_LEADERBOARD;
$standardwhere = "(locked=0 AND (superuser & $superusermask) = 0)";

page_header("Hall of Fame");
checkday();

addnav("Other");
villagenav();
$playersperpage = 50;

$op = httpget('op');
if ($op == "") $op = "kills";
$subop = httpget('subop');
if ($subop == "") $subop = "most";

$sql = "SELECT count(acctid) AS c FROM " . db_prefix("accounts") . " WHERE $standardwhere";
$extra = "";
if ($op == "kills") {
	$extra = " AND dragonkills > 0";
} elseif ($op == "days") {
	$extra = " AND dragonkills > 0 AND bestdragonage > 0";
}
$result = db_query($sql.$extra);
$row = db_fetch_assoc($result);
$totalplayers = $row['c'];

$page = (int)httpget('page');
if ($page == 0) $page = 1;
$pageoffset = $page;
if ($pageoffset > 0) $pageoffset--;
$pageoffset *= $playersperpage;
$from = $pageoffset+1;
$to = min($pageoffset+$playersperpage, $totalplayers);
$limit = "$pageoffset,$playersperpage";

addnav("Warrior Rankings");
addnav("Dragon Kills", "hof.php?op=kills&subop=$subop&page=1");
addnav("Gold", "hof.php?op=money&subop=$subop&page=1");
addnav("Gems", "hof.php?op=gems&subop=$subop&page=1");
addnav("Charm", "hof.php?op=charm&subop=$subop&page=1");
addnav("Toughness", "hof.php?op=tough&subop=$subop&page=1");
addnav("Resurrections", "hof.php?op=resurrects&subop=$subop&page=1");
addnav("Dragon Kill Speed", "hof.php?op=days&subop=$subop&page=1");
addnav("Sorting");
addnav("Best", "hof.php?op=$op&subop=most&page=$page");
addnav("Worst", "hof.php?op=$op&subop=least&page=$page");
if ($totalplayers > $playersperpage) {
	addnav("Pages");
	for($i = 0; $i < $totalplayers; $i+= $playersperpage) {
		$pnum = ($i/$playersperpage+1);
		$min = ($i+1);
		$max = min($i+$playersperpage,$totalplayers);
		if ($page == $pnum) {
			addnav(array("`b`#Page %s`0 (%s-%s)`b", $pnum, $min, $max), "hof.php?op=$op&subop=$subop&page=$pnum");
		} else {
			addnav(array("Page %s (%s-%s)", $pnum, $min, $max), "hof.php?op=$op&subop=$subop&page=$pnum");
		}
	}
}

function display_table($title, $sql, $none=false, $foot=false,
		$data_header=false, $tag=false, $translate=false)
{
	global $session, $from, $to, $page, $playersperpage, $totalplayers;

	$title = translate_inline($title);
	if ($foot !== false) $foot = translate_inline($foot);
	if ($none !== false) $none = translate_inline($none);
	else $none = translate_inline("No players found.");
	if ($data_header !== false) {
		$data_header = translate_inline($data_header);
		reset ($data_header);
	}
	if ($tag !== false) $tag = translate_inline($tag);
	$rank = translate_inline("Rank");
	$name = translate_inline("Name");

	if ($totalplayers > $playersperpage) {
		output("`c`b`^%s`0`b `7(Page %s: %s-%s of %s)`0`c`n", $title, $page, $from, $to, $totalplayers);
	} else {
		output("`c`b`^%s`0`b`c`n", $title);
	}
	rawoutput("<table cellspacing='0' cellpadding='2' align='center'>");
	rawoutput("<tr class='trhead'>");
	output_notl("<td>`b$rank`b</td><td>`b$name`b</td>", true);
	if ($data_header !== false) {
		for ($i = 0; $i < count($data_header); $i++) {
			output_notl("<td>`b{$data_header[$i]}`b</td>", true);
		}
	}
	$result = db_query($sql);
	if (db_num_rows($result)==0){
		$size = ($data_header === false) ? 2 : 2+count($data_header);
		output_notl("<tr class='trlight'><td colspan='$size' align='center'>`&$none`0</td></tr>",true);
	} else {
		$i=-1;
		while ($row = db_fetch_assoc($result)) {
			$i++;
			if ($row['name']==$session['user']['name']){
				rawoutput("<tr class='hilight'>");
			} else {
				rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
			}
			output_notl("<td>%s</td><td>`&%s`0</td>",($i+$from), $row['name'], true);
			if ($data_header !== false) {
				for ($j = 0; $j < count($data_header); $j++) {
					$id = "data" . ($j+1);
					$val = $row[$id];
					if (isset($translate[$id]) &&
							$translate[$id] == 1 && !is_numeric($val)) {
						$val = translate_inline($val);
					}
					if ($tag !== false) $val = $val . " " . $tag[$j];
					output_notl("<td align='right'>%s</td>", $val, true);
				}
			}
			rawoutput("</tr>");
		}
	}
	rawoutput("</table>");
	if ($foot !== false) output_notl("`n`c%s`c", $foot);
}

if ($op=="days") {
	if ($subop == "least") {
		$order = "DESC";
		$meop = ">=";
	}else{
		$order = "ASC";
		$meop = "<=";
	}
} else {
	if ($subop == "least") {
		$order = "ASC";
		$meop = "<=";
	}else{
		$order = "DESC";
		$meop = ">=";
	}
}


$sexsel = "IF(sex,'`%Female`0','`!Male`0')";
$racesel = "IF(race!='0' and race!='',race,'".RACE_UNKNOWN."')";

if ($op=="money"){
	$sql = "SELECT name,(CAST(gold as signed)+goldinbank+round((((rand()*10)-5)/100)*(CAST(gold as signed)+goldinbank))) AS data1 FROM " . db_prefix("accounts") . " WHERE $standardwhere ORDER BY data1 $order, level $order, experience $order, acctid $order LIMIT $limit";
	$me = "SELECT count(acctid) AS count FROM ".db_prefix("accounts")." WHERE $standardwhere AND (goldinbank+CAST(gold as signed)+round((((rand()*10)-5)/100)*(goldinbank+CAST(gold as signed)))) $meop ".($session['user']['goldinbank'] + $session['user']['gold']);
	$adverb = "richest";
	if ($subop == "least") $adverb = "poorest";
	$title = "The $adverb warriors in the land";
	$foot = "(Gold Amount is accurate to +/- 5%)";
	$headers = array("Estimated Gold");
	$tags = array("gold");
	$table = array($title, $sql, false, $foot, $headers, $tags);
} elseif ($op == "gems") {
	$sql = "SELECT name FROM ". db_prefix("accounts") . " WHERE $standardwhere ORDER BY gems $order, level $order, experience $order, acctid $order LIMIT $limit";
	$me = "SELECT count(acctid) AS count FROM ".db_prefix("accounts")." WHERE $standardwhere AND gems $meop {$session['user']['gems']}";
	if ($subop == "least") $adverb = "least";
	else $adverb = "most";
	$title = "The warriors with the $adverb gems in the land";
	$table = array($title, $sql);
} elseif ($op=="charm"){
	$sql = "SELECT name,$sexsel AS data1, $racesel AS data2 FROM " . db_prefix("accounts") . " WHERE $standardwhere ORDER BY charm $order, level $order, experience $order, acctid $order LIMIT $limit";
	$me = "SELECT count(acctid) AS count FROM ".db_prefix("accounts")." WHERE $standardwhere AND charm $meop {$session['user']['charm']}";
	$adverb = "most beautiful";
	if ($subop == "least") $adverb = "ugliest";
	$title = "The $adverb warriors in the land.";
	$headers = array("Gender", "Race");
	$translate = array("data1"=>1, "data2"=>1);
	$table = array($title, $sql, false, false, $headers, false, $translate);
} elseif ($op=="tough"){
	$sql = "SELECT name,level AS data2 , $racesel as data1 FROM " . db_prefix("accounts") . " WHERE $standardwhere ORDER BY maxhitpoints $order, level $order, experience $order, acctid $order LIMIT $limit";
	$me = "SELECT count(acctid) AS count FROM ".db_prefix("accounts")." WHERE $standardwhere AND maxhitpoints $meop {$session['user']['maxhitpoints']}";
	$adverb = "toughest";
	if ($subop == "least") $adverb = "wimpiest";
	$title = "The $adverb warriors in the land";
	$headers = array("Race", "Level");
	$translate = array("data1"=>1);
	$table = array($title, $sql, false, false, $headers, false, $translate);
} elseif ($op=="resurrects"){
	$sql = "SELECT name,level AS data1 FROM " . db_prefix("accounts") . " WHERE $standardwhere ORDER BY resurrections $order, level $order, experience $order, acctid $order LIMIT $limit";
	$me = "SELECT count(acctid) AS count FROM ".db_prefix("accounts")." WHERE $standardwhere AND resurrections $meop {$session['user']['resurrections']}";
	$adverb = "most suicidal";
	if ($subop == "least") $adverb = "least suicidal";
	$title = "The $adverb warriors in the land";
	$headers = array("Level");
	$table = array($title, $sql, false, false, $headers, false);
} elseif ($op=="days") {
	$unk = translate_inline("Unknown");
	$sql = "SELECT name, IF(bestdragonage,bestdragonage,'$unk') AS data1 FROM " . db_prefix("accounts") . " WHERE $standardwhere $extra ORDER BY bestdragonage $order, level $order, experience $order, acctid $order LIMIT $limit";
	$me = "SELECT count(acctid) AS count FROM ".db_prefix("accounts")." WHERE $standardwhere $extra AND bestdragonage $meop {$session['user']['bestdragonage']}";
	$adverb = "fastest";
	if ($subop == "least") $adverb = "slowest";
	$title = "Heroes with the $adverb dragon kills in the land";
	$headers = array("Best Days");
	$none = "There are no heroes in the land.";
	$table = array($title, $sql, $none, false, $headers, false);
} else {
	$unk = translate_inline("Unknown");
	$sql = "SELECT name,dragonkills AS data1,level AS data2,'&nbsp;' AS data3, IF(dragonage,dragonage,'$unk') AS data4, '&nbsp;' AS data5, IF(bestdragonage,bestdragonage,'$unk') AS data6 FROM " . db_prefix("accounts") . " WHERE $standardwhere $extra ORDER BY dragonkills $order,level $order,experience $order, acctid $order LIMIT $limit";
	if ($session['user']['dragonkills']>0) $me = "SELECT count(acctid) AS count FROM ".db_prefix("accounts")." WHERE $standardwhere $extra AND dragonkills $meop {$session['user']['dragonkills']}";
	$adverb = "most";
	if ($subop == "least") $adverb = "least";
	$title = "Heroes with the $adverb dragon kills in the land";
	$headers = array("Kills", "Level", "&nbsp;", "Days", "&nbsp;", "Best Days");
	$none = "There are no heroes in the land.";
	$table = array($title, $sql, $none, false, $headers, false);
}

if (isset($table) && is_array($table)){
	call_user_func_array("display_table",$table);
	if (isset($me) && $me>"" && $totalplayers){
		$meresult = db_query($me);
		$row = db_fetch_assoc($meresult);
		$pct = round(100*$row['count']/$totalplayers, 0);
		if ($pct < 1) $pct = 1;
		output("`c`7You rank within around the top `&%s`7%% in this listing.`0`c",$pct);
	}
}

page_footer();
?>