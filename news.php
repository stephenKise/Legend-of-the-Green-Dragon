<?php
// translator ready
// addnews ready
// mail ready
define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

tlschema("news");

modulehook("news-intercept",array());

if ((int)getsetting("expirecontent",180)>0){
	$sql = "DELETE FROM " . db_prefix("news") . " WHERE newsdate<'".date("Y-m-d H:i:s",strtotime("-".getsetting("expirecontent",180)." days"))."'";
	//echo $sql;
	db_query($sql);
}

if ($session['user']['loggedin']) checkday();
$newsperpage=50;

$offset = (int)httpget('offset');
$timestamp=strtotime((0-$offset)." days");
$sql = "SELECT count(newsid) AS c FROM " . db_prefix("news") . " WHERE newsdate='".date("Y-m-d",$timestamp)."'";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$totaltoday=$row['c'];
$page = (int)httpget('page');
if (!$page) $page=1;
$pageoffset = $page;
if ($pageoffset>0) $pageoffset--;
$pageoffset*=$newsperpage;
$sql = "SELECT * FROM " . db_prefix("news") . " WHERE newsdate='".date("Y-m-d",$timestamp)."' ORDER BY newsid DESC LIMIT $pageoffset,$newsperpage";
$result = db_query($sql);
page_header("LoGD News");
$date=date("D, M j, Y",$timestamp);

$pagestr = "";
if ($totaltoday > $newsperpage) {
	$pagestr = sprintf_translate("(Items %s - %s of %s)", $pageoffset+1,
			min($pageoffset+$newsperpage,$totaltoday), $totaltoday);
}

$sql2 = "SELECT " . db_prefix("motd") . ".*,name AS motdauthorname FROM " . db_prefix("motd") . " LEFT JOIN " . db_prefix("accounts") . " ON " . db_prefix("accounts") . ".acctid = " . db_prefix("motd") . ".motdauthor ORDER BY motddate DESC LIMIT 1";
$result2 = db_query_cached($sql2, "lastmotd");
while ($row = db_fetch_assoc($result2)) {
	require_once("lib/motd.php");
	require_once("lib/nltoappon.php");
	if ($row['motdauthorname']=="")
		$row['motdauthorname']="`@Green Dragon Staff`0";
	if ($row['motdtype']==0){
		motditem($row['motdtitle'], $row['motdbody'],$row['motdauthorname'], $row['motddate'], "");
	}else{
		pollitem($row['motditem'], $row['motdtitle'], $row['motdbody'],$row['motdauthorname'],$row['motddate'], false);
	}
}
output_notl("`n");
output("`c`b`!News for %s %s`0`b`c", $date, $pagestr);

while ($row = db_fetch_assoc($result)) {
	output_notl("`c`2-=-`@=-=`2-=-`@=-=`2-=-`@=-=`2-=-`0`c");
	if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
		$del = translate_inline("Del");
		rawoutput("[ <a href='superuser.php?op=newsdelete&newsid=".$row['newsid']."&return=".URLEncode($_SERVER['REQUEST_URI'])."'>$del</a> ]&nbsp;");
		addnav("","superuser.php?op=newsdelete&newsid={$row['newsid']}&return=".URLEncode($_SERVER['REQUEST_URI']));
	}
	tlschema($row['tlschema']);
	if ($row['arguments']>""){
		$arguments = array();
		$base_arguments = unserialize($row['arguments']);
		array_push($arguments,$row['newstext']);
		while (list($key,$val)=each($base_arguments)){
			array_push($arguments,$val);
		}
		$news = call_user_func_array("sprintf_translate",$arguments);
		rawoutput(tlbutton_clear());
	}else{
		$news = translate_inline($row['newstext']);
	}
	tlschema();
	output_notl($news."`n");
}
if (db_num_rows($result)==0){
	output_notl("`c`2-=-`@=-=`2-=-`@=-=`2-=-`@=-=`2-=-`0`c");
	output("`1`b`c Nothing of note happened this day.  All in all a boring day. `c`b`0");
}
output_notl("`c`2-=-`@=-=`2-=-`@=-=`2-=-`@=-=`2-=-`0`c");
if (!$session['user']['loggedin']) {
	addnav("Login Screen", "index.php");
} else if ($session['user']['alive']){
	villagenav();
}else{
	tlschema("nav");
	if($session['user']['sex'] == 1) {
		addnav("`!`bYou're dead, Jane!`b`0");
	} else {
		addnav("`!`bYou're dead, Jim!`b`0");
	}
	addnav("S?Land of Shades","shades.php");
	addnav("G?The Graveyard","graveyard.php");
	require_once("lib/extended-battle.php");
	suspend_companions("allowinshades", true);
	addnav("Log out","login.php?op=logout");
	tlschema();
}
addnav("News");
addnav("Previous News","news.php?offset=".($offset+1));
if ($offset>0){
	addnav("Next News","news.php?offset=".($offset-1));
}
if ($session['user']['loggedin'])
	addnav("Preferences","prefs.php");
addnav("About this game","about.php");

tlschema("nav");
if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
	addnav("Superuser");
	addnav(",?Comment Moderation","moderate.php");
}
if ($session['user']['superuser']&~SU_DOESNT_GIVE_GROTTO){
	addnav("Superuser");
	addnav("X?Superuser Grotto","superuser.php");
}
if ($session['user']['superuser'] & SU_INFINITE_DAYS){
	addnav("Superuser");
	addnav("/?New Day","newday.php");
}
tlschema();

addnav("","news.php");
if ($totaltoday>$newsperpage){
	addnav("Today's news");
	for ($i=0;$i<$totaltoday;$i+=$newsperpage){
		$pnum = $i/$newsperpage+1;
		if ($pnum == $page) {
			addnav(array("`b`#Page %s`0`b", $pnum),"news.php?offset=$offset&page=$pnum");
		} else {
			addnav(array("Page %s", $pnum),"news.php?offset=$offset&page=$pnum");
		}
	}
}

page_footer();
?>