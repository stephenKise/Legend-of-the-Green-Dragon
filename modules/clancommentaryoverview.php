<?php

// this is a module that allows players who are allowed to moderate clans to
// get an overview of the recent commentary for all clans.

// note that deletesticks™ and bansticks™ will not be available directly
// through this module.  the code for them was deliberately left out because
// including them would duplicate a good chunk of core code into this module,
// and the functionality of the sticks in question should be easily accessible
// via the navs to each individual clan.

// should the code for the aforementioned sticks be moved into an easily
// accessible function in the future, then this module may be later altered
// to take advantage of the functions.

// credit goes to jt traub for final touches

function clancommentaryoverview_getmoduleinfo()
{
	$info = array(
		"name"=>"Clan Commentary Overview",
		"category"=>"Administrative",
		"author"=>"dying",
		"version"=>"0.1",
		"download"=>"core_module",
		"settings"=>array(
			"Clan Commentary Overview Settings, title",
			"numcomments"=>"Limit to number of recent comments retrieved per clan,range,0,50,1|20"
		)
	);
	return $info;
}

function clancommentaryoverview_install()
{
	module_addhook("header-moderate");

	return true;
}

function clancommentaryoverview_uninstall()
{
	return true;
}

function clancommentaryoverview_dohook($hookname, $args)
{
	global $session;

	switch ($hookname) {
	case "header-moderate":
		if ($session['user']['superuser'] & SU_MODERATE_CLANS)
		{
			addnav("Clan Halls");
			addnav("Clan Commentary Overview", "runmodule.php?module=clancommentaryoverview");
		}
		break;
	}
	return $args;
}

function clancommentaryoverview_run()
{
	page_header("Clan Commentary Overview");

	$numcomments = get_module_setting("numcomments");

	require_once("lib/superusernav.php");
	superusernav();
	addnav("C?Commentary Overview", "moderate.php");

	addnav("Clan Halls");
	$sql = "SELECT clanid, clanname, clanshort FROM " . db_prefix("clans") . " ORDER BY clanid";
	$res = db_query($sql);
	// since these are proper names, they shouldn't be translated
	tlschema("notranslate");
	while ($row=db_fetch_assoc($res)) {
		addnav(array("<%s> %s", $row['clanshort'], $row['clanname']),
			"moderate.php?area=clan-{$row['clanid']}");
	}
	tlschema();

	$sql = "SELECT clanid, clanname FROM " . db_prefix("clans") . " ORDER BY clanid";
	$res = db_query($sql);

	$firstclan=1;

	while ($clan=db_fetch_assoc($res)) {
		$cid = $clan['clanid'];
		$csql = "SELECT * FROM " . db_prefix("commentary") . " WHERE section='clan-" . $cid . "' ORDER BY postdate DESC LIMIT " . $numcomments;
		$cres = db_query($csql);

		if (db_num_rows($cres)>0) {

			if ($firstclan==1) {
				$firstclan = 0;

				addnav("", "runmodule.php?module=clancommentaryoverview");
				$buttonrefresh = translate_inline("Refresh");
				rawoutput("<form action='runmodule.php?module=clancommentaryoverview' method='post'>");
				rawoutput("<input type='submit' class='button' value='$buttonrefresh'>");
				rawoutput("</form>");
			}

			rawoutput("<hr>");

			$cname = $clan['clanname'];
			addnav("", "moderate.php?area=clan-" . $cid);
			rawoutput("<a href='moderate.php?area=clan-" . $cid . "'>");
			output_notl("`b`^%s`b`0", $cname);
			rawoutput("</a>");
			output_notl("`n");

			$carray = array();
			while ($ccomment=db_fetch_assoc($cres)) array_push($carray, $ccomment);
			while ($ccomment=array_pop($carray)) clancommentaryoverview_displaycomment($ccomment);
		}
	}

	page_footer();
}

function clancommentaryoverview_displaycomment($ccomment)
{
	$section = translate_inline($ccomment['section']);
	$time = clancommentaryoverview_formattedtime(strtotime($ccomment['postdate']));
	$author = clancommentaryoverview_formattedauthor($ccomment['author']);
	$comment = clancommentaryoverview_formattedcomment($ccomment['comment']);

	output("(%s) %s %s %s`0`n", $section, $time, $author, $comment);
}

	// the code here was mostly taken from the lib/commentary.php file,
	// although it may be more useful if the code was placed into one of
	// the files in the lib directory
function clancommentaryoverview_formattedtime($timestamp)
{
	global $session;

	if ($session['user']['prefs']['timestamp']==1) {
		if (!isset($session['user']['prefs']['timeformat'])) $session['user']['prefs']['timeformat'] = "[m/d h:ia]";
		$time = strtotime("+{$session['user']['prefs']['timeoffset']} hours",$timestamp);
		return (date("`7" . $session['user']['prefs']['timeformat'] . "`0",$time));
	} elseif ($session['user']['prefs']['timestamp']==2) {
		return ("`7(" . reltime($timestamp) . ")`0");
	} else {
		return "";
	}
}

function clancommentaryoverview_formattedauthor($acctid)
{
	$sql = "SELECT name, clanid, clanrank FROM " . db_prefix("accounts") . " WHERE acctid=" . $acctid;
	$res = db_query($sql);

	if (db_num_rows($res)>0) {
		$row = db_fetch_assoc($res);
		$tag = clancommentaryoverview_formattedclantag($row['clanid'], $row['clanrank']);
		if ($tag != "") $tag .= " ";
		return ($tag . "`&" . $row['name']);
	}

	return "";
}

function clancommentaryoverview_formattedclantag($clanid, $clanrank)
{
	if ($clanrank==0) return "";

	$sql = "SELECT clanid, clanshort FROM " . db_prefix("clans") . " WHERE clanid=" . $clanid;
	$res = db_query($sql);
	if ($row=db_fetch_assoc($res)) {
		$clanshort = $row['clanshort'];

		if ($clanrank==1) return ("`#<`2$clanshort`#>`0");
		if ($clanrank==2) return ("`^<`2$clanshort`^>`0");
		if ($clanrank==3) return ("`&<`2$clanshort`&>`0");

			// should be hit only if more clan rank values are added
		return ("`%<`2$clanshort`%>`0");

	} else return "";
}

function clancommentaryoverview_formattedcomment($comment)
{
	$emote = preg_replace("|^::|", "`&", $comment);
	if ($emote != $comment) return $emote;
	$emote = preg_replace("|^:|", "`&", $comment);
	if ($emote != $comment) return $emote;
	$emote = preg_replace("|^/me|", "`&", $comment);
	if ($emote != $comment) return $emote;

	return (sprintf("`3says, \"`#%s`3\"", $comment));
}

?>
